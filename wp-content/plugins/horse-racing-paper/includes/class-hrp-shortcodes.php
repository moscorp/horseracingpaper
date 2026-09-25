<?php
/**
 * Public shortcodes.
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
    }

    public static function race_day($atts = []): string
    {
        // Placeholder until DB layer is wired after legacy import.
        ob_start();
        ?>
        <div class="hrp-dense" data-hrp-race-day>
            <div class="hrp-race-tabs" role="tablist">
                <?php for ($i = 1; $i <= 8; $i++) : ?>
                    <button type="button" role="tab" data-race="R<?php echo (int) $i; ?>" aria-selected="<?php echo $i === 1 ? 'true' : 'false'; ?>">R<?php echo (int) $i; ?></button>
                <?php endfor; ?>
            </div>
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>馬名</th>
                    <th>騎師</th>
                    <th>檔</th>
                    <th>賠率</th>
                    <th>評分</th>
                    <th>訊號</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="7">資料層尚未接上（等待 legacy 匯入）。</td></tr>
                </tbody>
            </table>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
