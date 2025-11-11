<?php

namespace DirectoristSimpleAffiliate\Forms;

use DirectoristSimpleAffiliate\Core\AffiliateManager;

/**
 * Handle affiliate registration form processing
 */
class RegistrationForm
{
    /**
     * Process form submission
     *
     * @return array Result with status and message
     */
    public static function process()
    {
        // Debug: Log that form processing started
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DSA: Form processing started');
        }

        // Verify nonce
        // if (!isset($_POST['dsa_registration_nonce']) || !wp_verify_nonce($_POST['dsa_registration_nonce'], 'dsa_registration_form')) {
        //     if (defined('WP_DEBUG') && WP_DEBUG) {
        //         error_log('DSA: Nonce verification failed');
        //     }
        //     return [
        //         'success' => false,
        //         'message' => __('Security check failed. Please try again.', 'directorist-simple-affiliate')
        //     ];
        // }

        // Validate required fields
        $errors = self::validate_fields($_POST);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode('<br>', $errors)
            ];
        }

        $affiliate_manager = AffiliateManager::get_instance();
        $is_logged_in = is_user_logged_in();
        $user_id = null;

        if ($is_logged_in) {
            // User is logged in - use existing user
            $user_id = get_current_user_id();

            // Check if user already has affiliate status
            if ($affiliate_manager->is_affiliate($user_id)) {
                return [
                    'success' => false,
                    'message' => __('You have already submitted an affiliate application.', 'directorist-simple-affiliate')
                ];
            }
        } else {
            // User is not logged in - create new user
            $email = sanitize_email($_POST['dsa_email']);
            $full_name = sanitize_text_field($_POST['dsa_full_name']);

            // Check if email already exists
            if (email_exists($email)) {
                return [
                    'success' => false,
                    'message' => __('An account with this email already exists. Please log in first.', 'directorist-simple-affiliate')
                ];
            }

            // Generate username from email
            $username = sanitize_user(substr($email, 0, strpos($email, '@')), true);
            $username = self::generate_unique_username($username);

            // Generate random password
            $password = wp_generate_password(12, false);

            // Create user
            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                return [
                    'success' => false,
                    'message' => $user_id->get_error_message()
                ];
            }

            // Update user display name
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $full_name,
                'first_name' => $full_name,
            ]);

            // Send welcome email with password
            self::send_welcome_email($email, $username, $password, $full_name);
        }

        // Prepare payment email based on payment method
        $payment_email = '';
        if ($_POST['dsa_payment_method'] === 'PayPal' && !empty($_POST['dsa_paypal_email'])) {
            $payment_email = sanitize_email($_POST['dsa_paypal_email']);
        }

        // Prepare affiliate registration data
        $affiliate_data = [
            'status' => 'pending',
            'payment_email' => $payment_email,
            'payment_method' => sanitize_text_field($_POST['dsa_payment_method']),
            'website' => isset($_POST['dsa_website']) ? esc_url_raw($_POST['dsa_website']) : '',
            'phone' => isset($_POST['dsa_phone']) ? sanitize_text_field($_POST['dsa_phone']) : '',
            'promotion_method' => isset($_POST['dsa_promotion_method']) ? sanitize_textarea_field($_POST['dsa_promotion_method']) : '',
        ];

        // Add payment method specific data
        if ($_POST['dsa_payment_method'] === 'PayPal' && isset($_POST['dsa_paypal_email'])) {
            $affiliate_data['paypal_email'] = sanitize_email($_POST['dsa_paypal_email']);
        }
        if ($_POST['dsa_payment_method'] === 'Bank Transfer' && isset($_POST['dsa_bank_details'])) {
            $affiliate_data['bank_details'] = sanitize_textarea_field($_POST['dsa_bank_details']);
        }

        // Register affiliate using AffiliateManager
        $result = $affiliate_manager->register_affiliate($user_id, $affiliate_data);

        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DSA: AffiliateManager register_affiliate result: ' . print_r($result, true));
        }

        if (is_wp_error($result)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('DSA: Error registering affiliate: ' . $result->get_error_message());
            }
            return [
                'success' => false,
                'message' => $result->get_error_message()
            ];
        }

        // Send notification email to admin
        self::send_admin_notification($user_id, $result['affiliate_code']);

        return [
            'success' => true,
            'message' => __('Thank you! Your affiliate application has been submitted and is pending approval.', 'directorist-simple-affiliate')
        ];
    }

    /**
     * Validate form fields
     *
     * @param array $data
     * @return array Array of error messages
     */
    private static function validate_fields($data)
    {
        $errors = [];

        // Full Name
        if (empty($data['dsa_full_name'])) {
            $errors[] = __('Full name is required.', 'directorist-simple-affiliate');
        }

        // Email
        if (empty($data['dsa_email'])) {
            $errors[] = __('Email address is required.', 'directorist-simple-affiliate');
        } elseif (!is_email($data['dsa_email'])) {
            $errors[] = __('Please enter a valid email address.', 'directorist-simple-affiliate');
        }

        // Payment Method
        $allowed_methods = ['PayPal', 'Bank Transfer'];
        if (empty($data['dsa_payment_method'])) {
            $errors[] = __('Payment method is required.', 'directorist-simple-affiliate');
        } elseif (!in_array($data['dsa_payment_method'], $allowed_methods)) {
            $errors[] = __('Please select a valid payment method.', 'directorist-simple-affiliate');
        } else {
            // Validate conditional fields based on payment method
            if ($data['dsa_payment_method'] === 'PayPal') {
                if (empty($data['dsa_paypal_email'])) {
                    $errors[] = __('PayPal email is required.', 'directorist-simple-affiliate');
                } elseif (!is_email($data['dsa_paypal_email'])) {
                    $errors[] = __('Please enter a valid PayPal email address.', 'directorist-simple-affiliate');
                }
            } elseif ($data['dsa_payment_method'] === 'Bank Transfer') {
                if (empty($data['dsa_bank_details'])) {
                    $errors[] = __('Bank transfer details are required.', 'directorist-simple-affiliate');
                }
            }
        }

        // Website/Social Media URL
        if (empty($data['dsa_website'])) {
            $errors[] = __('Website/Social Media URL is required.', 'directorist-simple-affiliate');
        } elseif (!filter_var($data['dsa_website'], FILTER_VALIDATE_URL)) {
            $errors[] = __('Please enter a valid URL.', 'directorist-simple-affiliate');
        }

        // Promotion Method
        if (empty($data['dsa_promotion_method'])) {
            $errors[] = __('Please describe how you will promote Directorist.', 'directorist-simple-affiliate');
        }

        // Terms & Conditions
        if (empty($data['dsa_terms'])) {
            $errors[] = __('You must agree to the terms and conditions.', 'directorist-simple-affiliate');
        }

        return $errors;
    }


    /**
     * Generate unique username
     *
     * @param string $base_username
     * @return string
     */
    private static function generate_unique_username($base_username)
    {
        $username = $base_username;
        $counter = 1;

        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Send welcome email to new user
     *
     * @param string $email
     * @param string $username
     * @param string $password
     * @param string $full_name
     */
    private static function send_welcome_email($email, $username, $password, $full_name)
    {
        $subject = __('Welcome to Directorist Affiliate Program', 'directorist-simple-affiliate');
        $message = sprintf(
            __('Hello %s,

Thank you for applying to become a Directorist affiliate!

Your account has been created:
Username: %s
Password: %s

You can log in at: %s

Your affiliate application is currently pending approval. You will receive an email once your application has been reviewed.

Best regards,
Directorist Team', 'directorist-simple-affiliate'),
            $full_name,
            $username,
            $password,
            wp_login_url()
        );

        wp_mail($email, $subject, $message);
    }

    /**
     * Send notification email to admin
     *
     * @param int $user_id
     * @param string $affiliate_code
     */
    private static function send_admin_notification($user_id, $affiliate_code)
    {
        $admin_email = get_option('admin_email');
        $user = get_userdata($user_id);

        $subject = __('New Affiliate Application - Directorist', 'directorist-simple-affiliate');
        $message = sprintf(
            __('A new affiliate application has been submitted:

User: %s (%s)
Affiliate Code: %s
Status: Pending

Please review the application in the WordPress admin panel.', 'directorist-simple-affiliate'),
            $user->display_name,
            $user->user_email,
            $affiliate_code
        );

        wp_mail($admin_email, $subject, $message);
    }
}

