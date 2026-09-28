<?php
/**
 * Dense WP Admin console (Tailwind).
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
            '自由馬紙',
            '自由馬紙',
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
        wp_enqueue_media();
        wp_enqueue_style(
            'hrp-admin',
            HRP_PLUGIN_URL . 'admin/css/hrp-admin.css',
            [],
            HRP_VERSION
        );
        wp_enqueue_script(
            'hrp-admin',
            HRP_PLUGIN_URL . 'admin/js/hrp-admin.js',
            ['jquery'],
            HRP_VERSION,
            true
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
        echo '<h1>自由馬紙控制台</h1>';
        echo '<p class="description">' . esc_html($settings['site_title_en'] ?? "Carl's Racing Paper") . '</p>';
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
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=hrp-settings')) . '">設定 / 品牌 / 頁面</a></p>';
        echo '</div>';
    }

    public static function render_settings(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $s = HRP_Settings::get();
        $key = HRP_Settings::OPTION_KEY;
        $logo_url = HRP_Settings::logo_url();
        $favicon_url = HRP_Settings::favicon_url();

        echo '<div class="wrap hrp-admin"><h1>設定</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('hrp_settings_group');

        echo '<div class="hrp-section-head">品牌</div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">中文站名</div><div><input name="' . esc_attr($key) . '[site_title_zh]" value="' . esc_attr($s['site_title_zh']) . '" class="regular-text"></div></div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">英文站名</div><div><input name="' . esc_attr($key) . '[site_title_en]" value="' . esc_attr($s['site_title_en']) . '" class="regular-text"></div></div>';

        echo '<div class="hrp-field-row"><div class="hrp-field-label">站徽 Logo</div><div>';
        echo '<input type="hidden" class="hrp-media-id" name="' . esc_attr($key) . '[logo_id]" value="' . esc_attr((string) $s['logo_id']) . '">';
        echo '<button type="button" class="button hrp-media-pick" data-title="選擇站徽">上傳 / 選擇</button> ';
        echo '<button type="button" class="button hrp-media-clear">清除</button>';
        echo '<div class="hrp-preview"><img class="hrp-media-preview" src="' . esc_url($logo_url) . '" alt="logo"></div>';
        echo '<p class="description">未上傳時使用主題內建預設站徽。建議正方形 PNG。</p>';
        echo '</div></div>';

        echo '<div class="hrp-field-row"><div class="hrp-field-label">Favicon</div><div>';
        echo '<input type="hidden" class="hrp-media-id" name="' . esc_attr($key) . '[favicon_id]" value="' . esc_attr((string) $s['favicon_id']) . '">';
        echo '<button type="button" class="button hrp-media-pick" data-title="選擇 Favicon">上傳 / 選擇</button> ';
        echo '<button type="button" class="button hrp-media-clear">清除</button>';
        echo '<div class="hrp-preview"><img class="hrp-media-preview" src="' . esc_url($favicon_url) . '" alt="favicon" style="max-height:48px"></div>';
        echo '<p class="description">未上傳時使用主題內建預設圖示。</p>';
        echo '</div></div>';

        echo '<div class="hrp-section-head">側欄分類（階層選單）</div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">賽事結果</div><div><input name="' . esc_attr($key) . '[blog][category_racing_result]" value="' . esc_attr($s['blog']['category_racing_result']) . '" class="regular-text"><p class="description">分類名稱或 slug</p></div></div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">賽事分析</div><div><input name="' . esc_attr($key) . '[blog][category_racing_analysis]" value="' . esc_attr($s['blog']['category_racing_analysis']) . '" class="regular-text"></div></div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">六合彩結果</div><div><input name="' . esc_attr($key) . '[blog][category_m6_result]" value="' . esc_attr($s['blog']['category_m6_result']) . '" class="regular-text"></div></div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">六合彩分析</div><div><input name="' . esc_attr($key) . '[blog][category_m6_analysis]" value="' . esc_attr($s['blog']['category_m6_analysis']) . '" class="regular-text"></div></div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">每組顯示</div><div><input type="number" min="1" max="24" name="' . esc_attr($key) . '[blog][nav_limit]" value="' . esc_attr((string) $s['blog']['nav_limit']) . '"> <span class="description">預設 6；「更多」可展開下一組</span></div></div>';

        echo '<div class="hrp-section-head">頁腳內容（關於 / 私隱 / 條款）</div>';
        echo '<p class="description">儲存後會同步到頁面 slug：about、privacy、terms。</p>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">關於我們</div><div><textarea class="hrp-legal" name="' . esc_attr($key) . '[pages][about]">' . esc_textarea($s['pages']['about']) . '</textarea></div></div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">私隱政策</div><div><textarea class="hrp-legal" name="' . esc_attr($key) . '[pages][privacy]">' . esc_textarea($s['pages']['privacy']) . '</textarea></div></div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">使用條款</div><div><textarea class="hrp-legal" name="' . esc_attr($key) . '[pages][tos]">' . esc_textarea($s['pages']['tos']) . '</textarea></div></div>';

        echo '<div class="hrp-section-head">系統</div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">Cron Secret</div><div><input name="' . esc_attr($key) . '[cron_secret]" value="' . esc_attr($s['cron_secret']) . '" class="large-text" autocomplete="off"><p class="description">之後 cron URL 必須帶此 token</p></div></div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">Funds Email</div><div><input name="' . esc_attr($key) . '[funds_email_to]" value="' . esc_attr($s['funds_email_to']) . '" class="regular-text"></div></div>';

        echo '<div class="hrp-section-head">優先分門檻（前台色碼）</div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">強烈推薦 ≥</div><div><input type="number" step="0.1" name="' . esc_attr($key) . '[blog][priority_hot]" value="' . esc_attr((string) $s['blog']['priority_hot']) . '"></div></div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">值得留意 ≥</div><div><input type="number" step="0.1" name="' . esc_attr($key) . '[blog][priority_watch]" value="' . esc_attr((string) $s['blog']['priority_watch']) . '"></div></div>';
        echo '<div class="hrp-field-row"><div class="hrp-field-label">可作配腳 ≥</div><div><input type="number" step="0.1" name="' . esc_attr($key) . '[blog][priority_place]" value="' . esc_attr((string) $s['blog']['priority_place']) . '"></div></div>';

        echo '<div class="hrp-section-head">評分權重（預留）</div>';
        echo '<table class="hrp-admin-table"><tbody>';
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
