<?php
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Получаем товары в корзине
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.stock, p.image_path 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    header("Location: cart.php");
    exit;
}

// Проверяем наличие
$error = '';
foreach ($cartItems as $item) {
    if ($item['quantity'] > $item['stock']) {
        $error = "Товара «{$item['name']}» недостаточно на складе. Доступно: {$item['stock']} шт.";
        break;
    }
}

// Получаем пункты выдачи
$pickupPoints = $pdo->query("SELECT * FROM pickup_points ORDER BY address")->fetchAll();

// Если пунктов выдачи нет — добавляем тестовые
if (empty($pickupPoints)) {
    $pdo->exec("INSERT INTO pickup_points (address, work_hours) VALUES 
        ('ул. Ленина, 10, офис 5', '10:00-20:00'),
        ('пр. Мира, 45, ТЦ \"Солнечный\"', '09:00-21:00'),
        ('ул. Гагарина, 8', '10:00-18:00')
    ");
    $pickupPoints = $pdo->query("SELECT * FROM pickup_points ORDER BY address")->fetchAll();
}

$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pickup_id']) && empty($error)) {
    $pickupId = (int)$_POST['pickup_id'];
    
    if ($pickupId <= 0) {
        $error = "Выберите пункт выдачи";
    } else {
        // Проверяем, что пункт выдачи существует
        $stmt = $pdo->prepare("SELECT id FROM pickup_points WHERE id = ?");
        $stmt->execute([$pickupId]);
        if (!$stmt->fetch()) {
            $error = "Выберите пункт выдачи";
        } else {
            // Создаём заказ
            $pdo->beginTransaction();
            try {
                // Финальная проверка остатков
                $finalTotal = 0;
                foreach ($cartItems as $item) {
                    if ($item['quantity'] > $item['stock']) {
                        throw new Exception("Товара «{$item['name']}» недостаточно на складе. Доступно: {$item['stock']} шт.");
                    }
                    $finalTotal += $item['price'] * $item['quantity'];
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, total_price, pickup_id, status, created_at) 
                    VALUES (?, ?, ?, 'new', NOW())
                ");
                $stmt->execute([$userId, $finalTotal, $pickupId]);
                $orderId = $pdo->lastInsertId();
                
                // Переносим товары из корзины в order_items и списываем со склада
                $stmtItem = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price) 
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
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$userId]);
                
                $pdo->commit();
                
                header("Location: success.php?order_id=" . $orderId);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа — TEAGReen</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .checkout-page { padding: 120px 0 60px; background: #f9f6f0; min-height: 100vh; }
        .checkout-container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
        .checkout-container h1 { font-size: 36px; color: var(--accent); margin-bottom: 10px; text-align: center; }
        .checkout-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 30px; }
        .checkout-form { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .checkout-form h2 { font-size: 22px; color: var(--accent); margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 5px; color: #333; }
        .form-group select, .form-group input { width: 100%; padding: 14px 18px; border: 2px solid #e0e0e0; border-radius: 16px; font-size: 16px; transition: 0.3s; box-sizing: border-box; background: white; }
        .form-group select:focus, .form-group input:focus { outline: none; border-color: var(--accent); }
        .order-summary { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); height: fit-content; }
        .order-summary h2 { font-size: 22px; color: var(--accent); margin-bottom: 20px; }
        .order-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .order-item .name { color: #333; }
        .order-item .qty { color: #999; font-size: 14px; }
        .order-item .price { font-weight: 600; color: var(--accent); }
        .order-total { display: flex; justify-content: space-between; padding-top: 20px; margin-top: 20px; border-top: 2px solid var(--accent); font-size: 20px; font-weight: 700; }
        .order-total .total-label { color: #333; }
        .order-total .total-price { color: var(--accent); }
        .btn-submit { width: 100%; padding: 16px; background: var(--accent); color: white; border: none; border-radius: 16px; font-size: 18px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 20px; }
        .btn-submit:hover { background: #472a4a; transform: translateY(-2px); }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        .error-message { background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        .back-link { display: inline-block; margin-top: 20px; color: var(--accent); text-decoration: none; font-weight: 500; }
        .pickup-option { display: flex; align-items: center; padding: 15px; border: 2px solid #eee; border-radius: 12px; cursor: pointer; transition: all 0.2s; margin-bottom: 10px; }
        .pickup-option:hover { border-color: var(--accent); background: #f9f6f0; }
        .pickup-option input[type="radio"] { margin-right: 15px; width: 20px; height: 20px; flex-shrink: 0; }
        .pickup-option.selected { border-color: var(--accent); background: #f9f6f0; }
        .pickup-option .address { font-weight: 500; }
        .pickup-option .hours { color: #999; font-size: 14px; }
        @media (max-width: 768px) {
            .checkout-grid { grid-template-columns: 1fr; }
            .order-summary { order: -1; }
        }
    </style>
</head>
<body>

<?php require 'includes/header.php'; ?>

<section class="checkout-page">
    <div class="checkout-container">
        <h1>📋 Оформление заказа</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="checkout-grid">
            <div class="checkout-form">
                <h2>📍 Выберите пункт выдачи</h2>
                <form method="POST" id="checkoutForm">
                    <div class="form-group">
                        <?php foreach ($pickupPoints as $index => $point): ?>
                        <label class="pickup-option <?= $index === 0 ? 'selected' : '' ?>" onclick="selectPickup(this)">
                            <input type="radio" name="pickup_id" value="<?= $point['id'] ?>" <?= $index === 0 ? 'checked' : '' ?> required>
                            <div>
                                <div class="address">📍 <?= htmlspecialchars($point['address']) ?></div>
                                <?php if (!empty($point['work_hours'])): ?>
                                    <div class="hours">🕐 <?= htmlspecialchars($point['work_hours']) ?></div>
                                <?php endif; ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" class="btn-submit" <?= !empty($error) ? 'disabled' : '' ?>>
                        ✅ Подтвердить заказ
                    </button>
                </form>
                <a href="cart.php" class="back-link">← Вернуться в корзину</a>
            </div>
            
            <div class="order-summary">
                <h2>🛒 Ваш заказ</h2>
                <?php foreach ($cartItems as $item): ?>
                <div class="order-item">
                    <span class="name"><?= htmlspecialchars($item['name']) ?></span>
                    <span>
                        <span class="qty">×<?= $item['quantity'] ?></span>
                        <span class="price"><?= number_format($item['price'] * $item['quantity'], 0, '.', ' ') ?> ₽</span>
                    </span>
                </div>
                <?php endforeach; ?>
                <div class="order-total">
                    <span class="total-label">Итого</span>
                    <span class="total-price"><?= number_format($total, 0, '.', ' ') ?> ₽</span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>

<script>
function selectPickup(element) {
    document.querySelectorAll('.pickup-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
}

document.addEventListener('DOMContentLoaded', function() {
    const firstOption = document.querySelector('.pickup-option');
    if (firstOption) firstOption.classList.add('selected');
});
</script>
</body>
</html>