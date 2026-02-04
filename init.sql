CREATE DATABASE IF NOT EXISTS stock_system;
USE stock_system;

DROP TABLE IF EXISTS stock_logs;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS categories;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    price DECIMAL(10, 2) DEFAULT 0.00,
    stock_quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 5,
    image_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS stock_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    type ENUM('IN', 'OUT') NOT NULL,
    quantity INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default user: admin / admin123
INSERT IGNORE INTO users (username, password_hash, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Seed initial categories
-- Seed initial categories
INSERT IGNORE INTO categories (name) VALUES 
('CPU'), ('GPU'), ('RAM'), ('Motherboard'), ('Storage'), ('PSU'), ('Case'), ('Cooling');

-- Seed initial products
INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level) VALUES
((SELECT id FROM categories WHERE name='CPU'), 'Core i9-14900K', 'Intel', '14900K', 589.99, 50, 5),
((SELECT id FROM categories WHERE name='CPU'), 'Ryzen 9 7950X3D', 'AMD', '7950X3D', 649.99, 40, 5),
((SELECT id FROM categories WHERE name='GPU'), 'GeForce RTX 4090', 'NVIDIA', 'Founders Edition', 1599.99, 10, 2),
((SELECT id FROM categories WHERE name='GPU'), 'Radeon RX 7900 XTX', 'Sapphire', 'Nitro+', 999.99, 20, 3),
((SELECT id FROM categories WHERE name='RAM'), 'Dominator Platinum RGB 32GB', 'Corsair', 'DDR5-6000', 169.99, 100, 10),
((SELECT id FROM categories WHERE name='Motherboard'), 'ROG Maximus Z790 Hero', 'ASUS', 'Z790', 629.99, 15, 3),
((SELECT id FROM categories WHERE name='Storage'), '990 PRO 2TB', 'Samsung', 'NVMe', 179.99, 80, 10),
((SELECT id FROM categories WHERE name='PSU'), 'HX1000i', 'Corsair', '1000W Platinum', 239.99, 30, 5),
((SELECT id FROM categories WHERE name='Case'), 'O11 Dynamic Evo', 'Lian Li', 'Mid-Tower', 159.99, 25, 5),
((SELECT id FROM categories WHERE name='Cooling'), 'Kraken Elite 360', 'NZXT', 'AIO Liquid', 279.99, 35, 5);
