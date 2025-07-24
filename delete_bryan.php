<?php
require_once 'config.php';

try {
    // Find user id for username 'bryan'
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['bryan']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userId = $user['id'];
        // Delete user by id
        $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->execute([$userId]);
        echo json_encode(['success' => true, 'message' => "User 'bryan' deleted successfully."]);
    } else {
        echo json_encode(['success' => false, 'message' => "User 'bryan' not found."]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
}
?>
