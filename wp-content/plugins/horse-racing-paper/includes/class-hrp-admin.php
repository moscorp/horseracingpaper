<?php
/**
 * Dense WP Admin console.
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

final class HRP_Admin
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
    }

    public static function menu(): void
    {
        add_menu_page(
            '賽馬報紙',
            '賽馬報紙',
            'manage_options',
            'hrp-console',
            [self::class, 'render_dashboard'],
            'dashicons-chart-area',
            3
        );
        add_submenu_page('hrp-console', '設定', '設定', 'manage_options', 'hrp-settings', [self::class, 'render_settings']);
        add_submenu_page('hrp-console', 'Cron', 'Cron', 'manage_options', 'hrp-cron', [self::class, 'render_cron']);
    }

    public static function assets(string $hook): void
    {
        if (strpos($hook, 'hrp-') === false && strpos($hook, 'hrp-console') === false) {
            return;
        }
        wp_enqueue_style(
            'hrp-admin',
            HRP_PLUGIN_URL . 'admin/css/console.css',
            [],
            HRP_VERSION
        );
    }

    public static function render_dashboard(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $settings = HRP_Settings::get();
        $jobs = HRP_Cron_Registry::all();
        $enabled = count(array_filter($jobs, static fn($j) => !empty($j['enabled'])));

        $meeting = HRP_Race_Repository::latest_meeting();
        $race_count = 0;
        $runner_count = 0;
        if ($meeting) {
            $races = HRP_Race_Repository::races_for_meeting((string) $meeting->id);
            $race_count = count($races);
            foreach ($races as $race) {
                $runner_count += count(HRP_Race_Repository::runners_with_scores((string) $race->id));
            }
        }

        echo '<div class="wrap hrp-admin">';
        echo '<h1>賽馬報紙控制台</h1>';
        echo '<div class="hrp-admin-grid">';
        echo '<div class="hrp-admin-card"><strong>站名</strong><div>' . esc_html($settings['site_title_zh']) . '</div></div>';
        if ($meeting) {
            $venue = HRP_Race_Repository::venue_label((string) $meeting->venue_code);
            echo '<div class="hrp-admin-card"><strong>最近賽日</strong><div>' . esc_html($meeting->date . ' ' . $venue) . '</div></div>';
            echo '<div class="hrp-admin-card"><strong>場次 / 出馬</strong><div>' . (int) $race_count . ' / ' . (int) $runner_count . '</div></div>';
        } else {
            echo '<div class="hrp-admin-card"><strong>最近賽日</strong><div>無資料</div></div>';
        }
        echo '<div class="hrp-admin-card"><strong>Cron</strong><div>' . (int) $enabled . ' / ' . count($jobs) . ' 啟用</div></div>';
        echo '</div>';
        echo '<p><a class="button button-primary" href="' . esc_url(home_url('/')) . '" target="_blank" rel="noopener">查看前台</a> ';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=hrp-cron')) . '">Cron 清單</a> ';
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=hrp-settings')) . '">設定 / 門檻</a></p>';
        echo '</div>';
    }

    public static function render_settings(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $s = HRP_Settings::get();
        $key = HRP_Settings::OPTION_KEY;
        echo '<div class="wrap hrp-admin"><h1>設定</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('hrp_settings_group');
        echo '<table class="hrp-admin-table"><tbody>';
        echo '<tr><th>中文站名</th><td><input name="' . esc_attr($key) . '[site_title_zh]" value="' . esc_attr($s['site_title_zh']) . '" class="regular-text"></td></tr>';
        echo '<tr><th>Cron Secret</th><td><input name="' . esc_attr($key) . '[cron_secret]" value="' . esc_attr($s['cron_secret']) . '" class="large-text" autocomplete="off"><p class="description">之後 cron URL 必須帶此 token</p></td></tr>';
        echo '<tr><th>Funds Email</th><td><input name="' . esc_attr($key) . '[funds_email_to]" value="' . esc_attr($s['funds_email_to']) . '" class="regular-text"></td></tr>';
        echo '<tr><th colspan="2"><strong>優先分門檻（前台色碼 / 文案）</strong></th></tr>';
        echo '<tr><th>強烈推薦 ≥</th><td><input type="number" step="0.1" name="' . esc_attr($key) . '[blog][priority_hot]" value="' . esc_attr((string) $s['blog']['priority_hot']) . '"></td></tr>';
        echo '<tr><th>值得留意 ≥</th><td><input type="number" step="0.1" name="' . esc_attr($key) . '[blog][priority_watch]" value="' . esc_attr((string) $s['blog']['priority_watch']) . '"></td></tr>';
        echo '<tr><th>可作配腳 ≥</th><td><input type="number" step="0.1" name="' . esc_attr($key) . '[blog][priority_place]" value="' . esc_attr((string) $s['blog']['priority_place']) . '"></td></tr>';
        echo '<tr><th colspan="2"><strong>評分權重（預留）</strong></th></tr>';
        foreach ($s['score_weights'] as $k => $v) {
            echo '<tr><th>' . esc_html($k) . '</th><td><input type="number" step="0.1" name="' . esc_attr($key) . '[score_weights][' . esc_attr($k) . ']" value="' . esc_attr((string) $v) . '"></td></tr>';
        }
        echo '</tbody></table>';
        submit_button('儲存');
        echo '</form></div>';
    }

    public static function render_cron(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $jobs = HRP_Cron_Registry::all();
        echo '<div class="wrap hrp-admin"><h1>Cron 清單</h1>';
        echo '<p class="description">重型抓取維持 cron-job.org；cPanel 僅用心跳。路徑仍指向 legacy /00，遷移後改外掛 runner。</p>';
        echo '<table class="hrp-admin-table"><thead><tr><th>名稱</th><th>群組</th><th>Provider</th><th>路徑</th><th>啟用</th></tr></thead><tbody>';
        foreach ($jobs as $job) {
            echo '<tr>';
            echo '<td>' . esc_html($job['name']) . '</td>';
            echo '<td>' . esc_html($job['group']) . '</td>';
            echo '<td>' . esc_html($job['provider']) . '</td>';
            echo '<td><code>' . esc_html($job['path'] ?: '—') . '</code></td>';
            echo '<td>' . (!empty($job['enabled']) ? '是' : '否') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }
}
