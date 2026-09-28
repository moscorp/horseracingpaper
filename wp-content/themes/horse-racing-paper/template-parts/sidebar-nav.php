<?php
/**
 * Left hierarchical hide/show menu — latest N per racing / Mark Six group.
 *
 * @package Horse_Racing_Paper
 */

$groups = class_exists('HRP_Nav') ? HRP_Nav::groups() : [];
?>
<aside class="hrp-sidebar" data-hrp-nav aria-label="內容導覽">
    <div class="border-b border-ink-line px-2.5 py-2 text-[11px] font-semibold tracking-wide text-paper-mute">
        最新內容
    </div>
    <?php if (!$groups) : ?>
        <p class="px-2.5 py-2 text-[12px] text-paper-mute">請啟用自由馬紙外掛以載入側欄。</p>
    <?php else : ?>
        <?php foreach ($groups as $index => $group) :
            $limit = (int) ($group['limit'] ?? 6);
            $expanded = $index === 0 ? 'true' : 'false';
            $posts = $group['posts'] ?? [];
            ?>
            <div class="hrp-nav-group" data-nav-group="<?php echo esc_attr($group['key']); ?>">
                <button type="button"
                    class="hrp-nav-toggle"
                    aria-expanded="<?php echo esc_attr($expanded); ?>"
                    data-nav-toggle>
                    <span><?php echo esc_html($group['label']); ?></span>
                    <span class="text-paper-mute" data-nav-caret aria-hidden="true"><?php echo $expanded === 'true' ? '−' : '+'; ?></span>
                </button>
                <div class="hrp-nav-panel"<?php echo $expanded === 'false' ? ' hidden' : ''; ?>>
                    <?php if (!$posts) : ?>
                        <p class="px-2.5 pb-2 text-[11px] text-paper-mute">暫無文章（分類：<?php echo esc_html($group['category']); ?>）</p>
                    <?php else : ?>
                        <ul class="hrp-nav-list">
                            <?php foreach ($posts as $i => $post) : ?>
                                <li<?php echo $i >= $limit ? ' class="hrp-nav-extra" hidden' : ''; ?>>
                                    <a href="<?php echo esc_url(get_permalink($post)); ?>">
                                        <?php echo esc_html(get_the_title($post)); ?>
                                        <span class="hrp-nav-meta"><?php echo esc_html(get_the_date('Y-m-d', $post)); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($posts) > $limit) : ?>
                            <div class="px-2 pb-2">
                                <button type="button" class="hrp-nav-more" data-nav-more data-archive="<?php echo esc_url($group['archive']); ?>">
                                    更多
                                </button>
                            </div>
                        <?php elseif (!empty($group['has_category']) && !empty($group['archive'])) : ?>
                            <div class="px-2 pb-2">
                                <a class="hrp-nav-more inline-block text-center" href="<?php echo esc_url($group['archive']); ?>">更多</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</aside>
