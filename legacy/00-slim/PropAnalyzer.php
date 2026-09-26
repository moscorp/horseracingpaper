<?php
/**
 * PropAnalyzer — Rich feature extraction for statistical rule mining.
 *
 * For each race (grouped by racingdate+raceno, sorted by 1/prewin ASC, tiebreak prepla ASC):
 *   - Analyze proppre patterns (symptoms)
 *   - Extract per-horse feature vectors
 */

class PropAnalyzer {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Fetch races grouped & sorted.
     */
    public function fetchRaces($venue = null, $dateFrom = null, $dateTo = null, $venueLike = null) {
        $sql = "SELECT * FROM racepropresult WHERE proppre IS NOT NULL";
        $params = [];

        if ($venue !== null && $venue !== '') {
            $sql .= " AND venue = ?";
            $params[] = $venue;
        }
        if ($venueLike !== null && $venueLike !== '') {
            $venues = explode(',', $venueLike);
            $placeholders = implode(',', array_fill(0, count($venues), '?'));
            $sql .= " AND venue IN ($placeholders)";
            foreach ($venues as $v) {
                $params[] = trim($v);
            }
        }
        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= " AND racingdate >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $sql .= " AND racingdate <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY racingdate DESC, raceno DESC, (1.0/prewin) DESC, prepla ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $races = [];
        foreach ($rows as $row) {
            if (isset($row['horsename']) && !mb_check_encoding($row['horsename'], 'UTF-8')) {
                $row['horsename'] = mb_convert_encoding($row['horsename'], 'UTF-8', 'UTF-8,GBK,BIG5');
            }
            if (isset($row['go_ch']) && !mb_check_encoding($row['go_ch'], 'UTF-8')) {
                $row['go_ch'] = mb_convert_encoding($row['go_ch'], 'UTF-8', 'UTF-8,GBK,BIG5');
            }
            $key = $row['racingdate'] . '|' . $row['venue'] . '|' . $row['raceno'];
            if (!isset($races[$key])) {
                $races[$key] = [
                    'racingdate' => $row['racingdate'],
                    'raceno'     => (int)$row['raceno'],
                    'venue'      => $row['venue'],
                    'distance'   => (int)$row['Distance'],
                    'go_ch'      => $row['go_ch'],
                    'horses'     => [],
                ];
            }
            $races[$key]['horses'][] = $row;
        }

        foreach ($races as &$race) {
            usort($race['horses'], function($a, $b) {
                $valA = $a['prewin'] > 0 ? (1.0 / (float)$a['prewin']) : 999999.0;
                $valB = $b['prewin'] > 0 ? (1.0 / (float)$b['prewin']) : 999999.0;
                if (abs($valA - $valB) > 0.00001) {
                    return $valA > $valB ? -1 : ($valA < $valB ? 1 : 0);
                }
                $pa = (float)$a['prepla']; $pb = (float)$b['prepla'];
                return $pa > $pb ? -1 : ($pa < $pb ? 1 : 0);
            });
        }

        return array_values($races);
    }

