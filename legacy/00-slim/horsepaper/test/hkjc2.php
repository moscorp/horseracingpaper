<?php
error_reporting(-1); // -1 for development mode
//require_once('../common/mossql.php');	
require_once("../common/class.phpmailer.php");

if(function_exists("date_default_timezone_set") AND function_exists("date_default_timezone_get"))
{
	// Set the default timezone to use. Available as of PHP 5.1
	// @date_default_timezone_set(@date_default_timezone_get()); // auto get server's timezone
	@date_default_timezone_set('Asia/Hong_Kong');
}


$var_updTime = date("H:i");
$var_date = "2019-02-13";
$var_venue = "HV";
$url = "https://bet.hkjc.com/racing/getJSON.aspx?type=winplaodds&date=".$var_date."&venue=".$var_venue."&start=1&end=15";
echo $url;

// DB connection
$dbHost = 'localhost';
$dbUser = 'melvin_melvin';
$dbPass = 'mopass';
$dbName = 'moosay';

$dbTblName = 'hkjcwp'; // Table Name

$mysqli = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
if (!$mysqli) {
    die('DATABASE ERROR: ' . mysqli_connect_errno());
}

/*
$date_format = 'Y-m-d';

$today = mktime();
$d = date('d', $today);
$m = date('m', $today);
$y = date('Y', $today);

$tomorrow = mktime(0, 0, 0, $m, ($d + 1), $y);

/*
echo ' Yesterday - ' . gmdate($date_format, $yesterday) . '<br />';
echo ' Today - ' . gmdate($date_format, $today) . '<br />';
echo ' Tomorrow - ' . gmdate($date_format, $tomorrow) . '<br />';

/**
* Curl send get request, support HTTPS protocol
* @param string $url The request url
* @param string $refer The request refer
* @param int $timeout The timeout seconds
* @return mixed
*/
function getRequest($url, $refer = "", $timeout = 10)
{
    $ssl = stripos($url,'https://') === 0 ? true : false;
    $curlObj = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_AUTOREFERER => 1,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)',
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
        CURLOPT_HTTPHEADER => ['Expect:'],
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    ];
    if ($refer) {
        $options[CURLOPT_REFERER] = $refer;
    }
    if ($ssl) {
        //support https
        $options[CURLOPT_SSL_VERIFYHOST] = false;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
    }
    curl_setopt_array($curlObj, $options);
    $returnData = curl_exec($curlObj);
    if (curl_errno($curlObj)) {
        //error message
        $returnData = curl_error($curlObj);
    }
    curl_close($curlObj);
    return $returnData;
}

/**
* Curl send post request, support HTTPS protocol
* @param string $url The request url
* @param array $data The post data
* @param string $refer The request refer
* @param int $timeout The timeout seconds
* @param array $header The other request header
* @return mixed
*/
function postRequest($url, $data, $refer = "", $timeout = 10, $header = [])
{
    $curlObj = curl_init();
    $ssl = stripos($url,'https://') === 0 ? true : false;
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_AUTOREFERER => 1,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)',
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
        CURLOPT_HTTPHEADER => ['Expect:'],
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_REFERER => $refer
    ];
    if (!empty($header)) {
        $options[CURLOPT_HTTPHEADER] = $header;
    }
    if ($refer) {
        $options[CURLOPT_REFERER] = $refer;
    }
    if ($ssl) {
        //support https
        $options[CURLOPT_SSL_VERIFYHOST] = false;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
    }
    curl_setopt_array($curlObj, $options);
    $returnData = curl_exec($curlObj);
    if (curl_errno($curlObj)) {
        //error message
        $returnData = curl_error($curlObj);
    }
    curl_close($curlObj);
    return $returnData;
}

//$getRes = getRequest($url);
//echo $getRes;//Get index page html of php.net

$postRes = postRequest($url,[]);
//echo $postRes;
//echo $postRes['OUT'];
//print_r($postRes);

function rstrstr($haystack,$needle){
	return substr($haystack, 0,strpos($haystack, $needle));
}

//if ($postRes["OUT"]==null) exit;

list($d['body'], $d['headers']) = explode(':',$postRes);
if ($d['headers']=="") exit;

$cnt =1;
$output = strstr($d['headers'],"@@@WIN");
//echo $cnt."w-".strstr(rstrstr($output,"#PLA"),"1")."<br>"; 
	$array = explode(';', strstr(rstrstr($output,"#PLA"),"1"));
	foreach($array as $key=>$value){
		$arr = explode('=', $value);
		$raceno = $arr[0];
		$get_win[$cnt]['W'][$raceno] = $arr[0] ? $arr[1] : 0;
		$params = "	INSERT INTO $dbTblName(date,venue,updatetime,raceno,horseno,type,odds)
					VALUES('$var_date','$var_venue','$var_updTime','$cnt','$raceno','W','".($arr[0] ? $arr[1] : 0)."')";
		mysqli_query($mysqli, $params);
	}
//print_r($get_win);
//echo "<br>";

do {
	$output = strstr($output,"#PLA");
	//echo $cnt."p-".strstr(rstrstr($output,"@@@WIN"),"1")."<br>";
	if(strstr(rstrstr($output,"@@@WIN"),"1")){
		$array = explode(';', strstr(rstrstr($output,"@@@WIN"),"1"));
		foreach($array as $key=>$value){
			$arr = explode('=', $value);
			$raceno = $arr[0];
			$get_win[$cnt]['P'][$raceno] = $arr[0] ? $arr[1] : 0;
			$params = "	INSERT INTO $dbTblName(date,venue,updatetime,raceno,horseno,type,odds)
						VALUES('$var_date','$var_venue','$var_updTime','$cnt','$raceno','P','".($arr[0] ? $arr[1] : 0)."')";
			mysqli_query($mysqli, $params);
		}
	}
	$cnt++;
	$output = strstr($output,"@@@WIN");
	//echo $cnt."w-".strstr(rstrstr($output,"#PLA"),"1")."<br>";
	if(strstr(rstrstr($output,"#PLA"),"1")){
		$array = explode(';', strstr(rstrstr($output,"#PLA"),"1"));
		foreach($array as $key=>$value){
			$arr = explode('=', $value);
			$raceno = $arr[0];
			$get_win[$cnt]['W'][$raceno] = $arr[0] ? $arr[1] : 0;
			$params = "	INSERT INTO $dbTblName(date,venue,updatetime,raceno,horseno,type,odds)
						VALUES('$var_date','$var_venue','$var_updTime','$cnt','$raceno','W','".($arr[0] ? $arr[1] : 0)."')";
			mysqli_query($mysqli, $params);
		}
	}

	$chk = substr(strstr($output,"@@@WIN"),0,1);
}while ($cnt<15); //( $chk != "#");

//mysqli_free_result($checkDB);

//print_r($get_win);

?>
