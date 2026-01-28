<?php
header('Content-Type: application/json');
require 'db.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_products':
            $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_categories':
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_stats':
            $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
            $lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level")->fetchColumn();
            $totalStock = $pdo->query("SELECT SUM(stock_quantity) FROM products")->fetchColumn() ?: 0;
            echo json_encode([
                'total_products' => $totalProducts,
                'low_stock' => $lowStock,
                'total_stock' => $totalStock
            ]);
            break;

        case 'add_product':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $sql = "INSERT INTO products (name, category_id, brand, model, price, stock_quantity, min_stock_level) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sql)->execute([
                    $data['name'],
                    $data['category_id'],
                    $data['brand'],
                    $data['model'],
                    $data['price'],
                    $data['stock_quantity'],
                    $data['min_stock_level'] ?? 5
                ]);
                echo json_encode(['success' => true]);
            }
            break;

        case 'update_stock':
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

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>