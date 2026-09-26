<?php
error_reporting(E_ALL);
require_once("lib/func_mailer_gmail.php");
include_once('simplehtmldom_1_9_1/simple_html_dom.php');
require_once('lib/func.php');	
require_once("lib/constants.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

ini_set("allow_url_fopen", 1);
header('Content-Type: text/html; charset=utf-8');
//---------------------------------------------------------------------------------------------

if(function_exists("date_default_timezone_set") AND function_exists("date_default_timezone_get")){
	// Set the default timezone to use. Available as of PHP 5.1
	// @date_default_timezone_set(@date_default_timezone_get()); // auto get server's timezone
	@date_default_timezone_set('Asia/Hong_Kong');
}

// $mysqli = mysqli_connect('localhost', 'melvin_melvin', 'mopass', 'moosay');

$mail= isset($_GET['mail']) ? $_GET['mail'] : 0;
$raceno=1;
//---------------------------------------------------------------------------------------------
//racing date detail---------------------------------------------------------------------------------------------

$url ="https://bet2.hkjc.com/racing/script/rsdata.js?lang=ch&date=*&venue=*";
$today = date("Y-m-d");
$url = isset($_GET['venue']) ? "https://bet2.hkjc.com/racing/script/rsdata.js?lang=ch&date=".$today."&venue=".$_GET['venue']."&CV=FO_L4.01R0f" : $url;
// echo $url;
// die(1);
// curl_setopt($handle, CURLOPT_ENCODING, 'gzip,deflate,sdch');
// file:///D:/WebDesign/HTML/test/default.html
$html = file_get_html("compress.zlib://".$url);

$racingdaydata = multiexplode2(array("var","="),$html) ;
// print_r($racingdaydata);
$newArray = array();
foreach($racingdaydata as $val) {
	if($val[0]){
		$val1 = str_replace("'","",$val[1]);
		$newArray += [$val[0] => $val1];
	}
}
// print_r($newArray);
$mtgDate="";
foreach($racingdaydata as $key) {
	if(str_replace(' ', '',$key[0])=="mtgDate"){ $mtgDate = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="mtgVenue"){ $mtgVenue = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="mtgTotalRace"){ $mtgTotalRace = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="venueLong"){ $venueLong = str_replace(array("'",";"," "),"",$key[1]); }
	if(str_replace(' ', '',$key[0])=="racePostTime"){ $racePostTime = str_replace(array("[","]"," "),"",$key[1]); } 
	if(str_replace(' ', '',$key[0])=="raceHeaderInfoEN"){ $raceHeaderInfoEN = $key[1];}	//str_replace(array("[","]"," "),"",$key[1]); } //

	if(str_replace(' ', '',$key[0])=="poolStatusByRace"){ $poolStatusByRace = $key[1];}	//str_replace(array("[","]"," "),"",$key[1]); } //
}
//-----------------------------------------------
$racetime = explode(",", $racePostTime);
$racetime = str_replace($mtgDate,"", $racetime);

$racingdate=$mtgDate;
$venue = isset($_GET['venue']) ? $_GET['venue'] : $mtgVenue;
$venueLong=$venueLong;
$start="1";
$end=$mtgTotalRace;

//---------------------------------------------------------------------------------------------------------------------------------------------------
if($racingdate <> date("Y-m-d")) exit;

    // clean up memory
$html->clear();
unset($html);

//----------------------------------------------------------------------------------------
if($mtgTotalRace){
    $url = "https://bet2.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=".$racingdate."&venue=".$venue."&start=1&end=".$end;
    $url_pre = "https://bet2.hkjc.com/racing/getJSON.aspx?type=winplaoddspre&date=".$racingdate."&venue=".$venue."&start=1&end=".$end;
    // echo $url."<br>";
    // echo $url_pre."<br>";
    $obj = postRequest($url,[]);
    $obj_pre = postRequest($url_pre,[]);
    // print_r ($obj);
    // print_r ($obj_pre);

    $sql_curr = "INSERT INTO `oddshistory` VALUES ".
        "(NULL, 'curr','".$racingdate."', '".$venue."', '".$mtgTotalRace."', '".$obj."', now())";
    // echo $sql_curr."<Br><Br>";    
    if($query = mysqli_query($mysqli, $sql_curr)){
        echo "done.c";
    }else {
        echo "Error : " . mysqli_error($mysqli);
    }


    //-----------
    // $oddshistory = "select * from oddshistory where type='pre' and racingdate='".$racingdate."' and venue='".$venue."'";
    // $oddshistory = mysqli_query($mysqli, $oddshistory);
    // $nofrec = $oddshistory->num_rows;
    // if(!$nofrec){
        // $sql_pre = "INSERT INTO `oddshistory` VALUES ".
        //     "(NULL, 'pre','".$racingdate."', '".$venue."', '".$mtgTotalRace."', '".$obj_pre."', now())";
        // // echo $sql_pre;    
        // if($query = mysqli_query($mysqli, $sql_pre)){
        //     echo "done";
        // }else {
        //     echo "Error : " . mysqli_error($mysqli);
        // }
    // }
    $sql_check = "SELECT COUNT(*) AS count 
              FROM oddshistory 
              WHERE racingdate = '$racingdate' 
                AND venue = '$venue' 
                AND type = 'pre'
                and date_format(rectime,'%Y-%m-%d') = '$racingdate' ";
    $sql_check = "SELECT COUNT(*) AS count
                    FROM oddshistory
                    WHERE racingdate = '$racingdate'
                    AND venue = '$venue'
                    AND type = 'pre'
                    AND date_format(rectime,'%Y-%m-%d') = '$racingdate'
                    AND position('@' IN odds) <> 9";

    $result = mysqli_query($mysqli, $sql_check);
    // echo $sql_check;
    if ($result === false) {
        // Query failed
        echo "Error executing SQL query: " . mysqli_error($mysqli);
    } else {    
        $row = mysqli_fetch_assoc($result);
        $count = $row['count'];
        // echo $count;
        if ($row['count'] == 0) {
            $sql_pre = "INSERT INTO `oddshistory` VALUES (NULL, 'pre', '$racingdate', '$venue', '$mtgTotalRace', '$obj_pre', NOW())";
            if ($query = mysqli_query($mysqli, $sql_pre)) {
                echo "done.p";
            } else {
                echo "Error: " . mysqli_error($mysqli);
            }
        } else {
            echo "Record already exists.";
        }
    }
}

?>
