-- Reader's Haven - Database Schema
-- MySQL Database Setup for Bookstore & Counselling System

-- Create Database
CREATE DATABASE IF NOT EXISTS readers_haven;
USE readers_haven;

-- ===== PRODUCTS TABLE =====
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    image_url VARCHAR(255),
    emoji VARCHAR(10),
    in_stock BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== CUSTOMERS TABLE =====
CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== ORDERS TABLE =====
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    shipping_address TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== ORDER ITEMS TABLE =====
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== BOOKINGS TABLE =====
CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_number VARCHAR(50) NOT NULL UNIQUE,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20),
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    notes TEXT,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_booking_date (booking_date),
    INDEX idx_booking_time (booking_time),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== UNAVAILABLE DATES TABLE =====
CREATE TABLE IF NOT EXISTS unavailable_dates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    unavailable_date DATE NOT NULL UNIQUE,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (unavailable_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== ADMIN USERS TABLE =====
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    full_name VARCHAR(255),
    role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== INVENTORY TABLE =====
CREATE TABLE IF NOT EXISTS inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL UNIQUE,
    quantity_in_stock INT DEFAULT 0,
    quantity_reserved INT DEFAULT 0,
    quantity_available INT GENERATED ALWAYS AS (quantity_in_stock - quantity_reserved) STORED,
    last_restocked TIMESTAMP NULL,
    reorder_level INT DEFAULT 10,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== AUDIT LOG TABLE =====
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_created_at (created_at),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== INSERT DEFAULT DATA =====

-- Insert Products with detailed descriptions
INSERT INTO products (name, description, price, category, emoji, in_stock) VALUES
('Gratitude Journal', 'Start each day with intention and end it with gratitude. This beautifully designed journal helps you cultivate a positive mindset through daily gratitude practice. With guided prompts, inspirational quotes, and plenty of space for reflection, you''ll discover how acknowledging life''s blessings—big and small—can transform your perspective, reduce stress, and increase happiness. Perfect for anyone seeking more joy, peace, and mindfulness in their daily life. Features high-quality paper, a ribbon bookmark, and an elegant cover that makes it a joy to use every single day.', 89990, 'book', '📓', TRUE),
('Prayer Journal', 'Deepen your spiritual journey with this thoughtfully crafted prayer journal. More than just a notebook, it''s a sacred space to record your prayers, track answered prayers, and witness God''s faithfulness in your life. With scripture prompts, guided reflection questions, and dedicated sections for praise, thanksgiving, and intercession, this journal helps you build a consistent, meaningful prayer practice. Watch your faith grow as you look back and see how your prayers have been answered. Whether you''re new to prayer or deepening your practice, this journal will become your trusted companion in your spiritual walk.', 82490, 'book', '✨', TRUE),
('Fitness Journal', 'Your complete roadmap to a healthier, stronger, more energized you! This comprehensive wellness journal combines fitness tracking, nutrition planning, and mindful living into one powerful tool. Track your workouts, plan balanced meals, monitor your progress, and celebrate every victory along the way. With expert tips on exercise, nutrition, sleep, and stress management, plus motivational content to keep you going when things get tough, you''ll have everything you need to build sustainable healthy habits. Perfect for beginners starting their fitness journey or athletes looking to level up. Your transformation starts here!', 71990, 'book', '💪', TRUE);

-- Insert Default Admin User (password: admin123)
INSERT INTO admin_users (username, password_hash, email, full_name, role) VALUES
('admin', '$2y$10$YOvVV5CxVHQW8ZVlR1H8e.8v1o8F4vqVqP8CmJ8X/XfQ5Z5QJ7gQm', 'admin@readers-haven.com', 'Administrator', 'admin');

-- Create inventory records for products
INSERT INTO inventory (product_id, quantity_in_stock, reorder_level) VALUES
(1, 50, 10),
(2, 75, 10),
(3, 60, 10);

-- ===== CREATE INDEXES FOR PERFORMANCE =====
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_bookings_customer_email ON bookings(customer_email);
CREATE INDEX idx_bookings_created ON bookings(created_at);
