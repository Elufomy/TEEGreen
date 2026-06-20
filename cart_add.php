<?php
require 'includes/db.php';

// Отладка: показываем все данные
echo "<pre>";
echo "SESSION: ";
print_r($_SESSION);
echo "\nPOST: ";
print_r($_POST);
echo "</pre>";

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Необходима авторизация']));
}

$productId = (int)$_POST['product_id'];
$quantity = (int)($_POST['quantity'] ?? 1);

echo "Product ID: $productId, Quantity: $quantity\n";

// Проверяем остаток на складе
$stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
$stmt->execute([$productId]);
$stock = $stmt->fetchColumn();

echo "Stock: $stock\n";

if ($stock < $quantity) {
    die(json_encode(['error' => "Недостаточно товара. В наличии: $stock шт."]));
}

// Добавляем в корзину
$stmt = $pdo->prepare("
    INSERT INTO cart (user_id, product_id, quantity) 
    VALUES (?, ?, ?) 
    ON DUPLICATE KEY UPDATE quantity = quantity + ?
");
$stmt->execute([$_SESSION['user_id'], $productId, $quantity, $quantity]);

echo "Success! Rows affected: " . $stmt->rowCount();
?>