<?php

/**
 * Database Constants - these constants are required in order for there to be a 
 * successful connection to the database. Make sure the information is correct.
 */
define("DB_TYPE", "mysql");
define("DB_HOST", "localhost");
define("DB_USER", "fengrmkw_melvin");
define("DB_PASS", "mopass.24626388");
define("DB_NAME", "fengrmkw_moosay");

define("DB_TYPEwp", "mysqli");
define("DB_HOSTwp", "localhost");
define("DB_USERwp", "fengrmkw_melvin");
define("DB_PASSwp", "mopass.24626388");
define("DB_NAMEwp", "fengrmkw_moosay");

$dbConfig = [
    'host' => 'localhost',
    'name' => 'fengrmkw_moosay',
    'user' => 'fengrmkw_melvin',
    'pass' => 'mopass.24626388'
];

/**
 * Special Name Constants - It's important that the ADMIN_NAME constant matches
 * the Super Admin username (case not important) who is level 10.
 */
define("ADMIN_NAME", "sales");
define("GUEST_NAME", "Guest");


/**
 * Level Constants - Best not to change these unless you know what you are doing.
 * Use groups to assign levels.
 */
define("SUPER_ADMIN_LEVEL", 10); // Super Admin - There can be only one!
define("ADMIN_LEVEL", 9); // Other Admins - Promoted by the Super Admin 
define("REGUSER_LEVEL", 3); // Normal Registered User
define("ADMIN_ACT", 2); // Awaiting Admin activation
define("ACT_EMAIL", 1); // Awaiting Email Activation
define("GUEST_LEVEL", 0);

$wpConfig = [
    'username' => 'horsemaster',      // WordPress 用戶名
    'app_password' => 'tspN A4Vf N4cp WMdT XZFg 45M3' // WordPress 應用密碼
];


// ===== OPENWA API CONFIGURATION =====
define('OPENWA_API_URL', 'https://wa.buycarl.com');  // Your Cloudflare tunnel URL
// define('OPENWA_API_KEY', 'owa_k1_4dc46325f4726ebff3d8a2b60982f7dd8d1b220375de60f4f57dc2eb70b4d735');
define('OPENWA_API_KEY', 'owa_k1_b3cfc971922a98461e6ab4b2584d0b16d96fdef7a4737f49eb8dc86d31cf83f3');
define('OPENWA_SESSION_ID', '49202476-9666-4ec8-ac00-010a719cd7cb');

define('API_TIMEOUT', 30);  // Timeout in seconds
define('RATE_LIMIT_DELAY', 1);  // Seconds between messages

// ===== APPLICATION CONFIGURATION =====
define('DEBUG_MODE', true);  // Set to false in production
define('LOG_DIR', __DIR__ . '/logs/');
define('MAX_RETRIES', 3);
define('BATCH_SIZE', 50);  // Max users per cron run

// Create log directory if not exists
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// Database connection function
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            logMessage("Database connection failed: " . $e->getMessage(), 'ERROR');
            throw new Exception("Database connection failed");
        }
    }
    return $pdo;
}

// Logging function
function logMessage($message, $level = 'INFO') {
    if (!DEBUG_MODE && $level === 'DEBUG') return;
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    $logFile = LOG_DIR . 'whatsapp_' . date('Y-m-d') . '.log';
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
?>