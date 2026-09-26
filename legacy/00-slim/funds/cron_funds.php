
<?php

include_once("func_hs.php");
require_once("../lib/constants.php");
require_once("../lib/func_mailer_gmail.php");
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($mysqli, "utf8mb4");

$days_of_week = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');

// // 1. Core Search Context & Interfaces (Must be first)
// require_once 'php-webdriver-main/lib/WebDriverSearchContext.php';
// require_once 'php-webdriver-main/lib/WebDriverCapabilities.php';
// require_once 'php-webdriver-main/lib/WebDriverHasCapabilities.php'; // FIXED NAME
// require_once 'php-webdriver-main/lib/JavaScriptExecutor.php';
// require_once 'php-webdriver-main/lib/WebDriverHasInputDevices.php';
// require_once 'php-webdriver-main/lib/WebDriverTakesScreenshot.php'; // FIXED NAME (usually needed)

// // 2. Main Library Files
// require_once 'php-webdriver-main/lib/WebDriver.php';
// require_once 'php-webdriver-main/lib/WebDriverBy.php';
// require_once 'php-webdriver-main/lib/WebDriverPlatform.php';

// // 3. Remote Implementation Files
// require_once 'php-webdriver-main/lib/Remote/DesiredCapabilities.php';
// require_once 'php-webdriver-main/lib/Remote/RemoteWebDriver.php';

// 1. Define the Autoloader (This replaces all manual require_once lines)
// spl_autoload_register(function ($class) {
//     // Project-specific namespace prefix
//     $prefix = 'Facebook\\WebDriver\\';

//     // Base directory for the namespace prefix (Adjust this path if necessary)
//     $base_dir = __DIR__ . '/php-webdriver-main/lib/';

//     // Does the class use the namespace prefix?
//     $len = strlen($prefix);
//     if (strncmp($prefix, $class, $len) !== 0) {
//         return;
//     }

//     // Get the relative class name and map to file path
//     $relative_class = substr($class, $len);
//     $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

//     // If the file exists, require it
//     if (file_exists($file)) {
//         require_once $file;
//     }
// });

// use Facebook\WebDriver\Remote\RemoteWebDriver;
// use Facebook\WebDriver\Remote\DesiredCapabilities;
// use Facebook\WebDriver\WebDriverBy;

// function getValuationDate() {
//     // Set up WebDriver
//     $host = 'http://localhost:4444/wd/hub'; // Change if needed
//     $driver = RemoteWebDriver::create($host, DesiredCapabilities::chrome());

//     // Navigate to the page
//     $driver->get('https://www.sunlife.com.hk/zh-hant/investments/mpf-orso-fund-prices-performance/mpf-fund-prices-performance.type-MPF/');

//     // Wait for the page to load
//     sleep(5); // Adjust as needed

//     // Find the valuation date element
//     $valuationDateElement = $driver->findElement(WebDriverBy::id('valuation_date'));

//     // Get the date text
//     $valuationDate = $valuationDateElement->getText();
    
//     // Close the driver
//     $driver->quit();

//     return $valuationDate;
// }

// // Call the function and display the result
// $valuationDate = getValuationDate();
// echo "Valuation Date: " . $valuationDate;

// function getdates(){
//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, 'https://www.sunlife.com.hk/webservice/getmpforsofunddata');
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
//     curl_setopt($ch, CURLOPT_HTTPHEADER, [
//         'Host: www.sunlife.com.hk',
//         'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:126.0) Gecko/20100101 Firefox/126.0',
//         'Accept: application/json, text/javascript, */*; q=0.01',
//         'Accept-Language: en-US,en;q=0.5',
//         'Accept-Encoding: gzip, deflate, br',
//         'Content-Type: application/json; charset=utf-8',
//         'X-Requested-With: XMLHttpRequest',
//         'Origin: https://www.sunlife.com.hk',
//         'Referer: https://www.sunlife.com.hk/zh-hant/investments/mpf-orso-fund-prices-performance/mpf-fund-prices-performance.type-MPF/',
//         'Sec-Fetch-Dest: empty',
//         'Sec-Fetch-Mode: cors',
//         'Sec-Fetch-Site: same-origin',
//         'Te: trailers',
//     ]);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, '{"domainAndUserName":"hkportal","sqlKey":"VALUATION_DT","sqlParams":["MPF"]}');  //MPF_LAST_VALUATION_DT
//     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

//     $response = curl_exec($ch);
//     $decode = json_decode($response, true);
//     print_r($decode);
//     $originalDate = $decode[0]['LAST_VALUATION_DT'];
//     // Create a DateTime object from the original date string
//     $dateTime = DateTime::createFromFormat('d/m/Y', $originalDate);
//     // Format the date to Y-m-d
//     $formattedDate = $dateTime->format('Y-m-d');
//     // Output the formatted date
//     return $formattedDate;
//     curl_close($ch);
// }

