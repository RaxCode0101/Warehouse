-- Migration to update user roles for bryan and alvian

UPDATE users SET role = 'admin' WHERE username = 'bryan';
UPDATE users SET role = 'user' WHERE username = 'alvian';
