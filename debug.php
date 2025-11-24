<?php
// Debug script untuk melacak masalah refresh
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log semua request
$logFile = 'debug_log.txt';
$logMessage = date('Y-m-d H:i:s') . " - " . $_SERVER['REQUEST_URI'] . " - " . $_SERVER['REQUEST_METHOD'] . "\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

// Cek session
session_start();
if (!isset($_SESSION['last_request'])) {
    $_SESSION['last_request'] = time();
} else {
    $timeDiff = time() - $_SESSION['last_request'];
    if ($timeDiff < 1) { // Jika request terlalu cepat (kurang dari 1 detik)
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - POSSIBLE INFINITE LOOP DETECTED\n", FILE_APPEND);
        die('Possible infinite loop detected. Please check your JavaScript code.');
    }
    $_SESSION['last_request'] = time();
}

echo "Debug mode active. Check debug_log.txt for details.";
?>