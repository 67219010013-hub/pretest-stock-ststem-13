<?php
header('Content-Type: application/json');
require 'db.php';

$action = $_GET['action'] ?? '';

try {
    session_start();

    // Auth check function
    function requireAuth()
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    function requireAdmin()
    {
        requireAuth();
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
    }

    switch ($action) {
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$data['username']]);
                $user = $stmt->fetch();

                if ($user && password_verify($data['password'], $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    echo json_encode(['success' => true, 'role' => $user['role']]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
                }
            }
            break;

        case 'register':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $username = $data['username'];
                $password = $data['password'];

                // Check if exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Username already exists']);
                    exit;
                }

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'customer')");

                if ($stmt->execute([$username, $hash])) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Registration failed']);
                }
            }
            break;

        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;

        case 'check_auth':
            if (isset($_SESSION['user_id'])) {
                echo json_encode(['authenticated' => true, 'username' => $_SESSION['username'], 'role' => $_SESSION['role']]);
            } else {
                echo json_encode(['authenticated' => false]);
            }
            break;

        case 'get_products':
            requireAuth();
            $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_categories':
            requireAuth();
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_stats':
            requireAdmin();
            $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
            $lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level")->fetchColumn();
            $totalStock = $pdo->query("SELECT SUM(stock_quantity) FROM products")->fetchColumn() ?: 0;
            echo json_encode([
                'total_products' => $totalProducts,
                'low_stock' => $lowStock,
                'total_stock' => $totalStock
            ]);
            break;

        case 'get_logs':
            requireAdmin();
            $stmt = $pdo->query("SELECT l.*, p.name as product_name FROM stock_logs l LEFT JOIN products p ON l.product_id = p.id ORDER BY l.created_at DESC LIMIT 10");
            echo json_encode($stmt->fetchAll());
            break;

        case 'add_product':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $sql = "INSERT INTO products (name, category_id, brand, model, price, stock_quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sql)->execute([
                    $data['name'],
                    $data['category_id'],
                    $data['brand'],
                    $data['model'],
                    $data['price'],
                    $data['stock_quantity'],
                    $data['min_stock_level'] ?? 5,
                    $data['image_url'] ?? null
                ]);
                echo json_encode(['success' => true]);
            }
            break;

        case 'update_stock':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $pid = $data['product_id'];
                $qty = $data['quantity'];
                $type = $data['type']; // 'IN' or 'OUT'
                $notes = $data['notes'] ?? '';

                $pdo->beginTransaction();

                // Update stock level
                $op = ($type === 'IN') ? '+' : '-';
                $sql = "UPDATE products SET stock_quantity = stock_quantity $op ? WHERE id = ?";
                $pdo->prepare($sql)->execute([$qty, $pid]);

                // Log transaction
                $sql = "INSERT INTO stock_logs (product_id, type, quantity, notes) VALUES (?, ?, ?, ?)";
                $pdo->prepare($sql)->execute([$pid, $type, $qty, $notes]);

                $pdo->commit();
                echo json_encode(['success' => true]);
            }
            break;

        case 'checkout':
            requireAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $cart = $data['cart'];

                if (empty($cart)) {
                    echo json_encode(['error' => 'Cart is empty']);
                    exit;
                }

                $pdo->beginTransaction();
                try {
                    // Create Order
                    $total = 0;
                    foreach ($cart as $item)
                        $total += $item['price'];

                    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price) VALUES (?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $total]);
                    $orderId = $pdo->lastInsertId();

                    // Process Items
                    foreach ($cart as $item) {
                        // Deduct Stock
                        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - 1 WHERE id = ? AND stock_quantity > 0");
                        $stmt->execute([$item['id']]);

                        if ($stmt->rowCount() == 0) {
                            throw new Exception("Product {$item['name']} is out of stock");
                        }

                        // Add Order Item
                        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, 1, ?)");
                        $stmt->execute([$orderId, $item['id'], $item['price']]);

                        // Log Stock Movement
                        $stmt = $pdo->prepare("INSERT INTO stock_logs (product_id, type, quantity, notes) VALUES (?, 'OUT', 1, 'Customer Order #$orderId')");
                        $stmt->execute([$item['id']]);
                    }

                    $pdo->commit();
                    echo json_encode(['success' => true, 'order_id' => $orderId]);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    http_response_code(400);
                    echo json_encode(['error' => $e->getMessage()]);
                }
            }
            break;

        case 'delete_product':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                if ($stmt->execute([$data['id']])) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Delete failed']);
                }
            }
            break;

        case 'edit_product':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $sql = "UPDATE products SET name=?, category_id=?, brand=?, model=?, price=?, min_stock_level=?, image_url=? WHERE id=?";
                $params = [
                    $data['name'],
                    $data['category_id'],
                    $data['brand'],
                    $data['model'],
                    $data['price'],
                    $data['min_stock_level'],
                    $data['image_url'],
                    $data['id']
                ];

                if ($pdo->prepare($sql)->execute($params)) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Update failed']);
                }
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>