<?php
/**
 * Editable constants / settings (no hard-coded magic numbers in scrapers).
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
            ],
            'funds_email_to' => '',
            'dense_ui' => true,
        ];
    }

    public static function seed_defaults(): void
    {
        if (get_option(self::OPTION_KEY) === false) {
            $defaults = self::defaults();
            // Keep generated secret only on first seed.
            add_option(self::OPTION_KEY, $defaults, '', false);
        }
    }

    public static function get(): array
    {
        return wp_parse_args((array) get_option(self::OPTION_KEY, []), self::defaults());
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

        if (isset($input['site_title_zh'])) {
            $out['site_title_zh'] = sanitize_text_field($input['site_title_zh']);
        }
        if (isset($input['cron_secret'])) {
            $out['cron_secret'] = sanitize_text_field($input['cron_secret']);
        }
        if (isset($input['timezone'])) {
            $out['timezone'] = sanitize_text_field($input['timezone']);
        }
        if (isset($input['funds_email_to'])) {
            $out['funds_email_to'] = sanitize_text_field($input['funds_email_to']);
        }
        if (isset($input['score_weights']) && is_array($input['score_weights'])) {
            foreach ($input['score_weights'] as $k => $v) {
                $out['score_weights'][sanitize_key($k)] = (float) $v;
            }
        }
        return $out;
    }
}
