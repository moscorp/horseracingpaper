<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // adjust later to your domain for security

$API_SECRET = '87e5450b-a617-42e9-b446-e523e08ebcbe'; // shared secret
//https://buycarl.com/00/api.php?token=87e5450b-a617-42e9-b446-e523e08ebcbe&table=00m6&limit=1000

// ---- CONFIG ----
$db_host = 'localhost';      // often localhost on shared hosting
$db_name = 'fengrmkw_moosay';
$db_user = 'fengrmkw_melvin';
$db_pass = 'YOUR_DB_PASSWORD';

// Simple token check (GET ?token=...)
$token = $_GET['token'] ?? '';
if ($token !== $API_SECRET) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ---- INPUT PARAMETERS ----
$table = $_GET['table'] ?? '';
$limit  = intval($_GET['limit'] ?? 100);
$where  = $_GET['where'] ?? ''; // optional: e.g. "status=active&date>2024-01-01"

// ---- ALLOWED TABLES (whitelist for safety) ----
$allowed = ['00m6']; // add your table names
if (!in_array($table, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid table']);
    exit;
}

// ---- BUILD QUERY ----
// $sql = "SELECT * FROM `$table` ORDER BY CONCAT(year, nos) DESC LIMIT $limit";
$sql = "SELECT * FROM `$table` LIMIT $limit";
if ($where) {
    $sql = "SELECT * FROM `$table` WHERE $where LIMIT $limit";
}

// ---- CONNECT & FETCH ----
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}
$mysqli->set_charset('utf8mb4');
$result = $mysqli->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query error: '.$mysqli->error]);
    exit;
}
$rows = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['data' => $rows, 'count' => count($rows)], JSON_PRETTY_PRINT);
