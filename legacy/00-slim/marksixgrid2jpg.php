<?php
function displaySpiralGrid($highlightNumbers, $highlight_estall, $highlight_est49, $highlight_max, $remark, $opacity = 50, $gridFontFile, $gridFontSize, $filename) {
    $size = 7; // Grid size
    $grid = array_fill(0, $size, array_fill(0, $size, 0)); // Create a 7x7 grid

    $x = $y = (int)($size / 2); // Start from the center
    $directions = [[0, -1], [-1, 0], [0, 1], [1, 0]]; // Left, Up, Right, Down (anti-clockwise)
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

    // Generate the image
    $imageWidth = 400;
    $imageHeight = 400;
    $cellSize = 50;
    $image = imagecreatetruecolor($imageWidth, $imageHeight);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $red = imagecolorallocate($image, 255, 0, 0);
    $lightYellow = imagecolorallocate($image, 255, 255, 200);
    $lightGreen = imagecolorallocate($image, 200, 255, 200);
    
    imagefill($image, 0, 0, $white);

    // Count occurrences of highlightNumbers in each row and column
    $rowCounts = array_fill(0, $size, 0);
    $colCounts = array_fill(0, $size, 0);

    foreach ($highlightNumbers as $number) {
        for ($i = 0; $i < $size; $i++) {
            if (in_array($number, $grid[$i])) {
                $rowCounts[$i]++;
            }
            if (in_array($number, array_column($grid, $i))) {
                $colCounts[$i]++;
            }
        }
    }

    // Draw the grid
    for ($i = 0; $i < $size; $i++) {
        for ($j = 0; $j < $size; $j++) {
            $cell = $grid[$i][$j];
            $xPos = ($j * $cellSize) + 25;
            $yPos = ($i * $cellSize) + 25;

            // Set cell background color based on highlight conditions
            $color = $white; // Default background color
            if ($rowCounts[$i] >= 2) {
                $color = $lightYellow; // Highlight entire row if it contains 2 or more highlightNumbers
            }
            if ($colCounts[$j] >= 2) {
                $color = $lightGreen; // Highlight entire column if it contains 2 or more highlightNumbers
            }

            // Draw the cell background
            imagefilledrectangle($image, $xPos, $yPos, $xPos + $cellSize, $yPos + $cellSize, $color);

            // Set text color based on highlight conditions
            $textColor = $black;
            if (in_array($cell, $highlightNumbers)) {
                $textColor = $red; // Highlight number text in red
            }

            // Draw the cell text with the specified font
            imagettftext($image, $gridFontSize, 0, $xPos + 15, $yPos + 35, $textColor, $gridFontFile, $cell); // Adjust font size and path

            // Draw borders
            imagerectangle($image, $xPos, $yPos, $xPos + $cellSize, $yPos + $cellSize, $black);

            // Add underline for highlighted numbers
            if (in_array($cell, $highlight_estall)) {
                imageline($image, $xPos + 10, $yPos + 35, $xPos + 40, $yPos + 35, $black);
            }
            // Add double underline for the specific number
            if ($cell == $highlight_est49) {
                imageline($image, $xPos + 10, $yPos + 40, $xPos + 40, $yPos + 40, $black);
                imageline($image, $xPos + 10, $yPos + 42, $xPos + 40, $yPos + 42, $black);
            }
        }
    }

    // Add date at the top
    $dateString = "Gann Squares [" . date("Y-m-d")."]";
    imagettftext($image, 12, 0, 10, 20, $black, 'font/TTT-Regular.otf', $dateString); // Adjust font path

    // Add remark at the bottom
    $remarkString = "" . $remark;
    imagettftext($image, 12, 0, 10, $imageHeight - 10, $black, 'font/TTT-Regular.otf', $remarkString); // Adjust font path

    // Create a watermark image
    $watermarkText = "Fi";
    $fontSize = 268; // Adjust font size as needed
    $fontFile = 'font/Futura LT Bold Oblique.ttf'; // Path to the watermark font
    $bbox = imagettfbbox($fontSize, 0, $fontFile, $watermarkText);
    $textWidth = $bbox[2] - $bbox[0];
    $textHeight = $bbox[1] - $bbox[7];
    
    // Create a new true color image for the watermark
    $watermarkImage = imagecreatetruecolor($imageWidth, $imageHeight);
    $transparent = imagecolorallocatealpha($watermarkImage, 255, 255, 255, 127); // Fully transparent
    imagefill($watermarkImage, 0, 0, $transparent);
    imagealphablending($watermarkImage, true);
    imagesavealpha($watermarkImage, true);

    // Draw the watermark with opacity
    $watermarkColor = imagecolorallocatealpha($watermarkImage, 0, 0, 0, 127 - $opacity); // Adjust color with opacity
    imagettftext($watermarkImage, $fontSize, 0, ($imageWidth - $textWidth) / 2 -30, ($imageHeight + $textHeight) / 2, $watermarkColor, $fontFile, $watermarkText);

    // Merge the watermark with the original image
    imagecopy($image, $watermarkImage, 0, 0, 0, 0, $imageWidth, $imageHeight);
    imagedestroy($watermarkImage); // Free up memory for watermark image

    // Save the image to a file
    
    imagejpeg($image, $filename);
    
    // Output the image to the browser
    header('Content-Type: image/jpeg');
    imagejpeg($image);
    imagedestroy($image); // Free up memory

    return; // Return to exit the function
}

// Example usage
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
$someremark = "Powered by buycarl.com ";
$gridFontFile = 'font/TTT-Regular.otf'; // Path to the grid font
$gridFontSize = 16;
$filename = "image/marksix/" . date('Ymd') . rand(100000, 999999) . '.jpg';

// Call the function to display the grid as an image
displaySpiralGrid($highlightNumbers, $highlight_estall, $highlight_est49, $highlight_max, $someremark, 10, $gridFontFile, $gridFontSize, $filename); // Adjust opacity as needed
?>