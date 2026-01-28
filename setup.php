<?php
require 'db.php';

try {
    $sql = file_get_contents('init.sql');
    $pdo->exec($sql);
    echo "Database initialized successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
