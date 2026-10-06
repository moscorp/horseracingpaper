<?php
/**
 * PropPrediction — Rule mining + scoring + symptom race-rules.
 *
 * P0–P2 fixes:
 *  - Compound AND rules match at predict time
 *  - Boolean polarity stored as field=true / field=false
 *  - Race-level-only rules skipped for ranking
 *  - Holdout training via dateBefore
 *  - Symptom rules a–g as raceRules
 *  - finalpick3 uses prop_val / original position
 */
class PropPrediction {

    /** 0 = keep every rule that passes minLift (no cap). */
    const TOP_N_RULES = 0;
    const DEFAULT_MIN_LIFT = 1.15;

    /** Columns identical for every horse in a race — no ranking value alone. */
    private static $RACE_LEVEL_COLS = array(
        'race_has_over50', 'race_has18', 'race_has19', 'race_hasMid40',
        'race_any18Bot', 'race_any19Bot', 'B2_lt_B3', 'B3_lt_B2',
        'race_d33x_count', 'race_d32x_count', 'race_d3x_count',
        'race_total33x', 'race_total3x', 'race_count40_49', 'race_count50plus',
        'race_unique_props', 'race_nhorses', 'race_prop_spread',
        'race_B2', 'race_B3', 'race_B4', 'race_maxVal',
    );

    private $trainFeatures = [];
    private $isTrained = false;
    private $minedRules = [];
    private $customRules = [];
    private $raceRules = [];
    private $baselinePlaced = 0;
    private $_tempCounts = array();
    private $_tempTotal = 0;
    private $_tempPlaced = 0;
    private $trainVenue = null;
    private $trainHorseCount = 0;
    private $trainRaceCount = 0;

    public function train(array $features, $topN = null, $minLift = null) {
        if ($topN === null) $topN = self::TOP_N_RULES;
        if ($minLift === null) $minLift = self::DEFAULT_MIN_LIFT;
        $this->beginTrain();
        foreach ($features as $f) {
            $this->_tempTotal++;
            if (!empty($f['placed'])) $this->_tempPlaced++;
            $this->_countFeatures($f);
        }
        return $this->finalizeTrain($topN, $minLift);
    }

    public function beginTrain() {
        $this->_tempCounts = array();
        $this->_tempTotal  = 0;
        $this->_tempPlaced = 0;
    }

    public function addChunk(array $features) {
        foreach ($features as $f) {
            $this->_tempTotal++;
            if (!empty($f['placed'])) $this->_tempPlaced++;
            $this->_countFeatures($f);
        }
    }

    public function finalizeTrain($topN = null, $minLift = null) {
        if ($topN === null) $topN = self::TOP_N_RULES;
        if ($minLift === null) $minLift = self::DEFAULT_MIN_LIFT;
        $this->baselinePlaced = round($this->_tempPlaced / max(1, $this->_tempTotal), 4);
        $rules = $this->_trainRulesFromCounts($minLift);

        usort($rules, function($a, $b) {
            $sa = isset($a['score']) ? $a['score'] : 0;
            $sb = isset($b['score']) ? $b['score'] : 0;
            return ($sb > $sa) ? 1 : (($sb < $sa) ? -1 : 0);
        });
        // topN <= 0 ⇒ unlimited (keep all rules that passed minLift)
        $this->minedRules = ($topN > 0) ? array_slice($rules, 0, $topN) : $rules;
        $this->isTrained = true;
        return $this->minedRules;
    }

    private function _countFeatures($f) {
        static $boolFields = null;
        if ($boolFields === null) {
            $boolFields = array(
                'is_top','is_top2','is_top3','is_bottom',
                'is_32x','is_33x','is_3x','is_2x','is_1x','is_4x','is_34x',
                'is_B2','is_B3','is_B4','is_lonely','is_dup',
                'race_has_over50','race_has18','race_has19',
                'race_hasMid40','race_any18Bot','race_any19Bot',
                'B2_lt_B3','B3_lt_B2','has_sequential_33x','has_sequential_32x',
                'has_sequential_2x','has_sequential_3x',
                'is_sequential_head','is_sequential_tail',
                'is_plateau_start','is_plateau_mid','is_plateau_end',
                'abrupt_drop','abrupt_rise',
                'group_leader','group_tail','group_mid','in_group',
                'is_peak','is_valley',
                'prev_gt','next_gt',
            );
        }

        $cats = array(
            'prop_range'        => array('50+','40-49','30-39','20-29','10-19'),
            'race_d33x_count'   => array(0,1,2,3),
            'race_d32x_count'   => array(0,1,2),
            'race_d3x_count'    => array(0,1,2),
            'race_total33x'     => array(0,1,2,3),
            'race_total3x'      => array(0,1,2,3),
            'race_count40_49'   => array(0,1,2,3),
            'race_count50plus'  => array(0,1,2,3),
            'group_size'        => array(1,2,3,4),
            'race_unique_props' => array(9,10,11,12,13,14),
        );

        foreach ($boolFields as $fname) {
            $keyT = $fname.'__true';
            $keyF = $fname.'__false';
            if (!isset($this->_tempCounts[$keyT])) { $this->_tempCounts[$keyT] = array('tot'=>0,'hit'=>0); }
            if (!isset($this->_tempCounts[$keyF])) { $this->_tempCounts[$keyF] = array('tot'=>0,'hit'=>0); }
            if (!empty($f[$fname])) {
                $this->_tempCounts[$keyT]['tot']++;
                if (!empty($f['placed'])) $this->_tempCounts[$keyT]['hit']++;
            } else {
                $this->_tempCounts[$keyF]['tot']++;
                if (!empty($f['placed'])) $this->_tempCounts[$keyF]['hit']++;
            }
        }

        foreach ($cats as $fname => $vals) {
            foreach ($vals as $v) {
                $keyH = $fname . '_' . (string)$v;
                if (!isset($this->_tempCounts[$keyH])) { $this->_tempCounts[$keyH] = array('tot'=>0,'hit'=>0); }
                $match = isset($f[$fname]) && (string)$f[$fname] === (string)$v;
                if ($match) {
                    $this->_tempCounts[$keyH]['tot']++;
                    if (!empty($f['placed'])) $this->_tempCounts[$keyH]['hit']++;
                }
            }
        }

        if (isset($f['label']) && $f['label'] !== null && $f['label'] !== '') {
            $keyH = 'label_' . $f['label'];
            if (!isset($this->_tempCounts[$keyH])) { $this->_tempCounts[$keyH] = array('tot'=>0,'hit'=>0); }
            $this->_tempCounts[$keyH]['tot']++;
            if (!empty($f['placed'])) $this->_tempCounts[$keyH]['hit']++;
        }

        // Auto pattern×role compounds — discovered from data, not hardcoded WHERE
        $compounds = $this->_compoundDefs();
        foreach ($compounds as $idx => $conds) {
            $ok = true;
            foreach ($conds as $filter) {
                $field  = $filter[0];
                $expect = $filter[1];
                if (!isset($f[$field]) || $f[$field] != $expect) { $ok = false; break; }
            }
            $keyName = 'cmpd_' . $idx;
            if (!isset($this->_tempCounts[$keyName])) { $this->_tempCounts[$keyName] = array('tot'=>0,'hit'=>0); }
            if (!isset($this->_tempCounts[$keyName.'__other'])) { $this->_tempCounts[$keyName.'__other'] = array('tot'=>0,'hit'=>0); }
            if ($ok) {
                $this->_tempCounts[$keyName]['tot']++;
                if (!empty($f['placed'])) $this->_tempCounts[$keyName]['hit']++;
            } else {
                $this->_tempCounts[$keyName.'__other']['tot']++;
                if (!empty($f['placed'])) $this->_tempCounts[$keyName.'__other']['hit']++;
            }
        }
    }

