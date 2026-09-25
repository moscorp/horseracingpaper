<?php
$url = 'https://info.cld.hkjc.com/graphql/base/';

// $data = '{"operationName" : racing, ';
// $data = "<<<'GRAPHQL'";
// $data .= '{"query":"fragment raceFragment on Race {\r\n  id\r\n  no\r\n  status\r\n  raceName_en\r\n  raceName_ch\r\n  postTime\r\n  country_en\r\n  country_ch\r\n  distance\r\n  wageringFieldSize\r\n  go_en\r\n  go_ch\r\n  ratingType\r\n  raceTrack {\r\n    description_en\r\n    description_ch\r\n  }\r\n  raceCourse {\r\n    description_en\r\n    description_ch\r\n    displayCode\r\n  }\r\n  raceClass_en\r\n  raceClass_ch\r\n  judgeSigns {\r\n    value_en\r\n  }\r\n}\r\n\r\nfragment racingBlockFragment on RaceMeeting {\r\n  jpEsts: pmPools(\r\n    oddsTypes: [TCE, TRI, FF, QTT, DT, TT, SixUP]\r\n    filters: [\"jackpot\", \"estimatedDividend\"]\r\n  ) {\r\n    leg {\r\n      number\r\n      races\r\n    }\r\n    oddsType\r\n    jackpot\r\n    estimatedDividend\r\n    mergedPoolId\r\n  }\r\n  poolInvs: pmPools(\r\n    oddsTypes: [WIN, PLA, QIN, QPL, CWA, CWB, CWC, IWN, FCT, TCE, TRI, FF, QTT, DBL, TBL, DT, TT, SixUP]\r\n  ) {\r\n    id\r\n    leg {\r\n      races\r\n    }\r\n  }\r\n  penetrometerReadings(filters: [\"first\"]) {\r\n    reading\r\n    readingTime\r\n  }\r\n  hammerReadings(filters: [\"first\"]) {\r\n    reading\r\n    readingTime\r\n  }\r\n  changeHistories(filters: [\"top3\"]) {\r\n    type\r\n    time\r\n    raceNo\r\n    runnerNo\r\n    horseName_ch\r\n    horseName_en\r\n    jockeyName_ch\r\n    jockeyName_en\r\n    scratchHorseName_ch\r\n    scratchHorseName_en\r\n    handicapWeight\r\n    scrResvIndicator\r\n  }\r\n}\r\n\r\nfragment racingFoPoolFragment on RacingFoPool {\r\n  instNo\r\n  poolId\r\n  oddsType\r\n  status\r\n  sellStatus\r\n  otherSelNo\r\n  inplayUpTo\r\n  expStartDateTime\r\n  expStopDateTime\r\n  raceStopSellNo\r\n  raceStopSellStatus\r\n  includeRaces\r\n  excludeRaces\r\n  lastUpdateTime\r\n  selections {\r\n    order\r\n    number\r\n    code\r\n    name_en\r\n    name_ch\r\n    scheduleRides\r\n    remainingRides\r\n    points\r\n    lineId\r\n    combId\r\n    combStatus\r\n    openOdds\r\n    prevOdds\r\n    currentOdds\r\n    results {\r\n      raceNo\r\n      points\r\n      point1st\r\n      point2nd\r\n      point3rd\r\n      dhRmk1st\r\n      dhRmk2nd\r\n      dhRmk3rd\r\n      count1st\r\n      count2nd\r\n      count3rd\r\n      count4th\r\n      numerator4th\r\n      denominator4th\r\n    }\r\n  }\r\n  otherSelections {\r\n    order\r\n    code\r\n    name_en\r\n    name_ch\r\n    scheduleRides\r\n    remainingRides\r\n    points\r\n    results {\r\n      raceNo\r\n      points\r\n      point1st\r\n      point2nd\r\n      point3rd\r\n      dhRmk1st\r\n      dhRmk2nd\r\n      dhRmk3rd\r\n      count1st\r\n      count2nd\r\n      count3rd\r\n      count4th\r\n      numerator4th\r\n      denominator4th\r\n    }\r\n  }\r\n}\r\n\r\nquery racing($date: String, $venueCode: String, $foOddsTypes: [OddsType], $foFilter: [String], $resultOddsType: [OddsType]) {\r\n  timeOffset {\r\n    rc\r\n  }\r\n  activeMeetings: raceMeetings {\r\n    id\r\n    venueCode\r\n    date\r\n    status\r\n    races {\r\n      no\r\n      postTime\r\n      status\r\n      wageringFieldSize\r\n    }\r\n  }\r\n  raceMeetings(date: $date, venueCode: $venueCode) {\r\n    id\r\n    status\r\n    venueCode\r\n    date\r\n    totalNumberOfRace\r\n    currentNumberOfRace\r\n    dateOfWeek\r\n    meetingType\r\n    totalInvestment\r\n    country {\r\n      code\r\n      namech\r\n      nameen\r\n      seq\r\n    }\r\n    races {\r\n      ...raceFragment\r\n      runners {\r\n        id\r\n        no\r\n        standbyNo\r\n        status\r\n        name_ch\r\n        name_en\r\n        horse {\r\n          id\r\n          code\r\n        }\r\n        color\r\n        barrierDrawNumber\r\n        handicapWeight\r\n        currentWeight\r\n        currentRating\r\n        internationalRating\r\n        gearInfo\r\n        racingColorFileName\r\n        allowance\r\n        trainerPreference\r\n        last6run\r\n        saddleClothNo\r\n        trumpCard\r\n        priority\r\n        finalPosition\r\n        deadHeat\r\n        winOdds\r\n        jockey {\r\n          code\r\n          name_en\r\n          name_ch\r\n        }\r\n        trainer {\r\n          code\r\n          name_en\r\n          name_ch\r\n        }\r\n      }\r\n    }\r\n    obSt: pmPools(oddsTypes: [WIN, PLA]) {\r\n      leg {\r\n        races\r\n      }\r\n      oddsType\r\n      comingleStatus\r\n    }\r\n    poolInvs: pmPools(\r\n      oddsTypes: [WIN, PLA, QIN, QPL, CWA, CWB, CWC, IWN, FCT, TCE, TRI, FF, QTT, DBL, TBL, DT, TT, SixUP]\r\n    ) {\r\n      id\r\n      leg {\r\n        number\r\n        races\r\n      }\r\n      status\r\n      sellStatus\r\n      oddsType\r\n      investment\r\n      mergedPoolId\r\n      lastUpdateTime\r\n    }\r\n    resPools: pmPools(oddsTypes: $resultOddsType) {\r\n      leg {\r\n        number\r\n        races\r\n      }\r\n      status\r\n      oddsType\r\n      name_en\r\n      name_ch\r\n      lastUpdateTime\r\n      dividends(officialOnly: true) {\r\n        winComb\r\n        type\r\n        div\r\n        seq\r\n        status\r\n        guarantee\r\n        partial\r\n        partialUnit\r\n      }\r\n      cWinSelections {\r\n        composite\r\n        name_ch\r\n        name_en\r\n        starters\r\n      }\r\n    }\r\n    ...racingBlockFragment\r\n    pmPools(oddsTypes: []) {\r\n      id\r\n    }\r\n    foPools(oddsTypes: $foOddsTypes, filters: $foFilter) {\r\n      ...racingFoPoolFragment\r\n    }\r\n    jkcInstNo: foPools(oddsTypes: [JKC], filters: [\"top\"]) {\r\n      instNo\r\n    }\r\n    tncInstNo: foPools(oddsTypes: [TNC], filters: [\"top\"]) {\r\n      instNo\r\n    }\r\n  }\r\n}","variables":{}}';
// $data .= "GRAPHQL;";

