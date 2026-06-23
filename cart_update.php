<?php
// cart_update.php
require 'includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Необходима авторизация']);
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

if ($productId <= 0) {
    echo json_encode(['error' => 'Неверный товар']);
    exit;
}

try {
    // Получаем текущее количество в корзине и остаток на складе
    $stmt = $pdo->prepare("
        SELECT c.quantity AS cart_qty, p.stock AS product_stock 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? AND c.product_id = ?
    ");
    $stmt->execute([$userId, $productId]);
    $data = $stmt->fetch();

    if (!$data) {
        echo json_encode(['error' => 'Товар не найден в корзине']);
        exit;
    }

    $currentQty = (int)$data['cart_qty'];
    $stock = (int)$data['product_stock'];
    $newQty = $currentQty;

    if ($action === 'add') {
        if ($currentQty >= $stock) {
            echo json_encode(['error' => "Нельзя добавить больше. На складе: {$stock} шт."]);
            exit;
        }
        $newQty = $currentQty + 1;
        $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?")
            ->execute([$newQty, $userId, $productId]);
            
    } elseif ($action === 'remove') {
        if ($currentQty <= 1) {
            // Если 1, то удаляем товар из корзины
            $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?")
                ->execute([$userId, $productId]);
            $newQty = 0;
        } else {
            $newQty = $currentQty - 1;
            $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?")
                ->execute([$newQty, $userId, $productId]);
        }
        
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?")
            ->execute([$userId, $productId]);
        $newQty = 0;
    }

    // Пересчитываем общую сумму
    $totalStmt = $pdo->prepare("
        SELECT SUM(c.quantity * p.price) 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $totalStmt->execute([$userId]);
    $total = (float)($totalStmt->fetchColumn() ?: 0);

    echo json_encode([
        'success' => true, 
        'total' => $total, 
        'new_qty' => $newQty,
        'stock' => $stock
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Ошибка: ' . $e->getMessage()]);
}
?>