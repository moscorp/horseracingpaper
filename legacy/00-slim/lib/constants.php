<?php
/**
 * Legacy DB/API constants — DO NOT commit real secrets.
 * Prefer environment variables or a local constants.local.php (gitignored).
 */

if (is_file(__DIR__ . '/constants.local.php')) {
    require_once __DIR__ . '/constants.local.php';
}

if (!defined('DB_TYPE')) {
    define('DB_TYPE', getenv('HRP_DB_TYPE') ?: 'mysql');
}
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('HRP_DB_HOST') ?: 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('HRP_DB_USER') ?: '');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('HRP_DB_PASS') ?: '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('HRP_DB_NAME') ?: '');
}

if (!defined('DB_TYPEwp')) {
    define('DB_TYPEwp', 'mysqli');
}
if (!defined('DB_HOSTwp')) {
    define('DB_HOSTwp', DB_HOST);
}
if (!defined('DB_USERwp')) {
    define('DB_USERwp', DB_USER);
}
if (!defined('DB_PASSwp')) {
    define('DB_PASSwp', DB_PASS);
}
if (!defined('DB_NAMEwp')) {
    define('DB_NAMEwp', DB_NAME);
}

$dbConfig = [
    'host' => DB_HOST,
    'name' => DB_NAME,
    'user' => DB_USER,
    'pass' => DB_PASS,
];

if (!defined('ADMIN_NAME')) {
    define('ADMIN_NAME', 'sales');
}
if (!defined('GUEST_NAME')) {
    define('GUEST_NAME', 'Guest');
}
if (!defined('SUPER_ADMIN_LEVEL')) {
    define('SUPER_ADMIN_LEVEL', 10);
}
if (!defined('ADMIN_LEVEL')) {
    define('ADMIN_LEVEL', 9);
}
if (!defined('REGUSER_LEVEL')) {
    define('REGUSER_LEVEL', 3);
}
if (!defined('ADMIN_ACT')) {
    define('ADMIN_ACT', 2);
}
if (!defined('ACT_EMAIL')) {
    define('ACT_EMAIL', 1);
}
if (!defined('GUEST_LEVEL')) {
    define('GUEST_LEVEL', 0);
}

$wpConfig = [
    'username' => getenv('HRP_WP_USER') ?: '',
    'app_password' => getenv('HRP_WP_APP_PASSWORD') ?: '',
];

if (!defined('OPENWA_API_URL')) {
    define('OPENWA_API_URL', getenv('OPENWA_API_URL') ?: 'https://wa.buycarl.com');
}
if (!defined('OPENWA_API_KEY')) {
    define('OPENWA_API_KEY', getenv('OPENWA_API_KEY') ?: '');
}
if (!defined('OPENWA_SESSION_ID')) {
    define('OPENWA_SESSION_ID', getenv('OPENWA_SESSION_ID') ?: '');
}
if (!defined('API_TIMEOUT')) {
    define('API_TIMEOUT', 30);
}
if (!defined('RATE_LIMIT_DELAY')) {
    define('RATE_LIMIT_DELAY', 1);
}
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}
if (!defined('LOG_DIR')) {
    define('LOG_DIR', __DIR__ . '/logs/');
}
if (!defined('MAX_RETRIES')) {
    define('MAX_RETRIES', 3);
}
if (!defined('BATCH_SIZE')) {
    define('BATCH_SIZE', 50);
}

if (!is_dir(LOG_DIR)) {
    @mkdir(LOG_DIR, 0755, true);
}

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        if (!DB_USER || !DB_NAME) {
            throw new Exception('Database credentials not configured (use constants.local.php or env)');
        }
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
    return $pdo;
}

function logMessage($message, $level = 'INFO') {
    if (!DEBUG_MODE && $level === 'DEBUG') {
        return;
    }
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    $logFile = LOG_DIR . 'whatsapp_' . date('Y-m-d') . '.log';
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}
