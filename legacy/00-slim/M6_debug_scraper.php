<?php
/**
 * Debug Scraper - Shows the actual HTML structure
 */

$url = 'http://www.9800.com.tw/lotto6/drop.html';
$html = file_get_contents($url);

if (!$html) {
    echo "Failed to fetch page.\n";
    exit(1);
}

echo "Page length: " . strlen($html) . "\n\n";

// Find all table rows
preg_match_all('/<TR[^>]*>(.*?)<\/TR>/is', $html, $rows);

echo "Found " . count($rows[0]) . " table rows.\n\n";

// Show first 5 rows
for ($i = 0; $i < min(5, count($rows[0])); $i++) {
    echo "Row " . ($i + 1) . ":\n";
    echo htmlspecialchars(substr($rows[0][$i], 0, 500)) . "\n";
    echo str_repeat("-", 80) . "\n";
}

// Look for numbers pattern specifically
preg_match_all('/\d{2}\s+\d{2}\s+\d{2}\s+\d{2}\s+\d{2}\s+\d{2}\s+\+\s+\d{2}/', $html, $numbers);
echo "\nFound " . count($numbers[0]) . " number patterns.\n";
if (!empty($numbers[0])) {
    echo "First number pattern: " . $numbers[0][0] . "\n";
}
?>