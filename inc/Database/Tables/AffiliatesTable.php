<?php

namespace DirectoristSimpleAffiliate\Database\Tables;

/**
 * Affiliates table structure and management
 */
class AffiliatesTable
{
    /**
     * Get table name with WordPress prefix
     *
     * @return string
     */
    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'dsa_affiliates';
    }

    /**
     * Create the affiliates table
     *
     * @param string $charset_collate
     */
    public static function create($charset_collate)
    {
        global $wpdb;

        $table_name = self::get_table_name();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            status enum('pending','active','suspended','rejected') NOT NULL DEFAULT 'pending',
            commission_rate decimal(5,2) DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY status (status)
        ) {$charset_collate};";

        dbDelta($sql);
    }
}

