<?php
/**
 * Manual Rules Platform API
 *
 * Actions:
 *   install | rebuild_fingerprints | dates | venues
 *   list_races | propose_picks | save_rule | list_rules | get_rule | toggle_rule | delete_rule
 *   backtest | backtest_all | eval_string | mine_rules
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('max_execution_time', '600');
@set_time_limit(600);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// constants: try prop/lib then ../lib (same as prop_api.php layout)
if (file_exists(__DIR__ . '/lib/constants.php')) {
    require_once __DIR__ . '/lib/constants.php';
} elseif (file_exists(__DIR__ . '/../lib/constants.php')) {
    require_once __DIR__ . '/../lib/constants.php';
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array('error' => 'constants.php not found (tried prop/lib and ../lib)'));
    exit;
}

require_once __DIR__ . '/PropFingerprint.php';
require_once __DIR__ . '/PropManualRuleEngine.php';

function out($data) {
    while (ob_get_level() > 0) { @ob_end_clean(); }
    $j = json_encode($data);
    echo ($j !== false) ? $j : json_encode(array('error' => 'JSON encode failed'));
}

function gp($k, $default = null) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') return $_GET[$k];
    if (isset($_POST[$k]) && $_POST[$k] !== '') return $_POST[$k];
    return $default;
}

function body_json() {
    static $cached = null;
    if ($cached !== null) return $cached;
    $raw = file_get_contents('php://input');
    $cached = $raw ? json_decode($raw, true) : array();
    if (!is_array($cached)) $cached = array();
    return $cached;
}

function bp($k, $default = null) {
    $b = body_json();
    return array_key_exists($k, $b) ? $b[$k] : gp($k, $default);
}

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
    $db = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    out(array('error' => 'DB connection failed', 'detail' => $e->getMessage()));
    exit;
}

function table_exists(PDO $db, $name) {
    $stmt = $db->prepare("SHOW TABLES LIKE ?");
    $stmt->execute(array($name));
    return (bool)$stmt->fetchColumn();
}

function table_has_column(PDO $db, $table, $column) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
    $stmt->execute(array($column));
    return (bool)$stmt->fetch();
}

/** Drop & recreate fingerprint table if an older/wrong schema is present */
function ensure_fingerprint_schema(PDO $db) {
    $needReset = false;
    if (table_exists($db, 'prop_race_fingerprint')) {
        if (!table_has_column($db, 'prop_race_fingerprint', 'racingdate')
            || !table_has_column($db, 'prop_race_fingerprint', 'prop_string')
            || !table_has_column($db, 'prop_race_fingerprint', 'venue_bucket')) {
            $needReset = true;
        }
    }
    if ($needReset) {
        $db->exec("DROP TABLE IF EXISTS `prop_race_fingerprint`");
    }
    install_schema($db);
    return $needReset;
}

