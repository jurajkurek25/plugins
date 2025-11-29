<?php
/**
 * Databázová trieda - helper funkcie pre databázové operácie
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Database {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Inicializácia
    }

    /**
     * Získanie názvu tabuľky
     */
    public static function get_table_name($table) {
        global $wpdb;
        $tables = array(
            'books' => $wpdb->prefix . 'kk_books',
            'lendings' => $wpdb->prefix . 'kk_lendings',
            'ratings' => $wpdb->prefix . 'kk_ratings',
            'notifications' => $wpdb->prefix . 'kk_notifications'
        );
        return isset($tables[$table]) ? $tables[$table] : '';
    }

    /**
     * Vloženie záznamu
     */
    public static function insert($table, $data, $format = null) {
        global $wpdb;
        $table_name = self::get_table_name($table);

        if (empty($table_name)) {
            return false;
        }

        $result = $wpdb->insert($table_name, $data, $format);

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Aktualizácia záznamu
     */
    public static function update($table, $data, $where, $format = null, $where_format = null) {
        global $wpdb;
        $table_name = self::get_table_name($table);

        if (empty($table_name)) {
            return false;
        }

        return $wpdb->update($table_name, $data, $where, $format, $where_format);
    }

    /**
     * Zmazanie záznamu
     */
    public static function delete($table, $where, $where_format = null) {
        global $wpdb;
        $table_name = self::get_table_name($table);

        if (empty($table_name)) {
            return false;
        }

        return $wpdb->delete($table_name, $where, $where_format);
    }

    /**
     * Získanie jedného záznamu
     */
    public static function get_row($table, $where, $output = OBJECT) {
        global $wpdb;
        $table_name = self::get_table_name($table);

        if (empty($table_name)) {
            return null;
        }

        $where_clause = self::build_where_clause($where);
        $query = "SELECT * FROM {$table_name} WHERE {$where_clause}";

        return $wpdb->get_row($wpdb->prepare($query, array_values($where)), $output);
    }

    /**
     * Získanie viacerých záznamov
     */
    public static function get_results($table, $where = array(), $output = OBJECT, $order_by = 'id DESC', $limit = null) {
        global $wpdb;
        $table_name = self::get_table_name($table);

        if (empty($table_name)) {
            return array();
        }

        $query = "SELECT * FROM {$table_name}";

        if (!empty($where)) {
            $where_clause = self::build_where_clause($where);
            $query .= " WHERE {$where_clause}";
            $query = $wpdb->prepare($query, array_values($where));
        }

        if (!empty($order_by)) {
            $query .= " ORDER BY {$order_by}";
        }

        if (!empty($limit)) {
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

        if (empty($table_name)) {
            return 0;
        }

        $query = "SELECT COUNT(*) FROM {$table_name}";

        if (!empty($where)) {
            $where_clause = self::build_where_clause($where);
            $query .= " WHERE {$where_clause}";
            $query = $wpdb->prepare($query, array_values($where));
        }

        return (int) $wpdb->get_var($query);
    }

    /**
     * Vytvorenie WHERE podmienky
     */
    private static function build_where_clause($where) {
        $conditions = array();
        foreach ($where as $key => $value) {
            $conditions[] = "{$key} = %s";
        }
        return implode(' AND ', $conditions);
    }

    /**
     * Vykonanie vlastného query
     */
    public static function query($query) {
        global $wpdb;
        return $wpdb->query($query);
    }

    /**
     * Získanie výsledkov vlastného query
     */
    public static function get_query_results($query, $output = OBJECT) {
        global $wpdb;
        return $wpdb->get_results($query, $output);
    }
}
