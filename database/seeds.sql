-- BikeSwap Demo Seed Data
-- Run after schema.sql to populate demo accounts
-- All passwords: 1234

SET NAMES utf8mb4;

-- ── USERS ────────────────────────────────────────────────────────

-- 1) Vlastnik (owner) - owns bikes, manages reservations
INSERT INTO `users` (`email`, `password_hash`, `role`, `first_name`, `surname`, `phone`, `address`, `is_verified`, `karma_score`) VALUES
('vlastnik@vlastnik.cz', '$2y$12$alpJ5/h1VLPw2QQbp6R4/eVsH9aEIbox/eBuqO/5ZPZFpFIA67H5y', 'user', 'Vlastník', 'Demo', '+420111111111', 'Pardubice, Zelená 15', 1, 15);

-- 2) Vypujcitel (borrower/finder) - borrows bikes, reports found bikes
INSERT INTO `users` (`email`, `password_hash`, `role`, `first_name`, `surname`, `phone`, `address`, `is_verified`, `karma_score`) VALUES
('vypujcitel@vypujcitel.cz', '$2y$12$alpJ5/h1VLPw2QQbp6R4/eVsH9aEIbox/eBuqO/5ZPZFpFIA67H5y', 'user', 'Vypůjčitel', 'Demo', '+420222222222', 'Pardubice, Hlavní 8', 1, 5);

-- 3) Policie (police) - police role, can view admin panel + found report conversations
INSERT INTO `users` (`email`, `password_hash`, `role`, `first_name`, `surname`, `phone`, `is_verified`) VALUES
('policie@policie.cz', '$2y$12$alpJ5/h1VLPw2QQbp6R4/eVsH9aEIbox/eBuqO/5ZPZFpFIA67H5y', 'police', 'Policie', 'ČR', '+420333333333', 1);

-- 4) Admin - full admin access
INSERT INTO `users` (`email`, `password_hash`, `role`, `first_name`, `surname`, `is_verified`) VALUES
('admin@admin.cz', '$2y$12$alpJ5/h1VLPw2QQbp6R4/eVsH9aEIbox/eBuqO/5ZPZFpFIA67H5y', 'admin', 'Administrátor', '', 1);

-- ── BIKES (owned by vlastnik, id=1) ─────────────────────────────

INSERT INTO `bikes` (`owner_id`, `qr_hash`, `brand`, `model`, `color`, `frame_number`, `year_of_manufacture`, `description`, `status`, `is_shared`, `auto_accept`, `availability_days`) VALUES
(1, 'a1b2c3d4e5f6a7b8a1b2c3d4e5f6a7b8', 'Trek', 'Marlin 7', 'Modrá', 'WTU123456789', 2023, 'Horské kolo, hydraulické brzdy, 29" kola.', 'active', 1, 1, '1,2,3,4,5'),
(1, 'b2c3d4e5f6a7b8c9b2c3d4e5f6a7b8c9', 'Giant', 'Escape 3', 'Černá', 'GNT987654321', 2022, 'Městské kolo pro dojíždění, lehký rám.', 'active', 1, 0, NULL),
(1, 'c3d4e5f6a7b8c9d0c3d4e5f6a7b8c9d0', 'Specialized', 'Allez', 'Červená', 'SPC456789012', 2024, 'Silniční kolo, karbonová vidlice, Shimano Claris.', 'stolen', 0, 0, NULL);

-- ── BIKE EXCLUDED DATES (Trek auto-accept: exclude Easter + vacation) ──

INSERT INTO `bike_excluded_dates` (`bike_id`, `excluded_date`) VALUES
(1, '2026-04-03'),
(1, '2026-04-06'),
(1, '2026-07-01'),
(1, '2026-07-02'),
(1, '2026-07-03');

-- ── THEFT REPORT (for the stolen Specialized) ───────────────────

INSERT INTO `theft_reports` (`bike_id`, `reported_by`, `theft_date`, `theft_location_text`, `description`, `police_case_number`, `status`) VALUES
(3, 1, '2026-02-20', 'Praha 2, Náměstí Míru', 'Kolo bylo uzamčeno u stojanu, zámek přeříznut.', 'PČR-2026-00123', 'open');

-- ── RESERVATIONS ─────────────────────────────────────────────────

-- 1) Completed reservation (vypujcitel borrowed Trek, already returned)
INSERT INTO `reservations` (`bike_id`, `borrower_id`, `owner_id`, `conversation_token`, `date_from`, `date_to`, `message`, `status`, `created_at`) VALUES
(1, 2, 1, 'res_token_demo_completed_00000001', '2026-02-01', '2026-02-05', 'Dobrý den, rád bych si půjčil kolo na víkendový výlet.', 'completed', '2026-01-28 10:00:00');

-- 2) Approved reservation (upcoming, vypujcitel borrows Trek)
INSERT INTO `reservations` (`bike_id`, `borrower_id`, `owner_id`, `conversation_token`, `date_from`, `date_to`, `message`, `status`, `created_at`) VALUES
(1, 2, 1, 'res_token_demo_a1b2c3d4e5f6a7b8c9d0', '2026-03-20', '2026-03-25', 'Dobrý den, rád bych si půjčil kolo na výlet do Krkonoš.', 'approved', '2026-03-10 14:30:00');

-- 3) Pending reservation (vypujcitel wants to borrow Giant)
INSERT INTO `reservations` (`bike_id`, `borrower_id`, `owner_id`, `conversation_token`, `date_from`, `date_to`, `message`, `status`, `created_at`) VALUES
(2, 2, 1, 'res_token_demo_pending_0000000001', '2026-04-10', '2026-04-12', 'Potřeboval bych kolo na dojíždění do práce, je volné?', 'pending', '2026-03-15 09:00:00');

