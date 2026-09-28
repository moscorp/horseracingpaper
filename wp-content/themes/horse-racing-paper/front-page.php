<?php
/**
 * Front page — dense race-day paper + sidebar nav.
 *
 * @package Horse_Racing_Paper
 */

get_header();
?>
<div class="hrp-layout flex-col md:flex-row">
    <?php get_template_part('template-parts/sidebar', 'nav'); ?>
    <main class="hrp-main">
        <h1 class="mb-2 mt-1 font-display text-base font-semibold text-paper md:text-lg">
            <?php echo esc_html(hrp_brand_title()); ?> · 今日賽事
        </h1>
        <?php if (shortcode_exists('hrp_race_day')) : ?>
            <?php echo do_shortcode('[hrp_race_day]'); ?>
        <?php else : ?>
            <p class="text-paper-mute">請在後台啟用 <strong class="text-brass">自由馬紙</strong> 外掛。</p>
        <?php endif; ?>
    </main>
</div>
<?php
get_footer();
