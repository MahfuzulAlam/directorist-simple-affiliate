<?php

namespace DirectoristSimpleAffiliate\Database\Tables;

/**
 * Affiliate visits table structure and management
 */
class AffiliateVisitsTable
{
    /**
     * Get table name with WordPress prefix
     *
     * @return string
     */
    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'dsa_affiliate_visits';
    }

    /**
     * Create the affiliate_visits table
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
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            referrer_url text DEFAULT NULL,
            landing_url text DEFAULT NULL,
            converted tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY code_id (code_id),
            KEY ip_address (ip_address),
            KEY converted (converted),
            KEY created_at (created_at),
            KEY affiliate_ip (affiliate_id, ip_address, created_at)
        ) {$charset_collate};";

        dbDelta($sql);
    }
}