    /**
     * Auto-build pattern × structural-role compounds for every race horse.
     * This is how training *investigates* symptoms like "33x + mid → place"
     * instead of hardcoding WHERE clauses.
     *
     * Pattern side  = what the prop band is (33x, 32x, B3, …)
     * Role side     = where in that structure (mid / leader / tail / top / …)
     */
    private function _compoundDefs() {
        static $cached = null;
        if ($cached !== null) return $cached;

        $patterns = array(
            'is_33x', 'is_32x', 'is_3x', 'is_2x', 'is_1x', 'is_4x', 'is_34x',
            'is_B2', 'is_B3', 'is_B4',
            'has_sequential_33x', 'has_sequential_32x',
            'abrupt_drop', 'abrupt_rise', 'is_lonely',
        );
        $roles = array(
            'group_mid', 'group_leader', 'group_tail',
            'is_top', 'is_top2', 'is_top3', 'is_bottom',
            'is_sequential_head', 'is_sequential_tail',
            'is_plateau_start', 'is_plateau_mid', 'is_plateau_end',
        );

        $list = array();
        // Every pattern × role pair (true AND true)
        foreach ($patterns as $p) {
            foreach ($roles as $r) {
                if ($p === $r) continue;
                $list[] = array(array($p, true), array($r, true));
            }
        }

        // Pattern × role × light race context (only a few high-value contexts)
        $contexts = array(
            array('race_has_over50', false),
            array('race_d3x_count', 1),
            array('race_d33x_count', 1),
            array('race_any18Bot', true),
            array('race_any19Bot', true),
            array('B2_lt_B3', true),
        );
        $focusPatterns = array('is_33x', 'is_32x', 'is_3x', 'is_B2', 'is_B3', 'is_B4', 'is_top');
        $focusRoles = array('group_mid', 'group_leader', 'group_tail', 'is_top');
        foreach ($focusPatterns as $p) {
            foreach ($focusRoles as $r) {
                if ($p === $r) continue;
                foreach ($contexts as $ctx) {
                    $list[] = array(array($p, true), array($r, true), $ctx);
                }
            }
        }

        // A few classic triples kept for continuity
        $list[] = array(array('is_top',true),array('race_count50plus',0),array('race_has_over50',false));
        $list[] = array(array('is_B2',true),array('race_has18',true),array('race_any18Bot',true));
        $list[] = array(array('is_B3',true),array('race_has19',true),array('race_any19Bot',true));
        $list[] = array(array('group_leader',true),array('is_dup',true),array('group_size',3));

        $cached = $list;
        return $cached;
    }

    private function _compoundTag($conds) {
        $tag = array();
        foreach ($conds as $filter) {
            $field  = $filter[0];
            $expect = $filter[1];
            if ($expect === true) $tag[] = $field . '=true';
            elseif ($expect === false) $tag[] = $field . '=false';
            else $tag[] = $field . '=' . $expect;
        }
        return implode(' AND ', $tag);
    }

    private function _addRule(&$list, $name, $cond, $other, $minLift) {
        if ($cond['tot'] < 3) return;
        // Drop noisy "almost everyone" negation rules (e.g. is_1x=false on 95% of field)
        $total = max(1, $this->_tempTotal);
        if (substr($name, -6) === '=false' && ($cond['tot'] / $total) > 0.70) {
            return;
        }
        // Prefer informative support: skip tiny accidental compounds unless strong lift
        $probTru = $cond['tot'] > 0 ? ($cond['hit'] / $cond['tot']) : 0;
        $probOth = $other['tot'] > 0 ? ($other['hit'] / $other['tot']) : $this->baselinePlaced;
        $lift = $probOth > 0 ? ($probTru / $probOth) : ($probTru > 0 ? 9.99 : 0);
        if ($lift < $minLift) return;
        if ($cond['tot'] < 8 && $lift < ($minLift + 0.3)) return;
        $list[] = array(
            'rule_name'  => $name,
            'support'    => $cond['tot'],
            'hit'        => $cond['hit'],
            'miss'       => $cond['tot'] - $cond['hit'],
            'accuracy'   => round($probTru * 100, 1),
            'baseline'   => round($this->baselinePlaced * 100, 1),
            'lift'       => round($lift, 2),
            'score'      => round($lift * log(max($cond['tot'],1), 10), 3),
            'race_level' => $this->_isRaceLevelOnly($name) ? 1 : 0,
        );
    }

