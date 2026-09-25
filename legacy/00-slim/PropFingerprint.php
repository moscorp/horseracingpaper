<?php
/**
 * PropFingerprint — build band vocabulary for a race prop string.
 *
 * Order: 1/prewin ASC, tie → prepla ASC (caller must pre-sort horses).
 * Bands: 1=10-19 … 5=50-59, 6=60-120
 * Labels: {n}x (pair), {k}{n}x (k≥3), w{n} singleton, B{n}/s{n} extrema
 * Size: small = value%10 <= 4, big = value%10 > 4
 * Tokens: pos21/pos22 for 2x; pos321/pos322/pos323 for 32x; etc.
 * Groups always carry size + appearance ord (among same label, then among label+size).
 */
class PropFingerprint {

    public static function venueBucket($venue) {
        $v = strtoupper(trim((string)$venue));
        if ($v === 'ST') return 'ST';
        if ($v === 'HV') return 'HV';
        if (preg_match('/^S[1-9]$/', $v)) return 'Sx';
        return 'Sx';
    }

    public static function bandOf($val) {
        $v = (int)$val;
        if ($v >= 10 && $v <= 19) return '1';
        if ($v >= 20 && $v <= 29) return '2';
        if ($v >= 30 && $v <= 39) return '3';
        if ($v >= 40 && $v <= 49) return '4';
        if ($v >= 50 && $v <= 59) return '5';
        if ($v >= 60 && $v <= 120) return '6';
        return null;
    }

    public static function sizeOf($val) {
        return ((int)$val % 10) <= 4 ? 'small' : 'big';
    }

    public static function labelFor($band, $count) {
        $count = (int)$count;
        $band = (string)$band;
        if ($count < 2) return null;
        if ($count === 2) return $band . 'x';
        return $count . $band . 'x';
    }

    /**
     * @param array $horses rows with proppre, optional finalPosition, horseno, horsecode
     * @return array fingerprint payload
     */
    public static function buildFromHorses(array $horses, $venue = '', $meta = array()) {
        $props = array();
        $fps = array();
        $metaH = array();
        foreach ($horses as $h) {
            $props[] = (int)$h['proppre'];
            $fps[] = isset($h['finalPosition']) ? (int)$h['finalPosition'] : null;
            $metaH[] = array(
                'horseno'   => isset($h['horseno']) ? $h['horseno'] : null,
                'horsecode' => isset($h['horsecode']) ? $h['horsecode'] : null,
                'horsename' => isset($h['horsename']) ? $h['horsename'] : null,
                'prewin'    => isset($h['prewin']) ? (float)$h['prewin'] : null,
            );
        }
        $fp = self::buildFromProps($props, $fps);
        $fp['venue'] = $venue;
        $fp['venue_bucket'] = self::venueBucket($venue);
        $fp['meta'] = $meta;
        $fp['horses'] = $metaH;
        return $fp;
    }

