<?php
require 'includes/db.php';

// Если не авторизован — отправляем на вход
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Получаем товары корзины
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image_path 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

// Считаем общую сумму
$totalPrice = 0;
foreach ($cartItems as $item) {
    $totalPrice += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина - TEAGReen</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Базовые стили для корзины, чтобы сразу выглядело нормально */
        .cart-container { max-width: 900px; margin: 50px auto; padding: 20px; }
        .cart-item { display: flex; align-items: center; border-bottom: 1px solid #eee; padding: 20px 0; gap: 20px; }
        .cart-item img { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; }
        .cart-item-info { flex-grow: 1; }
        .cart-item-info h3 { margin: 0 0 5px 0; font-size: 18px; }
        .cart-item-info .price { color: #4CAF50; font-weight: bold; font-size: 18px; }
        .cart-controls { display: flex; align-items: center; gap: 10px; }
        .btn-qty { width: 30px; height: 30px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 4px; font-size: 18px; }
        .btn-qty:hover { background: #f5f0e8; }
        .qty-display { font-weight: bold; min-width: 20px; text-align: center; }
        .btn-remove { color: #f44336; background: none; border: none; cursor: pointer; font-size: 14px; margin-left: 10px; }
        .cart-summary { margin-top: 30px; text-align: right; font-size: 20px; }
        .cart-summary strong { font-size: 24px; color: #333; }
        .btn-checkout { display: inline-block; background: #4CAF50; color: white; padding: 12px 30px; text-decoration: none; border-radius: 4px; margin-top: 20px; font-size: 18px; }
        .empty-cart { text-align: center; padding: 50px; color: #666; }
    </style>
</head>
<body>

    <!-- Меню (упрощенное для примера) -->
    <nav style="background: white; padding: 20px; text-align: center;">
        <a href="index.php">На главную</a> | <a href="catalog.php">Каталог</a>
    </nav>

    <div class="cart-container">
        <h1>Ваша корзина</h1>

        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <h2>Корзина пуста 😔</h2>
                <p>Добавьте вкусный чай из нашего каталога!</p>
                <a href="catalog.php" class="btn-checkout" style="background: #2196F3;">Перейти в каталог</a>
            </div>
        <?php else: ?>
            
            <div id="cart-items">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item" data-product-id="<?= $item['product_id'] ?>">
                    <img src="<?= !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : 'https://picsum.photos/seed/'.$item['product_id'].'/100/100' ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    
                    <div class="cart-item-info">
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="price"><?= number_format($item['price'], 0, '.', ' ') ?> ₽</p>
                    </div>

                    <div class="cart-controls">
                        <button class="btn-qty btn-minus">−</button>
                        <span class="qty-display"><?= $item['quantity'] ?></span>
                        <button class="btn-qty btn-plus">+</button>
                        <button class="btn-remove btn-delete">Удалить</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <p>Итого: <strong id="total-price"><?= number_format($totalPrice, 0, '.', ' ') ?> ₽</strong></p>
                <a href="checkout.php" class="btn-checkout">Оформить заказ</a>
            </div>

        <?php endif; ?>
    </div>

    <!-- Подключаем jQuery -->
    <script src="js/js/jquery-4.0.0.min.js"></script>
    <script>
    $(document).ready(function() {
        
        // Функция обновления корзины
        function updateCart(productId, action) {
            $.post('cart_update.php', { product_id: productId, action: action }, function(response) {
                if (response.error) {
                    alert(response.error);
                } else {
                    // Обновляем общую сумму
                    $('#total-price').text(Number(response.total).toLocaleString('ru-RU') + ' ₽');
                    
                    // Если действие "delete" или количество стало 0 — перезагружаем страницу для простоты
                    if (action === 'delete' || $('.cart-item[data-product-id="'+productId+'"] .qty-display').text() == 0) {
                        location.reload();
                    }
                }
            }, 'json');
        }

        // Клик на "+"
        $('.btn-plus').click(function() {
            var productId = $(this).closest('.cart-item').data('product-id');
            var qtySpan = $(this).siblings('.qty-display');
            var newQty = parseInt(qtySpan.text()) + 1;
            
            // Оптимистичное обновление UI
            qtySpan.text(newQty);
            updateCart(productId, 'add');
        });

        // Клик на "-"
        $('.btn-minus').click(function() {
            var productId = $(this).closest('.cart-item').data('product-id');
            var qtySpan = $(this).siblings('.qty-display');
            var currentQty = parseInt(qtySpan.text());
            
            if (currentQty > 1) {
                qtySpan.text(currentQty - 1);
                updateCart(productId, 'remove');
            }
        });

        // Клик на "Удалить"
        $('.btn-delete').click(function() {
            if (confirm('Удалить этот товар из корзины?')) {
                var productId = $(this).closest('.cart-item').data('product-id');
                $(this).closest('.cart-item').fadeOut(300, function() {
                    updateCart(productId, 'delete');
                });
            }
        });

    });
    </script>
</body>
</html>