<?php
$host = 'mysql-db'; // Using the service name from typical docker-compose, or 'localhost' if local
$dbname = 'stock_system';
$username = 'user';
$password = 'password';

// Check if we can connect to mysql without db first
try {
    // Attempt 1: Docker/env vars
    if (getenv('DB_HOST'))
        $host = getenv('DB_HOST');
    if (getenv('DB_USER'))
        $username = getenv('DB_USER');
    if (getenv('DB_PASSWORD'))
        $password = getenv('DB_PASSWORD');

    echo "Connecting to $host...\n";
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to Database Server. Re-creating schema...\n";

    $sql = "
    CREATE DATABASE IF NOT EXISTS stock_system;
    USE stock_system;

    DROP TABLE IF EXISTS stock_logs;
    DROP TABLE IF EXISTS products;
    DROP TABLE IF EXISTS users;
    DROP TABLE IF EXISTS categories;

    CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        address TEXT,
        phone VARCHAR(20),
        profile_image VARCHAR(255),
        role ENUM('admin', 'customer') DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_price DECIMAL(10, 2) NOT NULL,
        status VARCHAR(20) DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT,
        quantity INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
    );

    INSERT INTO users (username, password_hash, role) VALUES 
    ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

    CREATE TABLE products (
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

    CREATE TABLE stock_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT,
        type ENUM('IN', 'OUT') NOT NULL,
        quantity INT NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    );

    INSERT INTO categories (name) VALUES 
    ('CPU'), ('GPU'), ('RAM'), ('Motherboard'), ('Storage'), ('PSU'), ('Case'), ('Cooling'), ('Monitor'), ('Peripherals');

    INSERT INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES
    (1, 'Core i9-14900K', 'Intel', '14900K', 589.99, 50, 5, 'https://placehold.co/600x400/007bff/FFF?text=Core+i9-14900K'),
    (1, 'Ryzen 9 7950X3D', 'AMD', '7950X3D', 649.99, 40, 5, 'https://placehold.co/600x400/ff9900/000?text=Ryzen+9+7950X3D'),
    (2, 'GeForce RTX 4090', 'NVIDIA', 'Founders Edition', 1599.99, 10, 2, 'https://placehold.co/600x400/76b900/FFF?text=RTX+4090'),
    (2, 'Radeon RX 7900 XTX', 'Sapphire', 'Nitro+', 999.99, 20, 3, 'https://placehold.co/600x400/ff0000/FFF?text=RX+7900+XTX'),
    (3, 'Dominator Platinum RGB 32GB', 'Corsair', 'DDR5-6000', 169.99, 100, 10, 'https://placehold.co/600x400/333/FFF?text=Corsair+Dominator'),
    (4, 'ROG Maximus Z790 Hero', 'ASUS', 'Z790', 629.99, 15, 3, 'https://placehold.co/600x400/000/FFF?text=ROG+Maximus+Z790'),
    (5, '990 PRO 2TB', 'Samsung', 'NVMe', 179.99, 80, 10, 'https://placehold.co/600x400/000/FFF?text=Samsung+990+PRO'),
    (6, 'HX1000i', 'Corsair', '1000W Platinum', 239.99, 30, 5, 'https://placehold.co/600x400/333/FFF?text=Corsair+HX1000i'),
    (7, 'O11 Dynamic Evo', 'Lian Li', 'Mid-Tower', 159.99, 25, 5, 'https://placehold.co/600x400/ccc/000?text=Lian+Li+O11'),
    (8, 'Kraken Elite 360', 'NZXT', 'AIO Liquid', 279.99, 35, 5, 'https://placehold.co/600x400/6f42c1/FFF?text=NZXT+Kraken'),
    (9, 'Odyssey G9 OLED', 'Samsung', '49\" SC90', 1199.99, 10, 2, 'https://placehold.co/600x400/000/FFF?text=Odyssey+G9'),
    (9, 'UltraGear 27GR95QE', 'LG', '27\" OLED 240Hz', 849.99, 15, 3, 'https://placehold.co/600x400/a50034/FFF?text=UltraGear+OLED'),
    (10, 'G Pro X Superlight 2', 'Logitech', 'Wireless Mouse', 159.99, 50, 5, 'https://placehold.co/600x400/333/FFF?text=G+Pro+X'),
    (10, 'BlackWidow V4 Pro', 'Razer', 'Mechanical Keyboard', 229.99, 30, 5, 'https://placehold.co/600x400/44d62c/000?text=BlackWidow+V4');
    ";

    $pdo->exec($sql);
    echo "Database reset successfully!\n";

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    // Look at db.php content if available to see what they use
}
?>