<?php

namespace DirectoristSimpleAffiliate\Frontend;

use DirectoristSimpleAffiliate\Core\AffiliateManager;
use DirectoristSimpleAffiliate\Database\Managers\AffiliatesManager;
use DirectoristSimpleAffiliate\Database\Managers\AffiliateCodesManager;

/**
 * Frontend Dashboard Tab for Affiliates
 */
class DashboardTab
{
    /**
     * Initialize the dashboard tab
     */
    public static function init()
    {
        add_filter('directorist_dashboard_tabs', [__CLASS__, 'add_affiliate_tabs']);
        add_action('wp_ajax_dsa_generate_affiliate_code', [__CLASS__, 'handle_generate_code']);
        add_action('wp_ajax_dsa_delete_affiliate_code', [__CLASS__, 'handle_delete_code']);
        add_action('wp_ajax_dsa_update_affiliate_settings', [__CLASS__, 'handle_update_settings']);
    }

    /**
     * Add affiliate tabs to dashboard
     *
     * @param array $tabs Existing dashboard tabs
     * @return array
     */
    public static function add_affiliate_tabs($tabs)
    {
        $affiliate_manager = AffiliateManager::get_instance();
        
        // Only show tabs to active affiliates
        if (!$affiliate_manager->is_active_affiliate()) {
            return $tabs;
        }

        $tabs['dashboard_affiliate'] = [
            'title'     => __('Affiliate Codes', 'directorist-simple-affiliate'),
            'content'   => self::get_affiliate_tab_content(),
            'icon'      => 'las la-link',
        ];

        $tabs['dashboard_affiliate_settings'] = [
            'title'     => __('Affiliate Settings', 'directorist-simple-affiliate'),
            'content'   => self::get_settings_tab_content(),
            'icon'      => 'las la-cog',
        ];

        return $tabs;
    }

    /**
     * Get affiliate tab content
     *
     * @return string
     */
    private static function get_affiliate_tab_content()
    {
        ob_start();
        include DSA_PLUGIN_DIR . 'templates/frontend/dashboard-affiliate-tab.php';
        return ob_get_clean();
    }

    /**
     * Get settings tab content
     *
     * @return string
     */
    private static function get_settings_tab_content()
    {
        ob_start();
        include DSA_PLUGIN_DIR . 'templates/frontend/dashboard-settings-tab.php';
        return ob_get_clean();
    }

