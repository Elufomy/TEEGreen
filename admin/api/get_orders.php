<?php
require '../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещен']);
    exit;
}

// Получаем все заказы с данными пользователя и пункта выдачи
$stmt = $pdo->query("
    SELECT 
        o.id AS order_id,
        o.total_price,
        o.status,
        o.created_at,
        u.login AS user_login,
        pp.address AS pickup_address
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN pickup_points pp ON o.pickup_id = pp.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();

// Для каждого заказа получаем состав товаров
foreach ($orders as &$order) {
    $stmtItems = $pdo->prepare("
        SELECT oi.quantity, oi.price_at_purchase, p.name 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmtItems->execute([$order['order_id']]);
    $order['items'] = $stmtItems->fetchAll();
}

echo json_encode($orders, JSON_UNESCAPED_UNICODE);
?>