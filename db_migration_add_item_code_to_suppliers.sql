-- Migration to add item_code column to suppliers table
ALTER TABLE suppliers
ADD COLUMN item_code VARCHAR(50);
