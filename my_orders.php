<?php
require 'includes/db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Получаем все заказы пользователя
$stmt = $pdo->prepare("
    SELECT 
        o.id AS order_id,
        o.total_price,
        o.status,
        o.created_at,
        pp.address AS pickup_address
    FROM orders o
    LEFT JOIN pickup_points pp ON o.pickup_id = pp.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// Для каждого заказа получаем состав товаров
foreach ($orders as &$order) {
    $stmtItems = $pdo->prepare("
        SELECT oi.quantity, oi.price_at_purchase, p.name, p.image_path
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmtItems->execute([$order['order_id']]);
    $order['items'] = $stmtItems->fetchAll();
}

// Словарь статусов
$statusInfo = [
    'new'        => ['name' => 'Новый', 'color' => '#2196F3', 'icon' => '🆕'],
    'processing' => ['name' => 'В обработке', 'color' => '#FF9800', 'icon' => ''],
    'ready'      => ['name' => 'Готов к выдаче', 'color' => '#4CAF50', 'icon' => '✅'],
    'completed'  => ['name' => 'Выполнен', 'color' => '#9E9E9E', 'icon' => '🏁']
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заказы - TEAGReen</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .orders-page { max-width: 900px; margin: 40px auto; padding: 20px; }
        .orders-page h1 { text-align: center; color: var(--accent); margin-bottom: 30px; font-size: 32px; }
        .order-card { background: white; border-radius: 30px; padding: 30px; margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .order-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px solid #f5f0e8; }
        .order-number { font-size: 22px; font-weight: bold; color: var(--accent); }
        .order-date { color: #999; font-size: 14px; }
        .order-status { padding: 8px 20px; border-radius: 20px; color: white; font-weight: bold; font-size: 14px; }
        .order-info { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px; color: #666; font-size: 14px; }
        .order-info span { display: flex; align-items: center; gap: 5px; }
        .order-items { margin-bottom: 20px; }
        .order-item { display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px dashed #eee; }
        .order-item:last-child { border-bottom: none; }
        .order-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 12px; }
        .order-item-info { flex-grow: 1; }
        .order-item-info h3 { margin: 0 0 5px 0; font-size: 16px; color: #333; }
        .order-item-info p { margin: 0; color: #999; font-size: 13px; }
        .order-item-price { font-weight: bold; color: var(--accent); font-size: 16px; white-space: nowrap; }
        .order-total { text-align: right; font-size: 20px; font-weight: bold; color: var(--accent); padding-top: 15px; border-top: 2px solid #f5f0e8; }
        .empty-orders { text-align: center; padding: 60px 20px; background: white; border-radius: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .empty-orders h2 { color: #999; margin-bottom: 15px; }
        .empty-orders p { color: #bbb; margin-bottom: 25px; }
        .btn-shop { display: inline-block; background: var(--accent); color: white; padding: 12px 30px; border-radius: 30px; text-decoration: none; font-weight: bold; }
        .back-link { display: block; text-align: center; margin-top: 30px; color: var(--accent); text-decoration: none; font-weight: 500; }
        @media (max-width: 600px) {
            .order-header { flex-direction: column; align-items: flex-start; }
            .order-item { flex-wrap: wrap; }
        }
    </style>
</head>
<body style="background: #f9f6f0;">

    <!-- Меню -->
    <section class="hero" style="height: auto; min-height: 100vh; background: #f9f6f0;">
        <div class="container" style="height: auto; padding-top: 120px;">
            
            <div class="menu-block" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); width: 1250px; max-width: 90%; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 40px; padding: 0 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); z-index: 1000;">
                <nav class="main-menu">
                    <ul>
                        <li><a href="index.php">Главная</a></li>
                        <li><a href="catalog.php">Каталог</a></li>
                        <li><a href="my_orders.php">Мои заказы</a></li>
                        <li><a href="cart.php">Корзина</a></li>
                        <li><a href="#contacts">Контакты</a></li>
                        <li><a href="logout.php">Выход</a></li>
                    </ul>
                </nav>
            </div>
            
            <div class="orders-page">
                <h1> Мои заказы</h1>
                
                <?php if (empty($orders)): ?>
                    <div class="empty-orders">
                        <h2>У вас пока нет заказов</h2>
                        <p>Самое время выбрать вкусный чай!</p>
                        <a href="catalog.php" class="btn-shop">Перейти в каталог</a>
                    </div>
                <?php else: ?>
                    
                    <?php foreach ($orders as $order): 
                        $status = $statusInfo[$order['status']] ?? $statusInfo['new'];
                        $date = date('d.m.Y H:i', strtotime($order['created_at']));
                    ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-number">Заказ #<?= $order['order_id'] ?></span>
                                <span class="order-date"> · <?= $date ?></span>
                            </div>
                            <div class="order-status" style="background: <?= $status['color'] ?>;">
                                <?= $status['icon'] ?> <?= $status['name'] ?>
                            </div>
                        </div>
                        
                        <div class="order-info">
                            <span> <?= htmlspecialchars($order['pickup_address'] ?? 'Адрес не указан') ?></span>
                        </div>
                        
                        <div class="order-items">
                            <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <?php if (!empty($item['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                <?php else: ?>
                                    <img src="https://picsum.photos/seed/<?= $item['name'] ?>/100/100" alt="<?= htmlspecialchars($item['name']) ?>">
                                <?php endif; ?>
                                
                                <div class="order-item-info">
                                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                                    <p><?= $item['quantity'] ?> шт. × <?= number_format($item['price_at_purchase'], 0, '.', ' ') ?> ₽</p>
                                </div>
                                
                                <div class="order-item-price">
                                    <?= number_format($item['price_at_purchase'] * $item['quantity'], 0, '.', ' ') ?> ₽
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-total">
                            Итого: <?= number_format($order['total_price'], 0, '.', ' ') ?> ₽
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                <?php endif; ?>
                
                <a href="index.php" class="back-link">← Вернуться на главную</a>
            </div>
            
        </div>
    </section>
    
    <!-- Подвал -->
    <footer class="footer" id="contacts">
        <div class="footer__inner">
            <div class="footer__columns">
                <div class="footer__column">
                    <h4>TEAGReen</h4>
                    <p>Premium чай для настоящих ценителей. Только лучшие сорта со всего мира.</p>
                </div>
                <div class="footer__column">
                    <h4>Контакты</h4>
                    <p>📞 +7 (999) 123-45-67</p>
                    <p>✉️ info@teagreen.ru</p>
                    <p>📍 Москва, ул. Чайная, 15</p>
                </div>
                <div class="footer__column">
                    <h4>Режим работы</h4>
                    <p>Пн-Пт: 10:00 - 20:00</p>
                    <p>Сб-Вс: 11:00 - 18:00</p>
                </div>
            </div>
            <div class="footer__bottom">
                <p>© 2026 TEAGReen. Все права защищены.</p>
            </div>
        </div>
    </footer>

</body>
</html>