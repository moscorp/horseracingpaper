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
	@date_default_timezone_set(@date_default_timezone_get()); // auto get server's timezone
	// @date_default_timezone_set('UTC');
}

header('Content-Type: text/html; charset=utf-8');

$time_start = microtime(true);

// CASE	    : Scraping directly from https://www.investing.com/indices/germany-30-futures-technical is not an option!
// WHY	    : The script will loads slowly (specially for scraping per minute).
// SOLUTION : Deeply look for source API/JSON to retrieve datas (FOUND).
// NEW CASE : Scraping datas from https://ssltsw.forexprostools.com/api.php?action=refresher&pairs=8826&timeframe=60

$user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.1) Gecko/20090615 Firefox/3.5';

$scrape_me  = 'https://ssltsw.forexprostools.com/api.php?action=refresher&pairs=8826&timeframe=';

$referer    = 'https://www.investing.com/webmaster-tools/technical-summary-box';

$timeframe  = array('60','300','900','1800','3600','18000','86400','week','month');

for($i=0; $i<count($timeframe); $i++)
{
	$json = $scrape_me . $timeframe[$i];
	$out  = scrape_data($json, $referer);
	$obj  = json_decode($out, true);
	//echo $obj['time'] .' - '. $obj[8826]['row']['last'].' - <b>'. $obj[8826]['row']['ma'].'</b> - '.$timeframe[$i].'<br>';
	//if($i==0) { echo $obj['time'].'<br>'; }
	echo $obj[8826]['row']['ma'].'<br>';
	unset($json,$out,$obj);
}

usleep(1);
$time_end = microtime(true);
$time_overall = bcsub($time_end, $time_start, 4);
echo '<br>&nbsp;<br>'.PHP_EOL;
echo "Script executed in $time_overall seconds\n";

// Live comparation from localhost:
// cURL				 | script executed in 16.7992 seconds
// file_get_contents | script executed in 17.4817 seconds
// fopen			 | script executed in 27.7229 seconds

function scrape_data($url, $referer)
{
	$user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.1) Gecko/20090615 Firefox/3.5';

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_REFERER, $referer);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));

	// In order to read sites encrypted by SSL
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

	$data = curl_exec($ch);
	curl_close($ch);

	if ( $data === false || curl_errno($ch) ) {
		// TRY file_get_contents()
		$opts = array(
		        'http'=>array(
		            'method'=>"GET",
		            'header'=>"Accept-language: en\r\n" .
							  "Cookie: foo=bar\r\n" .
							  "Referer: $referer\r\n" .
							  "User-Agent: $user_agent\r\n",
					'timeout' => 5,
					'request_fulluri' => TRUE
		       ),
			'ssl' => array(
				'cafile' => __DIR__ . "/cacert.pem",
				'verify_peer' => true,
				'verify_peer_name' => true
            )
		);
		$ctxt = stream_context_create($opts);

		$data = file_get_contents($url, FALSE, $ctxt);
	}

	return $data;
}
