<?php
/**
 * Databázová trieda - pomocné funkcie pre prácu s databázou
 */

if (!defined('ABSPATH')) {
    exit;
}

class BBK_Database {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Získanie názvu tabuľky
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'bbk_' . $table;
    }

    /**
     * Insert do tabuľky
     */
    public static function insert($table, $data) {
        global $wpdb;
        $table_name = self::get_table_name($table);

        // Automaticky pridaj created_at ak nie je nastavené
        if (!isset($data['created_at'])) {
            $data['created_at'] = current_time('mysql');
        }

        // Automaticky pridaj updated_at pre tabuľku books
        if ($table === 'books' && !isset($data['updated_at'])) {
            $data['updated_at'] = current_time('mysql');
        }

        $wpdb->insert($table_name, $data);

        return $wpdb->insert_id;
    }

    /**
     * Update tabuľky
     */
    public static function update($table, $data, $where) {
        global $wpdb;
        $table_name = self::get_table_name($table);

        // Automaticky aktualizuj updated_at pre tabuľku books
        if ($table === 'books' && !isset($data['updated_at'])) {
            $data['updated_at'] = current_time('mysql');
        }

        return $wpdb->update($table_name, $data, $where);
    }

    /**
     * Delete z tabuľky
     */
    public static function delete($table, $where) {
        global $wpdb;
        $table_name = self::get_table_name($table);

        return $wpdb->delete($table_name, $where);
    }

    /**
     * Získanie riadku
     */
    public static function get_row($table, $where, $output = OBJECT) {
        global $wpdb;
        $table_name = self::get_table_name($table);

        $where_clause = self::build_where_clause($where);
        $query = "SELECT * FROM {$table_name} WHERE {$where_clause}";

        return $wpdb->get_row($query, $output);
    }

    /**
     * Získanie výsledkov
     */
    public static function get_results($table, $where = array(), $output = OBJECT, $order_by = null, $limit = null) {
        global $wpdb;
        $table_name = self::get_table_name($table);

        $query = "SELECT * FROM {$table_name}";

        if (!empty($where)) {
            $where_clause = self::build_where_clause($where);
            $query .= " WHERE {$where_clause}";
        }

        if ($order_by) {
            $query .= " ORDER BY {$order_by}";
        }

        if ($limit) {
            $query .= " LIMIT {$limit}";
        }

        return $wpdb->get_results($query, $output);
    }

    /**
     * Získanie počtu záznamov
     */
    public static function get_count($table, $where = array()) {
        global $wpdb;
        $table_name = self::get_table_name($table);

        $query = "SELECT COUNT(*) FROM {$table_name}";

        if (!empty($where)) {
            $where_clause = self::build_where_clause($where);
            $query .= " WHERE {$where_clause}";
        }

        return $wpdb->get_var($query);
    }

    /**
     * Pomocná funkcia pre WHERE clause
     */
    private static function build_where_clause($where) {
        global $wpdb;
        $conditions = array();

        foreach ($where as $key => $value) {
            if (is_null($value)) {
                $conditions[] = "`{$key}` IS NULL";
            } else {
                $conditions[] = $wpdb->prepare("`{$key}` = %s", $value);
            }
        }

        return implode(' AND ', $conditions);
    }
}
