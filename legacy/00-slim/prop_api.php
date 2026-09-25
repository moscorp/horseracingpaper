<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'lib/constants.php';
require_once 'PropAnalyzer.php';
require_once 'PropPrediction.php';

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
    $db = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo safe_json(['error' => 'DB connection failed']);
    exit;
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo safe_json(['error' => $e->getMessage()]);
    exit;
}

function gp($k) {
    return !empty($_GET[$k]) ? $_GET[$k] : null;
}

function safe_json($data) {
    $out = json_encode($data);
    if ($out === false) {
        return json_encode(array('error' => 'JSON encode failed', 'reason' => json_last_error_msg()));
    }
    return $out;
}

$analyzer  = new PropAnalyzer($db);
$predictor = new PropPrediction();
$venuePredictors = null;

try {
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    switch ($action) {

        case 'venues':
            $stmt = $db->query("SELECT DISTINCT venue FROM racepropresult ORDER BY venue");
            echo safe_json($stmt->fetchAll(PDO::FETCH_COLUMN));
            break;

        case 'list_dates':
            $stmt = $db->query("SELECT DISTINCT racingdate FROM racepropresult ORDER BY racingdate DESC");
            echo safe_json($stmt->fetchAll(PDO::FETCH_COLUMN));
            break;

        case 'train':
            // Train with optional holdout: if date_from set, train on dates BEFORE it
            $dateFrom = gp('date_from');
            $dateTo   = gp('date_to');
            $holdout  = gp('holdout'); // '1' = train before date_from
            if ($holdout === '1' && $dateFrom) {
                $races = $analyzer->fetchRaces(gp('venue'), null, null, gp('venue_like'));
                // Filter to racingdate < date_from
                $races = array_values(array_filter($races, function($r) use ($dateFrom) {
                    return $r['racingdate'] < $dateFrom;
                }));
            } else {
                $races = $analyzer->fetchRaces(gp('venue'), $dateFrom, $dateTo, gp('venue_like'));
            }
            $result   = $analyzer->analyzeBatch($races);
            $features = $result['features'];
            $mined    = $predictor->train($features, null, PropPrediction::DEFAULT_MIN_LIFT);
            $predictor->registerSymptomRules();
            echo safe_json(array(
                'total_horses' => count($features),
                'total_races'  => count($result['analyses']),
                'baseline'     => round($predictor->getBaseline() * 100, 1),
                'trained'      => $predictor->isTrained(),
                'holdout'      => ($holdout === '1'),
                'train_before' => ($holdout === '1') ? $dateFrom : null,
                'rules'        => $mined,
            ));
            break;

        case 'add_custom_rule':
            $name  = gp('name') ?: '';
            $col   = gp('col') ?: '';
            $val   = gp('val') ?: '';
            $op    = gp('op') ?: 'equals';
            if (!$name || !$col) {
                echo safe_json(array('error' => 'name and col required'));
                break;
            }
            $predictor->addCustomRule($name, function($f) use ($col, $val, $op) {
                $actual = isset($f[$col]) ? $f[$col] : null;
                switch ($op) {
                    case 'equals':   return (string)$actual === (string)$val;
                    case 'not':      return (string)$actual !== (string)$val;
                    case 'gte':      return (float)$actual >= (float)$val;
                    case 'lte':      return (float)$actual <= (float)$val;
                    case 'contains': return strpos((string)$actual, (string)$val) !== false;
                    case 'bool':     return !empty($f[$col]);
                    default:         return (string)$actual === (string)$val;
                }
            });
            echo safe_json(array(
                'ok'      => true,
                'name'    => $name,
                'count'   => count($predictor->getCustomRules()),
            ));
            break;

        case 'clear_custom_rules':
            $predictor->clearCustomRules();
            echo safe_json(array('ok' => true, 'cleared' => true));
            break;

        case 'add_race_rule':
            $name = gp('name') ?: 'chain_rule';
            $predictor->addRaceRule($name, function($features) {
                $n = count($features);
                $result = array();
                for ($i = 0; $i < $n - 2; $i++) {
                    $v1  = isset($features[$i]['prop_val']) ? (int)$features[$i]['prop_val'] : 0;
                    $l1  = isset($features[$i]['label']) ? $features[$i]['label'] : null;
                    $v2  = isset($features[$i+1]['prop_val']) ? (int)$features[$i+1]['prop_val'] : 0;
                    $l2  = isset($features[$i+1]['label']) ? $features[$i+1]['label'] : null;
                    if ($v1 > 36 && $l1 !== '3x' && $v2 >= 30 && $v2 <= 36 && $l2 !== '3x') {
                        $result[] = $i + 2;
                    }
                }
                return $result;
            });
            echo safe_json(array('ok' => true, 'name' => $name));
            break;

        case 'predict':
            // Walk-forward: train on history BEFORE date_from (or all if no from)
            $dateFrom = gp('date_from');
            $dateTo   = gp('date_to');
            // Guard: empty dates = full history → OOM/timeout on shared hosts
            if (!$dateFrom) {
                $dateFrom = ((int)date('Y') - 2) . '-09-01';
            }
            if (!$dateTo) {
                $dateTo = date('Y-m-d');
            }
            $dateBefore = $dateFrom;
            $holdoutUsed = false;

            if ($venuePredictors === null) {
                $venuePredictors = PropPrediction::createPerVenue(
                    $db, null, null, null, $dateBefore
                );
                $holdoutUsed = ($dateBefore !== null);

                // Fallback: if holdout left almost no rules, retrain on all history
                $anyRules = false;
                foreach ($venuePredictors as $vp) {
                    if (count($vp->getMinedRules()) >= 3) { $anyRules = true; break; }
                }
                if ($dateBefore && !$anyRules) {
                    $venuePredictors = PropPrediction::createPerVenue($db);
                    $holdoutUsed = false;
                    $dateBefore = null;
                }
            }

            $races    = $analyzer->fetchRaces(gp('venue'), $dateFrom, $dateTo, gp('venue_like'));
            $result   = $analyzer->analyzeBatch($races);
            $allFeats = $result['features'];
            $analyses = $result['analyses'];

            $racePreds = [];
            foreach ($analyses as $analysis) {
                $raceKey   = $analysis['race_key'];
                $venue     = isset($analysis['venue']) ? $analysis['venue'] : 'ST';
                $vkey      = in_array($venue, array('ST', 'HV')) ? $venue : 'Sx';
                $vpred     = $venuePredictors[$vkey];

                $raceFeats = array_values(array_filter($allFeats, function($f) use ($raceKey) {
                    return (isset($f['race_key']) ? $f['race_key'] : '') === $raceKey;
                }));

                $perHorse = $vpred->predictRace($raceFeats);

                // Attach outcomes by position (stable) then horse name
                $fpByPos = array();
                $fpByHorse = array();
                foreach ($raceFeats as $rf) {
                    $pos = isset($rf['position']) ? (int)$rf['position'] : null;
                    $fp  = isset($rf['finalPosition']) ? $rf['finalPosition'] : null;
                    if ($pos !== null) $fpByPos[$pos] = $fp;
                    if (isset($rf['horse'])) $fpByHorse[$rf['horse']] = $fp;
                }
                foreach ($perHorse as &$ph) {
                    $pos = isset($ph['position']) ? (int)$ph['position'] : null;
                    if ($pos !== null && array_key_exists($pos, $fpByPos)) {
                        $ph['finalPosition'] = $fpByPos[$pos];
                    } elseif (isset($ph['horse']) && array_key_exists($ph['horse'], $fpByHorse)) {
                        $ph['finalPosition'] = $fpByHorse[$ph['horse']];
                    } else {
                        $ph['finalPosition'] = null;
                    }
                }
                unset($ph);

                $propsArr = isset($analysis['prop_order']) ? $analysis['prop_order'] : array();
                $picks = $vpred->finalpick3($perHorse, $propsArr);
                $picksRules = $vpred->finalpick3onlyrules($perHorse);

                $racePreds[] = [
                    'race_key'    => $analysis['race_key'],
                    'racingdate'  => $analysis['racingdate'],
                    'raceno'      => $analysis['raceno'],
                    'venue'       => $analysis['venue'],
                    'distance'    => $analysis['distance'],
                    'go_ch'       => $analysis['go_ch'],
                    'horse_count' => $analysis['horse_count'],
                    'prop_order'  => $analysis['prop_order'],
                    'labels'      => $analysis['labels'],
                    'symptoms'    => [
                        'B2' => $analysis['B2'], 'B2_pos' => $analysis['B2_pos'],
                        'B3' => $analysis['B3'], 'B3_pos' => $analysis['B3_pos'],
                        'B4' => $analysis['B4'], 'B4_pos' => $analysis['B4_pos'],
                    ],
                    'predictions' => $perHorse,
                    'finalpicks'  => $picks,
                    'finalpicks_rules' => $picksRules,
                ];
            }

            $venueRules = array();
            foreach ($venuePredictors as $vk => $vp) {
                $venueRules[$vk] = array(
                    'rules'        => $vp->getMinedRules(),
                    'baseline'     => round($vp->getBaseline() * 100, 1),
                    'train_venue'  => $vp->getTrainVenue(),
                    'train_horses' => $vp->getTrainHorseCount(),
                    'train_races'  => $vp->getTrainRaceCount(),
                    'rules_count'  => count($vp->getMinedRules()),
                );
            }

            $metrics = PropPrediction::computeMetrics($racePreds);

            // Display rules for the selected venue group (not always ST)
            $displayVenue = gp('venue');
            $venueLike = gp('venue_like');
            $displayKey = 'ST';
            if ($displayVenue === 'HV') {
                $displayKey = 'HV';
            } elseif ($displayVenue === 'ST') {
                $displayKey = 'ST';
            } elseif ($displayVenue === 'Sx' || ($venueLike && preg_match('/S[0-9]/', $venueLike))) {
                $displayKey = 'Sx';
            } elseif ($displayVenue && !in_array($displayVenue, array('ST', 'HV'))) {
                $displayKey = 'Sx';
            }
            // "All" venues: show all three summaries; default table = ST
            $displayRules = $venueRules[$displayKey]['rules'];
            $displayBase  = $venueRules[$displayKey]['baseline'];

            echo safe_json(array(
                'baseline'     => $displayBase,
                'total_races'  => count($racePreds),
                'rules_mined'  => count($displayRules),
                'mined_rules'  => $displayRules,
                'rules_venue'  => $displayKey,
                'venue_rules'  => $venueRules,
                'train_before' => $dateBefore,
                'holdout'      => $holdoutUsed,
                'metrics'      => $metrics,
                'races'        => $racePreds,
            ));
            break;

        case 'predict_raw':
            $venue  = gp('venue') ?: 'ST';
            $raw    = gp('props') ?: '';
            $propsArr = array_map('intval', array_filter(explode(',', $raw), 'strlen'));
            $prewinArr = [];
            if ($rawPre = gp('prewin')) {
                $prewinArr = array_map('floatval', array_filter(explode(',', $rawPre), 'strlen'));
            }
            if (empty($propsArr)) {
                echo safe_json(array('error'=> 'No props provided'));
                break;
            }
            if ($venuePredictors === null) {
                $venuePredictors = PropPrediction::createPerVenue($db);
            }
            $vkey = in_array($venue, array('ST', 'HV')) ? $venue : 'Sx';
            if (!isset($venuePredictors[$vkey])) $vkey = 'ST';
            $preds = $venuePredictors[$vkey]->predictRawProps($venue, $propsArr, $prewinArr);
            $picks = $venuePredictors[$vkey]->finalpick3($preds, $propsArr);
            $picksRules = $venuePredictors[$vkey]->finalpick3onlyrules($preds);
            echo safe_json(array(
                'venue'       => $vkey,
                'rules_count' => count($venuePredictors[$vkey]->getMinedRules()),
                'baseline'    => round($venuePredictors[$vkey]->getBaseline() * 100, 1),
                'predictions' => $preds,
                'finalpicks'  => $picks,
                'finalpicks_rules' => $picksRules,
            ));
            break;

        default:
            echo safe_json(array(
                'api'     => 'Prop Prediction API',
                'actions' => array('venues','list_dates','train','predict','predict_raw','add_custom_rule','add_race_rule','clear_custom_rules'),
            ));
            break;
    }
} catch (Exception $e) {
    echo safe_json(array(
        'error' => $e->getMessage(),
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
    ));
}
