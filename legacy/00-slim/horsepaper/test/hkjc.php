<?php

error_reporting(0); // -1 for development mode

// DB connection
$dbHost = 'localhost';
$dbUser = 'melvin_melvin';
$dbPass = 'mopass';
$dbName = 'moosay';

$dbTblName = 'hkjc'; // Table Name


#################################################################################
## Scrape Win/Place datas from http://bet.hkjc.com/racing/default.aspx?lang=EN ##
#################################################################################

if (function_exists('ini_set'))
{
	ini_set('default_socket_timeout', 180);
}

if(function_exists("date_default_timezone_set") AND function_exists("date_default_timezone_get"))
{
	// Set the default timezone to use. Available as of PHP 5.1
	// @date_default_timezone_set(@date_default_timezone_get()); // auto get server's timezone
	@date_default_timezone_set('UTC');
}

header('Content-Type: text/html; charset=utf-8');

$time_start = microtime(true);

// MySQLi DB Connection
$mysqli = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
if (!$mysqli) {
    die('DATABASE ERROR: ' . mysqli_connect_errno());
}

// CASE	     : http://bet.hkjc.com/racing/default.aspx?lang=EN (getting value for Win/Place)
// XML START : Start by checking current Date & Venue here.

// Get the available venue & date
$scrape_venue_date = 'http://bet.hkjc.com/racing/getXML.aspx?type=pooltot&raceno=1';
$xml		 = simplexml_load_file($scrape_venue_date);
$json_string = json_encode($xml);
$json_array  = json_decode($json_string, TRUE);
$json_res    = $json_array['POOL_TOT'];

// STOP script if no data available
if ( strpos($json_string, 'ERR')!==false && strpos($json_string, 'Information not ready')!==false ) die();

if( array_key_exists('@attributes', $json_res) ) {
	// Found Single Vanue
	$json_result = $json_array;
} else {
	// Found Multiple Vanue
	$json_result = $json_res;
}

foreach($json_result as $jsr) {
	$jsr_date[]		 = $jsr['@attributes']['DATE'];
	$jsr_venue[]	 = $jsr['@attributes']['VENUE'];
}

// Do iteration since sometimes the venue ID is more than 1
for($i=0; $i<count($jsr_date); $i++) {
	$venue_url = 'http://bet.hkjc.com/racing/getXML.aspx?type=pooltot&date='.$jsr_date[$i].'&venue='.$jsr_venue[$i].'&raceno=1';
	$venue_xml = simplexml_load_file($venue_url);
	$venue_err = $venue_xml->attributes()->ERR;
	if ( !isset($venue_err) ) { // continue only if XML exist
		$wp_url = 'http://bet.hkjc.com/racing/getXML.aspx?type=jcbwracing_winplaodds&date='.$jsr_date[$i].'&venue='.$jsr_venue[$i];
		$wp_xml = simplexml_load_file($wp_url);
		$wp_res = $wp_xml->{0};
		$xml2str = explode('@@@WIN', $wp_res);

		$var_date  = $jsr_date[$i];  // Date
		$var_venue = $jsr_venue[$i]; // Venue

		for($j=1; $j<count($xml2str); $j++) { // $j is for Race No
			if ( $xml2str[$j] != '#PLA' ) {
				$xml2arr2 = explode('#PLA', $xml2str[$j]);

				// Retrieve Each Race Update Time
				$xml_timestamp_url = 'http://bet.hkjc.com/racing/getXML.aspx?type=pooltot&date='.$var_date.'&venue='.$var_venue.'&raceno='.$j;
				$xml		 = simplexml_load_file($xml_timestamp_url);
				$json_string = json_encode($xml);
				$json_array  = json_decode($json_string, TRUE);
				$var_updTime = $json_array['@attributes']['updateDate'].' '.$json_array['@attributes']['updateTime'];

				$xml2arr3 = array_filter(explode(';', $xml2arr2[0])); // Win
				$xml2arr4 = array_filter(explode(';', $xml2arr2[1])); // Place

				for($k=1; $k<=count($xml2arr3); $k++) { // $k is for Horse No
					$get_win   = explode('=', $xml2arr3[$k]);
					$get_place = explode('=', $xml2arr4[$k]);

					$checkDB = mysqli_query($mysqli, "SELECT id,updatetime FROM $dbTblName
													  WHERE date='$var_date' AND venue='$var_venue' AND raceno='$j' AND horseno='$k'");

					if ( mysqli_num_rows($checkDB) > 0 )
					{
						// Entry already exist, do UPDATE
						$checkOne = mysqli_fetch_assoc($checkDB);
						$curr_id = $checkOne['id'];
						$curr_ut = $checkOne['updatetime'];

					    // UPDATE DB ONLY if necessary (updatetime is updated on the server)
						if ( $var_updTime != $curr_ut ) {
							$params = "UPDATE $dbTblName SET updatetime='$var_updTime', win='$get_win[1]', place='$get_place[1]'
									   WHERE id='$curr_id'";
							mysqli_query($mysqli, $params);
						}
					} else {
						// Entry not yet exist, do CREATE
						$params = "INSERT INTO $dbTblName(date,venue,updatetime,raceno,horseno,win,place)
								   VALUES('$var_date','$var_venue','$var_updTime','$j','$k','$get_win[1]','$get_place[1]')";
						mysqli_query($mysqli, $params);
					}

					// Free result set
					mysqli_free_result($checkDB);

				} // END for $k
			}
		} // END for $j
	}
} // END for $i

/*
usleep(1);
$time_end = microtime(true);
$time_overall = bcsub($time_end, $time_start, 4);
echo '<br>&nbsp;<br>'.PHP_EOL;
echo "Script executed in $time_overall seconds\n";
*/
