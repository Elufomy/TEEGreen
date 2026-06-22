<?php
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    header("Location: index.php");
    exit;
}

// Получаем данные заказа
$stmt = $pdo->prepare("
    SELECT o.*, p.address as pickup_address 
    FROM orders o 
    LEFT JOIN pickup_points p ON o.pickup_id = p.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ оформлен! - TEAGReen</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .success-container { max-width: 600px; margin: 100px auto; padding: 40px; text-align: center; background: white; border-radius: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.1); }
        .success-icon { font-size: 80px; margin-bottom: 20px; }
        .success-container h1 { color: var(--accent); margin-bottom: 20px; }
        .order-info { background: #f9f6f0; padding: 20px; border-radius: 20px; margin: 30px 0; text-align: left; }
        .order-info p { margin: 10px 0; }
        .btn-home { display: inline-block; background: var(--accent); color: white; padding: 15px 40px; text-decoration: none; border-radius: 30px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body style="background: #f9f6f0;">
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h1>Заказ успешно оформлен!</h1>
        <p style="font-size: 18px; color: #666;">Номер вашего заказа: <strong>#<?= $order['id'] ?></strong></p>
        
        <div class="order-info">
            <p><strong>📦 Сумма заказа:</strong> <?= number_format($order['total_price'], 0, '.', ' ') ?> ₽</p>
            <p><strong>📍 Пункт выдачи:</strong> <?= htmlspecialchars($order['pickup_address']) ?></p>
            <p><strong>📅 Дата заказа:</strong> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
            <p><strong>📊 Статус:</strong> 
                <?php
                $statuses = [
                    'new' => 'Новый',
                    'processing' => 'В обработке',
                    'ready' => 'Готов к выдаче',
                    'completed' => 'Выполнен'
                ];
                echo $statuses[$order['status']] ?? $order['status'];
                ?>
            </p>
        </div>
        
        <p style="color: #666; margin: 30px 0;">Мы отправили подтверждение на вашу почту.</p>
        
        <a href="index.php" class="btn-home">Вернуться на главную</a>
        <a href="my_orders.php" class="btn-home" style="background: #FF9800; margin-left: 10px;">Мои заказы</a>
    </div>
</body>
</html>