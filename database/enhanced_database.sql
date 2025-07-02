-- Enhanced Database Schema with additional tables for better session management and reports

USE bagas_db;

-- Create remember_tokens table for persistent sessions
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_expires_at (expires_at)
);

-- Create reports table for managing various reports
CREATE TABLE IF NOT EXISTS reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    report_type ENUM('sales', 'inventory', 'user_activity', 'financial', 'custom') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    report_data JSON,
    parameters JSON,
    status ENUM('draft', 'generated', 'scheduled', 'archived') DEFAULT 'draft',
    file_path VARCHAR(255),
    generated_at DATETIME NULL,
    scheduled_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_report_type (report_type),
    INDEX idx_status (status),
    INDEX idx_generated_at (generated_at)
);

-- Create suppliers table for better inventory management
CREATE TABLE IF NOT EXISTS suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add supplier_id to products table
ALTER TABLE products ADD COLUMN supplier_id INT NULL,
ADD FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL;

-- Create purchase_orders table for tracking inventory purchases
CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_id INT NOT NULL,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('draft', 'sent', 'received', 'cancelled') DEFAULT 'draft',
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    actual_delivery_date DATE,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_order_date (order_date)
);

-- Create purchase_order_items table
CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create notifications table for system notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL, -- NULL means system-wide notification
    type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(255),
    expires_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Insert sample suppliers
INSERT IGNORE INTO suppliers (id, name, contact_person, email, phone, address) VALUES 
(1, 'PT Elektronik Jaya', 'Budi Santoso', 'budi@elektronikjaya.com', '021-1234567', 'Jl. Sudirman No. 123, Jakarta'),
(2, 'Toko Buku Cerdas', 'Sari Dewi', 'sari@bukucerdas.com', '021-2345678', 'Jl. Thamrin No. 456, Jakarta'),
(3, 'Fashion Store Indonesia', 'Andi Rahman', 'andi@fashionstore.id', '021-3456789', 'Jl. MH Thamrin No. 789, Jakarta');

-- Update existing products with suppliers
UPDATE products SET supplier_id = 1 WHERE category_id = 1; -- Electronics
UPDATE products SET supplier_id = 2 WHERE category_id = 2; -- Books  
UPDATE products SET supplier_id = 3 WHERE category_id = 3; -- Clothing

-- Insert sample purchase orders
INSERT IGNORE INTO purchase_orders (id, supplier_id, user_id, order_number, total_amount, status, order_date) VALUES 
(1, 1, 1, 'PO-2024-001', 5000000, 'received', '2024-06-01'),
(2, 2, 1, 'PO-2024-002', 850000, 'received', '2024-06-15'),
(3, 3, 1, 'PO-2024-003', 1200000, 'sent', '2024-07-01');

-- Insert sample purchase order items
INSERT IGNORE INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_price) VALUES 
(1, 1, 5, 3000000), -- Smartphones
(1, 6, 8, 200000),  -- Headphones
(2, 2, 10, 120000), -- Books
(2, 5, 15, 70000),  -- Novels
(3, 3, 20, 60000);  -- T-shirts

-- Insert sample reports
INSERT IGNORE INTO reports (user_id, report_type, title, description, status) VALUES 
(1, 'sales', 'Monthly Sales Report - June 2024', 'Comprehensive sales analysis for June 2024', 'generated'),
(1, 'inventory', 'Low Stock Alert Report', 'Products running low on inventory', 'generated'),
(1, 'user_activity', 'User Activity Summary', 'Summary of user activities in the system', 'draft'),
(1, 'financial', 'Quarterly Financial Report', 'Financial overview for Q2 2024', 'scheduled');

-- Insert sample notifications
INSERT IGNORE INTO notifications (user_id, type, title, message, action_url) VALUES 
(1, 'warning', 'Low Stock Alert', 'Product "Smartphone Android" is running low on stock (5 remaining)', '/products.php'),
(1, 'info', 'New Purchase Order', 'Purchase order PO-2024-003 has been created', '/purchase_orders.php'),
(1, 'success', 'Report Generated', 'Monthly sales report has been generated successfully', '/reports.php'),
(NULL, 'info', 'System Maintenance', 'Scheduled maintenance on July 15, 2024 from 2:00-4:00 AM', NULL);
