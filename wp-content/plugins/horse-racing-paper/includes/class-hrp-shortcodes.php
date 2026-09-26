<?php
/**
 * Public shortcodes — dense race-day paper.
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

final class HRP_Shortcodes
{
    public static function init(): void
    {
        add_shortcode('hrp_race_day', [self::class, 'race_day']);
        add_action('wp_enqueue_scripts', [self::class, 'assets']);
    }

    public static function assets(): void
    {
        wp_register_style(
            'hrp-race-day',
            HRP_PLUGIN_URL . 'public/css/race-day.css',
            [],
            HRP_VERSION
        );
        wp_register_script(
            'hrp-race-day',
            HRP_PLUGIN_URL . 'public/js/race-day.js',
            [],
            HRP_VERSION,
            true
        );
    }

    public static function race_day($atts = []): string
    {
        $atts = shortcode_atts([
            'date' => '',
        ], $atts, 'hrp_race_day');

        wp_enqueue_style('hrp-race-day');
        wp_enqueue_script('hrp-race-day');

        $date = $atts['date'] !== '' ? sanitize_text_field($atts['date']) : null;
        $payload = HRP_Race_Repository::race_day($date);
        $meeting = $payload['meeting'];
        $by_race = $payload['by_race'];

        ob_start();

        if (!$meeting || !$by_race) {
            echo '<div class="hrp-dense"><p class="hrp-empty">暫無賽事資料（請確認資料庫 hkracing_meetings / runners 已同步）。</p></div>';
            return (string) ob_get_clean();
        }

        $venue = HRP_Race_Repository::venue_label((string) $meeting->venue_code);
        $race_nos = array_keys($by_race);
        sort($race_nos, SORT_NUMERIC);
        $first = (int) $race_nos[0];
        ?>
        <div class="hrp-dense" data-hrp-race-day>
            <div class="hrp-meeting-meta">
                <span class="hrp-meta-strong"><?php echo esc_html($meeting->date); ?></span>
                <span><?php echo esc_html($venue); ?>（<?php echo esc_html((string) $meeting->venue_code); ?>）</span>
                <span><?php echo (int) $meeting->total_number_of_race; ?> 場</span>
                <?php if (!empty($meeting->status)) : ?>
                    <span class="hrp-muted"><?php echo esc_html((string) $meeting->status); ?></span>
                <?php endif; ?>
            </div>

            <div class="hrp-race-tabs" role="tablist" aria-label="場次">
                <?php foreach ($race_nos as $no) : ?>
                    <button type="button"
                        role="tab"
                        data-race="<?php echo (int) $no; ?>"
                        aria-selected="<?php echo $no === $first ? 'true' : 'false'; ?>">
                        R<?php echo (int) $no; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach ($race_nos as $no) :
                $block = $by_race[$no];
                $race = $block['race'];
                $runners = $block['runners'];
                $hidden = $no === $first ? '' : ' hidden';
                $title = $race->race_name_ch ?: $race->race_name_en;
                $dist = $race->distance ? ((int) $race->distance . '米') : '';
                $klass = $race->race_class_ch ?: $race->race_class_en;
                ?>
                <section class="hrp-race-panel" data-race-panel="<?php echo (int) $no; ?>"<?php echo $hidden; ?>>
                    <div class="hrp-race-head">
                        <strong>第<?php echo (int) $no; ?>場</strong>
                        <?php if ($title) : ?><span><?php echo esc_html($title); ?></span><?php endif; ?>
                        <?php if ($klass) : ?><span class="hrp-muted"><?php echo esc_html($klass); ?></span><?php endif; ?>
                        <?php if ($dist) : ?><span class="hrp-muted"><?php echo esc_html($dist); ?></span><?php endif; ?>
                        <?php if (!empty($race->post_time)) : ?>
                            <span class="hrp-muted"><?php echo esc_html((string) $race->post_time); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="hrp-table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>馬名</th>
                                <th>騎師</th>
                                <th>練馬師</th>
                                <th>檔</th>
                                <th>磅</th>
                                <th>評分</th>
                                <th>獨贏</th>
                                <th>優先</th>
                                <th>訊號</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!$runners) : ?>
                                <tr><td colspan="10">此場尚無出馬資料</td></tr>
                            <?php else : ?>
                                <?php foreach ($runners as $r) :
                                    $score = (float) ($r->blog_priority_score ?? $r->total_score ?? 0);
                                    $tier = HRP_Race_Repository::priority_tier($score);
                                    $odds = $r->win_odds_snapshot ?? $r->runner_win_odds;
                                    $name = $r->name_ch ?: $r->name_en;
                                    $jockey = $r->jockey_name_ch ?: $r->jockey_name_en;
                                    $trainer = $r->trainer_name_ch ?: $r->trainer_name_en;
                                    ?>
                                    <tr class="hrp-tier-<?php echo esc_attr($tier); ?>">
                                        <td><?php echo esc_html((string) $r->runner_no); ?></td>
                                        <td class="hrp-horse" title="<?php echo esc_attr((string) $r->horse_code); ?>">
                                            <?php echo esc_html((string) $name); ?>
                                            <?php if (!empty($r->prediction_tag)) : ?>
                                                <span class="hrp-tag"><?php echo esc_html((string) $r->prediction_tag); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html((string) $jockey); ?></td>
                                        <td><?php echo esc_html((string) $trainer); ?></td>
                                        <td><?php echo esc_html((string) ($r->barrier_draw_number ?: '—')); ?></td>
                                        <td><?php echo esc_html((string) ($r->handicap_weight ?: '—')); ?></td>
                                        <td><?php echo esc_html((string) ($r->current_rating ?: '—')); ?></td>
                                        <td><?php echo $odds !== null && $odds !== '' ? esc_html(number_format((float) $odds, 1)) : '—'; ?></td>
                                        <td class="hrp-score"><?php echo $score > 0 ? esc_html(number_format($score, 1)) : '—'; ?></td>
                                        <td class="hrp-signal"><?php echo esc_html(HRP_Race_Repository::signal_label($r)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endforeach; ?>

            <p class="hrp-legend">
                優先分門檻（可在後台調整）：
                <span class="hrp-tier-hot">強烈</span> /
                <span class="hrp-tier-watch">留意</span> /
                <span class="hrp-tier-place">配腳</span> /
                <span class="hrp-tier-low">謹慎</span>
                · 列按優先分排序
            </p>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
