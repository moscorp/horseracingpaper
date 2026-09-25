<?php
/**
 * Horse Racing Paper theme bootstrap.
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    register_nav_menus([
        'hrp_primary' => __('主選單', 'horse-racing-paper'),
    ]);
});

add_action('wp_enqueue_scripts', static function (): void {
    $ver = wp_get_theme()->get('Version') ?: '0.1.0';
    wp_enqueue_style(
        'hrp-fonts',
        'https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;600;700&family=Noto+Serif+TC:wght@600;700&family=JetBrains+Mono:wght@400;600&display=swap',
        [],
        null
    );
    wp_enqueue_style('hrp-theme', get_stylesheet_uri(), ['hrp-fonts'], $ver);
    wp_enqueue_script(
        'hrp-theme',
        get_template_directory_uri() . '/assets/js/race-day.js',
        [],
        $ver,
        true
    );
});
