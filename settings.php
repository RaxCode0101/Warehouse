<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        
        $userId = $_SESSION['user_id'];
        $file = $_FILES['profile_picture'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
            exit;
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
            exit;
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/uploads/profile_pictures/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            
            // Get old profile picture to delete
            try {
                $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $oldPicture = $stmt->fetchColumn();
                
                // Delete old file if exists
                if ($oldPicture && file_exists(__DIR__ . '/' . $oldPicture)) {
                    unlink(__DIR__ . '/' . $oldPicture);
                }
            } catch (PDOException $e) {
                // Continue even if old file deletion fails
            }
            
            // Update database
            $profilePicturePath = 'uploads/profile_pictures/' . $filename;
            
            try {
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$profilePicturePath, $userId]);
                
                // Update session
                $_SESSION['profile_picture'] = $profilePicturePath;
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Profile picture updated successfully',
                    'profile_picture' => $profilePicturePath
                ]);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        }
        
        exit;
    }
    
    // Handle other settings updates (theme, etc)
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['theme'])) {
        $_SESSION['theme'] = $input['theme'];
        echo json_encode(['success' => true, 'message' => 'Theme updated']);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'No valid action provided']);
    exit;
}

// Handle GET request - return current settings
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'settings' => [
            'theme' => $_SESSION['theme'] ?? 'light',
            'profile_picture' => $_SESSION['profile_picture'] ?? ''
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);