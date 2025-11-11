<?php

namespace DirectoristSimpleAffiliate\Database;

use DirectoristSimpleAffiliate\Database\Tables\AffiliatesTable;
use DirectoristSimpleAffiliate\Database\Tables\AffiliateVisitsTable;
use DirectoristSimpleAffiliate\Database\Tables\ReferralsTable;
use DirectoristSimpleAffiliate\Database\Tables\PayoutsTable;
use DirectoristSimpleAffiliate\Database\Tables\AffiliateCodesTable;

/**
 * Database table manager
 * Handles creation and management of all plugin tables
 */
class TableManager
{
    /**
     * Create all plugin tables
     */
    public static function create_tables()
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Create affiliates table
        AffiliatesTable::create($charset_collate);

        // Create affiliate_visits table
        AffiliateVisitsTable::create($charset_collate);

        // Create referrals table
        ReferralsTable::create($charset_collate);

        // Create payouts table
        PayoutsTable::create($charset_collate);

        // Create affiliate_codes table
        AffiliateCodesTable::create($charset_collate);
    }

    /**
     * Drop all plugin tables
     */
    public static function drop_tables()
    {
        global $wpdb;

        $tables = [
            AffiliatesTable::get_table_name(),
            AffiliateVisitsTable::get_table_name(),
            ReferralsTable::get_table_name(),
            PayoutsTable::get_table_name(),
            AffiliateCodesTable::get_table_name(),
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
}

