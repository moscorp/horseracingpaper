<?php
/**
 * Left hierarchical menu — native <details> accordion.
 * Post groups + static 數據分析 tool links.
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
            $type = (string) ($group['type'] ?? 'posts');
            $limit = (int) ($group['limit'] ?? 6);
            $nav_posts = $group['posts'] ?? [];
            $nav_links = $group['links'] ?? [];
            $archive = !empty($group['archive']) ? (string) $group['archive'] : '';
            $on_tools = is_page(['racing-analysis', 'mark-six-gann-chart', 'mark-six-gann']);
            // First content group open by default; expand 數據分析 when viewing a tool page.
            $open = (($group['key'] ?? '') === 'data_tools') ? $on_tools : ($index === 0 && !$on_tools);
            ?>
            <details class="hrp-nav-group" data-nav-group="<?php echo esc_attr($group['key']); ?>"<?php echo $open ? ' open' : ''; ?>>
                <summary class="hrp-nav-heading">
                    <span class="hrp-nav-title">
                        <?php echo esc_html($group['label']); ?>
                        <?php if ($archive !== '') : ?>
                            <a class="hrp-nav-archive" href="<?php echo esc_url($archive); ?>" data-nav-archive>全部</a>
                        <?php endif; ?>
                    </span>
                    <span class="hrp-nav-caret" aria-hidden="true"></span>
                </summary>
                <div class="hrp-nav-panel">
                    <?php if ($type === 'links') : ?>
                        <?php if (!$nav_links) : ?>
                            <p class="px-2.5 pb-2 text-[11px] text-paper-dim">暫無分析工具。</p>
                        <?php else : ?>
                            <ul class="hrp-nav-list">
                                <?php foreach ($nav_links as $link) :
                                    $href = (string) ($link['url'] ?? '');
                                    if ($href === '') {
                                        continue;
                                    }
                                    ?>
                                    <li>
                                        <a href="<?php echo esc_url($href); ?>">
                                            <?php echo esc_html((string) ($link['label'] ?? '')); ?>
                                            <?php if (!empty($link['meta'])) : ?>
                                                <span class="hrp-nav-meta"><?php echo esc_html((string) $link['meta']); ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php elseif (!$nav_posts) : ?>
                        <p class="px-2.5 pb-2 text-[11px] text-paper-dim">暫無文章（分類：<?php echo esc_html($group['category'] ?? ''); ?>）</p>
                    <?php else : ?>
                        <ul class="hrp-nav-list">
                            <?php foreach ($nav_posts as $i => $nav_post) :
                                if (!$nav_post instanceof WP_Post) {
                                    continue;
                                }
                                $href = get_permalink($nav_post);
                                if (!$href) {
                                    continue;
                                }
                                ?>
                                <li<?php echo $i >= $limit ? ' class="hrp-nav-extra" hidden' : ''; ?>>
                                    <a href="<?php echo esc_url($href); ?>">
                                        <?php echo esc_html(function_exists('hrp_display_title') ? hrp_display_title(get_the_title($nav_post)) : get_the_title($nav_post)); ?>
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
            </details>
        <?php endforeach; ?>
    <?php endif; ?>
</aside>
<?php
wp_reset_postdata();
