<?php

// error_reporting(E_ALL);
error_reporting(E_ALL & ~E_NOTICE); //only in log
ini_set('display_errors', 0);   //hide from user
// ini_set("allow_url_fopen", 1);
header('Content-Type: text/html; charset=utf-8');
// header('Content-Type: application/json; charset=utf-8');
require_once('lib/mossql.php');	
require_once('lib/func.php');	
require_once('lib/func_grec.php');	
require_once("lib/func_mailer_gmail.php");
include_once('simplehtmldom_1_9_1/simple_html_dom.php');



require_once("lib/constants.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($mysqli, "utf8mb4");

$url = 'https://www.sunlife.com.hk/webservice/getmpforsofunddata';
$params = [
    'domainAndUserName' => 'hkportal',
    'sqlKey' => 'MPF_DAILYPRICE',
    'sqlParams' => ['2024-05-13', 'MPF']
];

// Append parameters to the URL
$queryString = http_build_query($params);
$url .= '?' . $queryString;
// echo $url;
// // Retrieve the JSON data
// $jsonData = file_get_contents($url);

// // Convert JSON data to PHP array
// $array = json_decode($jsonData, true);

// // Print the resulting array
// print_r($array);


// $url = 'https://www.sunlife.com.hk/webservice/getmpforsofunddata';

// $url = 'https://www.sunlife.com.hk/zh-hant/investments/mpf-orso-fund-prices-performance/mpf-fund-prices-performance.type-MPF';

// // Set the POST data
// $postData = array(
//     'domainAndUserName' => 'hkportal',
//     'sqlKey' => 'MPF_DAILYPRICE',
//     'sqlParams' => '[
//   "2024-05-14",
//   "MPF"
// ]' //array('2024-05-13', 'MPF')
// );

$tttt = postRequest($url,[]);
print_r($tttt);
// // Initialize curl
// $curl = curl_init();

// // Set the curl options
// curl_setopt($curl, CURLOPT_URL, $url);
// curl_setopt($curl, CURLOPT_POST, true);
// curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postData));
// curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

// // Execute the curl request
// $response = curl_exec($curl);
// echo $response;

// // Close curl
// curl_close($curl);

// // Convert JSON response to PHP array
// $array = json_decode($response, true);

// // Print the resulting array
// print_r($array);

?>
