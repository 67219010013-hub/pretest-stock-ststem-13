<?php
$host = 'stock_db';
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
    $pdo->exec("INSERT IGNORE INTO categories (name) VALUES ('Monitor'), ('Peripherals'), ('CPU'), ('GPU'), ('RAM'), ('Motherboard'), ('Storage'), ('PSU'), ('Case'), ('Cooling')");

    echo "Seeding new products...\n";
    echo "Seeding base products (CPU, GPU, etc.)...\n";
    // Helper to get ID
    function getCatId($pdo, $name)
    {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetchColumn();
    }

    $cats = ['CPU', 'GPU', 'RAM', 'Motherboard', 'Storage', 'PSU', 'Case', 'Cooling'];
    $catIds = [];
    foreach ($cats as $c)
        $catIds[$c] = getCatId($pdo, $c);

    if ($catIds['CPU']) {
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['CPU'], 'Core i9-14900K', 'Intel', '14900K', 589.99, 50, 5, 'https://placehold.co/600x400/007bff/FFF?text=Core+i9-14900K']);
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['CPU'], 'Core i5-13600K', 'Intel', '13600K', 299.99, 100, 10, 'https://placehold.co/600x400/007bff/FFF?text=Core+i5-13600K']);
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['CPU'], 'Ryzen 9 7950X3D', 'AMD', '7950X3D', 649.99, 40, 5, 'https://placehold.co/600x400/ff9900/000?text=Ryzen+9+7950X3D']);
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['CPU'], 'Ryzen 5 7600X', 'AMD', '7600X', 229.99, 80, 10, 'https://placehold.co/600x400/ff9900/000?text=Ryzen+5+7600X']);
    }
    if ($catIds['GPU']) {
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['GPU'], 'GeForce RTX 4090', 'NVIDIA', 'Founders Edition', 1599.99, 10, 2, 'https://placehold.co/600x400/76b900/FFF?text=RTX+4090']);
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['GPU'], 'GeForce RTX 4060 Ti', 'MSI', 'Ventus 2X', 399.99, 50, 5, 'https://placehold.co/600x400/76b900/FFF?text=RTX+4060+Ti']);
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['GPU'], 'Radeon RX 7900 XTX', 'Sapphire', 'Nitro+', 999.99, 20, 3, 'https://placehold.co/600x400/ff0000/FFF?text=RX+7900+XTX']);
    }
    if ($catIds['RAM']) {
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['RAM'], 'Dominator Platinum RGB 32GB', 'Corsair', 'DDR5-6000', 169.99, 100, 10, 'https://placehold.co/600x400/333/FFF?text=Corsair+Dominator']);
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['RAM'], 'Vengeance 16GB', 'Corsair', 'DDR5-5200', 89.99, 150, 20, 'https://placehold.co/600x400/333/FFF?text=Corsair+Vengeance']);
    }
    if ($catIds['Motherboard']) {
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['Motherboard'], 'ROG Maximus Z790 Hero', 'ASUS', 'Z790', 629.99, 15, 3, 'https://placehold.co/600x400/000/FFF?text=ROG+Maximus+Z790']);
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['Motherboard'], 'B650 AORUS ELITE', 'Gigabyte', 'B650', 199.99, 40, 5, 'https://placehold.co/600x400/ff6600/FFF?text=B650+Aorus']);
    }
    if ($catIds['Storage']) {
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['Storage'], '990 PRO 2TB', 'Samsung', 'NVMe', 179.99, 80, 10, 'https://placehold.co/600x400/000/FFF?text=Samsung+990+PRO']);
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['Storage'], 'WD Black SN850X 1TB', 'Western Digital', 'NVMe', 99.99, 100, 10, 'https://placehold.co/600x400/000/FFF?text=WD+Black+SN850X']);
    }
    if ($catIds['PSU']) {
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['PSU'], 'HX1000i', 'Corsair', '1000W Platinum', 239.99, 30, 5, 'https://placehold.co/600x400/333/FFF?text=Corsair+HX1000i']);
    }
    if ($catIds['Case']) {
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['Case'], 'O11 Dynamic Evo', 'Lian Li', 'Mid-Tower', 159.99, 25, 5, 'https://placehold.co/600x400/ccc/000?text=Lian+Li+O11']);
    }
    if ($catIds['Cooling']) {
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$catIds['Cooling'], 'Kraken Elite 360', 'NZXT', 'AIO Liquid', 279.99, 35, 5, 'https://placehold.co/600x400/6f42c1/FFF?text=NZXT+Kraken']);
    }

    // Get Category IDs
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute(['Monitor']);
    $monId = $stmt->fetchColumn();

    $stmt->execute(['Peripherals']);
    $perId = $stmt->fetchColumn();

    if ($monId) {
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$monId, 'Odyssey G9 OLED', 'Samsung', '49" SC90', 1199.99, 10, 2, 'https://placehold.co/600x400/000000/FFF?text=Odyssey+G9']);
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$monId, 'UltraGear 27GR95QE', 'LG', '27" OLED 240Hz', 849.99, 15, 3, 'https://placehold.co/600x400/000000/FFF?text=UltraGear+OLED']);
    }

    if ($perId) {
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$perId, 'G Pro X Superlight 2', 'Logitech', 'Wireless Mouse', 159.99, 50, 5, 'https://placehold.co/600x400/000000/FFF?text=G+Pro+X']);
        $pdo->prepare("INSERT IGNORE INTO products (category_id, name, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$perId, 'BlackWidow V4 Pro', 'Razer', 'Mechanical Keyboard', 229.99, 30, 5, 'https://placehold.co/600x400/000000/FFF?text=BlackWidow+V4']);
    }

    // 5. Update Images for ALL products (ensure they show up even if product exists)
    echo "Updating product images...\n";
    $images = [
        'Core i9-14900K' => 'https://placehold.co/600x400/007bff/FFF?text=Core+i9-14900K',
        'Ryzen 9 7950X3D' => 'https://placehold.co/600x400/ff9900/000?text=Ryzen+9+7950X3D',
        'GeForce RTX 4090' => 'https://placehold.co/600x400/76b900/FFF?text=RTX+4090',
        'Radeon RX 7900 XTX' => 'https://placehold.co/600x400/ff0000/FFF?text=RX+7900+XTX',
        'Dominator Platinum RGB 32GB' => 'https://placehold.co/600x400/333/FFF?text=Corsair+Dominator',
        'ROG Maximus Z790 Hero' => 'https://placehold.co/600x400/000/FFF?text=ROG+Maximus+Z790',
        '990 PRO 2TB' => 'https://placehold.co/600x400/000/FFF?text=Samsung+990+PRO',
        'HX1000i' => 'https://placehold.co/600x400/333/FFF?text=Corsair+HX1000i',
        'O11 Dynamic Evo' => 'https://placehold.co/600x400/ccc/000?text=Lian+Li+O11',
        'Kraken Elite 360' => 'https://placehold.co/600x400/6f42c1/FFF?text=NZXT+Kraken',
        'Odyssey G9 OLED' => 'https://placehold.co/600x400/000/FFF?text=Odyssey+G9',
        'UltraGear 27GR95QE' => 'https://placehold.co/600x400/a50034/FFF?text=UltraGear+OLED',
        'G Pro X Superlight 2' => 'https://placehold.co/600x400/333/FFF?text=G+Pro+X',
        'BlackWidow V4 Pro' => 'https://placehold.co/600x400/44d62c/000?text=BlackWidow+V4'
    ];

    $stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE name = ?");
    foreach ($images as $name => $url) {
        $stmt->execute([$url, $name]);
    }

    echo "Update complete! All products now have images.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>