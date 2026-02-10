-- Payment System Enhancement Migration
-- Adds payment verification fields to orders table

-- Add payment_verified_at column if not exists
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS payment_verified_at TIMESTAMP NULL DEFAULT NULL 
AFTER payment_status;

-- Add payment_verification_notes column if not exists
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS payment_verification_notes TEXT NULL 
AFTER payment_verified_at;

-- Add payment_reference column if not exists
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(255) NULL 
AFTER payment_verification_notes;

-- Update payment_status enum to include new statuses if not already present
-- Note: This requires recreating the column with new ENUM values
-- First, add a temporary column
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS payment_status_new ENUM('pending', 'awaiting_confirmation', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending';

-- Copy existing data
UPDATE orders SET payment_status_new = 
  CASE 
    WHEN payment_status = 'pending' THEN 'pending'
    WHEN payment_status = 'completed' THEN 'completed'
    WHEN payment_status = 'failed' THEN 'failed'
    WHEN payment_status = 'refunded' THEN 'refunded'
    ELSE 'pending'
  END;

-- Drop old column and rename new one
ALTER TABLE orders DROP COLUMN payment_status;
ALTER TABLE orders CHANGE COLUMN payment_status_new payment_status 
  ENUM('pending', 'awaiting_confirmation', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending';

-- Create index on payment_status for faster filtering
CREATE INDEX IF NOT EXISTS idx_payment_status ON orders(payment_status);

-- Create index on payment_method for faster filtering
CREATE INDEX IF NOT EXISTS idx_payment_method ON orders(payment_method);

-- Insert sample data (optional - for testing)
-- You can remove this section if you don't want test data
/*
INSERT INTO orders (order_number, customer_id, customer_name, customer_email, total_amount, payment_method, payment_status, status, created_at)
VALUES 
  ('ORD-TEST-BANK-001', 1, 'Test Customer', 'test@example.com', 50000, 'bank_transfer', 'awaiting_confirmation', 'pending', NOW()),
  ('ORD-TEST-MOBILE-001', 1, 'Test Customer 2', 'test2@example.com', 35000, 'mobile_money', 'awaiting_confirmation', 'pending', NOW());
*/
