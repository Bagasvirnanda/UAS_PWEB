-- Create cart table for shopping cart functionality
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

-- Add created_at and updated_at columns to categories table if they don't exist
ALTER TABLE categories 
ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add some sample data to products and categories for testing
INSERT IGNORE INTO categories (id, name, description, created_at, updated_at) VALUES 
(1, 'Electronics', 'Electronic devices and gadgets', NOW(), NOW()),
(2, 'Books', 'Various types of books', NOW(), NOW()),
(3, 'Clothing', 'Fashion and clothing items', NOW(), NOW());

INSERT IGNORE INTO products (id, name, description, price, stock, category_id, created_at, updated_at) VALUES 
(1, 'Smartphone Android', 'Latest Android smartphone with high-end features', 3500000, 15, 1, NOW(), NOW()),
(2, 'Programming Book', 'Complete guide to web programming', 150000, 25, 2, NOW(), NOW()),
(3, 'Casual T-Shirt', 'Comfortable cotton t-shirt', 75000, 30, 3, NOW(), NOW()),
(4, 'Laptop Gaming', 'High performance gaming laptop', 12000000, 8, 1, NOW(), NOW()),
(5, 'Novel Fiction', 'Bestselling fiction novel', 85000, 20, 2, NOW(), NOW());
