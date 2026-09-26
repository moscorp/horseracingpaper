<?php
/**
 * PropManualRuleEngine — evaluate condition_json / pick_json against fingerprints.
 *
 * Hit definition: finalPosition <= fp_max (default 4, configurable 3|4).
 * Marks: each resolved pick position gets +mark_weight (equal, no priority).
 */
require_once __DIR__ . '/PropFingerprint.php';

class PropManualRuleEngine {

    /**
     * Does fingerprint match condition AST?
     * condition: { "all": [ ...clauses ], "any": [ ... ] }
     */
    public static function matchCondition(array $fp, $condition) {
        if ($condition === null || $condition === '' || $condition === array()) {
            return true;
        }
        if (is_string($condition)) {
            $condition = json_decode($condition, true);
        }
        if (!is_array($condition)) return false;

        if (isset($condition['all']) && is_array($condition['all'])) {
            foreach ($condition['all'] as $c) {
                if (!self::matchClause($fp, $c)) return false;
            }
        }
        if (isset($condition['any']) && is_array($condition['any'])) {
            if (empty($condition['any'])) {
                // empty any = ignore
            } else {
                $ok = false;
                foreach ($condition['any'] as $c) {
                    if (self::matchClause($fp, $c)) { $ok = true; break; }
                }
                if (!$ok) return false;
            }
        }
        // bare clause object
        if (!isset($condition['all']) && !isset($condition['any'])) {
            return self::matchClause($fp, $condition);
        }
        return true;
    }

    public static function matchClause(array $fp, $c) {
        if (!is_array($c) || empty($c)) return true;

        if (isset($c['venue_bucket'])) {
            $vb = isset($fp['venue_bucket']) ? $fp['venue_bucket'] : null;
            if ($c['venue_bucket'] === 'ALL') return true;
            return $vb === $c['venue_bucket'];
        }

        $op = isset($c['op']) ? $c['op'] : null;
        $band = isset($c['band']) ? (string)$c['band'] : null;

        if ($op === 'has_label') {
            $label = $c['label'];
            $size = isset($c['size']) ? $c['size'] : null;
            return PropFingerprint::countGroups($fp, $band, $label, $size) >= 1;
        }

        if ($op === 'c_label' || $op === 'c2x' || $op === 'count') {
            $label = isset($c['label']) ? $c['label'] : null;
            $size = isset($c['size']) ? $c['size'] : null;
            // shorthand c2x on band without label → pair count
            if ($op === 'c2x' && $label === null && $band) {
                $cnt = isset($fp['bands'][$band]['c_pair']) ? (int)$fp['bands'][$band]['c_pair'] : 0;
            } elseif (isset($c['key']) && $band) {
                // e.g. key=c32x
                $cnt = isset($fp['bands'][$band][$c['key']]) ? (int)$fp['bands'][$band][$c['key']] : 0;
            } else {
                $cnt = PropFingerprint::countGroups($fp, $band, $label, $size);
            }
            return self::cmpCount($cnt, $c);
        }

        if ($op === 'c_w') {
            $w = isset($fp['bands'][$band]['w']) ? $fp['bands'][$band]['w'] : array();
            $size = isset($c['size']) ? $c['size'] : null;
            $cnt = 0;
            foreach ($w as $ww) {
                if ($size === null || $ww['size'] === $size) $cnt++;
            }
            return self::cmpCount($cnt, $c);
        }

        if ($op === 'only_label') {
            // string's only multi-group label type is this label (may have multiple of same)
            $label = $c['label'];
            $labels = isset($fp['all_labels']) ? $fp['all_labels'] : array();
            if (empty($labels)) return false;
            foreach ($labels as $lb) {
                if ($lb !== $label) return false;
            }
            return in_array($label, $labels, true);
        }

        if ($op === 'nested_in') {
            // inner group positions all between outer top/bottom
            $inner = self::resolveGroupSpec($fp, isset($c['inner']) ? $c['inner'] : $c);
            $outer = self::resolveGroupSpec($fp, isset($c['outer']) ? $c['outer'] : array());
            if (!$inner || !$outer) return false;
            $lo = min($outer['pos']);
            $hi = max($outer['pos']);
            foreach ($inner['pos'] as $p) {
                if ($p < $lo || $p > $hi) return false;
            }
            return true;
        }

        if ($op === 'adjacent') {
            $pa = self::resolvePositions($fp, isset($c['from']) ? $c['from'] : null, isset($c['from_of']) ? $c['from_of'] : (isset($c['of']) ? $c['of'] : null));
            $pb = self::resolvePositions($fp, isset($c['to']) ? $c['to'] : null, isset($c['to_of']) ? $c['to_of'] : null);
            if (empty($pa) || empty($pb)) return false;
            foreach ($pa as $a) {
                foreach ($pb as $b) {
                    if (abs($a - $b) === 1) return true;
                }
            }
            return false;
        }

        if ($op === 'not_top') {
            $pos = self::resolvePositions($fp, isset($c['ref']) ? $c['ref'] : null, isset($c['of']) ? $c['of'] : null);
            if (empty($pos)) return false;
            foreach ($pos as $p) {
                if ((int)$p === 0) return false;
            }
            return true;
        }

        if ($op === 'is_top') {
            $pos = self::resolvePositions($fp, isset($c['ref']) ? $c['ref'] : null, isset($c['of']) ? $c['of'] : null);
            if (empty($pos)) return false;
            foreach ($pos as $p) {
                if ((int)$p === 0) return true;
            }
            return false;
        }

        if ($op === 'no_pairs' || $op === 'no_x') {
            // No duplicate prop values → no ?x / 3?x groups (all singletons)
            $labels = isset($fp['all_labels']) ? $fp['all_labels'] : array();
            return empty($labels);
        }

        if ($op === 'neq_token') {
            // e.g. w2 is not B2 — compare values
            $a = self::resolveTokenValue($fp, isset($c['a']) ? $c['a'] : null, isset($c['a_of']) ? $c['a_of'] : null);
            $b = self::resolveTokenValue($fp, isset($c['b']) ? $c['b'] : null, isset($c['b_of']) ? $c['b_of'] : null);
            if ($a === null || $b === null) return false;
            return $a !== $b;
        }

        if ($op === 'contains_value') {
            $val = (int)$c['value'];
            $order = isset($fp['prop_order']) ? $fp['prop_order'] : array();
            return in_array($val, $order, true);
        }

        // unknown op — ignore as true if empty, else false
        return false;
    }

