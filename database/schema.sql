-- BikeSwap Database Schema
-- MySQL / MariaDB
-- UTF-8 (utf8mb4) for full Unicode support

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── USERS ──────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `users` (
    `id`                 INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `email`              VARCHAR(255)    NOT NULL,
    `password_hash`      VARCHAR(255)    NOT NULL,
    `role`               ENUM('user', 'police', 'admin') NOT NULL DEFAULT 'user',
    `name`               VARCHAR(100)    NOT NULL,
    `phone`              VARCHAR(20)     NULL DEFAULT NULL,
    `address`            VARCHAR(255)    NULL DEFAULT NULL,
    `is_verified`        TINYINT(1)      NOT NULL DEFAULT 0,
    `verification_token` VARCHAR(64)     NULL DEFAULT NULL,
    `created_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login_at`      DATETIME        NULL DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;


-- ── BIKES ──────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `bikes` (
    `id`                   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `owner_id`             INT UNSIGNED    NOT NULL,
    `qr_hash`              VARCHAR(64)     NOT NULL,
    `brand`                VARCHAR(100)    NOT NULL,
    `model`                VARCHAR(100)    NULL DEFAULT NULL,
    `color`                VARCHAR(50)     NOT NULL,
    `frame_number`         VARCHAR(100)    NULL DEFAULT NULL,
    `year_of_manufacture`  SMALLINT UNSIGNED NULL DEFAULT NULL,
    `description`          TEXT            NULL DEFAULT NULL,
    `status`               ENUM('active', 'stolen', 'found', 'recovered') NOT NULL DEFAULT 'active',
    `is_shared`            TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_bikes_qr_hash` (`qr_hash`),
    KEY `idx_bikes_owner` (`owner_id`),
    KEY `idx_bikes_status` (`status`),
    KEY `idx_bikes_shared` (`is_shared`),
    CONSTRAINT `fk_bikes_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;


-- ── BIKE PHOTOS ────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `bike_photos` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `bike_id`       INT UNSIGNED    NOT NULL,
    `file_path`     VARCHAR(255)    NOT NULL,
    `is_primary`    TINYINT(1)      NOT NULL DEFAULT 0,
    `uploaded_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `uploaded_by`   INT UNSIGNED    NOT NULL,

    PRIMARY KEY (`id`),
    KEY `idx_photos_bike` (`bike_id`),
    CONSTRAINT `fk_photos_bike` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_photos_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;


-- ── THEFT REPORTS ──────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `theft_reports` (
    `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `bike_id`             INT UNSIGNED    NOT NULL,
    `reported_by`         INT UNSIGNED    NULL DEFAULT NULL,
    `reporter_email`      VARCHAR(255)    NULL DEFAULT NULL,
    `reporter_ip`         VARCHAR(45)     NULL DEFAULT NULL,
    `theft_date`          DATE            NULL DEFAULT NULL,
    `theft_location_text` VARCHAR(255)    NULL DEFAULT NULL,
    `theft_location_lat`  DECIMAL(10, 7)  NULL DEFAULT NULL,
    `theft_location_lng`  DECIMAL(10, 7)  NULL DEFAULT NULL,
    `description`         TEXT            NULL DEFAULT NULL,
    `police_case_number`  VARCHAR(50)     NULL DEFAULT NULL,
    `status`              ENUM('open', 'investigating', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_theft_bike` (`bike_id`),
    KEY `idx_theft_status` (`status`),
    CONSTRAINT `fk_theft_bike` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_theft_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;


-- ── FOUND REPORTS ──────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `found_reports` (
    `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `bike_id`             INT UNSIGNED    NULL DEFAULT NULL,
    `qr_hash_scanned`     VARCHAR(64)     NULL DEFAULT NULL,
    `reported_by`         INT UNSIGNED    NULL DEFAULT NULL,
    `reporter_email`      VARCHAR(255)    NULL DEFAULT NULL,
    `reporter_phone`      VARCHAR(20)     NULL DEFAULT NULL,
    `reporter_ip`         VARCHAR(45)     NULL DEFAULT NULL,
    `found_date`          DATE            NULL DEFAULT NULL,
    `found_location_text` VARCHAR(255)    NULL DEFAULT NULL,
    `found_location_lat`  DECIMAL(10, 7)  NULL DEFAULT NULL,
    `found_location_lng`  DECIMAL(10, 7)  NULL DEFAULT NULL,
    `description`         TEXT            NULL DEFAULT NULL,
    `status`              ENUM('pending', 'verified', 'contacted', 'resolved') NOT NULL DEFAULT 'pending',
    `verified_by`         INT UNSIGNED    NULL DEFAULT NULL,
    `verified_at`         DATETIME        NULL DEFAULT NULL,
    `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_found_bike` (`bike_id`),
    KEY `idx_found_status` (`status`),
    KEY `idx_found_qr` (`qr_hash_scanned`),
    CONSTRAINT `fk_found_bike` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_found_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_found_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;


-- ── FOUND REPORT PHOTOS ────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `found_report_photos` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `found_report_id`  INT UNSIGNED    NOT NULL,
    `file_path`        VARCHAR(255)    NOT NULL,
    `uploaded_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_found_photos_report` (`found_report_id`),
    CONSTRAINT `fk_found_photos_report` FOREIGN KEY (`found_report_id`) REFERENCES `found_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;


-- ── RESERVATIONS ───────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `reservations` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `bike_id`         INT UNSIGNED    NOT NULL,
    `borrower_id`     INT UNSIGNED    NOT NULL,
    `start_datetime`  DATETIME        NOT NULL,
    `end_datetime`    DATETIME        NOT NULL,
    `message`         TEXT            NULL DEFAULT NULL,
    `status`          ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    `owner_response`  TEXT            NULL DEFAULT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_reservations_bike` (`bike_id`),
    KEY `idx_reservations_borrower` (`borrower_id`),
    KEY `idx_reservations_status` (`status`),
    KEY `idx_reservations_dates` (`bike_id`, `start_datetime`, `end_datetime`),
    CONSTRAINT `fk_reservations_bike` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reservations_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;


-- ── ACTIVITY LOG ───────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `activity_log` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED    NULL DEFAULT NULL,
    `action`      VARCHAR(50)     NOT NULL,
    `entity_type` VARCHAR(50)     NOT NULL,
    `entity_id`   INT UNSIGNED    NULL DEFAULT NULL,
    `old_value`   JSON            NULL DEFAULT NULL,
    `new_value`   JSON            NULL DEFAULT NULL,
    `ip_address`  VARCHAR(45)     NULL DEFAULT NULL,
    `user_agent`  VARCHAR(500)    NULL DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_log_user` (`user_id`),
    KEY `idx_log_entity` (`entity_type`, `entity_id`),
    KEY `idx_log_action` (`action`),
    CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;


-- ── RATE LIMITS ────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `ip_address`       VARCHAR(45)     NOT NULL,
    `action_type`      VARCHAR(50)     NOT NULL,
    `attempts_count`   INT UNSIGNED    NOT NULL DEFAULT 1,
    `first_attempt_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_attempt_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_rate_ip_action` (`ip_address`, `action_type`),
    KEY `idx_rate_first_attempt` (`first_attempt_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;


SET FOREIGN_KEY_CHECKS = 1;