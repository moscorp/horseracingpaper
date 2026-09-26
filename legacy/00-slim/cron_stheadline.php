<?php
error_reporting(E_ERROR | E_PARSE);

include_once ("lib/func_aii.php");
include_once ("lib/func_basedata.php");
include_once ("lib/constants.php");
$mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);

// $basedata = basedata($raceingdate,$venueCode,$resultOddsType);
// print_r($basedata['data']['raceMeetings'][0]['poolInvs']);
// foreach($basedata['data']['raceMeetings'][0]['poolInvs'] as $invest){
//     $oddsType = $invest['oddsType'];
//     $races = implode(", ", $invest['leg']['races']);
//     $investment = $invest['investment'];
//     echo $oddsType.$races.$investment."<br>";
// }

// https://racing.stheadline.com/api/raceOdds/latest?raceNo=1&type=win,place,quin,place-quin&rev=2

function fetchAndStoreRaceData($url, $storageFile) {
    // Fetch the JSON data from the URL
    $jsonData = file_get_contents($url);
    
    // Check for errors in fetching the data
    if ($jsonData === false) {
        die("Error fetching data from the URL.");
    }

    // Decode the JSON data into a PHP associative array
    $dataArray = json_decode($jsonData, true);

    // Check if the JSON decoding was successful
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Error decoding JSON data: " . json_last_error_msg());
    }

    // Store the data in a file (as JSON)
    file_put_contents($storageFile, json_encode($dataArray, JSON_PRETTY_PRINT));

    return $dataArray; // Optionally return the data
}

$url = "https://racing.stheadline.com/api/raceDay/published?rev=2";
$jsonData = file_get_contents($url);
$dataDay = json_decode($jsonData, true);
// print_r($dataDay);
foreach($dataDay['data']['races'] as $raceno => $racedetail){
    $racenos[] = $racedetail['raceNo'];
}
$raceVenue = $dataDay['data']['raceVenue'];
// echo $raceVenue;
$raceinfo = [];

foreach ($racenos as $raceNo) {
    $url = "https://racing.stheadline.com/api/raceOdds/latest?raceNo=" . $raceNo . "&type=win,place,quin,place-quin&rev=2";
    $jsonData = file_get_contents($url);
    
    if ($jsonData === false) {
        die("Error fetching data from URL: $url");
    }

    // Log the raw JSON data
    error_log("Raw JSON Data: " . $jsonData);

    $dataArray = json_decode($jsonData, true);
    // print_r($dataArray);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Error decoding JSON data: " . json_last_error_msg());
    }

    if($dataArray['data']['race']['state'] == "entry"){
        $raceDate = substr($dataArray['data']['race']['raceDate'], 0, 10);
        $startTime = substr($dataArray['data']['race']['startTime'], 0, 10);
        $source = $dataArray['data']['win']['source'];
        //WIN-01-RACE_20250105_0010-202501051252342534
        $time = substr($source, 34, 2) . ':' . substr($source, 36, 2);
        $sourcetimefull = substr($source, 26, 4) . '-' . substr($source, 30, 2) . '-' . substr($source, 32, 2) . " " .$time;
    
        // Collect the source time and other necessary data in an array
        foreach ($dataArray['data']['win']['raceOddsList'] as $odds) {
            $horseNo = $odds['horseNo1'];
            $winValue = $odds['value'];
            $winInvestment = $odds['investment'];
    
            // Store collected data
            $raceinfo[] = [
                'race_date' => $raceDate,
                'startTime' => $startTime,
                'venue' => $raceVenue,
                'race_no' => $raceNo,
                'horse_no' => $horseNo,
                'win_value' => $winValue,
                'win_investment' => $winInvestment,
                'sourcetimefull' => $sourcetimefull,
                'source_time' => $time,
                'place_value' => null,        // Initialize as null
                'place_investment' => null,   // Initialize as null
            ];
        }
    
        foreach ($dataArray['data']['place']['raceOddsList'] as $odds) {
            $horseNo = $odds['horseNo1'];
            $placeValue = $odds['value'];
            $placeInvestment = $odds['investment'];
    
            // Store collected data, assuming horseNo is the same
            foreach ($raceinfo as &$info) {
                if ($info['race_no'] === $raceNo && $info['horse_no'] === $horseNo) {
                    $info['place_value'] = $placeValue;
                    $info['place_investment'] = $placeInvestment;
                    break;
                }
            }
        }
    }
}

// Log the collected race information
error_log("Collected Race Info: " . print_r($raceinfo, true));

// Insert collected race data into the database
foreach ($raceinfo as $info) {
    // Check for null values and log them
    if (in_array(null, $info, true)) {
        error_log("Warning: Attempting to insert null values: " . print_r($info, true));
    }

    // $stmt = $mysqli->prepare("INSERT INTO racedata_stheadline (race_date, startTime, venue, race_no, horse_no, win_value, win_investment, place_value, place_investment, source_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    // if ($stmt === false) {
    //     die("Prepare failed: " . htmlspecialchars($mysqli->error));
    // }
    
    // $stmt->bind_param(
    //     "sssisdddds",
    //     $info['race_date'],
    //     $info['startTime'],
    //     $info['venue'],
    //     $info['race_no'],
    //     $info['horse_no'],
    //     $info['win_value'],
    //     $info['win_investment'],
    //     $info['place_value'],
    //     $info['place_investment'],
    //     $info['source_time']
    // );
    
    // Prepare the SELECT statement to check for existing records
    $check_stmt = $mysqli->prepare("SELECT COUNT(*) FROM racedata_stheadline WHERE startTime = ? AND venue = ? AND race_no = ? AND horse_no = ? AND source_time = ?");
    if ($check_stmt === false) {
        die("Prepare failed: " . htmlspecialchars($mysqli->error));
    }
    
    // Bind parameters
    $check_stmt->bind_param("sssis", $info['startTime'], $info['venue'], $info['race_no'], $info['horse_no'], $info['source_time']);
    
    // Execute the check
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();
    
    // If the record does not exist, proceed with the insert
    if ($count == 0) {
        $stmt = $mysqli->prepare("INSERT INTO racedata_stheadline (race_date, startTime, venue, race_no, horse_no, win_value, win_investment, place_value, place_investment, sourcetimefull, source_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            die("Prepare failed: " . htmlspecialchars($mysqli->error));
        }
    
        $stmt->bind_param(
            "sssisddddss",
            $info['race_date'],
            $info['startTime'],
            $info['venue'],
            $info['race_no'],
            $info['horse_no'],
            $info['win_value'],
            $info['win_investment'],
            $info['place_value'],
            $info['place_investment'],
            $info['sourcetimefull'],
            $info['source_time']
        );
    
        // Execute the insert
        if (!$stmt->execute()) {
            die("Execute failed: " . htmlspecialchars($stmt->error));
        }
    
        $stmt->close();
    } else {
        // Record already exists, skip insertion
        echo "Record already exists, skipping insert.";
    }

    // if (!$stmt->execute()) {
    //     die("Execute failed: " . htmlspecialchars($stmt->error));
    // }
}

// Close the statement and connection
$stmt->close();
$mysqli->close();

echo "Race data successfully saved to the database.";

?>