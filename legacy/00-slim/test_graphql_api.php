<?php
// test_graphql_api.php - 测试 GraphQL API

$date = '2026-04-19'; // 测试日期
$venueCode = 'ST';

$query = '{
  raceMeetings(date: "' . $date . '", venueCode: "' . $venueCode . '") {
    id
    venueCode
    date
    status
    totalNumberOfRace
    races {
      no
      postTime
      status
      wageringFieldSize
    }
  }
}';

$payload = json_encode(['query' => $query]);

$ch = curl_init('https://info.cld.hkjc.com/graphql/base/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($curlError) echo "CURL Error: $curlError\n";

$data = json_decode($response, true);

if ($data && !isset($data['errors'])) {
    echo "✓ API 正常响应\n";
    echo "返回数据: " . json_encode($data['data']['raceMeetings'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "✗ API 错误:\n";
    print_r($data['errors'] ?? $response);
}