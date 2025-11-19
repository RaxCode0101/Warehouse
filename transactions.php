<?php
require_once 'config.php';

// Export CSV when action=export
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($_GET['action']) && $_GET['action'] === 'export') {
    try {
        $sort_by = $_GET['sort_by'] ?? 'id';
        $sort_order = $_GET['sort_order'] ?? 'ASC';
        $allowed_sort_columns = ['id', 'order_id', 'transaction_date', 'price', 'payment_method', 'status'];
        if (!in_array($sort_by, $allowed_sort_columns)) { 
            $sort_by = 'id'; 
        }
        $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

        $stmt = $pdo->query("SELECT id, order_id, transaction_date, price, payment_method, status FROM transactions ORDER BY $sort_by $sort_order");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transactions.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['id','order_id','transaction_date','price','payment_method','status']);
        foreach ($rows as $r) { 
            fputcsv($output, $r); 
        }
        fclose($output);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
        exit;
    }
}

header('Content-Type: application/json');

function respond($success, $data = [], $message = '') {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

// Handle POST (create/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // ✅ LOG REQUEST untuk debugging
    error_log("POST Request: " . json_encode($input));
    
    $id = $input['id'] ?? null;
    $order_id = $input['order_id'] ?? null;
    $transaction_date = $input['transaction_date'] ?? null;
    $price = $input['price'] ?? null;
    $payment_method = $input['payment_method'] ?? null;
    $status = trim($input['status'] ?? 'belum lunas'); // ✅ TRIM dan default
    
    // ✅ Pastikan status tidak kosong
    if (empty($status)) {
        $status = 'belum lunas';
    }
    
    // ✅ LOG status sebelum validasi
    error_log("Received status: '$status' (length: " . strlen($status) . ")");
    
    // Validasi wajib
    if (!$order_id || !$transaction_date) {
        respond(false, [], 'Order ID and transaction date are required');
    }
    
    // Validasi order_id exists
    try {
        $orderCheck = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
        $orderCheck->execute([$order_id]);
        if ($orderCheck->rowCount() === 0) {
            respond(false, [], 'Failed to save transaction: The specified order does not exist. Please provide a valid order ID.');
        }
    } catch (PDOException $e) {
        respond(false, [], 'Failed to validate order: ' . $e->getMessage());
    }
    
    // ✅ VALIDASI STATUS: Normalisasi
    $status = strtolower(trim($status));
    $allowed_statuses = ['belum lunas', 'lunas', 'unknown'];
    
    if (!in_array($status, $allowed_statuses)) {
        error_log("Invalid status '$status', using default 'belum lunas'");
        $status = 'belum lunas';
    }
    
    // ✅ LOG final status
    error_log("Final status to save: '$status'");
    
    try {
        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE transactions SET order_id = ?, transaction_date = ?, price = ?, payment_method = ?, status = ? WHERE id = ?");
            $result = $stmt->execute([$order_id, $transaction_date, $price, $payment_method, $status, $id]);
            
            // ✅ Verify update
            $verify = $pdo->prepare("SELECT status FROM transactions WHERE id = ?");
            $verify->execute([$id]);
            $savedStatus = $verify->fetchColumn();
            error_log("Verified saved status in DB: '$savedStatus'");
            
            $message = 'Transaction updated successfully';
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO transactions (order_id, transaction_date, price, payment_method, status) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$order_id, $transaction_date, $price, $payment_method, $status]);
            $message = 'Transaction added successfully';
        }
        
        if ($result) {
            respond(true, [], $message);
        } else {
            respond(false, [], 'Failed to save transaction');
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        respond(false, [], 'Failed to save transaction: ' . $e->getMessage());
    }
    exit;
}

// Handle GET and DELETE
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $search = $_GET['search'] ?? '';
        $sort_by = $_GET['sort_by'] ?? 'id';
        $sort_order = $_GET['sort_order'] ?? 'ASC';

        $allowed_sort_columns = ['id', 'order_id', 'transaction_date', 'price', 'payment_method', 'status'];
        if (!in_array($sort_by, $allowed_sort_columns)) {
            $sort_by = 'id';
        }
        $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

        try {
            if ($search) {
                $stmt = $pdo->prepare("
                    SELECT id, order_id, transaction_date, price, payment_method, status 
                    FROM transactions 
                    WHERE 
                        CAST(order_id AS CHAR) LIKE ? OR 
                        payment_method LIKE ? OR 
                        status LIKE ? OR 
                        transaction_date LIKE ?
                    ORDER BY $sort_by $sort_order
                ");
                $like_search = "%$search%";
                $stmt->execute([$like_search, $like_search, $like_search, $like_search]);
            } else {
                $stmt = $pdo->query("SELECT id, order_id, transaction_date, price, payment_method, status FROM transactions ORDER BY $sort_by $sort_order");
            }
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ✅ LOG untuk debugging
            error_log("Fetched " . count($items) . " transactions");
            foreach ($items as $item) {
                error_log("ID {$item['id']}: status = '{$item['status']}' (length: " . strlen($item['status']) . ")");
            }
            
            respond(true, $items);
        } catch (PDOException $e) {
            respond(false, [], 'Failed to fetch transactions: ' . $e->getMessage());
        }
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $delete_vars);
        $id = $delete_vars['id'] ?? null;
        if (!$id) {
            respond(false, [], 'ID is required for deletion');
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
            $stmt->execute([$id]);
            respond(true, [], 'Transaction deleted successfully');
        } catch (PDOException $e) {
            respond(false, [], 'Failed to delete transaction: ' . $e->getMessage());
        }
        break;

    default:
        respond(false, [], 'Unsupported HTTP method');
}