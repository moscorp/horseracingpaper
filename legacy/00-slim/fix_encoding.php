<?php
/**
 * Fix encoding for horsename and go_ch in racepropresult.
 * Step 1: Show broken rows (preview)
 * Step 2: Prompt to fix in browser
 *
 * Usage: fix_encoding.php?action=check   (preview)
 *        fix_encoding.php?action=fix     (update DB)
 *        fix_encoding.php?action=rollback (restore from backup)
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'lib/constants.php';
require_once 'PropAnalyzer.php';

$dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
$db = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$action = isset($_GET['action']) ? $_GET['action'] : 'check';

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fix Encoding</title>';
echo '<style>body{font-family:monospace;background:#111;color:#ccc;padding:20px}
table{border-collapse:collapse;margin-bottom:15px}
td,th{padding:4px 10px;border:1px solid #333;font-size:13px}
.bad{background:#3a1a1a}.good{background:#1a3a1a}.ok{color:#5f5}
.btn{padding:8px 16px;border:none;cursor:pointer;font-size:14px;margin-right:10px}
.btn-fix{background:#c44;color:#fff}.btn-check{background:#48f;color:#fff}
</style></head><body>';

echo '<h2>Encoding Fix Tool</h2>';
echo '<a href="?action=check" class="btn btn-check">Refresh Check</a> ';
echo '<a href="?action=fix" class="btn btn-fix" onclick="return confirm(\'Apply fixes? BACKUP FIRST!!\')">Apply Fixes</a>';
echo '<p style="color:#888">Backup: CREATE TABLE racepropresult_bak AS SELECT * FROM racepropresult;</p>';

if ($action === 'check') {
    $stmt = $db->query("SELECT id, horsename, go_ch FROM racepropresult WHERE horsename IS NOT NULL ORDER BY id");
    $broken = [];
    $total  = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $total++;
        $hn  = (string)$row['horsename'];
        $gc  = (string)$row['go_ch'];
        $id  = $row['id'];

        $hnRealChinese = count_chinese_chars($hn);
        $gcRealChinese = count_chinese_chars($gc);
        $hnLatin1 = count_chinese_chars(@mb_convert_encoding($hn, 'UTF-8', 'latin1'));
        $gcLatin1 = count_chinese_chars(@mb_convert_encoding($gc, 'UTF-8', 'latin1'));

        // Flag both if Latin1 conversion produces MORE Chinese chars than current
        $hnFixable = $hnLatin1 > $hnRealChinese;
        $gcFixable = $gcLatin1 > $gcRealChinese;

        if ($hnFixable || $gcFixable) {
            $broken[] = [
                'id'         => $id,
                'hn_orig'    => $hn,
                'hn_fix'     => $hnFixable ? mb_convert_encoding($hn, 'UTF-8', 'latin1') : $hn,
                'hn_bad'     => $hnFixable,
                'gc_orig'    => $gc,
                'gc_fix'     => $gcFixable ? mb_convert_encoding($gc, 'UTF-8', 'latin1') : $gc,
                'gc_bad'     => $gcFixable,
            ];
        }
    }

    if (count($broken) > 0) {
        echo '<table><tr><th>ID</th><th>Horsename (current)</th><th>→ Fixed</th><th>go_ch (current)</th><th>→ Fixed</th></tr>';
        $shown = 0;
        foreach ($broken as $row) {
            if (++$shown > 30) { echo '<tr><td colspan="5">...' . (count($broken)-30) . ' more broken rows...</td></tr>'; break; }
            $hc = $row['hn_bad'] ? 'bad' : 'good';
            $gc = $row['gc_bad'] ? 'bad' : 'good';
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td class='$hc'>" . htmlspecialchars($row['hn_orig']) . "</td>";
            echo "<td class='ok'>" . htmlspecialchars($row['hn_fix']) . "</td>";
            echo "<td class='$gc'>" . htmlspecialchars($row['gc_orig']) . "</td>";
            echo "<td class='ok'>" . htmlspecialchars($row['gc_fix']) . "</td>";
            echo "</tr>";
        }
        echo '</table>';
    } else {
        echo '<p class="ok">All good — no encoding issues found.</p>';
    }
} elseif ($action === 'fix') {
    $stmt = $db->query("SELECT id, horsename, go_ch FROM racepropresult WHERE horsename IS NOT NULL");
    $fixes  = 0;
    $errors = 0;
    $skipped = 0;

    $upd = $db->prepare("UPDATE racepropresult SET horsename=?, go_ch=? WHERE id=?");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hn  = (string)$row['horsename'];
        $gc  = (string)$row['go_ch'];

        $hnLatin1 = count_chinese_chars(@mb_convert_encoding($hn, 'UTF-8', 'latin1'));
        $gcLatin1 = count_chinese_chars(@mb_convert_encoding($gc, 'UTF-8', 'latin1'));
        $hnNative = count_chinese_chars($hn);
        $gcNative = count_chinese_chars($gc);

        $hnFixable = $hnLatin1 > $hnNative;
        $gcFixable = $gcLatin1 > $gcNative;

        if ($hnFixable || $gcFixable) {
            $fixedHn = $hnFixable ? mb_convert_encoding($hn, 'UTF-8', 'latin1') : $hn;
            $fixedGc = $gcFixable ? mb_convert_encoding($gc, 'UTF-8', 'latin1') : $gc;
            try {
                $upd->execute([$fixedHn, $fixedGc, $row['id']]);
                $fixes++;
            } catch (Exception $e) {
                $errors++;
            }
        } else {
            $skipped++;
        }
    }

    echo "<p class='ok'>Fixed $fixes rows. Errors: $errors. Skipped (already clean): $skipped.</p>";
    echo '<p><a href="?action=check">Run check again to verify</a></p>';
}

function tryFix($str) {
    $encodings = ['GBK', 'BIG5', 'SJIS', 'EUC-JP', 'CP936', 'CP950'];
    $best  = null;
    $bestScore = -999;
    $neutral = 0;

    $backup = $str;
    foreach ($encodings as $enc) {
        $candidate = @mb_convert_encoding($str, 'UTF-8', $enc);
        if ($candidate === false) continue;
        if (!mb_check_encoding($candidate, 'UTF-8')) continue;

        $probs = count_chinese_chars($candidate);
        $score = $probs;
        $len = mb_strlen($candidate, 'UTF-8');

        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $candidate;
        }
    }

    // Fallback: try double-conversion Latin1 → UTF-8
    if ($best === null) {
        $alt = @mb_convert_encoding($str, 'UTF-8', 'SJIS');
        if ($alt !== false && mb_check_encoding($alt, 'UTF-8') && count_chinese_chars($alt) > 0) {
            return $alt;
        }
        // Last shot: assume raw binary is UTF-8
        $utf8 = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
        if ($utf8 !== false && mb_check_encoding($utf8, 'UTF-8')) {
            return $utf8;
        }
    }

    return $best !== null ? $best : $str;
}

function count_chinese_chars($str) {
    $cnt = 0;
    $len = mb_strlen($str, 'UTF-8');
    for ($i = 0; $i < $len; $i++) {
        $char = mb_substr($str, $i, 1, 'UTF-8');
        $codePoint = mb_ord($char, 'UTF-8');
        if ($codePoint >= 0x4E00 && $codePoint <= 0x9FFF) $cnt++;
        if ($codePoint >= 0x3400 && $codePoint <= 0x4DBF) $cnt++;
    }
    return $cnt;
}

echo '</body></html>';