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
                // Read posts without advancing the main loop / global $post.
                $posts = is_array($q->posts) ? $q->posts : [];
                unset($q);
            }
            $out[] = [
                'key' => $def['key'],
                'type' => 'posts',
                'label' => $def['label'],
                'category' => $resolved,
                'archive' => $archive,
                'has_category' => (bool) $term,
                'limit' => $limit,
                'posts' => $posts,
            ];

            // Insert interactive tools after 賽事分析 (before Mark Six groups).
            if ($def['key'] === 'racing_analysis') {
                $out[] = self::data_tools_group();
            }
        }
        return $out;
    }

    /**
     * Static tool links — legacy /00 analysis hub + Gann (not WP categories).
     *
     * @return array{key:string,type:string,label:string,archive:string,links:array<int,array{key:string,label:string,url:string,meta:string}>,posts:array,limit:int,category:string,has_category:bool}
     */
    public static function data_tools_group(): array
    {
        $hub = self::page_url('racing-analysis', home_url('/racing-analysis/'));
        $gann = self::page_url('mark-six-gann-chart', home_url('/mark-six-gann-chart/'));
        if ($gann === home_url('/mark-six-gann-chart/')) {
            $alt = self::page_url('mark-six-gann', '');
            if ($alt !== '') {
                $gann = $alt;
            }
        }

        $tabs = [
            ['key' => 'dashboard', 'label' => '分析儀表板', 'tab' => 'dashboard', 'meta' => '總覽'],
            ['key' => 'horses', 'label' => '馬匹勝率榜', 'tab' => 'horses', 'meta' => 'Horses'],
            ['key' => 'trainers', 'label' => '練馬師勝率榜', 'tab' => 'trainers', 'meta' => 'Trainers'],
            ['key' => 'jockeys', 'label' => '騎師勝率榜', 'tab' => 'jockeys', 'meta' => 'Jockeys'],
            ['key' => 'jockey_trainer', 'label' => '騎練合作分析', 'tab' => 'jockey_trainer', 'meta' => 'Jockey × Trainer'],
            ['key' => 'track', 'label' => '場地性能分析', 'tab' => 'track', 'meta' => 'Track'],
            ['key' => 'jockey_barrier', 'label' => '騎師檔位偏好', 'tab' => 'jockey_barrier', 'meta' => 'Barrier'],
            ['key' => 'prediction', 'label' => '勝率預測', 'tab' => 'prediction', 'meta' => 'Prediction'],
            ['key' => 'odds', 'label' => '賠率趨勢分析', 'tab' => 'odds', 'meta' => 'Odds'],
            ['key' => 'upcoming', 'label' => '即時賽事分析', 'tab' => 'upcoming', 'meta' => 'Upcoming'],
        ];

        $links = [];
        foreach ($tabs as $tab) {
            $url = add_query_arg('tab', $tab['tab'], $hub);
            if (function_exists('hrp_link_cache_bust')) {
                $url = hrp_link_cache_bust($url);
            }
            $links[] = [
                'key' => $tab['key'],
                'label' => $tab['label'],
                'url' => $url,
                'meta' => $tab['meta'],
            ];
        }

        $gann_url = $gann;
        if (function_exists('hrp_link_cache_bust')) {
            $gann_url = hrp_link_cache_bust($gann_url);
        }
        $links[] = [
            'key' => 'm6_gann',
            'label' => '六合彩江恩圖',
            'url' => $gann_url,
            'meta' => 'Gann Mark Six',
        ];

        $archive = $hub;
        if (function_exists('hrp_link_cache_bust')) {
            $archive = hrp_link_cache_bust($archive);
        }

        return [
            'key' => 'data_tools',
            'type' => 'links',
            'label' => '數據分析',
            'category' => '',
            'archive' => $archive,
            'has_category' => false,
            'limit' => count($links),
            'posts' => [],
            'links' => $links,
        ];
    }

    /**
     * Permalink for a published page slug, or $fallback.
     */
    public static function page_url(string $slug, string $fallback = ''): string
    {
        $page = get_page_by_path($slug);
        if ($page instanceof WP_Post && $page->post_status === 'publish') {
            $url = get_permalink($page);
            return $url ? (string) $url : $fallback;
        }
        return $fallback;
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
        $posts = is_array($q->posts) ? $q->posts : [];
        unset($q);
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
