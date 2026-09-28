<?php

function PlaColors($finalPosition) {
    // Define an associative array for mapping final positions to colors
    $colors = [
        '1' => 'red',
        '2' => 'blue',
        '3' => 'green',
        '4' => 'brown',
    ];

    // Check if the final position exists in the array and return the corresponding color style
    if (isset($colors[$finalPosition])) {
        return " color: {$colors[$finalPosition]}; "; // Return CSS text color style
    }

    // Default case if the final position does not match any key
    return "color: black;"; // Default color if not found
}

function textcolorAlert($inpla, $notInpla) {
    // Get counts from horseinfo array
    $inplaCount = isset($inpla) ? $inpla : 0;
    $notInplaCount = isset($notInpla) ? $notInpla : 0;

    // Calculate total count
    $totalCount = $inplaCount + $notInplaCount;

    // Initialize the result variable
    $alert = " style=\"opacity: 0.5;\" "; // Default to low opacity

    // Check if the total count is greater than 0 to avoid division by zero
    if ($totalCount > 0) {
        $percentage = $inplaCount / ($notInplaCount ? $notInplaCount : 1); // Calculate the percentage

        // Determine the result based on the percentage
        if ($percentage >= 1) {
            $alert = " style='background-color: #99FF99; color:red;' "; // More than 100%
        } elseif ($percentage > 0.75) {
            $alert = " style='background-color: #99FF99;' "; // More than 75%
        } elseif ($percentage > 0.40) {
            $alert = " style='background-color: #CDFFCD;' "; // More than 50%
        }
    }

    return $alert; // Return the resulting style
}

function isValidDate($dateString) {
    $dateTime = DateTime::createFromFormat('Y-m-d', $dateString);
    return $dateTime && $dateTime->format('Y-m-d') === $dateString;
}

function colorstr($string){
    // Split the string by the delimiter '/'
    $numbers = explode('/', $string);
    
    // Initialize an array to hold the modified numbers
    $modifiedNumbers = [];
    
    // Loop through each number to check its value
    foreach ($numbers as $number) {
        if ($number < 4) {
            // If the number is less than 4, wrap it in a red span
            $modifiedNumbers[] = "<span style='color: red;'>$number</span>";
        } else {
            // Otherwise, keep the number as is
            $modifiedNumbers[] = $number;
        }
    }
    
    // Join the modified numbers back into a string with '/' as the separator
    $output = implode('/', $modifiedNumbers);
    return $output;
}

// Function to merge the Running_Position and Running_Position_jockey arrays
function mergeRunningPositions($running_positions, $running_positions_jockey) {
    $merged_positions = [];

    // Iterate through the Running_Position array
    foreach ($running_positions as $race_key => $positions) {
        foreach ($positions as $time => $position) {
            // Get the corresponding jockeys for this time
            $jockeys = isset($running_positions_jockey[$race_key][$time]) ?
                explode(';', rtrim($running_positions_jockey[$race_key][$time], ';')) : [];

            // Store the position with the associated jockeys
            $merged_positions[$race_key][$time] = [
                'position' => $position,
                'jockeys' => $jockeys
            ];
        }
    }

    return $merged_positions;
}

function running_pos_jockey($merged_running_position, $jockey, $distance) {
    // Check if the specified distance exists in the positions
    if (isset($merged_running_position[$distance])) {
        $data = $merged_running_position[$distance];
        $position = $data['position'];
        $jockeys = $data['jockeys'];

        // Check if $jockeys is indeed an array
        if (is_array($jockeys)) {
            // Create a new string to hold the colored position
            $colored_position = '';

            // Iterate over each character in the position string
            for ($i = 0; $i < strlen($position); $i++) {
                // Check if the current index corresponds to a jockey
                if (isset($jockeys[$i]) && $jockeys[$i] === $jockey) {
                    // If the jockey matches, wrap the character in a span
                    $colored_position .= "<span style='color: red; display:inline-block;'>" . $position[$i] . "</span>";
                    // $colored_position .= ($i > 0 ? ' ' : '') . "<span style='color: red;'>" . $position[$i] . "</span>";
                } else {
                    // Otherwise, just append the character
                    $colored_position .= $position[$i];
                }
            // if($distance =='1600'){
            //     echo $jockey.$position.$i."<br>";
            // }
            }

            // Return the formatted position string
            return $colored_position;//"Distance: $distance, Position: $colored_position";
        } else {
            return "";//"Jockeys data is not an array.";
        }
    }

    return "";//"Distance $distance not found.";
}