    private static function cmpCount($cnt, $c) {
        if (isset($c['eq'])) return $cnt === (int)$c['eq'];
        if (isset($c['gte'])) return $cnt >= (int)$c['gte'];
        if (isset($c['lte'])) return $cnt <= (int)$c['lte'];
        if (isset($c['gt'])) return $cnt > (int)$c['gt'];
        if (isset($c['lt'])) return $cnt < (int)$c['lt'];
        return $cnt >= 1;
    }

    /**
     * Resolve pick_json → list of unique positions (0-based).
     */
    public static function resolvePicks(array $fp, $pickJson) {
        if (is_string($pickJson)) $pickJson = json_decode($pickJson, true);
        if (!is_array($pickJson)) return array();
        $marks = isset($pickJson['marks']) ? $pickJson['marks'] : (isset($pickJson[0]) ? $pickJson : array());
        $out = array();
        foreach ($marks as $m) {
            foreach (self::resolveOnePick($fp, $m) as $p) {
                if ($p === null || $p < 0 || $p >= $fp['n_horses']) continue;
                $out[(int)$p] = true;
            }
        }
        return array_map('intval', array_keys($out));
    }

    private static function resolveOnePick(array $fp, $m) {
        if (!is_array($m)) return array();
        $ref = isset($m['ref']) ? $m['ref'] : null;
        $of = isset($m['of']) ? $m['of'] : (isset($m['of_group']) ? $m['of_group'] : null);
        $offset = isset($m['offset']) ? (int)$m['offset'] : 0;

        // between: pick w{n} whose pos is strictly between two refs
        if (isset($m['between']) && is_array($m['between']) && count($m['between']) === 2) {
            $band = isset($m['band']) ? (string)$m['band'] : (isset($of['band']) ? (string)$of['band'] : null);
            $wBand = isset($m['w_band']) ? (string)$m['w_band'] : $band;
            // default: pick w3 between pos21 and pos22 of a 2x
            if ($wBand === null && isset($m['ref']) && preg_match('/^w(\d)$/', $m['ref'], $mm)) {
                $wBand = $mm[1];
            }
            $p0 = self::resolvePositions($fp, $m['between'][0], $of);
            $p1 = self::resolvePositions($fp, $m['between'][1], $of);
            if (empty($p0) || empty($p1) || $wBand === null) return array();
            $lo = min($p0[0], $p1[0]);
            $hi = max($p0[0], $p1[0]);
            $hits = array();
            $ws = isset($fp['bands'][$wBand]['w']) ? $fp['bands'][$wBand]['w'] : array();
            foreach ($ws as $w) {
                if ($w['pos'] > $lo && $w['pos'] < $hi) $hits[] = $w['pos'];
            }
            return $hits;
        }

        $positions = self::resolvePositions($fp, $ref, $of);
        if ($offset !== 0) {
            $shifted = array();
            foreach ($positions as $p) {
                $shifted[] = $p + $offset;
            }
            $positions = $shifted;
        }
        return $positions;
    }

