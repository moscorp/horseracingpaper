<?php
/**
 * Compatibility shims for legacy AliDropship (alids) blog templates.
 * Old storefront theme defined getPostViews()/setPostViews(); without them
 * alids/template/blog/tpl/_single.php fatals.
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('getPostViews')) {
    /**
     * @param int|string $postID
     * @return string
     */
    function getPostViews($postID)
    {
        $postID = (int) $postID;
        $count_key = 'post_views_count';
        $count = get_post_meta($postID, $count_key, true);
        if ($count === '' || $count === false) {
            delete_post_meta($postID, $count_key);
            add_post_meta($postID, $count_key, '0');
            return '0';
        }
        return (string) $count;
    }
}

if (!function_exists('setPostViews')) {
    /**
     * @param int|string $postID
     * @return void
     */
    function setPostViews($postID)
    {
        $postID = (int) $postID;
        $count_key = 'post_views_count';
        $count = get_post_meta($postID, $count_key, true);
        if ($count === '' || $count === false) {
            $count = 0;
            delete_post_meta($postID, $count_key);
            add_post_meta($postID, $count_key, '0');
            return;
        }
        $count = (int) $count + 1;
        update_post_meta($postID, $count_key, (string) $count);
    }
}

// Reduce prefetch double-counting when setPostViews is used.
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
