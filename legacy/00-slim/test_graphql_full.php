<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'HKJC_graphql_client.php';

echo "=== Testing GraphQL - Production Ready ===\n\n";

$client = new GraphQLClient();

// 获取未来赛马日
echo "Getting upcoming race days (next 14 days)...\n";
try {
    $upcoming = $client->getUpcomingRaceDays(14);
    echo "Found " . count($upcoming) . " race days:\n";
    echo str_repeat("-", 50) . "\n";
    
    $currentDate = '';
    foreach ($upcoming as $day) {
        if ($currentDate != $day['date']) {
            echo "\n{$day['date']}:\n";
            $currentDate = $day['date'];
        }
        echo "  - {$day['venue_name']}: {$day['race_count']} races\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// 获取指定日期的详细数据
$targetDate = '2026-04-26';
$targetVenue = 'ST';
echo "Getting detailed data for {$targetDate} {$targetVenue}...\n";

try {
    $data = $client->getRacingData($targetDate, $targetVenue);
    
    if ($data && isset($data['raceMeetings'][0])) {
        $meeting = $data['raceMeetings'][0];
        echo "✓ Meeting: {$meeting['date']} - {$meeting['venueCode']}\n";
        echo "✓ Total races: {$meeting['totalNumberOfRace']}\n\n";
        
        echo "Race Details:\n";
        echo str_repeat("-", 60) . "\n";
        
        foreach ($meeting['races'] as $race) {
            $runnerCount = count($race['runners'] ?? []);
            echo sprintf("Race %2d: %-30s %3d runners\n", 
                $race['no'], 
                mb_substr($race['raceName_ch'], 0, 28), 
                $runnerCount
            );
        }
    } else {
        echo "No data found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}