<?php
require 'config.php';

try {
    // Fungsi untuk menambahkan atau memperbarui user
    function addOrUpdateUser($pdo, $username, $password, $role, $full_name = '') {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name) VALUES (:username, :password, :role, :full_name) ON DUPLICATE KEY UPDATE password = :password, role = :role, full_name = :full_name");
        $stmt->execute([':username' => $username, ':password' => $hashed_password, ':role' => $role, ':full_name' => $full_name]);
    }

    // Tambahkan user alvian
    addOrUpdateUser($pdo, 'alvian', 'alvian123', 'user', 'Alvian Nur Isra');

    // Tambahkan admin bryan
    addOrUpdateUser($pdo, 'bryan', 'bryan123', 'admin', 'Bryan Phillip Sumarauw');

    echo "Users successfully added/updated.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>