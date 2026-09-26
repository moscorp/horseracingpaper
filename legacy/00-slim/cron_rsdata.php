<?php

// error_reporting(E_ALL);
error_reporting(E_ALL & ~E_NOTICE); //only in log
ini_set('display_errors', 0);   //hide from user
// ini_set("allow_url_fopen", 1);
header('Content-Type: text/html; charset=utf-8');
require_once('lib/mossql.php');	
require_once('lib/func.php');	
require_once('lib/func_grec.php');	
require_once("lib/func_mailer_gmail.php");
include_once('simplehtmldom_1_9_1/simple_html_dom.php');

require_once("lib/constants.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($mysqli, "utf8mb4");

$racingdate = date("Y-m-d");
$venuelist = array("ST");//,"HV","S1","S2","S3","S4","S5");

foreach ($venuelist as $venue) {
    $url = "https://bet2.hkjc.com/racing/script/rsdata.js?lang=ch&date=".$racingdate."&venue=".strtoupper($venuelist)."&CV=FO_L4.01R0f";
    // echo $url;
    $html = file_get_contents("compress.zlib://".$url);
    
    $rsdata = array();
    
    // Extract the values of the variables from the JavaScript content
    // preg_match_all('/var\s+(\w+)\s*=\s*\'([^\']+)\';/', $html, $matches, PREG_SET_ORDER);
    // preg_match_all('/var\s+(\w+)\s*=\s*"(.*?)";/', $html, $matches, PREG_SET_ORDER);
    // preg_match_all('/var\s+(\w+)\s*=\s*\'(.*?)\';/', $html, $matches, PREG_SET_ORDER);
    // preg_match_all('/var\s+(\w+)\s*=\s*\'(.*?)\';/', $html, $matches, PREG_SET_ORDER);
    // preg_match_all('/var\s+(\w+)\s*=\s*\'([^\']+)\';/', $html, $matches, PREG_SET_ORDER);
    // preg_match_all('/var\s+(\w+)\s*=\s*([^;]+);/', $html, $matches, PREG_SET_ORDER);
    preg_match_all('/var\s+(\w+)\s*=\s*([^;]+);/', $html, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $key = $match[1];
        // $value = $match[2];
        $value = trim($match[2], '\'');
        echo $key.$value."<br>";

        // if ($key === 'top5Jockey' || $key === 'top5Trainer') {
        //     $rsdata[$key] = explode(';', $value);
        // } elseif ($key === 'mtgCurRace' || $key === 'mtgTotalRace' || $key === 'mtgRanRace') {
        //     $rsdata[$key] = intval($value);
        // } elseif ($key === 'isLastRaceRan' || $key === 'isOverseaMeeting') {
        //     $rsdata[$key] = boolval($value);
        // } elseif ($key === 'mtgDate') {
        //     $rsdata[$key] = date('Y-m-d', strtotime($value));
        // } else {
        //     $rsdata[$key] = $value;
        // }
            // if ($key === 'top5Jockey' || $key === 'top5Trainer') {
            //     $rsdata[$key] = explode(';', $value);
            // } elseif ($key === 'mtgCurRace' || $key === 'mtgTotalRace' || $key === 'mtgRanRace') {
            //     $rsdata[$key] = intval($value);
            // } elseif ($key === 'isLastRaceRan' || $key === 'isOverseaMeeting') {
            //     $rsdata[$key] = boolval($value);
            // } elseif ($key === 'mtgDate') {
            //     $rsdata[$key] = date('Y-m-d', strtotime($value));
            // } else {
            //     $rsdata[$key] = $value;
            // }
        // if ($key === 'top5Jockey' || $key === 'top5Trainer') {
        //     $rsdata[$key] = explode(';', $value);
        // } elseif ($key === 'mtgCurRace' || $key === 'mtgTotalRace' || $key === 'mtgRanRace') {
        //     $rsdata[$key] = intval($value);
        // } elseif ($key === 'isLastRaceRan' || $key === 'isOverseaMeeting') {
        //     $rsdata[$key] = boolval($value);
        // } elseif ($key === 'mtgDate') {
        //     $rsdata[$key] = date('Y-m-d', strtotime($value));
        // } else {
        //     $rsdata[$key] = $value;
        // }
        
        if ($key === 'top5Jockey' || $key === 'top5Trainer') {
            $rsdata[$key] = explode(';', $value);
        } elseif ($key === 'mtgCurRace' || $key === 'mtgTotalRace' || $key === 'mtgRanRace') {
            $rsdata[$key] = intval($value);
        } elseif ($key === 'isLastRaceRan' || $key === 'isOverseaMeeting') {
            $rsdata[$key] = boolval($value);
        } elseif ($key === 'mtgDate') {
            $rsdata[$key] = date('Y-m-d', strtotime($value));
        } else {
            $rsdata[$key] = $value;
        }
    }
    
    // Extract the race header information
    preg_match('/var\s+raceHeaderInfoCH\s*=\s*(\[.*?\]);/', $html, $match);
    $rsdata['raceHeaderInfo'] = json_decode($match[1], true);
    
    // $rsdata['mtgCurRace'] = intval($rsdata['mtgCurRace']);
    // $rsdata['mtgTotalRace'] = intval($rsdata['mtgTotalRace']);
    // $rsdata['mtgRanRace'] = intval($rsdata['mtgRanRace']);
    // $rsdata['isLastRaceRan'] = boolval($rsdata['isLastRaceRan']);
    // $rsdata['isOverseaMeeting'] = boolval($rsdata['isOverseaMeeting']);
    
    // Check if the mtgDate value is set
    // if (isset($rsdata['mtgDate'])) {
    //     // Convert the mtgDate value to a valid date format
    //     $rsdata['mtgDate'] = date('Y-m-d', strtotime($rsdata['mtgDate']));
    // } else {
    //     // If mtgDate is not set, use a default value or handle the error in another way
    //     $rsdata['mtgDate'] = date('Y-m-d'); // Replace with a valid default value
    // }

    print_r($rsdata);
    echo $rsdata['mtgTotalRace']."--------------";
    
    $sql = "INSERT INTO rsdata (
        `top5Jockey`,
        `top5Trainer`,
        `mtgDate`,
        `mtgVenue`,
        `dayShort`,
        `venueShort`,
        `dayLong`,
        `venueLongCh`,
        `reserveList`,
        `scratchList`,
        `meetingIdKey`,
        `foKey`,
        `raceHeaderInfo`,
        `mtgCurRace`,
        `mtgTotalRace`,
        `mtgRanRace`,
        `isLastRaceRan`,
        `isOverseaMeeting`
    )
    VALUES (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )
    ON DUPLICATE KEY UPDATE
        `top5Jockey` = VALUES(`top5Jockey`),
        `top5Trainer` = VALUES(`top5Trainer`),
        `dayShort` = VALUES(`dayShort`),
        `venueShort` = VALUES(`venueShort`),
        `dayLong` = VALUES(`dayLong`),
        `venueLongCh` = VALUES(`venueLongCh`),
        `reserveList` = VALUES(`reserveList`),
        `scratchList` = VALUES(`scratchList`),
        `meetingIdKey` = VALUES(`meetingIdKey`),
        `foKey` = VALUES(`foKey`),
        `raceHeaderInfo` = VALUES(`raceHeaderInfo`),
        `mtgCurRace` = VALUES(`mtgCurRace`),
        `mtgTotalRace` = VALUES(`mtgTotalRace`),
        `mtgRanRace` = VALUES(`mtgRanRace`),
        `isLastRaceRan` = VALUES(`isLastRaceRan`),
        `isOverseaMeeting` = VALUES(`isOverseaMeeting`)
    ";
    // echo $sql;
    $stmt = $mysqli->prepare($sql);
    $top5Jockey = json_encode($rsdata['top5Jockey']);
    $top5Trainer = json_encode($rsdata['top5Trainer']);
    $raceHeaderInfo = json_encode($rsdata['raceHeaderInfo']);
    
    $stmt->bind_param(
        "ssssssssssssssssss",
        $top5Jockey,
        $top5Trainer,
        $rsdata['mtgDate'],
        $rsdata['mtgVenue'],
        $rsdata['dayShort'],
        $rsdata['venueShort'],
        $rsdata['dayLong'],
        $rsdata['venueLongCh'],
        $rsdata['reserveList'],
        $rsdata['scratchList'],
        $rsdata['meetingIdKey'],
        $rsdata['foKey'],
        $raceHeaderInfo,
        $rsdata['mtgCurRace'],
        $rsdata['mtgTotalRace'],
        $rsdata['mtgRanRace'],
        $rsdata['isLastRaceRan'],
        $rsdata['isOverseaMeeting']
    );
    
    // Check if the `mtgDate` and `mtgVenue` combination already exists
    $stmt2 = $mysqli->prepare("SELECT COUNT(*) FROM rsdata WHERE mtgDate = ? AND mtgVenue = ?");
    $stmt2->bind_param("ss", $rsdata['mtgDate'], $rsdata['mtgVenue']);
    $stmt2->execute();
    $stmt2->store_result();
    $stmt2->bind_result($count);
    $stmt2->fetch();
    
    if ($count == 0) {
        if ($stmt->execute()) {
            echo "<br>Data inserted successfully.";
        } else {
            echo "<br>Error inserting data: " . $stmt->error;
        }
    } else {
        echo "<br>Data already exists for the given `mtgDate` and `mtgVenue`.";
    }

    $stmt->close();
    $stmt2->close();
}

?>