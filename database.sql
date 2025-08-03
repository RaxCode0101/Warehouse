-- Create database
CREATE DATABASE IF NOT EXISTS warehouse_db;

-- Use the database
USE warehouse_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create inventory table
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) UNIQUE NOT NULL,
    item_image BLOB,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(100) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    status ENUM('In Stock', 'Low Stock', 'Out of Stock') NOT NULL DEFAULT 'In Stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create suppliers table
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_name VARCHAR(100),
    contact_email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    status ENUM('Pending', 'Processing', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    total_amount INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Create transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_number VARCHAR(50) UNIQUE NOT NULL,
    order_id INT NOT NULL,
    transaction_date DATE NOT NULL,
    amount INT NOT NULL,
    payment_method VARCHAR(50),
    status ENUM('Pending', 'Completed', 'Failed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);