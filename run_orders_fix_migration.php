<?php
// Script to run the orders table structure fix migration
require_once 'config.php';

echo "Starting orders table structure fix migration...\n";

try {
    // Read and execute the migration SQL
    $migrationSQL = file_get_contents('db_migration_fix_orders_table_structure.sql');
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $migrationSQL)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $pdo->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "✅ Migration completed successfully!\n";
    echo "✅ Orders table structure has been updated to match PHP code expectations.\n";
    
    // Verify the table structure
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Updated orders table structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']}: {$column['Type']} ({$column['Null']}) {$column['Default']}\n";
    }
    
    // Test with sample data
    $testData = [
        ['item_code' => 'TEST001', 'buyers' => 'Test Buyer', 'order_date' => '2024-01-20', 'status' => 'Pending', 'total_amount' => 1000]
    ];
    
    foreach ($testData as $data) {
        $stmt = $pdo->prepare("INSERT INTO orders (item_code, buyers, order_date, status, total_amount) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['item_code'], $data['buyers'], $data['order_date'], $data['status'], $data['total_amount']]);
    }
    
    echo "\n✅ Sample test data inserted successfully!\n";
    echo "✅ The 'Failed to fetch orders: Action is required' error should now be resolved.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Please check your database connection and permissions.\n";
}
?>
