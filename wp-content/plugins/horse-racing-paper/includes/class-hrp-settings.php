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
            'site_title_zh' => '賽馬報紙',
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
                'category_racing' => '賽事分析',
                'category_review' => '賽後回顧',
                'category_m6' => '六合彩',
                'idempotent' => true,
                // From legacy HKJC_blogpost.php display thresholds
                'priority_hot' => 70,
                'priority_watch' => 55,
                'priority_place' => 40,
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

        foreach (['site_title_zh', 'cron_secret', 'timezone', 'funds_email_to'] as $key) {
            if (isset($input[$key])) {
                $out[$key] = sanitize_text_field($input[$key]);
            }
        }
        if (isset($input['score_weights']) && is_array($input['score_weights'])) {
            foreach ($input['score_weights'] as $k => $v) {
                $out['score_weights'][sanitize_key($k)] = (float) $v;
            }
        }
        if (isset($input['blog']) && is_array($input['blog'])) {
            foreach (['category_racing', 'category_review', 'category_m6'] as $k) {
                if (isset($input['blog'][$k])) {
                    $out['blog'][$k] = sanitize_text_field($input['blog'][$k]);
                }
            }
            foreach (['priority_hot', 'priority_watch', 'priority_place'] as $k) {
                if (isset($input['blog'][$k])) {
                    $out['blog'][$k] = (float) $input['blog'][$k];
                }
            }
            if (isset($input['blog']['idempotent'])) {
                $out['blog']['idempotent'] = !empty($input['blog']['idempotent']);
            }
        }
        return $out;
    }
}
