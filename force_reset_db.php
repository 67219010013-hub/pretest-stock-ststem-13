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
    ('CPU'), ('GPU'), ('RAM'), ('Motherboard'), ('Storage'), ('PSU'), ('Case'), ('Cooling');
    ";

    $pdo->exec($sql);
    echo "Database reset successfully!\n";

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    // Look at db.php content if available to see what they use
}
?>