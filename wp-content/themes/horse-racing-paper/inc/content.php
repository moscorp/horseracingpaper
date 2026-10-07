<?php
/**
 * Single-post helpers + legacy cron HTML normalization.
 *
 * Cron blog posts ship alids-era inline purple/light CSS. The theme shell is
 * already Tailwind; this layer makes the body HTML read as 自由馬紙.
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Strip leading emoji / symbol noise from titles for denser chrome.
 */
function hrp_display_title(string $title): string
{
    $clean = preg_replace('/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}\s]+/u', '', $title);
    $clean = is_string($clean) ? trim($clean) : trim($title);
    return $clean !== '' ? $clean : $title;
}

/**
 * Chinese group label for a post's primary racing/M6 category.
 */
function hrp_post_group_label(WP_Post $post): string
{
    if (!class_exists('HRP_Nav')) {
        $cats = get_the_category($post->ID);
        return $cats ? (string) $cats[0]->name : '';
    }

    $map = [
        'racing_result' => '賽事結果',
        'racing_analysis' => '賽事分析',
        'm6_result' => '六合彩結果',
        'm6_analysis' => '六合彩分析',
    ];
    $aliases = HRP_Nav::category_aliases();
    $cats = get_the_category($post->ID);
    foreach ($cats as $cat) {
        $slug = (string) $cat->slug;
        $name = (string) $cat->name;
        foreach ($aliases as $key => $list) {
            foreach ($list as $alias) {
                if (strcasecmp($alias, $slug) === 0 || strcasecmp($alias, $name) === 0) {
                    return $map[$key] ?? $name;
                }
            }
        }
    }
    return $cats ? (string) $cats[0]->name : '';
}

/**
 * Archive URL for the post's mapped group, if any.
 */
function hrp_post_group_archive(WP_Post $post): string
{
    if (!class_exists('HRP_Nav')) {
        $cats = get_the_category($post->ID);
        return $cats ? (string) get_category_link($cats[0]) : '';
    }
    $aliases = HRP_Nav::category_aliases();
    $cats = get_the_category($post->ID);
    foreach ($cats as $cat) {
        $slug = (string) $cat->slug;
        $name = (string) $cat->name;
        foreach ($aliases as $list) {
            foreach ($list as $alias) {
                if (strcasecmp($alias, $slug) === 0 || strcasecmp($alias, $name) === 0) {
                    return (string) get_category_link($cat);
                }
            }
        }
    }
    $cats = get_the_category($post->ID);
    return $cats ? (string) get_category_link($cats[0]) : '';
}

/**
 * Adjacent published posts in the same primary category.
 *
 * @return array{prev:?WP_Post,next:?WP_Post}
 */
function hrp_adjacent_in_category(WP_Post $post): array
{
    $cats = get_the_category($post->ID);
    if (!$cats) {
        return ['prev' => null, 'next' => null];
    }
    $cat_id = (int) $cats[0]->term_id;

    $prev_q = new WP_Query([
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'cat' => $cat_id,
        'date_query' => [['before' => $post->post_date, 'inclusive' => false]],
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ]);
    $next_q = new WP_Query([
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'cat' => $cat_id,
        'date_query' => [['after' => $post->post_date, 'inclusive' => false]],
        'orderby' => 'date',
        'order' => 'ASC',
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ]);

    $prev = !empty($prev_q->posts[0]) && $prev_q->posts[0] instanceof WP_Post ? $prev_q->posts[0] : null;
    $next = !empty($next_q->posts[0]) && $next_q->posts[0] instanceof WP_Post ? $next_q->posts[0] : null;
    unset($prev_q, $next_q);

    return ['prev' => $prev, 'next' => $next];
}

/**
 * Remap alids/cron purple + system fonts to 自由馬紙 ink/brass.
 */
