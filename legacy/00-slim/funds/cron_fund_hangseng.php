<?php

require_once("../lib/constants.php");
require_once("../lib/func_mailer_gmail.php");

$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($mysqli, "utf8mb4");

// Check connection
if (!$mysqli) {
    die("Connection failed: " . mysqli_connect_error());
}

$var = "&fundPricePeriodFrom=2026-01-28&fundPricePeriodTo=2026-01-28";

// URL to scrape
$url = "https://rbwm-api.hsbc.com.hk/wpb-gpbw-mmw-hk-hase-pa-p-wpp-mpf-market-data-prod-proxy/v1/funds?schemeCodes=HS&includes=fundPrice";  //.$var;

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute cURL request
$response = curl_exec($ch);
curl_close($ch);

// Decode the JSON response
$data = json_decode($response, true);

// Check if data exists
if (isset($data['data']['schemeInfos'])) {
    $insertQueries = []; // Array to hold insert queries

    foreach ($data['data']['schemeInfos'] as $scheme) {
        if (isset($scheme['fundPriceInfos'])) {
            foreach ($scheme['fundInfos'] as $fundInfos) {
                $fundCode = $fundInfos['fundCode'];
                $fundnamecn[$fundCode] = $fundInfos['fundNames'][1]['value'];
            }
            foreach ($scheme['fundPriceInfos'] as $fundPriceInfo) {
                $fundCode = $fundPriceInfo['fundCode'];

                foreach ($fundPriceInfo['fundPrices'] as $priceInfo) {
                    // Get price date
                    $priceDate = new DateTime($priceInfo['priceDate']);
                    $formattedDate = $priceDate->format('Y-m-d');

                    // Get fund buy price and currency code
                    $fundBuyPrice = $priceInfo['fundBuyPrice']['amount'];
                    $fundCurrencyCode = $priceInfo['fundBuyPrice']['fundCurrencyCode'];

                    // Query to insert data if the date does not exist
                    $insertQuery = "INSERT INTO funds (UNIT_PRICE_DATE, FUND_CODE, FUND_NAME, FUND_CURRENCY, PRICE, rectime) 
                                    VALUES('$formattedDate', '$fundCode', '{$fundnamecn[$fundCode]}', '$fundCurrencyCode', '$fundBuyPrice', now())
                                    ON DUPLICATE KEY UPDATE PRICE = VALUES(PRICE);";

                    // Store the query in the array
                    $insertQueries[] = $insertQuery;
                }
            }
        }
    }

    // Execute each insert query
    foreach ($insertQueries as $insert) {
        if (!mysqli_query($mysqli, $insert)) {
            echo "Error executing query: " . mysqli_error($mysqli) . "<br>";
        }
    }
} else {
    echo "No data found!";
}

// Close the database connection
mysqli_close($mysqli);
?>