    /**
     * Resolve a ref string to positions.
     * refs: top, bottom, pos21, pos321, B2, s2, w2, pos31, …
     * of: { band, label, size, ord | ord_size }
     */
    public static function resolvePositions(array $fp, $ref, $of = null) {
        if ($ref === null || $ref === '') return array();

        if (is_array($ref)) {
            // allow {ref, of} nested
            return self::resolvePositions($fp, isset($ref['ref']) ? $ref['ref'] : null, isset($ref['of']) ? $ref['of'] : $of);
        }

        $ref = (string)$ref;

        if ($ref === 'top' && !$of) return array(0);
        if ($ref === 'bottom' && !$of) return array($fp['bottom']);

        // bottom/top of a group
        if ($ref === 'group_bottom' || $ref === 'bottom_of' || ($ref === 'bottom' && $of)) {
            $g = self::resolveGroupSpec($fp, $of);
            return $g ? array($g['bottom']) : array();
        }
        if ($ref === 'group_top' || $ref === 'top_of' || ($ref === 'top' && $of)) {
            $g = self::resolveGroupSpec($fp, $of);
            return $g ? array($g['top']) : array();
        }

        // B2 / s2
        if (preg_match('/^B([1-6])$/', $ref, $m)) {
            $b = $fp['bands'][$m[1]]['B'];
            return $b ? array($b['pos']) : array();
        }
        if (preg_match('/^s([1-6])$/', $ref, $m)) {
            $b = $fp['bands'][$m[1]]['s'];
            return $b ? array($b['pos']) : array();
        }

        // w2 / w3 — filtered by of.size and of.ord_size (or of.ord = ord within size when size set)
        if (preg_match('/^w([1-6])$/', $ref, $m)) {
            $ws = isset($fp['bands'][$m[1]]['w']) ? $fp['bands'][$m[1]]['w'] : array();
            $size = ($of && isset($of['size'])) ? $of['size'] : null;
            $ordSize = ($of && isset($of['ord_size'])) ? (int)$of['ord_size'] : null;
            $ord = ($of && isset($of['ord'])) ? (int)$of['ord'] : null;
            $out = array();
            foreach ($ws as $w) {
                if ($size !== null && $w['size'] !== $size) continue;
                if ($ordSize !== null) {
                    if ((int)$w['ord_size'] !== $ordSize) continue;
                } elseif ($ord !== null && $size !== null) {
                    // legacy saved rules: ord + size means ord within that size
                    if ((int)$w['ord_size'] !== $ord) continue;
                } elseif ($ord !== null) {
                    if ((int)$w['ord'] !== $ord) continue;
                }
                $out[] = $w['pos'];
            }
            return $out;
        }

        // pos tokens: pos21, pos22, pos321, pos31, pos332…
        if (preg_match('/^pos(\d+)$/', $ref, $m)) {
            $token = 'pos' . $m[1];
            // Prefer group from $of
            if ($of) {
                $g = self::resolveGroupSpec($fp, $of);
                if ($g && isset($g['tokens'][$token])) {
                    return array($g['tokens'][$token]);
                }
                // token may be pos + labelBase + idx; if of points to group, map by index suffix
                if ($g && preg_match('/^pos(.+)(\d)$/', $token, $tm)) {
                    $idx = (int)$tm[2] - 1;
                    if (isset($g['pos'][$idx])) return array($g['pos'][$idx]);
                }
            }
            // scan all groups for this token (first match by appearance); prefer size in of
            foreach ($fp['bands'] as $band => $bd) {
                foreach ($bd['groups'] as $g) {
                    if ($of && isset($of['size']) && $g['size'] !== $of['size']) continue;
                    if ($of && isset($of['label']) && $g['label'] !== $of['label']) continue;
                    if ($of && isset($of['band']) && (string)$of['band'] !== (string)$band) continue;
                    if (isset($g['tokens'][$token])) {
                        return array($g['tokens'][$token]);
                    }
                }
            }
            return array();
        }

        // raw position
        if (preg_match('/^\d+$/', $ref)) {
            return array((int)$ref);
        }

        return array();
    }

    private static function resolveTokenValue(array $fp, $ref, $of = null) {
        if ($ref === null) return null;
        if (preg_match('/^B([1-6])$/', $ref, $m)) {
            $b = $fp['bands'][$m[1]]['B'];
            return $b ? $b['value'] : null;
        }
        if (preg_match('/^s([1-6])$/', $ref, $m)) {
            $b = $fp['bands'][$m[1]]['s'];
            return $b ? $b['value'] : null;
        }
        if (preg_match('/^w([1-6])$/', $ref, $m)) {
            $pos = self::resolvePositions($fp, $ref, $of);
            if (empty($pos)) return null;
            return $fp['prop_order'][$pos[0]];
        }
        $pos = self::resolvePositions($fp, $ref, $of);
        if (empty($pos)) return null;
        return $fp['prop_order'][$pos[0]];
    }

    /**
     * Resolve a single group from spec {band, label, size, ord, ord_size}
     */
    public static function resolveGroupSpec(array $fp, $spec) {
        if (!$spec || !is_array($spec)) return null;
        $band = isset($spec['band']) ? (string)$spec['band'] : null;
        if ($band === null) return null;
        $label = isset($spec['label']) ? $spec['label'] : null;
        $size = isset($spec['size']) ? $spec['size'] : null;
        $ord = isset($spec['ord']) ? $spec['ord'] : null;
        $ordSize = isset($spec['ord_size']) ? $spec['ord_size'] : null;
        // default: first matching (ord 1) when size set use ord_size
        if ($size !== null && $ordSize === null && $ord === null) {
            $ordSize = 1;
        } elseif ($ord === null && $ordSize === null) {
            $ord = 1;
        }
        $gs = PropFingerprint::findGroups($fp, $band, $label, $size, $ord, $ordSize);
        return !empty($gs) ? $gs[0] : null;
    }

    /**
     * Apply one rule → pick positions + marks.
     */
    public static function applyRule(array $fp, array $rule) {
        $cond = isset($rule['condition_json']) ? $rule['condition_json'] : (isset($rule['condition']) ? $rule['condition'] : array());
        if (!self::matchCondition($fp, $cond)) {
            return array('matched' => false, 'picks' => array(), 'marks' => array());
        }
        $pick = isset($rule['pick_json']) ? $rule['pick_json'] : (isset($rule['pick']) ? $rule['pick'] : array());
        $picks = self::resolvePicks($fp, $pick);
        $w = isset($rule['mark_weight']) ? (float)$rule['mark_weight'] : 1.0;
        $marks = array();
        foreach ($picks as $p) {
            $marks[$p] = $w;
        }
        return array('matched' => true, 'picks' => $picks, 'marks' => $marks);
    }

