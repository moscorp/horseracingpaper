<?php
/**
 * Ensure About / Privacy / ToS pages exist and sync from settings.
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

final class HRP_Pages
{
    public const SLUGS = [
        'about' => ['slug' => 'about', 'title' => '關於我們'],
        'privacy' => ['slug' => 'privacy', 'title' => '私隱政策'],
        'tos' => ['slug' => 'terms', 'title' => '使用條款'],
    ];

    public static function init(): void
    {
        add_action('update_option_' . HRP_Settings::OPTION_KEY, [self::class, 'sync_from_settings'], 10, 0);
        add_action('admin_init', [self::class, 'maybe_ensure'], 20);
    }

    public static function maybe_ensure(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (get_option('hrp_pages_seeded') === '1') {
            return;
        }
        self::ensure_pages();
        update_option('hrp_pages_seeded', '1', false);
    }

    public static function sync_from_settings(): void
    {
        self::ensure_pages();
    }

    public static function ensure_pages(): void
    {
        $settings = HRP_Settings::get();
        $pages = $settings['pages'] ?? [];

        foreach (self::SLUGS as $key => $meta) {
            $content = (string) ($pages[$key] ?? '');
            $existing = get_page_by_path($meta['slug']);
            $postarr = [
                'post_title' => $meta['title'],
                'post_name' => $meta['slug'],
                'post_content' => $content !== '' ? wpautop($content) : '',
                'post_status' => 'publish',
                'post_type' => 'page',
            ];
            if ($existing instanceof WP_Post) {
                $postarr['ID'] = $existing->ID;
                wp_update_post($postarr);
            } else {
                wp_insert_post($postarr, true);
            }
        }
    }

    public static function url(string $key): string
    {
        $meta = self::SLUGS[$key] ?? null;
        if (!$meta) {
            return home_url('/');
        }
        $page = get_page_by_path($meta['slug']);
        if ($page instanceof WP_Post) {
            return get_permalink($page);
        }
        return home_url('/' . $meta['slug'] . '/');
    }
}
