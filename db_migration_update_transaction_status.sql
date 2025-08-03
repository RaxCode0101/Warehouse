-- Migration to update status column in transactions table to use 'belum lunas' and 'lunas' values
ALTER TABLE transactions MODIFY COLUMN status ENUM('belum lunas', 'lunas') NOT NULL DEFAULT 'belum lunas';
