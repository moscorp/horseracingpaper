<?php
// debug_api.php - Test the HKJC API directly

$apiUrl = 'https://info.cld.hkjc.com/graphql/base/';

// Test 1: Simple query
$query = '{ activeMeetings { id venueCode date } }';

echo "Testing API with query: " . $query . "\n\n";

$payload = json_encode(['query' => $query]);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";

if ($error) {
    echo "CURL Error: " . $error . "\n";
}

if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "\nDecoded data:\n";
    print_r($data);
}