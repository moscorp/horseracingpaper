<?php
function pickodds($oddsarray, $date, $venueCode, $raceNo, $CurrPre) {
    // Initialize the array
    $odds = [];
    // echo $date.$venueCode.$raceNo.$CurrPre."<br>";
    $oddsarray = racingdata($date, $venueCode, $raceNo, $CurrPre);
    $Pre = $oddsarray['data']['raceMeetings'][0]['pmPools'];
    $venue = $venueCode;

    foreach ($Pre as $oddsdata) {
        $oddsType = $oddsdata['oddsType'];
        $id = $oddsdata['id'];
        preg_match("/{$oddsType}(\d+)/", $id, $matches);
        $raceno = $matches[1]; 
        // $venue = substr($oddsdata['id'], 8, 2);
        
        if (in_array($oddsType, ['QINPre', 'QPLPre', 'QIN', 'QPL', 'TRITop', 'FFTop', 'TCETop', 'QTTTop', 'DBL'])) {
            foreach ($oddsdata['oddsNodes'] as $racedata) {
                if (isset($racedata['combString'])) {
                    $odds[$venue][$raceno][$oddsType]['combString'][] = $racedata['combString'];
                    $odds[$venue][$raceno][$oddsType]['oddsValue'][] = $racedata['oddsValue'];
                    $odds[$venue][$raceno][$oddsType]['oddsDropValue'][] = $racedata['oddsDropValue'];
                    
                }
            }
            $topValue = isset($oddsdata['oddsNodes'][0]['combString']) ? $oddsdata['oddsNodes'][0]['combString'] : null;
            // $odds[$venue][$raceno][$oddsType]['top'] = isset($odds[$venue][$raceno][$oddsType]['top']) ? $odds[$venue][$raceno][$oddsType]['top'] : $topValue;
            
            if ($topValue !== null) {
                // Split the string into an array
                $topArray = explode(',', $topValue);
                
                // Convert to integers
                $topArray = array_map('intval', $topArray);
                
                // Now assign to $odds
                $odds[$venue][$raceno][$oddsType]['top'] = isset($odds[$venue][$raceno][$oddsType]['top']) ? $odds[$venue][$raceno][$oddsType]['top'] : $topArray;
            }
            
            // Initialize the count array for unique numbers
            $countArray = [];
            
            // Combine all combString values into a single array
            // $combStringArray = $odds[$venue][$raceno][$oddsType]['combString'];
            // Ensure that the necessary keys exist in the $odds array
            if (isset($odds[$venue]) && isset($odds[$venue][$raceno]) && isset($odds[$venue][$raceno][$oddsType])) {
                // Access the combString if everything is set
                $combStringArray = $odds[$venue][$raceno][$oddsType]['combString'];
            } else {
                // Handle the case where the keys don't exist
                $combStringArray = []; // Or set to a default value as needed
                // error_log("Warning: Missing keys in odds array for venue: $venue, race number: $raceno, odds type: $oddsType.");
            }
            
            // Initialize countArray
            // $countArray = [];
            
            // Check if $combStringArray is not empty
            if (!empty($combStringArray)) {
                // Iterate through the combString array to count occurrences
                foreach ($combStringArray as $combString) {
                    // Split the string into individual numbers
                    $numbers = explode(',', $combString);
                    
                    // Count each number
                    foreach ($numbers as $number) {
                        if (isset($countArray[$number])) {
                            $countArray[$number]++;
                        } else {
                            $countArray[$number] = 1; // Initialize the count
                        }
                    }
                }
            } else {
                // Handle the case where $combStringArray is empty (optional)
                // e.g., you could initialize $countArray or log a message
                $countArray = []; // Optional: Clear or initialize $countArray
            }
            $newCountArray = [];

            // Convert keys from string format to integer format
            foreach ($countArray as $key => $value) {
                $newKey = (int)$key; // Convert string key to integer
                $newCountArray[$newKey] = $value;
            }
            
            // Store the new countArray back to the odds array
            $odds[$venue][$raceno][$oddsType]['countArray'] = $newCountArray;
            
            // Store the countArray in a single entry
            // $odds[$venue][$raceno][$oddsType]['countArray'] = $countArray;
        }else{
            foreach ($oddsdata['oddsNodes'] as $racedata) {
                $horseno = $racedata['combString'] * 1; // Ensure this is numeric
                $odds[$venue][$raceno][$horseno][$CurrPre][$oddsType] = $racedata['oddsValue'];
                $odds[$venue][$raceno][$horseno][$CurrPre]['oddsDropValue'] = $racedata['oddsDropValue'];
                $odds[$venue][$raceno][$horseno][$CurrPre]['hotFavourite'] = $racedata['hotFavourite'];
            
                // $plapre = $odds[$venue][$raceno][$horseno]['Pre']['PLAPre'];
                // Initialize the variable
                $plapre = null; // Default value
                
                // Check if the required keys exist in the odds array
                if (isset($odds[$venue][$raceno][$horseno]['Pre']['PLAPre'])) {
                    $plapre = $odds[$venue][$raceno][$horseno]['Pre']['PLAPre'];
                } else {
                    // Handle the case where the keys are not found
                    // error_log("Warning: PLAPre for venue '$venue', race number '$raceno', horse number '$horseno' not found in odds array.");
                    $plapre = 'N/A'; // Default value if not found
                }
                // $winpre = $odds[$venue][$raceno][$horseno]['Pre']['WINPre'];
                // Initialize the variable
                $winpre = null; // Default value
                
                // Check if the required keys exist in the odds array
                if (isset($odds[$venue][$raceno][$horseno]['Pre']) && isset($odds[$venue][$raceno][$horseno]['Pre']['WINPre'])) {
                    $winpre = $odds[$venue][$raceno][$horseno]['Pre']['WINPre'];
                } else {
                    // Handle the case where the keys are not found
                    // error_log("Warning: WINPre for venue '$venue', race number '$raceno', horse number '$horseno' not found in odds array.");
                    $winpre = 'N/A'; // Default value if not found
                }
                // $prop = number_format(100 * $plapre / $winpre, 0);
                if (is_numeric($plapre) && is_numeric($winpre) && $winpre != 0) {
                    $prop = number_format(100 * $plapre / $winpre, 0);
                } else {
                    // Handle the case where inputs are not numeric or winpre is zero
                    $prop = 0; // or set to another default value, or throw an error
                }
                $odds[$venue][$raceno][$horseno][$CurrPre]['proppre'] = $prop;
            }
            
        }
        if (in_array($oddsType, ['DBL'])) {
            // $leg = $oddsdata['oddsType']['races'][0]."-".$oddsdata['oddsType']['races'][1];
            if (isset($oddsdata['oddsType']['races']) && is_array($oddsdata['oddsType']['races'])) {
                // Check if there are at least two races
                if (count($oddsdata['oddsType']['races']) >= 2) {
                    $leg = $oddsdata['oddsType']['races'][0] . "-" . $oddsdata['oddsType']['races'][1];
                } else {
                    // Handle the case where there are not enough races
                    $leg = ''; // or some default value
                }
            } else {
                // Handle the case where 'races' is not set or not an array
                $leg = ''; // or some default value
            }
            
            foreach ($oddsdata['oddsNodes'] as $racedata) {
                // Split the combString into an array
                $horsenos = explode('/', $racedata['combString']); // e.g., ["01", "02"]
        
                // Ensure we have at least two horse numbers
                if (count($horsenos) >= 2) {
                    $horseno1 = ltrim($horsenos[0], '0'); // Pull 1 for $horseno1
                    $horseno2 = ltrim($horsenos[1], '0'); // Pull 2 for $horseno2
                    
                    $odds[$venue][$raceno]['DBL2'][$horseno1][$horseno2]['oddsValue'] = $racedata['oddsValue'];
                    $odds[$venue][$raceno]['DBL2'][$horseno1][$horseno2]['oddsDropValue'] = $racedata['oddsDropValue'];
                }
            }
        }
    }
    
    // Debugging output to check the structure
    // print_r($odds);

    return $odds;
}

