<?php
$host = 'localhost';
$dbname = 'warehouse_db';
$username = 'root';
$password = '';

try {
    // First try to connect without specifying database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists, if not create it
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    if($stmt->rowCount() == 0) {
        $pdo->exec("CREATE DATABASE $dbname");
    }
    
    // Now connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
}
?>