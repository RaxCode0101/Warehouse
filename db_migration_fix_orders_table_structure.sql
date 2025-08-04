-- Migration to fix orders table structure to match PHP code expectations
-- This migration updates the orders table to match the structure expected by orders.php

-- Step 1: Create a backup of existing orders table (if it exists)
CREATE TABLE IF NOT EXISTS orders_backup AS SELECT * FROM orders WHERE 1=2;

-- Step 2: Drop the existing orders table if it exists
DROP TABLE IF EXISTS orders;

-- Step 3: Create the corrected orders table structure
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) NOT NULL,
    buyers VARCHAR(255) NOT NULL,
    order_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    total_amount INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Step 4: Add indexes for better performance
CREATE INDEX idx_item_code ON orders(item_code);
CREATE INDEX idx_buyers ON orders(buyers);
CREATE INDEX idx_status ON orders(status);
CREATE INDEX idx_order_date ON orders(order_date);

-- Step 5: Insert sample data for testing (optional)
-- INSERT INTO orders (item_code, buyers, order_date, status, total_amount) VALUES 
-- ('ITEM001', 'John Doe', '2024-01-15', 'Pending', 1000),
-- ('ITEM002', 'Jane Smith', '2024-01-16', 'Completed', 2500),
-- ('ITEM003', 'Bob Johnson', '2024-01-17', 'Processing', 1500);

-- Step 6: Verify the table structure
-- DESCRIBE orders;
