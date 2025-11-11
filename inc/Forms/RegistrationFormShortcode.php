<?php

namespace DirectoristSimpleAffiliate\Forms;

/**
 * Shortcode for affiliate registration form
 */
class RegistrationFormShortcode
{
    /**
     * Initialize shortcode
     */
    public static function init()
    {
        add_shortcode('dsa_registration_form', [__CLASS__, 'render']);
        
        // Process form early in WordPress lifecycle
        add_action('init', [__CLASS__, 'process_form_early'], 1);
    }
    
    /**
     * Process form submission early
     */
    public static function process_form_early()
    {
        if (isset($_POST['dsa_submit_registration']) && !empty($_POST['dsa_registration_nonce'])) {
            // Store result in a global variable to retrieve in shortcode
            global $dsa_form_result;
            $dsa_form_result = RegistrationForm::process();
            
            // Debug logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('DSA: Form processed early, result stored in global');
            }
        }
    }

    /**
     * Render registration form
     *
     * @param array $atts
     * @return string
     */
    public static function render($atts = [])
    {
        // Get result from global (set during early processing) or process now as fallback
        global $dsa_form_result;
        $result = null;
        
        if (isset($dsa_form_result)) {
            // Use result from early processing
            $result = $dsa_form_result;
            unset($GLOBALS['dsa_form_result']); // Clear after use
        } elseif (isset($_POST['dsa_submit_registration']) && !empty($_POST['dsa_registration_nonce'])) {
            // Fallback: process directly if early processing didn't run
            $result = RegistrationForm::process();
        }
        $result = RegistrationForm::process();

        // Enqueue assets
        self::enqueue_assets();

        // Get current user data if logged in
        $user_data = null;
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $user_data = [
                'full_name' => $current_user->display_name,
                'email' => $current_user->user_email,
            ];
        }

        // Start output buffering
        ob_start();
        include DSA_PLUGIN_DIR . 'templates/registration-form.php';
        return ob_get_clean();
    }

    /**
     * Enqueue form assets
     */
    private static function enqueue_assets()
    {
        wp_enqueue_style(
            'dsa-registration-form',
            DSA_PLUGIN_URL . 'assets/css/registration-form.css',
            [],
            DSA_VERSION
        );

        wp_enqueue_script(
            'dsa-registration-form',
            DSA_PLUGIN_URL . 'assets/js/registration-form.js',
            ['jquery'],
            DSA_VERSION,
            true
        );
    }
}

