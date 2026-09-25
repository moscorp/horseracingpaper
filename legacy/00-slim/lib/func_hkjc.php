<?php

function hkjc_marksixDraw_fetch_draws(string $endpointUrl, array $headers = []): array
{
    $payload = [
        'operationName' => 'marksixDraw',
        'variables' => (object)[],
        'query' => 'fragment lotteryDrawsFragment on LotteryDraw {
  id
  year
  no
  openDate
  closeDate
  drawDate
  status
  snowballCode
  snowballName_en
  snowballName_ch
  lotteryPool {
    sell
    status
    totalInvestment
    jackpot
    unitBet
    estimatedPrize
    derivedFirstPrizeDiv
    lotteryPrizes {
      type
      winningUnit
      dividend
    }
  }
  drawResult {
    drawnNo
    xDrawnNo
  }
}

query marksixDraw {
  timeOffset {
    m6
    ts
  }
  lotteryDraws {
    ...lotteryDrawsFragment
  }
}',
    ];

    $defaultHeaders = [
        'Content-Type: application/json; charset=utf-8',
        'Accept: application/json',
        'Origin: https://bet.hkjc.com',
        'Referer: https://bet.hkjc.com/',
    ];

    $ch = curl_init($endpointUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => 'gzip',
        CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
        CURLOPT_TIMEOUT => 30,
    ]);

    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("cURL error: " . $err);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($resp, true);
    if (!is_array($json)) {
        throw new RuntimeException("Invalid JSON response. HTTP {$httpCode}. Body: " . substr($resp, 0, 3000));
    }
    if (isset($json['errors']) && is_array($json['errors'])) {
        throw new RuntimeException("GraphQL errors: " . json_encode($json['errors']));
    }

    return $json; // raw graphql response
}

function hkjc_extract_marksix_rows_from_response(array $graphqlResp): array
{
    $rows = [];

    // Expected shape:
    // data -> lotteryDraws -> [ { id, year, no, drawDate, status, drawResult: { drawnNo, xDrawnNo } }, ... ]
    $draws = $graphqlResp['data']['lotteryDraws'] ?? [];
    if (!is_array($draws)) return $rows;

    // IMPORTANT: do NOT sort. Iterate in given order.
    foreach ($draws as $draw) {
        $id = $draw['id'] ?? null;
        $year = $draw['year'] ?? null;
        $no = $draw['no'] ?? null;

        $status = $draw['status'] ?? null;
        $drawDate = $draw['drawDate'] ?? null;

        $drawnNo = $draw['drawResult']['drawnNo'] ?? [];
        // drawnNo is often an array like [ {"no":..}, {"no":..} ] or just values depending on API
        // We’ll normalize it into an ordered list:
        $drawnOrderNos = [];

        if (is_array($drawnNo)) {
            // If elements are scalar:
            //   drawnNo = ["1","2","..."]
            // If elements are objects:
            //   drawnNo = [{"no":"1"}, ...]
            foreach ($drawnNo as $item) {
                if (is_array($item)) {
                    // try common keys
                    if (array_key_exists('no', $item)) {
                        $drawnOrderNos[] = (string)$item['no'];
                    } elseif (array_key_exists('drawnNo', $item)) {
                        $drawnOrderNos[] = (string)$item['drawnNo'];
                    } else {
                        // fallback: JSON encode
                        $drawnOrderNos[] = json_encode($item);
                    }
                } else {
                    $drawnOrderNos[] = (string)$item;
                }
            }
        }

        // You can decide whether to store only finished draws:
        // status might be something like "CLOSED"/"OPEN"/"DRAWN" etc.
        $rows[] = [
            'id' => $id,
            'year' => $year,
            'no' => $no,
            'drawDate' => $drawDate,
            'status' => $status,
            'drawnOrderNos' => $drawnOrderNos, // preserve API order
            'drawnOrderNos_json' => json_encode($drawnOrderNos, JSON_UNESCAPED_SLASHES),
        ];
    }

    return $rows;
}


function hkjc_marksix_upsert_rows(PDO $pdo, array $rows): void
{
    // Ensure uniqueness: table PK should be id
    $sql = "
        INSERT INTO hkjc_marksix_draws
            (id, year, draw_no, draw_date, status, drawn_order_nos_json)
        VALUES
            (:id, :year, :draw_no, :draw_date, :status, :drawn_order_nos_json)
        ON DUPLICATE KEY UPDATE
            year = VALUES(year),
            draw_no = VALUES(draw_no),
            draw_date = VALUES(draw_date),
            status = VALUES(status),
            drawn_order_nos_json = VALUES(drawn_order_nos_json)
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($rows as $r) {
        if (empty($r['id'])) continue;

        $stmt->execute([
            ':id' => (string)$r['id'],
            ':year' => $r['year'],
            ':draw_no' => (string)($r['no'] ?? ''),
            ':draw_date' => $r['drawDate'], // may need parsing if it's not DATETIME
            ':status' => $r['status'],
            ':drawn_order_nos_json' => $r['drawnOrderNos_json'],
        ]);
    }
}

function hkjc_marksix_upsert_rows_by_year_no(PDO $pdo, array $rows): void
{
    $sql = "
      INSERT INTO hkjc_marksix_draws
        (year, draw_no, status, draw_date, drawn_order_nos_json, drawn_order_nos_count)
      VALUES
        (:year, :draw_no, :status, :draw_date, :json, :cnt)
      ON DUPLICATE KEY UPDATE
        status = VALUES(status),
        draw_date = VALUES(draw_date),
        drawn_order_nos_json = VALUES(drawn_order_nos_json),
        drawn_order_nos_count = VALUES(drawn_order_nos_count),
        updated_at = CURRENT_TIMESTAMP
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($rows as $r) {
        if (empty($r['year']) || empty($r['no'])) continue;

        $cnt = isset($r['drawnOrderNos']) ? count($r['drawnOrderNos']) : 0;
        $stmt->execute([
            ':year' => (int)$r['year'],
            ':draw_no' => (string)$r['no'],
            ':status' => $r['status'],
            ':draw_date' => $r['drawDate'], // ensure parseable to DATETIME or handle conversion
            ':json' => $r['drawnOrderNos_json'],
            ':cnt' => $cnt,
        ]);
    }
}