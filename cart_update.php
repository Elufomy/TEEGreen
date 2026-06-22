<?php
// cart_update.php
require 'includes/db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Необходима авторизация']));
}

$productId = (int)$_POST['product_id'];
$action = $_POST['action']; // 'add', 'remove', 'delete'
$userId = $_SESSION['user_id'];

try {
    if ($action === 'delete') {
        // Полное удаление товара из корзины
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
    } else {
        // Изменение количества
        $change = ($action === 'add') ? 1 : -1;
        
        // Получаем текущее количество
        $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $currentQty = (int)$stmt->fetchColumn();
        $newQty = $currentQty + $change;

        if ($newQty <= 0) {
            // Если количество стало 0 или меньше — удаляем товар
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
        } else {
            // Проверяем, хватит ли товара на складе при добавлении
            if ($action === 'add') {
                $stmtStock = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                $stmtStock->execute([$productId]);
                $stock = (int)$stmtStock->fetchColumn();

                if ($newQty > $stock) {
                    die(json_encode(['error' => 'Товар закончился на складе', 'qty' => $stock]));
                }
            }

            // Обновляем количество
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$newQty, $userId, $productId]);
        }
    }

    // Считаем новую общую сумму корзины для ответа
    $stmtTotal = $pdo->prepare("
        SELECT SUM(c.quantity * p.price) 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmtTotal->execute([$userId]);
    $total = $stmtTotal->fetchColumn() ?: 0;

    echo json_encode(['success' => true, 'total' => $total]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>