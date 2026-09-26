
<?php

// include_once("func_hs.php");
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

$symbol = "^HSI"; // Hang Seng Index symbol
$apiUrl = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}";

$params = array(
    "period1" => strtotime("-1 year"),
    "period2" => time(),
    "interval" => "1d",
    "events" => "history",
    "includePrePost" => true
);

$url = $apiUrl . "?" . http_build_query($params);

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3"
    ),
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false
));

$response = curl_exec($curl);
$error = curl_error($curl);
curl_close($curl);

if (!empty($error)) {
    echo "Error: $error";
    return;
}

$data = json_decode($response, true);
if (isset($data['chart']['result'][0])) {
    $result = $data['chart']['result'][0];
    $meta = $result['meta'];
    $indicators = $result['indicators'];

    // Extract the relevant data
    $dates = $result['timestamp'];
    $openPrices = $indicators['quote'][0]['open'];
    $closePrices = $indicators['quote'][0]['close'];
    $highPrices = $indicators['quote'][0]['high'];
    $lowPrices = $indicators['quote'][0]['low'];
    $volumes = $indicators['quote'][0]['volume'];
    $adjClosePrices = $indicators['adjclose'][0]['adjclose'];


   // Process the data and save to the database
    foreach ($dates as $index => $date) {
        $dateString = date("Y-m-d", $date);

        // Check if the data for the current date already exists in the table
        $sql = "SELECT COUNT(*) FROM funds_hsi_indicators WHERE tradeday = '$dateString'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        if ($row["COUNT(*)"] == 0) {
            // Check if all the required columns have data
            if (!is_null($highPrices[$index]) && !is_null($lowPrices[$index]) && !is_null($closePrices[$index]) && !is_null($volumes[$index]) && !is_null($openPrices[$index]) && !is_null($adjClosePrices[$index]) && $volumes[$index]>0) {
                $sql = "INSERT INTO funds_hsi_indicators (tradeday, high, low, close, volume, open, adjclose)
                        VALUES ('$dateString', {$highPrices[$index]}, {$lowPrices[$index]}, {$closePrices[$index]}, {$volumes[$index]}, {$openPrices[$index]}, {$adjClosePrices[$index]})";
                if ($conn->query($sql) === TRUE) {
                    // echo "New record created for $dateString.\n";
                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error . "\n";
                }
            } else {
                echo "Skipping $dateString due to missing data.\n";
            }
        } else {
            echo "Data for $dateString already exists in the database.\n";
        }
    }

    $conn->close();
} else {
    echo "Error: Unable to retrieve data from the API.";
}
 
?>