function determinePattern($pattern) {
    // Split the input pattern into an array of digits
    $digits = explode(' ', trim($pattern));
    
    // Validate that we have at least 3 digits
    // if (count($digits) < 3 || !allDigits($digits)) {
    //     return "-";//"Invalid input. Please provide at least three digits separated by spaces.";
    // }
    
    // Normalize the input to ensure we have 4 digits
    $firstFourDigits = array_map('intval', $digits); // Convert to integers

    // If there are only 3 digits, append a zero
    if (count($firstFourDigits) === 3) {
        $firstFourDigits[3] = $firstFourDigits[2];  //0; // Treat the last digit as 0
    }

    // Convert the first two and last two digits
    // $firstTwo = $firstFourDigits[0] * 10 + $firstFourDigits[1]; // First two digits
    // $lastTwo = $firstFourDigits[2] * 10 + $firstFourDigits[3]; // Last two digits
    // $lastone = $firstFourDigits[3]; // Last one digits
    // $pos1 = $firstFourDigits[0];
    // $pos2 = $firstFourDigits[1];
    // $pos3 = $firstFourDigits[2];
    // $pos4 = $firstFourDigits[3];
    // Check if $firstFourDigits contains at least 4 elements
    if (isset($firstFourDigits) && count($firstFourDigits) >= 4) {
        $firstTwo = $firstFourDigits[0] * 10 + $firstFourDigits[1]; // First two digits
        $lastTwo = $firstFourDigits[2] * 10 + $firstFourDigits[3]; // Last two digits
        $lastone = $firstFourDigits[3]; // Last one digit
        $pos1 = $firstFourDigits[0];
        $pos2 = $firstFourDigits[1];
        $pos3 = $firstFourDigits[2];
        $pos4 = $firstFourDigits[3];
    } else {
        // Handle the case where the array does not have enough elements
        // You can set default values or throw an error
        $firstTwo = 0; // Default value
        $lastTwo = 0;  // Default value
        $lastone = 0;  // Default value
        $pos1 = 0;     // Default value
        $pos2 = 0;     // Default value
        $pos3 = 0;     // Default value
        $pos4 = 0;     // Default value
    
        // Optionally, log an error or throw an exception
        // error_log("Warning: Not enough elements in firstFourDigits array.");
    }

    // Check if all digits are similar (difference within 1)
    if (isSimilar($firstFourDigits) && $pos1 > 4 ) {
        if($lastone < 4){
            return 'A';
        }else{
            return 'a'; // All digits are similar (within 1)
        }
    }

    // Define the conditions for each pattern
    // if ($firstTwo < 55) {
    //     // First two digits are less than 5
    //     if ($lastTwo > $firstTwo && $lastone < 4) {
    //         return 'F'; // Pattern 1 1 2 3
    //     } elseif ($lastTwo > $firstTwo && $lastone > 4) {
    //         return 'f'; // Pattern 1 1 2 6
    //     }
    // } elseif ($firstTwo >= 55) {
    //     // First two digits are greater than or equal to 5
    //     if ($lastTwo < $firstTwo && $lastone < 4) {
    //         return 'R'; // Pattern 4 5 2 3
    //     } elseif ($lastTwo > $firstTwo && $lastone > 4) {
    //         return 'r'; // Pattern 4 5 6 7
    //     }
    // } elseif ($lastone <3) {
    //     return '?';
    // }
    if ($pos1 < 4 && $pos2 < 4 && $pos3 < 4 && $pos4 < 4 ) {
        return 'F';
    }
    if ($pos1 < 4 && $pos2 < 4 && $pos4 > 3 ) {
        return 'f';
    }
    if ($pos1 >= 4 && $pos2 >= 4 && $pos4 < 4 ) {
        return 'R';
    }
    if ($pos1 >= 4 && $pos4 > 4 ) {
        return 'r';
    }

    return ".";//"No matching pattern found.".$firstTwo."-".$lastTwo;
}

// Helper function to check if all elements are digits
function allDigits($array) {
    foreach ($array as $item) {
        if (!ctype_digit($item)) {
            return false; // Return false if any item is not a digit
        }
    }
    return true; // All items are digits
}

