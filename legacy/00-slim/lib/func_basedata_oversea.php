<?php

function fetchHorseInfo($ids, $meetingDate, $raceNumber) {
// GraphQL query
$query = <<<'GRAPHQL'
query SimulcastHorseProfile($ids: [String!], $meetingDate: String, $raceNumber: String) {
  simulcastHorse(ids: $ids, meetingDate: $meetingDate, raceNumber: $raceNumber) {
    age
    brandNumber
    earings
    colour {
      code
      chinese
      english
    }
    dam
    countryOfOrigin {
      chinese
      code
      english
    }
    sire
    sireOfDam
    rating
    name_ch
    name_en
    gender {
      chinese
      code
      english
    }
    id
    trainer {
      name_ch
      name_en
    }
    ownerName {
      chinese
      code
      english
    }
    winningRecord {
      totalStakes
      firstPlace
      secondPlace
      thirdPlace
      totalRun
    }
    performanceStats {
      type
      firstPlace
      secondPlace
      thirdPlace
      totalRun
      ssn
    }
    horseFormRecord {
      id
    }
  }
}
GRAPHQL;

    // Prepare the variables array
    $variables = [
        'ids' => $ids,
        'meetingDate' => $meetingDate,
        'raceNumber' => $raceNumber
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
    echo "Raw response:\n";
    

    // Decode the response
    $data = json_decode($response, true);
    // print_r($data);
    // Close the cURL session
    curl_close($ch);

    // Initialize horseinfo array
    $horseinfo = [];

    // Check if data is available and populate the horseinfo array
    if (isset($data['data']['simulcastHorse'])) {
        foreach ($data['data']['simulcastHorse'] as $horse) {
            $horseinfo[] = [
                'age' => $horse['age'],
                'brandNumber' => $horse['brandNumber'],
                'earings' => $horse['earings'],
                'colour' => $horse['colour'],
                'dam' => $horse['dam'],
                'countryOfOrigin' => $horse['countryOfOrigin'],
                'sire' => $horse['sire'],
                'sireOfDam' => $horse['sireOfDam'],
                'rating' => $horse['rating'],
                'name_ch' => $horse['name_ch'],
                'name_en' => $horse['name_en'],
                'gender' => $horse['gender'],
                'id' => $horse['id'],
                'trainer' => $horse['trainer'],
                'ownerName' => $horse['ownerName'],
                'winningRecord' => $horse['winningRecord'],
                'performanceStats' => $horse['performanceStats'],
                'horseFormRecord' => $horse['horseFormRecord'],
            ];
        }
    } else {
        // Print any errors in the response
        if (isset($data['errors'])) {
            echo "Errors:\n";
            print_r($data['errors']);
        }
    }

    return $horseinfo;
}

// Example usage
// $ids = ["20251018S11001"];
// $meetingDate = "20251018";
// $raceNumber = "1";
// $horseinfo = fetchHorseInfo($ids, $meetingDate, $raceNumber);

// // Output the horseinfo array for debugging
// print_r($horseinfo);
?>