    private function _trainRulesFromCounts($minLift) {
        $rules = array();
        $seenBool = array();

        foreach ($this->_tempCounts as $key => $count) {
            if (strpos($key, '__other') !== false) continue;

            if (substr($key, -6) === '__true') {
                $bn = substr($key, 0, -6);
                $oppKey = $bn . '__false';
                $other = isset($this->_tempCounts[$oppKey]) ? $this->_tempCounts[$oppKey] : array('tot'=>0,'hit'=>0);
                $this->_addRule($rules, $bn . '=true', $count, $other, $minLift);
                $seenBool[$bn] = true;
            } elseif (substr($key, -7) === '__false') {
                $bn = substr($key, 0, -7);
                $oppKey = $bn . '__true';
                $other = isset($this->_tempCounts[$oppKey]) ? $this->_tempCounts[$oppKey] : array('tot'=>0,'hit'=>0);
                $this->_addRule($rules, $bn . '=false', $count, $other, $minLift);
                $seenBool[$bn] = true;
            } elseif (strpos($key, 'prop_range_') === 0) {
                $val = substr($key, 11);
                $this->_addRule($rules, 'prop_range = ' . $val, $count, array('tot'=>0,'hit'=>0), $minLift);
            } elseif (strpos($key, 'race_d33x_count_') === 0) {
                $val = substr($key, 16);
                $this->_addRule($rules, 'race_d33x_count = ' . $val, $count, array('tot'=>0,'hit'=>0), $minLift);
            } elseif (strpos($key, 'race_d32x_count_') === 0) {
                $val = substr($key, 16);
                $this->_addRule($rules, 'race_d32x_count = ' . $val, $count, array('tot'=>0,'hit'=>0), $minLift);
            } elseif (strpos($key, 'race_d3x_count_') === 0) {
                $val = substr($key, 15);
                $this->_addRule($rules, 'race_d3x_count = ' . $val, $count, array('tot'=>0,'hit'=>0), $minLift);
            } elseif (strpos($key, 'race_total33x_') === 0) {
                $val = substr($key, 14);
                $this->_addRule($rules, 'race_total33x = ' . $val, $count, array('tot'=>0,'hit'=>0), $minLift);
            } elseif (strpos($key, 'race_total3x_') === 0) {
                $val = substr($key, 13);
                $this->_addRule($rules, 'race_total3x = ' . $val, $count, array('tot'=>0,'hit'=>0), $minLift);
            } elseif (strpos($key, 'race_count40_49_') === 0) {
                $val = substr($key, 16);
                $this->_addRule($rules, 'race_count40_49 = ' . $val, $count, array('tot'=>0,'hit'=>0), $minLift);
            } elseif (strpos($key, 'race_count50plus_') === 0) {
                $val = substr($key, 17);
                $this->_addRule($rules, 'race_count50plus = ' . $val, $count, array('tot'=>0,'hit'=>0), $minLift);
            } elseif (strpos($key, 'group_size_') === 0) {
                $val = substr($key, 11);
                $this->_addRule($rules, 'group_size = ' . $val, $count, array('tot'=>0,'hit'=>0), $minLift);
            } elseif (strpos($key, 'race_unique_props_') === 0) {
                $val = substr($key, 18);
                $this->_addRule($rules, 'race_unique_props = ' . $val, $count, array('tot'=>0,'hit'=>0), $minLift);
            } elseif (strpos($key, 'label_') === 0) {
                $val = substr($key, 6);
                if ($val !== '_null_' && $val !== '_other_') {
                    $this->_addRule($rules, 'label = ' . $val, $count, array('tot'=>0,'hit'=>0), $minLift);
                }
            } elseif (strpos($key, 'cmpd_') === 0) {
                $idx = (int)substr($key, 5);
                $defs = $this->_compoundDefs();
                $tag = isset($defs[$idx]) ? $this->_compoundTag($defs[$idx]) : ('cmpd_' . $idx);
                $otherKey = $key . '__other';
                $other = isset($this->_tempCounts[$otherKey]) ? $this->_tempCounts[$otherKey] : array('tot'=>0,'hit'=>0);
                $this->_addRule($rules, $tag, $count, $other, $minLift);
            }
        }
        return $rules;
    }

    public function predictRace($features) {
        $n = count($features);
        if ($n < 1) return array();

        if (!$this->isTrained || empty($this->minedRules)) {
            $res = array();
            foreach ($features as $idx => $f) {
                $pw = isset($f['prewin']) ? (float)$f['prewin'] : 10;
                if ($pw <= 0) $pw = 99;
                $bp = round(1.0/(1+$pw), 3);
                $res[] = array(
                    'horse'       => isset($f['horse']) ? $f['horse'] : null,
                    'position'    => isset($f['position']) ? (int)$f['position'] : $idx,
                    'prop_val'    => isset($f['prop_val']) ? $f['prop_val'] : 0,
                    'label'       => isset($f['label']) ? $f['label'] : null,
                    'baseProb'    => $bp,
                    'ruleProb'    => 0,
                    'probability' => $bp,
                    'rule_hits'   => 0,
                    'totalLift'   => 0,
                    'rawScore'    => $bp,
                );
            }
            usort($res, array($this, '_sortByProb'));
            return $res;
        }

        $allRules = $this->_allRules();
        $rows = array();

        // Pass 1: ALL matching non-race-level rules (no hit cap)
        foreach ($features as $idx => $f) {
            $pw = isset($f['prewin']) ? (float)$f['prewin'] : 10;
            if ($pw <= 0) $pw = 99;
            $baseProb = 1.0 / (1 + $pw);

            $ruleHits = 0;
            $excessLift = 0.0;
            foreach ($allRules as $rule) {
                $rname = $rule['rule_name'];
                if (!empty($rule['race_level']) || $this->_isRaceLevelOnly($rname)) {
                    continue;
                }
                if ($this->_matchRule($rname, $f, $allRules)) {
                    $lv = isset($rule['lift']) ? (float)$rule['lift'] : 1.0;
                    $ruleHits++;
                    $excessLift += max(0.0, $lv - 1.0);
                }
            }

            $pv = isset($f['prop_val']) ? (float)$f['prop_val'] : 0;
            $cappedProp = ($pv > 45) ? (45 + ($pv - 45) * 0.3) : $pv;

            $rows[] = array(
                'horse'       => isset($f['horse']) ? $f['horse'] : null,
                'position'    => isset($f['position']) ? (int)$f['position'] : $idx,
                'prop_val'    => isset($f['prop_val']) ? $f['prop_val'] : 0,
                'label'       => isset($f['label']) ? $f['label'] : null,
                'baseProb'    => $baseProb,
                'rule_hits'   => $ruleHits,
                'totalLift'   => $excessLift,
                'propNorm'    => $cappedProp / 100.0,
                'symptom'     => 0.0,
            );
        }

        // Pass 2: symptom race-rules → additive boost
        if (!empty($this->raceRules)) {
            foreach ($this->raceRules as $raceRule) {
                $targets = call_user_func($raceRule['callback'], $features);
                $boost = isset($raceRule['boost']) ? (float)$raceRule['boost'] : 0.08;
                foreach ($targets as $t) {
                    foreach ($rows as &$row) {
                        if ((int)$row['position'] === (int)$t) {
                            $row['symptom'] += $boost;
                            $row['rule_hits']++;
                            $row['totalLift'] += 1.0; // symptom ≈ excess lift 1.0
                            break;
                        }
                    }
                    unset($row);
                }
            }
        }

        // Pass 3: within-race relative channels
        $minLift = $maxLift = $rows[0]['totalLift'];
        $minHits = $maxHits = $rows[0]['rule_hits'];
        $minSym  = $maxSym  = $rows[0]['symptom'];
        foreach ($rows as $r) {
            if ($r['totalLift'] < $minLift) $minLift = $r['totalLift'];
            if ($r['totalLift'] > $maxLift) $maxLift = $r['totalLift'];
            if ($r['rule_hits'] < $minHits) $minHits = $r['rule_hits'];
            if ($r['rule_hits'] > $maxHits) $maxHits = $r['rule_hits'];
            if ($r['symptom'] < $minSym) $minSym = $r['symptom'];
            if ($r['symptom'] > $maxSym) $maxSym = $r['symptom'];
        }
        $spanLift = max(0.001, $maxLift - $minLift);
        $spanHits = max(1, $maxHits - $minHits);
        $hasSym = ($maxSym > $minSym + 0.0001);

        $rawScores = array();
        foreach ($rows as $i => $r) {
            $liftRel = ($r['totalLift'] - $minLift) / $spanLift;
            $hitsRel = ($r['rule_hits'] - $minHits) / $spanHits;
            $symRel  = $hasSym ? (($r['symptom'] - $minSym) / max(0.001, $maxSym - $minSym)) : 0.0;

            // ruleProb = pure rule/symptom channel (0..1), independent of market
            $ruleProb = $liftRel * 0.55 + $hitsRel * 0.30 + $symRel * 0.15;

            // Final blend — rules weighted higher so Prob ≠ just prewin
            $raw = $r['baseProb'] * 0.20
                 + $ruleProb      * 0.50
                 + $r['propNorm'] * 0.20
                 + $symRel        * 0.10;

            $rows[$i]['ruleProb'] = round($ruleProb, 3);
            $rows[$i]['rawScore'] = $raw;
            $rawScores[] = $raw;
        }

        // Pass 4: map raw → display probability with spread
        $minRaw = min($rawScores);
        $maxRaw = max($rawScores);
        $spanRaw = max(0.001, $maxRaw - $minRaw);

        $predictions = array();
        foreach ($rows as $i => $r) {
            $norm = ($r['rawScore'] - $minRaw) / $spanRaw;
            $prob = 0.08 + 0.42 * $norm;
            if ($n >= 10) {
                $prob = 0.06 + 0.46 * pow($norm, 0.9);
            }

            $predictions[] = array(
                'horse'       => $r['horse'],
                'position'    => $r['position'],
                'prop_val'    => $r['prop_val'],
                'label'       => $r['label'],
                'baseProb'    => round($r['baseProb'], 3),
                'ruleProb'    => $r['ruleProb'],
                'probability' => round($prob, 3),
                'rule_hits'   => $r['rule_hits'],
                'totalLift'   => round($r['totalLift'], 3),
                'rawScore'    => round($r['rawScore'], 4),
            );
        }

        usort($predictions, array($this, '_sortByProb'));
        return $predictions;
    }