// $query = <<<'GRAPHQL'
// {"query":"fragment raceFragment on Race {\r\n  id\r\n  no\r\n  status\r\n  raceName_en\r\n  raceName_ch\r\n  postTime\r\n  country_en\r\n  country_ch\r\n  distance\r\n  wageringFieldSize\r\n  go_en\r\n  go_ch\r\n  ratingType\r\n  raceTrack {\r\n    description_en\r\n    description_ch\r\n  }\r\n  raceCourse {\r\n    description_en\r\n    description_ch\r\n    displayCode\r\n  }\r\n  raceClass_en\r\n  raceClass_ch\r\n  judgeSigns {\r\n    value_en\r\n  }\r\n}\r\n\r\nfragment racingBlockFragment on RaceMeeting {\r\n  jpEsts: pmPools(\r\n    oddsTypes: [TCE, TRI, FF, QTT, DT, TT, SixUP]\r\n    filters: [\"jackpot\", \"estimatedDividend\"]\r\n  ) {\r\n    leg {\r\n      number\r\n      races\r\n    }\r\n    oddsType\r\n    jackpot\r\n    estimatedDividend\r\n    mergedPoolId\r\n  }\r\n  poolInvs: pmPools(\r\n    oddsTypes: [WIN, PLA, QIN, QPL, CWA, CWB, CWC, IWN, FCT, TCE, TRI, FF, QTT, DBL, TBL, DT, TT, SixUP]\r\n  ) {\r\n    id\r\n    leg {\r\n      races\r\n    }\r\n  }\r\n  penetrometerReadings(filters: [\"first\"]) {\r\n    reading\r\n    readingTime\r\n  }\r\n  hammerReadings(filters: [\"first\"]) {\r\n    reading\r\n    readingTime\r\n  }\r\n  changeHistories(filters: [\"top3\"]) {\r\n    type\r\n    time\r\n    raceNo\r\n    runnerNo\r\n    horseName_ch\r\n    horseName_en\r\n    jockeyName_ch\r\n    jockeyName_en\r\n    scratchHorseName_ch\r\n    scratchHorseName_en\r\n    handicapWeight\r\n    scrResvIndicator\r\n  }\r\n}\r\n\r\nfragment racingFoPoolFragment on RacingFoPool {\r\n  instNo\r\n  poolId\r\n  oddsType\r\n  status\r\n  sellStatus\r\n  otherSelNo\r\n  inplayUpTo\r\n  expStartDateTime\r\n  expStopDateTime\r\n  raceStopSellNo\r\n  raceStopSellStatus\r\n  includeRaces\r\n  excludeRaces\r\n  lastUpdateTime\r\n  selections {\r\n    order\r\n    number\r\n    code\r\n    name_en\r\n    name_ch\r\n    scheduleRides\r\n    remainingRides\r\n    points\r\n    lineId\r\n    combId\r\n    combStatus\r\n    openOdds\r\n    prevOdds\r\n    currentOdds\r\n    results {\r\n      raceNo\r\n      points\r\n      point1st\r\n      point2nd\r\n      point3rd\r\n      dhRmk1st\r\n      dhRmk2nd\r\n      dhRmk3rd\r\n      count1st\r\n      count2nd\r\n      count3rd\r\n      count4th\r\n      numerator4th\r\n      denominator4th\r\n    }\r\n  }\r\n  otherSelections {\r\n    order\r\n    code\r\n    name_en\r\n    name_ch\r\n    scheduleRides\r\n    remainingRides\r\n    points\r\n    results {\r\n      raceNo\r\n      points\r\n      point1st\r\n      point2nd\r\n      point3rd\r\n      dhRmk1st\r\n      dhRmk2nd\r\n      dhRmk3rd\r\n      count1st\r\n      count2nd\r\n      count3rd\r\n      count4th\r\n      numerator4th\r\n      denominator4th\r\n    }\r\n  }\r\n}\r\n\r\nquery racing($date: String, $venueCode: String, $foOddsTypes: [OddsType], $foFilter: [String], $resultOddsType: [OddsType]) {\r\n  timeOffset {\r\n    rc\r\n  }\r\n  activeMeetings: raceMeetings {\r\n    id\r\n    venueCode\r\n    date\r\n    status\r\n    races {\r\n      no\r\n      postTime\r\n      status\r\n      wageringFieldSize\r\n    }\r\n  }\r\n  raceMeetings(date: $date, venueCode: $venueCode) {\r\n    id\r\n    status\r\n    venueCode\r\n    date\r\n    totalNumberOfRace\r\n    currentNumberOfRace\r\n    dateOfWeek\r\n    meetingType\r\n    totalInvestment\r\n    country {\r\n      code\r\n      namech\r\n      nameen\r\n      seq\r\n    }\r\n    races {\r\n      ...raceFragment\r\n      runners {\r\n        id\r\n        no\r\n        standbyNo\r\n        status\r\n        name_ch\r\n        name_en\r\n        horse {\r\n          id\r\n          code\r\n        }\r\n        color\r\n        barrierDrawNumber\r\n        handicapWeight\r\n        currentWeight\r\n        currentRating\r\n        internationalRating\r\n        gearInfo\r\n        racingColorFileName\r\n        allowance\r\n        trainerPreference\r\n        last6run\r\n        saddleClothNo\r\n        trumpCard\r\n        priority\r\n        finalPosition\r\n        deadHeat\r\n        winOdds\r\n        jockey {\r\n          code\r\n          name_en\r\n          name_ch\r\n        }\r\n        trainer {\r\n          code\r\n          name_en\r\n          name_ch\r\n        }\r\n      }\r\n    }\r\n    obSt: pmPools(oddsTypes: [WIN, PLA]) {\r\n      leg {\r\n        races\r\n      }\r\n      oddsType\r\n      comingleStatus\r\n    }\r\n    poolInvs: pmPools(\r\n      oddsTypes: [WIN, PLA, QIN, QPL, CWA, CWB, CWC, IWN, FCT, TCE, TRI, FF, QTT, DBL, TBL, DT, TT, SixUP]\r\n    ) {\r\n      id\r\n      leg {\r\n        number\r\n        races\r\n      }\r\n      status\r\n      sellStatus\r\n      oddsType\r\n      investment\r\n      mergedPoolId\r\n      lastUpdateTime\r\n    }\r\n    resPools: pmPools(oddsTypes: $resultOddsType) {\r\n      leg {\r\n        number\r\n        races\r\n      }\r\n      status\r\n      oddsType\r\n      name_en\r\n      name_ch\r\n      lastUpdateTime\r\n      dividends(officialOnly: true) {\r\n        winComb\r\n        type\r\n        div\r\n        seq\r\n        status\r\n        guarantee\r\n        partial\r\n        partialUnit\r\n      }\r\n      cWinSelections {\r\n        composite\r\n        name_ch\r\n        name_en\r\n        starters\r\n      }\r\n    }\r\n    ...racingBlockFragment\r\n    pmPools(oddsTypes: []) {\r\n      id\r\n    }\r\n    foPools(oddsTypes: $foOddsTypes, filters: $foFilter) {\r\n      ...racingFoPoolFragment\r\n    }\r\n    jkcInstNo: foPools(oddsTypes: [JKC], filters: [\"top\"]) {\r\n      instNo\r\n    }\r\n    tncInstNo: foPools(oddsTypes: [TNC], filters: [\"top\"]) {\r\n      instNo\r\n    }\r\n  }\r\n}","variables":{}}';
// GRAPHQL;

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
$variables = [];

