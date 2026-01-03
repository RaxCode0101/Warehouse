<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Session-based data storage for demo purposes
session_start();

// Initialize suppliers data if not exists
if (!isset($_SESSION['suppliers'])) {
    $_SESSION['suppliers'] = [
        1 => [
            'id' => 1,
            'name' => 'PT. Sukses Jaya Abadi',
            'phone' => '+62 21 5551234',
            'address' => 'Jl. Sudirman No. 123, Jakarta',
            'item_code' => 'SUP001'
        ],
        2 => [
            'id' => 2,
            'name' => 'CV. Makmur Sejahtera',
            'phone' => '+62 22 7788990',
            'address' => 'Jl. Asia Afrika No. 456, Bandung',
            'item_code' => 'SUP002'
        ],
        3 => [
            'id' => 3,
            'name' => 'PT. Sentosa Abadi',
            'phone' => '+62 31 8877665',
            'address' => 'Jl. Raya Gubeng No. 789, Surabaya',
            'item_code' => 'SUP003'
        ],
        4 => [
            'id' => 4,
            'name' => 'UD. Jaya Makmur',
            'phone' => '+62 274 555444',
            'address' => 'Jl. Malioboro No. 321, Yogyakarta',
            'item_code' => 'SUP004'
        ],
        5 => [
            'id' => 5,
            'name' => 'PT. Inti Sejahtera',
            'phone' => '+62 61 666555',
            'address' => 'Jl. Sisingamangaraja No. 654, Medan',
            'item_code' => 'SUP005'
        ]
    ];
    $_SESSION['next_id'] = 6;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet();
            break;
        case 'POST':
            handlePost();
            break;
        case 'DELETE':
            handleDelete();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleGet() {
    $search = isset($_GET['search']) ? strtolower($_GET['search']) : '';
    $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'id';
    $sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';
    
    // Validate sort column
    $allowed_columns = ['id', 'name', 'phone', 'address', 'item_code'];
    if (!in_array($sort_by, $allowed_columns)) {
        $sort_by = 'id';
    }
    
    // Validate sort order
    $sort_order = strtoupper($sort_order);
    if (!in_array($sort_order, ['ASC', 'DESC'])) {
        $sort_order = 'ASC';
    }
    
    // Get suppliers from session
    $suppliers = array_values($_SESSION['suppliers']);
    
    // Filter by search
    $filtered_suppliers = $suppliers;
    if (!empty($search)) {
        $filtered_suppliers = array_filter($suppliers, function($supplier) use ($search) {
            return strpos(strtolower($supplier['name']), $search) !== false ||
                   strpos(strtolower($supplier['phone']), $search) !== false ||
                   strpos(strtolower($supplier['address']), $search) !== false ||
                   strpos(strtolower($supplier['item_code']), $search) !== false;
        });
    }
    
    // Sort results
    usort($filtered_suppliers, function($a, $b) use ($sort_by, $sort_order) {
        $val_a = $a[$sort_by];
        $val_b = $b[$sort_by];
        
        if ($sort_order === 'ASC') {
            return $val_a <=> $val_b;
        } else {
            return $val_b <=> $val_a;
        }
    });
    
    echo json_encode([
        'success' => true,
        'data' => array_values($filtered_suppliers),
        'total' => count($filtered_suppliers)
    ]);
}

function handlePost() {
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    $id = isset($data['id']) ? $data['id'] : null;
    $name = isset($data['name']) ? trim($data['name']) : '';
    $phone = isset($data['phone']) ? trim($data['phone']) : '';
    $address = isset($data['address']) ? trim($data['address']) : '';
    $item_code = isset($data['item_code']) ? trim($data['item_code']) : '';
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Supplier name is required']);
        return;
    }
    
    if ($id) {
        // Update existing supplier
        if (isset($_SESSION['suppliers'][$id])) {
            $_SESSION['suppliers'][$id] = [
                'id' => $id,
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
                'item_code' => $item_code
            ];
            echo json_encode(['success' => true, 'message' => 'Supplier updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Supplier not found']);
        }
    } else {
        // Add new supplier
        $new_id = $_SESSION['next_id']++;
        $_SESSION['suppliers'][$new_id] = [
            'id' => $new_id,
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'item_code' => $item_code
        ];
        echo json_encode(['success' => true, 'message' => 'Supplier added successfully']);
    }
}

function handleDelete() {
    // Get ID from GET or POST
    $id = null;
    
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
    } elseif (isset($_POST['id'])) {
        $id = $_POST['id'];
    } else {
        // Try to get from raw input
        $input = file_get_contents('php://input');
        parse_str($input, $data);
        if (isset($data['id'])) {
            $id = $data['id'];
        }
    }
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Supplier ID is required']);
        return;
    }
    
    if (isset($_SESSION['suppliers'][$id])) {
        unset($_SESSION['suppliers'][$id]);
        echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Supplier not found']);
    }
}
?>