// Helper function to check if all digits are similar (difference within 1)
function isSimilar($array) {
    $first = intval($array[0]);
    foreach ($array as $digit) {
        if (abs($first - intval($digit)) > 1) {
            return false; // If any digit differs by more than 1, return false
        }
    }
    return true; // All digits are similar
}

//---------------------------------------------------------------------------------------------------------------------------------------
function mapColorsHtml($input) {
    // Define the mapping of digits to colors using HTML styles
    $colorMapping = [
        '1' => 'red',
        '2' => 'blue',
        '3' => 'green',
        '4' => 'brown',
    ];

    // Initialize an array to hold the resulting HTML
    $resultHtml = [];

    // Split the input into separate components using commas
    $components = explode(',', $input); // Split by comma

    // Loop through each component in the input
    foreach ($components as $component) {
        $trimmedComponent = trim($component); // Remove any leading/trailing whitespace
        
        // Check if the component is one of the keys in the color mapping
        if (array_key_exists($trimmedComponent, $colorMapping)) {
            $resultHtml[] = "<span style='color: {$colorMapping[$trimmedComponent]};'>{$trimmedComponent}</span>"; // Add colored digit
        } else {
            $resultHtml[] = $trimmedComponent; // Leave other components unchanged
        }
    }

    // Return the result as a string
    return implode(', ', $resultHtml); // Join the components with commas
}

// function mapColorsHtml($input) {
//     // Define the mapping of digits to colors using HTML styles
//     $colorMapping = [
//         '1' => 'red',
//         '2' => 'blue',
//         '3' => 'green',
//         '4' => 'brown',
//     ];

//     // Initialize an array to hold the resulting HTML
//     $resultHtml = [];

//     // Loop through each character in the input string
//     for ($i = 0; $i < strlen($input); $i++) {
//         $digit = $input[$i]; // Get the current digit

//         // Check if the digit exists in the mapping
//         if (array_key_exists($digit, $colorMapping)) {
//             $resultHtml[] = "<span style='color: {$colorMapping[$digit]};'>{$digit}</span>"; // Add colored digit
//         } else {
//             $resultHtml[] = $digit; // If not found, add the digit without styling
//         }
//     }

//     // Return the result as a string
//     return implode('', $resultHtml); // Join the colors without spaces
// }

function mapColors($jockey, $jockeys) {
    // Convert the comma-separated string to an array
    $jockeyArray = explode(',', $jockeys);
    
    // Initialize an array to hold the formatted jockey names
    $formattedJockeys = [];

    // Iterate through each jockey
    foreach ($jockeyArray as $jockeyName) {
        // Trim any whitespace from the jockey name
        $jockeyName = trim($jockeyName);
        
        // Check if the current jockey matches the specified jockey
        if ($jockeyName === $jockey) {
            // If it matches, wrap it in a span with red color
            $formattedJockeys[] = "<span style='color: blue;'>$jockeyName</span>";
        } else {
            // Otherwise, just add the jockey name as is
            $formattedJockeys[] = $jockeyName;
        }
    }

    // Join the formatted jockeys back into a comma-separated string
    return implode(',', $formattedJockeys);
}

function countValuesInPattern($last6run) {
    $totalCount = 0;
    $countLessThanEqual4 = 0;

    // Split the string by '/'
    $values = explode('/', $last6run);

    // Iterate through each value
    foreach ($values as $value) {
        // Convert to integer
        $num = (int)$value;

        // Check if it is a valid number and count it
        if ($num >= 0) { // Assuming you want to count non-negative numbers
            $totalCount++;

            // Check if the value is less than or equal to 4
            if ($num <= 4) {
                $countLessThanEqual4++;
            }
        }
    }
    // Calculate percentage if totalCount is greater than 0
    $percentage = ($totalCount > 0) ? ($countLessThanEqual4 / $totalCount) * 100 : 0;
    return [
        'totalCount' => $totalCount,
        'countLessThanEqual4' => $countLessThanEqual4,
        'percentage' => $percentage,
    ];
}