    private function _sortByProb($a, $b) {
        if ($b['probability'] > $a['probability']) return 1;
        if ($b['probability'] < $a['probability']) return -1;
        // Tie-break: higher rawScore, then higher baseProb, then lower position
        if (isset($a['rawScore']) && isset($b['rawScore']) && $b['rawScore'] != $a['rawScore']) {
            return ($b['rawScore'] > $a['rawScore']) ? 1 : -1;
        }
        if ($b['baseProb'] != $a['baseProb']) {
            return ($b['baseProb'] > $a['baseProb']) ? 1 : -1;
        }
        return $a['position'] - $b['position'];
    }

    /**
     * Match a rule name against a feature vector.
     * Supports: bare bool, field=true/false, "field = val", AND compounds.
     */
    private function _matchRule($ruleName, $f, $allRules = null) {
        $rules = $allRules ? $allRules : $this->_allRules();
        foreach ($rules as $r) {
            if ($r['rule_name'] === $ruleName && isset($r['condition'])) {
                return call_user_func($r['condition'], $f);
            }
        }
        if (stripos($ruleName, ' AND ') !== false) {
            $parts = preg_split('/\s+AND\s+/i', $ruleName);
            foreach ($parts as $part) {
                if (!$this->_matchAtomic(trim($part), $f)) return false;
            }
            return true;
        }
        return $this->_matchAtomic($ruleName, $f);
    }

    private function _matchAtomic($expr, $f) {
        $expr = trim($expr);
        if ($expr === '') return false;

        // field = val  OR  field=val
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/', $expr, $m)) {
            $col = $m[1];
            $val = trim($m[2]);
            $actual = array_key_exists($col, $f) ? $f[$col] : null;
            if ($val === 'true')  return !empty($f[$col]);
            if ($val === 'false') return empty($f[$col]);
            return (string)$actual === (string)$val;
        }