// function getdates() {
//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, 'https://www.sunlife.com.hk/webservice/getmpforsofunddata');
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
//     curl_setopt($ch, CURLOPT_HTTPHEADER, [
//         'Host: www.sunlife.com.hk',
//         'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:126.0) Gecko/20100101 Firefox/126.0',
//         'Accept: application/json, text/javascript, */*; q=0.01',
//         'Accept-Language: en-US,en;q=0.5',
//         'Accept-Encoding: gzip, deflate, br',
//         'Content-Type: application/json; charset=utf-8',
//         'X-Requested-With: XMLHttpRequest',
//         'Origin: https://www.sunlife.com.hk',
//         'Referer: https://www.sunlife.com.hk/zh-hant/investments/mpf-orso-fund-prices-performance/mpf-fund-prices-performance.type-MPF/',
//         'Sec-Fetch-Dest: empty',
//         'Sec-Fetch-Mode: cors',
//         'Sec-Fetch-Site: same-origin',
//         'Te: trailers',
//     ]);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, '{"domainAndUserName":"hkportal","sqlKey":"MPF_LAST_VALUATION_DT","sqlParams":["MPF"]}');
//     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

//     $response = curl_exec($ch);
    
//     // Check for cURL errors
//     if ($response === false) {
//         echo 'cURL Error: ' . curl_error($ch);
//         curl_close($ch);
//         return null; // Return null on error
//     }

//     $decode = json_decode($response, true);
    
//     // Check if the response is valid
//     if (isset($decode[0]['VALUATION_DT'])) {    //LAST_VALUATION_DT
//         $originalDate = $decode[0]['VALUATION_DT'];
//         // Create a DateTime object from the original date string
//         $dateTime = DateTime::createFromFormat('d/m/Y', $originalDate);
        
//         // Check if the DateTime object creation was successful
//         if ($dateTime === false) {
//             echo 'Failed to parse date: ' . $originalDate;
//             return null; // Return null if parsing failed
//         }

//         // Format the date to Y-m-d
//         $formattedDate = $dateTime->format('Y-m-d');
//         // Output the formatted date
//         return $formattedDate;
//     } else {
//         echo 'Date not found in response.';
//         return null; // Return null if the date value is not set
//     }

//     curl_close($ch);
// }

function getfunds($lastupdate)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.sunlife.com.hk/webservice/getmpforsofunddata');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Host: www.sunlife.com.hk',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:126.0) Gecko/20100101 Firefox/126.0',
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Accept-Language: en-US,en;q=0.5',
        'Content-Type: application/json; charset=utf-8',
        'X-Requested-With: XMLHttpRequest',
        'Origin: https://www.sunlife.com.hk',
        'Referer: https://www.sunlife.com.hk/zh-hant/investments/mpf-orso-fund-prices-performance/mpf-fund-prices-performance.type-MPF/',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-origin',
        'Te: trailers',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{"domainAndUserName":"hkportal","sqlKey":"MPF_DAILYPRICE","sqlParams":["'.$lastupdate.'","MPF"]}');
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    // Output the formatted date
    return $response;
    curl_close($ch);
}

function getdates(){
    
}
$fund = array();
$lastupdate = getdates() ?? date('Y-m-d', strtotime('-2 days'));
// Call getdates() and handle fallback

// Check if $lastupdate falls on Saturday or Sunday
$dayOfWeek = date('l', strtotime($lastupdate)); // Get the weekday name

if ($dayOfWeek == 'Saturday') {
    // If Saturday, go back to the previous Friday
    $lastupdate = date('Y-m-d', strtotime($lastupdate . ' -1 day'));
} elseif ($dayOfWeek == 'Sunday') {
    // If Sunday, go back to the previous Friday
    $lastupdate = date('Y-m-d', strtotime($lastupdate . ' -2 days'));
}
// echo $lastupdate;
$fund = hs($lastupdate);
if(!empty($lastupdate)){
    $response = getfunds($lastupdate);
    // Convert JSON response to PHP array
    $array = json_decode($response, true);
    // print_r($array);
    // Print the resulting array
    
    foreach($array as $funds) {
        // Only process if $funds is actually an array with the required keys
        if (is_array($funds) && isset($funds['FUND_CD'])) {
            $fund[$lastupdate][$funds['FUND_CD']]['FUND_CODE'] = $funds['FUND_CD'];
            $fund[$lastupdate][$funds['FUND_CD']]['FUND_NAME'] = $funds['FUND_DESCRIPTION_ZH'];
            $fund[$lastupdate][$funds['FUND_CD']]['FUND_CURRENCY'] = $funds['FUND_CLASS_ZH'];
            $fund[$lastupdate][$funds['FUND_CD']]['PRICE'] = $funds['NAV'];
        }
    }

}else{
    exit("Dates are empty!");
}

    // Check if the $funddate already exists in the database
    $query = "SELECT COUNT(*) as cnt FROM funds WHERE UNIT_PRICE_DATE = '$lastupdate'";
    // echo $query;
    $result = $mysqli->query($query);
    // echo $result->num_rows;
    // if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // if ($row[0] > 0) {
    if ($row['cnt'] > 0 || $data['FUND_CODE'] == "h") {
        // The $funddate already exists, so skip the insert statement
        // continue;
        // echo $row['cnt'];
    }else{
        $insert = "";
        foreach($fund as $funddate => $funddata) {
            foreach ($funddata as $data) {
                $insert .= "INSERT INTO funds
                            VALUES('','$funddate','{$data['FUND_CODE']}','{$data['FUND_NAME']}','{$data['FUND_CURRENCY']}','{$data['PRICE']}',now());";
            }
        }
        
    
        $result = $mysqli->multi_query($insert);
    }