    /**
     * Feed a prop string (or int array) + list of rules → per-position mark totals + which rules hit.
     * Standalone final output for manual rules.
     *
     * @param string|int[] $propString e.g. "59,40,33,28,28,24"
     * @param array $rules rows with condition_json, pick_json, mark_weight, code, id
     * @param string $venueBucket optional ST|HV|Sx for venue clauses
     */
    public static function evaluateString($propString, array $rules, $venueBucket = 'Sx') {
        if (is_string($propString)) {
            $parts = preg_split('/[,\s]+/', trim($propString));
            $props = array();
            foreach ($parts as $p) {
                if ($p === '') continue;
                $props[] = (int)$p;
            }
        } else {
            $props = array_map('intval', $propString);
        }
        $fp = PropFingerprint::buildFromProps($props);
        $fp['venue_bucket'] = $venueBucket;

        $markTotal = array_fill(0, count($props), 0.0);
        $hitRules = array();
        $pickMap = array(); // pos => [rule codes]

        foreach ($rules as $rule) {
            if (isset($rule['enabled']) && !(int)$rule['enabled']) continue;
            $r = self::applyRule($fp, $rule);
            if (!$r['matched']) continue;
            $code = isset($rule['code']) ? $rule['code'] : ('#' . (isset($rule['id']) ? $rule['id'] : '?'));
            $hitRules[] = $code;
            foreach ($r['marks'] as $pos => $w) {
                $markTotal[$pos] += $w;
                if (!isset($pickMap[$pos])) $pickMap[$pos] = array();
                $pickMap[$pos][] = $code;
            }
        }

        $ranked = array();
        for ($i = 0; $i < count($props); $i++) {
            $ranked[] = array(
                'pos'    => $i,
                'prop'   => $props[$i],
                'marks'  => $markTotal[$i],
                'rules'  => isset($pickMap[$i]) ? $pickMap[$i] : array(),
            );
        }
        usort($ranked, function ($a, $b) {
            if ($a['marks'] === $b['marks']) return $a['pos'] - $b['pos'];
            return ($a['marks'] < $b['marks']) ? 1 : -1;
        });

        return array(
            'prop_string'   => implode(',', $props),
            'fingerprint'   => $fp,
            'marks'         => $markTotal,
            'hit_rules'     => $hitRules,
            'hit_rule_count'=> count($hitRules),
            'ranked'        => $ranked,
            'top_picks'     => array_values(array_filter($ranked, function ($x) {
                return $x['marks'] > 0;
            })),
        );
    }

    /**
     * Live pick-3 for race cards: map manual marks → horseno (same shape as finalpick3onlyrules).
     *
     * @param int[] $propOrder prop string values (top→bottom)
     * @param array $horseNos parallel horseno list (same index as propOrder)
     * @param array $rules enabled manual rules
     * @param string $venueBucket
     * @param int $topN
     */
    public static function finalPick3($propOrder, $horseNos, array $rules, $venueBucket = 'Sx', $topN = 3) {
        $eval = self::evaluateString($propOrder, $rules, $venueBucket);
        $picks = array();
        $n = 0;
        $ranked = isset($eval['ranked']) ? $eval['ranked'] : array();
        foreach ($ranked as $row) {
            if (!isset($row['marks']) || (float)$row['marks'] <= 0) continue;
            if ($n >= (int)$topN) break;
            $pos = (int)$row['pos'];
            $picks[] = array(
                'horseno'   => isset($horseNos[$pos]) ? $horseNos[$pos] : ($pos + 1),
                'position'  => $pos,
                'prop_val'  => isset($row['prop']) ? $row['prop'] : 0,
                'marks'     => $row['marks'],
                'rules'     => isset($row['rules']) ? $row['rules'] : array(),
                'pick_rank' => $n + 1,
            );
            $n++;
        }
        return $picks;
    }

