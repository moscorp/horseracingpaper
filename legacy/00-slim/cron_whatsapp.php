<?php
//The idInstance and apiTokenInstance values are available in your account, double brackets must be removed
$url = 'https://7105.api.greenapi.com/waInstance7105409157/sendMessage/067b78d0850942689ed15e3a156eef61897a6e03aa0e42ccbd';

/**
 * Calculates compound interest between two dates.
 *
 * @param float  $principal    The initial amount (P)
 * @param float  $interestRate The annual interest rate as a percentage (e.g., 5.5 for 5.5%)
 * @param string $depositDate  The start date (YYYY-MM-DD)
 * @param string $endDate      The end date (YYYY-MM-DD)
 * @return array               Contains final balance and total interest earned
 */
function calculateCompoundInterest($principal, $interestRate, $depositDate, $endDate) {
    // 1. Calculate the time (t) in years based on total days between dates
    $d1 = new DateTime($depositDate);
    $d2 = new DateTime($endDate);
    $interval = $d1->diff($d2);
    $totalDays = $interval->days; 
    $t = $totalDays / 365; // Convert days to years

    // 2. Prepare variables for formula: A = P(1 + r/n)^(nt)
    $r = $interestRate / 100; // Annual rate in decimal form
    $n = 365;                 // Compounding frequency (daily)

    // 3. Perform calculation
    // Final Amount = P * (1 + r/n) ^ (n * t)
    $finalBalance = $principal * pow((1 + ($r / $n)), ($n * $t));
    $interestEarned = $finalBalance - $principal;

    return [
        'principal' => round($principal, 2),
        'interest'  => round($interestEarned, 2),
        'balance'   => round($finalBalance, 2),
        'days'      => $totalDays
    ];
}

// --- Usage Example ---
$principle = 30000;
$interestrate = 5.0; // 5%
$depositdate = "2025-09-08";
$enddate = date("Y-m-d");

$result = calculateCompoundInterest($principle, $interestrate, $depositdate, $enddate);

$principle2 = 9800;
$interestrate2 = 5.0; // 5%
$depositdate2 = "2026-05-01";
$enddate2 = date("Y-m-d");

$result2 = calculateCompoundInterest($principle2, $interestrate2, $depositdate2, $enddate2);

$text = "";
$text .= "Dividend update:" . "\n";
$text .= "*****************************"  . "\n";
$text .= "Principal (". $depositdate  ."):\n $" . number_format($result['principal'],2) . "\n";
$text .= "Interest Earned: $" . number_format($result['interest'],2) . " over " . number_format($result['days'],0) . " days\n";
$text .= "----------------------------"  . "\n";
$text .= "Principal (". $depositdate2 ."):\n $" . number_format($result2['principal'],2) . "\n";
$text .= "Interest Earned: $" . number_format($result2['interest'],2) . " over " . number_format($result2['days'],0) . " days\n";
$text .= "----------------------------"  . "\n";
$total = $result['balance'] + $result2['balance'];
$text .= "Final Balance: $" . number_format($total,2) . "\n";
$text .= "*****************************"  . "\n";
$text .= "Sent by momoBanker"  . "\n";

//chatId is the number to send the message to (@c.us for private chats, @g.us for group chats)
$data=array(
'chatId'=>'85256677739@c.us', 
'message'=> $text);

$options = array(
    'http' => array(
        'header' => "Content-Type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    )
);

$context = stream_context_create($options);

$response = file_get_contents($url, false, $context);

echo $response;
?>