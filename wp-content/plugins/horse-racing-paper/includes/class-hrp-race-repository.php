<?php
/**
 * Race-day data from hkracing_* tables.
 *
 * @package Horse_Racing_Paper
 */

if (!defined('ABSPATH')) {
    exit;
}

final class HRP_Race_Repository
{
    public static function latest_meeting(?string $date = null): ?object
    {
        if ($date) {
            return HRP_DB::get_row(
                'SELECT * FROM hkracing_meetings WHERE date = %s ORDER BY updated_at DESC LIMIT 1',
                $date
            );
        }

        // Prefer today/future; else most recent past meeting with races.
        $today = HRP_DB::get_row(
            "SELECT * FROM hkracing_meetings
             WHERE date >= CURDATE()
             ORDER BY date ASC
             LIMIT 1"
        );
        if ($today) {
            return $today;
        }

        return HRP_DB::get_row(
            'SELECT * FROM hkracing_meetings ORDER BY date DESC LIMIT 1'
        );
    }

    /** @return array<int, object> */
    public static function races_for_meeting(string $meeting_id): array
    {
        return HRP_DB::get_results(
            'SELECT * FROM hkracing_races WHERE meeting_id = %s ORDER BY race_no ASC',
            $meeting_id
        );
    }

    /**
     * Runners joined with latest v2 score for a race.
     *
     * @return array<int, object>
     */
    public static function runners_with_scores(string $race_id): array
    {
        return HRP_DB::get_results(
            "SELECT
                r.runner_no,
                r.horse_code,
                r.name_ch,
                r.name_en,
                r.jockey_name_ch,
                r.jockey_name_en,
                r.trainer_name_ch,
                r.trainer_name_en,
                r.barrier_draw_number,
                r.handicap_weight,
                r.current_rating,
                r.win_odds AS runner_win_odds,
                r.final_position,
                r.status,
                s.total_score,
                s.blog_priority_score,
                s.blog_score,
                s.priority,
                s.prediction_tag,
                s.prediction_class,
                s.win_odds_snapshot,
                s.place_odds,
                s.barrier_trial_signal,
                s.barrier_trial_level,
                s.barrier_trial_keyword,
                s.score_win_rate,
                s.score_recent_form,
                s.score_draw_history,
                s.score_odds
             FROM hkracing_runners r
             LEFT JOIN hkracing_v2_score s
               ON s.race_id = r.race_id
              AND s.horse_code = r.horse_code
             WHERE r.race_id = %s
             ORDER BY
                CAST(NULLIF(r.runner_no, '') AS UNSIGNED) ASC,
                r.runner_no ASC",
            $race_id
        );
    }

    /**
     * Full race-day payload for UI.
     *
     * @return array{meeting:?object,races:array,by_race:array<int,array>}
     */
    public static function race_day(?string $date = null): array
    {
        $meeting = self::latest_meeting($date);
        if (!$meeting) {
            return ['meeting' => null, 'races' => [], 'by_race' => []];
        }

        $races = self::races_for_meeting((string) $meeting->id);
        $by_race = [];
        foreach ($races as $race) {
            $runners = self::runners_with_scores((string) $race->id);
            usort($runners, static function ($a, $b) {
                $sa = (float) ($a->blog_priority_score ?? $a->total_score ?? 0);
                $sb = (float) ($b->blog_priority_score ?? $b->total_score ?? 0);
                if ($sa === $sb) {
                    return ((int) $a->runner_no) <=> ((int) $b->runner_no);
                }
                return $sb <=> $sa; // higher score first for dense paper
            });
            $by_race[(int) $race->race_no] = [
                'race' => $race,
                'runners' => $runners,
            ];
        }

        return [
            'meeting' => $meeting,
            'races' => $races,
            'by_race' => $by_race,
        ];
    }

    public static function venue_label(string $code): string
    {
        $map = [
            'ST' => '沙田',
            'HV' => '跑馬地',
        ];
        return $map[strtoupper($code)] ?? $code;
    }

    public static function signal_label(?object $runner): string
    {
        $level = trim((string) ($runner->barrier_trial_level ?? ''));
        $kw = trim((string) ($runner->barrier_trial_keyword ?? ''));
        $sig = (int) ($runner->barrier_trial_signal ?? 0);
        if ($level === '' && $sig <= 0) {
            return '—';
        }
        $parts = [];
        if ($level !== '') {
            $parts[] = $level;
        }
        if ($sig > 0) {
            $parts[] = (string) $sig;
        }
        if ($kw !== '') {
            $parts[] = $kw;
        }
        return implode(' ', $parts);
    }

    public static function priority_tier(float $score): string
    {
        $s = HRP_Settings::get();
        $blog = $s['blog'] ?? [];
        $hot = (float) ($blog['priority_hot'] ?? 70);
        $watch = (float) ($blog['priority_watch'] ?? 55);
        $place = (float) ($blog['priority_place'] ?? 40);
        if ($score >= $hot) {
            return 'hot';
        }
        if ($score >= $watch) {
            return 'watch';
        }
        if ($score >= $place) {
            return 'place';
        }
        return 'low';
    }
}