    /**
     * @param int[] $props
     * @param (int|null)[] $fps
     */
    public static function buildFromProps(array $props, array $fps = array()) {
        $n = count($props);
        $freq = array();
        $valPositions = array();
        for ($i = 0; $i < $n; $i++) {
            $v = (int)$props[$i];
            if (!isset($freq[$v])) $freq[$v] = 0;
            $freq[$v]++;
            $valPositions[$v][] = $i;
        }

        $bands = array();
        foreach (array('1','2','3','4','5','6') as $b) {
            $bands[$b] = array(
                'groups' => array(),
                'c_pair' => 0,
                'c_triple_plus' => 0,
                'c2x' => 0,
                'c32x' => 0,
                'c42x' => 0,
                'B' => null,
                's' => null,
                'w' => array(),
            );
        }

        // Groups (count >= 2)
        foreach ($freq as $val => $cnt) {
            if ($cnt < 2) continue;
            $band = self::bandOf($val);
            if ($band === null) continue;
            $label = self::labelFor($band, $cnt);
            $posList = $valPositions[$val];
            sort($posList);
            $tokenBase = preg_replace('/x$/', '', $label); // "2", "32", "3", "33"
            $tokens = array();
            for ($k = 0; $k < count($posList); $k++) {
                $tokens['pos' . $tokenBase . ($k + 1)] = $posList[$k];
            }
            $bands[$band]['groups'][] = array(
                'value'  => (int)$val,
                'count'  => (int)$cnt,
                'label'  => $label,
                'size'   => self::sizeOf($val),
                'pos'    => $posList,
                'top'    => $posList[0],
                'bottom' => $posList[count($posList) - 1],
                'tokens' => $tokens,
            );
        }

        // Singletons + B/s + counts; assign ord
        foreach ($bands as $band => &$bd) {
            // appearance order among all groups
            usort($bd['groups'], function ($a, $b) {
                if ($a['top'] === $b['top']) return $a['value'] - $b['value'];
                return $a['top'] - $b['top'];
            });
            $ordAll = array();
            $ordSize = array();
            foreach ($bd['groups'] as &$g) {
                $lbl = $g['label'];
                if (!isset($ordAll[$lbl])) $ordAll[$lbl] = 0;
                $ordAll[$lbl]++;
                $g['ord'] = $ordAll[$lbl];

                $sk = $lbl . '|' . $g['size'];
                if (!isset($ordSize[$sk])) $ordSize[$sk] = 0;
                $ordSize[$sk]++;
                $g['ord_size'] = $ordSize[$sk];

                if ($g['count'] === 2) {
                    $bd['c_pair']++;
                    $bd['c2x']++;
                } elseif ($g['count'] >= 3) {
                    $bd['c_triple_plus']++;
                    if ($g['count'] === 3) $bd['c32x']++;
                    if ($g['count'] >= 4) $bd['c42x']++;
                }
            }
            unset($g);

            $maxV = null; $maxP = null; $minV = null; $minP = null;
            $w = array();
            for ($i = 0; $i < $n; $i++) {
                $v = (int)$props[$i];
                if (self::bandOf($v) !== (string)$band) continue;
                if ($maxV === null || $v > $maxV) { $maxV = $v; $maxP = $i; }
                if ($minV === null || $v < $minV) { $minV = $v; $minP = $i; }
                if ($freq[$v] === 1) {
                    $w[] = array(
                        'value' => $v,
                        'pos'   => $i,
                        'size'  => self::sizeOf($v),
                    );
                }
            }
            if ($maxV !== null) {
                $bd['B'] = array('value' => $maxV, 'pos' => $maxP, 'size' => self::sizeOf($maxV));
                $bd['s'] = array('value' => $minV, 'pos' => $minP, 'size' => self::sizeOf($minV));
            }
            // appearance order for w (global ord + ord within same size)
            usort($w, function ($a, $b) { return $a['pos'] - $b['pos']; });
            $ordSizeCount = array();
            foreach ($w as $wi => &$ww) {
                $ww['ord'] = $wi + 1;
                $sk = $ww['size'];
                if (!isset($ordSizeCount[$sk])) $ordSizeCount[$sk] = 0;
                $ordSizeCount[$sk]++;
                $ww['ord_size'] = $ordSizeCount[$sk];
            }
            unset($ww);
            $bd['w'] = $w;

            // aliases used in rules: B2, s2, w2, c2x, c32x
            $bd['B' . $band] = $bd['B'];
            $bd['s' . $band] = $bd['s'];
            $bd['w' . $band] = $bd['w'];
            // c{band}x already as c2x for band 2 via key name below
            $bd['c' . $band . 'x'] = $bd['c_pair'];
            $bd['c3' . $band . 'x'] = $bd['c_triple_plus'];
        }
        unset($bd);

        // UI cells
        $cells = array();
        for ($i = 0; $i < $n; $i++) {
            $v = (int)$props[$i];
            $cnt = $freq[$v];
            $band = self::bandOf($v);
            $fill = 'none';
            $label = null;
            if ($cnt === 2) {
                $fill = 'yellow';
                $label = $band ? self::labelFor($band, 2) : null;
            } elseif ($cnt >= 3) {
                $fill = 'green';
                $label = $band ? self::labelFor($band, $cnt) : null;
            }
            $cells[] = array(
                'pos'   => $i,
                'prop'  => $v,
                'fp'    => isset($fps[$i]) ? $fps[$i] : null,
                'fill'  => $fill,
                'label' => $label,
                'size'  => self::sizeOf($v),
                'band'  => $band,
                'count' => $cnt,
            );
        }

        // All multi-group labels present (for only_label)
        $allLabels = array();
        foreach ($bands as $bd) {
            foreach ($bd['groups'] as $g) {
                $allLabels[$g['label']] = true;
            }
        }

        return array(
            'n_horses'   => $n,
            'prop_order' => $props,
            'prop_string'=> implode(',', $props),
            'top'        => 0,
            'bottom'     => $n > 0 ? $n - 1 : null,
            'bands'      => $bands,
            'cells'      => $cells,
            'all_labels' => array_keys($allLabels),
            'fps'        => $fps,
        );
    }

    /**
     * Find groups matching filters.
     * @return array list of groups
     */
    public static function findGroups(array $fp, $band, $label = null, $size = null, $ord = null, $ordSize = null) {
        $band = (string)$band;
        if (!isset($fp['bands'][$band])) return array();
        $out = array();
        foreach ($fp['bands'][$band]['groups'] as $g) {
            if ($label !== null && $g['label'] !== $label) continue;
            if ($size !== null && $g['size'] !== $size) continue;
            if ($ord !== null && (int)$g['ord'] !== (int)$ord) continue;
            if ($ordSize !== null && (int)$g['ord_size'] !== (int)$ordSize) continue;
            $out[] = $g;
        }
        return $out;
    }

    public static function countGroups(array $fp, $band, $label = null, $size = null) {
        return count(self::findGroups($fp, $band, $label, $size, null, null));
    }

    /** Ensure w[] entries have ord_size (for cached bands_json from before ord_size existed). */
    public static function normalizeBands(array &$bands) {
        foreach ($bands as $band => &$bd) {
            if (!isset($bd['w']) || !is_array($bd['w'])) continue;
            usort($bd['w'], function ($a, $b) { return $a['pos'] - $b['pos']; });
            $ordSizeCount = array();
            foreach ($bd['w'] as $wi => &$ww) {
                if (!isset($ww['ord'])) $ww['ord'] = $wi + 1;
                $sk = isset($ww['size']) ? $ww['size'] : self::sizeOf($ww['value']);
                if (!isset($ordSizeCount[$sk])) $ordSizeCount[$sk] = 0;
                $ordSizeCount[$sk]++;
                $ww['ord_size'] = $ordSizeCount[$sk];
            }
            unset($ww);
        }
        unset($bd);
    }
}
