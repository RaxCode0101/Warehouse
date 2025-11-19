<?php
header('Content-Type: application/json');
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

function respond($success, $data = [], $message = '') {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

switch ($method) {
    case 'GET':
        $search = $_GET['search'] ?? '';
        $sort_by = $_GET['sort_by'] ?? 'id';
        $sort_order = $_GET['sort_order'] ?? 'ASC';

        $allowed_sort_columns = ['id', 'supplier_name', 'item_code', 'buyers', 'order_date', 'status', 'total_amount'];
        if (!in_array($sort_by, $allowed_sort_columns)) {
            $sort_by = 'id';
        }
        $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

        try {
            $baseQuery = "SELECT o.*, s.name as supplier_name FROM orders o LEFT JOIN suppliers s ON o.supplier_id = s.id";

            if ($search) {
                $stmt = $pdo->prepare("$baseQuery WHERE o.item_code LIKE ? OR o.buyers LIKE ? OR s.name LIKE ? ORDER BY $sort_by $sort_order");
                $like_search = "%$search%";
                $stmt->execute([$like_search, $like_search, $like_search]);
            } else {
                $stmt = $pdo->query("$baseQuery ORDER BY $sort_by $sort_order");
            }
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(true, $items);
        } catch (PDOException $e) {
            respond(false, [], 'Failed to fetch orders: ' . $e->getMessage());
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            respond(false, [], 'Invalid input');
        }

        $id = $input['id'] ?? null;
        $supplier_id = filter_var($input['supplier_id'] ?? null, FILTER_VALIDATE_INT);
        $item_code = trim($input['item_code'] ?? '');
        $buyers = trim($input['buyers'] ?? '');
        $order_date = $input['order_date'] ?? '';
        $status = $input['status'] ?? 'Pending';
        $total_amount = filter_var($input['total_amount'] ?? 0, FILTER_VALIDATE_INT);

        // --- Validasi Input yang Diperketat ---
        if (empty($supplier_id) || $supplier_id === false || $supplier_id <= 0) {
            respond(false, [], 'Supplier ID harus dipilih dan valid.');
        }
        if (empty($item_code) || empty($buyers) || empty($order_date)) {
            respond(false, [], 'Kode barang, pembeli, dan tanggal order wajib diisi.');
        }

        if (!in_array($status, ['Pending', 'Processing', 'Completed', 'Cancelled'])) {
            $status = 'Pending';
        }

        // --- Validasi Integritas Data (Kunci Pencegahan Error 1452) ---
        try {
            // Periksa apakah supplier_id ada di tabel suppliers
            $stmtCheck = $pdo->prepare("SELECT 1 FROM suppliers WHERE id = ?");
            $stmtCheck->execute([$supplier_id]);
            if ($stmtCheck->fetchColumn() === false) {
                respond(false, [], "Supplier dengan ID '{$supplier_id}' tidak ditemukan.");
            }

            // Lanjutkan operasi jika supplier valid
            if ($id) {
                // UPDATE
                $stmt = $pdo->prepare("UPDATE orders SET supplier_id = ?, item_code = ?, buyers = ?, order_date = ?, status = ?, total_amount = ? WHERE id = ?");
                $stmt->execute([$supplier_id, $item_code, $buyers, $order_date, $status, $total_amount, $id]);
                respond(true, [], 'Order updated successfully');
            } else { 
                // INSERT
                $stmt = $pdo->prepare("INSERT INTO orders (supplier_id, item_code, buyers, order_date, status, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$supplier_id, $item_code, $buyers, $order_date, $status, $total_amount]);
                $lastId = $pdo->lastInsertId();
                respond(true, ['id' => $lastId], 'Order created successfully');
            }
        } catch (PDOException $e) {
            // Menangkap error database yang tidak terduga, bukan error validasi
            error_log("Database Error on Order Save: " . $e->getMessage());
            respond(false, [], 'Gagal menyimpan order karena kesalahan sistem.');
        }
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $delete_vars);
        $id = $delete_vars['id'] ?? null;
        if (!$id) {
            respond(false, [], 'ID is required for deletion');
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            respond(true, [], 'Order deleted successfully');
        } catch (PDOException $e) {
            respond(false, [], 'Failed to delete order: ' . $e->getMessage());
        }
        break;

    default:
        respond(false, [], 'Unsupported HTTP method');
}
