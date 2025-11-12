<?php

namespace DirectoristSimpleAffiliate\Database\Managers;

use DirectoristSimpleAffiliate\Database\Tables\AffiliateCodesTable;

/**
 * Manager class for affiliate_codes table
 * Handles read, write, and data retrieval operations
 */
class AffiliateCodesManager
{
    /**
     * Get table name
     *
     * @return string
     */
    public static function get_table_name()
    {
        return AffiliateCodesTable::get_table_name();
    }

    /**
     * Insert a new affiliate code
     *
     * @param array $data
     * @return int|false The number of rows inserted, or false on error
     */
    public static function insert($data)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $defaults = [
            'type' => 'default',
            'clicks' => 0,
            'conversions' => 0,
            'status' => 'active',
            'created_at' => current_time('mysql'),
        ];

        $data = wp_parse_args($data, $defaults);

        return $wpdb->insert($table_name, $data);
    }

    /**
     * Update an affiliate code
     *
     * @param int $id
     * @param array $data
     * @return int|false The number of rows updated, or false on error
     */
    public static function update($id, $data)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        return $wpdb->update(
            $table_name,
            $data,
            ['id' => $id],
            null,
            ['%d']
        );
    }

    /**
     * Delete an affiliate code
     *
     * @param int $id
     * @return int|false The number of rows deleted, or false on error
     */
    public static function delete($id)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        return $wpdb->delete($table_name, ['id' => $id], ['%d']);
    }

    /**
     * Get affiliate code by ID
     *
     * @param int $id
     * @return object|null
     */
    public static function get($id)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $id
        ));
    }

    /**
     * Get affiliate code by code string
     *
     * @param string $code
     * @return object|null
     */
    public static function get_by_code($code)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE code = %s",
            $code
        ));
    }

    /**
     * Get codes by affiliate ID
     *
     * @param int $affiliate_id
     * @param array $args
     * @return array
     */
    public static function get_by_affiliate_id($affiliate_id, $args = [])
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $defaults = [
            'status' => '',
            'type' => '',
            'limit' => -1,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ["affiliate_id = %d"];
        $where_values = [$affiliate_id];

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $where_values[] = $args['status'];
        }

        if (!empty($args['type'])) {
            $where[] = "type = %s";
            $where_values[] = $args['type'];
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where);
        $limit_clause = $args['limit'] > 0 ? $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $args['offset']) : '';

        $orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}");
        $order_clause = $orderby ? "ORDER BY {$orderby}" : '';

        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} {$where_clause} {$order_clause} {$limit_clause}",
            $where_values
        );

        return $wpdb->get_results($query);
    }

    /**
     * Get all affiliate codes
     *
     * @param array $args
     * @return array
     */
    public static function get_all($args = [])
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $defaults = [
            'affiliate_id' => 0,
            'status' => '',
            'type' => '',
            'limit' => -1,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);

        $where = [];
        $where_values = [];

        if ($args['affiliate_id'] > 0) {
            $where[] = "affiliate_id = %d";
            $where_values[] = $args['affiliate_id'];
        }

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $where_values[] = $args['status'];
        }

        if (!empty($args['type'])) {
            $where[] = "type = %s";
            $where_values[] = $args['type'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit_clause = $args['limit'] > 0 ? $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $args['offset']) : '';

        $orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}");
        $order_clause = $orderby ? "ORDER BY {$orderby}" : '';

        if (!empty($where_values)) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$table_name} {$where_clause} {$order_clause} {$limit_clause}",
                $where_values
            );
        } else {
            $query = "SELECT * FROM {$table_name} {$order_clause} {$limit_clause}";
        }

        return $wpdb->get_results($query);
    }

    /**
     * Count affiliate codes
     *
     * @param array $args
     * @return int
     */
    public static function count($args = [])
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $where = [];
        $where_values = [];

        if (!empty($args['affiliate_id'])) {
            $where[] = "affiliate_id = %d";
            $where_values[] = $args['affiliate_id'];
        }

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $where_values[] = $args['status'];
        }

        if (!empty($args['type'])) {
            $where[] = "type = %s";
            $where_values[] = $args['type'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        if (!empty($where_values)) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} {$where_clause}",
                $where_values
            );
        } else {
            $query = "SELECT COUNT(*) FROM {$table_name}";
        }

        return (int) $wpdb->get_var($query);
    }

    /**
     * Increment clicks for a code
     *
     * @param int $id
     * @return int|false
     */
    public static function increment_clicks($id)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        return $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} SET clicks = clicks + 1 WHERE id = %d",
            $id
        ));
    }

    /**
     * Increment conversions for a code
     *
     * @param int $id
     * @return int|false
     */
    public static function increment_conversions($id)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        return $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} SET conversions = conversions + 1 WHERE id = %d",
            $id
        ));
    }
}