    /**
     * Handle AJAX request to generate affiliate code
     */
    public static function handle_generate_code()
    {
        check_ajax_referer('dsa_affiliate_dashboard', 'nonce');

        $affiliate_manager = AffiliateManager::get_instance();
        $user_id = get_current_user_id();

        // Verify user is active affiliate
        if (!$affiliate_manager->is_active_affiliate($user_id)) {
            wp_send_json_error([
                'message' => __('You must be an active affiliate to generate codes.', 'directorist-simple-affiliate')
            ]);
        }

        // Get affiliate record
        $affiliate = AffiliatesManager::get_by_user_id($user_id);
        if (!$affiliate) {
            wp_send_json_error([
                'message' => __('Affiliate record not found.', 'directorist-simple-affiliate')
            ]);
        }

        // Get form data
        $code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'custom';
        $campaign_name = isset($_POST['campaign_name']) ? sanitize_text_field($_POST['campaign_name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $expires_at = isset($_POST['expires_at']) ? sanitize_text_field($_POST['expires_at']) : '';

        // Generate code if not provided
        if (empty($code)) {
            $code = self::generate_unique_code();
        } else {
            // Validate code is unique
            $existing = AffiliateCodesManager::get_by_code($code);
            if ($existing) {
                wp_send_json_error([
                    'message' => __('This code already exists. Please choose a different one.', 'directorist-simple-affiliate')
                ]);
            }
        }

        // Prepare data
        $data = [
            'affiliate_id' => $affiliate->id,
            'code' => $code,
            'type' => $type,
            'campaign_name' => !empty($campaign_name) ? $campaign_name : null,
            'description' => !empty($description) ? $description : null,
            'status' => 'active',
            'expires_at' => !empty($expires_at) ? date('Y-m-d H:i:s', strtotime($expires_at)) : null,
        ];

        // Insert code
        $result = AffiliateCodesManager::insert($data);

        if ($result === false) {
            wp_send_json_error([
                'message' => __('Failed to generate affiliate code.', 'directorist-simple-affiliate')
            ]);
        }

        // Get the created code
        $created_code = AffiliateCodesManager::get_by_code($code);

        wp_send_json_success([
            'message' => __('Affiliate code generated successfully!', 'directorist-simple-affiliate'),
            'code' => $created_code,
        ]);
    }

    /**
     * Handle AJAX request to delete affiliate code
     */
    public static function handle_delete_code()
    {
        check_ajax_referer('dsa_affiliate_dashboard', 'nonce');

        $affiliate_manager = AffiliateManager::get_instance();
        $user_id = get_current_user_id();

        // Verify user is active affiliate
        if (!$affiliate_manager->is_active_affiliate($user_id)) {
            wp_send_json_error([
                'message' => __('You must be an active affiliate to manage codes.', 'directorist-simple-affiliate')
            ]);
        }

        // Get code ID
        $code_id = isset($_POST['code_id']) ? absint($_POST['code_id']) : 0;
        if (!$code_id) {
            wp_send_json_error([
                'message' => __('Invalid code ID.', 'directorist-simple-affiliate')
            ]);
        }

        // Get affiliate record
        $affiliate = AffiliatesManager::get_by_user_id($user_id);
        if (!$affiliate) {
            wp_send_json_error([
                'message' => __('Affiliate record not found.', 'directorist-simple-affiliate')
            ]);
        }

        // Verify code belongs to this affiliate
        $code = AffiliateCodesManager::get($code_id);
        if (!$code || $code->affiliate_id != $affiliate->id) {
            wp_send_json_error([
                'message' => __('You do not have permission to delete this code.', 'directorist-simple-affiliate')
            ]);
        }

        // Don't allow deleting default codes
        if ($code->type === 'default') {
            wp_send_json_error([
                'message' => __('Default codes cannot be deleted.', 'directorist-simple-affiliate')
            ]);
        }

        // Delete code
        $result = AffiliateCodesManager::delete($code_id);

        if ($result === false) {
            wp_send_json_error([
                'message' => __('Failed to delete affiliate code.', 'directorist-simple-affiliate')
            ]);
        }

        wp_send_json_success([
            'message' => __('Affiliate code deleted successfully!', 'directorist-simple-affiliate'),
        ]);
    }

    /**
     * Generate unique affiliate code
     *
     * @return string
     */
    private static function generate_unique_code()
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
     * Get affiliate codes for current user
     *
     * @return array
     */
    public static function get_user_affiliate_codes()
    {
        $affiliate_manager = AffiliateManager::get_instance();
        $user_id = get_current_user_id();

        if (!$affiliate_manager->is_active_affiliate($user_id)) {
            return [];
        }

        $affiliate = AffiliatesManager::get_by_user_id($user_id);
        if (!$affiliate) {
            return [];
        }

        return AffiliateCodesManager::get_by_affiliate_id($affiliate->id);
    }

    /**
     * Generate affiliate URL
     *
     * @param string $code Affiliate code
     * @param string $url Optional destination URL
     * @return string
     */
    public static function generate_affiliate_url($code, $url = '')
    {
        if (empty($url)) {
            $url = home_url();
        }

        $param = apply_filters('dsa_affiliate_url_parameter', 'ref');
        return add_query_arg($param, $code, $url);
    }

    /**
     * Handle AJAX request to update affiliate settings
     */
    public static function handle_update_settings()
    {
        check_ajax_referer('dsa_affiliate_dashboard', 'nonce');

        $affiliate_manager = AffiliateManager::get_instance();
        $user_id = get_current_user_id();

        // Verify user is active affiliate
        if (!$affiliate_manager->is_active_affiliate($user_id)) {
            wp_send_json_error([
                'message' => __('You must be an active affiliate to update settings.', 'directorist-simple-affiliate')
            ]);
        }

        // Get form data
        $data = [];

        if (isset($_POST['payment_method'])) {
            $data['payment_method'] = sanitize_text_field($_POST['payment_method']);
        }

        if (isset($_POST['website'])) {
            $data['website'] = esc_url_raw($_POST['website']);
        }

        if (isset($_POST['phone'])) {
            $data['phone'] = sanitize_text_field($_POST['phone']);
        }

        // Payment method specific fields
        if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'PayPal' && isset($_POST['paypal_email'])) {
            $data['paypal_email'] = sanitize_email($_POST['paypal_email']);
        }

        if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Bank Transfer' && isset($_POST['bank_details'])) {
            $data['bank_details'] = sanitize_textarea_field($_POST['bank_details']);
        }

        // Update affiliate data
        $result = $affiliate_manager->update_affiliate_data($user_id, $data);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
        }

        wp_send_json_success([
            'message' => __('Settings updated successfully!', 'directorist-simple-affiliate')
        ]);
    }
}

