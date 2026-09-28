<?php
/**
 * Single post — bind the URL post BEFORE header/sidebar run.
 *
 * @package Horse_Racing_Paper
 */

$hrp_post_id = (int) get_queried_object_id();
$hrp_post = $hrp_post_id > 0 ? get_post($hrp_post_id) : null;
if (!$hrp_post instanceof WP_Post || $hrp_post->post_status !== 'publish') {
    // Fallback for private/preview when logged in.
    $obj = get_queried_object();
    $hrp_post = $obj instanceof WP_Post ? $obj : null;
}

get_header();
?>
<div class="hrp-layout flex-col md:flex-row">
    <?php get_template_part('template-parts/sidebar', 'nav'); ?>
    <main class="hrp-main">
        <?php if ($hrp_post instanceof WP_Post) :
            $GLOBALS['post'] = $hrp_post;
            setup_postdata($hrp_post);
            ?>
            <article <?php post_class('hrp-single', $hrp_post); ?> data-hrp-post="<?php echo (int) $hrp_post->ID; ?>">
                <h1 class="mb-1 font-display text-xl font-bold text-paper"><?php echo esc_html(get_the_title($hrp_post)); ?></h1>
                <p class="mb-3 text-[12px] text-paper-dim">
                    <?php echo esc_html(get_the_date('Y-m-d H:i', $hrp_post)); ?>
                    <?php if (function_exists('getPostViews')) : ?>
                        · <?php echo esc_html(getPostViews($hrp_post->ID)); ?> 次瀏覽
                    <?php endif; ?>
                </p>
                <div class="hrp-content text-[15px] leading-relaxed text-paper">
                    <?php
                    if (function_exists('setPostViews')) {
                        setPostViews($hrp_post->ID);
                    }
                    // Use the bound post object — never rely on a polluted Loop.
                    echo apply_filters('the_content', $hrp_post->post_content);
                    ?>
                </div>
            </article>
            <?php
            wp_reset_postdata();
        else : ?>
            <p class="text-paper-dim">找不到文章。</p>
        <?php endif; ?>
        <p class="mt-4">
            <a class="text-brass hover:underline" href="<?php echo esc_url(home_url('/')); ?>">← 返回<?php echo esc_html(hrp_brand_title()); ?></a>
        </p>
    </main>
</div>
<?php
get_footer();
