<?php
require 'includes/db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Необходима авторизация']));
}

$productId = (int)$_POST['product_id'];
$quantity = (int)($_POST['quantity'] ?? 1);

// Проверяем остаток на складе
$stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
$stmt->execute([$productId]);
$stock = $stmt->fetchColumn();

if ($stock < $quantity) {
    die(json_encode(['error' => "Недостаточно товара. В наличии: $stock шт."]));
}

$stmt = $pdo->prepare("
    INSERT INTO cart (user_id, product_id, quantity) 
    VALUES (?, ?, ?) 
    ON DUPLICATE KEY UPDATE quantity = quantity + ?
");
$stmt->execute([$_SESSION['user_id'], $productId, $quantity, $quantity]);

echo json_encode(['success' => true]);
?>