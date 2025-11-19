<?php
// Mulai session untuk bisa membaca pesan dari simpan_order.php
session_start();

// Sertakan file koneksi untuk mengambil data supplier
require_once 'koneksi.php';

// Ambil semua supplier dari database untuk ditampilkan di dropdown
try {
    $stmt = $pdo->query("SELECT id, nama_supplier FROM suppliers ORDER BY nama_supplier ASC");
    $suppliers = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("ERROR: Tidak bisa mengambil data supplier. " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Order Baru</title>
    <style>
        body { font-family: sans-serif; margin: 2em; }
        .form-group { margin-bottom: 1em; }
        label { display: block; margin-bottom: 0.5em; }
        .alert { padding: 1em; margin-bottom: 1em; border-radius: 5px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        input, select, button { padding: 0.5em; width: 300px; }
        button { width: auto; cursor: pointer; }
    </style>
</head>
<body>

    <h1>Buat Order Baru</h1>

    <?php
    // Tampilkan pesan sukses jika ada
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']); // Hapus pesan agar tidak tampil lagi
    }

    // Tampilkan pesan error jika ada
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
        unset($_SESSION['error_message']); // Hapus pesan agar tidak tampil lagi
    }
    ?>

    <form action="simpan_order.php" method="POST">
        <div class="form-group">
            <label for="nama_produk">Nama Produk:</label>
            <input type="text" id="nama_produk" name="nama_produk" required>
        </div>

        <div class="form-group">
            <label for="jumlah">Jumlah:</label>
            <input type="number" id="jumlah" name="jumlah" required min="1">
        </div>

        <div class="form-group">
            <label for="supplier_id">Pilih Supplier:</label>
            <select id="supplier_id" name="supplier_id" required>
                <option value="">-- Pilih Supplier --</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= htmlspecialchars($supplier['id']) ?>">
                        <?= htmlspecialchars($supplier['nama_supplier']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">Simpan Order</button>
    </form>

</body>
</html>