// $headers = array(
//     'Content-Type: application/json',
// );

// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, $url);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POSTFIELDS,  json_encode($data)); //$data);

// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, $url);
// curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//     'Content-Type: application/json',
//     'Content-Encoding: gzip'
// ));
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, $url);
// curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//     'Content-Type: application/json',
//     'Content-Encoding: gzip'
// ));
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_POSTFIELDS, gzcompress(json_encode($query)));

// $response = curl_exec($ch);
// $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// $query = json_encode($data);
// $gzipQuery = gzencode($query, 9);

// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, $url);
// curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//     'Content-Type: application/json',
//     'Content-Encoding: gzip'
// ));
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_POSTFIELDS, $gzipQuery);

// $response = curl_exec($ch);
// $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// if ($httpCode >= 400) {
//     echo "Error: HTTP Status Code $httpCode\n";
//     echo "Raw Response:\n";
//     var_dump($response);
// } else {
//     $responseArray = json_decode($response, true);
//     if (isset($responseArray['data'])) {
//         // Access the data from the 'data' key
//         $data = $responseArray['data'];
//         // Display the data in a more organized way
//         echo "Result:\n";
//         print_r($data);
//     } else {
//         echo "No data found in the response.";
//     }
// }
// $query = json_encode($data);

// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, $url);
// curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//     'Content-Type: application/json',
//     // 'Content-Encoding: gzip' // Temporarily remove the gzip header
// ));
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

