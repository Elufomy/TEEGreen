<?php
require 'includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Необходима авторизация']);
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($productId <= 0) {
    echo json_encode(['error' => 'Неверный ID товара']);
    exit;
}

// Получаем stock и сколько уже в корзине
$stmt = $pdo->prepare("
    SELECT p.stock, p.name, COALESCE(c.quantity, 0) AS in_cart
    FROM products p
    LEFT JOIN cart c ON c.product_id = p.id AND c.user_id = ?
    WHERE p.id = ?
");
$stmt->execute([$_SESSION['user_id'], $productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['error' => 'Товар не найден']);
    exit;
}

$stock = (int)$product['stock'];
$inCart = (int)$product['in_cart'];
$available = $stock - $inCart; // Сколько ещё можно добавить

if ($available <= 0) {
    echo json_encode(['error' => 'Товар закончился']);
    exit;
}

if ($quantity > $available) {
    echo json_encode(['error' => "Недостаточно товара. Доступно: {$available} шт."]);
    exit;
}

// Добавляем в корзину
$stmt = $pdo->prepare("
    INSERT INTO cart (user_id, product_id, quantity) 
    VALUES (?, ?, ?) 
    ON DUPLICATE KEY UPDATE quantity = quantity + ?
");
$stmt->execute([$_SESSION['user_id'], $productId, $quantity, $quantity]);

// Новое доступное количество
$newAvailable = $available - $quantity;

echo json_encode([
    'success' => true,
    'message' => 'Товар добавлен в корзину',
    'new_available' => $newAvailable,
    'total_in_cart' => $inCart + $quantity
]);
?>