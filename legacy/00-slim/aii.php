<?php

$data = [
    'operationName' => 'racing',
    'query' => 'fragment raceFragment on Race {
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
    }',
    'variables' => [
        'date' => '2024-08-11',
        'venueCode' => 'S1',
        'foOddsTypes' => [],
        'foFilter' => ['top'],
        'resultOddsType' => []
    ]
];

$options = [
    CURLOPT_URL => 'https://info.cld.hkjc.com/graphql/base/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept-Encoding: gzip, deflate, br, zstd',
        'Accept-Language: zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7'
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data)
];

$curl = curl_init();
curl_setopt_array($curl, $options);
$response = curl_exec($curl);

$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

$header = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

$responseHeaders = explode("\n", $header);
$encodingHeader = null;
foreach ($responseHeaders as $header) {
    if (stripos($header, 'Content-Encoding:') === 0) {
        $encodingHeader = trim(substr($header, 16));
        break;
    }
}

if ($encodingHeader === 'gzip') {
    $body = gzinflate(substr($body, 10));
} elseif ($encodingHeader === 'deflate') {
    $body = gzuncompress($body);
}

curl_close($curl);

echo $body;
?>