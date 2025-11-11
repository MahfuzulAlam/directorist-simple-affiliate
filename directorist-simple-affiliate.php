<?php
/**
 * Plugin Name: Directorist - Simple Affiliate
 * Plugin URI: https://wpxplore.com/tools/directorist-simple-affiliate
 * Description: Monetize your Directorist directory with a powerful affiliate program. Track referrals, manage commissions, and reward partners who drive pricing plan sales.
 * Version: 1.0.0
 * Author: wpXplore
 * Author URI: https://wpxplore.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: directorist-simple-affiliate
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DSA_VERSION', '1.0.0');
define('DSA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DSA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DSA_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
require_once DSA_PLUGIN_DIR . 'vendor/autoload.php';

// Initialize plugin
use DirectoristSimpleAffiliate\Core\Plugin;

/**
 * Main plugin initialization
 */
function dsa_init() {
    $plugin = new Plugin();
    $plugin->init();
}

// Hook into WordPress
add_action('plugins_loaded', 'dsa_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create database tables
    \DirectoristSimpleAffiliate\Database\TableManager::create_tables();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Cleanup if needed
});

