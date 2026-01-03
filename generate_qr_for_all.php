<?php
require_once 'config.php';

// Function to generate QR code using online API
function generateQRCode($item_code, $name) {
    $qrData = json_encode([
        'code' => $item_code,
        'name' => $name,
        'type' => 'inventory'
    ]);

    // Try multiple APIs as fallback
    $apis = [
        'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrData),
        'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($qrData),
        'https://api.qr-code-generator.com/v1/create?access-token=demo&data=' . urlencode($qrData)
    ];

    foreach ($apis as $apiUrl) {
        // Generate unique filename
        $filename = 'qr_' . $item_code . '_' . time() . '_' . rand(1000, 9999) . '.png';
        $filepath = 'uploads/' . $filename;

        // Set timeout for API call
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'PHP-QR-Generator/1.0'
            ]
        ]);

        // Download and save QR code image
        $imageData = file_get_contents($apiUrl, false, $context);
        if ($imageData !== false && strlen($imageData) > 100) { // Basic check for valid image data
            if (file_put_contents($filepath, $imageData)) {
                return $filepath;
            }
        }
    }

    return null; // Return null if all APIs failed
}

try {
    // Get all inventory items that don't have QR codes yet
    $stmt = $pdo->query("SELECT id, item_code, name FROM inventory WHERE qr_code IS NULL OR qr_code = ''");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $successCount = 0;
    $failCount = 0;

    echo "Found " . count($items) . " items without QR codes.\n";
    echo "Generating QR codes...\n\n";

    foreach ($items as $item) {
        echo "Processing item: {$item['item_code']} - {$item['name']}\n";

        $qrPath = generateQRCode($item['item_code'], $item['name']);

        if ($qrPath) {
            // Update the database with the QR code path
            $updateStmt = $pdo->prepare("UPDATE inventory SET qr_code = ? WHERE id = ?");
            $updateStmt->execute([$qrPath, $item['id']]);

            echo "✅ QR code generated and saved: $qrPath\n";
            $successCount++;
        } else {
            echo "❌ Failed to generate QR code for item: {$item['item_code']}\n";
            $failCount++;
        }

        // Small delay to avoid overwhelming the APIs
        usleep(100000); // 0.1 second
    }

    echo "\n=== SUMMARY ===\n";
    echo "Total items processed: " . count($items) . "\n";
    echo "Successful: $successCount\n";
    echo "Failed: $failCount\n";
    echo "QR code generation completed!\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
