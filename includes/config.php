<?php
/**
 * NEXUS CAMPUS PORTAL
 * Database Configuration
 */

define('DB_HOST',   'localhost');
define('DB_USER',   'root');
define('DB_PASS',   '');          // Ganti sesuai password MySQL kamu
define('DB_NAME',   'nexus_campus');
define('DB_PORT',   3306);
define('DB_CHARSET','utf8mb4');

// App Config
define('APP_NAME',    'NexusCampus');
define('APP_VERSION', '2.0.1');

// Auto-detect APP_URL robustly
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';

// Check if running on localhost:8000 root or Apache subfolder
if (strpos($host, 'localhost:8000') !== false) {
    define('APP_URL', $protocol . $host);
} else {
    define('APP_URL', $protocol . $host . '/kampus-portal');
}
define('UPLOAD_DIR',  __DIR__ . '/../uploads/tugas/');
define('MAX_FILE_MB', 10);  // max upload 10MB

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(503);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
