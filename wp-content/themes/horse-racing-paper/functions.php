<?php
/**
 * 自由馬紙 theme bootstrap.
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme asset version — filemtime so CDN/browser cannot keep stale JS/CSS.
 */
function hrp_asset_ver(string $relative): string
{
    $path = get_template_directory() . $relative;
    $mtime = file_exists($path) ? (string) filemtime($path) : '';
    $theme = (string) (wp_get_theme()->get('Version') ?: '0');
    return $theme . ($mtime !== '' ? '.' . $mtime : '');
}

/**
 * Ask LiteSpeed / known caches to drop everything (safe to call repeatedly).
 */
function hrp_purge_page_caches(): void
{
    if (!headers_sent()) {
        // LiteSpeed Cache honors this response header.
        header('X-LiteSpeed-Purge: *');
    }
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

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    register_nav_menus([
        'hrp_primary' => __('主選單', 'horse-racing-paper'),
    ]);
});

// Run early so a single uncached hit after deploy can wipe stale HTML.
add_action('init', static function (): void {
    $ver = (string) (wp_get_theme()->get('Version') ?: '');
    if ($ver === '') {
        return;
    }
    if (get_option('hrp_deployed_theme_ver') !== $ver) {
        update_option('hrp_deployed_theme_ver', $ver, false);
        update_option('hrp_force_purge_once', $ver, false);
    }

    // Manual purge: /?hrp_purge=1 (also used by deploy smoke curl).
    $want = isset($_GET['hrp_purge']) && (string) $_GET['hrp_purge'] !== '';
    $pending = get_option('hrp_force_purge_once');
    if ($want || ($pending && $pending === $ver)) {
        hrp_purge_page_caches();
        if ($pending) {
            delete_option('hrp_force_purge_once');
        }
    }
}, 0);

add_action('send_headers', static function (): void {
    if (is_admin()) {
        return;
    }
    // Do not let LiteSpeed/browser keep bad HTML for hours.
    header('Cache-Control: private, max-age=0, must-revalidate');
    if (function_exists('do_action')) {
        do_action('litespeed_control_set_nocache', 'hrp html must revalidate');
    }
    // Pending purge flag still set → keep sending purge header until cleared on init.
    $ver = (string) (wp_get_theme()->get('Version') ?: '');
    if ($ver !== '' && get_option('hrp_force_purge_once') === $ver && !headers_sent()) {
        header('X-LiteSpeed-Purge: *');
    }
}, 0);

/**
 * Append theme version to content links so LiteSpeed/CDN cannot keep serving
 * pre-fix HTML for the same pretty permalink.
 */
function hrp_link_cache_bust(string $url): string
{
    if ($url === '' || strpos($url, 'hrpv=') !== false) {
        return $url;
    }
    $ver = (string) (wp_get_theme()->get('Version') ?: '0');
    return add_query_arg('hrpv', $ver, $url);
}

add_filter('post_link', static function ($url) {
    return hrp_link_cache_bust((string) $url);
}, 99);
add_filter('page_link', static function ($url) {
    return hrp_link_cache_bust((string) $url);
}, 99);
add_filter('post_type_link', static function ($url) {
    return hrp_link_cache_bust((string) $url);
}, 99);
add_filter('term_link', static function ($url) {
    return hrp_link_cache_bust((string) $url);
}, 99);
add_filter('year_link', 'hrp_link_cache_bust', 99);
add_filter('month_link', 'hrp_link_cache_bust', 99);
add_filter('day_link', 'hrp_link_cache_bust', 99);

add_action('wp_enqueue_scripts', static function (): void {
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
        hrp_asset_ver('/assets/css/hrp.css')
    );
    wp_enqueue_script(
        'hrp-theme',
        get_template_directory_uri() . '/assets/js/theme.js',
        [],
        hrp_asset_ver('/assets/js/theme.js'),
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
