<?php
/**
 * Category archive — used by sidebar「更多」.
 *
 * @package Horse_Racing_Paper
 */

get_header();
$term = get_queried_object();
?>
<div class="hrp-layout flex-col md:flex-row">
    <?php get_template_part('template-parts/sidebar', 'nav'); ?>
    <main class="hrp-main">
        <h1 class="mb-3 font-display text-lg font-bold text-brass">
            <?php echo esc_html($term instanceof WP_Term ? $term->name : '分類'); ?>
        </h1>
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article class="mb-2 border-b border-ink-line py-2">
                <a class="text-sm text-paper hover:text-brass" href="<?php the_permalink(); ?>">
                    <?php the_title(); ?>
                    <span class="ml-2 text-[11px] text-paper-mute"><?php echo esc_html(get_the_date('Y-m-d H:i')); ?></span>
                </a>
            </article>
        <?php endwhile; ?>
            <div class="mt-3 text-sm text-paper-mute"><?php the_posts_pagination(); ?></div>
        <?php else : ?>
            <p class="text-paper-mute">此分類尚無文章。</p>
        <?php endif; ?>
    </main>
</div>
<?php
get_footer();
