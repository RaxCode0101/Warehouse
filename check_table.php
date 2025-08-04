<?php
require 'config.php';

try {
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
