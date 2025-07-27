<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Helper function
function respond($success, $data = [], $message = '') {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(false, [], 'Not authenticated');
}
$user_id = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get user profile data
    $stmt = $pdo->prepare('SELECT username, email, phone, place, birth_date, profile_picture FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        respond(false, [], 'User not found');
    }
    // Calculate age
    $user['umur'] = null;
    if (!empty($user['birth_date'])) {
        $birth = new DateTime($user['birth_date']);
        $now = new DateTime();
        $user['umur'] = $now->diff($birth)->y;
    }
    respond(true, $user);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'update_profile') {
        // Allow both admin and user to update their own profile data
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'user'])) {
            respond(false, [], 'Unauthorized: Only admin or user can update profile data');
        }
        $username = trim($input['username'] ?? '');
        $email = trim($input['email'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $place = trim($input['place'] ?? '');
        $birth_date = trim($input['birth_date'] ?? '');
        if (!$username || !$email || !$phone || !$place || !$birth_date) {
            respond(false, [], 'Semua field harus diisi');
        }
        // Update user data
        $stmt = $pdo->prepare('UPDATE users SET username=?, email=?, phone=?, place=?, birth_date=? WHERE id=?');
        $stmt->execute([$username, $email, $phone, $place, $birth_date, $user_id]);
        respond(true, [], 'Data profil berhasil diupdate');
    } else {
        // Update password (default)
        $oldPassword = $input['oldPassword'] ?? '';
        $newPassword = $input['newPassword'] ?? '';
        $confirmPassword = $input['confirmPassword'] ?? '';
        if (!$oldPassword || !$newPassword || !$confirmPassword) {
            respond(false, [], 'Semua field password harus diisi');
        }
        if ($newPassword !== $confirmPassword) {
            respond(false, [], 'Konfirmasi password baru tidak cocok');
        }
        // Get current password hash
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            respond(false, [], 'User not found');
        }
        if (!password_verify($oldPassword, $user['password'])) {
            respond(false, [], 'Password lama salah');
        }
        // Update password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$newHash, $user_id]);
        respond(true, [], 'Password berhasil diupdate');
    }
} else {
    respond(false, [], 'Unsupported method');
}
