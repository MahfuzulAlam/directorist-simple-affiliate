<?php
/**
 * Uninstall script for Directorist - Simple Affiliate
 * 
 * This file is executed when the plugin is deleted from WordPress.
 * It removes all database tables created by the plugin.
 *
 * @package DirectoristSimpleAffiliate
 */

// Exit if uninstall not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load the autoloader
//require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Drop all plugin tables
//\DirectoristSimpleAffiliate\Database\TableManager::drop_tables();

