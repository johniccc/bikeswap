-- Migration: Add password reset token columns to users table
-- Run only once. If columns already exist, skip this migration.
ALTER TABLE `users`
  ADD COLUMN `password_reset_token`   VARCHAR(64) NULL DEFAULT NULL AFTER `verification_token`,
  ADD COLUMN `password_reset_expires` DATETIME    NULL DEFAULT NULL AFTER `password_reset_token`;
