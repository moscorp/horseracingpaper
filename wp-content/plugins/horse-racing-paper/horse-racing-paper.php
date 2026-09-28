<?php
/**
 * Plugin Name: Horse Racing Paper
 * Description: 自由馬紙（Carl's Racing Paper）— racing data, Mark Six, funds email, cron registry, dense admin console. Replaces legacy /00 scripts over time.
 * Version: 0.3.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Moscorp
 * Text Domain: horse-racing-paper
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HRP_VERSION', '0.3.2');
define('HRP_PLUGIN_FILE', __FILE__);
define('HRP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HRP_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HRP_PLUGIN_DIR . 'includes/class-hrp-compat.php';
require_once HRP_PLUGIN_DIR . 'includes/class-hrp-settings.php';
require_once HRP_PLUGIN_DIR . 'includes/class-hrp-db.php';
require_once HRP_PLUGIN_DIR . 'includes/class-hrp-race-repository.php';
require_once HRP_PLUGIN_DIR . 'includes/class-hrp-cron-registry.php';
require_once HRP_PLUGIN_DIR . 'includes/class-hrp-pages.php';
require_once HRP_PLUGIN_DIR . 'includes/class-hrp-nav.php';
require_once HRP_PLUGIN_DIR . 'includes/class-hrp-admin.php';
require_once HRP_PLUGIN_DIR . 'includes/class-hrp-shortcodes.php';

add_action('plugins_loaded', static function (): void {
    HRP_Settings::init();
    HRP_Cron_Registry::init();
    HRP_Pages::init();
    HRP_Admin::init();
    HRP_Shortcodes::init();

    // One-time brand rename from earlier scaffold defaults.
    if (get_option('hrp_brand_ziyou_migrated') !== '1') {
        $s = HRP_Settings::get();
        $changed = false;
        if (($s['site_title_zh'] ?? '') === '賽馬報紙') {
            $s['site_title_zh'] = '自由馬紙';
            $changed = true;
        }
        if (empty($s['site_title_en'])) {
            $s['site_title_en'] = "Carl's Racing Paper";
            $changed = true;
        }
        if ($changed) {
            update_option(HRP_Settings::OPTION_KEY, $s, false);
        }
        update_option('hrp_brand_ziyou_migrated', '1', false);
    }

    // Map Chinese placeholder category names → live buycarl slugs.
    if (get_option('hrp_nav_cats_v031') !== '1') {
        $s = HRP_Settings::get();
        $map = [
            'category_racing_result' => 'race-review',
            'category_racing_analysis' => 'racing-news',
            'category_m6_result' => 'mark-six-analysis',
            'category_m6_analysis' => 'mark-six-prediction',
            'category_racing' => 'racing-news',
            'category_review' => 'race-review',
            'category_m6' => 'mark-six-prediction',
        ];
        $zh = ['賽事結果', '賽事分析', '六合彩結果', '六合彩分析', '賽後回顧', '六合彩'];
        foreach ($map as $key => $slug) {
            $cur = (string) ($s['blog'][$key] ?? '');
            if ($cur === '' || in_array($cur, $zh, true)) {
                $s['blog'][$key] = $slug;
            }
        }
        update_option(HRP_Settings::OPTION_KEY, $s, false);
        update_option('hrp_nav_cats_v031', '1', false);
    }
});

register_activation_hook(__FILE__, static function (): void {
    HRP_Settings::seed_defaults();
    HRP_Cron_Registry::seed_jobs();
    HRP_Pages::ensure_pages();
    update_option('hrp_pages_seeded', '1', false);
});
