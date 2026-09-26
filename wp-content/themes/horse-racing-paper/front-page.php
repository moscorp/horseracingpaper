<?php
/**
 * Front page — dense race-day paper + latest analysis posts.
 *
 * @package Horse_Racing_Paper
 */

get_header();

$settings = class_exists('HRP_Settings') ? HRP_Settings::get() : ['site_title_zh' => '賽馬報紙'];
?>
<main class="hrp-shell">
    <h1 class="hrp-section-title"><?php echo esc_html($settings['site_title_zh'] ?? '賽馬報紙'); ?> · 今日賽事</h1>
    <?php if (shortcode_exists('hrp_race_day')) : ?>
        <?php echo do_shortcode('[hrp_race_day]'); ?>
    <?php else : ?>
        <p class="hrp-tagline">請在後台啟用 <strong>Horse Racing Paper</strong> 外掛。</p>
    <?php endif; ?>

    <h2 class="hrp-section-title">最新分析</h2>
    <?php
    $q = new WP_Query([
        'posts_per_page' => 8,
        'post_status' => 'publish',
        'ignore_sticky_posts' => true,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    if ($q->have_posts()) :
        echo '<ul style="list-style:none;padding:0;margin:0;">';
        while ($q->have_posts()) :
            $q->the_post();
            ?>
            <li style="padding:0.3rem 0;border-bottom:1px solid #2a3440;">
                <a href="<?php the_permalink(); ?>" style="color:inherit;text-decoration:none;">
                    <?php the_title(); ?>
                    <span class="hrp-tagline" style="display:inline;margin-left:0.5rem;"><?php echo esc_html(get_the_date('Y-m-d H:i')); ?></span>
                </a>
            </li>
            <?php
        endwhile;
        echo '</ul>';
        wp_reset_postdata();
    else :
        echo '<p class="hrp-tagline">尚無文章。</p>';
    endif;
    ?>
</main>
<?php
get_footer();
