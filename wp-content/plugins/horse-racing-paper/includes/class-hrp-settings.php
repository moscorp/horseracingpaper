<?php
/**
 * Editable constants / settings (no hard-coded magic numbers).
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

final class HRP_Settings
{
    public const OPTION_KEY = 'hrp_settings';

    public static function init(): void
    {
        add_action('admin_init', [self::class, 'register']);
    }

    public static function defaults(): array
    {
        return [
            'site_title_zh' => '自由馬紙',
            'site_title_en' => "Carl's Racing Paper",
            'logo_id' => 0,
            'favicon_id' => 0,
            'cron_secret' => wp_generate_password(32, false, false),
            'timezone' => 'Asia/Hong_Kong',
            'score_weights' => [
                'form' => 25,
                'barrier' => 15,
                'odds' => 20,
                'class' => 15,
                'distance' => 15,
                'jockey' => 10,
            ],
            'blog' => [
                // Live buycarl.com slugs (legacy cron publishers).
                'category_racing_result' => 'race-review',
                'category_racing_analysis' => 'racing-news',
                'category_m6_result' => 'mark-six-analysis',
                'category_m6_analysis' => 'mark-six-prediction',
                // Legacy aliases kept for older scripts
                'category_racing' => 'racing-news',
                'category_review' => 'race-review',
                'category_m6' => 'mark-six-prediction',
                'idempotent' => true,
                'priority_hot' => 70,
                'priority_watch' => 55,
                'priority_place' => 40,
                'nav_limit' => 6,
            ],
            'pages' => [
                'about' => "自由馬紙（Carl's Racing Paper）提供香港賽馬與六合彩的數據整理、優先分與賽後分析。\n\n內容僅供參考，不構成投注建議。",
                'privacy' => "我們尊重訪客私隱。本站可能使用必要 Cookie 以維持登入與偏好設定。\n\n除非法律要求，我們不會出售個人資料予第三方。",
                'tos' => "使用本站即表示你同意：內容僅供資訊與娛樂參考；投注有風險，請量力而為並遵守當地法例。\n\n本站可不經通知更新條款。",
            ],
            'funds_email_to' => '',
            'dense_ui' => true,
        ];
    }

    public static function seed_defaults(): void
    {
        if (get_option(self::OPTION_KEY) === false) {
            add_option(self::OPTION_KEY, self::defaults(), '', false);
        }
    }

    public static function get(): array
    {
        $stored = (array) get_option(self::OPTION_KEY, []);
        $defaults = self::defaults();
        $merged = wp_parse_args($stored, $defaults);
        $merged['score_weights'] = wp_parse_args(
            (array) ($stored['score_weights'] ?? []),
            $defaults['score_weights']
        );
        $merged['blog'] = wp_parse_args(
            (array) ($stored['blog'] ?? []),
            $defaults['blog']
        );
        $merged['pages'] = wp_parse_args(
            (array) ($stored['pages'] ?? []),
            $defaults['pages']
        );
        $merged['logo_id'] = (int) ($merged['logo_id'] ?? 0);
        $merged['favicon_id'] = (int) ($merged['favicon_id'] ?? 0);
        return $merged;
    }

    public static function get_value(string $key, $default = null)
    {
        $all = self::get();
        return $all[$key] ?? $default;
    }

    public static function register(): void
    {
        register_setting('hrp_settings_group', self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize'],
            'default' => self::defaults(),
        ]);
    }

    public static function sanitize($input): array
    {
        $current = self::get();
        $input = is_array($input) ? $input : [];
        $out = $current;

        foreach (['site_title_zh', 'site_title_en', 'cron_secret', 'timezone', 'funds_email_to'] as $key) {
            if (isset($input[$key])) {
                $out[$key] = sanitize_text_field($input[$key]);
            }
        }

        foreach (['logo_id', 'favicon_id'] as $key) {
            if (isset($input[$key])) {
                $out[$key] = max(0, (int) $input[$key]);
            }
        }

        if (isset($input['score_weights']) && is_array($input['score_weights'])) {
            foreach ($input['score_weights'] as $k => $v) {
                $out['score_weights'][sanitize_key($k)] = (float) $v;
            }
        }

        if (isset($input['blog']) && is_array($input['blog'])) {
            foreach ([
                'category_racing_result',
                'category_racing_analysis',
                'category_m6_result',
                'category_m6_analysis',
                'category_racing',
                'category_review',
                'category_m6',
            ] as $k) {
                if (isset($input['blog'][$k])) {
                    $out['blog'][$k] = sanitize_text_field($input['blog'][$k]);
                }
            }
            foreach (['priority_hot', 'priority_watch', 'priority_place'] as $k) {
                if (isset($input['blog'][$k])) {
                    $out['blog'][$k] = (float) $input['blog'][$k];
                }
            }
            if (isset($input['blog']['nav_limit'])) {
                $out['blog']['nav_limit'] = max(1, min(24, (int) $input['blog']['nav_limit']));
            }
            if (isset($input['blog']['idempotent'])) {
                $out['blog']['idempotent'] = !empty($input['blog']['idempotent']);
            }
        }

        if (isset($input['pages']) && is_array($input['pages'])) {
            foreach (['about', 'privacy', 'tos'] as $k) {
                if (isset($input['pages'][$k])) {
                    $out['pages'][$k] = wp_kses_post($input['pages'][$k]);
                }
            }
        }

        return $out;
    }

    public static function logo_url(): string
    {
        $id = (int) self::get_value('logo_id', 0);
        if ($id > 0) {
            $url = wp_get_attachment_image_url($id, 'full');
            if ($url) {
                return $url;
            }
        }
        return get_template_directory_uri() . '/assets/images/logo.png';
    }

    public static function favicon_url(): string
    {
        $id = (int) self::get_value('favicon_id', 0);
        if ($id > 0) {
            $url = wp_get_attachment_image_url($id, 'full');
            if ($url) {
                return $url;
            }
        }
        return get_template_directory_uri() . '/assets/images/favicon.png';
    }
}
