<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spiral Number Grid</title>
    <style>
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f5f5f5;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(7, 50px);
            grid-gap: 5px;
        }
        .grid-item {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ccc;
            font-size: 20px;
            background-color: #e0e0e0;
        }
        .highlight {
            color: red; /* Highlight color */
        }
        .row-highlight {
            background-color: lightyellow; /* Row highlight color */
        }
        .col-highlight {
            background-color: lightgreen; /* Column highlight color */
        }
        .underline {
            text-decoration: underline; /* Underline style */
        }
        .double-underline {
            text-decoration: underline double; /* Double underline style */
        }
        .bold {
            font-weight: bold; /* Bold style */
        }
        .faded {
            opacity: 0.5; /* Reduced opacity */
        }
    </style>
</head>
<body>

<?php
function displaySpiralGrid($highlightNumbers, $highlight_estall, $highlight_est49, $highlight_max) {
    $size = 7; // Grid size
    $grid = array_fill(0, $size, array_fill(0, $size, 0)); // Create a 7x7 grid

    $x = $y = $size / 2; // Start from the center
    $directions = [[0, 1], [1, 0], [0, -1], [-1, 0]]; // Right, Down, Left, Up
    $dir = 0; // Start with the first direction
    $steps = 1; // Number of steps in the current direction
    $num = 1; // Start number

    while ($num <= 49) {
        for ($i = 0; $i < 2; $i++) { // Increase steps after completing two directions
            for ($j = 0; $j < $steps; $j++) {
                if ($num > 49) break; // Stop if number exceeds 49
                $grid[$y][$x] = $num++;
                $x += $directions[$dir][0]; // Move in the current direction
                $y += $directions[$dir][1];
            }
            $dir = ($dir + 1) % 4; // Change direction
        }
        $steps++; // Increase steps after completing two directions
    }

    // Check for row and column highlights
    $rowCounts = array_fill(0, $size, 0);
    $colCounts = array_fill(0, $size, 0);

    foreach ($highlightNumbers as $number) {
        foreach ($grid as $i => $row) {
            foreach ($row as $j => $cell) {
                if ($cell == $number) {
                    $rowCounts[$i]++;
                    $colCounts[$j]++;
                }
            }
        }
    }

    // Collect the grid output
    $output = '';
    $output .= "<div class=\"container\">";
    $output .= "<div class=\"grid\">";

    foreach ($grid as $i => $row) {
        foreach ($row as $j => $cell) {
            $rowClass = $rowCounts[$i] >= 2 ? 'row-highlight' : '';
            $colClass = $colCounts[$j] >= 2 ? 'col-highlight' : '';
            $highlightClass = in_array($cell, $highlightNumbers) ? 'highlight' : '';
            $underlineClass = in_array($cell, $highlight_estall) ? 'underline' : '';
            $doubleUnderlineClass = ($cell == $highlight_est49) ? 'double-underline' : '';
            $boldClass = ($cell == $highlight_max) ? 'bold' : '';
            // $fadedClass = ($rowClass || $colClass) ? 'faded' : ''; // Apply faded class if in highlighted row or column
            $fadedClass = ($rowClass || $colClass || $highlightClass || $underlineClass || $doubleUnderlineClass || $boldClass) ? '' : 'faded'; // Apply faded class if in highlighted row or column
            
            $output .= "<div class='grid-item $rowClass $colClass $highlightClass $underlineClass $doubleUnderlineClass $boldClass $fadedClass'>$cell</div>";
        }
    }

    $output .= "</div>"; // Close grid div
    $output .= "</div>"; // Close container div
    return $output; // Return the collected output
}

// Example usage:
$highlightNumbers = [2, 8, 15, 19, 22, 34]; // Numbers to highlight in red
$est1 = 8; 
$est2 = 22; 
$est3 = 3; 
$est4 = 14; 
$est5 = 27; 
$est6 = 35; 
$est7 = 46; 

// Combine individual variables into an array
$highlight_estall = [$est1, $est2, $est3, $est4, $est5, $est6, $est7]; 

$highlight_est49 = 49; // Number to double underline
$highlight_max = 34;   // Number to bold

echo displaySpiralGrid($highlightNumbers, $highlight_estall, $highlight_est49, $highlight_max); // Display the grid
?>
</body>
</html>