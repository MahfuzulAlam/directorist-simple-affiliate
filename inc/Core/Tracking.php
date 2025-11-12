<?php

namespace DirectoristSimpleAffiliate\Core;

use DirectoristSimpleAffiliate\Database\Managers\AffiliatesManager;
use DirectoristSimpleAffiliate\Database\Managers\AffiliateCodesManager;
use DirectoristSimpleAffiliate\Database\Managers\AffiliateVisitsManager;

/**
 * Visitor Tracking System for Affiliate Referral Links
 */
class Tracking
{
    /**
     * Cookie name for affiliate referral
     */
    const COOKIE_NAME = 'directorist_affiliate_ref';

    /**
     * Default cookie duration in days
     */
    const DEFAULT_COOKIE_DURATION = 30;

    /**
     * Default duplicate visit prevention period in hours
     */
    const DEFAULT_DUPLICATE_HOURS = 24;

    /**
     * Max visits per IP per hour (rate limiting)
     */
    const MAX_VISITS_PER_HOUR = 100;

    /**
     * Initialize tracking system
     */
    public static function init()
    {
        // Detect ref parameter and set cookie
        add_action('init', [__CLASS__, 'detect_referral_link'], 1);

        // AJAX handler for recording visits
        add_action('wp_ajax_dsa_record_visit', [__CLASS__, 'handle_record_visit']);
        add_action('wp_ajax_nopriv_dsa_record_visit', [__CLASS__, 'handle_record_visit']);

        // Enqueue tracking script
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_tracking_script']);
    }

    /**
     * Detect referral link and set cookie
     */
    public static function detect_referral_link()
    {
        // Get ref parameter (configurable via filter)
        $param_name = apply_filters('dsa_affiliate_url_parameter', 'ref');
        $ref_code = isset($_GET[$param_name]) ? sanitize_text_field($_GET[$param_name]) : '';

        if (empty($ref_code)) {
            return;
        }

        // Validate and get affiliate code
        $code = AffiliateCodesManager::get_by_code($ref_code);
        if (!$code || $code->status !== 'active') {
            return;
        }

        // Check if code is expired
        if (!empty($code->expires_at) && strtotime($code->expires_at) < current_time('timestamp')) {
            return;
        }

        // Get affiliate
        $affiliate = AffiliatesManager::get($code->affiliate_id);
        if (!$affiliate) {
            return;
        }

        // Self-referral prevention
        if (is_user_logged_in()) {
            $current_user_id = get_current_user_id();
            if ($affiliate->user_id == $current_user_id) {
                // Optionally show message
                if (apply_filters('dsa_show_self_referral_message', true)) {
                    add_action('wp_footer', function() {
                        echo '<div class="dsa-self-referral-notice" style="position:fixed;bottom:20px;right:20px;background:#fff;padding:15px;border:1px solid #ccd0d4;border-radius:4px;box-shadow:0 2px 5px rgba(0,0,0,0.1);z-index:9999;max-width:300px;">';
                        echo '<p style="margin:0;color:#d63638;">' . esc_html__('You cannot use your own referral link.', 'directorist-simple-affiliate') . '</p>';
                        echo '</div>';
                        echo '<script>setTimeout(function(){document.querySelector(".dsa-self-referral-notice").remove();},5000);</script>';
                    });
                }
                return;
            }
        }

        // Set cookie
        $cookie_duration = apply_filters('dsa_cookie_duration_days', self::DEFAULT_COOKIE_DURATION);
        $expire = time() + ($cookie_duration * DAY_IN_SECONDS);
        
        // Use secure flag if site is HTTPS
        $secure = is_ssl();
        
        // Set cookie with httponly flag (note: JavaScript can't read httponly cookies, so we'll use a separate approach)
        setcookie(self::COOKIE_NAME, $ref_code, $expire, '/', '', $secure, true);
        
        // Also set a JavaScript-readable cookie for client-side tracking
        setcookie(self::COOKIE_NAME . '_js', $ref_code, $expire, '/', '', $secure, false);

        // Redirect to clean URL (remove ref parameter)
        if (apply_filters('dsa_redirect_after_cookie_set', true)) {
            $clean_url = remove_query_arg($param_name);
            if ($clean_url !== $_SERVER['REQUEST_URI']) {
                wp_safe_redirect($clean_url);
                exit;
            }
        }
    }

    /**
     * Handle AJAX request to record visit
     */
    public static function handle_record_visit()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dsa_tracking_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        // Get ref code from cookie
        $ref_code = isset($_COOKIE[self::COOKIE_NAME . '_js']) ? sanitize_text_field($_COOKIE[self::COOKIE_NAME . '_js']) : '';
        