function convertToCircledNumber($number): string {
    // Check if the input is numeric
    if (!is_numeric($number)) {
        throw new InvalidArgumentException("Input must be a numeric value.");
    }
    
    // Convert the input to an integer
    $number = (int)$number;
    
    // If the number is greater than 20, return it as a string without conversion
    if ($number > 20) {
        return (string)$number; // Return the original value as a string
    }

    // Check if the number is within the valid range for circled numbers (1-20)
    if ($number < 1 || $number > 20) {
        throw new InvalidArgumentException("Number must be between 1 and 20 inclusive.");
    }
    
    // Unicode for circled numbers starts at 9312 for 0
    // Circled numbers for 1-20 are from 9313 to 9324
    return mb_chr(9312 + $number - 1); // 9312 is the Unicode for '0', so 9313 corresponds to '1'
}

// function convertToCircledNumber($number): string {
//     // Check if the input is numeric
//     if (!is_numeric($number)) {
//     throw new InvalidArgumentException("Input must be a numeric value.");
//     }
    
//     // Convert the input to an integer
//     $number = (int)$number;
    
//     // Check if the number is within the valid range for circled numbers (1-20)
//     if ($number < 1 || $number > 20) {
//         throw new InvalidArgumentException("Number must be between 1 and 20 inclusive.");
//     }
    
//     // Unicode for circled numbers starts at 9312 for 0
//     // Circled numbers for 1-20 are from 9313 to 9324
//     return mb_chr(9312 + $number - 1); // 9312 is the Unicode for '0', so 9313 corresponds to '1'
// }

// function convertToCircledNumber($number): string {
//     // Check if the input is numeric
//     if (!is_numeric($number)) {
//         throw new InvalidArgumentException("Input must be a numeric value.");
//     }

//     // Convert the input to an integer
//     $number = (int)$number;

//     // Check if the number is within the valid range for circled numbers (1-20)
//     if ($number < 1 || $number > 20) {
//         throw new InvalidArgumentException("Number must be between 1 and 20 inclusive.");
//     }

//     // Unicode for circled numbers starts at 9312 for 0
//     // Circled numbers for 1-20 are from 9313 to 9324
//     return mb_chr(9312 + $number); // 9312 is the Unicode for '0', so 9313 corresponds to '1'
// }


// function convertToCircledNumber($number): string {
//     // Check if the input is numeric
//     if (!is_numeric($number)) {
//         throw new InvalidArgumentException("Input must be a numeric value.");
//     }

//     // Convert the input to an integer
//     $number = (int)$number;

//     // Check if the number is within the valid range for circled numbers (1-20)
//     if ($number < 1 || $number > 20) {
//         throw new InvalidArgumentException("Number must be between 1 and 20 inclusive.");
//     }

//     // Unicode for circled numbers starts at 9312 for 0
//     // Circled numbers for 1-20 are from 9313 to 9324
//     return mb_chr(9312 + $number - 1); // 9312 is the Unicode for '0', so 9313 corresponds to '1'
// }

// try {
//     echo convertToCircledNumber($input); // This should now work correctly
// } catch (InvalidArgumentException $e) {
//     echo "Error: " . $e->getMessage();
// }
/**
 * Function to set counts based on preprop value.
 *
 * @param float $preprop The value to check.
 * @return array An associative array with count values.
 */

function getFirstDigit($number) {
    // Convert the number to a string
    $numberString = (string) $number;

    // Return the first character as an integer
    return (int) $numberString[0]; // or use intval() to convert to an integer
}

