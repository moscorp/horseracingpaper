<?php

error_reporting(0); // -1 for development mode

######################################################################################
## Scrape datas from https://www.investing.com/indices/germany-30-futures-technical ##
######################################################################################

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

// CASE	     : http://bet.hkjc.com/racing/default.aspx?lang=EN (getting value for Win/Place)
// XML START : Start by checking current Date & Venue here.

// Get the available venue & date
$scrape_venue_date = 'http://bet.hkjc.com/racing/getXML.aspx?type=pooltot&raceno=1';
$xml		 = simplexml_load_file($scrape_venue_date);
$json_string = json_encode($xml);
$json_array  = json_decode($json_string, TRUE);
$json_res    = $json_array['POOL_TOT'];

// STOP script if no data available
if ( strpos($json_string, 'ERR')!==false && strpos($json_string, 'Information not ready')!==false ) die('Information not ready');

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
		echo '<h1>Date: '.$var_date.'<br>Venue: '.$var_venue.'</h1>';

		for($j=1; $j<count($xml2str); $j++) { // $j is for Race No
			if ( $xml2str[$j] != '#PLA' ) {
				$xml2arr2 = explode('#PLA', $xml2str[$j]);

				// Retrieve Each Race Update Time
				$xml_timestamp_url = 'http://bet.hkjc.com/racing/getXML.aspx?type=pooltot&date='.$var_date.'&venue='.$var_venue.'&raceno='.$j;
				$xml		 = simplexml_load_file($xml_timestamp_url);
				$json_string = json_encode($xml);
				$json_array  = json_decode($json_string, TRUE);
				$var_updTime = $json_array['@attributes']['updateDate'].' '.$json_array['@attributes']['updateTime'];

				echo '<h3 style="margin-bottom:0;">Race No: '.$j.'<br>Update Time: '.$var_updTime.'</h3>';
				$xml2arr3 = array_filter(explode(';', $xml2arr2[0])); // Win
				$xml2arr4 = array_filter(explode(';', $xml2arr2[1])); // Place

				echo '<table border="1" cellpadding="10" cellspacing="0">';
				echo '<tr><td align="center">Horse No</td><td align="center">Win</td><td align="center">Place</td></tr>';

				for($k=1; $k<=count($xml2arr3); $k++) { // $k is for Horse No
					$get_win = explode('=', $xml2arr3[$k]);
					$get_place = explode('=', $xml2arr4[$k]);
					echo '<tr>';
					echo '<td align="center">'.$k.'</td>';
					echo '<td align="center">'. $get_win[1] .'</td>';
					echo '<td align="center">'. $get_place[1] .'</td>';
					echo '</tr>';
				} // END for $k
				echo '</table>';
			}
		} // END for $j
	}
} // END for $i


usleep(1);
$time_end = microtime(true);
$time_overall = bcsub($time_end, $time_start, 4);
echo '<br>&nbsp;<br>'.PHP_EOL;
echo "Script executed in $time_overall seconds\n";

