<?php

namespace DirectoristSimpleAffiliate\Database\Tables;

/**
 * Affiliate codes table structure and management
 */
class AffiliateCodesTable
{
    /**
     * Get table name with WordPress prefix
     *
     * @return string
     */
    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'dsa_codes';
    }

    /**
     * Create the affiliate_codes table
     *
     * @param string $charset_collate
     */
    public static function create($charset_collate)
    {
        global $wpdb;

        $table_name = self::get_table_name();
        $affiliates_table = AffiliatesTable::get_table_name();

        // Note: dbDelta doesn't handle foreign keys well, so we'll create the table first
        // and then add the foreign key constraint separately if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) UNSIGNED NOT NULL,
            code varchar(100) NOT NULL,
            type enum('default','custom','campaign') NOT NULL DEFAULT 'default',
            campaign_name varchar(255) DEFAULT NULL,
            description text DEFAULT NULL,
            clicks int(11) NOT NULL DEFAULT 0,
            conversions int(11) NOT NULL DEFAULT 0,
            status enum('active','inactive','expired') NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY affiliate_id (affiliate_id),
            KEY status (status)
        ) {$charset_collate};";

        dbDelta($sql);

        // Add foreign key constraint separately (dbDelta doesn't handle it well)
        $fk_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = %s 
            AND TABLE_NAME = %s 
            AND CONSTRAINT_NAME = 'fk_affiliate_codes_affiliate_id'",
            DB_NAME,
            $table_name
        ));

        if (!$fk_exists) {
            $wpdb->query("ALTER TABLE {$table_name} 
                ADD CONSTRAINT fk_affiliate_codes_affiliate_id 
                FOREIGN KEY (affiliate_id) REFERENCES {$affiliates_table}(id) ON DELETE CASCADE");
        }
    }
}

