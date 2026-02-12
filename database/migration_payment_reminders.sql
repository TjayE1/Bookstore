-- Payment Reminder Migration
-- Adds reminder tracking fields to orders table

-- Add payment_reminder_count if missing
SET @has_payment_reminder_count := (
	SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'orders'
	  AND COLUMN_NAME = 'payment_reminder_count'
);

SET @add_payment_reminder_count := IF(
	@has_payment_reminder_count = 0,
	'ALTER TABLE orders ADD COLUMN payment_reminder_count INT DEFAULT 0',
	'SELECT 1'
);

PREPARE stmt FROM @add_payment_reminder_count;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add payment_reminder_last_sent_at if missing
SET @has_payment_reminder_last_sent_at := (
	SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'orders'
	  AND COLUMN_NAME = 'payment_reminder_last_sent_at'
);

SET @add_payment_reminder_last_sent_at := IF(
	@has_payment_reminder_last_sent_at = 0,
	'ALTER TABLE orders ADD COLUMN payment_reminder_last_sent_at TIMESTAMP NULL DEFAULT NULL',
	'SELECT 1'
);

PREPARE stmt FROM @add_payment_reminder_last_sent_at;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index if missing
SET @has_reminder_index := (
	SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.STATISTICS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'orders'
	  AND INDEX_NAME = 'idx_payment_reminder_last_sent_at'
);

SET @add_reminder_index := IF(
	@has_reminder_index = 0,
	'CREATE INDEX idx_payment_reminder_last_sent_at ON orders(payment_reminder_last_sent_at)',
	'SELECT 1'
);

PREPARE stmt FROM @add_reminder_index;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