function setXCountsBasedOnPrecnt($racingdata, $date, $venueCode) {
    // Initialize an empty array to store count results for each race
    $countsResults = [];

    // Check if the required data exists in $racingdata for the specified date and venueCode
    if (isset($racingdata[$date][$venueCode])) {
        foreach ($racingdata[$date][$venueCode] as $raceno => $horses) {
            if(is_numeric($raceno)){
                // Initialize counts for the current raceno
                $counts = [
                    '1xcnt' => 0,
                    '2xcnt' => 0,
                    '3xcnt' => 0,
                    '4xcnt' => 0,
                    '5xcnt' => 0,
                ];
                $uniquevalue = [
                    '5xnique' => [],
                    '4xnique' => [],
                    '3xnique' => [],
                    '2xnique' => [],
                    '1xnique' => [],
                ];
                $temp_preprop = 0;
    
                // foreach ($horses as $horseno => $data) {
                //     if(is_numeric($horseno)){
                //         $preprop = $data['Pre']['proppre'];
                //         $precnt = $data['Pre']['cnt'];
                //         if($data['Pre']['propprepos'] == 1){
                //             $racingdata[$date][$venueCode][$raceno]['TopPrePos'] = getFirstDigit($preprop);
                //         }
                //         if (isset($precnt)) {
                //             if($precnt>1){
                //                 // Determine counts based on preprop value
                //                 if ($preprop >= 30 && $preprop < 40) {
                //                     $counts['3xcnt']++;
                //                     $uniquevalue['3xnique'][] = $preprop;
                //                 } elseif ($preprop >= 20 && $preprop < 30) {
                //                     $counts['2xcnt']++;
                //                     $uniquevalue['2xnique'][] = $preprop;
                //                 } elseif ($preprop >= 10 && $preprop < 20) {
                //                     $counts['1xcnt']++;
                //                     $uniquevalue['1xnique'][] = $preprop;
                //                 } elseif ($preprop >= 40 && $preprop < 50) {
                //                     $counts['4xcnt']++;
                //                     $uniquevalue['4xnique'][] = $preprop;
                //                 } elseif ($preprop >= 50 ) {
                //                     $counts['5xcnt']++;
                //                     $uniquevalue['5xnique'][] = $preprop;
                //                 }
                //             }
                //         }
                //     }
                // }
    
                // // Update the racing data with the counts for this raceno
                // $racingdata[$date][$venueCode][$raceno]['5xcnt'] = $counts['5xcnt'];
                // $racingdata[$date][$venueCode][$raceno]['4xcnt'] = $counts['4xcnt'];
                // $racingdata[$date][$venueCode][$raceno]['3xcnt'] = $counts['3xcnt'];
                // $racingdata[$date][$venueCode][$raceno]['2xcnt'] = $counts['2xcnt'];
                // $racingdata[$date][$venueCode][$raceno]['1xcnt'] = $counts['1xcnt'];
                
                // $racingdata[$date][$venueCode][$raceno]['1xnique'] = count(array_unique($uniquevalue['1xnique']));
                // $racingdata[$date][$venueCode][$raceno]['2xnique'] = count(array_unique($uniquevalue['2xnique']));
                // $racingdata[$date][$venueCode][$raceno]['3xnique'] = count(array_unique($uniquevalue['3xnique']));
                // $racingdata[$date][$venueCode][$raceno]['4xnique'] = count(array_unique($uniquevalue['4xnique']));
                // $racingdata[$date][$venueCode][$raceno]['5xnique'] = count(array_unique($uniquevalue['5xnique']));
                // echo $date.$venueCode.$raceno."--".$counts['2xcnt']."--".$counts['3xcnt']."--".$counts['4xcnt']."<br>";    
                foreach ($horses as $horseno => $data) {
                    if (is_numeric($horseno)) {
                        $preprop = $data['Pre']['proppre'];
                        $precnt = $data['Pre']['cnt'];
                
                        // Set TopPrePos if propprepos is 1
                        if ($data['Pre']['propprepos'] == 1) {
                            $racingdata[$date][$venueCode][$raceno]['TopPrePos'] = getFirstDigit($preprop);
                            $racingdata[$date][$venueCode][$raceno]['TopPrePos2'] = ($preprop);
                        }
                
                        // Only process if precnt is set and greater than 1
                        if (isset($precnt) && $precnt > 1) {
                            // Determine counts and track unique values based on preprop value
                            if ($preprop >= 30 && $preprop < 40) {
                                $counts['3xcnt']++;
                                // if (!in_array($preprop, $uniquevalue['3xnique'])) {
                                    $uniquevalue['3xunique'][] = $preprop; // Add unique value
                                // }
                            } elseif ($preprop >= 20 && $preprop < 30) {
                                $counts['2xcnt']++;
                                // if (!in_array($preprop, $uniquevalue['2xnique'])) {
                                    $uniquevalue['2xunique'][] = $preprop; // Add unique value
                                // }
                            } elseif ($preprop >= 10 && $preprop < 20) {
                                $counts['1xcnt']++;
                                // if (!in_array($preprop, $uniquevalue['1xnique'])) {
                                    $uniquevalue['1xunique'][] = $preprop; // Add unique value
                                // }
                            } elseif ($preprop >= 40 && $preprop < 50) {
                                $counts['4xcnt']++;
                                // if (!in_array($preprop, $uniquevalue['4xnique'])) {
                                    $uniquevalue['4xunique'][] = $preprop; // Add unique value
                                // }
                            } elseif ($preprop >= 50) {
                                $counts['5xcnt']++;
                                // if (!in_array($preprop, $uniquevalue['5xnique'])) {
                                    $uniquevalue['5xunique'][] = $preprop; // Add unique value
                                // }
                            }
                        }
                        // if(getFirstDigit($temp_preprop) == getFirstDigit($preprop) && $temp_preprop > $preprop){
                        //     $racingdata[$date][$venueCode][$raceno][$horseno]['Pre']['smallerthanbefore'] = $preprop;
                        // }
                        $temp_preprop = $preprop;
                    }
                }
                
                // Update the racing data with the counts for this raceno
                $racingdata[$date][$venueCode][$raceno]['5xcnt'] = $counts['5xcnt'];
                $racingdata[$date][$venueCode][$raceno]['4xcnt'] = $counts['4xcnt'];
                $racingdata[$date][$venueCode][$raceno]['3xcnt'] = $counts['3xcnt'];
                $racingdata[$date][$venueCode][$raceno]['2xcnt'] = $counts['2xcnt'];
                $racingdata[$date][$venueCode][$raceno]['1xcnt'] = $counts['1xcnt'];
                
                // Store the count of unique values
                $racingdata[$date][$venueCode][$raceno]['1xunique'] = count(array_unique($uniquevalue['1xunique']));
                $racingdata[$date][$venueCode][$raceno]['2xunique'] = count(array_unique($uniquevalue['2xunique']));
                $racingdata[$date][$venueCode][$raceno]['3xunique'] = count(array_unique($uniquevalue['3xunique']));
                $racingdata[$date][$venueCode][$raceno]['4xunique'] = count(array_unique($uniquevalue['4xunique']));
                $racingdata[$date][$venueCode][$raceno]['5xunique'] = count(array_unique($uniquevalue['5xunique']));
                
                // Store the count of unique values
                $racingdata[$date][$venueCode][$raceno]['1xuniquearray'] = (array_unique($uniquevalue['1xunique']));
                $racingdata[$date][$venueCode][$raceno]['2xuniquearray'] = (array_unique($uniquevalue['2xunique']));
                $racingdata[$date][$venueCode][$raceno]['3xuniquearray'] = (array_unique($uniquevalue['3xunique']));
                $racingdata[$date][$venueCode][$raceno]['4xuniquearray'] = (array_unique($uniquevalue['4xunique']));
                $racingdata[$date][$venueCode][$raceno]['5xuniquearray'] = (array_unique($uniquevalue['5xunique']));
            }
        }
    }
    return $racingdata; // Return the updated racing data
}

