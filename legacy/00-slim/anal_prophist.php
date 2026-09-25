<?php
include_once ("lib/constants.php");
$mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Race Prop Result</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        #results {
            margin-top: 20px;
        }
        table {
            /*width: 100%;*/
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
            vertical-align:top;
        }
        th {
            background-color: #f2f2f2;
        }
        .highlight {
            background-color: yellow; /* Highlight color for same numbers */
        }
        .finalPosition1 {
            color: red; /* Color for finalPosition = 1 */
        }
        .finalPosition2 {
            color: blue; /* Color for finalPosition = 2 */
        }
        .finalPosition3 {
            color: green; /* Color for finalPosition = 3 */
        }
        .finalPosition4 {
            color: brown; /* Color for finalPosition = 4 */
        }
        .nopla {
            opacity: 0.4; 
        }
        .underline {
            text-decoration: underline;
        }
        
    </style>
</head>
<body>

<!--<h2>Live Prop Analysis</h2>-->
<!--<label for="venueSelect">Venue:</label>-->
<!--<select id="venueSelect">-->
<!--    <option value="All">All</option>-->
<!--    <option value="ST">ST</option>-->
<!--    <option value="HV">HV</option>-->
<!--    <option value="Oth">Oth</option>-->
<!--</select>-->
<!--<br>-->
        <select id="venueSelect" name="venueSelect">
            <?php
            echo "<option value=''></option>";
            $query = "SELECT 
                          CASE 
                              WHEN venue NOT IN ('ST', 'HV') THEN 'Sx' 
                              ELSE venue 
                          END AS venue 
                      FROM racepropresult 
                      GROUP BY venue";
            $result = $mysqli->query($query); // Replace $db with your database connection variable

            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . htmlspecialchars($row['venue']) . "'>" . htmlspecialchars($row['venue']) . "</option>";
            }
            ?>
        </select>
<!--<br>-->
<br>
        <select id="go_ch" name="go_ch">
            <?php
            $query = "SELECT go_ch FROM racepropresult GROUP BY go_ch";
            $result = $mysqli->query($query); // Replace $db with your database connection variable

            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . htmlspecialchars($row['go_ch']) . "'>" . htmlspecialchars($row['go_ch']) . "</option>";
            }
            ?>
        </select>
<br>
<!--<label for="compareDigit">FirstDigit:</label>-->
<input type="text" id="compareDigit" placeholder="Initial digit...">
<table><tr>
    <td><label><input type="checkbox" class="multiplier" value="6x">6x</label>
    <td><label><input type="checkbox" class="multiplier" value="5x">5x</label>
    <td><label><input type="checkbox" class="multiplier" value="4x">4x</label>
    <td><label><input type="checkbox" class="multiplier" value="3x">3x</label>
    <td><label><input type="checkbox" class="multiplier" value="2x">2x</label>
    <td><label><input type="checkbox" class="multiplier" value="1x">1x</label>
    <td><label><input type="checkbox" class="multiplier" value="xx">xx</label>
</tr>
<tr>
    <td><label><input type="checkbox" class="multiplier2" value="35x">35</label>
    <td><label><input type="checkbox" class="multiplier2" value="34x">34</label>
    <td><label><input type="checkbox" class="multiplier2" value="33x">33</label>
    <td><label><input type="checkbox" class="multiplier2" value="32x">32</label>
    <td><label><input type="checkbox" class="multiplier2" value="31x">31</label>
    <td><label>.</label>
</table>
<!--<br>-->
<input type="text" id="haveDigit" placeholder="Contain a digit...">
<div id="results">
    <!--<h2>Proppre Results</h2>-->
    <table>
        <thead>
            <!--<tr>-->
            <!--    <th>..</th>-->
            <!--</tr>-->
        </thead>
        <tbody id="resultsBody">
            <!-- Results will be populated here -->
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    // Set focus on the compareDigit input box when the page loads
    $('#compareDigit').focus();
    
    function fetchResults() {
        var digit = $('#compareDigit').val();
        var haveDigit = $('#haveDigit').val();
        var venue = $('#venueSelect').val(); // Get selected venue
        var go_ch = $('#go_ch').val();
        var selectedMultipliers = [];
        var selectedMultipliers2 = [];

        // Gather selected multipliers
        $('.multiplier:checked').each(function() {
            selectedMultipliers.push($(this).val());
        });
        $('.multiplier2:checked').each(function() {
            selectedMultipliers2.push($(this).val());
        });
        
        if (digit || haveDigit || selectedMultipliers || selectedMultipliers2) {
            $.ajax({
                url: 'fetch_racepropresult.php',
                type: 'GET',
                data: { prop: digit, venue: venue, go_ch:go_ch, haveDigit: haveDigit, multipliers: selectedMultipliers, multipliers2: selectedMultipliers2 },
                dataType: 'json',
                success: function(data) {
                    $('#resultsBody').empty(); // Clear previous results

                    // Loop through the fetched data
                    $.each(data, function(index, item) {
                        var row = $('<td><table><tr></tr></table></td>'); // Create a new table row
                        var counts = {}; // Object to count occurrences of each value

                        // First, count the occurrences of each proppre value
                        $.each(item.proppre, function(i, value) {
                            counts[value] = (counts[value] || 0) + 1; // Increment count
                        });

                        // Then, create <td> for each value with highlighting for duplicates and color coding
                        $.each(item.proppre, function(i, value) {
                            var cell = $('<tr><td></td></tr>').text(value); // Create a new <td>

                            // If the count is greater than 1, add highlight class
                            if (counts[value] > 1) {
                                cell.addClass('highlight');
                            }
                            
                            if (value == item.max2x || value == item.max3x || value == item.max4x) {
                                cell.addClass('underline');
                            }

                            // Apply color based on finalPosition
                            var finalPosition = item.finalPosition[i]; // Get the corresponding finalPosition
                            if (finalPosition == "1") {
                                cell.addClass('finalPosition1');
                            } else if (finalPosition == "2") {
                                cell.addClass('finalPosition2');
                            } else if (finalPosition == "3") {
                                cell.addClass('finalPosition3');
                            } else if (finalPosition == "4") {
                                cell.addClass('finalPosition4');
                            } else {
                                cell.addClass('nopla');
                            }

                            row.append(cell); // Append <td> to the row
                        });

                        $('#resultsBody').append(row); // Append the completed row to the table body
                    });
                    $('#compareDigit').focus();
                },
                error: function() {
                    $('#resultsBody').empty().append('<tr><td colspan="1">Error fetching data.</td></tr>');
                }
            });
        } else {
            $('#resultsBody').empty(); // Clear results if input is empty
        }
    }
    // Event listeners for input and checkbox changes
    $('#compareDigit').on('input', fetchResults);
    $('#haveDigit').on('input', fetchResults);
    
    $('.multiplier').on('change', fetchResults);
    $('.multiplier2').on('change', fetchResults);
    $('#venueSelect').on('change', fetchResults);
    $('#go_ch').on('change', fetchResults);
    
});
</script>



</body>
</html>