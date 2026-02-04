<?php
require 'db.php';

try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in users table: " . implode(", ", $columns) . "\n";

    if (!in_array('role', $columns)) {
        echo "MISSING 'role' column!\n";
    } else {
        echo "'role' column exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>