        // Fallback to POST data if cookie not available
        if (empty($ref_code) && isset($_POST['ref_code'])) {
            $ref_code = sanitize_text_field($_POST['ref_code']);
        }

        if (empty($ref_code)) {
            wp_send_json_error(['message' => 'No referral code found']);
        }

        // Validate and get affiliate code
        $code = AffiliateCodesManager::get_by_code($ref_code);
        if (!$code || $code->status !== 'active') {
            wp_send_json_error(['message' => 'Invalid or inactive code']);
        }

        // Check if code is expired
        if (!empty($code->expires_at) && strtotime($code->expires_at) < current_time('timestamp')) {
            wp_send_json_error(['message' => 'Code expired']);
        }

        // Get affiliate
        $affiliate = AffiliatesManager::get($code->affiliate_id);
        if (!$affiliate) {
            wp_send_json_error(['message' => 'Affiliate not found']);
        }

        // Self-referral prevention
        if (is_user_logged_in()) {
            $current_user_id = get_current_user_id();
            if ($affiliate->user_id == $current_user_id) {
                wp_send_json_error(['message' => 'Self-referral not allowed']);
            }
        }

        // Rate limiting - check visits from same IP in last hour
        $ip_address = self::get_client_ip();
        $recent_visits = self::check_rate_limit($ip_address);
        if ($recent_visits >= self::MAX_VISITS_PER_HOUR) {
            wp_send_json_error(['message' => 'Rate limit exceeded']);
        }

        // Check for duplicate visit (same IP within configured hours)
        $duplicate_hours = apply_filters('dsa_duplicate_visit_hours', self::DEFAULT_DUPLICATE_HOURS);
        if (self::is_duplicate_visit($ip_address, $code->affiliate_id, $code->id, $duplicate_hours)) {
            wp_send_json_success(['message' => 'Duplicate visit ignored', 'duplicate' => true]);
        }

        // Get visit data
        $referrer_url = isset($_POST['referrer']) ? esc_url_raw($_POST['referrer']) : '';
        $landing_url = isset($_POST['landing']) ? esc_url_raw($_POST['landing']) : home_url($_SERVER['REQUEST_URI']);
        $user_agent = isset($_POST['user_agent']) ? sanitize_text_field($_POST['user_agent']) : '';

        // Anonymize IP for privacy (optional)
        $ip_address = apply_filters('dsa_anonymize_ip', $ip_address);

        // Prepare visit data
        $visit_data = [
            'affiliate_id' => $affiliate->id,
            'code_id' => $code->id,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'referrer_url' => $referrer_url,
            'landing_url' => $landing_url,
            'converted' => 0,
        ];

        // Insert visit
        global $wpdb;
        $result = AffiliateVisitsManager::insert($visit_data);

