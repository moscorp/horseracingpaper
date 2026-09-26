<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spiral Number Grid</title>
</head>
<body>

<?php
function displaySpiralGrid($highlightNumbers, $highlight_estall, $highlight_est49, $highlight_max) {
    $size = 7; // Grid size
    $grid = array_fill(0, $size, array_fill(0, $size, 0)); // Create a 7x7 grid

    $x = $y = (int)($size / 2); // Start from the center
    $directions = [[0, -1], [-1, 0], [0, 1], [1, 0]]; // Left, Up, Right, Down (anti-clockwise)
    $directions = [[0, 1], [1, 0], [0, -1], [-1, 0]]; // Right, Down, Left, Up (clockwise)
    $directions = [[1, 0], [0, 1], [-1, 0], [0, -1]]; // Right, Down, Left, Up (clockwise)
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
    $output .= "<div style='display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f5f5f5;'>";
    $output .= "<div style='display: grid; grid-template-columns: repeat(7, 50px); grid-gap: 5px;'>";

    foreach ($grid as $i => $row) {
        foreach ($row as $j => $cell) {
            $rowClass = $rowCounts[$i] >= 2 ? 'background-color: lightyellow;' : '';
            $colClass = $colCounts[$j] >= 2 ? 'background-color: lightgreen;' : '';
            $highlightClass = in_array($cell, $highlightNumbers) ? 'color: red;' : '';
            $underlineClass = in_array($cell, $highlight_estall) ? 'text-decoration: underline;' : '';
            $doubleUnderlineClass = ($cell == $highlight_est49) ? 'text-decoration: underline double;' : '';
            $boldClass = ($cell == $highlight_max) ? 'font-weight: bold;' : '';
            $fadedClass = ($rowClass || $colClass) ? 'opacity: 0.5;' : ''; // Apply faded class if in highlighted row or column
            
            $style = "width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; font-size: 20px; background-color: #e0e0e0; $rowClass $colClass $highlightClass $underlineClass $doubleUnderlineClass $boldClass $fadedClass";
            $output .= "<div style='$style'>$cell</div>";
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