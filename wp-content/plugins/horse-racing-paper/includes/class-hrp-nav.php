<?php
/**
 * Hierarchical sidebar nav data (latest N per group).
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

final class HRP_Nav
{
    /**
     * @return array<int, array{key:string,label:string,category:string,archive:string,posts:array<int,WP_Post>}>
     */
    public static function groups(int $limit = 0): array
    {
        $s = HRP_Settings::get();
        $blog = $s['blog'] ?? [];
        if ($limit <= 0) {
            $limit = max(1, (int) ($blog['nav_limit'] ?? 6));
        }
        // Fetch one extra page worth so "More" can reveal without a second request.
        $fetch = $limit * 2;

        $defs = [
            [
                'key' => 'racing_result',
                'label' => '賽事結果',
                'category' => (string) ($blog['category_racing_result'] ?? '賽事結果'),
            ],
            [
                'key' => 'racing_analysis',
                'label' => '賽事分析',
                'category' => (string) ($blog['category_racing_analysis'] ?? $blog['category_racing'] ?? '賽事分析'),
            ],
            [
                'key' => 'm6_result',
                'label' => '六合彩結果',
                'category' => (string) ($blog['category_m6_result'] ?? '六合彩結果'),
            ],
            [
                'key' => 'm6_analysis',
                'label' => '六合彩分析',
                'category' => (string) ($blog['category_m6_analysis'] ?? $blog['category_m6'] ?? '六合彩分析'),
            ],
        ];

        $out = [];
        foreach ($defs as $def) {
            $term = self::resolve_category($def['category']);
            $posts = [];
            $archive = '';
            if ($term) {
                $archive = (string) get_category_link($term);
                $q = new WP_Query([
                    'posts_per_page' => $fetch,
                    'post_status' => 'publish',
                    'ignore_sticky_posts' => true,
                    'cat' => (int) $term->term_id,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'no_found_rows' => true,
                ]);
                $posts = $q->posts;
                wp_reset_postdata();
            }
            $out[] = [
                'key' => $def['key'],
                'label' => $def['label'],
                'category' => $def['category'],
                'archive' => $archive,
                'has_category' => (bool) $term,
                'limit' => $limit,
                'posts' => $posts,
            ];
        }
        return $out;
    }

    public static function resolve_category(string $name_or_slug): ?WP_Term
    {
        $name_or_slug = trim($name_or_slug);
        if ($name_or_slug === '') {
            return null;
        }
        $by_slug = get_term_by('slug', sanitize_title($name_or_slug), 'category');
        if ($by_slug instanceof WP_Term) {
            return $by_slug;
        }
        $by_name = get_term_by('name', $name_or_slug, 'category');
        return $by_name instanceof WP_Term ? $by_name : null;
    }
}
