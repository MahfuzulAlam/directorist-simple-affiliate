<?php

namespace DirectoristSimpleAffiliate\Database\Tables;

/**
 * Payouts table structure and management
 */
class PayoutsTable
{
    /**
     * Get table name with WordPress prefix
     *
     * @return string
     */
    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'dsa_payouts';
    }

    /**
     * Create the payouts table
     *
     * @param string $charset_collate
     */
    public static function create($charset_collate)
    {
        global $wpdb;

        $table_name = self::get_table_name();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_method varchar(100) DEFAULT NULL,
            transaction_id varchar(255) DEFAULT NULL,
            status enum('requested','processing','completed','failed') NOT NULL DEFAULT 'requested',
            requested_at datetime NOT NULL,
            paid_at datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY status (status),
            KEY transaction_id (transaction_id)
        ) {$charset_collate};";

        dbDelta($sql);
    }
}

