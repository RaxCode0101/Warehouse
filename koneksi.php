<?php
// --- Detail Koneksi Database ---
// Ganti dengan detail database Anda
$host = 'localhost';
$dbname = 'warehouse_db';
$user = 'root';
$pass = ''; // Sesuaikan jika database Anda memiliki password
$charset = 'utf8mb4';

// --- Konfigurasi PDO ---
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Melempar exception jika ada error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Mengambil data sebagai array asosiatif
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// --- Buat Objek Koneksi PDO ---
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Tampilkan pesan error yang lebih ramah di production
    // Di development, biarkan pesan ini untuk debugging
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
