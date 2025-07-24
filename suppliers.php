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

$allowed_sort_columns = ['id', 'name', 'contact_name', 'contact_email', 'phone', 'address', 'item_code'];
        if (!in_array($sort_by, $allowed_sort_columns)) {
            $sort_by = 'id';
        }
        $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

        try {
if ($search) {
    $stmt = $pdo->prepare("SELECT suppliers.*, suppliers.item_code AS supplier_item_code, inventory.item_code AS item_code_display FROM suppliers LEFT JOIN inventory ON suppliers.item_code = inventory.item_code WHERE suppliers.name LIKE ? OR suppliers.contact_name LIKE ? OR suppliers.contact_email LIKE ? OR suppliers.phone LIKE ? OR suppliers.address LIKE ? ORDER BY $sort_by $sort_order");
    $like_search = "%$search%";
    $stmt->execute([$like_search, $like_search, $like_search, $like_search, $like_search]);
} else {
    $stmt = $pdo->query("SELECT suppliers.*, suppliers.item_code AS supplier_item_code, inventory.item_code AS item_code_display FROM suppliers LEFT JOIN inventory ON suppliers.item_code = inventory.item_code ORDER BY $sort_by $sort_order");
}
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(true, $items);
        } catch (PDOException $e) {
            respond(false, [], 'Failed to fetch suppliers: ' . $e->getMessage());
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            respond(false, [], 'Invalid input');
        }

$id = $input['id'] ?? null;
$name = trim($input['name'] ?? '');
$contact_name = trim($input['contact_name'] ?? '');
$contact_email = trim($input['contact_email'] ?? '');
$phone = trim($input['phone'] ?? '');
$address = trim($input['address'] ?? '');
$item_code = trim($input['item_code'] ?? '');

if (!$name) {
    respond(false, [], 'Name is required');
}

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, contact_name = ?, contact_email = ?, phone = ?, address = ?, item_code = ? WHERE id = ?");
        $stmt->execute([$name, $contact_name, $contact_email, $phone, $address, $item_code, $id]);
        respond(true, [], 'Supplier updated successfully');
    } else {
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_name, contact_email, phone, address, item_code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $contact_name, $contact_email, $phone, $address, $item_code]);
        respond(true, [], 'Supplier created successfully');
    }
} catch (PDOException $e) {
    respond(false, [], 'Failed to save supplier: ' . $e->getMessage());
}
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $delete_vars);
        $id = $delete_vars['id'] ?? null;
        if (!$id) {
            respond(false, [], 'ID is required for deletion');
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            respond(true, [], 'Supplier deleted successfully');
        } catch (PDOException $e) {
            respond(false, [], 'Failed to delete supplier: ' . $e->getMessage());
        }
        break;

    default:
        respond(false, [], 'Unsupported HTTP method');
}
