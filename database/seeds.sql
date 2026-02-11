-- BikeSwap Seed Data
-- Run after schema.sql to populate test data

-- All passwords: 1234
INSERT INTO `users` (`email`, `password_hash`, `role`, `name`, `is_verified`) VALUES
('admin@bikeswap.cz', '$2y$10$vRHkN13rSlGtpLLQRfEujubyZrFBQZZfPoEMfPNQYQxJTzbMk/Lxq', 'admin', 'Administrátor', 1);

-- Test user (password: heslo123)
INSERT INTO `users` (`email`, `password_hash`, `role`, `name`, `phone`, `is_verified`) VALUES
('jan@example.com', '$2y$10$5/tR19pN0BgGRFKFF4X9GO6sfqEL2Z7vQvQWuF9JsMVVsWN7plh6S', 'user', 'Jan Novák', '+420 777 123 456', 1);

-- Test police user (password: heslo123)
INSERT INTO `users` (`email`, `password_hash`, `role`, `name`, `is_verified`) VALUES
('policie@example.com', '$2y$10$Gr.uFgFOuZsDRcARASYbbuvSGoHVS6fBCNGdt9yHqUt/wFbuExj/O', 'police', 'Strážník Dvořák', 1);