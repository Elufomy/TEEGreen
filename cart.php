<?php
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Получаем товары в корзине
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image_path, p.stock 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина — TEAGReen</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cart-page { padding: 120px 0 60px; background: #f9f6f0; min-height: 100vh; }
        .cart-container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
        .cart-container h1 { font-size: 36px; color: var(--accent); margin-bottom: 10px; }
        .cart-container .sub { color: #666; margin-bottom: 30px; }
        .cart-grid { display: flex; flex-direction: column; gap: 20px; }
        .cart-item { background: white; border-radius: 20px; padding: 20px; display: flex; gap: 20px; align-items: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .cart-item img { width: 100px; height: 100px; object-fit: cover; border-radius: 15px; }
        .cart-item-info { flex: 1; }
        .cart-item-info h3 { margin: 0 0 5px 0; font-size: 18px; color: #333; }
        .cart-item-info .price { font-size: 20px; font-weight: 700; color: var(--accent); }
        .cart-item-actions { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .qty-btn { width: 36px; height: 36px; border-radius: 50%; border: 1px solid #ddd; background: white; cursor: pointer; font-size: 18px; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
        .qty-btn:hover { background: var(--accent); color: white; border-color: var(--accent); }
        .qty-input { width: 50px; text-align: center; font-size: 16px; border: 1px solid #ddd; border-radius: 8px; padding: 8px; }
        .remove-btn { background: none; border: none; color: #dc2626; cursor: pointer; font-size: 20px; transition: 0.3s; padding: 8px; }
        .remove-btn:hover { transform: scale(1.2); }
        .cart-summary { background: white; border-radius: 20px; padding: 30px; margin-top: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .cart-summary .total-label { font-size: 18px; color: #666; }
        .cart-summary .total-price { font-size: 32px; font-weight: 700; color: var(--accent); }
        .btn-checkout { padding: 16px 40px; background: var(--accent); color: white; border: none; border-radius: 16px; font-size: 18px; font-weight: 600; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; }
        .btn-checkout:hover { background: #472a4a; transform: translateY(-2px); }
        .btn-checkout:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        .empty-cart { text-align: center; padding: 60px 0; }
        .empty-cart .icon { font-size: 80px; margin-bottom: 20px; }
        .empty-cart h2 { color: #666; margin-bottom: 20px; }
        .empty-cart a { color: var(--accent); text-decoration: none; font-weight: 600; }
        .btn-catalog { display: inline-block; padding: 12px 30px; background: var(--accent); color: white; text-decoration: none; border-radius: 30px; margin-top: 15px; }
        @media (max-width: 768px) {
            .cart-item { flex-wrap: wrap; }
            .cart-item img { width: 80px; height: 80px; }
            .cart-item-actions { width: 100%; justify-content: flex-start; }
            .cart-summary { flex-direction: column; gap: 20px; text-align: center; }
            .btn-checkout { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>

<?php require 'includes/header.php'; ?>

<section class="cart-page">
    <div class="cart-container">
        <h1>🛒 Корзина</h1>
        <p class="sub">Ваши выбранные товары</p>
        
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <div class="icon">🛍️</div>
                <h2>Корзина пуста</h2>
                <p>Добавьте вкусный чай из нашего каталога!</p>
                <a href="catalog.php" class="btn-catalog">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <div class="cart-grid">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item" id="item-<?= $item['cart_id'] ?>">
                    <?php if (!empty($item['image_path'])): ?>
                        <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    <?php else: ?>
                        <img src="https://picsum.photos/seed/<?= $item['product_id'] ?>/100/100" alt="<?= htmlspecialchars($item['name']) ?>">
                    <?php endif; ?>
                    
                    <div class="cart-item-info">
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="price"><?= number_format($item['price'], 0, '.', ' ') ?> ₽</p>
                    </div>
                    
                    <div class="cart-item-actions">
                        <button class="qty-btn" onclick="updateQuantity(<?= $item['cart_id'] ?>, -1)">−</button>
                        <input type="number" class="qty-input" id="qty-<?= $item['cart_id'] ?>" value="<?= $item['quantity'] ?>" min="1" readonly>
                        <button class="qty-btn" onclick="updateQuantity(<?= $item['cart_id'] ?>, 1)">+</button>
                        <button class="remove-btn" onclick="removeItem(<?= $item['cart_id'] ?>)">🗑️</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cart-summary">
                <div>
                    <span class="total-label">Итого:</span>
                    <span class="total-price" id="cart-total"><?= number_format($total, 0, '.', ' ') ?> ₽</span>
                </div>
                <a href="checkout.php" class="btn-checkout">Оформить заказ →</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>

<script src="js/js/jquery-4.0.0.min.js"></script>
<script>
function updateQuantity(cartId, delta) {
    var input = document.getElementById('qty-' + cartId);
    var newQty = parseInt(input.value) + delta;
    if (newQty < 1) return;
    
    $.post('cart_update.php', { cart_id: cartId, quantity: newQty }, function(response) {
        try {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка обновления');
            }
        } catch(e) {
            location.reload();
        }
    });
}

function removeItem(cartId) {
    if (!confirm('Удалить товар из корзины?')) return;
    
    $.post('cart_update.php', { cart_id: cartId, quantity: 0 }, function(response) {
        try {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка удаления');
            }
        } catch(e) {
            location.reload();
        }
    });
}
</script>
</body>
</html>