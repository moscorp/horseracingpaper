<?php

// include_once("func_hs.php");
require_once("../lib/constants.php");
require_once("../lib/func_mailer_gmail.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($conn, "utf8mb4");

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

// Set the start and end dates for the data retrieval
$endDate = time(); // Today
$startDate = strtotime("-1 week", $endDate); // One week ago - reduce time frame for testing

// Loop through each day and retrieve the data
for ($currentDate = $startDate; $currentDate <= $endDate; $currentDate += (60 * 60 * 24)) { // Increment by one day
    $dateString = date("Y-m-d", $currentDate); // Get date string for database check

    $period1 = $currentDate;
    $period2 = $currentDate + (60 * 60 * 24); // End of the current day

    $params = array(
        "period1" => $period1,
        "period2" => $period2,
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
        CURLOPT_TIMEOUT => 30, // Increased timeout
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; rv:91.0) Gecko/20100101 Firefox/91.0", // More realistic User-Agent
            "Accept-Language: en-US,en;q=0.5",  // Add Accept-Language header
        ),
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIESESSION => true, // Enable cookie handling
        CURLOPT_COOKIEJAR => dirname(__FILE__) . '/cookies.txt',   // Cookie jar file
        CURLOPT_COOKIEFILE => dirname(__FILE__) . '/cookies.txt',  // Cookie file to send
    ));

    // Add a delay *before* each API request
    sleep(1); // Increased delay to 10 seconds

    $response = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Get HTTP status code
    curl_close($curl);

    if (!empty($error)) {
        echo "cURL Error for $dateString: $error\n";
        continue; // Skip to the next day
    }

    if ($httpCode != 200) {
        echo "HTTP Error $httpCode for $dateString. Response: $response\n";
        continue; // Skip to the next day
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

        // Check if 'adjclose' exists before accessing it
        if (isset($indicators['adjclose'][0]['adjclose'])) {
            $adjClosePrices = $indicators['adjclose'][0]['adjclose'];
        } else {
            echo "Warning: 'adjclose' data is missing for $dateString.  Using close prices instead.\n";
            $adjClosePrices = $closePrices; // Fallback to close prices
        }


        // Process the data and save to the database
        if (is_array($dates)) { // Check if $dates is an array
            foreach ($dates as $index => $date) {
                $dateString = date("Y-m-d", $date);

                // Check if the data for the current date already exists in the table
                $sql = "SELECT COUNT(*) FROM funds_hsi_indicators WHERE tradeday = '$dateString'";
                $result = $conn->query($sql);
                $row = $result->fetch_assoc();
                if ($row["COUNT(*)"] == 0) {
                    // Check if all the required columns have data
                    if (!is_null($highPrices[$index]) && !is_null($lowPrices[$index]) && !is_null($closePrices[$index]) && !is_null($volumes[$index]) && !is_null($openPrices[$index]) && !is_null($adjClosePrices[$index]) && $volumes[$index] > 0) {
                        $sql = "INSERT INTO funds_hsi_indicators (tradeday, high, low, close, volume, open, adjclose)
                                VALUES ('$dateString', {$highPrices[$index]}, {$lowPrices[$index]}, {$closePrices[$index]}, {$volumes[$index]}, {$openPrices[$index]}, {$adjClosePrices[$index]})";
                        if ($conn->query($sql) === TRUE) {
                            echo "Inserted data for $dateString.\n";
                        } else {
                            echo "Database Error for $dateString: " . $sql . "<br>" . $conn->error . "\n";
                        }
                    } else {
                        echo "Skipping $dateString due to missing data.\n";
                    }
                } else {
                    echo "Data for $dateString already exists in the database.\n";
                }
                // Removed the sleep here, as we already have a sleep before the API call
            }
        } else {
            echo "Warning: No data found for " . date("Y-m-d", $currentDate) . "\n";
        }

    } else {
        echo "API Error for $dateString: Unable to retrieve data. Response: $response\n";
    }
}

$conn->close();

?>