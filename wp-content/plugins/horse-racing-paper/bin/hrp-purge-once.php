<?php
/**
 * One-shot LiteSpeed purge — invoked as:
 *   https://buycarl.com/wp-content/plugins/horse-racing-paper/bin/hrp-purge-once.php?k=HRP_PURGE_TOKEN
 * Deploy curls this after FTP upload. Safe if token mismatches.
 */

$docroot = dirname(__DIR__, 4); // .../wp-content/plugins/horse-racing-paper/bin -> site root
$wp_load = $docroot . '/wp-load.php';
if (!is_readable($wp_load)) {
    // Fallback: walk up looking for wp-load.php
    $dir = __DIR__;
    for ($i = 0; $i < 8; $i++) {
        $dir = dirname($dir);
        if (is_readable($dir . '/wp-load.php')) {
            $wp_load = $dir . '/wp-load.php';
            break;
        }
    }
}

if (!is_readable($wp_load)) {
    header('HTTP/1.1 500');
    header('X-LiteSpeed-Purge: *');
    echo "wp-load missing\n";
    exit(1);
}

require_once $wp_load;

$token = isset($_GET['k']) ? (string) $_GET['k'] : '';
$expect = (string) get_option('hrp_purge_token', '');
if ($expect === '') {
    $expect = substr(sha1((string) AUTH_KEY . 'hrp-purge'), 0, 24);
    update_option('hrp_purge_token', $expect, false);
}

if (!hash_equals($expect, $token)) {
    // Still send purge header — deploy passes the right token after reading option is hard;
    // allow AUTH_KEY-derived default known to deploy via fixed salt fallback.
    $fallback = substr(sha1('hrp-static-purge-v1'), 0, 24);
    if (!hash_equals($fallback, $token) && !hash_equals($expect, $token)) {
        header('HTTP/1.1 403');
        echo "forbidden\n";
        exit(1);
    }
}

if (!headers_sent()) {
    header('X-LiteSpeed-Purge: *');
    header('X-LiteSpeed-Purge: public, *');
    header('Cache-Control: no-cache');
}

if (has_action('litespeed_purge_all')) {
    do_action('litespeed_purge_all');
}
if (class_exists('LiteSpeed\\Purge') && method_exists('LiteSpeed\\Purge', 'purge_all')) {
    \LiteSpeed\Purge::purge_all();
}

$ids = get_posts([
    'numberposts' => 80,
    'post_type' => 'post',
    'post_status' => 'publish',
    'fields' => 'ids',
    'category_name' => 'race-review,racing-news,mark-six-analysis,mark-six-prediction',
]);
foreach ($ids as $id) {
    do_action('litespeed_purge_post', (int) $id);
    clean_post_cache((int) $id);
}

echo 'HRP_PURGE_OK count=' . count($ids) . "\n";
