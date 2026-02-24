<?php
$host = 'stock_db';
$db = 'stock_system';
$user = 'user';
$pass = 'password';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Auto-initialize tables if they don't exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        $sql = "
            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS users (
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

            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                total_price DECIMAL(10, 2) NOT NULL,
                status VARCHAR(20) DEFAULT 'completed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT,
                quantity INT NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
            );

            INSERT IGNORE INTO users (username, password_hash, role) VALUES 
            ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

            INSERT IGNORE INTO categories (name) VALUES 
            ('CPU'), ('GPU'), ('RAM'), ('Motherboard'), ('Storage'), ('PSU'), ('Case'), ('Cooling'), ('Monitor'), ('Peripherals');
        ";
        $pdo->exec($sql);
    }

    // Migration for Products: image_url
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_url'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN image_url TEXT");
    }

    // Migration for Users: add missing profile columns
    $userColumns = ['full_name' => 'VARCHAR(100)', 'address' => 'TEXT', 'phone' => 'VARCHAR(20)', 'profile_image' => 'VARCHAR(255)'];
    foreach ($userColumns as $col => $type) {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col $type");
        }
    }

    // Auto-seed if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    if ($stmt->fetchColumn() == 0) {
        $sql = "
            ((SELECT id FROM categories WHERE name='Case'), 'O11 Dynamic Evo', 'Lian Li', 'Mid-Tower', 159.99, 25, 5, 'https://m.media-amazon.com/images/I/61kL-u4qHXL._AC_SL1500_.jpg'),
            ((SELECT id FROM categories WHERE name='Cooling'), 'Kraken Elite 360', 'NZXT', 'AIO Liquid', 279.99, 35, 5, 'https://m.media-amazon.com/images/I/71G1+w+s+5L._AC_SL1500_.jpg');
        ";
        $pdo->exec($sql);
    }
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}
?>