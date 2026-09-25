<?php
// test_sync.php - Quick test script

require_once 'HKJCRacingDataManager.php';

// Database configuration
$dbConfig = [
    'host' => 'localhost',
    'name' => 'fengrmkw_moosay',
    'user' => 'fengrmkw_melvin',
    'pass' => 'mopass.24626388'
];


try {
    $manager = new HKJCRacingDataManager(
        $dbConfig['host'],
        $dbConfig['name'],
        $dbConfig['user'],
        $dbConfig['pass']
    );
    
    echo "=== Testing Active Meetings ===\n";
    $active = $manager->getActiveMeetingsOnly();
    print_r($active);
    
    if (!empty($active)) {
        echo "\n=== Testing Current Active Meeting ===\n";
        $current = $manager->getCurrentActiveMeeting();
        print_r($current);
        
        echo "\n=== Testing Full Meeting Data ===\n";
        $meeting = $manager->getMeetingData($current['date'], $current['venueCode'], null, true);
        echo "Meeting ID: " . ($meeting['id'] ?? 'N/A') . "\n";
        echo "Total Races: " . ($meeting['totalNumberOfRace'] ?? 'N/A') . "\n";
        
        echo "\n=== Testing Database Join ===\n";
        $joined = $manager->getJoinedMeetingData($current['date'], $current['venueCode']);
        echo "Meetings found: " . count($joined) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}