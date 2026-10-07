<?php
require_once 'lib/constants.php'; // creates $pdo
require_once 'lib/func_hkjc.php'; // functions above

$endpoint = "https://info.cld.hkjc.com/graphql/base/";
$now = new DateTime('now', new DateTimeZone('Asia/Hong_Kong')); // adjust if your server TZ differs

$start = (clone $now)->setTime(21, 30, 0);
$end   = (clone $now)->setTime(21, 45, 0);

// Only run the polling loop if we're within the window
if ($now < $start || $now > $end) {
    echo "SKIP - outside 21:30-09:45 window. Now=" . $now->format('H:i:s') . "\n";
    exit;
}

function hkjc_poll_marksix_until_done(
    PDO $pdo,
    string $endpointUrl,
    int $pollSeconds = 30,
    int $maxSeconds = 600
): void {
    $endAt = time() + $maxSeconds;

    while (time() < $endAt) {
        $resp = hkjc_marksixDraw_fetch_draws($endpointUrl);
        $rows = hkjc_extract_marksix_rows_from_response($resp);

        hkjc_marksix_upsert_rows_by_year_no($pdo, $rows);

        if (hkjc_any_draw_has_7_numbers($rows)) {
            break;
        }

        sleep($pollSeconds);
    }
}

// Poll up to 15 minutes, but since cron triggers inside 09:30-09:45,
// this keeps collecting when results take time to appear.
hkjc_poll_marksix_until_done($pdo, $endpoint, 30, 900);

echo "OK - done polling\n";