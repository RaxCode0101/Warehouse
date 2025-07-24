<?php
require_once 'config.php';

$username = 'bryan';
$password = 'bryan123';
$full_name = 'Bryan Phillip Sumarauw';
$role = 'admin';

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    if ($user) {
        // Update existing user with admin role and password
        $stmtUpdate = $pdo->prepare("UPDATE users SET full_name = ?, password = ?, role = ? WHERE id = ?");
        $stmtUpdate->execute([$full_name, $hashedPassword, $role, $user['id']]);
        echo json_encode(['success' => true, 'message' => "User 'bryan' updated to admin role successfully."]);
    } else {
        // Insert new user
        $stmtInsert = $pdo->prepare("INSERT INTO users (username, full_name, password, role) VALUES (?, ?, ?, ?)");
        $stmtInsert->execute([$username, $full_name, $hashedPassword, $role]);
        echo json_encode(['success' => true, 'message' => "User 'bryan' created with admin role successfully."]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error creating/updating user: ' . $e->getMessage()]);
}
?>
