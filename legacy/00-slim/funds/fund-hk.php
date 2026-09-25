<?php
function getdates()
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
        'Accept-Encoding: gzip, deflate, br',
        'Content-Type: application/json; charset=utf-8',
        'X-Requested-With: XMLHttpRequest',
        'Origin: https://www.sunlife.com.hk',
        'Referer: https://www.sunlife.com.hk/zh-hant/investments/mpf-orso-fund-prices-performance/mpf-fund-prices-performance.type-MPF/',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-origin',
        'Te: trailers',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{"domainAndUserName":"hkportal","sqlKey":"MPF_LAST_VALUATION_DT","sqlParams":["MPF"]}');
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }

    $decode = json_decode($response, true);
    if (isset($decode[0]['LAST_VALUATION_DT'])) {
        $originalDate = $decode[0]['LAST_VALUATION_DT'];
        // Create a DateTime object from the original date string
        $dateTime = DateTime::createFromFormat('d/m/Y', $originalDate);
        // Format the date to Y-m-d
        return $dateTime->format('Y-m-d');
    } else {
        return null;
    }
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
        'Accept-Encoding: gzip, deflate, br',
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
    curl_close($ch);

    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }

    // Handle potential gzip encoding
    if (isset($response) && !empty($response)) {
        $decodedResponse = gzdecode($response);
        if ($decodedResponse === false) {
            // If gzdecode fails, the response might not be compressed, so use the original response
            $decodedResponse = $response;
        }
        return $decodedResponse;
    }

    return null;
}

$lastupdate = getdates();
if (!empty($lastupdate)) {
    echo getfunds($lastupdate);
} else {
    exit("Dates are empty!");
}
?>
