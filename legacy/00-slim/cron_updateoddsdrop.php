<?php

// error_reporting(E_ALL);
include_once ("lib/func_basedata.php");
include_once ("lib/func_mailer_gmail.php");
include_once ("lib/constants.php");

$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($mysqli, "utf8mb4");

$logs = "";
$today = date("Y-m-d");
$Yesterday = date('d.m.Y',strtotime("-2 days"));

if (!$mysqli) {
    die('DATABASE ERROR: ' . mysqli_connect_errno());
}

//-----------------------------------------------------------------------
$sql = "SELECT * FROM oddsDrop WHERE date>'$Yesterday' GROUP BY date, horsecode ORDER BY horsecode, date";
$oddsDrop_array = mysqli_query($mysqli, $sql);

// Initialize the array to avoid null warnings
$oddsDrophists = []; // Ensure this is initialized
$oddsDrop_laterest = []; // Ensure this is initialized

foreach ($oddsDrop_array as $oddsDrophist) {
    $horseid = $oddsDrophist['horsecode'];
    // Concatenate date and horse code
    $oddsDrophists[] = $oddsDrophist['date'] . $horseid;
}
// print_r($oddsDrophists);

//---------------------------------------------------------------------------------------------------------------------------------

$basedata = basedata($raceingdate,$venueCode,$resultOddsType);
// print_r($basedata);

$activeMeetings = $basedata['data']['activeMeetings'];
    foreach ($activeMeetings as $meeting) {
        $allvenue[] = [
            'date' => $meeting['date'],
            'venueCode' => $meeting['venueCode'],
        ];
    }

// print_r($allvenue);
$cnt = 0;
foreach ($allvenue as $rsdata => $rsdetail) {
    $raceMeetings = $basedata['data']['raceMeetings'][$rsdata]['races'];
    
    foreach ($raceMeetings as $race) { //---------------------------------------------------------------------------------------------------------------------------
        $raceno = $race['no'];
        $status = $race['status'];
        echo $raceno.$status."<br>";
        switch ($status) {
            case "RESULT":
                foreach ($race['runners'] as $racedetail) { 
                    $horseno = $racedetail['no'];
                    $horsecode = $racedetail['horse']['code'];
                    $horsename = $racedetail['name_ch'];
                    $finalPosition = $racedetail['finalPosition'];
                    $mix = $date . $horsecode;
                    $pre = $odds[$venue][$raceno][$horseno]['Pre']['preprop'];
                    $curr = $odds[$venue][$raceno][$horseno]['Curr']['prop'];
                    $prewin = $odds[$venue][$raceno][$horseno]['Pre']['WINPre']; 
                    $currwin = $odds[$venue][$raceno][$horseno]['Curr']['WIN'];
    
                    // Check if $oddsDrophists is not empty and perform in_array check
                    if (!in_array($mix, $oddsDrophists)) {
                        // Ensure the odds variable is set and contains the expected structure
                        if (isset($odds[$venue][$raceno][$horseno]['Curr']['oddsDropValue']) && 
                            $odds[$venue][$raceno][$horseno]['Curr']['oddsDropValue'] > 0) {
                            
                            $insert_sql = "INSERT INTO oddsDrop VALUES (''," .
                                "'" . $date . "'," . // Add quotes around string values
                                "'" . $venue . "'," .
                                "'" . $horsecode . "'," .
                                "'" . $horsename . "'," .
                                $odds[$venue][$raceno][$horseno]['Curr']['oddsDropValue'] . "," .
                                $pre . "," .
                                $curr . "," .
                                $prewin . "," .
                                $currwin . "," .
                                $finalPosition.")";
                            
                            // echo $insert_sql . "<br>";
                            $result = mysqli_query($mysqli, $insert_sql);
                            $cnt++;
                        }
                    }
                }
                //--------------------------------------------------------------------
                if($cnt){
                    $msg = 'ai-updateOddsDrop-'.$cnt;
                    $insert = "	INSERT INTO logs
                    VALUES(0,'$msg',now())";
                    // echo $insert;
                    mysqli_query($mysqli, $insert);
                }
                //--------------------------------------------------------------------	
                break;
            case "DECLARED":
                break;
        }
    }
    

}




?>