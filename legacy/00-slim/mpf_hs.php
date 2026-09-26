

<?php
function getdates()
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://rbwm-api.hsbc.com.hk/pws-hk-hase-mpfunitprice-papi-prod-proxy/v1/mpf/getDailyUnitPriceByCodeDate');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Host: rbwm-api.hsbc.com.hk',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Accept-Language: zh-HK',
        'Accept-Encoding: gzip, deflate, br',
        'Content-Type: application/json; charset=utf-8',
        'X-Requested-With: XMLHttpRequest',
        'Origin: https://www.hangseng.com',
        'Referer: https://www.hangseng.com/',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: cross-site',
        'Te: trailers',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{"domainAndUserName":"hkportal","sqlKey":"MPF_LAST_VALUATION_DT","sqlParams":["MPF"]}');
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $decode = json_decode($response, true);
    $originalDate = $decode[0]['LAST_VALUATION_DT'];
    // Create a DateTime object from the original date string
    $dateTime = DateTime::createFromFormat('d/m/Y', $originalDate);
    // Format the date to Y-m-d
    $formattedDate = $dateTime->format('Y-m-d');
    // Output the formatted date
    return $formattedDate;
    curl_close($ch);
}


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

$lastupdate = getdates();
if(!empty($lastupdate)){
    $response = getfunds($lastupdate);
    // Convert JSON response to PHP array
    $array = json_decode($response, true);
    
    // Print the resulting array
    print_r($array);

}else{
    exit("Dates are empty!");
}