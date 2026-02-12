SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_verified_at'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN payment_verified_at TIMESTAMP NULL DEFAULT NULL', 
    'SELECT "payment_verified_at already exists" AS Status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add payment_verification_notes
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_verification_notes'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN payment_verification_notes TEXT', 
    'SELECT "payment_verification_notes already exists" AS Status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add payment_reference
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_reference'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN payment_reference VARCHAR(255)', 
    'SELECT "payment_reference already exists" AS Status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add payment_reminder_count
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_reminder_count'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN payment_reminder_count INT DEFAULT 0', 
    'SELECT "payment_reminder_count already exists" AS Status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add payment_reminder_last_sent_at
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_reminder_last_sent_at'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN payment_reminder_last_sent_at TIMESTAMP NULL DEFAULT NULL', 
    'SELECT "payment_reminder_last_sent_at already exists" AS Status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update payment_status enum (always run - it's safe)
ALTER TABLE orders MODIFY COLUMN payment_status 
ENUM('pending', 'awaiting_confirmation', 'processing', 'completed', 'failed', 'refunded') 
DEFAULT 'pending';

-- Check and add idx_payment_status
SET @idx_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_payment_status'
);
SET @sql = IF(@idx_exists = 0, 
    'CREATE INDEX idx_payment_status ON orders(payment_status)', 
    'SELECT "idx_payment_status already exists" AS Status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add idx_payment_method
SET @idx_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_payment_method'
);
SET @sql = IF(@idx_exists = 0, 
    'CREATE INDEX idx_payment_method ON orders(payment_method)', 
    'SELECT "idx_payment_method already exists" AS Status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add idx_payment_reminder_last_sent_at
SET @idx_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_payment_reminder_last_sent_at'
);
SET @sql = IF(@idx_exists = 0, 
    'CREATE INDEX idx_payment_reminder_last_sent_at ON orders(payment_reminder_last_sent_at)', 
    'SELECT "idx_payment_reminder_last_sent_at already exists" AS Status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Migration completed successfully! All payment columns and indexes are ready.' AS Status;
