<?php
header('Content-Type: application/json');

$targetDir = "uploads/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['product_image'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['product_image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error']);
        exit;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        exit;
    }

    $fileName = basename($file['name']);
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = uniqid('img_', true) . '.' . $fileExt;
    $targetFilePath = $targetDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        echo json_encode(['success' => true, 'path' => $targetFilePath]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
