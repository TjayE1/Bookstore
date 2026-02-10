-- Bank Transfer Payment Support Migration
-- NOTE: Payment status ENUM updates are handled by migration_payment_verification.sql
-- This migration adds bank transfer specific enhancements

-- Add bank transfer verification fields if not exists
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS bank_transfer_reference VARCHAR(255) NULL 
AFTER payment_reference;

ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS bank_transfer_verified_at TIMESTAMP NULL DEFAULT NULL 
AFTER bank_transfer_reference;

-- Add indexes for bank transfer filtering and performance
CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON orders(payment_status);
CREATE INDEX IF NOT EXISTS idx_orders_payment_method ON orders(payment_method);
CREATE INDEX IF NOT EXISTS idx_orders_bank_transfer_ref ON orders(bank_transfer_reference);
CREATE INDEX IF NOT EXISTS idx_orders_payment_verified_at ON orders(payment_verified_at);
