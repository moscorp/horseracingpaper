<?php
include_once ("lib/constants.php");
// Database connection
$mysqli = mysqli_connect(DB_HOSTwp, DB_USERwp, DB_PASSwp, DB_NAMEwp);

// Check connection
if (!$mysqli) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handling the update request
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $venue = strtoupper($_POST['venue']);
//     $proptop = intval($_POST['proptop']);
//     $prop = $_POST['prop'];
//     $samecnt = intval($_POST['samecnt']); // Ensure this is an integer
//     $isbigger = isset($_POST['isbigger']) ? 1 : 0;
//     $issmaller = isset($_POST['issmaller']) ? 1 : 0;
//     $pos = $_POST['pos'];
//     $same1x = isset($_POST['same1x']) ? 1 : 0;
//     $same2x = isset($_POST['same2x']) ? 1 : 0;
//     $same3x = isset($_POST['same3x']) ? 1 : 0;
//     $predict = $_POST['predict'];
//     $pick = $_POST['pick']; // Handle empty as needed
//     $nopick = $_POST['nopick']; // Handle empty as needed

//     $sql = "insert into `hrp_propanalysis` value ('', 
//         venue = '$venue',
//         proptop = '$proptop',
//         prop = '$prop', 
//         samecnt = '$samecnt', 
//         isbigger = '$isbigger', 
//         issmaller = '$issmaller', 
//         pos = '$pos', 
//         same1x = '$same1x', 
//         same2x = '$same2x', 
//         same3x = '$same3x', 
//         predict = '$predict', 
//         pick = '$pick', 
//         nopick = '$nopick' )
//         ";
//     $mysqli->query($sql);
// }

// Handling the update request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $venue = strtoupper($_POST['venue']);
    $proptop = intval($_POST['proptop']);
    $prop = $_POST['prop'];
    $samecnt = intval($_POST['samecnt']); // Ensure this is an integer
    $isbigger = isset($_POST['isbigger']) ? 1 : 0;
    $issmaller = isset($_POST['issmaller']) ? 1 : 0;
    $pos = $_POST['pos'];
    $same1x = isset($_POST['same1x']) ? 1 : 0;
    $same2x = isset($_POST['same2x']) ? 1 : 0;
    $same3x = isset($_POST['same3x']) ? 1 : 0;
    $predict = $_POST['predict'];
    $pick = $_POST['pick']; // Handle empty as needed
    $nopick = $_POST['nopick']; // Handle empty as needed

    // Use a prepared statement for the insert
    $sql = "INSERT INTO `hrp_propanalysis` 
        (venue, proptop, prop, samecnt, isbigger, issmaller, pos, same1x, same2x, same3x, predict, pick, nopick) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $mysqli->prepare($sql);
    
    // Check if the statement was prepared successfully
    if ($stmt) {
        // Bind parameters
        $stmt->bind_param('sisiiisiiisss', $venue, $proptop, $prop, $samecnt, $isbigger, $issmaller, $pos, $same1x, $same2x, $same3x, $predict, $pick, $nopick);
        // var_dump($venue, $proptop, $prop, $samecnt, $isbigger, $issmaller, $pos, $same1x, $same2x, $same3x, $predict, $pick, $nopick);
        
        // Execute the statement
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Data inserted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Insert failed: ' . $stmt->error]);
        }
        
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Prepared statement failed: ' . $mysqli->error]);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Prop Analysis</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Edit Prop Analysis</h1>
    <form id="propForm">
        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
        <table>
        <tr><td>Venue: <td><input type="text" name="venue" value="<?php echo $data['venue']; ?>"><br>
        <tr><td>Prop Top: <td><input type="text" name="proptop" value="<?php echo $data['proptop']; ?>"><br>
        <tr><td>Prop: <td><input type="text" name="prop" value="<?php echo htmlspecialchars($data['prop']); ?>" required><br>
        <tr><td>Same Count: <td><input type="number" name="samecnt" value="<?php echo htmlspecialchars($data['samecnt']); ?>" min="0" required>
        <tr><td>Is Bigger: <td><input type="checkbox" name="isbigger" value="1" <?php echo $data['isbigger'] ? 'checked' : ''; ?>>
        <tr><td>Is Smaller: <td><input type="checkbox" name="issmaller" value="1" <?php echo $data['issmaller'] ? 'checked' : ''; ?>>
        <tr><td>Position:
            <td><select name="pos" required>
                <option value="t" <?php echo $data['pos'] === 't' ? 'selected' : ''; ?>>Top</option>
                <option value="m" <?php echo $data['pos'] === 'm' ? 'selected' : ''; ?>>Middle</option>
                <option value="b" <?php echo $data['pos'] === 'b' ? 'selected' : ''; ?>>Bottom</option>
            </select>
        </tr><td>
        <tr><td>Same 1x: <td><input type="checkbox" name="same1x" value="1" <?php echo $data['same1x'] ? 'checked' : ''; ?>>
        <tr><td>Same 2x: <td><input type="checkbox" name="same2x" value="1" <?php echo $data['same2x'] ? 'checked' : ''; ?>>
        <tr><td>Same 3x: <td><input type="checkbox" name="same3x" value="1" <?php echo $data['same3x'] ? 'checked' : ''; ?>>
        <tr><td>Predict: <td><input type="text" name="predict" value="<?php echo htmlspecialchars($data['predict']); ?>" >
        <tr><td>Pick: <td><input type="text" name="pick" value="<?php echo htmlspecialchars($data['pick']); ?>" required>
        <tr><td>NoPick: <td><input type="text" name="nopick" value="<?php echo htmlspecialchars($data['nopick']); ?>" >
        <tr><td colspan=2><button type="submit">Update</button>
        </table>
    </form>

    <div id="result"></div>

    <script>
        $(document).ready(function() {
            $('#propForm').on('submit', function(event) {
                event.preventDefault(); // Prevent the form from submitting the traditional way
                $.ajax({
                    type: 'POST',
                    url: '', // Same page for form submission
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        $('#result').html('<p>' + response.message + '</p>');
                        if (response.status === 'success') {
                            // Optionally, you can redirect or update the UI further
                            $('#result').html('<p>done!</p>');
                        }
                        location.reload();
                    },
                    error: function() {
                        // $('#result').html('<p>An error occurred while updating.</p>');
                        location.reload();
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
$query = "select * from hrp_propanalysis order by venue,proptop desc, prop desc";
$data = mysqli_query($mysqli, $query);        
    if(mysqli_num_rows($data)>0){
        echo "<table border=1>";
        while($prop = mysqli_fetch_assoc($data)){
            echo "<tr>";
            echo "<td>".$prop['venue'];
            echo "<td>".$prop['proptop'];
            echo "<td>".$prop['prop'];
            echo "<td>".$prop['samecnt'];
            echo "<td>".$prop['isbigger'];
            echo "<td>".$prop['issmaller'];
            echo "<td>".$prop['pos'];
            echo "<td>".$prop['same1x'];
            echo "<td>".$prop['same2x'];
            echo "<td>".$prop['same3x'];
            echo "<td>".$prop['predict'];
            echo "<td>".$prop['pick'];
            echo "<td>".$prop['nopick'];
        }
    }
$mysqli->close();
?>