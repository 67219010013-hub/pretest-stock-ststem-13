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
                    $_SESSION['profile_image'] = $user['profile_image'] ?? null;
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
                $fullName = $data['full_name'] ?? '';
                $address = $data['address'] ?? '';
                $phone = $data['phone'] ?? '';
                $image = $data['profile_image'] ?? '';

                // Check if exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Username already exists']);
                    exit;
                }

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, address, phone, profile_image, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')");

                if ($stmt->execute([$username, $hash, $fullName, $address, $phone, $image])) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Registration failed']);
                }
            }
            break;

        case 'get_profile':
            requireAuth();
            $stmt = $pdo->prepare("SELECT id, username, full_name, address, phone, profile_image, role, created_at FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            // Get Order History
            $stmtOrders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
            $stmtOrders->execute([$_SESSION['user_id']]);
            $orders = $stmtOrders->fetchAll();

            // Get Order Items for each order (simple loop since scale is small)
            foreach ($orders as &$order) {
                $stmtItems = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image_url FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                $stmtItems->execute([$order['id']]);
                $order['items'] = $stmtItems->fetchAll();
            }

            echo json_encode(['user' => $user, 'orders' => $orders]);
            break;

        case 'update_profile':
            requireAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $sql = "UPDATE users SET full_name=?, address=?, phone=?, profile_image=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$data['full_name'], $data['address'], $data['phone'], $data['profile_image'], $_SESSION['user_id']])) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Update failed']);
                }
            }
            break;

        case 'upload_image':
            // requireAuth(); // Allow public upload for registration
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
                $file = $_FILES['file'];
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir))
                    mkdir($uploadDir, 0777, true);

                $fileName = uniqid() . '_' . basename($file['name']);
                $targetPath = $uploadDir . $fileName;

                // Simple validation
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    echo json_encode(['success' => false, 'error' => 'Invalid file type']);
                    exit;
                }

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    echo json_encode(['success' => true, 'url' => $targetPath]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Upload failed']);
                }
            }
            break;

        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;

        case 'check_auth':
            if (isset($_SESSION['user_id'])) {
                // Refresh session user data if needed? For now just return session
                // Ideally we might want to fetch latest image/role
                $stmt = $pdo->prepare("SELECT username, role, profile_image FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $u = $stmt->fetch();
                echo json_encode([
                    'authenticated' => true,
                    'username' => $u['username'],
                    'role' => $u['role'],
                    'profile_image' => $u['profile_image']
                ]);
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

        case 'get_ai_recommendation':
            requireAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $budget = floatval($data['budget'] ?? 0);
                $usage = $data['usage'] ?? 'gaming'; // gaming, workstation, office

                if ($budget <= 0) {
                    echo json_encode(['error' => 'Please provide a valid budget']);
                    exit;
                }

                // Fetch all available products grouped by category
                $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock_quantity > 0");
                $allProducts = $stmt->fetchAll();

                $categories = [];
                foreach ($allProducts as $p) {
                    $categories[$p['category_name']][] = $p;
                }

                // Sort category products by price
                foreach ($categories as $catName => &$prods) {
                    usort($prods, function ($a, $b) {
                        return $a['price'] <=> $b['price'];
                    });
                }

                $recommendation = [];
                $totalPrice = 0;
                $explanation = "";

                // Heuristic selection logic
                // Allocation ratios based on usage
                $ratios = [
                    'gaming' => ['GPU' => 0.45, 'CPU' => 0.20, 'Motherboard' => 0.10, 'RAM' => 0.08, 'Storage' => 0.07, 'PSU' => 0.05, 'Case' => 0.05],
                    'workstation' => ['CPU' => 0.35, 'GPU' => 0.25, 'Motherboard' => 0.15, 'RAM' => 0.12, 'Storage' => 0.08, 'PSU' => 0.05],
                    'office' => ['CPU' => 0.30, 'RAM' => 0.20, 'Storage' => 0.20, 'Motherboard' => 0.15, 'PSU' => 0.15],
                ];

                $usageRatios = $ratios[$usage] ?? $ratios['gaming'];
                $criticalCategories = array_keys($usageRatios);

                // Initial selection
                foreach ($criticalCategories as $cat) {
                    if (!isset($categories[$cat]))
                        continue;

                    $targetPrice = $budget * $usageRatios[$cat];
                    $bestMatch = null;

                    // Find product closest to target price but not exceeding too much if budget allows
                    foreach ($categories[$cat] as $p) {
                        if ($p['price'] <= $targetPrice * 1.2) {
                            $bestMatch = $p;
                        }
                    }

                    if ($bestMatch) {
                        $recommendation[$cat] = $bestMatch;
                        $totalPrice += $bestMatch['price'];
                    }
                }

                // Optimization: If over budget, downgrade cheapest components
                while ($totalPrice > $budget && count($recommendation) > 0) {
                    $catToDowngrade = null;
                    $maxCurrentPrice = 0;

                    // Logic to pick which one to downgrade (not the primary one for the usage)
                    foreach ($recommendation as $cat => $p) {
                        if ($catToDowngrade === null || ($p['price'] > 50)) {
                            $catToDowngrade = $cat;
                        }
                    }

                    if ($catToDowngrade) {
                        $currentIdx = 0;
                        foreach ($categories[$recommendation[$catToDowngrade]['category_name']] as $idx => $p) {
                            if ($p['id'] == $recommendation[$catToDowngrade]['id']) {
                                $currentIdx = $idx;
                                break;
                            }
                        }

                        if ($currentIdx > 0) {
                            $cheaper = $categories[$recommendation[$catToDowngrade]['category_name']][$currentIdx - 1];
                            $totalPrice -= $recommendation[$catToDowngrade]['price'];
                            $recommendation[$catToDowngrade] = $cheaper;
                            $totalPrice += $cheaper['price'];
                        } else {
                            // Can't downgrade further, stop
                            break;
                        }
                    } else {
                        break;
                    }
                }

                // AI Explanation generation
                if ($usage === 'gaming') {
                    $explanation = "For your Gaming build, I prioritized the GPU and CPU to ensure maximum frame rates. ";
                    if (isset($recommendation['GPU']))
                        $explanation .= "The " . $recommendation['GPU']['name'] . " will handle the heavy lifting for 1440p/4K gaming. ";
                } else if ($usage === 'workstation') {
                    $explanation = "For this Workstation setup, I focused on a high-thread-count CPU and sufficient RAM for multitasking and rendering. ";
                } else {
                    $explanation = "I've selected reliable and efficient components perfect for productivity and daily tasks while staying well within your budget. ";
                }

                $explanation .= "This build comes to a total of $" . number_format($totalPrice, 2) . ".";

                echo json_encode([
                    'success' => true,
                    'recommendation' => array_values($recommendation),
                    'total_price' => $totalPrice,
                    'explanation' => $explanation,
                    'usage' => $usage,
                    'budget' => $budget
                ]);
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