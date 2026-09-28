<?php
/**
 * Left hierarchical hide/show menu — latest N per racing / Mark Six group.
 *
 * Important: never assign to $post here — that global is the main query’s current post.
 *
 * @package Horse_Racing_Paper
 */

$groups = class_exists('HRP_Nav') ? HRP_Nav::groups() : [];
?>
<aside class="hrp-sidebar" data-hrp-nav aria-label="內容導覽">
    <div class="border-b border-ink-line px-2.5 py-2 text-[11px] font-semibold tracking-wide text-paper-dim">
        最新內容
    </div>
    <?php if (!$groups) : ?>
        <p class="px-2.5 py-2 text-[12px] text-paper-dim">請啟用自由馬紙外掛以載入側欄。</p>
    <?php else : ?>
        <?php foreach ($groups as $index => $group) :
            $limit = (int) ($group['limit'] ?? 6);
            $expanded = $index === 0 ? 'true' : 'false';
            $nav_posts = $group['posts'] ?? [];
            $archive = !empty($group['archive']) ? (string) $group['archive'] : '';
            ?>
            <div class="hrp-nav-group" data-nav-group="<?php echo esc_attr($group['key']); ?>">
                <div class="hrp-nav-heading">
                    <?php if ($archive !== '') : ?>
                        <a class="hrp-nav-title" href="<?php echo esc_url($archive); ?>">
                            <?php echo esc_html($group['label']); ?>
                        </a>
                    <?php else : ?>
                        <span class="hrp-nav-title"><?php echo esc_html($group['label']); ?></span>
                    <?php endif; ?>
                    <button type="button"
                        class="hrp-nav-toggle"
                        aria-expanded="<?php echo esc_attr($expanded); ?>"
                        aria-label="<?php echo esc_attr($group['label'] . ' 展開/收合'); ?>"
                        data-nav-toggle>
                        <span data-nav-caret aria-hidden="true"><?php echo $expanded === 'true' ? '−' : '+'; ?></span>
                    </button>
                </div>
                <div class="hrp-nav-panel"<?php echo $expanded === 'false' ? ' hidden' : ''; ?>>
                    <?php if (!$nav_posts) : ?>
                        <p class="px-2.5 pb-2 text-[11px] text-paper-dim">暫無文章（分類：<?php echo esc_html($group['category']); ?>）</p>
                    <?php else : ?>
                        <ul class="hrp-nav-list">
                            <?php foreach ($nav_posts as $i => $nav_post) : ?>
                                <li<?php echo $i >= $limit ? ' class="hrp-nav-extra" hidden' : ''; ?>>
                                    <a href="<?php echo esc_url(get_permalink($nav_post)); ?>">
                                        <?php echo esc_html(get_the_title($nav_post)); ?>
                                        <span class="hrp-nav-meta"><?php echo esc_html(get_the_date('Y-m-d', $nav_post)); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($nav_posts) > $limit) : ?>
                            <div class="px-2 pb-2">
                                <button type="button" class="hrp-nav-more" data-nav-more data-archive="<?php echo esc_url($archive); ?>">
                                    更多
                                </button>
                            </div>
                        <?php elseif ($archive !== '') : ?>
                            <div class="px-2 pb-2">
                                <a class="hrp-nav-more inline-block text-center" href="<?php echo esc_url($archive); ?>">更多</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</aside>
<?php
// Restore main query post after sidebar markup (defensive).
if (function_exists('wp_reset_postdata')) {
    wp_reset_postdata();
}
