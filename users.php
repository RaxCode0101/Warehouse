<?php
header('Content-Type: application/json');
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

function respond($success, $data = [], $message = '') {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

// ✅ Helper function untuk cek apakah user adalah admin atau Bryan
function isAdminOrBryan() {
    session_start();
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $userRole = trim(strtolower($_SESSION['role'] ?? ''));
    $userFullName = trim(strtolower($_SESSION['full_name'] ?? ''));
    
    return ($userRole === 'admin' || $userFullName === 'bryan phillip sumarauw');
}

switch ($method) {
    case 'GET':
        session_start();
        
        // ✅ SEMUA USER BISA MELIHAT (READ)
        $search = $_GET['search'] ?? '';
        $sort_by = $_GET['sort_by'] ?? 'id';
        $sort_order = $_GET['sort_order'] ?? 'ASC';

        $allowed_sort_columns = ['id', 'username', 'full_name', 'created_at', 'role'];
        if (!in_array($sort_by, $allowed_sort_columns)) {
            $sort_by = 'id';
        }
        $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

        try {
            if ($search) {
                $stmt = $pdo->prepare("SELECT id, username, full_name, role, created_at FROM users WHERE username LIKE ? OR full_name LIKE ? ORDER BY $sort_by $sort_order");
                $like_search = "%$search%";
                $stmt->execute([$like_search, $like_search]);
            } else {
                $stmt = $pdo->query("SELECT id, username, full_name, role, created_at FROM users ORDER BY $sort_by $sort_order");
            }
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ✅ Kirim juga info permission user
            $canEdit = isAdminOrBryan();
            
            respond(true, [
                'users' => $items,
                'permissions' => [
                    'can_edit' => $canEdit,
                    'can_delete' => $canEdit,
                    'can_add' => $canEdit
                ]
            ]);
        } catch (PDOException $e) {
            respond(false, [], 'Failed to fetch users: ' . $e->getMessage());
        }
        break;

    case 'POST':
        // ✅ HANYA ADMIN/BRYAN YANG BISA EDIT/ADD (CREATE/UPDATE)
        if (!isAdminOrBryan()) {
            respond(false, [], 'Unauthorized: Only admins can modify user data');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            respond(false, [], 'Invalid input');
        }

        $id = $input['id'] ?? null;
        $username = trim($input['username'] ?? '');
        $full_name = trim($input['full_name'] ?? '');
        $password = $input['password'] ?? null;
        $role = $input['role'] ?? 'user';

        if (!$username || !$full_name) {
            respond(false, [], 'Username and full name are required');
        }

        if (!in_array($role, ['admin', 'user'])) {
            respond(false, [], 'Invalid role');
        }

        try {
            if ($id) {
                // Update existing user
                $bryanUserId = 1;
                if ($id == $bryanUserId) {
                    $role = 'admin'; // Force Bryan to always be admin
                }
                
                if ($password) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, password = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $full_name, $hashedPassword, $role, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $full_name, $role, $id]);
                }
                respond(true, [], 'User updated successfully');
            } else {
                // Create new user
                if (!$password) {
                    respond(false, [], 'Password is required for new user');
                }
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, full_name, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $full_name, $hashedPassword, $role]);
                respond(true, [], 'User created successfully');
            }
        } catch (PDOException $e) {
            respond(false, [], 'Failed to save user: ' . $e->getMessage());
        }
        break;

    case 'DELETE':
        // ✅ HANYA ADMIN/BRYAN YANG BISA DELETE
        if (!isAdminOrBryan()) {
            respond(false, [], 'Unauthorized: Only admins can delete users');
        }

        parse_str(file_get_contents("php://input"), $delete_vars);
        $id = $delete_vars['id'] ?? null;
        
        if (!$id) {
            respond(false, [], 'ID is required for deletion');
        }
        
        // Cegah hapus user Bryan (id = 1)
        $bryanUserId = 1;
        if ($id == $bryanUserId) {
            respond(false, [], 'Cannot delete the primary admin account');
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            respond(true, [], 'User deleted successfully');
        } catch (PDOException $e) {
            respond(false, [], 'Failed to delete user: ' . $e->getMessage());
        }
        break;

    default:
        respond(false, [], 'Unsupported HTTP method');
}