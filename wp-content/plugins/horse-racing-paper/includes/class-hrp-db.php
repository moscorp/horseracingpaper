<?php
/**
 * Database access — uses WordPress $wpdb (same fengrmkw_moosay DB).
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

final class HRP_DB
{
    /** @return wpdb */
    public static function wpdb()
    {
        global $wpdb;
        return $wpdb;
    }

    public static function table(string $name): string
    {
        // Custom tables are not WP-prefixed.
        return $name;
    }

    public static function prepare(string $sql, ...$args): string
    {
        $wpdb = self::wpdb();
        if (!$args) {
            return $sql;
        }
        return $wpdb->prepare($sql, ...$args);
    }

    /** @return array<int, object> */
    public static function get_results(string $sql, ...$args): array
    {
        $wpdb = self::wpdb();
        $query = $args ? $wpdb->prepare($sql, ...$args) : $sql;
        $rows = $wpdb->get_results($query);
        return is_array($rows) ? $rows : [];
    }

    /** @return object|null */
    public static function get_row(string $sql, ...$args)
    {
        $wpdb = self::wpdb();
        $query = $args ? $wpdb->prepare($sql, ...$args) : $sql;
        $row = $wpdb->get_row($query);
        return $row ?: null;
    }

    /** @return mixed */
    public static function get_var(string $sql, ...$args)
    {
        $wpdb = self::wpdb();
        $query = $args ? $wpdb->prepare($sql, ...$args) : $sql;
        return $wpdb->get_var($query);
    }
}
