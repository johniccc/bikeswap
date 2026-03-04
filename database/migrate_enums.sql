-- Migration: Add missing ENUM values
-- Run this on existing databases to fix 500 errors from status truncation

-- Add 'closed' to found_reports.status
ALTER TABLE `found_reports`
    MODIFY `status` ENUM('pending', 'verified', 'contacted', 'resolved', 'closed') NOT NULL DEFAULT 'pending';

-- Add 'disputed' to reservations.status
ALTER TABLE `reservations`
    MODIFY `status` ENUM('pending','approved','rejected','active','completed','cancelled','not_returned','disputed') NOT NULL DEFAULT 'pending';
