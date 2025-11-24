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

// ✅ FUNCTION TO GENERATE UNIQUE TRANSACTION NUMBER
function generateTransactionNumber($pdo) {
    $date = date('Ymd'); // Format: YYYYMMDD
    $prefix = 'TRX-' . $date . '-';
    
    // Get the last transaction number for today
    try {
        $stmt = $pdo->prepare("SELECT transaction_number FROM transactions WHERE transaction_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $lastNumber = $stmt->fetchColumn();
        
        if ($lastNumber) {
            // Extract the sequence number and increment
            $sequence = (int)substr($lastNumber, -4);
            $newSequence = $sequence + 1;
        } else {
            // First transaction of the day
            $newSequence = 1;
        }
        
        // Format: TRX-YYYYMMDD-0001
        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
        
    } catch (PDOException $e) {
        // Fallback to timestamp-based number
        return $prefix . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

// Handle POST (create/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'] ?? null;
    $order_id = $input['order_id'] ?? null;
    $transaction_date = $input['transaction_date'] ?? null;
    $price = $input['price'] ?? null;
    $payment_method = $input['payment_method'] ?? null;
    $status = trim($input['status'] ?? 'belum lunas');
    
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
    
    // Normalisasi status
    $status = strtolower(trim($status));
    $allowed_statuses = ['belum lunas', 'lunas', 'unknown'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'belum lunas';
    }
    
    try {
        if ($id) {
            // ✅ UPDATE - Tidak perlu generate transaction_number baru
            $stmt = $pdo->prepare("UPDATE transactions SET order_id = ?, transaction_date = ?, price = ?, payment_method = ?, status = ? WHERE id = ?");
            $result = $stmt->execute([$order_id, $transaction_date, $price, $payment_method, $status, $id]);
            
            if ($result) {
                respond(true, [], 'Transaction updated successfully');
            } else {
                respond(false, [], 'Failed to update transaction');
            }
        } else {
            // ✅ INSERT - Generate transaction_number otomatis
            $transaction_number = generateTransactionNumber($pdo);
            
            // Check if transaction_number column exists
            $checkColumn = $pdo->query("SHOW COLUMNS FROM transactions LIKE 'transaction_number'");
            $hasTransactionNumber = $checkColumn->rowCount() > 0;
            
            if ($hasTransactionNumber) {
                // Insert with transaction_number
                $stmt = $pdo->prepare("INSERT INTO transactions (transaction_number, order_id, transaction_date, price, payment_method, status) VALUES (?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([$transaction_number, $order_id, $transaction_date, $price, $payment_method, $status]);
            } else {
                // Insert without transaction_number (backward compatibility)
                $stmt = $pdo->prepare("INSERT INTO transactions (order_id, transaction_date, price, payment_method, status) VALUES (?, ?, ?, ?, ?)");
                $result = $stmt->execute([$order_id, $transaction_date, $price, $payment_method, $status]);
            }
            
            if ($result) {
                respond(true, [], 'Transaction added successfully with number: ' . ($hasTransactionNumber ? $transaction_number : 'auto'));
            } else {
                respond(false, [], 'Failed to add transaction');
            }
        }
    } catch (PDOException $e) {
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