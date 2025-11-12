<?php

namespace DirectoristSimpleAffiliate\Database\Managers;

use DirectoristSimpleAffiliate\Database\Tables\AffiliatesTable;

/**
 * Manager class for affiliates table
 * Handles read, write, and data retrieval operations
 */
class AffiliatesManager
{
    /**
     * Get table name
     *
     * @return string
     */
    public static function get_table_name()
    {
        return AffiliatesTable::get_table_name();
    }

    /**
     * Insert a new affiliate
     *
     * @param array $data
     * @return int|false The number of rows inserted, or false on error
     */
    public static function insert($data)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $defaults = [
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $data = wp_parse_args($data, $defaults);

        return $wpdb->insert($table_name, $data);
    }

    /**
     * Update an affiliate
     *
     * @param int $id
     * @param array $data
     * @return int|false The number of rows updated, or false on error
     */
    public static function update($id, $data)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $data['updated_at'] = current_time('mysql');

        return $wpdb->update(
            $table_name,
            $data,
            ['id' => $id],
            null,
            ['%d']
        );
    }

    /**
     * Delete an affiliate
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
     * Get affiliate by ID
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
     * Get affiliate by user ID
     *
     * @param int $user_id
     * @return object|null
     */
    public static function get_by_user_id($user_id)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE user_id = %d",
            $user_id
        ));
    }


    /**
     * Get all affiliates
     *
     * @param array $args
     * @return array
     */
    public static function get_all($args = [])
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $defaults = [
            'status' => '',
            'limit' => -1,
            'offset' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);

        $where = [];
        $where_values = [];

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $where_values[] = $args['status'];
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
     * Count affiliates
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

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $where_values[] = $args['status'];
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
}

