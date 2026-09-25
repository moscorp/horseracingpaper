<?php

function hs($fulldate){
    $ch = curl_init();
    
    $apiUrl = "https://rbwm-api.hsbc.com.hk/pws-hk-hase-mpfunitprice-papi-prod-proxy/v1/mpf/getDailyUnitPriceByCodeDate";
    $locale = "zh";
    // $unitYear = "2024";
    // $unitMth = "05";
    // $unitDay = "23";
    $prodCode = "HSBCTRUSTPLUS";
    
    $dateParts = explode("-", $fulldate);
    $unitYear = $dateParts[0];
    $unitMth = $dateParts[1];
    $unitDay = $dateParts[2];
    // Prepare the payload
    $payload = json_encode([
        "locale" => $locale,
        "unitYear" => $unitYear,
        "unitMth" => $unitMth,
        "unitDay" => $unitDay,
        "prodCode" => $prodCode
    ]);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    
    $fund = array();
    if(curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
    } else {
        // Process the response
        $data = json_decode($response, true);
        $trustPlusList = $data['trustPlusList'];
    
        foreach($trustPlusList as $funds){
            $fund[$fulldate][$funds['FUND_CODE']]['FUND_CODE'] = $funds['FUND_CODE'];
            $fund[$fulldate][$funds['FUND_CODE']]['FUND_NAME'] = $funds['FUND_NAME'];
            $fund[$fulldate][$funds['FUND_CODE']]['FUND_CURRENCY'] = $funds['FUND_CURRENCY'];
            $fund[$fulldate][$funds['FUND_CODE']]['PRICE'] = $funds['ASK_PRICE'];
        }
    }
        // print_r($fund);
    
        //var_dump($data);
        // you can fetch information what you want from that json.
        // echo json_encode($trustPlusList);
        // print_r($trustPlusList);
        // $trustPlusFundNameList = $data['trustPlusFundNameList'];
        // print_r($trustPlusFundNameList);
        // $fundName = $trustPlusFundNameList['ANEF'];
        
    // Close cURL session
    curl_close($ch);
    
    return $fund;
}
?>
