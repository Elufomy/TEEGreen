<?php
require 'includes/db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Получаем товары корзины
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.stock, p.image_path 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

// Если корзина пуста — редирект
if (empty($cartItems)) {
    header("Location: cart.php");
    exit;
}

// Считаем общую сумму
$totalPrice = 0;
foreach ($cartItems as $item) {
    $totalPrice += $item['price'] * $item['quantity'];
}

// Получаем пункты выдачи
$pickupPoints = $pdo->query("SELECT * FROM pickup_points ORDER BY id")->fetchAll();

// Если пунктов выдачи нет — добавляем тестовые
if (empty($pickupPoints)) {
    $pdo->exec("INSERT INTO pickup_points (address) VALUES 
        ('ул. Ленина, 10, офис 5'),
        ('пр. Мира, 45, ТЦ \"Солнечный\"'),
        ('ул. Гагарина, 8')
    ");
    $pickupPoints = $pdo->query("SELECT * FROM pickup_points")->fetchAll();
}

// Обработка оформления заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickupId = (int)$_POST['pickup_id'];
    
    if ($pickupId <= 0) {
        $error = "Выберите пункт выдачи";
    } else {
        try {
            // Начинаем транзакцию
            $pdo->beginTransaction();
            
            // Финальная проверка остатков и пересчёт суммы
            $finalTotal = 0;
            foreach ($cartItems as $item) {
                if ($item['quantity'] > $item['stock']) {
                    throw new Exception("Товар «{$item['name']}» закончился. Доступно: {$item['stock']} шт.");
                }
                $finalTotal += $item['price'] * $item['quantity'];
            }
            
            // Создаём заказ
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, pickup_id, total_price, status, created_at) 
                VALUES (?, ?, ?, 'new', NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $pickupId, $finalTotal]);
            $orderId = $pdo->lastInsertId();
            
            // Добавляем товары в заказ и списываем со склада
            $stmtItem = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) 
                VALUES (?, ?, ?, ?)
            ");
            $stmtStock = $pdo->prepare("
                UPDATE products SET stock = stock - ? WHERE id = ?
            ");
            
            foreach ($cartItems as $item) {
                $stmtItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                $stmtStock->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Очищаем корзину
            $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$_SESSION['user_id']]);
            
            // Подтверждаем транзакцию
            $pdo->commit();
            
            // Перенаправляем на страницу успеха
            header("Location: success.php?order_id=$orderId");
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа - TEAGReen</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .checkout-container { max-width: 800px; margin: 50px auto; padding: 20px; }
        .checkout-section { background: white; border-radius: 20px; padding: 30px; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .checkout-section h2 { margin: 0 0 20px 0; color: var(--accent); font-size: 24px; }
        .order-item { display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid #eee; }
        .order-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .order-item-info { flex-grow: 1; }
        .order-item-info h3 { margin: 0 0 5px 0; font-size: 16px; }
        .order-item-info p { margin: 0; color: #666; font-size: 14px; }
        .order-item-price { font-weight: bold; color: var(--accent); }
        .pickup-options { display: flex; flex-direction: column; gap: 10px; }
        .pickup-option { display: flex; align-items: center; padding: 15px; border: 2px solid #eee; border-radius: 12px; cursor: pointer; transition: all 0.2s; }
        .pickup-option:hover { border-color: var(--accent); background: #f9f6f0; }
        .pickup-option input[type="radio"] { margin-right: 15px; width: 20px; height: 20px; }
        .pickup-option.selected { border-color: var(--accent); background: #f9f6f0; }
        .order-total { font-size: 24px; font-weight: bold; text-align: right; margin: 20px 0; }
        .btn-checkout { width: 100%; background: var(--accent); color: white; padding: 15px; border: none; border-radius: 30px; font-size: 18px; font-weight: bold; cursor: pointer; }
        .btn-checkout:hover { opacity: 0.9; }
        .error { background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .back-link { display: inline-block; margin-top: 15px; color: var(--accent); text-decoration: none; }
    </style>
</head>
<body>

    <!-- Меню -->
    <section class="hero" style="height: auto; min-height: 100vh; background: #f9f6f0;">
        <div class="container" style="height: auto; padding-top: 120px;">
            
            <div class="menu-block" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); width: 1250px; max-width: 90%; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 40px; padding: 0 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); z-index: 1000;">
                <nav class="main-menu">
                    <ul>
                        <li><a href="index.php">Главная</a></li>
                        <li><a href="catalog.php">Каталог</a></li>
                        <li><a href="#contacts">Контакты</a></li>
                    </ul>
                </nav>
            </div>
            
            <div class="checkout-container">
                <h1 style="text-align: center; margin-bottom: 30px; color: var(--accent);">Оформление заказа</h1>
                
                <?php if (isset($error)): ?>
                    <div class="error">❌ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <!-- Товары -->
                <div class="checkout-section">
                    <h2>📦 Ваш заказ</h2>
                    
                    <?php foreach ($cartItems as $item): ?>
                    <div class="order-item">
                        <?php if (!empty($item['image_path'])): ?>
                            <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                        <?php else: ?>
                            <img src="https://picsum.photos/seed/<?= $item['product_id'] ?>/100/100" alt="<?= htmlspecialchars($item['name']) ?>">
                        <?php endif; ?>
                        
                        <div class="order-item-info">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p><?= $item['quantity'] ?> шт. × <?= number_format($item['price'], 0, '.', ' ') ?> ₽</p>
                        </div>
                        
                        <div class="order-item-price">
                            <?= number_format($item['price'] * $item['quantity'], 0, '.', ' ') ?> ₽
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="order-total">
                        Итого: <?= number_format($totalPrice, 0, '.', ' ') ?> ₽
                    </div>
                </div>
                
                <!-- Пункты выдачи -->
                <form method="POST">
                    <div class="checkout-section">
                        <h2>📍 Выберите пункт выдачи</h2>
                        
                        <div class="pickup-options">
                            <?php foreach ($pickupPoints as $point): ?>
                            <label class="pickup-option" onclick="selectPickup(this)">
                                <input type="radio" name="pickup_id" value="<?= $point['id'] ?>" <?= $point['id'] == ($pickupPoints[0]['id'] ?? 0) ? 'checked' : '' ?> required>
                                <div>
                                    <strong>Пункт выдачи #<?= $point['id'] ?></strong><br>
                                    <span style="color: #666;"><?= htmlspecialchars($point['address']) ?></span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-checkout">
                        ✅ Подтвердить заказ
                    </button>
                </form>
                
                <a href="cart.php" class="back-link">← Вернуться в корзину</a>
            </div>
            
        </div>
    </section>

    <script>
    function selectPickup(element) {
        document.querySelectorAll('.pickup-option').forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
    }
    
    // Выделяем первый пункт при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        const firstOption = document.querySelector('.pickup-option');
        if (firstOption) firstOption.classList.add('selected');
    });
    </script>
</body>
</html>