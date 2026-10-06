<?php
/**
 * Template Name: 六合彩江恩圖
 * Template for WP page mark-six-gann-chart — latest Gann square image.
 *
 * @package Horse_Racing_Paper
 */

$draw_date = '';
$img_url = '';
$grid_url = home_url('/00/marksixgrid.php');

if (class_exists('HRP_DB')) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- custom legacy table.
    $row = HRP_DB::get_row(
        'SELECT laterest_drawdate, jpgfilename FROM hkms_gann ORDER BY id DESC LIMIT 1'
    );
    if ($row && !empty($row->jpgfilename)) {
        $draw_date = (string) ($row->laterest_drawdate ?? '');
        $path = ltrim((string) $row->jpgfilename, '/');
        $img_url = home_url('/00/' . $path);
    }
}

// Fallback: newest jpg written by cron_6Gann under /00/image/marksix/.
if ($img_url === '') {
    $doc = isset($_SERVER['DOCUMENT_ROOT']) ? (string) $_SERVER['DOCUMENT_ROOT'] : '';
    $dir = $doc !== '' ? rtrim($doc, '/') . '/00/image/marksix' : '';
    if ($dir !== '' && is_dir($dir)) {
        $files = glob($dir . '/*.jpg') ?: [];
        if ($files) {
            usort($files, static function ($a, $b) {
                return filemtime($b) <=> filemtime($a);
            });
            $newest = $files[0];
            $img_url = home_url('/00/image/marksix/' . basename($newest));
            $draw_date = gmdate('Y-m-d', (int) filemtime($newest));
        }
    }
}

get_header();
?>
<div class="hrp-layout hrp-layout--wide flex-col md:flex-row">
    <?php get_template_part('template-parts/sidebar', 'nav'); ?>
    <main class="hrp-main hrp-main--wide">
        <article class="hrp-tool-page" data-hrp-tool="gann">
            <h1 class="mb-1 font-display text-xl font-bold text-brass">六合彩江恩圖</h1>
            <p class="mb-3 text-[12px] text-paper-dim">
                專業走勢分析 · 每日自動更新
                <?php if ($draw_date !== '') : ?>
                    · 期數／日期：<?php echo esc_html($draw_date); ?>
                <?php endif; ?>
            </p>

            <?php if ($img_url !== '') : ?>
                <div class="hrp-gann-figure">
                    <img
                        class="hrp-gann-img"
                        src="<?php echo esc_url($img_url); ?>"
                        alt="六合彩江恩圖 <?php echo esc_attr($draw_date); ?>"
                        loading="lazy"
                    >
                </div>
            <?php else : ?>
                <p class="mb-3 text-[13px] text-paper-dim">暫未找到已產生的江恩圖圖片，以下為即時格網預覽。</p>
            <?php endif; ?>

            <div class="hrp-tool-frame-wrap hrp-tool-frame-wrap--short mt-3">
                <iframe
                    class="hrp-tool-frame"
                    title="六合彩江恩格網"
                    src="<?php echo esc_url($grid_url); ?>"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
            </div>
            <p class="mt-2 text-[11px] text-paper-dim">
                <a class="text-brass hover:underline" href="<?php echo esc_url($grid_url); ?>" target="_blank" rel="noopener">開啟互動格網</a>
            </p>
        </article>
    </main>
</div>
<?php
get_footer();
