<?php

namespace DirectoristSimpleAffiliate\Core;

use DirectoristSimpleAffiliate\Forms\RegistrationFormShortcode;
use DirectoristSimpleAffiliate\Admin\AffiliateRequestsPage;
use DirectoristSimpleAffiliate\Frontend\DashboardTab;

/**
 * Main plugin class
 */
class Plugin
{
    /**
     * Initialize the plugin
     */
    public function init()
    {
        // Initialize shortcode
        RegistrationFormShortcode::init();

        // Initialize admin page
        AffiliateRequestsPage::init();

        // Initialize frontend dashboard tab
        DashboardTab::init();

        // Enqueue frontend dashboard assets
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_frontend_assets']);

        // Prevent affiliates from accessing admin panel
        add_action('admin_init', [__CLASS__, 'prevent_affiliate_admin_access']);
    }

    /**
     * Enqueue frontend dashboard assets
     */
    public static function enqueue_frontend_assets()
    {
        // Only enqueue on dashboard pages
        // Check if we're on a page that might contain the dashboard
        global $post;
        if (!$post) {
            return;
        }

        // Check if the page contains the dashboard shortcode or is a dashboard page
        $is_dashboard = false;
        if (function_exists('get_directorist_option')) {
            $is_dashboard = get_directorist_option('user_dashboard');
        }

        if (!is_page($is_dashboard)) {
            return;
        }

        wp_enqueue_style(
            'dsa-frontend-dashboard',
            DSA_PLUGIN_URL . 'assets/css/frontend-dashboard.css',
            [],
            DSA_VERSION
        );

        wp_enqueue_script(
            'dsa-frontend-dashboard',
            DSA_PLUGIN_URL . 'assets/js/frontend-dashboard.js',
            ['jquery'],
            DSA_VERSION,
            true
        );

        wp_localize_script('dsa-frontend-dashboard', 'dsaFrontend', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dsa_affiliate_dashboard'),
        ]);
    }

    /**
     * Prevent affiliates from accessing admin panel
     */
    public static function prevent_affiliate_admin_access()
    {
        $affiliate_manager = AffiliateManager::get_instance();
        if ($affiliate_manager->is_affiliate() && !current_user_can('manage_options')) {
            wp_redirect(home_url());
            exit;
        }
    }
}

