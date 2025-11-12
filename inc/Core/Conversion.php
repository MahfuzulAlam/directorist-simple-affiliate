<?php

namespace DirectoristSimpleAffiliate\Core;

use DirectoristSimpleAffiliate\Database\Managers\AffiliatesManager;
use DirectoristSimpleAffiliate\Database\Managers\AffiliateCodesManager;
use DirectoristSimpleAffiliate\Database\Managers\ReferralsManager;
use DirectoristSimpleAffiliate\Database\Managers\AffiliateVisitsManager;

/**
 * Conversion Tracking System for Affiliate Referrals
 */
class Conversion
{
    /**
     * Initialize conversion tracking
     */
    public static function init()
    {
        // Hook into order creation
        add_action('atbdp_order_created', [__CLASS__, 'handle_order_created'], 10, 2);

        // Hook into order completion
        add_action('atbdp_order_completed', [__CLASS__, 'handle_order_completed'], 10, 2);

        // Hook into order status changes (for cancellations/refunds)
        add_action('atbdp_order_status_changed', [__CLASS__, 'handle_order_status_changed'], 10, 3);
    }

    /**
     * Handle order creation - create referral record
     *
     * @param int $order_id Order ID
     * @param int $listing_id Listing ID
     */
    public static function handle_order_created($order_id, $listing_id)
    {
        // Check if affiliate cookie exists
        $ref_code = Tracking::get_cookie_value();
        
        if (empty($ref_code)) {
            return; // No affiliate cookie, skip
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

        // Get order data
        $order_amount = get_post_meta($order_id, '_amount', true);
        if (empty($order_amount)) {
            $order_amount = 0.00;
        }

        // Get customer user ID - try multiple methods
        $customer_user_id = 0;
        
        // Method 1: Check order meta
        $customer_user_id = get_post_meta($order_id, '_customer_id', true);
        
        // Method 2: Check order author
        if (empty($customer_user_id)) {
            $order = get_post($order_id);
            $customer_user_id = $order ? $order->post_author : 0;
        }
        
        // Method 3: Check if user is logged in (for current order)
        if (empty($customer_user_id) && is_user_logged_in()) {
            $customer_user_id = get_current_user_id();
        }

        // Self-purchase prevention
        if ($customer_user_id && $affiliate->user_id == $customer_user_id) {
            // Log self-purchase attempt
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("DSA: Self-purchase prevented for order #{$order_id} by affiliate user #{$affiliate->user_id}");
            }
            return;
        }

        // Check for duplicate referral (one referral per order)
        $existing_referral = ReferralsManager::get_by_order_id($order_id);
        if ($existing_referral) {
            // Referral already exists for this order
            return;
        }

        // Get product ID (could be plan ID or listing ID)
        $product_id = get_post_meta($order_id, '_fm_plan_ordered', true);
        if (empty($product_id)) {
            $product_id = $listing_id; // Fallback to listing ID
        }

        // Calculate commission
        $commission_data = self::calculate_commission($affiliate, $order_amount, $product_id);

        // Prepare referral data
        $referral_data = [
            'affiliate_id' => $affiliate->id,
            'code_id' => $code->id,
            'order_id' => $order_id,
            'customer_user_id' => $customer_user_id ?: null,
            'product_id' => $product_id ?: null,
            'order_amount' => $order_amount ?: 0.00,
            'commission_amount' => $commission_data['amount'],
            'commission_rate' => $commission_data['rate'],
            'status' => 'pending',
        ];

        // Insert referral
        global $wpdb;
        $result = ReferralsManager::insert($referral_data);

        if ($result === false) {
            // Log error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("DSA: Failed to create referral for order #{$order_id}");
                error_log("DSA: Database error: " . $wpdb->last_error);
            }
            return;
        }

        $referral_id = $wpdb->insert_id;

        // Update visit record - mark as converted
        self::mark_visit_as_converted($affiliate->id, $code->id, $order_id);

        // Increment conversions counter in affiliate_codes table
        AffiliateCodesManager::update($code->id, [
            'conversions' => $code->conversions + 1
        ]);

        // Send email notification to affiliate
        self::send_new_referral_notification($affiliate->user_id, $referral_id, $order_id, $order_amount, $commission_data['amount']);

        // Optional: Send admin notification
        if (apply_filters('dsa_notify_admin_on_referral', true)) {
            self::send_admin_referral_notification($affiliate->user_id, $order_id, $order_amount, $commission_data['amount']);
        }

