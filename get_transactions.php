<?php
header('Content-Type: application/json');

// include file koneksi (PDO)
require_once __DIR__ . "/koneksi.php";

// cek koneksi PDO
if (!$pdo) {
    echo json_encode(["error" => "Database connection failed (PDO not initialized)"]);
    exit;
}

try {
    // Query data transaksi
    $stmt = $pdo->query("SELECT id, order_id, transaction_date, price, payment_method, status FROM transactions ORDER BY id DESC");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

} catch (PDOException $e) {
    echo json_encode([
        "error" => "Query failed",
        "details" => $e->getMessage()
    ]);
}