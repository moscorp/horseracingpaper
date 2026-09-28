<?php
/**
 * 自由馬紙 theme bootstrap.
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    register_nav_menus([
        'hrp_primary' => __('主選單', 'horse-racing-paper'),
    ]);

    // Purge LiteSpeed / known page caches when theme version changes after deploy.
    $ver = (string) (wp_get_theme()->get('Version') ?: '');
    if ($ver !== '' && get_option('hrp_deployed_theme_ver') !== $ver) {
        update_option('hrp_deployed_theme_ver', $ver, false);
        if (has_action('litespeed_purge_all')) {
            do_action('litespeed_purge_all');
        }
        if (class_exists('LiteSpeed\Purge') && method_exists('LiteSpeed\Purge', 'purge_all')) {
            \LiteSpeed\Purge::purge_all();
        }
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
    }
});

// Avoid 8h browser HTML cache of wrong singular pages (LiteSpeed was sending max-age=28800).
add_action('send_headers', static function (): void {
    if (is_admin()) {
        return;
    }
    header('Cache-Control: private, max-age=0, must-revalidate');
}, 99);

add_action('wp_enqueue_scripts', static function (): void {
    $ver = wp_get_theme()->get('Version') ?: '0.3.0';
    wp_enqueue_style(
        'hrp-fonts',
        'https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;600;700&family=Noto+Serif+TC:wght@600;700&family=JetBrains+Mono:wght@400;600&display=swap',
        [],
        null
    );
    wp_enqueue_style(
        'hrp-theme',
        get_template_directory_uri() . '/assets/css/hrp.css',
        ['hrp-fonts'],
        $ver
    );
    wp_enqueue_script(
        'hrp-theme',
        get_template_directory_uri() . '/assets/js/theme.js',
        [],
        $ver,
        true
    );
}, 5);

add_action('wp_head', static function (): void {
    $favicon = class_exists('HRP_Settings')
        ? HRP_Settings::favicon_url()
        : get_template_directory_uri() . '/assets/images/favicon.png';
    $apple = get_template_directory_uri() . '/assets/images/apple-touch-icon.png';
    echo '<link rel="icon" href="' . esc_url($favicon) . '" sizes="any">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . esc_url($apple) . '">' . "\n";
}, 1);

/**
 * Shared layout helpers.
 */
function hrp_brand_title(): string
{
    if (class_exists('HRP_Settings')) {
        return (string) HRP_Settings::get_value('site_title_zh', '自由馬紙');
    }
    return get_bloginfo('name') ?: '自由馬紙';
}

function hrp_brand_subtitle(): string
{
    if (class_exists('HRP_Settings')) {
        return (string) HRP_Settings::get_value('site_title_en', "Carl's Racing Paper");
    }
    return get_bloginfo('description') ?: "Carl's Racing Paper";
}

function hrp_logo_url(): string
{
    if (class_exists('HRP_Settings')) {
        return HRP_Settings::logo_url();
    }
    return get_template_directory_uri() . '/assets/images/logo.png';
}

function hrp_page_url(string $key): string
{
    if (class_exists('HRP_Pages')) {
        return HRP_Pages::url($key);
    }
    $map = ['about' => 'about', 'privacy' => 'privacy', 'tos' => 'terms'];
    return home_url('/' . ($map[$key] ?? $key) . '/');
}
