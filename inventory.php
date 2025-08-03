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
        // Handle search and sorting
        $search = $_GET['search'] ?? '';
        $sort_by = $_GET['sort_by'] ?? 'id';
        $sort_order = $_GET['sort_order'] ?? 'ASC';

        $allowed_sort_columns = ['id', 'item_code', 'name', 'category', 'stock', 'status'];
        if (!in_array($sort_by, $allowed_sort_columns)) {
            $sort_by = 'id';
        }
        $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

        try {
            if ($search) {
                $stmt = $pdo->prepare("SELECT * FROM inventory WHERE item_code LIKE ? OR name LIKE ? OR category LIKE ? ORDER BY $sort_by $sort_order");
                $like_search = "%$search%";
                $stmt->execute([$like_search, $like_search, $like_search]);
            } else {
                $stmt = $pdo->query("SELECT * FROM inventory ORDER BY $sort_by $sort_order");
            }
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(true, $items);
        } catch (PDOException $e) {
            respond(false, [], 'Failed to fetch inventory: ' . $e->getMessage());
        }
        break;

    case 'POST':
        // Create or Update
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            respond(false, [], 'Invalid input');
        }

        $id = $input['id'] ?? null;
        $item_code = trim($input['item_code'] ?? '');
        $name = trim($input['name'] ?? '');
        $category = trim($input['category'] ?? '');
        $stock = intval($input['stock'] ?? 0);
        $status = $input['status'] ?? 'In Stock';
        $image_path = $input['image_path'] ?? null;

        if (!$item_code || !$name || !$category) {
            respond(false, [], 'Item code, name, and category are required');
        }

        if (!in_array($status, ['In Stock', 'Low Stock', 'Out of Stock'])) {
            $status = 'In Stock';
        }

        try {
            if ($id) {
                // Update
                $stmt = $pdo->prepare("UPDATE inventory SET item_code = ?, name = ?, category = ?, stock = ?, status = ?, image_path = ? WHERE id = ?");
                $stmt->execute([$item_code, $name, $category, $stock, $status, $image_path, $id]);
                respond(true, [], 'Inventory item updated successfully');
            } else {
                // Create
                $stmt = $pdo->prepare("INSERT INTO inventory (item_code, name, category, stock, status, image_path) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$item_code, $name, $category, $stock, $status, $image_path]);
                respond(true, [], 'Inventory item created successfully');
            }
        } catch (PDOException $e) {
            respond(false, [], 'Failed to save inventory item: ' . $e->getMessage());
        }
        break;

    case 'DELETE':
        // Delete
        parse_str(file_get_contents("php://input"), $delete_vars);
        $id = $delete_vars['id'] ?? null;
        if (!$id) {
            respond(false, [], 'ID is required for deletion');
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
            $stmt->execute([$id]);
            respond(true, [], 'Inventory item deleted successfully');
        } catch (PDOException $e) {
            respond(false, [], 'Failed to delete inventory item: ' . $e->getMessage());
        }
        break;

    default:
        respond(false, [], 'Unsupported HTTP method');
}
