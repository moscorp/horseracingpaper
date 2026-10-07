<?php
/**
 * Static pages — About / Privacy / ToS and others.
 *
 * @package Horse_Racing_Paper
 */

$hrp_page_id = (int) get_queried_object_id();
$hrp_page = $hrp_page_id > 0 ? get_post($hrp_page_id) : null;
if (!$hrp_page instanceof WP_Post) {
    $obj = get_queried_object();
    $hrp_page = $obj instanceof WP_Post ? $obj : null;
}

get_header();
?>
<div class="hrp-layout flex-col md:flex-row">
    <?php get_template_part('template-parts/sidebar', 'nav'); ?>
    <main class="hrp-main">
        <?php if ($hrp_page instanceof WP_Post) :
            $GLOBALS['post'] = $hrp_page;
            setup_postdata($hrp_page);
            ?>
            <article <?php post_class('', $hrp_page); ?> data-hrp-post="<?php echo (int) $hrp_page->ID; ?>">
                <h1 class="mb-3 font-display text-xl font-bold text-brass"><?php echo esc_html(get_the_title($hrp_page)); ?></h1>
                <div class="hrp-content max-w-3xl text-[15px] leading-relaxed text-paper">
                    <?php echo apply_filters('the_content', $hrp_page->post_content); ?>
                </div>
            </article>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <p class="text-paper-dim">找不到頁面。</p>
        <?php endif; ?>
    </main>
</div>
<?php
get_footer();
