
<?php

include_once("func_hs.php");
require_once("../lib/constants.php");
require_once("../lib/func_mailer_gmail.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getYesterdaysDate() {
    $yesterday = strtotime('-1 day');
    $weekday = date('w', $yesterday);
    
    if ($weekday == 0 || $weekday == 6) {
        return null;
    }
    
    return date('Y-m-d', $yesterday);
}

// Parameters for the URL
$interval = "1d";
$period1 = '2024-06-24';//date('Y-m-d');
$period2 = $period1;

// URL to scrape
$url = "https://query1.finance.yahoo.com/v8/finance/chart/%5EHSI?events=capitalGain%7Cdiv%7Csplit&formatted=true&includeAdjustedClose=true&interval=" . $interval . "&period1=" . strtotime($period1) . "&period2=" . strtotime($period2) . "&symbol=%5EHSI&userYfid=true&lang=en-US&region=US";
// Create a stream context with the User-Agent header
$context = stream_context_create([
    'http' => [
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3"
    ]
]);
// Fetch the data
$response = file_get_contents($url, false, $context);

// Decode the JSON
$data = json_decode($response, true);

$meta = array();
$indicators = array();
// Extract the meta and indicator data
$meta = $data['chart']['result'][0]['meta'];
$indicators = $data['chart']['result'][0]['indicators'];

// Prepare the SQL statements
$sql_meta = "INSERT INTO funds_hsi (tradeday, currency, symbol, exchangeName, fullExchangeName, instrumentType, firstTradeDate, regularMarketTime, hasPrePostMarketData, gmtoffset, timezone, exchangeTimezoneName, regularMarketPrice, fiftyTwoWeekHigh, fiftyTwoWeekLow, regularMarketDayHigh, regularMarketDayLow, regularMarketVolume, chartPreviousClose, priceHint) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$sql_indicator = "INSERT INTO funds_hsi_indicators (tradeday, high, low, close, volume, open, adjclose) VALUES (?, ?, ?, ?, ?, ?, ?)";

// Prepare the statements
$stmt_indicator = $conn->prepare($sql_indicator);
if (!$stmt_indicator) {
    echo "Error preparing SQL statement: " . $conn->error;
    exit;
}

// Prepare the statements
$stmt_meta = $conn->prepare($sql_meta);
if (!$stmt_meta) {
    echo "Error preparing SQL statement: " . $conn->error;
    exit;
}
// Convert $meta['regularMarketTime'] to Hong Kong time zone
$hkTimezone = new DateTimeZone('Asia/Hong_Kong');
$regularMarketTime = (new DateTime('@' . $meta['regularMarketTime'], new DateTimeZone('UTC')))->setTimezone($hkTimezone)->format('Y-m-d H:i:s');

// Bind the parameters and execute the statements
$stmt_meta->bind_param("ssssssssisssddddddid", 
    $period1,
    $meta['currency'], 
    $meta['symbol'], 
    $meta['exchangeName'], 
    $meta['fullExchangeName'], 
    $meta['instrumentType'], 
    date('Y-m-d', $meta['firstTradeDate']), 
    $regularMarketTime,
    $meta['hasPrePostMarketData'], 
    $meta['gmtoffset'], 
    $meta['timezone'], 
    $meta['exchangeTimezoneName'], 
    $meta['regularMarketPrice'], 
    $meta['fiftyTwoWeekHigh'], 
    $meta['fiftyTwoWeekLow'], 
    $meta['regularMarketDayHigh'], 
    $meta['regularMarketDayLow'], 
    $meta['regularMarketVolume'], 
    $meta['chartPreviousClose'], 
    $meta['priceHint']
);
$stmt_meta->execute();

$stmt_indicator->bind_param("sddidddd", 
    $period1,
    $indicators['quote'][0]['high'][0], 
    $indicators['quote'][0]['low'][0], 
    $indicators['quote'][0]['close'][0], 
    $indicators['quote'][0]['volume'][0], 
    $indicators['quote'][0]['open'][0], 
    $indicators['adjclose'][0]['adjclose'][0]
);
$stmt_indicator->execute();
 
?>