function hrp_normalize_legacy_post_html(string $html): string
{
    if ($html === '') {
        return $html;
    }

    // Drop machine comments used by cron publishers.
    $html = preg_replace('/<!--\s*racing_[^>]*-->/i', '', $html) ?? $html;

    // Gradients first (full tokens), then leftover accent hexes.
    $brand_grad = 'linear-gradient(135deg, #2a2218 0%, #1a222c 55%, #3a2e1c 100%)';
    $html = str_ireplace(
        [
            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'linear-gradient(135deg,#667eea 0%,#764ba2 100%)',
        ],
        $brand_grad,
        $html
    );
    $html = str_ireplace(['#667eea', '#764ba2'], ['#c4a35a', '#8a7340'], $html);

    $replace = [
        "font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" => 'font-family: "Noto Sans TC", "Source Han Sans TC", sans-serif',
        'font-family: -apple-system, BlinkMacSystemFont, &#039;Segoe UI&#039;, Roboto, sans-serif' => 'font-family: &quot;Noto Sans TC&quot;, &quot;Source Han Sans TC&quot;, sans-serif',
        'font-family: Arial, sans-serif' => 'font-family: "Noto Sans TC", "Source Han Sans TC", sans-serif',
        'font-family: Arial, sans-serif;' => 'font-family: "Noto Sans TC", "Source Han Sans TC", sans-serif;',
        'max-width:1200px' => 'max-width:100%',
        'max-width: 1200px' => 'max-width: 100%',
        'border-radius: 20px' => 'border-radius: 4px',
        'border-radius:20px' => 'border-radius:4px',
        'border-radius: 15px' => 'border-radius: 4px',
        'border-radius:15px' => 'border-radius:4px',
        'border-radius: 12px 0 0 0' => 'border-radius: 2px 0 0 0',
        'border-radius: 0 12px 0 0' => 'border-radius: 0 2px 0 0',
        'border-radius: 30px' => 'border-radius: 999px',
    ];
    $html = strtr($html, $replace);

    // Soften oversized light cards on the dark shell.
    $html = preg_replace(
        '/background:\s*#f8f9fa/i',
        'background: #f3f0e8; border: 1px solid #d9d2c3',
        $html
    ) ?? $html;

    return $html;
}

/**
 * Remove a leading content H1 that duplicates the theme title.
 */
function hrp_strip_duplicate_leading_heading(string $html, string $title): string
{
    $plain_title = hrp_display_title(wp_strip_all_tags($title));
    if ($plain_title === '' || $html === '') {
        return $html;
    }

    if (!preg_match('/^\s*(?:<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>\s*)*(<h1\b[^>]*>[\s\S]*?<\/h1>)/i', $html, $m)) {
        return $html;
    }

    $heading_text = hrp_display_title(wp_strip_all_tags($m[1]));
    $lower = static function (string $s): string {
        return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    };
    similar_text($lower($plain_title), $lower($heading_text), $pct);
    if ($pct >= 72) {
        $html = preg_replace('/^\s*(?:<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>\s*)*<h1\b[^>]*>[\s\S]*?<\/h1>/i', '', $html, 1) ?? $html;
    }
    return $html;
}

/**
 * Render prepared single-post HTML.
 */
function hrp_render_single_content(WP_Post $post): string
{
    $html = (string) $post->post_content;
    $html = hrp_normalize_legacy_post_html($html);
    $html = hrp_strip_duplicate_leading_heading($html, get_the_title($post));
    $html = apply_filters('the_content', $html);
    // Normalize again after shortcodes/embeds may reintroduce markup.
    return hrp_normalize_legacy_post_html($html);
}

add_filter('document_title_parts', static function (array $parts): array {
    if (!empty($parts['title'])) {
        $parts['title'] = hrp_display_title((string) $parts['title']);
    }
    if (class_exists('HRP_Settings')) {
        $parts['site'] = (string) HRP_Settings::get_value('site_title_zh', '自由馬紙');
    }
    return $parts;
}, 20);
