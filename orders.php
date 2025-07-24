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

        $allowed_sort_columns = ['id', 'order_number', 'supplier_id', 'order_date', 'status', 'total_amount'];
        if (!in_array($sort_by, $allowed_sort_columns)) {
            $sort_by = 'id';
        }
        $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

        try {
            if ($search) {
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number LIKE ? OR status LIKE ? ORDER BY $sort_by $sort_order");
                $like_search = "%$search%";
                $stmt->execute([$like_search, $like_search]);
            } else {
                $stmt = $pdo->query("SELECT * FROM orders ORDER BY $sort_by $sort_order");
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
        $order_number = trim($input['order_number'] ?? '');
        $supplier_id = intval($input['supplier_id'] ?? 0);
        $order_date = $input['order_date'] ?? '';
        $status = $input['status'] ?? 'Pending';
        $total_amount = floatval($input['total_amount'] ?? 0);

        if (!$order_number || !$supplier_id || !$order_date) {
            respond(false, [], 'Order number, supplier, and order date are required');
        }

        if (!in_array($status, ['Pending', 'Processing', 'Completed', 'Cancelled'])) {
            $status = 'Pending';
        }

        try {
            // Validate supplier_id exists
            $supplierCheck = $pdo->prepare("SELECT id FROM suppliers WHERE id = ?");
            $supplierCheck->execute([$supplier_id]);
            if ($supplierCheck->rowCount() === 0) {
                respond(false, [], 'Invalid supplier_id: Supplier does not exist');
            }

            if ($id) {
                $stmt = $pdo->prepare("UPDATE orders SET order_number = ?, supplier_id = ?, order_date = ?, status = ?, total_amount = ? WHERE id = ?");
                $stmt->execute([$order_number, $supplier_id, $order_date, $status, $total_amount, $id]);
                respond(true, [], 'Order updated successfully');
            } else {
                $stmt = $pdo->prepare("INSERT INTO orders (order_number, supplier_id, order_date, status, total_amount) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$order_number, $supplier_id, $order_date, $status, $total_amount]);
                respond(true, [], 'Order created successfully');
            }
        } catch (PDOException $e) {
            respond(false, [], 'Failed to save order: ' . $e->getMessage());
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