        if ($result !== false) {
            // Increment click counter in affiliate_codes table
            AffiliateCodesManager::update($code->id, [
                'clicks' => $code->clicks + 1
            ]);

            wp_send_json_success([
                'message' => 'Visit recorded',
                'visit_id' => $wpdb->insert_id ?? 0
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to record visit']);
        }
    }

    /**
     * Check rate limit for IP address
     *
     * @param string $ip_address
     * @return int Number of visits in last hour
     */
    private static function check_rate_limit($ip_address)
    {
        global $wpdb;
        $table_name = AffiliateVisitsManager::get_table_name();
        
        $one_hour_ago = date('Y-m-d H:i:s', current_time('timestamp') - HOUR_IN_SECONDS);
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} 
            WHERE ip_address = %s 
            AND created_at >= %s",
            $ip_address,
            $one_hour_ago
        ));

        return (int) $count;
    }

    /**
     * Check if visit is duplicate
     *
     * @param string $ip_address
     * @param int $affiliate_id
     * @param int $code_id
     * @param int $hours
     * @return bool
     */
    private static function is_duplicate_visit($ip_address, $affiliate_id, $code_id, $hours)
    {
        global $wpdb;
        $table_name = AffiliateVisitsManager::get_table_name();
        
        $time_threshold = date('Y-m-d H:i:s', current_time('timestamp') - ($hours * HOUR_IN_SECONDS));
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} 
            WHERE ip_address = %s 
            AND affiliate_id = %d 
            AND code_id = %d 
            AND created_at >= %s",
            $ip_address,
            $affiliate_id,
            $code_id,
            $time_threshold
        ));

        return $count > 0;
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    public static function get_client_ip()
    {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    /**
     * Enqueue tracking script
     */
    public static function enqueue_tracking_script()
    {
        wp_enqueue_script(
            'dsa-tracking',
            DSA_PLUGIN_URL . 'assets/js/tracking.js',
            ['jquery'],
            DSA_VERSION,
            true
        );

        wp_localize_script('dsa-tracking', 'dsaTracking', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dsa_tracking_nonce'),
            'cookieName' => self::COOKIE_NAME . '_js',
        ]);
    }

    /**
     * Get cookie value
     *
     * @return string|false
     */
    public static function get_cookie_value()
    {
        return isset($_COOKIE[self::COOKIE_NAME . '_js']) ? sanitize_text_field($_COOKIE[self::COOKIE_NAME . '_js']) : false;
    }

    /**
     * Get visits with filters
     *
     * @param array $args
     * @return array
     */
    public static function get_visits($args = [])
    {
        $defaults = [
            'affiliate_id' => 0,
            'code_id' => 0,
            'converted' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);

        global $wpdb;
        $visits_table = AffiliateVisitsManager::get_table_name();
        $affiliates_table = AffiliatesManager::get_table_name();
        $codes_table = AffiliateCodesManager::get_table_name();
        $users_table = $wpdb->users;

        $where = [];
        $where_values = [];

        // Join with affiliates and codes tables
        $join = "LEFT JOIN {$affiliates_table} a ON v.affiliate_id = a.id 
                 LEFT JOIN {$codes_table} c ON v.code_id = c.id 
                 LEFT JOIN {$users_table} u ON a.user_id = u.ID";

        if ($args['affiliate_id'] > 0) {
            $where[] = "v.affiliate_id = %d";
            $where_values[] = $args['affiliate_id'];
        }

        if ($args['code_id'] > 0) {
            $where[] = "v.code_id = %d";
            $where_values[] = $args['code_id'];
        }

        if ($args['converted'] !== '') {
            $where[] = "v.converted = %d";
            $where_values[] = (int) $args['converted'];
        }

        if (!empty($args['date_from'])) {
            $where[] = "DATE(v.created_at) >= %s";
            $where_values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = "DATE(v.created_at) <= %s";
            $where_values[] = $args['date_to'];
        }

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = "(u.display_name LIKE %s OR u.user_email LIKE %s OR c.code LIKE %s OR v.ip_address LIKE %s)";
            $where_values[] = $search;
            $where_values[] = $search;
            $where_values[] = $search;
            $where_values[] = $search;
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit_clause = $args['limit'] > 0 ? $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $args['offset']) : '';

        $orderby = sanitize_sql_orderby("v.{$args['orderby']} {$args['order']}");
        $order_clause = $orderby ? "ORDER BY {$orderby}" : '';

        $select = "v.*, a.user_id, u.display_name, u.user_email, c.code as affiliate_code";

        if (!empty($where_values)) {
            $query = $wpdb->prepare(
                "SELECT {$select} FROM {$visits_table} v {$join} {$where_clause} {$order_clause} {$limit_clause}",
                $where_values
            );
        } else {
            $query = "SELECT {$select} FROM {$visits_table} v {$join} {$order_clause} {$limit_clause}";
        }

        return $wpdb->get_results($query);
    }

    /**
     * Count visits with filters
     *
     * @param array $args
     * @return int
     */
    public static function count_visits($args = [])
    {
        $defaults = [
            'affiliate_id' => 0,
            'code_id' => 0,
            'converted' => '',
            'date_from' => '',
            'date_to' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        global $wpdb;
        $visits_table = AffiliateVisitsManager::get_table_name();

        $where = [];
        $where_values = [];

        if ($args['affiliate_id'] > 0) {
            $where[] = "affiliate_id = %d";
            $where_values[] = $args['affiliate_id'];
        }

        if ($args['code_id'] > 0) {
            $where[] = "code_id = %d";
            $where_values[] = $args['code_id'];
        }

        if ($args['converted'] !== '') {
            $where[] = "converted = %d";
            $where_values[] = (int) $args['converted'];
        }

        if (!empty($args['date_from'])) {
            $where[] = "DATE(created_at) >= %s";
            $where_values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = "DATE(created_at) <= %s";
            $where_values[] = $args['date_to'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        if (!empty($where_values)) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$visits_table} {$where_clause}",
                $where_values
            );
        } else {
            $query = "SELECT COUNT(*) FROM {$visits_table}";
        }

        return (int) $wpdb->get_var($query);
    }
}