-- ── RESERVATION MESSAGES ─────────────────────────────────────────

-- Messages for reservation 1 (completed)
INSERT INTO `reservation_messages` (`reservation_id`, `sender_type`, `sender_user_id`, `message`, `created_at`) VALUES
(1, 'borrower', 2, 'Dobrý den, rád bych si půjčil kolo na víkendový výlet.', '2026-01-28 10:00:00'),
(1, 'owner', 1, 'Samozřejmě, kolo je k dispozici. Kde si ho chcete vyzvednout?', '2026-01-28 12:15:00'),
(1, 'borrower', 2, 'Můžeme se domluvit u hlavního nádraží v Pardubicích?', '2026-01-28 14:00:00'),
(1, 'system', NULL, 'Rezervace byla schválena vlastníkem.', '2026-01-29 08:00:00'),
(1, 'system', NULL, 'Výpůjčka byla aktivována.', '2026-02-01 09:00:00'),
(1, 'system', NULL, 'Výpůjčka byla úspěšně dokončena.', '2026-02-05 18:00:00');

-- Messages for reservation 2 (approved)
INSERT INTO `reservation_messages` (`reservation_id`, `sender_type`, `sender_user_id`, `message`, `created_at`) VALUES
(2, 'borrower', 2, 'Dobrý den, rád bych si půjčil kolo na výlet do Krkonoš.', '2026-03-10 14:30:00'),
(2, 'owner', 1, 'Dobrý den, kolo je k dispozici. Kde si ho chcete vyzvednout?', '2026-03-10 16:00:00'),
(2, 'borrower', 2, 'Ideálně někde v centru Pardubic, děkuji!', '2026-03-10 17:30:00'),
(2, 'system', NULL, 'Rezervace byla schválena vlastníkem.', '2026-03-11 09:00:00');

-- Messages for reservation 3 (pending)
INSERT INTO `reservation_messages` (`reservation_id`, `sender_type`, `sender_user_id`, `message`, `created_at`) VALUES
(3, 'borrower', 2, 'Potřeboval bych kolo na dojíždění do práce, je volné?', '2026-03-15 09:00:00');

-- ── RESERVATION REVIEWS (for completed reservation) ──────────────

-- Owner reviews borrower
INSERT INTO `reservation_reviews` (`reservation_id`, `reviewer_id`, `reviewed_user_id`, `rating`, `comment`, `is_revealed`, `created_at`) VALUES
(1, 1, 2, 5, 'Kolo vrátil v perfektním stavu, příjemná komunikace.', 1, '2026-02-06 10:00:00');

-- Borrower reviews owner
INSERT INTO `reservation_reviews` (`reservation_id`, `reviewer_id`, `reviewed_user_id`, `rating`, `comment`, `is_revealed`, `created_at`) VALUES
(1, 2, 1, 4, 'Kolo bylo super, jen předání trvalo trochu déle.', 1, '2026-02-06 11:00:00');

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
(1, 'reservation', 'Nová rezervace', 'Vypůjčitel Demo si chce půjčit vaše kolo Trek Marlin 7.', '/reservation/2', 1),
(1, 'reservation', 'Nová rezervace', 'Vypůjčitel Demo si chce půjčit vaše kolo Giant Escape 3.', '/reservation/3', 0),
(2, 'reservation', 'Rezervace schválena', 'Vaše rezervace kola Trek Marlin 7 byla schválena.', '/reservation/2', 0),
(2, 'reservation', 'Výpůjčka dokončena', 'Vaše výpůjčka kola Trek Marlin 7 byla úspěšně dokončena.', '/reservation/1', 1);

-- ── USER PREFERENCES ─────────────────────────────────────────────

INSERT INTO `user_preferences` (`user_id`, `email_on_found_report`, `email_on_reservation`, `email_on_message`, `email_on_status_change`) VALUES
(1, 1, 1, 0, 1),
(2, 1, 1, 0, 1),
(3, 1, 1, 1, 1),
(4, 1, 1, 1, 1);

-- ── BIKE WARNINGS (police warning on an abandoned bike) ──────────

INSERT INTO `bike_warnings` (`bike_id`, `created_by`, `reason`, `deadline`, `location_description`, `status`) VALUES
(2, 3, 'Kolo stojí delší dobu nepřipoutané u stojanů. Prosíme majitele o přesunutí nebo kontaktování správy.', '2026-03-25', 'Pardubice hlavní nádraží', 'active');

-- ── ACTIVITY LOG (sample entries) ────────────────────────────────

INSERT INTO `activity_log` (`user_id`, `action`, `entity_type`, `entity_id`, `new_value`, `ip_address`, `created_at`) VALUES
(1, 'create', 'bike', 1, '{"brand":"Trek","model":"Marlin 7"}', '127.0.0.1', '2026-01-15 10:00:00'),
(1, 'create', 'bike', 2, '{"brand":"Giant","model":"Escape 3"}', '127.0.0.1', '2026-01-15 10:05:00'),
(1, 'create', 'bike', 3, '{"brand":"Specialized","model":"Allez"}', '127.0.0.1', '2026-01-15 10:10:00'),
(1, 'report_theft', 'theft_report', 1, '{"bike_id":3}', '127.0.0.1', '2026-02-20 14:00:00'),
(2, 'report_found', 'found_report', 1, '{"bike_id":3}', '127.0.0.1', '2026-03-01 11:00:00'),
(3, 'create_warning', 'bike_warning', 1, '{"bike_id":2}', '127.0.0.1', '2026-03-12 09:00:00'),
(4, 'login', 'user', 4, NULL, '127.0.0.1', '2026-03-14 08:00:00');
