<?php
/**
 * NEXUS CAMPUS PORTAL
 * Database Configuration
 */

// Tangkap environment variables dari Railway
$envHost = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST');
$envUser = getenv('MYSQLUSER') ?: getenv('MYSQL_USER');
$envPass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD');
$envName = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE');
$envPort = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT');

// Jika $envHost kosong, bernilai 'localhost', atau salah terbaca jadi string 'MySQL', 
// paksa ubah ke '127.0.0.1' agar koneksi TCP/IP sukses tanpa error nama host tidak dikenal.
if (empty($envHost) || $envHost === 'localhost' || strtolower($envHost) === 'mysql') {
    $envHost = '127.0.0.1';
}

define('DB_HOST',    $envHost);
define('DB_USER',    $envUser ?: 'root');
define('DB_PASS',    $envPass ?: '');
define('DB_NAME',    $envName ?: 'railway'); // Railway secara default menamai DB-nya 'railway'
define('DB_PORT',    $envPort ? (int)$envPort : 3306);
define('DB_CHARSET', 'utf8mb4');

// App Config
define('APP_NAME',    'NexusCampus');
define('APP_VERSION', '2.0.1');

// Auto-detect APP_URL robustly
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';

if (getenv('RAILWAY_STATIC_URL') || getenv('RAILWAY_PUBLIC_DOMAIN')) {
    $railwayUrl = getenv('RAILWAY_PUBLIC_DOMAIN') ?: getenv('RAILWAY_STATIC_URL');
    define('APP_URL', 'https://' . $railwayUrl);
} elseif (strpos($host, 'localhost:8000') !== false || strpos($host, 'localhost') !== false) {
    define('APP_URL', $protocol . $host);
} else {
    define('APP_URL', $protocol . $host . '/kampus-portal');
}

define('UPLOAD_DIR',  __DIR__ . '/../uploads/tugas/');
define('MAX_FILE_MB', 10);

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
