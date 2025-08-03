-- Migration to remove transaction_number column from transactions table
ALTER TABLE transactions DROP COLUMN transaction_number;