function basedata($raceingdate,$venueCode,$resultOddsType){

    $url = 'https://info.cld.hkjc.com/graphql/base/';
    
    $query = <<<'GRAPHQL'
    fragment raceFragment on Race {
      id
      no
      status
      raceName_en
      raceName_ch
      postTime
      country_en
      country_ch
      distance
      wageringFieldSize
      go_en
      go_ch
      ratingType
      raceTrack {
        description_en
        description_ch
      }
      raceCourse {
        description_en
        description_ch
        displayCode
      }
      raceClass_en
      raceClass_ch
      judgeSigns {
        value_en
      }
    }
    
    fragment racingBlockFragment on RaceMeeting {
      jpEsts: pmPools(
        oddsTypes: [TCE, TRI, FF, QTT, DT, TT, SixUP]
        filters: ["jackpot", "estimatedDividend"]
      ) {
        leg {
          number
          races
        }
        oddsType
        jackpot
        estimatedDividend
        mergedPoolId
      }
      poolInvs: pmPools(
        oddsTypes: [WIN, PLA, QIN, QPL, CWA, CWB, CWC, IWN, FCT, TCE, TRI, FF, QTT, DBL, TBL, DT, TT, SixUP]
      ) {
        id
        leg {
          races
        }
      }
      penetrometerReadings(filters: ["first"]) {
        reading
        readingTime
      }
      hammerReadings(filters: ["first"]) {
        reading
        readingTime
      }
      changeHistories(filters: ["top3"]) {
        type
        time
        raceNo
        runnerNo
        horseName_ch
        horseName_en
        jockeyName_ch
        jockeyName_en
        scratchHorseName_ch
        scratchHorseName_en
        handicapWeight
        scrResvIndicator
      }
    }
    
    fragment racingFoPoolFragment on RacingFoPool {
      instNo
      poolId
      oddsType
      status
      sellStatus
      otherSelNo
      inplayUpTo
      expStartDateTime
      expStopDateTime
      raceStopSellNo
      raceStopSellStatus
      includeRaces
      excludeRaces
      lastUpdateTime
      selections {
        order
        number
        code
        name_en
        name_ch
        scheduleRides
        remainingRides
        points
        lineId
        combId
        combStatus
        openOdds
        prevOdds
        currentOdds
        results {
          raceNo
          points
          point1st
          point2nd
          point3rd
          dhRmk1st
          dhRmk2nd
          dhRmk3rd
          count1st
          count2nd
          count3rd
          count4th
          numerator4th
          denominator4th
        }
      }
      otherSelections {
        order
        code
        name_en
        name_ch
        scheduleRides
        remainingRides
        points
        results {
          raceNo
          points
          point1st
          point2nd
          point3rd
          dhRmk1st
          dhRmk2nd
          dhRmk3rd
          count1st
          count2nd
          count3rd
          count4th
          numerator4th
          denominator4th
        }
      }
    }
    
    query racing($date: String, $venueCode: String, $foOddsTypes: [OddsType], $foFilter: [String], $resultOddsType: [OddsType]) {
      timeOffset {
        rc
      }
      activeMeetings: raceMeetings {
        id
        venueCode
        date
        status
        races {
          no
          postTime
          status
          wageringFieldSize
        }
      }
      raceMeetings(date: $date, venueCode: $venueCode) {
        id
        status
        venueCode
        date
        totalNumberOfRace
        currentNumberOfRace
        dateOfWeek
        meetingType
        totalInvestment
        country {
          code
          namech
          nameen
          seq
        }
        races {
          ...raceFragment
          runners {
            id
            no
            standbyNo
            status
            name_ch
            name_en
            horse {
              id
              code
            }
            color
            barrierDrawNumber
            handicapWeight
            currentWeight
            currentRating
            internationalRating
            gearInfo
            racingColorFileName
            allowance
            trainerPreference
            last6run
            saddleClothNo
            trumpCard
            priority
            finalPosition
            deadHeat
            winOdds
            jockey {
              code
              name_en
              name_ch
            }
            trainer {
              code
              name_en
              name_ch
            }
          }
        }
        obSt: pmPools(oddsTypes: [WIN, PLA]) {
          leg {
            races
          }
          oddsType
          comingleStatus
        }
        poolInvs: pmPools(
          oddsTypes: [WIN, PLA, QIN, QPL, CWA, CWB, CWC, IWN, FCT, TCE, TRI, FF, QTT, DBL, TBL, DT, TT, SixUP]
        ) {
          id
          leg {
            number
            races
          }
          status
          sellStatus
          oddsType
          investment
          mergedPoolId
          lastUpdateTime
        }
        resPools: pmPools(oddsTypes: $resultOddsType) {
          leg {
            number
            races
          }
          status
          oddsType
          name_en
          name_ch
          lastUpdateTime
          dividends(officialOnly: true) {
            winComb
            type
            div
            seq
            status
            guarantee
            partial
            partialUnit
          }
          cWinSelections {
            composite
            name_ch
            name_en
            starters
          }
        }
        ...racingBlockFragment
        pmPools(oddsTypes: []) {
          id
        }
        foPools(oddsTypes: $foOddsTypes, filters: $foFilter) {
          ...racingFoPoolFragment
        }
        jkcInstNo: foPools(oddsTypes: [JKC], filters: ["top"]) {
          instNo
        }
        tncInstNo: foPools(oddsTypes: [TNC], filters: ["top"]) {
          instNo
        }
      }
    }
    GRAPHQL;
    
    
    // $variables = [];
    // $variables = ['date' => '$raceingdate', 'venueCode' => '$venueCode', 'resultOddsType' => $resultOddsType];
    // $variables = [
    //     'date' => $raceingdate, // Remove '$' to reference the variable
    //     'venueCode' => $venueCode, // Remove '$' to reference the variable
    //     'foOddsTypes' => $foOddsTypes,
    //     'resultOddsType' => $resultOddsType
    // ];
    // Ensure $foOddsTypes is defined
    if (!isset($foOddsTypes)) {
        $foOddsTypes = []; // Default value (or you can assign a specific default value)
    }
    
    // Prepare the variables array
    $variables = [
        'date' => $raceingdate, // Remove '$' to reference the variable
        'venueCode' => $venueCode, // Remove '$' to reference the variable
        'foOddsTypes' => $foOddsTypes,
        'resultOddsType' => $resultOddsType
    ];
    
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
            // var_dump($responseData);
        } else {
            echo "Response is not valid JSON.\n";
            echo "Raw response:\n";
            // var_dump($response);
        }
    }
    
    curl_close($ch);
    $data = $responseData;
    
    return $data;
}

