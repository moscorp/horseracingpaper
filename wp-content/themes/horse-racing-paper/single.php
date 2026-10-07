<?php
/**
 * Single post — brand chrome + normalized legacy cron HTML.
 *
 * @package Horse_Racing_Paper
 */

$hrp_post_id = (int) get_queried_object_id();
$hrp_post = $hrp_post_id > 0 ? get_post($hrp_post_id) : null;
if (!$hrp_post instanceof WP_Post || $hrp_post->post_status !== 'publish') {
    $obj = get_queried_object();
    $hrp_post = $obj instanceof WP_Post ? $obj : null;
}

get_header();

$group_label = $hrp_post instanceof WP_Post ? hrp_post_group_label($hrp_post) : '';
$group_archive = $hrp_post instanceof WP_Post ? hrp_post_group_archive($hrp_post) : '';
$adjacent = $hrp_post instanceof WP_Post ? hrp_adjacent_in_category($hrp_post) : ['prev' => null, 'next' => null];
$wide = $hrp_post instanceof WP_Post; // race/M6 tables need the full column
?>
<div class="hrp-layout<?php echo $wide ? ' hrp-layout--wide' : ''; ?> flex-col md:flex-row">
    <?php get_template_part('template-parts/sidebar', 'nav'); ?>
    <main class="hrp-main<?php echo $wide ? ' hrp-main--wide' : ''; ?>">
        <?php if ($hrp_post instanceof WP_Post) :
            $GLOBALS['post'] = $hrp_post;
            setup_postdata($hrp_post);
            $display_title = hrp_display_title(get_the_title($hrp_post));
            ?>
            <article <?php post_class('hrp-single', $hrp_post); ?> data-hrp-post="<?php echo (int) $hrp_post->ID; ?>">
                <header class="hrp-single-head">
                    <?php if ($group_label !== '') : ?>
                        <p class="hrp-single-kicker">
                            <?php if ($group_archive !== '') : ?>
                                <a href="<?php echo esc_url($group_archive); ?>"><?php echo esc_html($group_label); ?></a>
                            <?php else : ?>
                                <?php echo esc_html($group_label); ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <h1 class="hrp-single-title"><?php echo esc_html($display_title); ?></h1>
                    <p class="hrp-single-meta">
                        <time datetime="<?php echo esc_attr(get_the_date('c', $hrp_post)); ?>">
                            <?php echo esc_html(get_the_date('Y-m-d H:i', $hrp_post)); ?>
                        </time>
                        <?php if (function_exists('getPostViews')) : ?>
                            <span class="hrp-single-meta-sep" aria-hidden="true">·</span>
                            <span><?php echo esc_html(getPostViews($hrp_post->ID)); ?> 次瀏覽</span>
                        <?php endif; ?>
                    </p>
                </header>

                <div class="hrp-content hrp-content--single">
                    <?php
                    if (function_exists('setPostViews')) {
                        setPostViews($hrp_post->ID);
                    }
                    echo hrp_render_single_content($hrp_post); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </div>
            </article>

            <nav class="hrp-single-adjacent" aria-label="同分類文章">
                <?php if ($adjacent['prev'] instanceof WP_Post) : ?>
                    <a class="hrp-adjacent-link hrp-adjacent-link--prev" href="<?php echo esc_url(get_permalink($adjacent['prev'])); ?>">
                        <span class="hrp-adjacent-label">上一篇</span>
                        <span class="hrp-adjacent-title"><?php echo esc_html(hrp_display_title(get_the_title($adjacent['prev']))); ?></span>
                    </a>
                <?php else : ?>
                    <span></span>
                <?php endif; ?>
                <?php if ($adjacent['next'] instanceof WP_Post) : ?>
                    <a class="hrp-adjacent-link hrp-adjacent-link--next" href="<?php echo esc_url(get_permalink($adjacent['next'])); ?>">
                        <span class="hrp-adjacent-label">下一篇</span>
                        <span class="hrp-adjacent-title"><?php echo esc_html(hrp_display_title(get_the_title($adjacent['next']))); ?></span>
                    </a>
                <?php endif; ?>
            </nav>
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
