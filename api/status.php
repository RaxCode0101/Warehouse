<?php
// api/status.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config.php';

try {
    // Cek status transaksi secara keseluruhan
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN status = 'lunas' THEN 1 ELSE 0 END) as lunas_count,
            SUM(CASE WHEN status = 'belum lunas' THEN 1 ELSE 0 END) as belum_lunas_count
        FROM transactions
    ");
    $status_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total = $status_data['total_transactions'];
    $lunas = $status_data['lunas_count'];
    $belum_lunas = $status_data['belum_lunas_count'];
    
    // Tentukan status sistem berdasarkan rasio transaksi lunas
    if ($total > 0) {
        $lunas_ratio = $lunas / $total;
        
        if ($lunas_ratio >= 0.8) {
            $system_status = 'lunas';
            $message = "Sistem normal - {$lunas}/{$total} transaksi lunas";
        } else if ($lunas_ratio >= 0.5) {
            $system_status = 'unknown';
            $message = "Perhatian - {$lunas}/{$total} transaksi lunas";
        } else {
            $system_status = 'belum lunas';
            $message = "Peringatan - {$belum_lunas}/{$total} transaksi belum lunas";
        }
    } else {
        $system_status = 'unknown';
        $message = 'Tidak ada data transaksi';
    }
    
    echo json_encode([
        'success' => true,
        'status' => $system_status,
        'detail' => $message,
        'data' => [
            'total' => $total,
            'lunas' => $lunas,
            'belum_lunas' => $belum_lunas
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'status' => 'unknown',
        'detail' => 'Error fetching system status: ' . $e->getMessage()
    ]);
}
?>