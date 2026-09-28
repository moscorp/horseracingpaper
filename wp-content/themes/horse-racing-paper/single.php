<?php
/**
 * Single post — Chinese race analysis / review.
 *
 * @package Horse_Racing_Paper
 */

get_header();
?>
<div class="hrp-layout flex-col md:flex-row">
    <?php get_template_part('template-parts/sidebar', 'nav'); ?>
    <main class="hrp-main">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article <?php post_class('hrp-single'); ?>>
                <h1 class="mb-1 font-display text-xl font-bold text-paper"><?php the_title(); ?></h1>
                <p class="mb-3 text-[12px] text-paper-mute">
                    <?php echo esc_html(get_the_date('Y-m-d H:i')); ?>
                    <?php if (function_exists('getPostViews')) : ?>
                        · <?php echo esc_html(getPostViews(get_the_ID())); ?> 次瀏覽
                    <?php endif; ?>
                </p>
                <div class="hrp-content text-[14px] leading-relaxed text-paper-soft">
                    <?php
                    if (function_exists('setPostViews')) {
                        setPostViews(get_the_ID());
                    }
                    the_content();
                    ?>
                </div>
            </article>
        <?php endwhile; else : ?>
            <p class="text-paper-mute">找不到文章。</p>
        <?php endif; ?>
        <p class="mt-4">
            <a class="text-brass hover:underline" href="<?php echo esc_url(home_url('/')); ?>">← 返回<?php echo esc_html(hrp_brand_title()); ?></a>
        </p>
    </main>
</div>
<?php
get_footer();
