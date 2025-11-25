<?php
$host = 'localhost';
$dbname = 'warehouse_db';
$username = 'root';
$password = '';

// Tambahkan di config.php untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// ✅ PERBAIKAN: SET TIMEOUT UNTUK MENCEGAH CONNECTION HANG
ini_set('max_execution_time', 30);
ini_set('default_socket_timeout', 10);

try {
    // First try to connect without specifying database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5); // ✅ SET TIMEOUT 5 DETIK

    // Check if database exists, if not create it
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    if($stmt->rowCount() == 0) {
        $pdo->exec("CREATE DATABASE $dbname");
    }
    
    // Now connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5); // ✅ SET TIMEOUT 5 DETIK
    
} 

catch(PDOException $e) {
    // ✅ PERBAIKAN: JANGAN DIE() SAAT ERROR, TAPI RETURN JSON YANG BISA DIPROSES
    error_log("Database connection failed: " . $e->getMessage());
    // Biarkan error dilanjutkan ke auth.php untuk handling yang proper
    throw new PDOException('Database connection failed: ' . $e->getMessage());
}
// catch(PDOException $e) {
//     die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
// }
?>