<?php
require 'includes/db.php';

// Получаем ID товара из URL
$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    header("Location: catalog.php");
    exit;
}

// Получаем данные товара с категорией
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: catalog.php");
    exit;
}

// Получаем похожие товары из той же категории (до 4 штук)
$stmtRelated = $pdo->prepare("
    SELECT * FROM products 
    WHERE category_id = ? AND id != ? AND stock > 0
    ORDER BY id DESC 
    LIMIT 4
");
$stmtRelated->execute([$product['category_id'], $productId]);
$relatedProducts = $stmtRelated->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> — TEAGReen</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    
    <section class="hero" style="height: auto; min-height: 100vh; background: #ffffff;">
        <div class="container" style="height: auto; padding-top: 120px;">
            
            <div class="menu-block" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); width: 1250px; max-width: 90%; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 40px; padding: 0 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); z-index: 1000;">
                <nav class="main-menu">
                    <ul>
                        <li><a href="index.php">Главная</a></li>
                        <li><a href="catalog.php">Каталог</a></li>
                        <li><a href="my_orders.php">Мои заказы</a></li>
                        <li><a href="#contacts">Контакты</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="cart.php">Корзина</a></li>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <li><a href="admin/index.php">Админка</a></li>
                            <?php endif; ?>
                            <li><a href="logout.php">Выход</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Вход</a></li>
                            <li><a href="register.php">Регистрация</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            
            <div style="max-width: 1000px; margin: 0 auto 20px; padding: 0 20px; color: #999; font-size: 14px;">
                <a href="index.php" style="color: #999; text-decoration: none;">Главная</a> → 
                <a href="catalog.php" style="color: #999; text-decoration: none;">Каталог</a> → 
                <?php if ($product['category_id']): ?>
                    <a href="catalog.php?category=<?= $product['category_id'] ?>" style="color: #999; text-decoration: none;"><?= htmlspecialchars($product['category_name']) ?></a> → 
                <?php endif; ?>
                <span style="color: #333;"><?= htmlspecialchars($product['name']) ?></span>
            </div>
            
            <div style="max-width: 1000px; margin: 0 auto; background: white; border-radius: 40px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.1); display: flex; gap: 40px; flex-wrap: wrap; position: relative;">
                
                <?php if ($product['is_new']): ?>
                    <div style="position: absolute; top: 30px; right: 30px; background: #ff5722; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; font-size: 14px; z-index: 10;">NEW</div>
                <?php endif; ?>
                
                <div style="flex: 1; min-width: 280px;">
                    <?php if (!empty($product['image_path'])): ?>
                        <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 100%; border-radius: 30px; object-fit: cover;">
                    <?php else: ?>
                        <img src="https://picsum.photos/seed/<?= $product['id'] ?>/500/500" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 100%; border-radius: 30px; object-fit: cover;">
                    <?php endif; ?>
                </div>
                
                <div style="flex: 1; min-width: 280px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <p style="font-size: 14px; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;"><?= htmlspecialchars($product['category_name'] ?? 'Без категории') ?></p>
                        
                        <h1 style="font-size: 36px; color: var(--accent); margin-bottom: 15px;"><?= htmlspecialchars($product['name']) ?></h1>
                        
                        <?php if (!empty($product['description'])): ?>
                            <p style="font-size: 18px; color: #666; margin-bottom: 20px; line-height: 1.6;"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        <?php endif; ?>
                        
                        <p style="font-size: 32px; font-weight: 700; color: var(--accent); margin-bottom: 10px;"><?= number_format($product['price'], 0, '.', ' ') ?> ₽ / 50г</p>
                        
                        <p style="font-size: 14px; margin-bottom: 30px; <?= $product['stock'] > 0 ? 'color: #4CAF50;' : 'color: #f44336;' ?>">
                            <?php if ($product['stock'] > 0): ?>
                                ✓ В наличии: <?= $product['stock'] ?> шт.
                            <?php else: ?>
                                ✗ Нет в наличии
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <?php if ($product['stock'] > 0): ?>
    <?php if (isset($_SESSION['user_id'])): ?>
        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; border: 1px solid #ddd; border-radius: 30px; overflow: hidden;">
                <button type="button" onclick="changeQty(-1)" style="width: 40px; height: 40px; background: white; border: none; cursor: pointer; font-size: 20px;">−</button>
                <input type="number" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>" readonly style="width: 50px; height: 40px; text-align: center; border: none; border-left: 1px solid #ddd; border-right: 1px solid #ddd; font-size: 16px;">
                <button type="button" onclick="changeQty(1)" style="width: 40px; height: 40px; background: white; border: none; cursor: pointer; font-size: 20px;">+</button>
            </div>
            
            <button class="btn" onclick="addToCart(<?= $product['id'] ?>)" style="max-width: 300px;">В корзину</button>
        </div>
    <?php else: ?>
        <div style="background: #fff3cd; padding: 20px; border-radius: 20px; text-align: center;">
            <a href="login.php" style="color: var(--accent); font-weight: bold; text-decoration: none;">Войдите в аккаунт</a>, чтобы добавить товар в корзину
        </div>
    <?php endif; ?>
<?php else: ?>
    <button class="btn" disabled style="max-width: 300px; background: #ccc; cursor: not-allowed;">Товар закончился</button>
<?php endif; ?>
                    
                    <a href="catalog.php" style="display: inline-block; margin-top: 15px; color: var(--accent); text-decoration: none; font-weight: 500;">← Назад в каталог</a>
                </div>
            </div>
            
            <?php if (!empty($relatedProducts)): ?>
            <div style="max-width: 1000px; margin: 60px auto 0; padding: 0 20px;">
                <h2 style="text-align: center; font-size: 28px; margin-bottom: 30px; color: var(--accent);">Похожие товары</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
                    <?php foreach ($relatedProducts as $related): ?>
                    <a href="product.php?id=<?= $related['id'] ?>" style="text-decoration: none; color: inherit; background: white; border-radius: 30px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: transform 0.2s; display: block;">
                        <?php if (!empty($related['image_path'])): ?>
                            <img src="<?= htmlspecialchars($related['image_path']) ?>" alt="<?= htmlspecialchars($related['name']) ?>" style="width: 100%; height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <img src="https://picsum.photos/seed/<?= $related['id'] ?>/300/260" alt="<?= htmlspecialchars($related['name']) ?>" style="width: 100%; height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        <div style="padding: 20px;">
                            <h3 style="margin: 0 0 10px 0; font-size: 16px;"><?= htmlspecialchars($related['name']) ?></h3>
                            <p style="color: var(--accent); font-weight: bold; font-size: 18px; margin: 0;"><?= number_format($related['price'], 0, '.', ' ') ?> ₽</p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </section>
    
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

    <script src="js/js/jquery-4.0.0.min.js"></script>
    <script>
    // Изменение количества
    function changeQty(delta) {
        var input = document.getElementById('quantity');
        var newVal = parseInt(input.value) + delta;
        var max = parseInt(input.max);
        
        if (newVal >= 1 && newVal <= max) {
            input.value = newVal;
        }
    }
    
    // Добавление в корзину
    function addToCart(productId) {
        var quantity = parseInt(document.getElementById('quantity').value);
        
        $.post('cart_add.php', { product_id: productId, quantity: quantity }, function(response) {
            try {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.error) {
                    alert('❌ ' + data.error);
                } else {
                    alert('✅ Товар добавлен в корзину!');
                }
            } catch(e) {
                alert('✅ Товар добавлен в корзину!');
            }
        }).fail(function() {
            alert('Ошибка при добавлении в корзину');
        });
    }
    </script>
</body>
</html>