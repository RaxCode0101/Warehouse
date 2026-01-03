<?php
require_once 'config.php';

try {
    $stmt = $pdo->query('SELECT item_code, name, qr_code FROM inventory LIMIT 10');
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Checking QR codes for first 10 items:\n\n";

    foreach($items as $item) {
        $hasQr = !empty($item['qr_code']);
        $qrPath = $item['qr_code'] ?: 'null';
        echo "{$item['item_code']}: " . ($hasQr ? 'HAS QR' : 'NO QR') . " - " . substr($qrPath, 0, 50) . "\n";
    }

    // Count total
    $stmt = $pdo->query('SELECT COUNT(*) as total, COUNT(qr_code) as with_qr FROM inventory');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\n=== SUMMARY ===\n";
    echo "Total items: {$result['total']}\n";
    echo "Items with QR codes: {$result['with_qr']}\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