// $response = curl_exec($ch);
// $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// $curlError = curl_error($ch);

// if ($httpCode >= 400 || !empty($curlError)) {
//     echo "Error: HTTP Status Code $httpCode\n";
//     echo "cURL Error: $curlError\n";
//     echo "Raw Response:\n";
//     var_dump($response);

//     // Check the response for a JSON error message
//     $responseArray = json_decode($response, true);
//     if (isset($responseArray['error'])) {
//         echo "Error message: " . $responseArray['error'] . "\n";
//     }
// } else {
//     $responseArray = json_decode($response, true);
//     if (isset($responseArray['data'])) {
//         // Access the data from the 'data' key
//         $data = $responseArray['data'];
//         // Display the data in a more organized way
//         echo "Result:\n";
//         print_r($data);
//     } else {
//         echo "No data found in the response.";
//     }
// }

// $query = $data;
// $variables = json_encode(['date' => '2024-08-11', 'venueCode' => 'S1']);

// $payload = array(
//     'query' => $query,
//     'variables' => $variables
// );

// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, $url);
// curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//     'Content-Type: application/x-www-form-urlencoded'
// ));
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));

// $response = curl_exec($ch);
// $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// $curlError = curl_error($ch);

// if ($httpCode >= 400 || !empty($curlError)) {
//     echo "Error: HTTP Status Code $httpCode\n";
//     echo "cURL Error: $curlError\n";
//     echo "Raw Response:\n";
//     var_dump($response);

