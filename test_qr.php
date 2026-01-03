<?php
// Test script for QR code functionality
require_once 'config.php';
require_once 'inventory.php'; // Include the inventory.php to access the generateQRCode function

// Test generating QR code
echo "Testing QR code generation...\n";
$qrPath = generateQRCode('TEST001', 'Test Item');
if ($qrPath) {
    echo "QR code generated successfully: $qrPath\n";
    if (file_exists($qrPath)) {
        echo "File exists on disk.\n";
    } else {
        echo "File does not exist on disk.\n";
    }
} else {
    echo "QR code generation failed.\n";
}

// Test database insertion
echo "\nTesting database insertion...\n";
try {
    $stmt = $pdo->prepare("INSERT INTO inventory (item_code, name, category, stock, status, image_path, qr_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute(['TEST001', 'Test Item', 'Test Category', 10, 'In Stock', null, $qrPath]);
    if ($result) {
        echo "Database insertion successful.\n";
        $lastId = $pdo->lastInsertId();
        echo "Inserted item ID: $lastId\n";
    } else {
        echo "Database insertion failed.\n";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

// Test fetching data
echo "\nTesting data fetching...\n";
try {
    $stmt = $pdo->query("SELECT * FROM inventory WHERE item_code = 'TEST001'");
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($item) {
        echo "Item fetched successfully:\n";
        echo "ID: " . $item['id'] . "\n";
        echo "Item Code: " . $item['item_code'] . "\n";
        echo "Name: " . $item['name'] . "\n";
        echo "QR Code Path: " . ($item['qr_code'] ?? 'NULL') . "\n";
    } else {
        echo "Item not found.\n";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
