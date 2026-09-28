<?php
/**
 * Fallback index.
 *
 * @package Horse_Racing_Paper
 */

get_header();
?>
<div class="hrp-layout flex-col md:flex-row">
    <?php get_template_part('template-parts/sidebar', 'nav'); ?>
    <main class="hrp-main">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article class="mb-3 border-b border-ink-line pb-3">
                <h1 class="m-0 text-sm font-semibold">
                    <a class="hover:text-brass" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h1>
                <p class="mt-1 text-[11px] text-paper-mute"><?php echo esc_html(get_the_date('Y-m-d H:i')); ?></p>
                <div class="mt-1 text-[13px] text-paper-soft"><?php the_excerpt(); ?></div>
            </article>
        <?php endwhile; else : ?>
            <p class="text-paper-mute">沒有內容。</p>
        <?php endif; ?>
    </main>
</div>
<?php
get_footer();
