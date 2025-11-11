<?php

namespace DirectoristSimpleAffiliate\Database\Tables;

/**
 * Referrals table structure and management
 */
class ReferralsTable
{
    /**
     * Get table name with WordPress prefix
     *
     * @return string
     */
    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'dsa_referrals';
    }

    /**
     * Create the referrals table
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
            code_id bigint(20) UNSIGNED DEFAULT NULL,
            order_id bigint(20) UNSIGNED DEFAULT NULL,
            customer_user_id bigint(20) UNSIGNED DEFAULT NULL,
            product_id bigint(20) UNSIGNED DEFAULT NULL,
            order_amount decimal(10,2) DEFAULT NULL,
            commission_amount decimal(10,2) DEFAULT NULL,
            commission_rate decimal(5,2) DEFAULT NULL,
            status enum('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
            payout_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL,
            approved_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY code_id (code_id),
            KEY order_id (order_id),
            KEY customer_user_id (customer_user_id),
            KEY status (status),
            KEY payout_id (payout_id)
        ) {$charset_collate};";

        dbDelta($sql);
    }
}

