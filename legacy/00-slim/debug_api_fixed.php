<?php
// debug_api_fixed.php - Mirror your working basedata function

function testGraphQL($racingdate = null, $venueCode = null, $resultOddsType = null) {
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
    
    // Prepare variables exactly like your working code
    $foOddsTypes = [];
    $variables = [
        'date' => $racingdate,
        'venueCode' => $venueCode,
        'foOddsTypes' => $foOddsTypes,
        'resultOddsType' => $resultOddsType ? [$resultOddsType] : null
    ];
    
    // Remove null values
    $variables = array_filter($variables, function($value) {
        return $value !== null;
    });
    
    $payload = [
        'query' => $query,
        'variables' => $variables
    ];
    
    echo "=== REQUEST DEBUG ===\n";
    echo "URL: " . $url . "\n";
    echo "Variables: " . json_encode($variables, JSON_PRETTY_PRINT) . "\n\n";
    
    $headers = [
        'Content-Type: application/json',
        'Accept-Encoding: gzip, deflate',
        'Accept: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "\n=== RESPONSE DEBUG ===\n";
    echo "HTTP Code: " . $httpCode . "\n";
    
    if (curl_errno($ch)) {
        echo 'CURL Error: ' . curl_error($ch) . "\n";
    }
    
    curl_close($ch);
    
    echo "Response: " . $response . "\n";
    
    return json_decode($response, true);
}

// Test 1: Call without parameters (should work based on your code)
echo "\n=== TEST 1: No parameters (should get active meetings) ===\n";
$result1 = testGraphQL(null, null, null);

// Test 2: Call with specific date
echo "\n=== TEST 2: With date 2026-04-06 and venue ST ===\n";
$result2 = testGraphQL('2026-04-06', 'ST', null);