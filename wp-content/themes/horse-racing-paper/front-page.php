<?php
/**
 * Front page — dense race-day paper shell.
 * Data comes from the horse-racing-paper plugin shortcode/API once wired.
 *
 * @package Horse_Racing_Paper
 */

get_header();
?>
<main class="hrp-shell">
    <h1 class="hrp-section-title">今日賽事</h1>
    <?php if (shortcode_exists('hrp_race_day')) : ?>
        <?php echo do_shortcode('[hrp_race_day]'); ?>
    <?php else : ?>
        <p class="hrp-tagline">請啟用 <strong>Horse Racing Paper</strong> 外掛以載入賽事資料。</p>
        <div class="hrp-dense" aria-hidden="true">
            <div class="hrp-race-tabs">
                <button type="button" aria-selected="true">R1</button>
                <button type="button">R2</button>
                <button type="button">R3</button>
            </div>
            <table>
                <thead>
                <tr>
                    <th>#</th><th>馬名</th><th>騎師</th><th>檔</th><th>賠率</th><th>評分</th><th>訊號</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="7">等待資料…</td></tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2 class="hrp-section-title">最新分析</h2>
    <?php if (have_posts()) : ?>
        <ul style="list-style:none;padding:0;margin:0;">
            <?php while (have_posts()) : the_post(); ?>
                <li style="padding:0.35rem 0;border-bottom:1px solid #2a3440;">
                    <a href="<?php the_permalink(); ?>" style="color:inherit;text-decoration:none;">
                        <?php the_title(); ?>
                        <span class="hrp-tagline" style="display:inline;margin-left:0.5rem;"><?php echo esc_html(get_the_date()); ?></span>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else : ?>
        <p class="hrp-tagline">尚無文章。</p>
    <?php endif; ?>
</main>
<?php
get_footer();
