<?php
/**
 * Single post — Chinese race analysis / review.
 * Note: AliDropship (alids) may still override via its own template loader;
 * getPostViews() compat in the plugin keeps that path from fataling.
 *
 * @package Horse_Racing_Paper
 */

get_header();
?>
<main class="hrp-shell">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article <?php post_class('hrp-single'); ?>>
            <h1 class="hrp-section-title" style="font-size:1.25rem;margin-bottom:0.35rem;"><?php the_title(); ?></h1>
            <p class="hrp-tagline" style="margin-bottom:0.75rem;">
                <?php echo esc_html(get_the_date('Y-m-d H:i')); ?>
                <?php if (function_exists('getPostViews')) : ?>
                    · <?php echo esc_html(getPostViews(get_the_ID())); ?> 次瀏覽
                <?php endif; ?>
            </p>
            <div class="hrp-content" style="line-height:1.5;font-size:14px;">
                <?php
                if (function_exists('setPostViews')) {
                    setPostViews(get_the_ID());
                }
                the_content();
                ?>
            </div>
        </article>
    <?php endwhile; else : ?>
        <p class="hrp-tagline">找不到文章。</p>
    <?php endif; ?>
    <p style="margin-top:1rem;"><a href="<?php echo esc_url(home_url('/')); ?>" style="color:#c4a35a;">← 返回賽馬報紙</a></p>
</main>
<?php
get_footer();
