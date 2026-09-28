<?php
/**
 * Front page — latest posts + dense race-day paper + sidebar nav.
 *
 * @package Horse_Racing_Paper
 */

get_header();

$latest = class_exists('HRP_Nav') ? HRP_Nav::latest_posts(12) : [];
$featured = $latest[0] ?? null;
?>
<div class="hrp-layout flex-col md:flex-row">
    <?php get_template_part('template-parts/sidebar', 'nav'); ?>
    <main class="hrp-main">
        <?php if ($featured instanceof WP_Post) : ?>
            <section class="mb-4 border-b border-ink-line pb-3">
                <p class="mb-1 text-[11px] font-semibold tracking-wide text-paper-mute">最新文章</p>
                <h1 class="m-0 font-display text-xl font-bold text-brass md:text-2xl">
                    <a class="hover:underline" href="<?php echo esc_url(get_permalink($featured)); ?>">
                        <?php echo esc_html(get_the_title($featured)); ?>
                    </a>
                </h1>
                <p class="mt-1 text-[12px] text-paper-mute">
                    <?php echo esc_html(get_the_date('Y-m-d H:i', $featured)); ?>
                </p>
                <div class="mt-2 text-[14px] leading-relaxed text-paper-soft">
                    <?php echo esc_html(wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $featured)), 60, '…')); ?>
                </div>
                <p class="mt-2">
                    <a class="text-sm text-brass hover:underline" href="<?php echo esc_url(get_permalink($featured)); ?>">閱讀全文 →</a>
                </p>
            </section>
        <?php endif; ?>

        <?php if (count($latest) > 1) : ?>
            <section class="mb-4">
                <h2 class="mb-2 mt-0 font-display text-base font-semibold text-paper">近期更新</h2>
                <ul class="m-0 list-none p-0">
                    <?php foreach (array_slice($latest, 1) as $post) : ?>
                        <li class="border-b border-ink-line py-1.5">
                            <a class="text-[13px] text-paper hover:text-brass" href="<?php echo esc_url(get_permalink($post)); ?>">
                                <?php echo esc_html(get_the_title($post)); ?>
                                <span class="ml-2 text-[11px] text-paper-mute"><?php echo esc_html(get_the_date('Y-m-d', $post)); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php elseif (!$featured) : ?>
            <p class="mb-3 text-paper-mute">尚無已發佈文章。</p>
        <?php endif; ?>

        <section>
            <h2 class="mb-2 mt-1 font-display text-base font-semibold text-paper">
                <?php echo esc_html(hrp_brand_title()); ?> · 今日賽事
            </h2>
            <?php if (shortcode_exists('hrp_race_day')) : ?>
                <?php echo do_shortcode('[hrp_race_day]'); ?>
            <?php else : ?>
                <p class="text-paper-mute">請在後台啟用 <strong class="text-brass">自由馬紙</strong> 外掛。</p>
            <?php endif; ?>
        </section>
    </main>
</div>
<?php
get_footer();
