<?php
/**
 * Plugin Name: Horse Racing Paper
 * Description: 賽馬報紙 — racing data, Mark Six, funds email, cron registry, dense admin console. Replaces legacy /00 scripts over time.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Moscorp
 * Text Domain: horse-racing-paper
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HRP_VERSION', '0.1.0');
define('HRP_PLUGIN_FILE', __FILE__);
define('HRP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HRP_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HRP_PLUGIN_DIR . 'includes/class-hrp-settings.php';
require_once HRP_PLUGIN_DIR . 'includes/class-hrp-cron-registry.php';
require_once HRP_PLUGIN_DIR . 'includes/class-hrp-admin.php';
require_once HRP_PLUGIN_DIR . 'includes/class-hrp-shortcodes.php';

add_action('plugins_loaded', static function (): void {
    HRP_Settings::init();
    HRP_Cron_Registry::init();
    HRP_Admin::init();
    HRP_Shortcodes::init();
});

register_activation_hook(__FILE__, static function (): void {
    HRP_Settings::seed_defaults();
    HRP_Cron_Registry::seed_jobs();
});
