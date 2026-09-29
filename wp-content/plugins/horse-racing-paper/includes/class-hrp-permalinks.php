<?php
/**
 * Permalinks + admin guidance.
 *
 * 「文章名稱」and「自訂結構」with /%postname%/ are the same pretty-URL format.
 * WordPress always snaps the radio back to「文章名稱」when they match — that is normal.
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

final class HRP_Permalinks
{
    public const STRUCTURE = '/%postname%/';

    public static function init(): void
    {
        add_action('admin_init', [self::class, 'ensure_structure'], 5);
        add_action('admin_notices', [self::class, 'admin_notice']);
    }

    public static function ensure_structure(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $current = (string) get_option('permalink_structure');
        if ($current === self::STRUCTURE) {
            return;
        }
        // Only auto-fix empty / plain structures — never overwrite exotic custom ones.
        if ($current === '' || $current === '/index.php/%postname%/') {
            update_option('permalink_structure', self::STRUCTURE);
            flush_rewrite_rules(false);
        }
    }

    public static function admin_notice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'options-permalink') {
            return;
        }
        $current = (string) get_option('permalink_structure');
        echo '<div class="notice notice-info"><p>';
        echo '<strong>自由馬紙：</strong> ';
        if ($current === self::STRUCTURE || $current === '') {
            echo '請保持「文章名稱」或自訂結構 <code>/%postname%/</code>。兩者相同；儲存後 WordPress 會自動勾選「文章名稱」，這是正常行為，不是設定失敗。';
        } else {
            echo '目前結構為 <code>' . esc_html($current) . '</code>。賽馬文章網址建議使用 <code>/%postname%/</code>（文章名稱）。';
        }
        echo ' 若文章內容顯示錯誤，請到 <strong>LiteSpeed Cache → Toolbox → Purge All</strong> 清除快取。';
        echo '</p></div>';
    }
}
