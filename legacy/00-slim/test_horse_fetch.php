<?php
// test_horse_fetch.php - Test fetching a specific horse

$horseCode = 'H470'; // One of the failing horses from your log
$url = "https://racing.hkjc.com/zh-hk/local/information/horse?HorseNo=" . urlencode($horseCode);

echo "Testing URL: $url\n\n";

// Method 1: Basic curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in output

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
if ($curlError) {
    echo "CURL Error: $curlError\n";
}

if ($httpCode === 200) {
    echo "✓ Page accessible!\n";
    echo "Response length: " . strlen($response) . " bytes\n";
    
    // Check if page contains "No such horse" or similar
    if (strpos($response, '沒有此馬匹') !== false || strpos($response, 'No such horse') !== false) {
        echo "✗ Horse not found - possibly invalid or retired code\n";
    } elseif (strpos($response, 'blocked') !== false || strpos($response, 'access denied') !== false) {
        echo "✗ Access blocked by HKJC\n";
    } else {
        echo "✓ Page loaded successfully!\n";
    }
} else {
    echo "✗ Failed to access page\n";
}