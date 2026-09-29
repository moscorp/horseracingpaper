<?php

require_once("../lib/constants.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($mysqli, "utf8mb4");

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($conn, "utf8mb4");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// API URL
$apiUrl = "https://www.hsi.com.hk/data/eng/rt/index-series/hsi/performance.do?7164";

// Initialize cURL
$ch = curl_init($apiUrl);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
]);

// Execute the request
$response = curl_exec($ch);

// Close cURL
curl_close($ch);

// Decode the JSON response
$result = json_decode($response, true);

// Check for successful response
if (isset($result['indexSeriesList'])) {
    $indexList = $result['indexSeriesList'][0]['indexList'];
    
    foreach ($indexList as $index) {
        if ($index['indexName'] == "Hang Seng Index") {
            $tradeDate = date('Y-m-d', strtotime($index['lastUpdate'])); // Parse trade date
            $closePrice = $index['indexValue']; // Closing price
            $highPrice = $index['todayHigh']; // Today's high
            $lowPrice = $index['todayLow']; // Today's low
            $openPrice = $index['previousClose']; // Open price (using previous close as a proxy)
            $volume = 0; // Set volume to 0 as it's not provided in the response
            $adjClose = $closePrice; // Set adjusted close to the closing price

            // Check if the data for the current date already exists in the table
            $sql = "SELECT COUNT(*) FROM funds_hsi_indicators WHERE tradeday = '$tradeDate'";
            $checkResult = $conn->query($sql);
            $checkRow = $checkResult->fetch_assoc();

            if ($checkRow["COUNT(*)"] == 0) {
                // Insert the data into the database
                $sqlInsert = "INSERT INTO funds_hsi_indicators (tradeday, high, low, close, volume, open, adjclose)
                              VALUES ('$tradeDate', $highPrice, $lowPrice, $closePrice, $volume, $openPrice, $adjClose)";

                if ($conn->query($sqlInsert) === TRUE) {
                    echo "Inserted data for $tradeDate with closing price $closePrice.\n";
                } else {
                    echo "Database Error for $tradeDate: " . $sqlInsert . "<br>" . $conn->error . "\n";
                }
            } else {
                echo "Data for $tradeDate already exists in the database.\n";
            }
        }
    }
} else {
    echo 'Error fetching data: ' . $result['msg'];
}

$conn->close();

?>