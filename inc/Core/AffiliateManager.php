<?php

namespace DirectoristSimpleAffiliate\Core;

use DirectoristSimpleAffiliate\Database\Managers\AffiliatesManager;
use DirectoristSimpleAffiliate\Database\Managers\AffiliateCodesManager;

/**
 * Centralized class for managing affiliate users using WordPress user meta
 * This class handles all affiliate operations and synchronizes user meta with database
 */
class AffiliateManager
{
    /**
     * User meta keys
     */
    const META_KEY_REGISTERED_AT = 'dsa_affiliate_registered_at';
    const META_KEY_WEBSITE = 'dsa_website';
    const META_KEY_PHONE = 'dsa_phone';
    const META_KEY_PAYMENT_METHOD = 'dsa_affiliate_payment_method';
    const META_KEY_PAYPAL_EMAIL = 'dsa_paypal_email';
    const META_KEY_BANK_DETAILS = 'dsa_bank_details';

    /**
     * Affiliate statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_REJECTED = 'rejected';

    /**
     * Instance of the class
     *
     * @var AffiliateManager
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return AffiliateManager
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a new affiliate
     *
     * @param int $user_id User ID
     * @param array $data Affiliate data
     * @return array|WP_Error Success with affiliate data or WP_Error on failure
     */
    public function register_affiliate($user_id, $data)
    {
        // Check if user already has affiliate status
        if ($this->is_affiliate($user_id)) {
            return new \WP_Error(
                'already_affiliate',
                __('User is already registered as an affiliate.', 'directorist-simple-affiliate')
            );
        }

        // Validate user exists
        $user = get_userdata($user_id);
        if (!$user) {
            return new \WP_Error(
                'invalid_user',
                __('Invalid user ID.', 'directorist-simple-affiliate')
            );
        }

        // Get default commission rate
        $default_commission_rate = apply_filters('dsa_default_commission_rate', 10.00);

        // Prepare affiliate data
        $status = isset($data['status']) ? $data['status'] : self::STATUS_PENDING;

        // Set user meta (only non-database fields)
        update_user_meta($user_id, self::META_KEY_REGISTERED_AT, current_time('mysql'));

        // Store payment method in user meta (not in database table)
        if (isset($data['payment_method'])) {
            update_user_meta($user_id, self::META_KEY_PAYMENT_METHOD, sanitize_text_field($data['payment_method']));
        }

        // Store additional data
        if (isset($data['website'])) {
            update_user_meta($user_id, self::META_KEY_WEBSITE, esc_url_raw($data['website']));
        }
        if (isset($data['phone'])) {
            update_user_meta($user_id, self::META_KEY_PHONE, sanitize_text_field($data['phone']));
        }
        if (isset($data['paypal_email'])) {
            update_user_meta($user_id, self::META_KEY_PAYPAL_EMAIL, sanitize_email($data['paypal_email']));
        }
        if (isset($data['bank_details'])) {
            update_user_meta($user_id, self::META_KEY_BANK_DETAILS, sanitize_textarea_field($data['bank_details']));
        }

        // Insert into database table
        $affiliate_data = [
            'user_id' => $user_id,
            'status' => $status,
            'commission_rate' => $default_commission_rate,
        ];

        $result = AffiliatesManager::insert($affiliate_data);

        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DSA: Database insert result: ' . print_r($result, true));
            error_log('DSA: Affiliate data being inserted: ' . print_r($affiliate_data, true));
            global $wpdb;
            if ($result === false) {
                error_log('DSA: Database error: ' . $wpdb->last_error);
            }
        }

        if ($result === false) {
            // Rollback user meta on database failure
            $this->remove_affiliate_meta($user_id);
            return new \WP_Error(
                'database_error',
                __('Failed to save affiliate data to database.', 'directorist-simple-affiliate')
            );
        }

        // Trigger action hook
        do_action('dsa_affiliate_registered', $user_id, $status);