function setCountsBasedOnPreprop($preprop) {
    $counts = [
        '1xcnt' => 0,
        '2xcnt' => 0,
        '3xcnt' => 0,
        '4xcnt' => 0,
        '5xcnt' => 0,
        // Add more if needed
    ];

    // Determine counts based on preprop value
    if ($preprop >= 30 && $preprop < 40) {
        $counts['3xcnt'] = 1; // or set to the actual count you want
    } elseif ($preprop >= 20 && $preprop < 30) {
        $counts['2xcnt'] = 1; // or set to the actual count you want
    } elseif ($preprop >= 10 && $preprop < 20) {
        $counts['1xcnt'] = 1; // or set to the actual count you want
    } elseif ($preprop >= 0 && $preprop < 10) {
        // Optionally handle counts for lower ranges
    }

    return $counts;
}

// Function to set counts based on proppre value
// function setCountsBasedOnPreprop($preprop, $history, $venue, $raceno) {
//     $counts = [
//         '1xcnt' => 0,
//         '2xcnt' => 0,
//         '3xcnt' => 0,
//         '4xcnt' => 0,
//         '5xcnt' => 0,
//     ];

//     // Count occurrences based on preprop value
//     if ($preprop >= 30 && $preprop < 40) {
//         $counts['3xcnt']++;
//     } elseif ($preprop >= 20 && $preprop < 30) {
//         $counts['2xcnt']++;
//     } elseif ($preprop >= 10 && $preprop < 20) {
//         $counts['1xcnt']++;
//     } elseif ($preprop >= 0 && $preprop < 10) {
//         // Optionally handle counts for lower ranges
//     }

