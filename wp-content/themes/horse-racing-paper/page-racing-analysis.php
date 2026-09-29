<?php
/**
 * Template Name: 賽馬分析系統
 * Template for WP page slug racing-analysis — embeds legacy /00 hub.
 *
 * @package Horse_Racing_Paper
 */

$allowed_tabs = [
    'dashboard',
    'horses',
    'trainers',
    'jockeys',
    'jockey_trainer',
    'track',
    'jockey_barrier',
    'prediction',
    'odds',
    'upcoming',
];
$tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'dashboard';
if (!in_array($tab, $allowed_tabs, true)) {
    $tab = 'dashboard';
}

$labels = [
    'dashboard' => '分析儀表板',
    'horses' => '馬匹勝率榜',
    'trainers' => '練馬師勝率榜',
    'jockeys' => '騎師勝率榜',
    'jockey_trainer' => '騎練合作分析',
    'track' => '場地性能分析',
    'jockey_barrier' => '騎師檔位偏好',
    'prediction' => '勝率預測',
    'odds' => '賠率趨勢分析',
    'upcoming' => '即時賽事分析',
];

$src = home_url('/00/HKJC_race_analysis.php');
$src = add_query_arg('tab', $tab, $src);

get_header();
?>
<div class="hrp-layout flex-col md:flex-row">
    <?php get_template_part('template-parts/sidebar', 'nav'); ?>
    <main class="hrp-main">
        <article class="hrp-tool-page" data-hrp-tool="racing-analysis" data-tab="<?php echo esc_attr($tab); ?>">
            <h1 class="mb-1 font-display text-xl font-bold text-brass">賽馬分析 · <?php echo esc_html($labels[$tab]); ?></h1>
            <p class="mb-3 text-[12px] text-paper-dim">數據分析工具（舊系統即時數據）</p>
            <div class="hrp-tool-frame-wrap">
                <iframe
                    class="hrp-tool-frame"
                    title="<?php echo esc_attr($labels[$tab]); ?>"
                    src="<?php echo esc_url($src); ?>"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
            </div>
            <p class="mt-2 text-[11px] text-paper-dim">
                <a class="text-brass hover:underline" href="<?php echo esc_url($src); ?>" target="_blank" rel="noopener">在新分頁開啟完整工具</a>
            </p>
        </article>
    </main>
</div>
<?php
get_footer();
