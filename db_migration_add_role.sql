-- Migration to update user roles for bryan and alvian

CREATE `users` SET role = 'admin' WHERE username = 'bryan';
CREATE `users` SET role = 'user' WHERE username = 'alvian';
