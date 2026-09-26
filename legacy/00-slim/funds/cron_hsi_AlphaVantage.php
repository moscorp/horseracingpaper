<?php
// Alpha Vantage API key (replace with your own key)
$apiKey = "V25B17HE1O5SU4BN";
$symbol = "HSI";
$endpoint = "https://www.alphavantage.co/query";
$params = [
    "function" => "TIME_SERIES_DAILY",
    "symbol" => $symbol,
    "apikey" => $apiKey
];

// Build the query URL
$url = $endpoint . '?' . http_build_query($params);

// Fetch data from API
$response = file_get_contents($url);
$data = json_decode($response, true);

// Extract closing prices for the last 7 days
if (isset($data["Time Series (Daily)"])) {
    $closingPrices = [];
    $count = 0;

    foreach ($data["Time Series (Daily)"] as $date => $info) {
        if ($count >= 7) break;
        $closingPrices[$date] = $info["4. close"];
        $count++;
    }

    // Display results
    foreach ($closingPrices as $date => $close) {
        echo "Date: $date - Closing Price: $close\n";
    }
} else {
    echo "Failed to fetch data.";
}
?>