<?php
/**
 * Mark Six Draw Scraper — 落球序 (drop order) from drop.html
 *
 * Parses the 落球序 column (red #FF0000 + special #660099), not 順序排序.
 *
 * Usage:
 *   php M6_scraper_all.php                  # fetch URL, fallback to ./drop.html
 *   php M6_scraper_all.php drop.html        # read local file
 */

include_once 'lib/constants.php';

date_default_timezone_set('Asia/Hong_Kong');

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

/**
 * Load HTML from CLI path, local drop.html, or remote URL.
 */
function loadDropHtml(array $argv): string
{
    $localDefault = __DIR__ . '/drop.html';

    if (isset($argv[1]) && $argv[1] !== '') {
        $path = $argv[1];
        if (!is_file($path)) {
            throw new RuntimeException("File not found: {$path}");
        }
        $html = file_get_contents($path);
        if ($html === false || $html === '') {
            throw new RuntimeException("Failed to read file: {$path}");
        }
        echo "Loaded local file: {$path} (" . strlen($html) . " bytes)\n";
        return $html;
    }

    $url = 'http://www.9800.com.tw/lotto6/drop.html';
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $html = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($html) {
        echo "Fetched URL ({$url}), size: " . strlen($html) . " bytes\n";
        return $html;
    }

    echo "URL fetch failed" . ($err ? " ({$err})" : '') . ", trying local drop.html...\n";
    if (!is_file($localDefault)) {
        throw new RuntimeException('Failed to fetch page and no local drop.html found.');
    }
    $html = file_get_contents($localDefault);
    if ($html === false || $html === '') {
        throw new RuntimeException('Failed to read local drop.html.');
    }
    echo "Loaded local fallback: {$localDefault} (" . strlen($html) . " bytes)\n";
    return $html;
}

/**
 * Parse 落球序 rows from drop.html table.
 * Each row: 期次 | 開獎日期 | 週幾 | 落球序 (6 reds + special) | 順序排序
 */
function parseDropOrderDraws(string $html): array
{
    $pattern = '/<TR[^>]*>.*?'
        . 'color="#800000"[^>]*>(\d{6})<\/font>.*?'
        . 'color="#000080"[^>]*>(\d{4}-\d{2}-\d{2})<\/font>.*?'
        . '週([一二三四五六日]).*?'
        . 'color="#FF0000">\s*([\d&nbsp;\s]+)<\/font>\s*\+\s*'
        . '<font[^>]*color="#660099">\s*(\d{1,2})\s*'
        . '/is';

    if (!preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
        return [];
    }

    $draws = [];
    foreach ($matches as $m) {
        $code = $m[1];
        $date = $m[2];
        $weekday = $m[3];
        $dropBlock = str_replace('&nbsp;', ' ', $m[4]);
        $special = intval($m[5]);

        preg_match_all('/\d{1,2}/', $dropBlock, $numParts);
        $numbers = array_map('intval', $numParts[0]);
        $numbers = array_values(array_filter($numbers, function ($n) {
            return $n >= 1 && $n <= 49;
        }));

        if (count($numbers) !== 6) {
            echo "Skip {$code}: expected 6 drop-order numbers, got " . count($numbers) . "\n";
            continue;
        }

        $draws[] = [
            'code'    => $code,
            'date'    => $date,
            'weekday' => $weekday,
            'numbers' => $numbers,
            'special' => $special,
        ];
    }

    usort($draws, function ($a, $b) {
        return $b['code'] <=> $a['code'];
    });

    return $draws;
}

function codeToYearNos(string $code): array
{
    return [
        'year' => '2' . substr($code, 0, 3),
        'nos'  => intval(substr($code, -2)),
    ];
}

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Mark Six Drop-Order Scraper (落球序) ===\n";

    $html  = loadDropHtml($argv ?? []);
    $draws = parseDropOrderDraws($html);

    if (empty($draws)) {
        echo "No 落球序 rows parsed. Check HTML format or use: php M6_scraper_all.php drop.html\n";
        exit(1);
    }

    echo "\nParsed " . count($draws) . " draw(s) from 落球序 column:\n";
    foreach ($draws as $d) {
        echo "  {$d['code']}: {$d['date']} - "
            . implode(' ', array_map(function ($n) {
                return str_pad((string) $n, 2, '0', STR_PAD_LEFT);
            }, $d['numbers']))
            . ' + ' . str_pad((string) $d['special'], 2, '0', STR_PAD_LEFT) . "\n";
    }

    $weekdayMap = ['一' => '一', '二' => '二', '三' => '三', '四' => '四', '五' => '五', '六' => '六', '日' => '日'];

    $insertStmt = $pdo->prepare("
        INSERT INTO `00m6` (year, nos, no1, no2, no3, no4, no5, no6, no7, memo)
        VALUES (:year, :nos, :no1, :no2, :no3, :no4, :no5, :no6, :no7, :memo)
    ");
    $existsStmt = $pdo->prepare('SELECT COUNT(*) FROM `00m6` WHERE year = :year AND nos = :nos');

    $saved = 0;
    $skipped = 0;

    foreach ($draws as $draw) {
        $ids = codeToYearNos($draw['code']);
        $year = $ids['year'];
        $nos  = $ids['nos'];

        $existsStmt->execute([':year' => $year, ':nos' => $nos]);
        if ((int) $existsStmt->fetchColumn() > 0) {
            echo "Skip {$year}-{$nos}: already in DB\n";
            $skipped++;
            continue;
        }

        // $weekdayChinese = $weekdayMap[$draw['weekday']] ?? $draw['weekday'];
        // $memo = "{$draw['date']} 星期{$weekdayChinese}";
        $dateTime = DateTime::createFromFormat('Y-m-d', $draw['date']);
        $dayOfWeek = $dateTime->format('D'); // Mon, Tue, Wed, Thu, Fri, Sat, Sun
        $memo = "{$draw['date']} {$dayOfWeek}";

        $insertStmt->execute([
            ':year' => $year,
            ':nos'  => $nos,
            ':no1'  => $draw['numbers'][0],
            ':no2'  => $draw['numbers'][1],
            ':no3'  => $draw['numbers'][2],
            ':no4'  => $draw['numbers'][3],
            ':no5'  => $draw['numbers'][4],
            ':no6'  => $draw['numbers'][5],
            ':no7'  => $draw['special'],
            ':memo' => $memo,
        ]);

        echo "Saved {$year}-{$nos}: "
            . implode(' ', $draw['numbers']) . " + {$draw['special']}\n";
        $saved++;
    }

    echo "\nDone. Saved: {$saved}, skipped (duplicate): {$skipped}\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