function install_schema(PDO $db) {
    // Inline DDL so install never depends on file parsing
    $stmts = array(
        "CREATE TABLE IF NOT EXISTS `prop_race_fingerprint` (
          `id` BIGINT NOT NULL AUTO_INCREMENT,
          `racingdate` DATE NOT NULL,
          `venue` VARCHAR(10) NOT NULL,
          `venue_bucket` ENUM('ST','HV','Sx') NOT NULL,
          `raceno` INT NOT NULL,
          `distance` INT DEFAULT NULL,
          `go_ch` VARCHAR(50) DEFAULT NULL,
          `n_horses` TINYINT NOT NULL,
          `prop_string` VARCHAR(255) NOT NULL,
          `bands_json` LONGTEXT NOT NULL,
          `cells_json` LONGTEXT NOT NULL,
          `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_race` (`racingdate`, `venue`, `raceno`),
          KEY `idx_bucket_date` (`venue_bucket`, `racingdate`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `prop_manual_rule` (
          `id` BIGINT NOT NULL AUTO_INCREMENT,
          `code` VARCHAR(32) NOT NULL,
          `name` VARCHAR(120) NOT NULL,
          `venue_scope` ENUM('ST','HV','Sx','ALL') NOT NULL DEFAULT 'Sx',
          `priority` INT NOT NULL DEFAULT 100,
          `enabled` TINYINT(1) NOT NULL DEFAULT 0,
          `mark_weight` DECIMAL(4,2) NOT NULL DEFAULT 1.00,
          `condition_json` LONGTEXT NOT NULL,
          `pick_json` LONGTEXT NOT NULL,
          `note_zh` TEXT DEFAULT NULL,
          `created_by` VARCHAR(40) DEFAULT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_code` (`code`),
          KEY `idx_scope_en` (`venue_scope`, `enabled`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `prop_manual_rule_evidence` (
          `id` BIGINT NOT NULL AUTO_INCREMENT,
          `rule_id` BIGINT NOT NULL,
          `racingdate` DATE NOT NULL,
          `venue` VARCHAR(10) NOT NULL,
          `raceno` INT NOT NULL,
          `prop_string` VARCHAR(255) NOT NULL,
          `result_json` LONGTEXT NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_rule_race` (`rule_id`, `racingdate`, `venue`, `raceno`),
          KEY `idx_race` (`racingdate`, `venue`, `raceno`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `prop_manual_rule_stat` (
          `rule_id` BIGINT NOT NULL,
          `venue_scope` VARCHAR(10) NOT NULL,
          `date_from` DATE NOT NULL,
          `date_to` DATE NOT NULL,
          `fp_max` TINYINT NOT NULL DEFAULT 4,
          `races_matched` INT NOT NULL,
          `picks_total` INT NOT NULL,
          `picks_placed` INT NOT NULL,
          `race_hit` INT NOT NULL,
          `hit_rate` DECIMAL(6,4) NOT NULL,
          `pick_hit_rate` DECIMAL(6,4) NOT NULL,
          `computed_at` DATETIME NOT NULL,
          PRIMARY KEY (`rule_id`, `venue_scope`, `date_from`, `date_to`, `fp_max`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    );
    foreach ($stmts as $sql) {
        $db->exec($sql);
    }
    return true;
}

function sort_horses(array &$horses) {
    usort($horses, function ($a, $b) {
        $pa = ((float)$a['prewin'] > 0) ? (1.0 / (float)$a['prewin']) : 0;
        $pb = ((float)$b['prewin'] > 0) ? (1.0 / (float)$b['prewin']) : 0;
        if ($pa == $pb) {
            return ((float)$a['prepla'] < (float)$b['prepla']) ? -1 : 1;
        }
        return ($pa > $pb) ? -1 : 1;
    });
}

function rebuild_fingerprints(PDO $db, $dateFrom = null, $dateTo = null) {
    // Same source table/columns as PropAnalyzer / racepropresult.sql
    $sql = "SELECT * FROM racepropresult WHERE proppre IS NOT NULL";
    $params = array();
    if ($dateFrom) { $sql .= " AND racingdate >= ?"; $params[] = $dateFrom; }
    if ($dateTo) { $sql .= " AND racingdate <= ?"; $params[] = $dateTo; }
    $sql .= " ORDER BY racingdate, venue, raceno, horseno";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    if (empty($rows)) {
        throw new RuntimeException(
            'No rows in racepropresult for this date range (proppre IS NOT NULL). Check dates / DB name.'
        );
    }

    $races = array();
    foreach ($rows as $r) {
        $key = $r['racingdate'] . '|' . $r['venue'] . '|' . $r['raceno'];
        if (!isset($races[$key])) {
            $races[$key] = array(
                'racingdate' => $r['racingdate'],
                'venue' => $r['venue'],
                'raceno' => (int)$r['raceno'],
                'distance' => isset($r['Distance']) ? (int)$r['Distance'] : null,
                'go_ch' => isset($r['go_ch']) ? $r['go_ch'] : '',
                'horses' => array(),
            );
        }
        $races[$key]['horses'][] = $r;
    }

    $ins = $db->prepare("
        INSERT INTO prop_race_fingerprint
          (racingdate, venue, venue_bucket, raceno, distance, go_ch, n_horses, prop_string, bands_json, cells_json)
        VALUES
          (:d, :v, :vb, :rn, :dist, :go, :n, :ps, :bj, :cj)
        ON DUPLICATE KEY UPDATE
          venue_bucket=VALUES(venue_bucket), distance=VALUES(distance), go_ch=VALUES(go_ch),
          n_horses=VALUES(n_horses), prop_string=VALUES(prop_string),
          bands_json=VALUES(bands_json), cells_json=VALUES(cells_json)
    ");

    $count = 0;
    foreach ($races as $race) {
        sort_horses($race['horses']);
        $fp = PropFingerprint::buildFromHorses(
            $race['horses'],
            $race['venue'],
            array(
                'racingdate' => $race['racingdate'],
                'raceno' => $race['raceno'],
                'distance' => $race['distance'],
                'go_ch' => $race['go_ch'],
            )
        );
        // ENUM only allows ST/HV/Sx — coerce anything else
        $vb = isset($fp['venue_bucket']) ? $fp['venue_bucket'] : 'Sx';
        if ($vb !== 'ST' && $vb !== 'HV' && $vb !== 'Sx') $vb = 'Sx';

        $ins->execute(array(
            ':d' => $race['racingdate'],
            ':v' => $race['venue'],
            ':vb' => $vb,
            ':rn' => $race['raceno'],
            ':dist' => $race['distance'],
            ':go' => $race['go_ch'],
            ':n' => $fp['n_horses'],
            ':ps' => $fp['prop_string'],
            ':bj' => json_encode($fp['bands']),
            ':cj' => json_encode($fp['cells']),
        ));
        $count++;
    }
    return $count;
}

function row_to_fp(array $row) {
    $bands = json_decode($row['bands_json'], true);
    $cells = json_decode($row['cells_json'], true);
    if (!is_array($bands)) $bands = array();
    if (!is_array($cells)) $cells = array();
    PropFingerprint::normalizeBands($bands);
    $props = array();
    foreach (explode(',', $row['prop_string']) as $p) {
        if ($p === '') continue;
        $props[] = (int)$p;
    }
    $fps = array();
    foreach ($cells as $c) {
        $fps[] = array_key_exists('fp', $c) ? $c['fp'] : null;
    }
    $allLabels = array();
    foreach ($bands as $bd) {
        if (!isset($bd['groups'])) continue;
        foreach ($bd['groups'] as $g) {
            $allLabels[$g['label']] = true;
        }
    }
    return array(
        'id' => isset($row['id']) ? (int)$row['id'] : null,
        'racingdate' => $row['racingdate'],
        'venue' => $row['venue'],
        'venue_bucket' => $row['venue_bucket'],
        'raceno' => (int)$row['raceno'],
        'distance' => $row['distance'],
        'go_ch' => $row['go_ch'],
        'n_horses' => (int)$row['n_horses'],
        'prop_order' => $props,
        'prop_string' => $row['prop_string'],
        'top' => 0,
        'bottom' => count($props) > 0 ? count($props) - 1 : null,
        'bands' => $bands,
        'cells' => $cells,
        'all_labels' => array_keys($allLabels),
        'fps' => $fps,
    );
}

function load_fingerprints(PDO $db, $venueBucket = null, $dateFrom = null, $dateTo = null) {
    $sql = "SELECT * FROM prop_race_fingerprint WHERE 1=1";
    $params = array();
    if ($venueBucket && $venueBucket !== 'ALL') {
        $sql .= " AND venue_bucket = ?";
        $params[] = $venueBucket;
    }
    if ($dateFrom) { $sql .= " AND racingdate >= ?"; $params[] = $dateFrom; }
    if ($dateTo) { $sql .= " AND racingdate <= ?"; $params[] = $dateTo; }
    $sql .= " ORDER BY racingdate DESC, venue, raceno";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $out = array();
    foreach ($stmt->fetchAll() as $row) {
        $out[] = row_to_fp($row);
    }
    return $out;
}

function normalize_rule(array $r) {
    if (is_string($r['condition_json'])) {
        $r['condition_json'] = json_decode($r['condition_json'], true);
    }
    if (is_string($r['pick_json'])) {
        $r['pick_json'] = json_decode($r['pick_json'], true);
    }
    $r['enabled'] = (int)$r['enabled'];
    $r['mark_weight'] = (float)$r['mark_weight'];
    $r['condition'] = $r['condition_json'];
    $r['pick'] = $r['pick_json'];
    return $r;
}

function load_rules(PDO $db, $enabledOnly = false, $scope = null) {
    $sql = "SELECT * FROM prop_manual_rule WHERE 1=1";
    $params = array();
    if ($enabledOnly) $sql .= " AND enabled = 1";
    if ($scope && $scope !== 'ALL') {
        $sql .= " AND (venue_scope = ? OR venue_scope = 'ALL')";
        $params[] = $scope;
    }
    $sql .= " ORDER BY priority ASC, id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = array();
    foreach ($stmt->fetchAll() as $r) {
        $rows[] = normalize_rule($r);
    }
    return $rows;
}

function next_rule_code(PDO $db, $scope) {
    $prefix = ($scope && $scope !== 'ALL') ? $scope : 'ALL';
    $stmt = $db->prepare("SELECT code FROM prop_manual_rule WHERE code LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute(array($prefix . '-R%'));
    $last = $stmt->fetchColumn();
    $n = 1;
    if ($last && preg_match('/R(\d+)$/', $last, $m)) {
        $n = (int)$m[1] + 1;
    }
    return $prefix . '-R' . str_pad((string)$n, 3, '0', STR_PAD_LEFT);
}

function count_rule_hits(array $fp, array $rules) {
    $n = 0;
    foreach ($rules as $rule) {
        if (!(int)$rule['enabled']) continue;
        $scope = $rule['venue_scope'];
        if ($scope !== 'ALL' && isset($fp['venue_bucket']) && $fp['venue_bucket'] !== $scope) continue;
        $r = PropManualRuleEngine::applyRule($fp, $rule);
        if ($r['matched']) $n++;
    }
    return $n;
}

function evidence_cells(array $fp, $fpMax = 4) {
    $out = array();
    foreach ($fp['cells'] as $c) {
        if (!isset($c['fp']) || $c['fp'] === null) continue;
        if ((int)$c['fp'] <= 0 || (int)$c['fp'] > (int)$fpMax) continue;
        $out[] = array('pos' => $c['pos'], 'prop' => $c['prop'], 'fp' => (int)$c['fp']);
    }
    return $out;
}

$action = gp('action', '');

try {
    switch ($action) {

        case 'install':
            $reset = ensure_fingerprint_schema($db);
            out(array('ok' => true, 'fingerprint_reset' => $reset));
            break;

        case 'diagnose':
            $info = array(
                'db' => isset($dbConfig['name']) ? $dbConfig['name'] : null,
                'source_table' => 'racepropresult',
                'tables' => array(),
            );
            foreach (array('racepropresult', 'prop_race_fingerprint', 'prop_manual_rule', 'prop_manual_rule_evidence', 'prop_manual_rule_stat') as $t) {
                $info['tables'][$t] = table_exists($db, $t);
            }
            if ($info['tables']['racepropresult']) {
                $info['source_rows'] = (int)$db->query("SELECT COUNT(*) FROM racepropresult WHERE proppre IS NOT NULL")->fetchColumn();
                $info['source_dates'] = $db->query("SELECT MIN(racingdate), MAX(racingdate) FROM racepropresult")->fetch(PDO::FETCH_NUM);
            }
            if ($info['tables']['prop_race_fingerprint']) {
                $info['fp_rows'] = (int)$db->query("SELECT COUNT(*) FROM prop_race_fingerprint")->fetchColumn();
                $cols = $db->query("SHOW COLUMNS FROM prop_race_fingerprint")->fetchAll(PDO::FETCH_COLUMN);
                $info['fp_columns'] = $cols;
            }
            out($info);
            break;

        case 'rebuild_fingerprints':
            $reset = ensure_fingerprint_schema($db);
            $n = rebuild_fingerprints($db, gp('date_from'), gp('date_to'));
            out(array('ok' => true, 'races' => $n, 'fingerprint_reset' => $reset));
            break;

        case 'dates':
            $stmt = $db->query("SELECT DISTINCT racingdate FROM racepropresult ORDER BY racingdate DESC");
            out($stmt->fetchAll(PDO::FETCH_COLUMN));
            break;

        case 'venues':
            $stmt = $db->query("SELECT DISTINCT venue FROM racepropresult ORDER BY venue");
            out($stmt->fetchAll(PDO::FETCH_COLUMN));
            break;

        case 'list_races':
            $bucket = gp('venue_bucket', gp('venue', 'ALL'));
            if ($bucket !== 'ST' && $bucket !== 'HV' && $bucket !== 'Sx' && $bucket !== 'ALL' && $bucket !== '') {
                $bucket = PropFingerprint::venueBucket($bucket);
            }
            if ($bucket === '') $bucket = 'ALL';

            $dateFrom = gp('date_from');
            $dateTo = gp('date_to');
            $condRaw = bp('condition');
            if (is_string($condRaw) && $condRaw !== '') {
                $cond = json_decode($condRaw, true);
            } else {
                $cond = is_array($condRaw) ? $condRaw : array();
            }
            if (!is_array($cond)) $cond = array();

            if ($bucket !== 'ALL') {
                if (!isset($cond['all'])) $cond['all'] = array();
                array_unshift($cond['all'], array('venue_bucket' => $bucket));
            }

            $fps = load_fingerprints($db, $bucket === 'ALL' ? null : $bucket, $dateFrom, $dateTo);
            if (empty($fps)) {
                rebuild_fingerprints($db, $dateFrom, $dateTo);
                $fps = load_fingerprints($db, $bucket === 'ALL' ? null : $bucket, $dateFrom, $dateTo);
            }

            $rules = load_rules($db, true);
            $list = array();
            foreach ($fps as $fp) {
                if (!empty($cond) && !PropManualRuleEngine::matchCondition($fp, $cond)) continue;
                $list[] = array(
                    'racingdate' => $fp['racingdate'],
                    'venue' => $fp['venue'],
                    'venue_bucket' => $fp['venue_bucket'],
                    'raceno' => $fp['raceno'],
                    'distance' => $fp['distance'],
                    'prop_string' => $fp['prop_string'],
                    'cells' => $fp['cells'],
                    'rule_hits' => count_rule_hits($fp, $rules),
                    'n_horses' => $fp['n_horses'],
                );
            }
            out(array('total' => count($list), 'condition' => $cond, 'races' => $list));
            break;

        case 'propose_picks':
            $body = body_json();
            $keys = isset($body['races']) ? $body['races'] : array();
            $fpMax = isset($body['fp_max']) ? (int)$body['fp_max'] : 4;
            if (empty($keys)) {
                out(array('error' => 'races required'));
                break;
            }
            $fps = array();
            $missing = array();
            $stmt = $db->prepare("SELECT * FROM prop_race_fingerprint WHERE racingdate=? AND venue=? AND raceno=?");
            foreach ($keys as $k) {
                $stmt->execute(array($k['racingdate'], $k['venue'], (int)$k['raceno']));
                $row = $stmt->fetch();
                if ($row) {
                    $fps[] = row_to_fp($row);
                } else {
                    $missing[] = $k['racingdate'] . ' ' . $k['venue'] . ' R' . $k['raceno'];
                }
            }
            $prop = PropManualRuleEngine::proposePicksFromEvidence($fps, $fpMax);
            $prop['loaded'] = count($fps);
            $prop['requested'] = count($keys);
            $prop['missing'] = $missing;
            if (empty($fps)) {
                $prop['error'] = 'No fingerprints found for checked races — Rebuild FP first';
            } elseif (empty($prop['marks']) && (int)$prop['races_with_fp'] === 0) {
                $prop['hint'] = 'Checked races have no FP≤' . $fpMax . ' cells in fingerprint — check borders on the list or Rebuild FP';
            }
            out($prop);
            break;

        case 'save_rule':
            $body = body_json();
            $scope = isset($body['venue_scope']) ? $body['venue_scope'] : 'Sx';
            $name = isset($body['name']) ? trim($body['name']) : '';
            $note = isset($body['note_zh']) ? $body['note_zh'] : '';
            $cond = isset($body['condition']) ? $body['condition'] : array();
            $pick = isset($body['pick']) ? $body['pick'] : array('marks' => array());
            $enabled = isset($body['enabled']) ? (int)$body['enabled'] : 0;
            $evidence = isset($body['evidence']) ? $body['evidence'] : array();
            $id = isset($body['id']) ? (int)$body['id'] : 0;
            $fpMax = isset($body['fp_max']) ? (int)$body['fp_max'] : 4;

            if ($name === '') {
                out(array('error' => 'name required'));
                break;
            }
            if (!is_array($cond)) $cond = array();
            if ($scope !== 'ALL') {
                if (!isset($cond['all'])) $cond['all'] = array();
                $hasV = false;
                foreach ($cond['all'] as $c) {
                    if (isset($c['venue_bucket'])) { $hasV = true; break; }
                }
                if (!$hasV) array_unshift($cond['all'], array('venue_bucket' => $scope));
            }

            if ($id > 0) {
                $stmt = $db->prepare("UPDATE prop_manual_rule SET name=?, venue_scope=?, enabled=?, condition_json=?, pick_json=?, note_zh=? WHERE id=?");
                $stmt->execute(array($name, $scope, $enabled, json_encode($cond), json_encode($pick), $note, $id));
                $ruleId = $id;
                $q = $db->prepare("SELECT code FROM prop_manual_rule WHERE id=?");
                $q->execute(array($id));
                $code = $q->fetchColumn();
            } else {
                $code = next_rule_code($db, $scope);
                $stmt = $db->prepare("INSERT INTO prop_manual_rule (code, name, venue_scope, enabled, condition_json, pick_json, note_zh) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute(array($code, $name, $scope, $enabled, json_encode($cond), json_encode($pick), $note));
                $ruleId = (int)$db->lastInsertId();
            }

            if (!empty($evidence)) {
                $db->prepare("DELETE FROM prop_manual_rule_evidence WHERE rule_id=?")->execute(array($ruleId));
                $eins = $db->prepare("INSERT INTO prop_manual_rule_evidence (rule_id, racingdate, venue, raceno, prop_string, result_json) VALUES (?,?,?,?,?,?)");
                $qfp = $db->prepare("SELECT * FROM prop_race_fingerprint WHERE racingdate=? AND venue=? AND raceno=?");
                foreach ($evidence as $ev) {
                    $result = isset($ev['result']) ? $ev['result'] : null;
                    $propStr = isset($ev['prop_string']) ? $ev['prop_string'] : '';
                    if ($result === null) {
                        $qfp->execute(array($ev['racingdate'], $ev['venue'], $ev['raceno']));
                        $row = $qfp->fetch();
                        if ($row) {
                            $fp = row_to_fp($row);
                            $propStr = $fp['prop_string'];
                            $result = evidence_cells($fp, $fpMax);
                        } else {
                            $result = array();
                        }
                    }
                    $eins->execute(array($ruleId, $ev['racingdate'], $ev['venue'], $ev['raceno'], $propStr, json_encode($result)));
                }
            }
            out(array('ok' => true, 'id' => $ruleId, 'code' => $code));
            break;

        case 'list_rules':
            $rules = load_rules($db, gp('enabled_only') === '1', gp('venue_scope'));
            $stmt = $db->query("SELECT rule_id, COUNT(*) c FROM prop_manual_rule_evidence GROUP BY rule_id");
            $ec = array();
            foreach ($stmt->fetchAll() as $r) $ec[$r['rule_id']] = (int)$r['c'];
            foreach ($rules as &$r) {
                $r['evidence_n'] = isset($ec[$r['id']]) ? $ec[$r['id']] : 0;
            }
            unset($r);
            out(array('rules' => $rules));
            break;

        case 'get_rule':
            $id = (int)gp('id');
            $stmt = $db->prepare("SELECT * FROM prop_manual_rule WHERE id=?");
            $stmt->execute(array($id));
            $r = $stmt->fetch();
            if (!$r) { out(array('error' => 'not found')); break; }
            $r = normalize_rule($r);
            $ev = $db->prepare("SELECT * FROM prop_manual_rule_evidence WHERE rule_id=?");
            $ev->execute(array($id));
            $r['evidence'] = $ev->fetchAll();
            out($r);
            break;

        case 'toggle_rule':
            $id = (int)bp('id', gp('id'));
            $en = bp('enabled', gp('enabled'));
            if ($en === null || $en === '') {
                $db->prepare("UPDATE prop_manual_rule SET enabled = 1 - enabled WHERE id=?")->execute(array($id));
            } else {
                $db->prepare("UPDATE prop_manual_rule SET enabled=? WHERE id=?")->execute(array((int)$en, $id));
            }
            out(array('ok' => true));
            break;

        case 'delete_rule':
            $id = (int)bp('id', gp('id'));
            $db->prepare("DELETE FROM prop_manual_rule_evidence WHERE rule_id=?")->execute(array($id));
            $db->prepare("DELETE FROM prop_manual_rule_stat WHERE rule_id=?")->execute(array($id));
            $db->prepare("DELETE FROM prop_manual_rule WHERE id=?")->execute(array($id));
            out(array('ok' => true));
            break;

        case 'backtest':
            $id = (int)gp('id');
            $fpMax = (int)gp('fp_max', 4);
            $dateFrom = gp('date_from');
            $dateTo = gp('date_to');
            $stmt = $db->prepare("SELECT * FROM prop_manual_rule WHERE id=?");
            $stmt->execute(array($id));
            $rule = $stmt->fetch();
            if (!$rule) { out(array('error' => 'not found')); break; }
            $rule = normalize_rule($rule);

            $scope = $rule['venue_scope'];
            $fps = load_fingerprints($db, $scope === 'ALL' ? null : $scope, $dateFrom, $dateTo);
            $bt = PropManualRuleEngine::backtestRule($rule, $fps, $fpMax);

            $df = $dateFrom ? $dateFrom : '1970-01-01';
            $dt = $dateTo ? $dateTo : '2099-12-31';
            $db->prepare("REPLACE INTO prop_manual_rule_stat
                (rule_id, venue_scope, date_from, date_to, fp_max, races_matched, picks_total, picks_placed, race_hit, hit_rate, pick_hit_rate, computed_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())")->execute(array(
                $id, $scope, $df, $dt, $fpMax,
                $bt['races_matched'], $bt['picks_total'], $bt['picks_placed'], $bt['race_hit'],
                $bt['hit_rate'], $bt['pick_hit_rate']
            ));

            $bt['rule'] = array('id' => $id, 'code' => $rule['code'], 'name' => $rule['name']);
            if (count($bt['details']) > 200) {
                $bt['details'] = array_slice($bt['details'], 0, 200);
                $bt['details_truncated'] = true;
            }
            out($bt);
            break;

        case 'backtest_all':
            $fpMax = (int)gp('fp_max', 4);
            $dateFrom = gp('date_from');
            $dateTo = gp('date_to');
            $rules = load_rules($db, gp('enabled_only') === '1', gp('venue_scope'));
            $results = array();
            foreach ($rules as $rule) {
                $scope = $rule['venue_scope'];
                $fps = load_fingerprints($db, $scope === 'ALL' ? null : $scope, $dateFrom, $dateTo);
                $bt = PropManualRuleEngine::backtestRule($rule, $fps, $fpMax);
                unset($bt['details']);
                $bt['id'] = $rule['id'];
                $bt['code'] = $rule['code'];
                $bt['name'] = $rule['name'];
                $bt['venue_scope'] = $scope;
                $bt['enabled'] = $rule['enabled'];
                $results[] = $bt;
            }
            $stmt = $db->query("SELECT rule_id, COUNT(*) c FROM prop_manual_rule_evidence GROUP BY rule_id");
            $ec = array();
            foreach ($stmt->fetchAll() as $r) $ec[$r['rule_id']] = (int)$r['c'];
            foreach ($results as &$r) {
                $r['evidence_n'] = isset($ec[$r['id']]) ? $ec[$r['id']] : 0;
            }
            unset($r);
            out(array('fp_max' => $fpMax, 'results' => $results));
            break;

        case 'eval_string':
            $body = body_json();
            $propStr = isset($body['prop_string']) ? $body['prop_string'] : gp('prop_string', '');
            $venueBucket = isset($body['venue_bucket']) ? $body['venue_bucket'] : gp('venue_bucket', 'Sx');
            $enabledOnly = !isset($body['enabled_only']) || $body['enabled_only'];
            $rules = load_rules($db, $enabledOnly, $venueBucket);
            $result = PropManualRuleEngine::evaluateString($propStr, $rules, $venueBucket);
            $result['rules_loaded'] = count($rules);
            $result['enabled_only'] = $enabledOnly ? 1 : 0;
            if (isset($result['fingerprint'])) {
                $result['cells'] = $result['fingerprint']['cells'];
                unset($result['fingerprint']['bands']);
            }
            out($result);
            break;

        case 'mine_rules':
            $body = body_json();
            $venue = isset($body['venue_bucket']) ? $body['venue_bucket'] : gp('venue_bucket', 'Sx');
            $dateFrom = isset($body['date_from']) ? $body['date_from'] : gp('date_from');
            $dateTo = isset($body['date_to']) ? $body['date_to'] : gp('date_to');
            $fpMax = isset($body['fp_max']) ? (int)$body['fp_max'] : (int)gp('fp_max', 4);
            $maxPicks = isset($body['max_picks']) ? (int)$body['max_picks'] : 3;
            $minMatched = isset($body['min_matched']) ? (int)$body['min_matched'] : 20;
            $maxDepth = isset($body['max_depth']) ? (int)$body['max_depth'] : 2;
            $holdout = isset($body['holdout_frac']) ? (float)$body['holdout_frac'] : 0.0;
            if ($fpMax !== 3 && $fpMax !== 4) $fpMax = 4;
            if ($maxPicks < 1 || $maxPicks > 5) $maxPicks = 3;

            $fps = load_fingerprints($db, ($venue === 'ALL') ? null : $venue, $dateFrom, $dateTo);
            if (empty($fps)) {
                out(array('error' => 'No fingerprints — Rebuild FP first', 'candidates' => array()));
                break;
            }
            $result = PropManualRuleEngine::mineRules($fps, array(
                'venue_bucket' => $venue,
                'fp_max' => $fpMax,
                'max_picks' => $maxPicks,
                'min_matched' => $minMatched,
                'max_depth' => $maxDepth,
                'holdout_frac' => $holdout,
                'max_candidates' => isset($body['max_candidates']) ? (int)$body['max_candidates'] : 400,
            ));
            $result['fingerprint_n'] = count($fps);
            out($result);
            break;

        default:
            out(array(
                'error' => 'unknown action',
                'actions' => array(
                    'install', 'diagnose', 'rebuild_fingerprints', 'dates', 'venues',
                    'list_races', 'propose_picks', 'save_rule', 'list_rules',
                    'get_rule', 'toggle_rule', 'delete_rule',
                    'backtest', 'backtest_all', 'eval_string', 'mine_rules'
                ),
            ));
    }
} catch (Exception $e) {
    // Keep 200 so UI .done() can show the message; also include detail
    out(array('error' => $e->getMessage(), 'ok' => false));
}