//     // If you want to keep track of cumulative counts
//     if (isset($history[$venue][$raceno])) {
//         foreach ($history[$venue][$raceno] as $horse) {
//             if ($horse['proppre'] >= 30 && $horse['proppre'] < 40) {
//                 $counts['3xcnt']++;
//             } elseif ($horse['proppre'] >= 20 && $horse['proppre'] < 30) {
//                 $counts['2xcnt']++;
//             } elseif ($horse['proppre'] >= 10 && $horse['proppre'] < 20) {
//                 $counts['1xcnt']++;
//             }
//             // Add checks for other ranges if needed
//         }
//     }

//     return $counts;
// }

/**
 * Function to count occurrences of preprop values.
 *
 * @param array $runners The runners array.
 * @return void
 */
function countPrepropOccurrences(&$runners) {
    $countMap = []; // To store the count of each value

    // Count occurrences of each preprop value
    foreach ($runners as $racedetail) {
        $preprop = $racedetail['preprop'];

        // Increment the count for this preprop value
        if ($preprop !== null) { // Ensure it's not null
            if (!isset($countMap[$preprop])) {
                $countMap[$preprop] = 0;
            }
            $countMap[$preprop]++;
        }
    }

    // Assign counts back to each runner
    foreach ($runners as $index => $racedetail) {
        $preprop = $racedetail['preprop'];
        if (isset($countMap[$preprop])) {
            $runners[$index][$preprop] = $countMap[$preprop];
        } else {
            $runners[$index][$preprop] = 0; // Or leave out if not needed
        }
    }
}

function calculateTotalMarks($positions) {
    $totalMarks = 0;

    foreach ($positions as $position) {
        // Assign marks based on the position
        switch ($position) {
            case 1:
                $totalMarks += 12; // 1st place: 12 marks
                break;
            case 2:
                $totalMarks += 6;  // 2nd place: 6 marks
                break;
            case 3:
                $totalMarks += 4;  // 3rd place: 4 marks
                break;
            default:
                $totalMarks += 0;   // Other positions: 0 marks
                break;
        }
    }

    return $totalMarks; // Return the total marks
}

function calculateTotalMarks_maparray($positionsString) {
    // Convert the string into an array of integers
    $positions = array_map('intval', explode(',', $positionsString));
    $totalMarks = 0;

    foreach ($positions as $position) {
        // Assign marks based on the position
        switch ($position) {
            case 1:
                $totalMarks += 12; // 1st place: 12 marks
                break;
            case 2:
                $totalMarks += 6;  // 2nd place: 6 marks
                break;
            case 3:
                $totalMarks += 4;  // 3rd place: 4 marks
                break;
            default:
                $totalMarks += 0;   // Other positions: 0 marks
                break;
        }
    }

    return $totalMarks; // Return the total marks
}

// Function to convert finish time to total seconds
function convertToSeconds($finishTime) {
    // // Split the finish time into components
    // list($hours, $minutes, $seconds) = explode('.', $finishTime);
    // // Convert to total seconds
    // return ($hours * 3600) + ($minutes * 60) + $seconds;
    $existingTimeSec = $finishTime ? array_reduce(explode('.', $finishTime), function($a, $b) { 
    return $a * 60 + $b; 
    }, 0) : INF;
    return $existingTimeSec;
}

// Example of how to store finish times
function storeFinishTime(&$horsehist, $horseid, $dist, $Finish_Time) {
    // Convert current finish time to seconds
    $currentFinishTimeInSeconds = convertToSeconds($Finish_Time);

    // Check if the finish time for this distance exists
    if (isset($horsehist[$horseid]['Finish_Time'][$dist])) {
        $storedFinishTimeInSeconds = convertToSeconds($horsehist[$horseid]['Finish_Time'][$dist]);
        // Only store the new finish time if it's less than the stored time
        if ($currentFinishTimeInSeconds < $storedFinishTimeInSeconds) {
            $horsehist[$horseid]['Finish_Time'][$dist] = $Finish_Time; // Update to new minimum time
        }
    } else {
        // If it doesn't exist, store the new finish time
        $horsehist[$horseid]['Finish_Time'][$dist] = $Finish_Time;
    }
}

function getMaxValueInRange5x($array) {
    // Filter values based on the criteria (50 <= value < 60)
    $filteredValues = array_filter($array, function($value) {
        return ($value >= 50 && $value < 60);
    });

    // Check if there are more than 1 value
    if (count($filteredValues) > 1) {
        // Get the maximum value from the filtered values
        return max($filteredValues);
    }

    // Return false if the conditions are not met
    return false;
}

?>