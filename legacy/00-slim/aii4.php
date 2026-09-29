<?php
  $url = 'https://info.cld.hkjc.com/graphql/base/';
  $venueCode = "S2";
  
    // switch ($oddsTypes) {
    //     case "Pre" :  $oddsTypes = ["WINPre","PLAPre","QINPre","QPLPre"]; break;
    //     case "Curr":  $oddsTypes = ["WIN","PLA","QIN","QPL"]; break;
    //     default    :  $oddsTypesarray = ["WIN","PLA","QIN","QPL"]; break;
    // }
    
  $date= "2024-08-11";
  $venueCode= 'S2';
  $raceNo= 1;
  $oddsTypes=  ['WINPre', 'PLAPre'];
  
    $query = <<<'GRAPHQL'
    query racing($date: String, $venueCode: String, $oddsTypes: [OddsType], $raceNo: Int) {
      raceMeetings(date: $date, venueCode: $venueCode) {
        pmPools(oddsTypes: $oddsTypes, raceNo: $raceNo) {
          id
          status
          sellStatus
          oddsType
          lastUpdateTime
          guarantee
          minTicketCost
          name_en
          name_ch
          leg {
            number
            races
          }
          cWinSelections {
            composite
            name_ch
            name_en
            starters
          }
          oddsNodes {
            combString
            oddsValue
            hotFavourite
            oddsDropValue
            bankerOdds {
              combString
              oddsValue
            }
          }
        }
      }
    }
    GRAPHQL;
    
    
    $variables = [];
    $variables = ['date' => '$raceingdate', 'venueCode' => '$venueCode', 'raceNo' => $raceNo, 'oddsTypes' => $oddsTypes];
    
    $payload = [
        'query' => $query,
        'variables' => $variables
    ];
    
    $headers = [
        'Content-Type: application/json',
        'Accept-Encoding: gzip, deflate' // Enable compression
    ];
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_ENCODING, ''); // Allow cURL to handle encoding
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        // Try to decode if it's JSON
        $responseData = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            var_dump($responseData);
        } else {
            echo "Response is not valid JSON.\n";
            echo "Raw response:\n";
            var_dump($response);
        }
    }
    
    curl_close($ch);
    $data = $responseData;
    

?>