function racingdataxx($raceingdate,$venueCode,$raceNo,$CurrPre){

    $url = 'https://info.cld.hkjc.com/graphql/base/';
    switch ($CurrPre) {
        case "Pre" :  $oddsTypes = ['WINPre', 'PLAPre', 'QINPre', 'QPLPre']; break;    //["WINPre","PLAPre","QINPre","QPLPre"]; break;
        case "Curr":  $oddsTypes = ['WIN','PLA','QIN','QPL']; break;
        default    :  $oddsTypes = ['WIN','PLA']; break;
    }
    
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
            // var_dump($responseData);
        } else {
            echo "Response is not valid JSON.\n";
            echo "Raw response:\n";
            // var_dump($response);
        }
    }
    
    curl_close($ch);
    $data = $responseData;
    
    return $data;
}

function racingdata($raceingdate, $venueCode, $raceNo, $CurrPre) {
    $url = 'https://info.cld.hkjc.com/graphql/base/';
    
    switch ($CurrPre) {
        case "Pre":
            $oddsTypes = ['WINPre', 'PLAPre', 'QINPre', 'QPLPre'];
            break;
        case "Curr":
            $oddsTypes = ['WIN', 'PLA', 'QIN', 'QPL', 'TRITop', 'FFTop', 'TCETop', 'QTTTop', 'DBL'];
            break;
        default:
            $oddsTypes = ['WIN', 'PLA', 'QIN', 'QPL', 'TRITop', 'FFTop', 'TCETop', 'QTTTop', 'DBL'];
            break;
    }

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

    $variables = [
        'date' => $raceingdate, // Remove '$' to reference the variable
        'venueCode' => $venueCode, // Remove '$' to reference the variable
        'raceNo' => $raceNo,
        'oddsTypes' => $oddsTypes
    ];

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
        echo 'Error: ' . curl_error($ch);
    } else {
        // Try to decode if it's JSON
        $responseData = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Successful JSON decode
            return $responseData;
        } else {
            echo "Response is not valid JSON.\n";
            echo "Raw response:\n";
            var_dump($response);
        }
    }

    curl_close($ch);
    // return null; // Return null if there's an error or no valid response
    $data = $responseData;
    
    return $data;
}

?>