<?php
require 'config.php';

try {
    // Read the migration file
    $sql = file_get_contents('db_migration_add_profile_picture.sql');
    
    // Execute the migration
    $pdo->exec($sql);
    
    echo "Migration executed successfully.\n";
    
    // Check the table structure
    $stmt = $pdo->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Users table structure:\n";
    foreach ($columns as $column) {
        echo $column['Field'] . " " . $column['Type'] . " " . $column['Null'] . " " . $column['Key'] . " " . $column['Default'] . " " . $column['Extra'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
