<?php
/**
 * Fallback index.
 *
 * @package Horse_Racing_Paper
 */

get_header();
?>
<main class="hrp-shell">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article style="margin-bottom:1rem;">
            <h1 class="hrp-section-title"><a href="<?php the_permalink(); ?>" style="color:inherit;"><?php the_title(); ?></a></h1>
            <div><?php the_excerpt(); ?></div>
        </article>
    <?php endwhile; else : ?>
        <p class="hrp-tagline">沒有內容。</p>
    <?php endif; ?>
</main>
<?php
get_footer();
