<?php

function fetchHorseHistory($ids, $meetingDate, $raceNumber, $venCode) {
// GraphQL query
$query = <<<'GRAPHQL'
query SimulcastRaceInfo($ids: [String!], $raceNumber: String, $meetingDate: String, $venCode: String) {
  simulcastHorse(ids: $ids, raceNumber: $raceNumber, meetingDate: $meetingDate, venCode: $venCode) {
    id
    horseFormRecord {
      id
      date
      jockey {
        code
        name_ch
        name_en
      }
      trainer {
        name_ch
        name_en
      }
      raceNo
      placeNo
      ruPlace
      venue {
        code
        chinese
        english
      }
      track {
        english
        chinese
        code
      }
      gear
      comments {
        code
        chinese
        english
      }
      winners {
        pos
        name {
          chinese
          english
        }
        cnty {
          code
          chinese
          english
        }
      }
      med {
        code
        chinese
        english
      }
      raceName {
        code
        chinese
        english
      }
      course {
        code
        chinese
        english
      }
      raceId
      raceIndex
      runnerStatus
      racingComments {
        name
        date
      }
      winOdds
      resultIndex
      horseWeight
      actualWeight
      marginOrLBW {
        code
        chinese
        english
      }
      season
      runnerResults {
        id
      }
      racecourse {
        code
        chinese
        english
      }
      going {
        code
        chinese
        english
      }
      sectionTimeSplits {
        sectionTime
        gpSec
        sectionNo
      }
      raceTimeSplits {
        sectionNo
        gpSec
        sectionTime
      }
      sectionInfos {
        sectionNo
        ruPos
        sectionTime
      }
      barrierDrNo
      distance
      finalTimeOfRace
      raceCountry {
        code
        chinese
        english
      }
      cntyShortNm {
        code
        chinese
        english
      }
      racecourse {
        code
        chinese
        english
      }
      numOfStarters
      currency {
        english
        code
        chinese
      }
      ageCondition {
        code
        english
        chinese
      }
      condition {
        code
        english
        chinese
      }
      prizeMoney
      fav
      placingRemarks
      className {
        chinese
        code
        english
      }
      raceTurn {
        chinese
        code
        english
      }
    }
    brandNumber
  }
}
GRAPHQL;

    // Prepare the variables array
    $variables = [
        'ids' => $ids,
        'meetingDate' => $meetingDate,
        'raceNumber' => $raceNumber,
        'venCode' => $venCode
    ];

    // Prepare the payload
    $payload = [
        'query' => $query,
        'variables' => $variables
    ];

    // Set the headers
    $headers = [
        'Content-Type: application/json',
        'Accept-Encoding: gzip, deflate' // Enable compression
    ];

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, "https://info.cld.hkjc.com/graphql/base/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_ENCODING, ''); // Allow cURL to handle encoding

    // Execute the request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        return [];
    }

    // Print the raw response for debugging
    // echo "Raw response:\n";
    // print_r($response);

    // Decode the response
    $data = json_decode($response, true);

    // Close the cURL session
    curl_close($ch);

    // Initialize raceinfo array
    $raceinfo = [];

    // Check if data is available and populate the raceinfo array
    if (isset($data['data']['simulcastHorse'])) {
        // foreach ($data['data']['simulcastHorse'] as $horse) {
        //     $raceinfo[] = [
        //         'id' => $horse['id'],
        //         'horseFormRecord' => $horse['horseFormRecord'],
        //         'brandNumber' => $horse['brandNumber'],
        //     ];
        // }
        foreach ($data['data']['simulcastHorse'] as $horse) {
            $horseData = [
                'id' => $horse['id'],
                'horseFormRecord' => [],
                $ids  => []
            ];

            if (isset($horse['horseFormRecord'])) {
                foreach ($horse['horseFormRecord'] as $formRecord) {
                    $dateFormatted = (new DateTime($formRecord['date']))->format('Y-m-d');
                    $horseData[$ids][] = [
                        'id' => $formRecord['id'],
                        'date' => $dateFormatted,
                        'jockey' => $formRecord['jockey'],
                        'trainer' => $formRecord['trainer'],
                        'raceNo' => $formRecord['raceNo'],
                        'raceIndex' => $formRecord['raceIndex'],
                        'placeNo' => $formRecord['placeNo'],
                        'ruPlace' => $formRecord['ruPlace'],
                        'venue' => $formRecord['venue'],
                        'track' => $formRecord['track']['chinese'],
                        'gear' => $formRecord['gear'],
                        'comments' => $formRecord['comments'],
                        'winOdds' => $formRecord['winOdds'],
                        'horseWeight' => $formRecord['horseWeight'],
                        'actualWeight' => $formRecord['actualWeight'],
                        'going' => $formRecord['going']['chinese'],
                        'barrierDrNo' => $formRecord['barrierDrNo'],
                        'distance' => $formRecord['distance'],
                        'finalTimeOfRace' => $formRecord['finalTimeOfRace'],
                        'className' => $formRecord['className']['code'],
                        'winners' => array_map(function($winner) {
                            return [
                                'pos' => $winner['pos'],
                                'name' => $winner['name'],
                            ];
                        }, $formRecord['winners']),
                        // Include any additional fields as needed
                    ];
                }
            }

            $raceinfo[] = $horseData;
        }
    } else {
        // Print any errors in the response
        if (isset($data['errors'])) {
            echo "Errors:\n";
            print_r($data['errors']);
        }
    }

    return $raceinfo;
}

// Example usage
// $ids = ["20251018S11001"];
// $meetingDate = "20251018";
// $raceNumber = "1";
// $venCode = "S1"; // Replace with the actual venue code
// $raceinfo = fetchRaceInfo($ids, $meetingDate, $raceNumber, $venCode);

// // Output the raceinfo array for debugging
// print_r($raceinfo);
?>