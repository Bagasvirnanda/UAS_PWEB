-- Database Setup Script for bagas.mangaverse.my.id
-- This script creates the complete database structure with proper foreign key constraints

-- Create database
CREATE DATABASE IF NOT EXISTS bagas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE bagas_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    category_id INT,
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Create transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create transaction_items table
CREATE TABLE IF NOT EXISTS transaction_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
);

-- Create user_activity_logs table
CREATE TABLE IF NOT EXISTS user_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity VARCHAR(255) NOT NULL,
    activity_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (id, username, email, password, full_name, role) VALUES 
(1, 'admin', 'admin@bagas.com', '$2y$12$8Hwl4tf/HJpXhG.Asc/XTOnhayKPJ0u6.8adup6QE7YBIPb08ZmZK', 'Administrator', 'admin');

-- Insert sample categories
INSERT IGNORE INTO categories (id, name, description) VALUES 
(1, 'Electronics', 'Electronic devices and gadgets'),
(2, 'Books', 'Various types of books'),
(3, 'Clothing', 'Fashion and clothing items');

-- Insert sample products
INSERT IGNORE INTO products (id, name, description, price, stock, category_id) VALUES 
(1, 'Smartphone Android', 'Latest Android smartphone with high-end features', 3500000, 15, 1),
(2, 'Programming Book', 'Complete guide to web programming', 150000, 25, 2),
(3, 'Casual T-Shirt', 'Comfortable cotton t-shirt', 75000, 30, 3),
(4, 'Laptop Gaming', 'High performance gaming laptop', 12000000, 8, 1),
(5, 'Novel Fiction', 'Bestselling fiction novel', 85000, 20, 2),
(6, 'Wireless Headphone', 'High quality wireless headphone', 250000, 12, 1);

-- Insert sample transactions
INSERT IGNORE INTO transactions (id, user_id, total_amount, status, payment_method) VALUES 
(1, 1, 75000, 'completed', 'cash'),
(2, 1, 250000, 'completed', 'transfer');

-- Insert sample transaction items
INSERT IGNORE INTO transaction_items (transaction_id, product_id, quantity, price) VALUES 
(1, 3, 1, 75000.00),
(2, 6, 1, 250000.00);

-- Insert sample activity logs
INSERT IGNORE INTO user_activity_logs (user_id, activity, activity_time) VALUES 
(1, 'Login to dashboard', NOW() - INTERVAL 1 HOUR),
(1, 'Created new product', NOW() - INTERVAL 2 HOUR),
(1, 'Updated transaction status', NOW() - INTERVAL 3 HOUR),
(1, 'Generated report', NOW() - INTERVAL 4 HOUR),
(1, 'Backup database', NOW() - INTERVAL 5 HOUR);
