-- Delivery Options Table
-- Add this to the database schema to support delivery method selection with pricing

-- ===== DELIVERY OPTIONS TABLE =====
CREATE TABLE IF NOT EXISTS delivery_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    delivery_time_min INT NOT NULL COMMENT 'Minimum days for delivery',
    delivery_time_max INT NOT NULL COMMENT 'Maximum days for delivery',
    cost DECIMAL(10, 2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alter orders table to include delivery information
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_method_id INT AFTER shipping_address;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_cost DECIMAL(10, 2) DEFAULT 0 AFTER delivery_method_id;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_date TIMESTAMP NULL AFTER delivery_cost;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS dispatch_slip_number VARCHAR(50) UNIQUE AFTER delivery_date;

-- Add foreign key constraint if not exists (check first to avoid errors)
ALTER TABLE orders 
ADD CONSTRAINT FOREIGN KEY (delivery_method_id) REFERENCES delivery_options(id) ON DELETE SET NULL;

-- ===== INSERT DEFAULT DELIVERY OPTIONS =====
INSERT IGNORE INTO delivery_options (name, description, delivery_time_min, delivery_time_max, cost) VALUES
('Standard Delivery', 'Delivered in 5-7 business days', 5, 7, 5000),
('Express Delivery', 'Delivered in 2-3 business days', 2, 3, 15000),
('Next Day Delivery', 'Delivered next business day (Orders before 2 PM)', 1, 1, 25000),
('Pickup at Store', 'Pick up from our store location', 0, 0, 0);

-- ===== CREATE INDEX FOR PERFORMANCE =====
CREATE INDEX idx_delivery_active ON delivery_options(is_active);
CREATE INDEX idx_orders_delivery ON orders(delivery_method_id);
CREATE INDEX idx_orders_dispatch_slip ON orders(dispatch_slip_number);
