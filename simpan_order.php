<?php
// Mulai session untuk menyimpan pesan error
session_start();

// Pastikan request adalah POST untuk keamanan
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Alihkan ke form jika diakses langsung
    header('Location: form_order.php');
    exit();
}

// Fungsi untuk mengarahkan kembali dengan pesan error
function redirect_with_error($message) {
    $_SESSION['error_message'] = $message;
    header('Location: form_order.php');
    exit();
}

// Sertakan file koneksi database
require_once 'koneksi.php';

// Ambil data dari form menggunakan filter untuk keamanan
$supplier_id = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT);
// FILTER_SANITIZE_STRING sudah deprecated.
// Cara yang benar adalah mengambil data mentah dan melakukan escaping saat output.
// Karena Anda sudah menggunakan prepared statements (untuk DB) dan htmlspecialchars (untuk HTML), ini aman.
$nama_produk = filter_input(INPUT_POST, 'nama_produk');
$jumlah = filter_input(INPUT_POST, 'jumlah', FILTER_VALIDATE_INT);

// Validasi dasar: pastikan semua data terisi dengan benar
if (empty($nama_produk) || empty($supplier_id) || empty($jumlah) || $jumlah < 1) {
    redirect_with_error("Semua field wajib diisi dan jumlah harus minimal 1.");
}

try {
    // =================================================================
    // INI ADALAH BAGIAN PERBAIKAN UTAMA: Validasi Foreign Key
    // =================================================================
    // Periksa kembali apakah supplier_id yang dikirim benar-benar ada di tabel 'suppliers'
    $stmt = $pdo->prepare("SELECT id FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplierExists = $stmt->fetch();

    if (!$supplierExists) {
        // Jika supplier tidak ditemukan, arahkan kembali dengan pesan error.
        // Ini adalah langkah yang mencegah error "Integrity constraint violation".
        redirect_with_error("Gagal menyimpan order. Supplier yang dipilih tidak valid.");
    }

    // Jika validasi berhasil, lanjutkan proses penyimpanan order
    // Menggunakan kolom yang umum ada di tabel order: `nama_produk`, `jumlah`, `supplier_id`, dan `tanggal_order`
    $sql = "INSERT INTO orders (nama_produk, jumlah, supplier_id, tanggal_order) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    
    // Eksekusi query untuk menyimpan order
    $stmt->execute([$nama_produk, $jumlah, $supplier_id]);

    // Simpan pesan sukses di session
    $_SESSION['success_message'] = "Order untuk produk '<strong>" . htmlspecialchars($nama_produk) . "</strong>' berhasil disimpan.";

    // Arahkan kembali ke form order
    header('Location: form_order.php');
    exit();

} catch (\PDOException $e) {
    // Tangkap error database lain yang mungkin terjadi dan tampilkan pesan yang lebih jelas.
    // Untuk production, sebaiknya log error ini daripada menampilkannya ke pengguna.
    error_log($e->getMessage()); // Log error ke file log server
    redirect_with_error("Terjadi kesalahan pada database. Silakan coba lagi nanti.");
}