    /**
     * Load enabled manual rules from DB (optional venue scope filter).
     */
    public static function loadEnabledRules(PDO $db, $venueScope = null) {
        if (!self::_tableExists($db, 'prop_manual_rule')) return array();
        $sql = "SELECT * FROM prop_manual_rule WHERE enabled = 1";
        $params = array();
        if ($venueScope && $venueScope !== 'ALL') {
            $sql .= " AND (venue_scope = ? OR venue_scope = 'ALL')";
            $params[] = $venueScope;
        }
        $sql .= " ORDER BY priority ASC, id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $out = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if (is_string($r['condition_json'])) {
                $r['condition_json'] = json_decode($r['condition_json'], true);
            }
            if (is_string($r['pick_json'])) {
                $r['pick_json'] = json_decode($r['pick_json'], true);
            }
            $r['enabled'] = (int)$r['enabled'];
            $r['mark_weight'] = isset($r['mark_weight']) ? (float)$r['mark_weight'] : 1.0;
            $out[] = $r;
        }
        return $out;
    }

    private static function _tableExists(PDO $db, $name) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute(array($name));
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Backtest one rule over fingerprint rows (+ fps in cells).
     * @param int $fpMax place threshold (3 or 4)
     */
    public static function backtestRule(array $rule, array $fingerprints, $fpMax = 4) {
        $matched = 0;
        $picksTotal = 0;
        $picksPlaced = 0;
        $raceHit = 0;
        $details = array();

        foreach ($fingerprints as $fp) {
            // venue scope
            $scope = isset($rule['venue_scope']) ? $rule['venue_scope'] : 'ALL';
            if ($scope !== 'ALL') {
                $vb = isset($fp['venue_bucket']) ? $fp['venue_bucket'] : PropFingerprint::venueBucket(isset($fp['venue']) ? $fp['venue'] : '');
                if ($vb !== $scope) continue;
            }
            $r = self::applyRule($fp, $rule);
            if (!$r['matched']) continue;
            $matched++;
            $placedThis = 0;
            $pickDetail = array();
            foreach ($r['picks'] as $pos) {
                $picksTotal++;
                $fpVal = null;
                if (isset($fp['cells'][$pos]['fp'])) $fpVal = $fp['cells'][$pos]['fp'];
                elseif (isset($fp['fps'][$pos])) $fpVal = $fp['fps'][$pos];
                $isPlaced = ($fpVal !== null && (int)$fpVal > 0 && (int)$fpVal <= (int)$fpMax);
                if ($isPlaced) {
                    $picksPlaced++;
                    $placedThis++;
                }
                $pickDetail[] = array(
                    'pos' => $pos,
                    'prop'=> $fp['prop_order'][$pos],
                    'fp'  => $fpVal,
                    'hit' => $isPlaced,
                );
            }
            if ($placedThis > 0) $raceHit++;
            $details[] = array(
                'racingdate' => isset($fp['racingdate']) ? $fp['racingdate'] : null,
                'venue'      => isset($fp['venue']) ? $fp['venue'] : null,
                'raceno'     => isset($fp['raceno']) ? $fp['raceno'] : null,
                'prop_string'=> isset($fp['prop_string']) ? $fp['prop_string'] : null,
                'cells'      => isset($fp['cells']) ? $fp['cells'] : array(),
                'picks'      => $pickDetail,
                'race_hit'   => $placedThis > 0,
                'no_picks'   => empty($r['picks']),
            );
        }

        $hitRate = $matched > 0 ? round($raceHit / $matched, 4) : 0;
        $pickHit = $picksTotal > 0 ? round($picksPlaced / $picksTotal, 4) : 0;

        return array(
            'races_matched' => $matched,
            'picks_total'   => $picksTotal,
            'picks_placed'  => $picksPlaced,
            'race_hit'      => $raceHit,
            'hit_rate'      => $hitRate,
            'pick_hit_rate' => $pickHit,
            'fp_max'        => (int)$fpMax,
            'details'       => $details,
        );
    }

    /**
     * Propose pick refs from FP≤fpMax cells across checked fingerprints (common tokens).
     *
     * Threshold: ≥34% of evidence races (min 2 when n≥3), else fall back to top votes
     * so mixed evidence still yields usable marks instead of {"marks":[]}.
     */
    public static function proposePicksFromEvidence(array $fingerprints, $fpMax = 4) {
        $tokenVotes = array();
        $racesWithFp = 0;
        foreach ($fingerprints as $fp) {
            $seen = array();
            $hadFp = false;
            if (!isset($fp['cells']) || !is_array($fp['cells'])) continue;
            foreach ($fp['cells'] as $cell) {
                if (!isset($cell['fp']) || $cell['fp'] === null || $cell['fp'] === '') continue;
                if ((int)$cell['fp'] <= 0 || (int)$cell['fp'] > (int)$fpMax) continue;
                $hadFp = true;
                $pos = (int)$cell['pos'];
                $v = (int)$cell['prop'];
                $band = isset($cell['band']) ? $cell['band'] : null;
                if ($band === null || $band === '') continue;
                $band = (string)$band;
                if (!isset($fp['bands'][$band])) continue;
                $bd = $fp['bands'][$band];

                // group tokens (2x / 32x / …)
                if (!empty($bd['groups'])) {
                    foreach ($bd['groups'] as $g) {
                        if ((int)$g['value'] !== $v) continue;
                        if (empty($g['tokens']) || !is_array($g['tokens'])) continue;
                        foreach ($g['tokens'] as $tok => $tpos) {
                            if ((int)$tpos !== $pos) continue;
                            $ordSize = isset($g['ord_size']) ? (int)$g['ord_size'] : (isset($g['ord']) ? (int)$g['ord'] : 1);
                            // vote key ignores ord so same role across races stacks
                            $key = $tok . '|' . $g['label'] . '|' . $g['size'] . '|' . $band;
                            $seen[$key] = array(
                                'ref' => $tok,
                                'of'  => array(
                                    'band' => $band,
                                    'label'=> $g['label'],
                                    'size' => $g['size'],
                                    'ord_size' => $ordSize,
                                ),
                            );
                        }
                    }
                }

                // singleton w{band}
                if ((int)$cell['count'] === 1 && !empty($bd['w'])) {
                    foreach ($bd['w'] as $w) {
                        if ((int)$w['pos'] !== $pos) continue;
                        $ordSize = isset($w['ord_size']) ? (int)$w['ord_size'] : (isset($w['ord']) ? (int)$w['ord'] : 1);
                        $key = 'w' . $band . '|' . $w['size'] . '|os' . $ordSize;
                        $seen[$key] = array(
                            'ref' => 'w' . $band,
                            'of'  => array(
                                'band' => $band,
                                'size' => $w['size'],
                                'ord_size' => $ordSize,
                            ),
                        );
                    }
                }
            }
            if ($hadFp) $racesWithFp++;
            foreach ($seen as $k => $spec) {
                if (!isset($tokenVotes[$k])) {
                    $tokenVotes[$k] = array('count' => 0, 'mark' => $spec);
                }
                $tokenVotes[$k]['count']++;
            }
        }
        uasort($tokenVotes, function ($a, $b) {
            if ($a['count'] === $b['count']) return 0;
            return ($a['count'] > $b['count']) ? -1 : 1;
        });

        $n = count($fingerprints);
        // Prefer tokens in ~1/3+ of checked races; min 2 when you have a few rows
        if ($n <= 1) {
            $need = 1;
        } elseif ($n === 2) {
            $need = 1;
        } else {
            $need = max(2, (int)ceil($n * 0.34));
        }

        $marks = array();
        foreach ($tokenVotes as $tv) {
            if ($tv['count'] >= $need) {
                $marks[] = $tv['mark'];
            }
            if (count($marks) >= 8) break;
        }

        $usedFallback = false;
        // Fallback: still return top tokens so Propose never silently yields []
        // when evidence has FP hits but patterns are mixed.
        if (empty($marks) && !empty($tokenVotes)) {
            $usedFallback = true;
            $minFallback = ($n >= 4) ? 2 : 1;
            foreach ($tokenVotes as $tv) {
                if ($tv['count'] < $minFallback) continue;
                $marks[] = $tv['mark'];
                if (count($marks) >= 5) break;
            }
        }

        $voteSummary = array();
        $i = 0;
        foreach ($tokenVotes as $k => $tv) {
            if ($i++ >= 12) break;
            $voteSummary[] = array(
                'key' => $k,
                'count' => $tv['count'],
                'mark' => $tv['mark'],
            );
        }

        return array(
            'marks' => $marks,
            'votes' => $tokenVotes,
            'vote_summary' => $voteSummary,
            'need' => $need,
            'evidence_n' => $n,
            'races_with_fp' => $racesWithFp,
            'used_fallback' => $usedFallback,
        );
    }

    /** Curated symptom atoms used by the Miner (same AST as Builder chips). */
    public static function symptomCatalog() {
        return array(
            array('name' => 'has 2x', 'clause' => array('op' => 'has_label', 'band' => '2', 'label' => '2x')),
            array('name' => 'has 32x', 'clause' => array('op' => 'has_label', 'band' => '2', 'label' => '32x')),
            array('name' => 'has 3x', 'clause' => array('op' => 'has_label', 'band' => '3', 'label' => '3x')),
            array('name' => 'has 33x', 'clause' => array('op' => 'has_label', 'band' => '3', 'label' => '33x')),
            array('name' => 'has 4x', 'clause' => array('op' => 'has_label', 'band' => '4', 'label' => '4x')),
            array('name' => 'has 34x', 'clause' => array('op' => 'has_label', 'band' => '4', 'label' => '34x')),
            array('name' => 'has 5x', 'clause' => array('op' => 'has_label', 'band' => '5', 'label' => '5x')),
            array('name' => 'has 6x', 'clause' => array('op' => 'has_label', 'band' => '6', 'label' => '6x')),
            array('name' => 'c2x=2', 'clause' => array('op' => 'c2x', 'band' => '2', 'eq' => 2)),
            array('name' => 'c32x≥2', 'clause' => array('op' => 'c_label', 'band' => '2', 'label' => '32x', 'gte' => 2)),
            array('name' => 'c3x=1', 'clause' => array('op' => 'c_label', 'band' => '3', 'label' => '3x', 'eq' => 1)),
            array('name' => 'c3x≥2', 'clause' => array('op' => 'c_label', 'band' => '3', 'label' => '3x', 'gte' => 2)),
            array('name' => 'only 32x', 'clause' => array('op' => 'only_label', 'label' => '32x')),
            array('name' => 'only 33x', 'clause' => array('op' => 'only_label', 'label' => '33x')),
            array('name' => 'small 3x', 'clause' => array('op' => 'has_label', 'band' => '3', 'label' => '3x', 'size' => 'small')),
            array('name' => '1 small 3x', 'clause' => array('op' => 'c_label', 'band' => '3', 'label' => '3x', 'size' => 'small', 'eq' => 1)),
            array('name' => 'big 3x', 'clause' => array('op' => 'has_label', 'band' => '3', 'label' => '3x', 'size' => 'big')),
            array('name' => 'only 1 w5', 'clause' => array('op' => 'c_w', 'band' => '5', 'eq' => 1)),
            array('name' => 'w5≥2', 'clause' => array('op' => 'c_w', 'band' => '5', 'gte' => 2)),
            array('name' => 'w5 not top', 'clause' => array('op' => 'not_top', 'ref' => 'w5')),
            array('name' => 'w6 not top', 'clause' => array('op' => 'not_top', 'ref' => 'w6')),
            array('name' => 'B2 not top', 'clause' => array('op' => 'not_top', 'ref' => 'B2')),
            array('name' => 'w2≠B2', 'clause' => array('op' => 'neq_token', 'a' => 'w2', 'b' => 'B2')),
            array('name' => 'w3≠B3', 'clause' => array('op' => 'neq_token', 'a' => 'w3', 'b' => 'B3')),
            array('name' => 'pos32 adj w2', 'clause' => array(
                'op' => 'adjacent', 'from' => 'pos32', 'to' => 'w2',
                'of' => array('band' => '3', 'label' => '3x', 'size' => 'small'),
            )),
            array('name' => 'pos21 adj w3', 'clause' => array(
                'op' => 'adjacent', 'from' => 'pos21', 'to' => 'w3',
                'of' => array('band' => '2', 'label' => '2x'),
            )),
            array('name' => 'small 3x in big 3x', 'clause' => array(
                'op' => 'nested_in',
                'inner' => array('band' => '3', 'label' => '3x', 'size' => 'small'),
                'outer' => array('band' => '3', 'label' => '3x', 'size' => 'big'),
            )),
        );
    }

    /** Combinations C(n,k) as index lists. */
    public static function indexCombos($n, $k) {
        $out = array();
        if ($k <= 0 || $k > $n) return $out;
        $idx = range(0, $k - 1);
        while (true) {
            $out[] = $idx;
            $i = $k - 1;
            while ($i >= 0 && $idx[$i] === $n - $k + $i) $i--;
            if ($i < 0) break;
            $idx[$i]++;
            for ($j = $i + 1; $j < $k; $j++) {
                $idx[$j] = $idx[$j - 1] + 1;
            }
        }
        return $out;
    }

    public static function comb($n, $k) {
        $n = (int)$n;
        $k = (int)$k;
        if ($k < 0 || $k > $n) return 0.0;
        if ($k === 0 || $k === $n) return 1.0;
        $k = min($k, $n - $k);
        $r = 1.0;
        for ($i = 1; $i <= $k; $i++) {
            $r = $r * ($n - $k + $i) / $i;
        }
        return $r;
    }

    /**
     * Random race-hit rate: k picks, fpMax places in field of n.
     * P(≥1 place) = 1 − C(n−fpMax, k) / C(n, k)
     */
    public static function baselineRaceHit($nHorses, $kPicks, $fpMax) {
        $n = max(1, (int)$nHorses);
        $k = max(0, (int)$kPicks);
        $p = max(1, (int)$fpMax);
        if ($k <= 0) return 0.0;
        if ($k >= $n) return 1.0;
        if ($p >= $n) return 1.0;
        $non = $n - $p;
        if ($k > $non) return 1.0;
        $den = self::comb($n, $k);
        if ($den <= 0) return 0.0;
        $miss = self::comb($non, $k) / $den;
        $hit = 1.0 - $miss;
        if ($hit < 0) $hit = 0.0;
        if ($hit > 1) $hit = 1.0;
        return $hit;
    }

    public static function baselinePickHit($nHorses, $fpMax) {
        $n = max(1, (int)$nHorses);
        return min(1.0, (float)$fpMax / $n);
    }

    /**
     * Mine condition × auto-pick candidates ranked by lift under pick budget.
     *
     * @param array $fingerprints venue-filtered fingerprint rows
     * @param array $opts venue_bucket, fp_max=4, max_picks=3, min_matched=20,
     *                    max_depth=2, max_candidates=400, holdout_frac=0
     */
    public static function mineRules(array $fingerprints, array $opts = array()) {
        $venue = isset($opts['venue_bucket']) ? $opts['venue_bucket'] : 'Sx';
        $fpMax = isset($opts['fp_max']) ? (int)$opts['fp_max'] : 4;
        $maxPicks = isset($opts['max_picks']) ? (int)$opts['max_picks'] : 3;
        $minMatched = isset($opts['min_matched']) ? (int)$opts['min_matched'] : 20;
        $maxDepth = isset($opts['max_depth']) ? (int)$opts['max_depth'] : 2;
        $maxCandidates = isset($opts['max_candidates']) ? (int)$opts['max_candidates'] : 400;
        $holdoutFrac = isset($opts['holdout_frac']) ? (float)$opts['holdout_frac'] : 0.0;
        if ($maxPicks < 1) $maxPicks = 3;
        if ($maxDepth < 1) $maxDepth = 1;
        if ($maxDepth > 3) $maxDepth = 3;

        // Optional holdout: train propose on older, score on newer
        $trainFps = $fingerprints;
        $testFps = $fingerprints;
        if ($holdoutFrac > 0.05 && $holdoutFrac < 0.6 && count($fingerprints) >= 40) {
            $sorted = $fingerprints;
            usort($sorted, function ($a, $b) {
                $da = isset($a['racingdate']) ? $a['racingdate'] : '';
                $db = isset($b['racingdate']) ? $b['racingdate'] : '';
                if ($da === $db) return 0;
                return ($da < $db) ? -1 : 1;
            });
            $cut = (int)floor(count($sorted) * (1.0 - $holdoutFrac));
            if ($cut < 10) $cut = 10;
            $trainFps = array_slice($sorted, 0, $cut);
            $testFps = array_slice($sorted, $cut);
        }

        $catalog = self::symptomCatalog();
        $nCat = count($catalog);
        $candidates = array();
        $tried = 0;
        $skippedMatched = 0;
        $skippedEmpty = 0;

        for ($depth = 1; $depth <= $maxDepth; $depth++) {
            $combos = self::indexCombos($nCat, $depth);
            foreach ($combos as $idxs) {
                if (count($candidates) >= $maxCandidates) break 2;
                $tried++;
                $names = array();
                $clauses = array();
                if ($venue && $venue !== 'ALL') {
                    $clauses[] = array('venue_bucket' => $venue);
                }
                foreach ($idxs as $ix) {
                    $names[] = $catalog[$ix]['name'];
                    $clauses[] = $catalog[$ix]['clause'];
                }
                $cond = array('all' => $clauses);
                $label = implode(' + ', $names);

                $matchedTrain = array();
                foreach ($trainFps as $fp) {
                    if (self::matchCondition($fp, $cond)) $matchedTrain[] = $fp;
                }
                if (count($matchedTrain) < $minMatched) {
                    $skippedMatched++;
                    continue;
                }

                $prop = self::proposePicksFromEvidence($matchedTrain, $fpMax);
                $marks = isset($prop['marks']) ? $prop['marks'] : array();
                if (empty($marks)) {
                    $skippedEmpty++;
                    continue;
                }
                if (count($marks) > $maxPicks) {
                    $marks = array_slice($marks, 0, $maxPicks);
                }
                $pick = array('marks' => $marks);

                $rule = array(
                    'code' => 'MINE',
                    'name' => $label,
                    'venue_scope' => ($venue && $venue !== 'ALL') ? $venue : 'ALL',
                    'condition_json' => $cond,
                    'pick_json' => $pick,
                    'enabled' => 1,
                    'mark_weight' => 1,
                );

                $bt = self::backtestRule($rule, $testFps, $fpMax);
                $matched = (int)$bt['races_matched'];
                if ($matched < $minMatched) {
                    $skippedMatched++;
                    continue;
                }
                $picksTotal = (int)$bt['picks_total'];
                $avgPicks = $matched > 0 ? round($picksTotal / $matched, 3) : 0;
                // Soft cap: allow tiny overshoot from multi-resolve, hard reject if > max+0.35
                if ($avgPicks > $maxPicks + 0.35) continue;

                $avgN = 0;
                $nAvg = 0;
                foreach ($testFps as $fp) {
                    if (!self::matchCondition($fp, $cond)) continue;
                    $avgN += isset($fp['n_horses']) ? (int)$fp['n_horses'] : count(isset($fp['prop_order']) ? $fp['prop_order'] : array());
                    $nAvg++;
                }
                $avgN = $nAvg > 0 ? ($avgN / $nAvg) : 14;

                $kEff = max(1, (int)round($avgPicks));
                if ($kEff > $maxPicks) $kEff = $maxPicks;
                $baseRace = self::baselineRaceHit($avgN, $kEff, $fpMax);
                $basePick = self::baselinePickHit($avgN, $fpMax);
                $raceHit = (float)$bt['hit_rate'];
                $pickHit = (float)$bt['pick_hit_rate'];
                $raceLift = ($baseRace > 0.01) ? round($raceHit / $baseRace, 3) : 0;
                $pickLift = ($basePick > 0.01) ? round($pickHit / $basePick, 3) : 0;

                // Require some edge vs random at same budget
                if ($raceLift < 1.05 && $pickLift < 1.1) continue;

                $score = round(
                    ($raceLift * 0.55 + $pickLift * 0.45)
                    * log(max(2, $matched))
                    / max(1.0, $avgPicks),
                    4
                );

                $candidates[] = array(
                    'name' => $label,
                    'symptoms' => $names,
                    'condition_json' => $cond,
                    'pick_json' => $pick,
                    'races_matched' => $matched,
                    'picks_total' => $picksTotal,
                    'picks_placed' => (int)$bt['picks_placed'],
                    'avg_picks' => $avgPicks,
                    'pick_hit_rate' => $pickHit,
                    'hit_rate' => $raceHit,
                    'baseline_race' => round($baseRace, 4),
                    'baseline_pick' => round($basePick, 4),
                    'race_lift' => $raceLift,
                    'pick_lift' => $pickLift,
                    'score' => $score,
                    'avg_field' => round($avgN, 1),
                    'marks_n' => count($marks),
                );
            }
        }

        usort($candidates, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                if ($a['race_lift'] === $b['race_lift']) return 0;
                return ($a['race_lift'] > $b['race_lift']) ? -1 : 1;
            }
            return ($a['score'] > $b['score']) ? -1 : 1;
        });

        return array(
            'candidates' => $candidates,
            'tried' => $tried,
            'kept' => count($candidates),
            'skipped_matched' => $skippedMatched,
            'skipped_empty_picks' => $skippedEmpty,
            'opts' => array(
                'venue_bucket' => $venue,
                'fp_max' => $fpMax,
                'max_picks' => $maxPicks,
                'min_matched' => $minMatched,
                'max_depth' => $maxDepth,
                'holdout_frac' => $holdoutFrac,
                'train_n' => count($trainFps),
                'test_n' => count($testFps),
            ),
        );
    }
}