        // bare boolean field → must be true
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $expr)) {
            return !empty($f[$expr]);
        }
        return false;
    }

    private function _atomColumn($expr) {
        $expr = trim($expr);
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)/', $expr, $m)) return $m[1];
        return $expr;
    }

    private function _isRaceLevelOnly($ruleName) {
        $parts = (stripos($ruleName, ' AND ') !== false)
            ? preg_split('/\s+AND\s+/i', $ruleName)
            : array($ruleName);
        foreach ($parts as $part) {
            $col = $this->_atomColumn($part);
            if (!in_array($col, self::$RACE_LEVEL_COLS, true)) {
                return false;
            }
        }
        return true;
    }

    public function predictRawProps($venue, $propsArr, $prewinArr = array()) {
        $n = count($propsArr);
        if ($n < 1) return array();

        $freq = array();
        $posMap = array();
        foreach ($propsArr as $i => $v) {
            $freq[$v] = isset($freq[$v]) ? $freq[$v]+1 : 1;
            $posMap[$v][] = $i;
        }
        $minPositions = array();
        $maxPositions = array();
        foreach ($posMap as $val => $posList) {
            $minPositions[$val] = min($posList);
            $maxPositions[$val] = max($posList);
        }

        $labels = array();
        foreach ($propsArr as $i => $v) {
            $cnt = $freq[$v];
            if ($cnt < 2) { $labels[$i] = null; continue; }
            if ($v>=10 && $v<=19) $labels[$i] = '1x';
            elseif ($v>=20 && $v<=29) $labels[$i] = ($cnt>=3 ? '32x' : '2x');
            elseif ($v>=30 && $v<=39) $labels[$i] = ($cnt>=3 ? '33x' : '3x');
            elseif ($v>=40 && $v<=49) $labels[$i] = ($cnt>=3 ? '34x' : '4x');
            else $labels[$i] = null;
        }

        $count40_49 = 0;
        $count50plus = 0;
        $B2 = $B2_pos = $B3 = $B3_pos = $B4 = $B4_pos = null;
        $has18 = $has19 = false;
        $p18 = $p19 = array();
        $hasMid40 = false;

        foreach ($propsArr as $i => $v) {
            if ($v>=40 && $v<=49) {
                $count40_49++;
                $ratio = $i / max(1, $n - 1);
                if ($ratio >= 0.25 && $ratio <= 0.75) $hasMid40 = true;
            }
            if ($v>=50) $count50plus++;
            if ($v>=20 && $v<=29 && ($B2===null||$v>$B2)) { $B2=$v; $B2_pos=$i; }
            if ($v>=30 && $v<=39 && ($B3===null||$v>$B3)) { $B3=$v; $B3_pos=$i; }
            if ($v>=40 && $v<=49 && ($B4===null||$v>$B4)) { $B4=$v; $B4_pos=$i; }
            if ($v==18) { $has18=true; $p18[]=$i; }
            if ($v==19) { $has19=true; $p19[]=$i; }
        }

        $botThreshold = $n * 0.66;
        $any18Bot = false;
        $any19Bot = false;
        foreach ($p18 as $p) { if ($p >= $botThreshold) { $any18Bot = true; break; } }
        foreach ($p19 as $p) { if ($p >= $botThreshold) { $any19Bot = true; break; } }

        $count_32x = 0;
        $count_33x = 0;
        $count_3x  = 0;
        foreach ($freq as $v => $cnt) {
            if ($v>=20 && $v<=29 && $cnt>=3) $count_32x++;
            if ($v>=30 && $v<=39 && $cnt>=3) $count_33x++;
            if ($v>=30 && $v<=39 && $cnt==2) $count_3x++;
        }
        $total33xLabels = 0;
        $total3xLabels  = 0;
        foreach ($labels as $l) {
            if ($l==='33x') $total33xLabels++;
            if ($l==='3x') $total3xLabels++;
        }

        $features = array();
        for ($i = 0; $i < $n; $i++) {
            $pv = $propsArr[$i];
            $features[] = array(
                'position' => $i,
                'prop_val' => $pv,
                'venue'    => $venue,
                'label'    => $labels[$i],
                'prop_range' => ($pv >= 50 ? '50+' : ($pv >= 40 ? '40-49' : ($pv >= 30 ? '30-39' : ($pv >= 20 ? '20-29' : '10-19')))),
                'is_top'   => ($i===0),
                'is_top2'  => ($i<=1),
                'is_top3'  => ($i<=2),
                'is_bottom'=> ($i===$n-1),
                'is_32x'   => ($labels[$i]==='32x'),
                'is_33x'   => ($labels[$i]==='33x'),
                'is_3x'    => ($labels[$i]==='3x'),
                'is_2x'    => ($labels[$i]==='2x'),
                'is_1x'    => ($labels[$i]==='1x'),
                'is_4x'    => ($labels[$i]==='4x'),
                'is_34x'   => ($labels[$i]==='34x'),
                'is_B2'    => ($i===$B2_pos),
                'is_B3'    => ($i===$B3_pos),
                'is_B4'    => ($i===$B4_pos),
                'is_lonely' => ($freq[$propsArr[$i]] == 1),
                'is_dup'    => ($freq[$propsArr[$i]] >= 2),
                'race_has_over50'  => ($count50plus>0),
                'race_count40_49'  => $count40_49,
                'race_count50plus' => $count50plus,
                'race_d32x_count'  => $count_32x,
                'race_d33x_count'  => $count_33x,
                'race_d3x_count'   => $count_3x,
                'race_total33x'    => $total33xLabels,
                'race_total3x'     => $total3xLabels,
                'race_has18'       => $has18,
                'race_has19'       => $has19,
                'race_hasMid40'    => $hasMid40,
                'race_any18Bot'    => $any18Bot,
                'race_any19Bot'    => $any19Bot,
                'B2_lt_B3'         => ($B2 !== null && $B3 !== null && $B2 < $B3),
                'B3_lt_B2'         => ($B3 !== null && $B2 !== null && $B3 < $B2),
                'prewin'           => isset($prewinArr[$i]) ? (float)$prewinArr[$i] : 10,
                'in_group'        => ($freq[$pv] >= 2),
                'group_leader'    => ($freq[$pv] >= 2 && $i === $minPositions[$pv]),
                'group_tail'      => ($freq[$pv] >= 2 && $i === $maxPositions[$pv]),
                'group_mid'       => ($freq[$pv] >= 3 && $i !== $minPositions[$pv] && $i !== $maxPositions[$pv]),
                'group_size'      => ($freq[$pv] >= 2 ? $freq[$pv] : 1),
                'has_sequential_33x' => ($labels[$i] === '33x' && $i + 1 < $n && $labels[$i+1] === '33x'),
                'has_sequential_32x' => ($labels[$i] === '32x' && $i + 1 < $n && $labels[$i+1] === '32x'),
                'has_sequential_2x'  => ($labels[$i] === '2x' && $i + 1 < $n && $labels[$i+1] === '2x'),
                'has_sequential_3x'  => ($labels[$i] === '3x' && $i + 1 < $n && $labels[$i+1] === '3x'),
                'is_sequential_head' => ($labels[$i] !== null && $i + 1 < $n && $labels[$i+1] === $labels[$i]),
                'is_sequential_tail' => ($labels[$i] !== null && $i > 0 && $labels[$i-1] === $labels[$i]),
                'abrupt_drop'      => ($i + 1 < $n && $pv > max(1, $propsArr[$i+1]) * 2),
                'abrupt_rise'      => ($i > 0 && $propsArr[$i-1] > max(1, $pv) * 2),
                'is_plateau_start'  => ($i + 1 < $n && abs($pv - $propsArr[$i+1]) <= 2 && ($i === 0 || abs($pv - $propsArr[$i-1]) > 2)),
                'is_plateau_mid'    => ($i > 0 && $i + 1 < $n && abs($pv - $propsArr[$i-1]) <= 2 && abs($pv - $propsArr[$i+1]) <= 2),
                'is_plateau_end'    => ($i > 0 && abs($pv - $propsArr[$i-1]) <= 2 && ($i === $n - 1 || abs($pv - $propsArr[$i+1]) > 2)),
                'is_peak'      => ($i > 0 && $i + 1 < $n && $propsArr[$i-1] <= $pv && $propsArr[$i+1] <= $pv),
                'is_valley'    => ($i > 0 && $i + 1 < $n && $propsArr[$i-1] >= $pv && $propsArr[$i+1] >= $pv),
                'prev_gt'      => ($i > 0 && $propsArr[$i-1] > $pv),
                'next_gt'      => ($i + 1 < $n && $propsArr[$i+1] > $pv),
                'race_unique_props' => count(array_unique($propsArr)),
            );
        }
        return $this->predictRace($features);
    }

    public function addCustomRule($name, $conditionFunc) {
        $this->customRules[] = array(
            'rule_name' => $name,
            'lift'      => 1.5,
            'condition' => $conditionFunc,
        );
    }

    public function addRaceRule($name, $callback, $boost = 0.08) {
        $this->raceRules[] = array(
            'rule_name' => $name,
            'lift'      => 2.0,
            'boost'     => $boost,
            'callback'  => $callback,
        );
    }

    /**
     * Encode domain symptom rules a–g from prop symptom.txt.
     * Each callback returns position indices to boost.
     */
    public function registerSymptomRules() {
        if (!empty($this->_symptomsRegistered)) return;
        $this->_symptomsRegistered = true;

        // (a) HV: 33x + 3x present → top of 33x, bottom of 3x
        $this->addRaceRule('sym_a_hv_33x_3x', function($features) {
            $venue = isset($features[0]['venue']) ? $features[0]['venue'] : '';
            if (strtoupper($venue) !== 'HV') return array();
            $d33 = isset($features[0]['race_d33x_count']) ? (int)$features[0]['race_d33x_count'] : 0;
            $d3  = isset($features[0]['race_d3x_count']) ? (int)$features[0]['race_d3x_count'] : 0;
            if ($d33 < 1 || $d3 < 1) return array();
            $out = array();
            foreach ($features as $f) {
                if (!empty($f['is_33x']) && !empty($f['group_leader'])) $out[] = (int)$f['position'];
                if (!empty($f['is_3x']) && !empty($f['group_tail'])) $out[] = (int)$f['position'];
            }
            return array_values(array_unique($out));
        }, 0.10);

        // (b) HV: 32x + 3x → top of 3x, middle of 32x
        $this->addRaceRule('sym_b_hv_32x_3x', function($features) {
            $venue = isset($features[0]['venue']) ? $features[0]['venue'] : '';
            if (strtoupper($venue) !== 'HV') return array();
            $d32 = isset($features[0]['race_d32x_count']) ? (int)$features[0]['race_d32x_count'] : 0;
            $d3  = isset($features[0]['race_d3x_count']) ? (int)$features[0]['race_d3x_count'] : 0;
            if ($d32 < 1 || $d3 < 1) return array();
            $out = array();
            foreach ($features as $f) {
                if (!empty($f['is_3x']) && !empty($f['group_leader'])) $out[] = (int)$f['position'];
                if (!empty($f['is_32x']) && !empty($f['group_mid'])) $out[] = (int)$f['position'];
            }
            return array_values(array_unique($out));
        }, 0.09);

        // (c) top1 lonely 50-59, next is B3 → boost B3
        $this->addRaceRule('sym_c_top50_next_B3', function($features) {
            $n = count($features);
            if ($n < 2) return array();
            $byPos = array();
            foreach ($features as $f) $byPos[(int)$f['position']] = $f;
            if (!isset($byPos[0]) || !isset($byPos[1])) return array();
            $t0 = $byPos[0];
            $v0 = (int)$t0['prop_val'];
            if ($v0 < 50 || $v0 > 59) return array();
            if (empty($t0['is_lonely'])) return array();
            if (!empty($byPos[1]['is_B3'])) return array(1);
            return array();
        }, 0.10);

        // (d) mid 40-49 present → smallest 20-29, else mid-40 horses
        $this->addRaceRule('sym_d_mid40_min20', function($features) {
            $n = count($features);
            if ($n < 1) return array();
            if (empty($features[0]['race_hasMid40'])) return array();
            $min20Pos = null;
            $min20Val = null;
            $mid40 = array();
            foreach ($features as $f) {
                $v = (int)$f['prop_val'];
                $i = (int)$f['position'];
                if ($v >= 20 && $v <= 29) {
                    if ($min20Val === null || $v < $min20Val) {
                        $min20Val = $v;
                        $min20Pos = $i;
                    }
                }
                if ($v >= 40 && $v <= 49) {
                    $ratio = $i / max(1, $n - 1);
                    if ($ratio >= 0.25 && $ratio <= 0.75) $mid40[] = $i;
                }
            }
            if ($min20Pos !== null) return array($min20Pos);
            return $mid40;
        }, 0.09);

        // (e) two 32x sets → top + bottom of the bigger 32x value
        $this->addRaceRule('sym_e_two_32x', function($features) {
            $d32 = isset($features[0]['race_d32x_count']) ? (int)$features[0]['race_d32x_count'] : 0;
            if ($d32 < 2) return array();
            $groups = array();
            foreach ($features as $f) {
                if (empty($f['is_32x'])) continue;
                $v = (int)$f['prop_val'];
                if (!isset($groups[$v])) $groups[$v] = array();
                $groups[$v][] = (int)$f['position'];
            }
            if (count($groups) < 2) return array();
            $vals = array_keys($groups);
            rsort($vals, SORT_NUMERIC);
            $big = $vals[0];
            $posList = $groups[$big];
            sort($posList, SORT_NUMERIC);
            return array($posList[0], $posList[count($posList) - 1]);
        }, 0.09);

        // (f) 19 at lower bottom → B3
        $this->addRaceRule('sym_f_19bot_B3', function($features) {
            if (empty($features[0]['race_any19Bot'])) return array();
            $out = array();
            foreach ($features as $f) {
                if (!empty($f['is_B3'])) $out[] = (int)$f['position'];
            }
            return $out;
        }, 0.09);

        // (g) 18 at lower bottom + B2 < B3 → B2
        $this->addRaceRule('sym_g_18bot_B2', function($features) {
            if (empty($features[0]['race_any18Bot'])) return array();
            if (empty($features[0]['B2_lt_B3'])) return array();
            $out = array();
            foreach ($features as $f) {
                if (!empty($f['is_B2'])) $out[] = (int)$f['position'];
            }
            return $out;
        }, 0.09);

        // Default chain rule (existing)
        $this->addRaceRule('default_chain', function($features) {
            $n = count($features);
            $result = array();
            for ($i = 0; $i < $n - 2; $i++) {
                $v1 = isset($features[$i]['prop_val']) ? (int)$features[$i]['prop_val'] : 0;
                $l1 = isset($features[$i]['label']) ? $features[$i]['label'] : null;
                $v2 = isset($features[$i+1]['prop_val']) ? (int)$features[$i+1]['prop_val'] : 0;
                $l2 = isset($features[$i+1]['label']) ? $features[$i+1]['label'] : null;
                if ($v1 > 36 && $l1 !== '3x' && $v2 >= 30 && $v2 <= 36 && $l2 !== '3x') {
                    $result[] = $i + 2;
                }
            }
            return $result;
        }, 0.08);
    }

    private $_symptomsRegistered = false;

    /**
     * finalpick3 — rank by differentiated probability + prop + market.
     */
    public function finalpick3($predictions, $propsArr = array()) {
        $n = count($predictions);
        if ($n < 1) return array();

        $maxRuleHits = 1;
        $maxTotalLift = 0.001;
        $maxProb = 0.001;
        foreach ($predictions as $p) {
            $maxRuleHits = max($maxRuleHits, (int)(isset($p['rule_hits']) ? $p['rule_hits'] : 0));
            $maxTotalLift = max($maxTotalLift, (float)(isset($p['totalLift']) ? $p['totalLift'] : 0));
            $maxProb = max($maxProb, (float)(isset($p['probability']) ? $p['probability'] : 0));
        }

        $scores = array();
        foreach ($predictions as $idx => $p) {
            $pos = isset($p['position']) ? (int)$p['position'] : $idx;
            $pv = isset($p['prop_val']) ? (float)$p['prop_val'] : 0;
            if ($pv == 0 && isset($propsArr[$pos])) $pv = (float)$propsArr[$pos];

            $cappedProp = $pv;
            if ($pv > 45) {
                $cappedProp = 45 + ($pv - 45) * 0.3;
            }

            $rh = (int)(isset($p['rule_hits']) ? $p['rule_hits'] : 0);
            $tl = (float)(isset($p['totalLift']) ? $p['totalLift'] : 0);
            $baseProb = (float)(isset($p['baseProb']) ? $p['baseProb'] : 0.05);
            $prob = (float)(isset($p['probability']) ? $p['probability'] : 0);

            $propNorm = $cappedProp / 100;
            $rhNorm = $rh / $maxRuleHits;
            $tlNorm = $tl / $maxTotalLift;
            $probNorm = $prob / $maxProb;

            $score = $probNorm * 0.40 + $baseProb * 0.25 + $propNorm * 0.15 + $tlNorm * 0.12 + $rhNorm * 0.08;
            $scores[$idx] = $score;
        }

        return $this->_buildTopPicks($predictions, $scores, 3);
    }

    /**
     * finalpick3onlyrules — pattern-only picks (ignore prop & market).
     *
     * Prop order is fixed; historical patterns hint which horses in that string
     * are more likely to place. Pick the strongest 3 by rule signal only.
     *
     *   score = 0.65 × (excessLift / maxLift) + 0.35 × (rule_hits / maxHits)
     *
     * Lift weighted higher: quality of matched rules > sheer match count.
     */
    public function finalpick3onlyrules($predictions) {
        $n = count($predictions);
        if ($n < 1) return array();

        $maxRuleHits = 1;
        $maxTotalLift = 0.001;
        foreach ($predictions as $p) {
            $maxRuleHits = max($maxRuleHits, (int)(isset($p['rule_hits']) ? $p['rule_hits'] : 0));
            $maxTotalLift = max($maxTotalLift, (float)(isset($p['totalLift']) ? $p['totalLift'] : 0));
        }

        $scores = array();
        foreach ($predictions as $idx => $p) {
            $rh = (int)(isset($p['rule_hits']) ? $p['rule_hits'] : 0);
            $tl = (float)(isset($p['totalLift']) ? $p['totalLift'] : 0);
            $scores[$idx] = ($tl / $maxTotalLift) * 0.65 + ($rh / $maxRuleHits) * 0.35;
        }

        return $this->_buildTopPicks($predictions, $scores, 3);
    }

    /** Build top-N pick rows from score map (idx => score). */
    private function _buildTopPicks($predictions, $scores, $topN) {
        $order = array_keys($scores);
        usort($order, function($ia, $ib) use ($scores, $predictions) {
            $sa = $scores[$ia]; $sb = $scores[$ib];
            if ($sb > $sa) return 1;
            if ($sb < $sa) return -1;
            $pa = $predictions[$ia]; $pb = $predictions[$ib];
            $la = (float)(isset($pa['totalLift']) ? $pa['totalLift'] : 0);
            $lb = (float)(isset($pb['totalLift']) ? $pb['totalLift'] : 0);
            if ($lb > $la) return 1;
            if ($lb < $la) return -1;
            $ha = (int)(isset($pa['rule_hits']) ? $pa['rule_hits'] : 0);
            $hb = (int)(isset($pb['rule_hits']) ? $pb['rule_hits'] : 0);
            if ($hb > $ha) return 1;
            if ($hb < $ha) return -1;
            $posa = isset($pa['position']) ? (int)$pa['position'] : $ia;
            $posb = isset($pb['position']) ? (int)$pb['position'] : $ib;
            return $posa - $posb;
        });

        $picks = array();
        $picked = 0;
        foreach ($order as $idx) {
            if ($picked >= $topN) break;
            $p = $predictions[$idx];
            $picks[] = array(
                'horseno'         => isset($p['horse']) ? $p['horse'] : ($idx + 1),
                'position'        => isset($p['position']) ? $p['position'] : $idx,
                'prop_val'        => isset($p['prop_val']) ? $p['prop_val'] : 0,
                'probability'     => isset($p['probability']) ? $p['probability'] : 0,
                'rule_hits'       => isset($p['rule_hits']) ? $p['rule_hits'] : 0,
                'totalLift'       => isset($p['totalLift']) ? $p['totalLift'] : 0,
                'baseProb'        => isset($p['baseProb']) ? $p['baseProb'] : 0,
                'finalPosition'   => isset($p['finalPosition']) ? $p['finalPosition'] : null,
                'composite_score' => round($scores[$idx], 4),
                'pick_rank'       => $picked + 1,
            );
            $picked++;
        }
        return $picks;
    }

    /**
     * Compute pick hit-rate metrics. Place = finalPosition ≤ 3.
     */
    public static function computeMetrics(array $racePreds) {
        $top1Hit = 0; $top1Total = 0;
        $top3Hit = 0; $top3Total = 0;
        $raceAny = 0; $raceTotal = 0;
        $fp3Hit = 0; $fp3Total = 0;
        $fp3RaceAny = 0; $fp3RaceTotal = 0;
        $rorHit = 0; $rorTotal = 0;
        $rorRaceAny = 0; $rorRaceTotal = 0;

        foreach ($racePreds as $r) {
            $preds = isset($r['predictions']) ? $r['predictions'] : array();
            if (empty($preds)) continue;

            $hasOutcome = false;
            foreach ($preds as $p) {
                if (isset($p['finalPosition']) && (int)$p['finalPosition'] > 0) { $hasOutcome = true; break; }
            }
            if (!$hasOutcome) continue;

            $raceTotal++;
            $top1Total++;
            $fp0 = isset($preds[0]['finalPosition']) ? (int)$preds[0]['finalPosition'] : 0;
            if ($fp0 > 0 && $fp0 <= 3) $top1Hit++;

            $any = false;
            $limit = min(3, count($preds));
            for ($i = 0; $i < $limit; $i++) {
                $top3Total++;
                $fp = isset($preds[$i]['finalPosition']) ? (int)$preds[$i]['finalPosition'] : 0;
                if ($fp > 0 && $fp <= 3) { $top3Hit++; $any = true; }
            }
            if ($any) $raceAny++;

            if (!empty($r['finalpicks'])) {
                $fp3RaceTotal++;
                $anyFp = false;
                foreach ($r['finalpicks'] as $pk) {
                    $fp3Total++;
                    $fp = isset($pk['finalPosition']) ? (int)$pk['finalPosition'] : 0;
                    if ($fp > 0 && $fp <= 3) { $fp3Hit++; $anyFp = true; }
                }
                if ($anyFp) $fp3RaceAny++;
            }

            if (!empty($r['finalpicks_rules'])) {
                $rorRaceTotal++;
                $anyRo = false;
                foreach ($r['finalpicks_rules'] as $pk) {
                    $rorTotal++;
                    $fp = isset($pk['finalPosition']) ? (int)$pk['finalPosition'] : 0;
                    if ($fp > 0 && $fp <= 3) { $rorHit++; $anyRo = true; }
                }
                if ($anyRo) $rorRaceAny++;
            }
        }

        return array(
            'top1_place_pct'     => $top1Total ? round(100.0 * $top1Hit / $top1Total, 1) : 0,
            'top1_hits'          => $top1Hit,
            'top1_total'         => $top1Total,
            'top3_place_pct'     => $top3Total ? round(100.0 * $top3Hit / $top3Total, 1) : 0,
            'top3_hits'          => $top3Hit,
            'top3_total'         => $top3Total,
            'race_any_top3_pct'  => $raceTotal ? round(100.0 * $raceAny / $raceTotal, 1) : 0,
            'race_any_top3'      => $raceAny,
            'races_scored'       => $raceTotal,
            'finalpick3_place_pct' => $fp3Total ? round(100.0 * $fp3Hit / $fp3Total, 1) : 0,
            'finalpick3_hits'    => $fp3Hit,
            'finalpick3_total'   => $fp3Total,
            'finalpick3_race_pct'=> $fp3RaceTotal ? round(100.0 * $fp3RaceAny / $fp3RaceTotal, 1) : 0,
            'rules_only_place_pct' => $rorTotal ? round(100.0 * $rorHit / $rorTotal, 1) : 0,
            'rules_only_hits'    => $rorHit,
            'rules_only_total'   => $rorTotal,
            'rules_only_race_pct'=> $rorRaceTotal ? round(100.0 * $rorRaceAny / $rorRaceTotal, 1) : 0,
        );
    }

    public function hasRaceRules() {
        return !empty($this->raceRules);
    }

    public function clearCustomRules() {
        $this->customRules = array();
        $this->raceRules = array();
        $this->_symptomsRegistered = false;
    }

    private function _allRules() {
        return array_merge($this->minedRules, $this->customRules);
    }

    public function getBaseline() { return $this->baselinePlaced; }
    public function getMinedRules() { return $this->minedRules; }
    public function getCustomRules()  { return $this->customRules; }
    public function isTrained() { return $this->isTrained; }
    public function getTrainVenue() { return $this->trainVenue; }
    public function getTrainHorseCount() { return $this->trainHorseCount; }
    public function getTrainRaceCount() { return $this->trainRaceCount; }

    /** Venue filter SQL fragment + bound params for ST / HV / Sx. */
    public static function venueSqlFilter($venue) {
        if ($venue === null || $venue === '') {
            return array('sql' => '', 'params' => array());
        }
        if ($venue === 'Sx') {
            // Explicit IN — avoid REGEXP edge cases; never includes ST/HV
            return array(
                'sql' => " AND venue IN ('S1','S2','S3','S4','S5','S6','S7','S8','S9')",
                'params' => array(),
            );
        }
        return array('sql' => ' AND venue = ?', 'params' => array($venue));
    }

    /**
     * Factory: per-venue predictors.
     * @param string|null $dateBefore  If set, train only on racingdate < $dateBefore (holdout).
     */
    public static function createPerVenue($db, $venues = null, $topN = null, $minLift = null, $dateBefore = null) {
        if ($venues === null) $venues = array('ST', 'HV', 'Sx');
        if ($topN === null) $topN = self::TOP_N_RULES;
        if ($minLift === null) $minLift = self::DEFAULT_MIN_LIFT;
        $instances = array();
        foreach ($venues as $v) {
            $p = new self();
            $p->trainAll($db, $topN, $minLift, $v, $dateBefore);
            $p->registerSymptomRules();
            $instances[$v] = $p;
        }
        return $instances;
    }

    /**
     * Train on venue history. Optional $dateBefore for walk-forward holdout.
     */
    public function trainAll($db, $topN = null, $minLift = null, $venue = null, $dateBefore = null) {
        if ($topN === null) $topN = self::TOP_N_RULES;
        if ($minLift === null) $minLift = self::DEFAULT_MIN_LIFT;
        $this->beginTrain();
        $this->trainVenue = $venue;
        $this->trainHorseCount = 0;
        $this->trainRaceCount = 0;
        $analyzer = new PropAnalyzer($db);

        $sql = "SELECT * FROM racepropresult WHERE proppre IS NOT NULL";
        $params = array();
        $vf = self::venueSqlFilter($venue);
        $sql .= $vf['sql'];
        foreach ($vf['params'] as $p) $params[] = $p;
        if ($dateBefore !== null && $dateBefore !== '') {
            $sql .= " AND racingdate < ?";
            $params[] = $dateBefore;
        }
        $sql .= " ORDER BY racingdate DESC, raceno DESC, venue ASC, (1.0/prewin) DESC, prepla ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $races = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Safety: never leak other venues into this trainer
            if ($venue === 'Sx') {
                if (!preg_match('/^S[0-9]$/', $row['venue'])) continue;
            } elseif ($venue !== null && $venue !== '' && $row['venue'] !== $venue) {
                continue;
            }

            if (isset($row['horsename']) && !mb_check_encoding($row['horsename'], 'UTF-8')) {
                $row['horsename'] = mb_convert_encoding($row['horsename'], 'UTF-8', 'UTF-8,GBK,BIG5');
            }
            if (isset($row['go_ch']) && !mb_check_encoding($row['go_ch'], 'UTF-8')) {
                $row['go_ch'] = mb_convert_encoding($row['go_ch'], 'UTF-8', 'UTF-8,GBK,BIG5');
            }
            $key = $row['racingdate'] . '|' . $row['venue'] . '|' . $row['raceno'];
            if (!isset($races[$key])) {
                $races[$key] = array(
                    'racingdate' => $row['racingdate'],
                    'raceno'     => (int)$row['raceno'],
                    'venue'      => $row['venue'],
                    'distance'   => (int)$row['Distance'],
                    'go_ch'      => $row['go_ch'],
                    'horses'     => array(),
                );
            }
            $races[$key]['horses'][] = $row;

            if (count($races) >= 100) {
                $keys = array_keys($races);
                $keepKey = $keys[count($keys) - 1];
                $batch = array();
                foreach ($races as $rk => $race) {
                    if ($rk === $keepKey) continue;
                    usort($race['horses'], array('PropPrediction', '_sortHorses'));
                    $batch[] = $race;
                }
                if (!empty($batch)) {
                    $result = $analyzer->analyzeBatch($batch);
                    $this->addChunk($result['features']);
                    $this->trainRaceCount += count($batch);
                    $this->trainHorseCount += count($result['features']);
                }
                $races = array($keepKey => $races[$keepKey]);
                unset($batch, $result);
            }
        }

        if (!empty($races)) {
            $batch = array();
            foreach ($races as $race) {
                usort($race['horses'], array('PropPrediction', '_sortHorses'));
                $batch[] = $race;
            }
            $result = $analyzer->analyzeBatch($batch);
            $this->addChunk($result['features']);
            $this->trainRaceCount += count($batch);
            $this->trainHorseCount += count($result['features']);
        }

        return $this->finalizeTrain($topN, $minLift);
    }

    public static function _sortHorses($a, $b) {
        $valA = $a['prewin'] > 0 ? (1.0 / (float)$a['prewin']) : 999999.0;
        $valB = $b['prewin'] > 0 ? (1.0 / (float)$b['prewin']) : 999999.0;
        if (abs($valA - $valB) > 0.00001) {
            return $valA > $valB ? -1 : 1;
        }
        $pa = (float)$a['prepla']; $pb = (float)$b['prepla'];
        return $pa > $pb ? -1 : ($pa < $pb ? 1 : 0);
    }
}
