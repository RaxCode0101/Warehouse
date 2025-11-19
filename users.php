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
        session_start();
        $userRole = $_SESSION['role'] ?? 'user';
        $userId = $_SESSION['user_id'] ?? null;

        $search = $_GET['search'] ?? '';
        $sort_by = $_GET['sort_by'] ?? 'id';
        $sort_order = $_GET['sort_order'] ?? 'ASC';

        $allowed_sort_columns = ['id', 'username', 'full_name', 'created_at', 'role'];
        if (!in_array($sort_by, $allowed_sort_columns)) {
            $sort_by = 'id';
        }
        $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

        try {
            // Return all users regardless of role
            if ($search) {
                $stmt = $pdo->prepare("SELECT id, username, full_name, role, created_at FROM users WHERE username LIKE ? OR full_name LIKE ? ORDER BY $sort_by $sort_order");
                $like_search = "%$search%";
                $stmt->execute([$like_search, $like_search]);
            } else {
                $stmt = $pdo->query("SELECT id, username, full_name, role, created_at FROM users ORDER BY $sort_by $sort_order");
            }
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(true, $items);
        } catch (PDOException $e) {
            respond(false, [], 'Failed to fetch users: ' . $e->getMessage());
        }
        break;

    case 'POST':
        session_start();
        if (!isset($_SESSION['role'])) {
            respond(false, [], 'Unauthorized: No role set in session');
        }
        
        // ✅ PERBAIKAN: Normalisasi role dan full_name
        $userRole = trim(strtolower($_SESSION['role'] ?? ''));
        $userFullName = trim(strtolower($_SESSION['full_name'] ?? ''));
        
        // Allow admin role OR user with full name 'bryan phillip sumarauw'
        if ($userRole !== 'admin' && $userFullName !== 'bryan phillip sumarauw') {
            respond(false, [], 'Unauthorized: Admins only');
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
                // Force role to admin for user with id 1 (Bryan)
                $bryanUserId = 1;
                if ($id == $bryanUserId) {
                    $role = 'admin';
                } else {
                    // If role is not provided in input, fetch current role from DB to preserve it
                    if (!isset($input['role']) || empty($input['role'])) {
                        $stmtRole = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                        $stmtRole->execute([$id]);
                        $existingRole = $stmtRole->fetchColumn();
                        if ($existingRole) {
                            $role = $existingRole;
                        }
                    }
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
        session_start();
        
        // ✅ PERBAIKAN: Sama seperti POST, izinkan admin ATAU Bryan
        if (!isset($_SESSION['role'])) {
            respond(false, [], 'Unauthorized: No role set in session');
        }
        
        $userRole = trim(strtolower($_SESSION['role'] ?? ''));
        $userFullName = trim(strtolower($_SESSION['full_name'] ?? ''));
        
        // Allow admin role OR user with full name 'bryan phillip sumarauw'
        if ($userRole !== 'admin' && $userFullName !== 'bryan phillip sumarauw') {
            respond(false, [], 'Unauthorized: Admins only');
        }

        parse_str(file_get_contents("php://input"), $delete_vars);
        $id = $delete_vars['id'] ?? null;
        
        if (!$id) {
            respond(false, [], 'ID is required for deletion');
        }
        
        // ✅ PERBAIKAN: Cegah hapus user Bryan (id = 1)
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