<?php
header('Content-Type: application/json');
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

function respond($success, $data = [], $message = '') {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

// Check admin access for POST, PUT, DELETE operations
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    requireAdmin();
}

switch ($method) {
    case 'GET':
        $search = $_GET['search'] ?? '';
        $sort_by = $_GET['sort_by'] ?? 'id';
        $sort_order = $_GET['sort_order'] ?? 'ASC';

        $allowed_sort_columns = ['id', 'order_id', 'transaction_date', 'amount', 'payment_method', 'status'];
        if (!in_array($sort_by, $allowed_sort_columns)) {
            $sort_by = 'id';
        }
        $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

        try {
            if ($search) {
                $stmt = $pdo->prepare("SELECT * FROM transactions WHERE order_id LIKE ? OR payment_method LIKE ? OR status LIKE ? ORDER BY $sort_by $sort_order");
                $like_search = "%$search%";
                $stmt->execute([$like_search, $like_search, $like_search]);
            } else {
                $stmt = $pdo->query("SELECT * FROM transactions ORDER BY $sort_by $sort_order");
            }
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(true, $items);
        } catch (PDOException $e) {
            respond(false, [], 'Failed to fetch transactions: ' . $e->getMessage());
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            respond(false, [], 'Invalid input');
        }

        $id = $input['id'] ?? null;
        $order_id = intval($input['order_id'] ?? 0);
        $transaction_date = $input['transaction_date'] ?? date('Y-m-d H:i:s');
        $amount = floatval($input['amount'] ?? 0);
        $payment_method = trim($input['payment_method'] ?? 'Cash');
        $status = $input['status'] ?? 'Pending';

        if (!$order_id || !$transaction_date || !$amount) {
            respond(false, [], 'Order ID, transaction date, and amount are required');
        }

        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE transactions SET order_id = ?, transaction_date = ?, amount = ?, payment_method = ?, status = ? WHERE id = ?");
                $stmt->execute([$order_id, $transaction_date, $amount, $payment_method, $status, $id]);
                respond(true, [], 'Transaction updated successfully');
            } else {
                $stmt = $pdo->prepare("INSERT INTO transactions (order_id, transaction_date, amount, payment_method, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$order_id, $transaction_date, $amount, $payment_method, $status]);
                respond(true, [], 'Transaction created successfully');
            }
        } catch (PDOException $e) {
            respond(false, [], 'Failed to save transaction: ' . $e->getMessage());
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