        // Trigger action hook
        do_action('dsa_referral_created', $referral_id, $order_id, $affiliate->id, $code->id);
    }

    /**
     * Handle order completion - approve referral
     *
     * @param int $order_id Order ID
     * @param int $listing_id Listing ID
     */
    public static function handle_order_completed($order_id, $listing_id)
    {
        // Find referral by order_id
        $referral = ReferralsManager::get_by_order_id($order_id);
        
        if (!$referral) {
            return; // No referral found
        }

        // Only update if status is pending
        if ($referral->status !== 'pending') {
            return;
        }

        // Update referral status to approved
        $result = ReferralsManager::update($referral->id, [
            'status' => 'approved',
            'approved_at' => current_time('mysql'),
        ]);

        if ($result === false) {
            // Log error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("DSA: Failed to approve referral #{$referral->id} for order #{$order_id}");
            }
            return;
        }

        // Get affiliate
        $affiliate = AffiliatesManager::get($referral->affiliate_id);
        if (!$affiliate) {
            return;
        }

        // Send email notification to affiliate
        self::send_approved_referral_notification($affiliate->user_id, $referral->id, $order_id, $referral->commission_amount);

        // Delete/expire affiliate cookie after successful conversion
        if (apply_filters('dsa_delete_cookie_on_conversion', true)) {
            self::delete_affiliate_cookie();
        }

        // Trigger action hook
        do_action('dsa_referral_approved', $referral->id, $order_id, $affiliate->id);
    }

    /**
     * Handle order status changes (cancellations/refunds)
     * Hook: atbdp_order_status_changed($new_status, $old_status, $order_id)
     *
     * @param string $new_status New order status
     * @param string $old_status Old order status
     * @param int $order_id Order ID
     */
    public static function handle_order_status_changed($new_status, $old_status, $order_id)
    {
        if (!$order_id || empty($new_status)) {
            return;
        }

        // Find referral by order_id
        $referral = ReferralsManager::get_by_order_id($order_id);
        
        if (!$referral) {
            return; // No referral found
        }

        // Handle cancellations and refunds
        if (in_array($new_status, ['cancelled', 'refunded', 'failed'])) {
            // Update referral status to rejected
            ReferralsManager::update($referral->id, [
                'status' => 'rejected',
            ]);

            // Decrement conversions counter
            if ($referral->code_id) {
                $code = AffiliateCodesManager::get($referral->code_id);
                if ($code && $code->conversions > 0) {
                    AffiliateCodesManager::update($referral->code_id, [
                        'conversions' => $code->conversions - 1
                    ]);
                }
            }

            // Get affiliate
            $affiliate = AffiliatesManager::get($referral->affiliate_id);
            if ($affiliate) {
                // Send notification to affiliate
                self::send_rejected_referral_notification($affiliate->user_id, $referral->id, $order_id, $new_status);
            }

            // Trigger action hook
            do_action('dsa_referral_rejected', $referral->id, $order_id, $new_status);
        }
    }

    /**
     * Calculate commission amount
     *
     * @param object $affiliate Affiliate object
     * @param float $order_amount Order amount
     * @param int $product_id Product/Plan ID
     * @return array ['amount' => float, 'rate' => float]
     */
    private static function calculate_commission($affiliate, $order_amount, $product_id = 0)
    {
        // Get product-specific commission rate (if available)
        $product_rate = 0;
        if ($product_id > 0) {
            $product_rate = apply_filters('dsa_product_commission_rate', 0, $product_id);
        }

        // Use product rate if available, otherwise use affiliate's default rate
        $commission_rate = $product_rate > 0 ? $product_rate : ($affiliate->commission_rate ?: 10.00);

        // Ensure rate is within valid range (0-100)
        $commission_rate = max(0, min(100, (float) $commission_rate));

        // Calculate commission amount
        $commission_amount = ($order_amount * $commission_rate) / 100;

        // Round to 2 decimal places
        $commission_amount = round($commission_amount, 2);

        return [
            'amount' => $commission_amount,
            'rate' => $commission_rate,
        ];
    }

    /**
     * Mark visit as converted
     *
     * @param int $affiliate_id
     * @param int $code_id
     * @param int $order_id
     */
    private static function mark_visit_as_converted($affiliate_id, $code_id, $order_id)
    {
        global $wpdb;
        $visits_table = AffiliateVisitsManager::get_table_name();

        // Get customer IP from order (if available) or use current visitor IP
        $customer_ip = self::get_customer_ip_from_order($order_id);
        if (empty($customer_ip)) {
            $customer_ip = Tracking::get_client_ip();
        }

        // Find recent visit matching affiliate, code, and IP
        // Look for visits within last 30 days
        $thirty_days_ago = date('Y-m-d H:i:s', current_time('timestamp') - (30 * DAY_IN_SECONDS));
        
        $visit = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$visits_table} 
            WHERE affiliate_id = %d 
            AND code_id = %d 
            AND ip_address = %s 
            AND converted = 0 
            AND created_at >= %s 
            ORDER BY created_at DESC 
            LIMIT 1",
            $affiliate_id,
            $code_id,
            $customer_ip,
            $thirty_days_ago
        ));

        if ($visit) {
            // Update visit to mark as converted
            AffiliateVisitsManager::update($visit->id, [
                'converted' => 1
            ]);
        }
    }

    /**
     * Get customer IP from order (if stored)
     *
     * @param int $order_id
     * @return string
     */
    private static function get_customer_ip_from_order($order_id)
    {
        // Try to get IP from order meta
        $ip = get_post_meta($order_id, '_customer_ip', true);
        if (!empty($ip)) {
            return $ip;
        }

        // Fallback: use Tracking class method
        return Tracking::get_client_ip();
    }

    /**
     * Delete affiliate cookie
     */
    private static function delete_affiliate_cookie()
    {
        $cookie_name = Tracking::COOKIE_NAME;
        $cookie_name_js = $cookie_name . '_js';
        
        // Delete both cookies
        setcookie($cookie_name, '', time() - 3600, '/', '', is_ssl(), true);
        setcookie($cookie_name_js, '', time() - 3600, '/', '', is_ssl(), false);
        
        // Also unset from $_COOKIE superglobal
        unset($_COOKIE[$cookie_name]);
        unset($_COOKIE[$cookie_name_js]);
    }

    /**
     * Send new referral notification to affiliate
     *
     * @param int $user_id Affiliate user ID
     * @param int $referral_id Referral ID
     * @param int $order_id Order ID
     * @param float $order_amount Order amount
     * @param float $commission_amount Commission amount
     */
    private static function send_new_referral_notification($user_id, $referral_id, $order_id, $order_amount, $commission_amount)
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $email = $user->user_email;
        $name = $user->display_name;

        $subject = __('New Referral - Commission Pending', 'directorist-simple-affiliate');

        $order_amount_formatted = self::format_price($order_amount);
        $commission_amount_formatted = self::format_price($commission_amount);

        $message = sprintf(
            __('Hello %s,

Great news! You have a new referral.

Order ID: #%d
Order Amount: %s
Your Commission: %s

This commission is pending approval and will be processed once the order is completed.

Thank you for promoting Directorist!

Best regards,
Directorist Team', 'directorist-simple-affiliate'),
            $name,
            $order_id,
            $order_amount_formatted,
            $commission_amount_formatted
        );

        wp_mail($email, $subject, $message);
    }

    /**
     * Send approved referral notification to affiliate
     *
     * @param int $user_id Affiliate user ID
     * @param int $referral_id Referral ID
     * @param int $order_id Order ID
     * @param float $commission_amount Commission amount
     */
    private static function send_approved_referral_notification($user_id, $referral_id, $order_id, $commission_amount)
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $email = $user->user_email;
        $name = $user->display_name;

        $subject = __('Commission Approved!', 'directorist-simple-affiliate');

        $commission_amount_formatted = self::format_price($commission_amount);

        $message = sprintf(
            __('Hello %s,

Congratulations! Your commission has been approved.

Order ID: #%d
Commission Amount: %s

This commission will be included in your next payout.

Thank you for your continued partnership!

Best regards,
Directorist Team', 'directorist-simple-affiliate'),
            $name,
            $order_id,
            $commission_amount_formatted
        );

        wp_mail($email, $subject, $message);
    }

    /**
     * Send rejected referral notification to affiliate
     *
     * @param int $user_id Affiliate user ID
     * @param int $referral_id Referral ID
     * @param int $order_id Order ID
     * @param string $reason Reason for rejection
     */
    private static function send_rejected_referral_notification($user_id, $referral_id, $order_id, $reason)
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $email = $user->user_email;
        $name = $user->display_name;

        $subject = __('Referral Status Update', 'directorist-simple-affiliate');

        $message = sprintf(
            __('Hello %s,

Your referral for Order #%d has been updated.

Status: %s

If you have any questions, please contact us.

Best regards,
Directorist Team', 'directorist-simple-affiliate'),
            $name,
            $order_id,
            ucfirst($reason)
        );

        wp_mail($email, $subject, $message);
    }

    /**
     * Send admin notification about new referral
     *
     * @param int $affiliate_user_id Affiliate user ID
     * @param int $order_id Order ID
     * @param float $order_amount Order amount
     * @param float $commission_amount Commission amount
     */
    private static function send_admin_referral_notification($affiliate_user_id, $order_id, $order_amount, $commission_amount)
    {
        $affiliate_user = get_userdata($affiliate_user_id);
        if (!$affiliate_user) {
            return;
        }

        $admin_email = get_option('admin_email');
        $affiliate_name = $affiliate_user->display_name;

        $subject = __('New Affiliate Referral Created', 'directorist-simple-affiliate');

        $order_amount_formatted = self::format_price($order_amount);
        $commission_amount_formatted = self::format_price($commission_amount);

        $message = sprintf(
            __('A new affiliate referral has been created.

Affiliate: %s (%s)
Order ID: #%d
Order Amount: %s
Commission Amount: %s

View order: %s', 'directorist-simple-affiliate'),
            $affiliate_name,
            $affiliate_user->user_email,
            $order_id,
            $order_amount_formatted,
            $commission_amount_formatted,
            admin_url('post.php?post=' . $order_id . '&action=edit')
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Format price for display
     *
     * @param float $amount
     * @return string
     */
    private static function format_price($amount)
    {
        // Use WooCommerce function if available
        if (function_exists('wc_price')) {
            return wc_price($amount);
        }

        // Fallback: format with currency symbol
        $currency_symbol = get_directorist_option('currency', '$');
        $decimals = get_directorist_option('decimal', 2);
        
        return $currency_symbol . number_format((float) $amount, $decimals);
    }
}

