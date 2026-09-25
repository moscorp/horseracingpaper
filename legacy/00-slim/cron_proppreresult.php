<?php
error_reporting(E_ERROR | E_PARSE);

include_once ("lib/func_aii.php");
include_once ("lib/func_basedata.php");
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");

$mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);
mysqli_set_charset($mysqli, "utf8mb4");

$basedata = basedata($raceingdate,$venueCode,$resultOddsType);
// print_r($basedata);

$activeMeetings = $basedata['data']['activeMeetings'];

    foreach ($activeMeetings as $meeting) {
        $allvenue[] = [
            'date' => $meeting['date'],
            'venueCode' => $meeting['venueCode'],
        ];
    }

    //-----------------------------------------------------------------------
    $sql = "SELECT * FROM racepropresult WHERE finalPosition ";
    // echo $sql;
    $racepropresult_array = mysqli_query($mysqli, $sql);
    
    // Initialize the array to avoid null warnings
    $racepropresult_hist = []; // Ensure this is initialized
    
    foreach ($racepropresult_array as $result) {
        $racepropresult[] = $result['racingdate'] . $result['venue'] . $result['raceno'] . $result['horseno'];
        // echo $result['racingdate'] . $result['venue'] . $result['raceno'] . $result['horseno'];
    }
    // print_r($racepropresult);
        
foreach ($allvenue as $rsdata) {
    // print_r($rsdata);
    $date = $rsdata['date'];
    $venue = $rsdata['venueCode'];
    // $basedata = basedata($date,$venue,$resultOddsType);
    $raceMeetings = $basedata['data']['raceMeetings'][0]['races'];
    // print_r($raceMeetings);
    $insert_sql = "INSERT INTO `racepropresult` (racingdate, venue, go_ch, horsecode, horsename, raceno, distance, horseno, prewin, prepla, proppre, finalPosition) VALUES ";
    $values = [];
             
    foreach ($raceMeetings as $race) { //---------------------------------------------------------------------------------------------------------------------------
        $raceno = $race['no'];
        $distance = $race['distance'];
        $status = $race['status'];
        $go_ch = $race['go_ch'];

        
        switch ($status) {
            case "RESULT":
                $CurrPre = 'Pre';
                $oddsPre = pickodds($oddsarray, $date, $venue, $raceno, $CurrPre);

                // print_r($oddsPre);
                $cnt=0;
                foreach ($race['runners'] as $racedetail) { 
                    $horseno = $racedetail['no'];
                    $horsecode = $racedetail['horse']['code'];
                    $horsename = $racedetail['name_ch'];
                    $finalPosition = $racedetail['finalPosition'];
                    $mix = $date . $venue .$raceno.$horseno;
                    $preprop = $oddsPre[$venue][$raceno][$horseno]['Pre']['proppre'];
                    $prewin = $oddsPre[$venue][$raceno][$horseno]['Pre']['WINPre'];
                    $prepla = $oddsPre[$venue][$raceno][$horseno]['Pre']['PLAPre'];
    
                    if(!in_array($mix, $racepropresult) && $raceno && $horseno && $finalPosition){
                            // $insert_sql .= "INSERT INTO `racepropresult` (racingdate, venue, horsecode, horsename, raceno, distance, horseno, prewin, proppre, finalPosition) VALUES 
                                // (" .
                            $values[] = "(" .
                                "'" . $date . "'," . // Add quotes around string values
                                "'" . $venue . "'," .
                                "'" . $go_ch . "'," .
                                "'" . $horsecode . "'," .
                                "'" . $horsename . "'," .
                                $raceno . "," .
                                $distance . "," .
                                $horseno . "," .
                                $prewin . "," .
                                $prepla . "," .
                                $preprop . "," .
                                $finalPosition. ") ";
                            
                            // echo $values . "<br>";
                            // $result = mysqli_query($mysqli, $insert_sql);
                            // Check for errors
                            if (!$result) {
                                echo "Error: " . mysqli_error($mysqli);
                            }
                            $cnt++;
                    }
                }
                break;
            case "DECLARED":
                break;
        }
    }
    if($cnt){
        $insert_sql .= implode(", ", $values);
        echo $insert_sql;
        $result = mysqli_query($mysqli, $insert_sql);
        
        $logs = "cron_proppreresult_".$cnt."_".$currentHongKongTime->format('Y-m-d H:i:s');
        $logsql = "	INSERT INTO logs
        VALUES('','$logs',now())";
        // echo $insert;
        mysqli_query($mysqli, $logsql);


    }
}