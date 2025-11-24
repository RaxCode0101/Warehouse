<?php
session_start();
header('Content-Type: application/json');

// Debugging
error_log('Request received: ' . print_r($_POST, true));

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
require_once 'config.php';

// Create users table if not exists
try {
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'user',
        profile_picture VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
} catch(PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Table creation failed: ' . $e->getMessage()]));
}

// Verify table exists
$stmt = $pdo->query("SHOW TABLES LIKE 'users'");
if($stmt->rowCount() == 0) {
    die(json_encode(['success' => false, 'message' => 'Users table does not exist']));
}

// Use $_REQUEST to handle both GET and POST variables, which is more robust for this scenario.
// This reliably captures the 'action' from FormData in file uploads.
$action = $_REQUEST['action'] ?? '';

error_log('Action received: ' . $action);

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Action is required']);
    exit;
}

switch($action) {
        case 'register':
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
        $fullName = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

        // Remove HTML tags and extra whitespace
        $username = trim(strip_tags($username));
        $password = trim(strip_tags($password));
        $fullName = trim(strip_tags($fullName));
        
        // Additional validation
        $username = trim($username);
        $fullName = trim($fullName);
        
        // Validate username format
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores']);
            exit;
        }
        
        // Validate full name format
        if (!preg_match('/^[a-zA-Z\s]+$/', $fullName)) {
            echo json_encode(['success' => false, 'message' => 'Full name can only contain letters and spaces']);
            exit;
        }

        // Validation
        if(empty($username) || empty($password) || empty($fullName)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        if(strlen($username) < 4) {
            echo json_encode(['success' => false, 'message' => 'Username must be at least 4 characters']);
            exit;
        }

        if(strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
            exit;
        }

        $role = 'user'; // Set default role to 'user'

        try {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if($stmt->fetch()) {
                // Reset login attempts on successful login
                unset($_SESSION['login_attempts']);
                unset($_SESSION['last_attempt_time']);
                
                echo json_encode(['success' => false, 'message' => 'Username already exists']);
                exit;
            }

            // Insert new user
            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                if ($hashedPassword === false) {
                    throw new Exception('Password hashing failed');
                }
                
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (:username, :password, :full_name, :role)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':full_name', $fullName);
                $stmt->bindParam(':role', $role);
                
                if($stmt->execute()) {
                    error_log("User registered successfully: " . $username);
                    echo json_encode(['success' => true, 'message' => 'Registration successful']);
                } else {
                    $errorInfo = $stmt->errorInfo();
                    error_log("Registration failed for $username: " . json_encode($errorInfo));
                    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $errorInfo[2]]);
                }
            } catch(PDOException $e) {
                error_log("Registration exception for $username: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Registration error: ' . $e->getMessage()]);
            }
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        }
        exit;

    case 'login':
        error_log('Login action triggered: ' . print_r($_POST, true));
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Username and password are required']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                error_log('Login successful for user: ' . $username);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                echo json_encode(['success' => true, 'message' => 'Login successful']);
            } else {
                error_log('Invalid username or password for user: ' . $username);
                echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
            }
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
        }
        exit;

    case 'logout':
        // Destroy session
        session_unset();
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        exit;

            case 'check_status':
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT id, username, full_name, role, profile_picture FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'logged_in' => true,
                'user' => [
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'profile_picture' => $user['profile_picture'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=random',
                    'role' => $user['role']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'logged_in' => false
            ]);
        }
        exit;

    case 'upload_profile_picture':
        // Log informasi file yang diunggah
        error_log(print_r($_FILES, true));

        if (!isset($_FILES['profilePicture']) || $_FILES['profilePicture']['error'] !== UPLOAD_ERR_OK) {
            error_log('File upload error: ' . $_FILES['profilePicture']['error']);
            echo json_encode(['success' => false, 'message' => 'File upload failed.']);
            exit;
        }

        // Validasi tipe file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['profilePicture']['type'], $allowedTypes)) {
            error_log('Invalid file type: ' . $_FILES['profilePicture']['type']);
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
            exit;
        }

        // Validasi ukuran file
        $maxFileSize = 2 * 1024 * 1024; // 2 MB
        if ($_FILES['profilePicture']['size'] > $maxFileSize) {
            error_log('File size exceeds limit: ' . $_FILES['profilePicture']['size']);
            echo json_encode(['success' => false, 'message' => 'File size exceeds the 2MB limit.']);
            exit;
        }

        // Direktori unggahan
        $uploadDir = 'uploads/profile_pictures/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Nama file unik
        $fileName = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . pathinfo($_FILES['profilePicture']['name'], PATHINFO_EXTENSION);
        $filePath = $uploadDir . $fileName;

        // Pindahkan file ke direktori unggahan
        if (move_uploaded_file($_FILES['profilePicture']['tmp_name'], $filePath)) {
            // Perbarui URL foto profil di database
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$filePath, $_SESSION['user_id']]);

            // Perbarui sesi dengan URL foto profil baru
            $_SESSION['profile_picture'] = $filePath;

            echo json_encode(['success' => true, 'newProfilePictureUrl' => $filePath]);
        } else {
            error_log('Failed to move uploaded file: ' . $_FILES['profilePicture']['tmp_name'] . ' to ' . $filePath);
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
        }
        exit;

        case 'get_users':
    try {
        $stmt = $pdo->query("SELECT id, username, full_name, role, created_at FROM users ORDER BY id DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}