if($_GET['mail']){
    
    $hsi = array();
    $day_before = date( 'Y-m-d', strtotime( $lastupdate . ' -21 day' ) );
    $query_hsi = "SELECT tradeday,close
              FROM  funds_hsi_indicators
              WHERE tradeday >= '$day_before'
              order by tradeday 
              ";
    // echo $query_hsi; limit 16
    $result_hsi = $mysqli->query($query_hsi);
    while ($row = $result_hsi->fetch_assoc()) {
        // $hsi[$row['tradeday']] = $row['close'];
        $tradedays[] = $row['tradeday'];
        $tradeday = $row['tradeday'];
        $close = $row['close'];
    
        $hsi[$tradeday] = array(
            'close' => $close,
            'is_lower' => ($previous_close !== null && $close < $previous_close)
        );
    
        $previous_close = $close;
    }
    
    $query = "SELECT *
              FROM funds
              WHERE FUND_CODE IN ('HSIF', 'GRF', 'R65-XSLIGE-B', 'R65-XSLIHC-B','R65-GCRCPF-B') 
              and UNIT_PRICE_DATE >= '$day_before'
              ORDER BY UNIT_PRICE_DATE DESC, FUND_CODE
              ";
            //   echo $query;
    $result = $mysqli->query($query);
    
    if ($result === false) {
        // The query failed, handle the error
        echo "Error executing the query: " . $mysqli->error;
    } else {
        $fundate = array();
        $fundcode = array();
        $fund = array();
    
        while ($row = $result->fetch_assoc()) {
            $fundate[] = $row['UNIT_PRICE_DATE'];
            $fundcode[] = $row['FUND_CODE'];
            $fundname[$row['FUND_CODE']] = $row['FUND_NAME'];
            $fund[$row['UNIT_PRICE_DATE']][$row['FUND_CODE']] = $row['PRICE'];
        }
    
        $fundate = array_unique($fundate);
        asort($fundate);
        $fundcode = array_unique($fundcode);
    
        $listr = "<table border=1 cellpadding='5' cellspacing='3' border-collapse= collapse; style=\"border-collapse: collapse;\">";
        $listr .= "<tr><th colspan=2>.</th><th>HSI</th>";
        foreach ($fundcode as $code) {
            $listr .= "<th nowrap>" . $fundname[$code] . "</th>";
        }
        $listr .= "</tr>";
        // print_r($tradedays);
        foreach ($fundate as $date) {
            $day_of_week = $days_of_week[date('w', strtotime($date))];
            $week = date('W', strtotime($date));

            if ($current_week !== $week) {
                $current_week = $week;
                $row_color = ($current_week % 2 == 0) ? 'lightgreen' : 'lightyellow';
            }

            $isFri = ($day_of_week == 'Fri') ? "<b>" : "";
            $listr .= "<tr style='background-color: $row_color;'><td>" . $date . "</td><td>" . $isFri . $day_of_week . "</td>";
        
            // Check if the current day's close price is lower than the previous day's close price
            if (isset($hsi[$date]['is_lower']) && $hsi[$date]['is_lower']) {
                // If the current day's close price is lower, use a red font
                $islower = "<font color='red'>";
            } else {
                $islower = "";
            }
        
            $listr .= "<td align='right'>" . $islower . number_format($hsi[$date]['close']) . "</td>";
        
            foreach ($fundcode as $code) {
                $listr .= "<td align='right'>" . (isset($fund[$date][$code]) ? $fund[$date][$code] : "&nbsp;") . "</td>";
            }
        
            $listr .= "</tr>";
        }
    
        $listr .= "</table>";
    }

    echo $listr;


    $emailtitle = "dailyupdate ";
	$ipAddress = substr($_SERVER['SERVER_ADDR'],-3);
	$Subject = "[FUNDS] -".$emailtitle." ".$ipAddress;	//$date;
	$Body    = $listr;
	$AltBody = '';
	$recipients = '';
	$log = '';
	$recipients_BCC = array(
		'melvinmo@gmail.com' => 'mm',
		'support@fengins.com' => 'fi',
		'kmwong6688@gmail.com' => 'kk',
		// ..
	 );
	 
	 $send = mailto($Subject,$Body,$AltBody,$recipients,$recipients_BCC,$log);

}
		
$mysqli->close();
?>