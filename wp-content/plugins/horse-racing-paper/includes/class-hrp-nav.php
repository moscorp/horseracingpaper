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
     * Live buycarl.com category aliases (slug / name).
     *
     * @return array<string, list<string>>
     */
    public static function category_aliases(): array
    {
        return [
            'racing_result' => ['race-review', 'Race Review', '賽事結果', '賽後回顧'],
            'racing_analysis' => ['racing-news', 'Racing News', '賽事分析'],
            'm6_result' => ['mark-six-analysis', 'Mark Six Analysis', '六合彩結果', '六合彩賽後'],
            'm6_analysis' => ['mark-six-prediction', 'Mark Six Prediction', '六合彩分析', '六合彩預測'],
        ];
    }

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
        $fetch = $limit * 2;
        $aliases = self::category_aliases();

        $defs = [
            [
                'key' => 'racing_result',
                'label' => '賽事結果',
                'category' => (string) ($blog['category_racing_result'] ?? 'race-review'),
            ],
            [
                'key' => 'racing_analysis',
                'label' => '賽事分析',
                'category' => (string) ($blog['category_racing_analysis'] ?? $blog['category_racing'] ?? 'racing-news'),
            ],
            [
                'key' => 'm6_result',
                'label' => '六合彩結果',
                'category' => (string) ($blog['category_m6_result'] ?? 'mark-six-analysis'),
            ],
            [
                'key' => 'm6_analysis',
                'label' => '六合彩分析',
                'category' => (string) ($blog['category_m6_analysis'] ?? $blog['category_m6'] ?? 'mark-six-prediction'),
            ],
        ];

        $out = [];
        foreach ($defs as $def) {
            $candidates = array_values(array_unique(array_filter(array_merge(
                [$def['category']],
                $aliases[$def['key']] ?? []
            ))));
            $term = self::resolve_category_any($candidates);
            $posts = [];
            $archive = '';
            $resolved = $def['category'];
            if ($term) {
                $resolved = $term->slug ?: $term->name;
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
                'category' => $resolved,
                'archive' => $archive,
                'has_category' => (bool) $term,
                'limit' => $limit,
                'posts' => $posts,
            ];
        }
        return $out;
    }

    /**
     * Latest published posts across racing / Mark Six categories (for homepage).
     *
     * @return array<int, WP_Post>
     */
    public static function latest_posts(int $limit = 12): array
    {
        $ids = [];
        foreach (self::groups(1) as $group) {
            if (!empty($group['has_category']) && !empty($group['archive'])) {
                $term = self::resolve_category((string) $group['category']);
                if ($term) {
                    $ids[] = (int) $term->term_id;
                }
            }
        }
        // Also resolve known live slugs directly.
        foreach (['race-review', 'racing-news', 'mark-six-analysis', 'mark-six-prediction'] as $slug) {
            $term = get_term_by('slug', $slug, 'category');
            if ($term instanceof WP_Term) {
                $ids[] = (int) $term->term_id;
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));

        $args = [
            'posts_per_page' => max(1, $limit),
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ];
        if ($ids) {
            $args['category__in'] = $ids;
        }

        $q = new WP_Query($args);
        $posts = $q->posts;
        wp_reset_postdata();
        return $posts;
    }

    /**
     * @param list<string> $names
     */
    public static function resolve_category_any(array $names): ?WP_Term
    {
        foreach ($names as $name) {
            $term = self::resolve_category((string) $name);
            if ($term) {
                return $term;
            }
        }
        return null;
    }

    public static function resolve_category(string $name_or_slug): ?WP_Term
    {
        $name_or_slug = trim($name_or_slug);
        if ($name_or_slug === '') {
            return null;
        }
        $by_slug = get_term_by('slug', $name_or_slug, 'category');
        if ($by_slug instanceof WP_Term) {
            return $by_slug;
        }
        $sanitized = sanitize_title($name_or_slug);
        if ($sanitized !== '' && $sanitized !== $name_or_slug) {
            $by_sanitized = get_term_by('slug', $sanitized, 'category');
            if ($by_sanitized instanceof WP_Term) {
                return $by_sanitized;
            }
        }
        $by_name = get_term_by('name', $name_or_slug, 'category');
        return $by_name instanceof WP_Term ? $by_name : null;
    }
}