    /**
     * Core: build feature vector shaped analysis.
     */
    public function analyzeRace($race) {
        $horses  = $race['horses'];
        $n       = count($horses);
        $propArr = [];
        foreach ($horses as $h) {
            $propArr[] = (int)$h['proppre'];
        }

        // Frequency table
        $freq = [];
        foreach ($propArr as $v) {
            $freq[$v] = (isset($freq[$v]) ? $freq[$v] : 0) + 1;
        }

        // label per position
        $labels = [];
        foreach ($propArr as $idx => $v) {
            $c = $freq[$v];
            if ($c < 2) { $labels[$idx] = null; continue; }
            if ($v >= 10 && $v <= 19) $labels[$idx] = '1x';
            elseif ($v >= 20 && $v <= 29) $labels[$idx] = ($c >= 3) ? '32x' : '2x';
            elseif ($v >= 30 && $v <= 39) $labels[$idx] = ($c >= 3) ? '33x' : '3x';
            elseif ($v >= 40 && $v <= 49) $labels[$idx] = ($c >= 3) ? '34x' : '4x';
            else $labels[$idx] = null;
        }

        // Position group info
        $valPositions = [];
        foreach ($propArr as $idx => $v) {
            $valPositions[$v][] = $idx;
        }

        $groups = [];
        foreach ($freq as $val => $cnt) {
            if ($cnt < 2) continue;
            $posList = $valPositions[$val];
            sort($posList);
            $lbl = '';
            if ($val >= 10 && $val <= 19) $lbl = '1x';
            elseif ($val >= 20 && $val <= 29) $lbl = ($cnt >= 3) ? '32x' : '2x';
            elseif ($val >= 30 && $val <= 39) $lbl = ($cnt >= 3) ? '33x' : '3x';
            elseif ($val >= 40 && $val <= 49) $lbl = ($cnt >= 3) ? '34x' : '4x';
            if ($lbl === '') continue;

            $groups[] = [
                'value'      => $val,
                'label'      => $lbl,
                'count'      => $cnt,
                'top_pos'    => $posList[0],
                'bottom_pos' => $posList[count($posList) - 1],
                'middle_pos' => $posList[(int)floor((count($posList) - 1) / 2)],
                'all_pos'    => $posList,
            ];
        }

        // Range stats
        $count40_49 = 0; $count50plus = 0; $hasOver50 = false;
        $B2 = $B2_pos = $B3 = $B3_pos = $B4 = $B4_pos = null;
        $min20 = $min20_pos = null;
        $maxVal = PHP_INT_MIN;
        $mid40 = false;
        $first40 = null;
        $pos18 = [];
        $pos19 = [];

        foreach ($propArr as $idx => $v) {
            if ($v >= 40 && $v <= 49) { $count40_49++; if (!$first40) $first40 = ['val'=>$v,'pos'=>$idx]; }
            if ($v >= 50) { $count50plus++; $hasOver50 = true; }
            if ($v >= 20 && $v <= 29) {
                if ($B2 === null || $v > $B2) { $B2 = $v; $B2_pos = $idx; }
                if ($min20 === null || $v < $min20) { $min20 = $v; $min20_pos = $idx; }
            }
            if ($v >= 30 && $v <= 39 && ($B3 === null || $v > $B3)) { $B3 = $v; $B3_pos = $idx; }
            if ($v >= 40 && $v <= 49 && ($B4 === null || $v > $B4)) { $B4 = $v; $B4_pos = $idx; }
            if ($v > $maxVal) $maxVal = $v;
            if ($v == 18) $pos18[] = $idx;
            if ($v == 19) $pos19[] = $idx;
        }

        // 40-49 middle check
        $hasMid40 = false;
        foreach ($propArr as $idx => $v) {
            if ($v >= 40 && $v <= 49) {
                $ratio = $idx / max(1, $n - 1);
                if ($ratio >= 0.25 && $ratio <= 0.75) { $hasMid40 = true; break; }
            }
        }

        $has18 = count($pos18) > 0;
        $has19 = count($pos19) > 0;
        $any18Bot = false; $any19Bot = false;
        $botThreshold = $n * 0.66;
        foreach ($pos18 as $p) { if ($p >= $botThreshold) { $any18Bot = true; break; } }
        foreach ($pos19 as $p) { if ($p >= $botThreshold) { $any19Bot = true; break; } }

        // Distinct groups
        $d32x = array_values(array_filter($groups, function($g) { return $g['label'] === '32x'; }));
        $d33x = array_values(array_filter($groups, function($g) { return $g['label'] === '33x'; }));
        $d3x  = array_values(array_filter($groups, function($g) { return $g['label'] === '3x'; }));
        $d2x  = array_values(array_filter($groups, function($g) { return $g['label'] === '2x'; }));
        $d1x  = array_values(array_filter($groups, function($g) { return $g['label'] === '1x'; }));

        $total33x = 0; $total3x = 0;
        foreach ($labels as $l) {
            if ($l === '33x') $total33x++;
            if ($l === '3x') $total3x++;
        }

        // ===== Per-horse feature vectors =====
        $features = [];
        for ($i = 0; $i < $n; $i++) {
            $h = $horses[$i];
            $v = $propArr[$i];
            $l = $labels[$i];
            $fp = (int)(isset($h['finalPosition']) ? $h['finalPosition'] : 0);

            $f = [
                'race_key'     => $race['racingdate'] . '|' . $race['venue'] . '|R' . $race['raceno'],
                'racingdate'   => $race['racingdate'],
                'raceno'       => $race['raceno'],
                'venue'        => $race['venue'],
                'distance'     => $race['distance'],
                'horse'        => $h['horsename'],
                'horsecode'    => $h['horsecode'],
                'prewin'       => (float)$h['prewin'],
                'prepla'       => (float)$h['prepla'],
                'position'     => $i,               // 0-index in sorted order
                'total_horses' => $n,
                'prop_val'     => $v,
                'label'        => $l ?: null,
                'finalPosition' => $fp,
                'go_ch'         => isset($h['go_ch']) ? $h['go_ch'] : '',

                // Label and value range category
                'prop_range'   => $v >= 50 ? '50+' : ($v >= 40 ? '40-49' : ($v >= 30 ? '30-39' : ($v >= 20 ? '20-29' : '10-19'))),
                'has_label'    => $l !== null,
                'is_32x'       => $l === '32x',
                'is_33x'       => $l === '33x',
                'is_3x'        => $l === '3x',
                'is_2x'        => $l === '2x',
                'is_1x'        => $l === '1x',
                'is_4x'        => $l === '4x',
                'is_34x'       => $l === '34x',

                // Position percentile
                'pos_ratio'    => round($i / max(1, $n - 1), 3),
                'is_top'       => $i === 0,
                'is_top2'      => $i <= 1,
                'is_top3'      => $i <= 2,
                'is_bottom'    => $i === $n - 1,
                'is_bottom2'   => $i >= $n - 2,
                'is_bottom3'   => $i >= $n - 3,
                'is_mid'       => $i >= floor($n * 0.33) && $i <= ceil($n * 0.66),

                // Top-1 special
                'top1_prop'    => $i === 0 ? $v : null,
                'top1_range'   => $i === 0 ? ($v >= 50 ? '50+' : ($v >= 40 ? '40-49' : ($v >= 30 ? '30-39' : ($v >= 20 ? '20-29' : '10-19')))) : null,
                'top1_is_50_59' => ($i === 0 && $v >= 50 && $v <= 59),

                // Race-level features (same for all horses but useful for crossing)
                'race_nhorses' => $n,
                'race_has_over50' => $hasOver50,
                'race_count40_49' => $count40_49,
                'race_count50plus' => $count50plus,
                'race_d32x_count'  => count($d32x),
                'race_d33x_count'  => count($d33x),
                'race_d3x_count'   => count($d3x),
                'race_total33x'    => $total33x,
                'race_total3x'     => $total3x,
                'race_B2'          => $B2,
                'race_B3'          => $B3,
                'race_B4'          => $B4,
                'race_maxVal'      => $maxVal,
                'race_has18'       => $has18,
                'race_has19'       => $has19,
                'race_hasMid40'    => $hasMid40,
                'race_any18Bot'    => $any18Bot,
                'race_any19Bot'    => $any19Bot,

                // Big verse and position relations
                'is_B2'        => ($i === $B2_pos),
                'is_B3'        => ($i === $B3_pos),
                'is_B4'        => ($i === $B4_pos),
                'B2_lt_B3'     => ($B2 !== null && $B3 !== null && $B2 < $B3),
                'B3_lt_B2'     => ($B3 !== null && $B2 !== null && $B3 < $B2),

                // Neighbor differences
                'prev_diff'    => $i > 0 ? $propArr[$i-1] - $v : null,
                'next_diff'    => $i+1 < $n ? $propArr[$i+1] - $v : null,
                'prev_gt'      => $i > 0 && $propArr[$i-1] > $v,
                'next_gt'      => $i+1 < $n && $propArr[$i+1] > $v,
                'is_valley'    => $i > 0 && $i+1 < $n && $propArr[$i-1] >= $v && $propArr[$i+1] >= $v,
                'is_peak'      => $i > 0 && $i+1 < $n && $propArr[$i-1] <= $v && $propArr[$i+1] <= $v,

                // voro distribution
                'dup_count'    => $freq[$v],
                'is_lonely'    => $freq[$v] == 1,
                'is_dup'       => $freq[$v] >= 2,

                // Final outcome (for training)
                'finalPos'     => $fp,
                'won'         => $fp <= 1,
                'placed'      => $fp <= 3,
                'within2'     => $fp <= 2,
                'within3'     => $fp <= 3,
                'within4'     => $fp <= 4,
                'within5'     => $fp <= 5,

                // ============ CROSS FEATURES ============
                'in_group'             => $freq[$v] >= 2,
                'group_leader'         => ($freq[$v] >= 2 && $i === min($valPositions[$v])),
                'group_tail'           => ($freq[$v] >= 2 && $i === max($valPositions[$v])),
                'group_mid'            => ($freq[$v] >= 3 && $i !== min($valPositions[$v]) && $i !== max($valPositions[$v])),
                'group_size'           => $freq[$v] >= 2 ? count($valPositions[$v]) : 1,
                'group_pctile'         => ($freq[$v] >= 2) ? round(array_search($i, $valPositions[$v]) / max(1, count($valPositions[$v]) - 1), 2) : 0,

                'has_sequential_33x'   => ($l === '33x' && $i + 1 < $n && $labels[$i+1] === '33x'),
                'has_sequential_32x'   => ($l === '32x' && $i + 1 < $n && $labels[$i+1] === '32x'),
                'has_sequential_2x'   => ($l === '2x' && $i + 1 < $n && $labels[$i+1] === '2x'),
                'has_sequential_3x'   => ($l === '3x' && $i + 1 < $n && $labels[$i+1] === '3x'),
                'is_sequential_head'  => ($l !== null && $i + 1 < $n && $labels[$i+1] === $l),
                'is_sequential_tail'  => ($l !== null && $i > 0 && $labels[$i-1] === $l),

                'abrupt_drop'      => ($i + 1 < $n && $v > max(1, $propArr[$i+1]) * 2),
                'abrupt_rise'      => ($i > 0 && $propArr[$i-1] > max(1, $v) * 2),
                'is_plateau_start'  => ($i + 1 < $n && abs($v - $propArr[$i+1]) <= 2 && ($i === 0 || abs($v - $propArr[$i-1]) > 2)),
                'is_plateau_mid'    => ($i > 0 && $i + 1 < $n && abs($v - $propArr[$i-1]) <= 2 && abs($v - $propArr[$i+1]) <= 2),
                'is_plateau_end'    => ($i > 0 && abs($v - $propArr[$i-1]) <= 2 && ($i === $n - 1 || abs($v - $propArr[$i+1]) > 2)),

                'race_unique_props'  => count(array_unique($propArr)),
                'race_prop_spread'   => max($propArr) - min($propArr),
            ];
            $features[] = $f;
        }

        // Also return the legacy 'analysis' structure for some client-side display
        $analysis = [
            'race_key'       => $race['racingdate'] . '|' . $race['venue'] . '|R' . $race['raceno'],
            'racingdate'     => $race['racingdate'],
            'raceno'         => $race['raceno'],
            'venue'          => $race['venue'],
            'distance'       => $race['distance'],
            'go_ch'          => isset($race['go_ch']) ? $race['go_ch'] : '',
            'horse_count'    => $n,
            'prop_order'     => $propArr,
            'labels'         => array_values($labels),
            'B2' => $B2, 'B2_pos' => $B2_pos,
            'B3' => $B3, 'B3_pos' => $B3_pos,
            'B4' => $B4, 'B4_pos' => $B4_pos,
            'groups'         => $groups,
            'min20_pos'      => $min20_pos,
            'hasMid40'       => $hasMid40,
            'any18Bot'       => $any18Bot,
            'any19Bot'       => $any19Bot,
        ];

        return [
            'analysis' => $analysis,
            'features' => $features,  // per-horse feature vectors
        ];
    }

    /**
     * Batch processing: returned is mixed analysis + features.
     */
    public function analyzeBatch($races) {
        $allAnalysis = [];
        $allFeatures = [];
        foreach ($races as $race) {
            $r = $this->analyzeRace($race);
            $allAnalysis[] = $r['analysis'];
            $allFeatures = array_merge($allFeatures, $r['features']);
        }
        return ['analyses' => $allAnalysis, 'features' => $allFeatures];
    }
}