//     // Check the response for a JSON error message
//     $responseArray = json_decode($response, true);
//     if (isset($responseArray['error'])) {
//         echo "Error message: " . $responseArray['error'] . "\n";
//     }
// } else {
//     $responseArray = json_decode($response, true);
//     if (isset($responseArray['data'])) {
//         // Access the data from the 'data' key
//         $data = $responseArray['data'];
//         // Display the data in a more organized way
//         echo "Result:\n";
//         print_r($data);
//     } else {
//         echo "No data found in the response.";
//     }
// }

$query = $query;
// $variables = ['variable1' => 'value1', 'variable2' => 'value2'];
// $variables = json_encode(['date' => '2024-08-11', 'venueCode' => 'S1']);

$payload = [
    'query' => $query,
    'variables' => $variables
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json'
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

if ($httpCode >= 400 || !empty($curlError)) {
    echo "Error: HTTP Status Code $httpCode\n";
    echo "cURL Error: $curlError\n";
    echo "Raw Response:\n";
    var_dump($response);

    // Check the response for a JSON error message
    $responseArray = json_decode($response, true);
    if (isset($responseArray['error'])) {
        echo "Error message: " . $responseArray['error'] . "\n";
    }
} else {
    $responseArray = json_decode($response, true);
    if (isset($responseArray['data'])) {
        // Access the data from the 'data' key
        $data = $responseArray['data'];
        // Display the data in a more organized way
        echo "Result:\n";
        print_r($data);
    } else {
        echo "No data found in the response.";
    }
}
?>
