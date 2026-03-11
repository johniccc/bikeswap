-- BikeSwap Demo Seed Data
-- Run after schema.sql to populate demo accounts
-- All passwords: 1234

SET NAMES utf8mb4;

-- ── USERS ────────────────────────────────────────────────────────

-- 1) Vlastnik (owner) - owns bikes, manages reservations
INSERT INTO `users` (`email`, `password_hash`, `role`, `first_name`, `surname`, `phone`, `is_verified`, `karma_score`) VALUES
('vlastnik@vlastnik.cz', '$2y$12$alpJ5/h1VLPw2QQbp6R4/eVsH9aEIbox/eBuqO/5ZPZFpFIA67H5y', 'user', 'Vlastník', 'Demo', '+420111111111', 1, 15);

-- 2) Vypujcitel (borrower/finder) - borrows bikes, reports found bikes
INSERT INTO `users` (`email`, `password_hash`, `role`, `first_name`, `surname`, `phone`, `is_verified`, `karma_score`) VALUES
('vypujcitel@vypujcitel.cz', '$2y$12$alpJ5/h1VLPw2QQbp6R4/eVsH9aEIbox/eBuqO/5ZPZFpFIA67H5y', 'user', 'Vypůjčitel', 'Demo', '+420222222222', 1, 5);

-- 3) Policie (police) - police role, can view admin panel + found report conversations
INSERT INTO `users` (`email`, `password_hash`, `role`, `first_name`, `surname`, `is_verified`) VALUES
('policie@policie.cz', '$2y$12$alpJ5/h1VLPw2QQbp6R4/eVsH9aEIbox/eBuqO/5ZPZFpFIA67H5y', 'police', 'Policie', 'ČR', 1);

-- 4) Admin - full admin access
INSERT INTO `users` (`email`, `password_hash`, `role`, `first_name`, `surname`, `is_verified`) VALUES
('admin@admin.cz', '$2y$12$alpJ5/h1VLPw2QQbp6R4/eVsH9aEIbox/eBuqO/5ZPZFpFIA67H5y', 'admin', 'Administrátor', '', 1);

-- ── BIKES (owned by vlastnik, id=1) ─────────────────────────────

INSERT INTO `bikes` (`owner_id`, `qr_hash`, `brand`, `model`, `color`, `frame_number`, `year_of_manufacture`, `description`, `status`, `is_shared`) VALUES
(1, 'a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8', 'Trek', 'Marlin 7', 'Modrá', 'WTU123456789', 2023, 'Horské kolo, hydraulické brzdy, 29" kola.', 'active', 1),
(1, 'b2c3d4e5f6a7b8c9b2c3d4e5f6a7b8c9', 'Giant', 'Escape 3', 'Černá', 'GNT987654321', 2022, 'Městské kolo pro dojíždění, lehký rám.', 'active', 0),
(1, 'c3d4e5f6a7b8c9d0c3d4e5f6a7b8c9d0', 'Specialized', 'Allez', 'Červená', 'SPC456789012', 2024, 'Silniční kolo, karbonová vidlice, Shimano Claris.', 'stolen', 0);

-- ── THEFT REPORT (for the stolen Specialized) ───────────────────

INSERT INTO `theft_reports` (`bike_id`, `reported_by`, `theft_date`, `theft_location_text`, `description`, `police_case_number`, `status`) VALUES
(3, 1, '2026-02-20', 'Praha 2, Náměstí Míru', 'Kolo bylo uzamčeno u stojanu, zámek přeříznut.', 'PČR-2026-00123', 'open');

-- ── RESERVATION (vypujcitel borrows Trek from vlastnik) ─────────

INSERT INTO `reservations` (`bike_id`, `borrower_id`, `owner_id`, `conversation_token`, `date_from`, `date_to`, `message`, `status`) VALUES
(1, 2, 1, 'res_token_demo_a1b2c3d4e5f6a7b8c9d0', '2026-03-10', '2026-03-15', 'Dobrý den, rád bych si půjčil kolo na výlet do Krkonoš.', 'approved');

-- ── RESERVATION MESSAGES ─────────────────────────────────────────

INSERT INTO `reservation_messages` (`reservation_id`, `sender_type`, `sender_user_id`, `message`) VALUES
(1, 'borrower', 2, 'Dobrý den, rád bych si půjčil kolo na výlet do Krkonoš.'),
(1, 'owner', 1, 'Dobrý den, kolo je k dispozici. Kde si ho chcete vyzvednout?'),
(1, 'borrower', 2, 'Ideálně někde v centru Prahy, děkuji!'),
(1, 'system', NULL, 'Rezervace byla schválena vlastníkem.');

-- ── FOUND REPORT (vypujcitel found the stolen Specialized) ──────

INSERT INTO `found_reports` (`bike_id`, `qr_hash_scanned`, `reported_by`, `reporter_email`, `conversation_token`, `found_date`, `found_location_text`, `description`, `status`) VALUES
(3, 'c3d4e5f6a7b8c9d0c3d4e5f6a7b8c9d0', 2, 'vypujcitel@vypujcitel.cz', 'found_token_demo_x1y2z3w4v5u6t7s8', '2026-03-01', 'Praha 3, park Parukářka', 'Kolo stálo opřené o strom, bez zámku.', 'contacted');

-- ── FOUND REPORT MESSAGES ────────────────────────────────────────

INSERT INTO `found_report_messages` (`found_report_id`, `sender_type`, `sender_user_id`, `message`) VALUES
(1, 'system', NULL, 'Nález byl nahlášen. Můžete komunikovat s nálezcem.'),
(1, 'finder', 2, 'Dobrý den, našel jsem vaše kolo v parku Parukářka. Je v pořádku.'),
(1, 'owner', 1, 'Děkuji mockrát! Kde se můžeme sejít pro předání?'),
(1, 'finder', 2, 'Mohu být dnes odpoledne u metra Jiřího z Poděbrad.');

-- ── NOTIFICATIONS ────────────────────────────────────────────────

INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `link`, `is_read`) VALUES
(1, 'found', 'Kolo nalezeno!', 'Někdo našel vaše kolo Specialized Allez.', '/found/1/conversation', 0),
(1, 'reservation', 'Nová rezervace', 'Vypůjčitel Demo si chce půjčit vaše kolo Trek Marlin 7.', '/reservation/1', 1),
(2, 'reservation', 'Rezervace schválena', 'Vaše rezervace kola Trek Marlin 7 byla schválena.', '/reservation/1', 0);

-- ── USER PREFERENCES ─────────────────────────────────────────────

INSERT INTO `user_preferences` (`user_id`, `email_on_found_report`, `email_on_reservation`, `email_on_message`, `email_on_status_change`) VALUES
(1, 1, 1, 0, 1),
(2, 1, 1, 0, 1);
