-- Migration: Add password reset token columns to users table
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `password_reset_token`   VARCHAR(64) NULL DEFAULT NULL AFTER `verification_token`,
  ADD COLUMN IF NOT EXISTS `password_reset_expires` DATETIME    NULL DEFAULT NULL AFTER `password_reset_token`;
