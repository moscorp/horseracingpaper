<?php
/**
 * Cron job registry (admin-visible). Execution migrates from /00 + cron-job.org.
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

final class HRP_Cron_Registry
{
    public const OPTION_KEY = 'hrp_cron_jobs';

    public static function init(): void
    {
        // Reserved for future REST runner: /wp-json/hrp/v1/cron/{job}
    }

    public static function seed_jobs(): void
    {
        if (get_option(self::OPTION_KEY) !== false) {
            return;
        }
        add_option(self::OPTION_KEY, self::default_jobs(), '', false);
    }

    public static function default_jobs(): array
    {
        // Seeded from CRON_INVENTORY.md — paths still legacy /00 until migrated.
        $jobs = [
            ['id' => 'racing_sync_v2', 'name' => 'Racing sync v2', 'path' => '/00/HKJCRacingDataManager_sync-v2.php', 'provider' => 'cron-job.org', 'group' => 'racing', 'enabled' => true],
            ['id' => 'horse_history_batch', 'name' => 'Horse history batch', 'path' => '/00/HKJCHorseHistoryBatchManager_sync.php', 'provider' => 'cron-job.org', 'group' => 'racing', 'enabled' => true],
            ['id' => 'odds_manager', 'name' => 'Odds manager', 'path' => '/00/HKJCOddsManager_cron.php', 'provider' => 'cron-job.org', 'group' => 'racing', 'enabled' => true],
            ['id' => 'odds_to_db', 'name' => 'Odds → DB', 'path' => '/00/cron_odds2db.php', 'provider' => 'cron-job.org', 'group' => 'racing', 'enabled' => true],
            ['id' => 'odds_drop', 'name' => 'Odds drop', 'path' => '/00/cron_updateoddsdrop.php', 'provider' => 'cron-job.org', 'group' => 'racing', 'enabled' => true],
            ['id' => 'racecard', 'name' => 'Race card', 'path' => '/00/cron_racecard2.php', 'provider' => 'cron-job.org', 'group' => 'racing', 'enabled' => true],
            ['id' => 'rsdata', 'name' => 'RS data', 'path' => '/00/cron_rsdata.php', 'provider' => 'cron-job.org', 'group' => 'racing', 'enabled' => true],
            ['id' => 'barrier_result', 'name' => 'Barrier result', 'path' => '/00/cron_barierresult.php', 'provider' => 'cron-job.org', 'group' => 'racing', 'enabled' => true],
            ['id' => 'barrier_signal', 'name' => 'Barrier signal batch', 'path' => '/00/HKJCRacing_barrier_signal_cron.php', 'provider' => 'cron-job.org', 'group' => 'racing', 'enabled' => true],
            ['id' => 'priority', 'name' => 'Update priority', 'path' => '/00/HKJCRacing_update_priority_cron.php', 'provider' => 'cron-job.org', 'group' => 'racing', 'enabled' => true],
            ['id' => 'score_v2', 'name' => 'Score v2 batch', 'path' => '/00/HKJCRacing_v2_score_cron.php', 'provider' => 'cron-job.org', 'group' => 'racing', 'enabled' => true],
            ['id' => 'blog_racing', 'name' => 'Racing blog batch', 'path' => '/00/HKJC_blogpost.php', 'provider' => 'cron-job.org', 'group' => 'content', 'enabled' => true],
            ['id' => 'blog_review', 'name' => 'Race review batch', 'path' => '/00/HKJC_race_review.php', 'provider' => 'cron-job.org', 'group' => 'content', 'enabled' => true],
            ['id' => 'm6_blog', 'name' => 'Mark Six blog', 'path' => '/00/M6_blogpost.php', 'provider' => 'cron-job.org', 'group' => 'm6', 'enabled' => true],
            ['id' => 'm6_yoast', 'name' => 'Mark Six Yoast', 'path' => '/00/M6_blogpost_update_yoast.php', 'provider' => 'cron-job.org', 'group' => 'm6', 'enabled' => true],
            ['id' => 'm6_scrape', 'name' => 'Mark Six scrape', 'path' => '/00/M6_scraper_all.php', 'provider' => 'cron-job.org', 'group' => 'm6', 'enabled' => true],
            ['id' => 'mail_6', 'name' => 'Report mail', 'path' => '/00/cron_6.php', 'provider' => 'cron-job.org', 'group' => 'mail', 'enabled' => true],
            ['id' => 'mail_gann', 'name' => 'Gann mail', 'path' => '/00/cron_6Gann.php', 'provider' => 'cron-job.org', 'group' => 'mail', 'enabled' => true],
            ['id' => 'funds', 'name' => 'Funds email', 'path' => '/00/funds/cron_funds.php', 'provider' => 'cron-job.org', 'group' => 'funds', 'enabled' => true],
            ['id' => 'fund_hangseng', 'name' => 'Hang Seng funds', 'path' => '/00/funds/cron_fund_hangseng.php', 'provider' => 'cron-job.org', 'group' => 'funds', 'enabled' => true],
            ['id' => 'hsi_hangseng', 'name' => 'HSI Hang Seng', 'path' => '/00/funds/cron_hsi_hangseng.php', 'provider' => 'cron-job.org', 'group' => 'funds', 'enabled' => true],
            ['id' => 'hsi_meta', 'name' => 'HSI meta', 'path' => '/00/funds/cron_hsi_meta.php', 'provider' => 'cron-job.org', 'group' => 'funds', 'enabled' => true],
            ['id' => 'heartbeat', 'name' => 'Heartbeat', 'path' => '', 'provider' => 'cpanel', 'group' => 'system', 'enabled' => true],
        ];

        foreach ($jobs as &$job) {
            $job['last_run'] = null;
            $job['last_status'] = null;
            $job['last_error'] = null;
            $job['schedule'] = '';
        }
        unset($job);
        return $jobs;
    }

    public static function all(): array
    {
        $jobs = get_option(self::OPTION_KEY, []);
        if (!is_array($jobs) || !$jobs) {
            return self::default_jobs();
        }
        return $jobs;
    }
}