        return [
            'success' => true,
            'user_id' => $user_id,
            'status' => $status,
        ];
    }

    /**
     * Check if user is an affiliate
     *
     * @param int|null $user_id User ID (null for current user)
     * @return bool
     */
    public function is_affiliate($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        // Check database table instead of user meta
        $affiliate = AffiliatesManager::get_by_user_id($user_id);
        return !empty($affiliate);
    }

    /**
     * Get affiliate status
     *
     * @param int|null $user_id User ID (null for current user)
     * @return string|false Status or false if not affiliate
     */
    public function get_affiliate_status($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        // Get status from database table
        $affiliate = AffiliatesManager::get_by_user_id($user_id);
        return $affiliate ? $affiliate->status : false;
    }

    /**
     * Check if affiliate is active and approved
     *
     * @param int|null $user_id User ID (null for current user)
     * @return bool
     */
    public function is_active_affiliate($user_id = null)
    {
        return $this->get_affiliate_status($user_id) === self::STATUS_ACTIVE;
    }

    /**
     * Update affiliate status
     *
     * @param int $user_id User ID
     * @param string $status New status
     * @param string $reason Optional reason for status change
     * @return bool|WP_Error
     */
    public function update_affiliate_status($user_id, $status, $reason = '')
    {
        if (!$this->is_affiliate($user_id)) {
            return new \WP_Error(
                'not_affiliate',
                __('User is not registered as an affiliate.', 'directorist-simple-affiliate')
            );
        }

        $valid_statuses = [self::STATUS_PENDING, self::STATUS_ACTIVE, self::STATUS_SUSPENDED, self::STATUS_REJECTED];
        if (!in_array($status, $valid_statuses)) {
            return new \WP_Error(
                'invalid_status',
                __('Invalid affiliate status.', 'directorist-simple-affiliate')
            );
        }

        $old_status = $this->get_affiliate_status($user_id);

        // Update database table (status is stored in database, not user meta)
        $affiliate = AffiliatesManager::get_by_user_id($user_id);
        if ($affiliate) {
            AffiliatesManager::update($affiliate->id, ['status' => $status]);
        } else {
            return new \WP_Error(
                'not_found',
                __('Affiliate record not found in database.', 'directorist-simple-affiliate')
            );
        }

        // Store reason if provided
        if (!empty($reason)) {
            update_user_meta($user_id, 'dsa_affiliate_status_reason', sanitize_textarea_field($reason));
        }

        // Trigger action hook
        do_action('dsa_affiliate_status_changed', $user_id, $old_status, $status, $reason);

        // Create default affiliate code when approved
        if ($status === self::STATUS_ACTIVE && $old_status !== self::STATUS_ACTIVE) {
            $this->create_default_affiliate_code($user_id);
        }

        // Send email notification
        $this->send_status_change_notification($user_id, $status, $reason);

        return true;
    }

    /**
     * Approve affiliate
     *
     * @param int $user_id User ID
     * @return bool|WP_Error
     */
    public function approve_affiliate($user_id)
    {
        return $this->update_affiliate_status($user_id, self::STATUS_ACTIVE);
    }

    /**
     * Reject affiliate
     *
     * @param int $user_id User ID
     * @param string $reason Optional rejection reason
     * @return bool|WP_Error
     */
    public function reject_affiliate($user_id, $reason = '')
    {
        return $this->update_affiliate_status($user_id, self::STATUS_REJECTED, $reason);
    }

    /**
     * Suspend affiliate
     *
     * @param int $user_id User ID
     * @param string $reason Optional suspension reason
     * @return bool|WP_Error
     */
    public function suspend_affiliate($user_id, $reason = '')
    {
        return $this->update_affiliate_status($user_id, self::STATUS_SUSPENDED, $reason);
    }

    /**
     * Get default affiliate code by user ID
     * Note: Codes are now managed in the separate affiliate_codes table
     * This method returns the first active code for the affiliate
     *
     * @param int|null $user_id User ID (null for current user)
     * @return string|false Affiliate code or false
     */
    public function get_affiliate_code($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id || !$this->is_affiliate($user_id)) {
            return false;
        }

        // Get affiliate record to get affiliate_id
        $affiliate = AffiliatesManager::get_by_user_id($user_id);
        if (!$affiliate) {
            return false;
        }

        // Get default code from affiliate_codes table
        $codes = AffiliateCodesManager::get_by_affiliate_id($affiliate->id, ['status' => 'active', 'type' => 'default', 'limit' => 1]);
        
        return !empty($codes) ? $codes[0]->code : false;
    }

    /**
     * Get complete affiliate data
     *
     * @param int|null $user_id User ID (null for current user)
     * @return array|false Affiliate data or false
     */
    public function get_affiliate_data($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id || !$this->is_affiliate($user_id)) {
            return false;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        // Get affiliate record from database
        $affiliate = AffiliatesManager::get_by_user_id($user_id);
        if (!$affiliate) {
            return false;
        }

        return [
            'user_id' => $user_id,
            'user_email' => $user->user_email,
            'display_name' => $user->display_name,
            'status' => $affiliate->status,
            'registered_at' => get_user_meta($user_id, self::META_KEY_REGISTERED_AT, true),
            'payment_method' => get_user_meta($user_id, self::META_KEY_PAYMENT_METHOD, true),
            'website' => get_user_meta($user_id, self::META_KEY_WEBSITE, true),
            'phone' => get_user_meta($user_id, self::META_KEY_PHONE, true),
            'paypal_email' => get_user_meta($user_id, self::META_KEY_PAYPAL_EMAIL, true),
            'bank_details' => get_user_meta($user_id, self::META_KEY_BANK_DETAILS, true),
        ];
    }

    /**
     * Get all affiliates filtered by status
     *
     * @param string $status Status filter (empty for all)
     * @param array $args Additional query arguments
     * @return array Array of affiliate data
     */
    public function get_affiliates($status = '', $args = [])
    {
        // Get affiliates from database table
        $db_args = [];
        if (!empty($status)) {
            $db_args['status'] = $status;
        }
        
        $db_affiliates = AffiliatesManager::get_all($db_args);
        
        $affiliates = [];
        foreach ($db_affiliates as $db_affiliate) {
            $affiliate_data = $this->get_affiliate_data($db_affiliate->user_id);
            if ($affiliate_data) {
                $affiliates[] = $affiliate_data;
            }
        }

        return $affiliates;
    }

    /**
     * Count affiliates by status
     *
     * @param string $status Status filter (empty for all)
     * @return int
     */
    public function count_affiliates($status = '')
    {
        // Count from database table
        $args = [];
        if (!empty($status)) {
            $args['status'] = $status;
        }
        
        return AffiliatesManager::count($args);
    }

    /**
     * Update affiliate data
     *
     * @param int $user_id User ID
     * @param array $data Data to update
     * @return bool|WP_Error
     */
    public function update_affiliate_data($user_id, $data)
    {
        if (!$this->is_affiliate($user_id)) {
            return new \WP_Error(
                'not_affiliate',
                __('User is not registered as an affiliate.', 'directorist-simple-affiliate')
            );
        }

        // Update user meta (only non-database fields)
        if (isset($data['payment_method'])) {
            update_user_meta($user_id, self::META_KEY_PAYMENT_METHOD, sanitize_text_field($data['payment_method']));
        }
        if (isset($data['website'])) {
            update_user_meta($user_id, self::META_KEY_WEBSITE, esc_url_raw($data['website']));
        }
        if (isset($data['phone'])) {
            update_user_meta($user_id, self::META_KEY_PHONE, sanitize_text_field($data['phone']));
        }
        if (isset($data['paypal_email'])) {
            update_user_meta($user_id, self::META_KEY_PAYPAL_EMAIL, sanitize_email($data['paypal_email']));
        }
        if (isset($data['bank_details'])) {
            update_user_meta($user_id, self::META_KEY_BANK_DETAILS, sanitize_textarea_field($data['bank_details']));
        }

        // Trigger action hook
        do_action('dsa_affiliate_data_updated', $user_id, $data);

        return true;
    }

    /**
     * Remove affiliate status
     *
     * @param int $user_id User ID
     * @param bool $delete_database_record Whether to delete database record (false to archive)
     * @return bool|WP_Error
     */
    public function remove_affiliate($user_id, $delete_database_record = false)
    {
        if (!$this->is_affiliate($user_id)) {
            return new \WP_Error(
                'not_affiliate',
                __('User is not registered as an affiliate.', 'directorist-simple-affiliate')
            );
        }

        // Remove user meta
        $this->remove_affiliate_meta($user_id);

        // Handle database record
        $affiliate = AffiliatesManager::get_by_user_id($user_id);
        if ($affiliate) {
            if ($delete_database_record) {
                AffiliatesManager::delete($affiliate->id);
            } else {
                // Archive by updating status (you might want to add a 'deleted' status)
                AffiliatesManager::update($affiliate->id, ['status' => 'archived']);
            }
        }

        // Trigger action hook
        do_action('dsa_affiliate_removed', $user_id);

        return true;
    }

    /**
     * Remove all affiliate meta from user
     *
     * @param int $user_id User ID
     */
    private function remove_affiliate_meta($user_id)
    {
        // Remove only user meta fields (status and code are in database table)
        delete_user_meta($user_id, self::META_KEY_REGISTERED_AT);
        delete_user_meta($user_id, self::META_KEY_PAYMENT_METHOD);
        delete_user_meta($user_id, self::META_KEY_WEBSITE);
        delete_user_meta($user_id, self::META_KEY_PHONE);
        delete_user_meta($user_id, self::META_KEY_PAYPAL_EMAIL);
        delete_user_meta($user_id, self::META_KEY_BANK_DETAILS);
        delete_user_meta($user_id, 'dsa_affiliate_status_reason');
    }


    /**
     * Create default affiliate code for approved affiliate
     *
     * @param int $user_id User ID
     * @return bool|WP_Error
     */
    private function create_default_affiliate_code($user_id)
    {
        $affiliate = AffiliatesManager::get_by_user_id($user_id);
        if (!$affiliate) {
            return false;
        }

        // Check if default code already exists
        $existing_codes = AffiliateCodesManager::get_by_affiliate_id($affiliate->id, ['type' => 'default', 'limit' => 1]);
        if (!empty($existing_codes)) {
            return true; // Default code already exists
        }

        // Generate unique code
        $code = $this->generate_unique_code();

        // Insert default code
        $data = [
            'affiliate_id' => $affiliate->id,
            'code' => $code,
            'type' => 'default',
            'status' => 'active',
        ];

        $result = AffiliateCodesManager::insert($data);
        return $result !== false;
    }

    /**
     * Generate unique affiliate code
     *
     * @return string
     */
    private function generate_unique_code()
    {
        $prefix = 'DSA';
        $max_attempts = 10;
        $attempt = 0;

        do {
            $random = strtoupper(wp_generate_password(8, false));
            $code = $prefix . $random;
            $attempt++;

            $existing = AffiliateCodesManager::get_by_code($code);
        } while ($existing && $attempt < $max_attempts);

        return $code;
    }

    /**
     * Send status change notification email
     *
     * @param int $user_id User ID
     * @param string $status New status
     * @param string $reason Optional reason
     */
    private function send_status_change_notification($user_id, $status, $reason = '')
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $email = $user->user_email;
        $name = $user->display_name;

        $status_labels = [
            self::STATUS_PENDING => __('Pending', 'directorist-simple-affiliate'),
            self::STATUS_ACTIVE => __('Active', 'directorist-simple-affiliate'),
            self::STATUS_SUSPENDED => __('Suspended', 'directorist-simple-affiliate'),
            self::STATUS_REJECTED => __('Rejected', 'directorist-simple-affiliate'),
        ];

        $status_label = isset($status_labels[$status]) ? $status_labels[$status] : $status;

        $subject = sprintf(
            __('Your Affiliate Application Status - %s', 'directorist-simple-affiliate'),
            $status_label
        );

        $message = sprintf(
            __('Hello %s,

Your affiliate application status has been updated to: %s', 'directorist-simple-affiliate'),
            $name,
            $status_label
        );

        if (!empty($reason)) {
            $message .= "\n\n" . sprintf(
                __('Reason: %s', 'directorist-simple-affiliate'),
                $reason
            );
        }

        if ($status === self::STATUS_ACTIVE) {
            $affiliate_code = $this->get_affiliate_code($user_id);
            if ($affiliate_code) {
                $message .= "\n\n" . sprintf(
                    __('Your affiliate code is: %s', 'directorist-simple-affiliate'),
                    $affiliate_code
                );
            }
        }

        $message .= "\n\n" . __('Best regards,', 'directorist-simple-affiliate') . "\n" . __('Directorist Team', 'directorist-simple-affiliate');

        wp_mail($email, $subject, $message);
    }
}

