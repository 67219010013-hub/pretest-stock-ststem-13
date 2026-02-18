<?php
$host = 'mysql-db';
$dbname = 'stock_system';
$username = 'user';
$password = 'password';

// Check if we can connect to mysql without db first
try {
    if (getenv('DB_HOST'))
        $host = getenv('DB_HOST');
    if (getenv('DB_USER'))
        $username = getenv('DB_USER');
    if (getenv('DB_PASSWORD'))
        $password = getenv('DB_PASSWORD');

    echo "Connecting to $host...\n";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected. Checking schema...\n";

    // 1. Add Columns to Users
    try {
        $pdo->query("SELECT full_name FROM users LIMIT 1");
    } catch (PDOException $e) {
        echo "Adding missing columns to 'users'...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(100)");
        $pdo->exec("ALTER TABLE users ADD COLUMN address TEXT");
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20)");
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255)");
    }

    // 2. Add Orders Table
    echo "Checking 'orders' table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_price DECIMAL(10, 2) NOT NULL,
            status VARCHAR(20) DEFAULT 'completed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // 3. Add Order Items Table
    echo "Checking 'order_items' table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT,
            quantity INT NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
        )
    ");

    // 4. Seed New Data (Monitors/Peripherals)
    echo "Seeding new categories...\n";
    $pdo->exec("INSERT IGNORE INTO categories (name) VALUES ('Monitor'), ('Peripherals')");

    echo "Seeding new products...\n";
    // Get Category IDs
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute(['Monitor']);
    $monId = $stmt->fetchColumn();

    $stmt->execute(['Peripherals']);
    $perId = $stmt->fetchColumn();

    if ($monId) {
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$monId, 'Odyssey G9 OLED', 'Samsung', '49" SC90', 1199.99, 10, 2]);
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$monId, 'UltraGear 27GR95QE', 'LG', '27" OLED 240Hz', 849.99, 15, 3]);
    }

    if ($perId) {
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$perId, 'G Pro X Superlight 2', 'Logitech', 'Wireless Mouse', 159.99, 50, 5]);
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$perId, 'BlackWidow V4 Pro', 'Razer', 'Mechanical Keyboard', 229.99, 30, 5]);
    }

    echo "Update complete! No data was lost.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>