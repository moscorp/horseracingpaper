<?php
/**
 * Category archive — Chinese group labels when mapped.
 *
 * @package Horse_Racing_Paper
 */

get_header();
$term = get_queried_object();
$label = '分類';
if ($term instanceof WP_Term) {
    $label = $term->name;
    if (class_exists('HRP_Nav')) {
        $map = [
            'racing_result' => '賽事結果',
            'racing_analysis' => '賽事分析',
            'm6_result' => '六合彩結果',
            'm6_analysis' => '六合彩分析',
        ];
        foreach (HRP_Nav::category_aliases() as $key => $aliases) {
            foreach ($aliases as $alias) {
                if (strcasecmp((string) $alias, (string) $term->slug) === 0
                    || strcasecmp((string) $alias, (string) $term->name) === 0) {
                    $label = $map[$key] ?? $label;
                    break 2;
                }
            }
        }
    }
}
?>
<div class="hrp-layout flex-col md:flex-row">
    <?php get_template_part('template-parts/sidebar', 'nav'); ?>
    <main class="hrp-main">
        <h1 class="mb-3 font-display text-lg font-bold text-brass">
            <?php echo esc_html($label); ?>
        </h1>
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article class="mb-2 border-b border-ink-line py-2">
                <a class="text-sm text-paper hover:text-brass" href="<?php the_permalink(); ?>">
                    <?php echo esc_html(hrp_display_title(get_the_title())); ?>
                    <span class="ml-2 text-[11px] text-paper-dim"><?php echo esc_html(get_the_date('Y-m-d H:i')); ?></span>
                </a>
            </article>
        <?php endwhile; ?>
            <div class="mt-3 text-sm text-paper-dim"><?php the_posts_pagination(); ?></div>
        <?php else : ?>
            <p class="text-paper-dim">此分類尚無文章。</p>
        <?php endif; ?>
    </main>
</div>
<?php
get_footer();
