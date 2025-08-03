-- Migration to drop foreign key constraint and then drop buyer_id column from orders table

ALTER TABLE orders DROP FOREIGN KEY orders_ibfk_1;

ALTER TABLE orders DROP COLUMN buyer_id;
