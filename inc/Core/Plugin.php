<?php

namespace DirectoristSimpleAffiliate\Core;

use DirectoristSimpleAffiliate\Forms\RegistrationFormShortcode;
use DirectoristSimpleAffiliate\Admin\AffiliateRequestsPage;

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

        // Prevent affiliates from accessing admin panel
        add_action('admin_init', [__CLASS__, 'prevent_affiliate_admin_access']);
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

