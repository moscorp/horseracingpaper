<?php
/**
 * Static pages — About / Privacy / ToS and others.
 *
 * @package Horse_Racing_Paper
 */

get_header();
?>
<div class="hrp-layout flex-col md:flex-row">
    <?php get_template_part('template-parts/sidebar', 'nav'); ?>
    <main class="hrp-main">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article <?php post_class(); ?>>
                <h1 class="mb-3 font-display text-xl font-bold text-brass"><?php the_title(); ?></h1>
                <div class="hrp-content max-w-3xl text-[14px] leading-relaxed text-paper-soft">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; else : ?>
            <p class="text-paper-mute">找不到頁面。</p>
        <?php endif; ?>
    </main>
</div>
<?php
get_footer();
