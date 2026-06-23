<?php
require 'includes/db.php';

$stmtCategories = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmtCategories->fetchAll();

$stmtNew = $pdo->query("SELECT * FROM products WHERE is_new = 1 ORDER BY id DESC LIMIT 10");
$newProducts = $stmtNew->fetchAll();

$stmtPopular = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 10");
$popularProducts = $stmtPopular->fetchAll();

$currentUserId = $_SESSION['user_id'] ?? 0;

function getAvailableStock($pdo, $productId, $userId) {
    if ($userId <= 0) {
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        return (int)$stmt->fetchColumn();
    }
    
    $stmt = $pdo->prepare("
        SELECT p.stock, COALESCE(c.quantity, 0) AS in_cart
        FROM products p
        LEFT JOIN cart c ON c.product_id = p.id AND c.user_id = ?
        WHERE p.id = ?
    ");
    $stmt->execute([$userId, $productId]);
    $data = $stmt->fetch();
    
    if (!$data) return 0;
    return (int)$data['stock'] - (int)$data['in_cart'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAGReen — premium чай</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .product-card {
            display: flex !important;
            flex-direction: column !important;
            height: 100% !important;
            position: relative;
        }
        .product-card img {
            height: 200px !important;
            object-fit: cover !important;
            width: 100%;
        }
        .product-card h3 {
            min-height: 48px;
            margin: 15px 15px 5px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-card .price {
            text-align: center;
            margin: 5px 15px 10px;
        }
        .stock-display {
            text-align: center;
            margin: 5px 15px 10px !important;
            font-size: 12px;
        }
        .cart-button-container {
            margin-top: auto;
            padding: 0 15px 15px;
        }
        .cart-button-container .btn {
            width: 100%;
        }
        .badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
    </style>
</head>
<body>
    
    <!-- хиро-->
    <section class="hero">
        <div class="container">
            <div class="menu-block">
                <button class="burger" id="burger">☰</button>
                <nav class="main-menu" id="mainMenu">
                    <ul>
                        <li><a href="#about">О нас</a></li>
                        <li><a href="#popular">Популярные</a></li>
                        <li><a href="#new">Новинки</a></li>
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
            
            <div class="hero_text">
                <h1>TEAGReen</h1>
                <p>premium for everyone</p>
            </div>
        </div>
    </section>
    
    <!-- популярные сорта -->
    <section class="carousel-section" id="popular">
        <div class="container">
            <h2 class="section-title">Популярные сорта</h2>
            
            <div class="swiper productSwiper">
                <div class="swiper-wrapper">
                    
                    <?php foreach ($popularProducts as $product): 
                        $available = getAvailableStock($pdo, $product['id'], $currentUserId);
                    ?>
                    <div class="swiper-slide" data-product-id="<?= $product['id'] ?>">
                        <div class="product-card">
                            <?php if ($product['is_new']): ?>
                                <span class="badge">NEW</span>
                            <?php endif; ?>
                            
                            <a href="product.php?id=<?= $product['id'] ?>" style="text-decoration: none; color: inherit;">
                                <?php if (!empty($product['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <img src="https://picsum.photos/seed/<?= $product['id'] ?>/300/260" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php endif; ?>
                                
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="price"><?= number_format($product['price'], 0, '.', ' ') ?> ₽ / 50г</p>
                            </a>
                            
                            <p class="stock-display" style="color: <?= $available > 0 ? '#4CAF50' : '#f44336' ?>;">
                                <?php if ($available > 0): ?>
                                    ✓ Доступно: <span class="stock-count"><?= $available ?></span> шт.
                                <?php else: ?>
                                    ✗ Нет в наличии
                                <?php endif; ?>
                            </p>
                            
                            <div class="cart-button-container">
                                <?php if ($available > 0 && isset($_SESSION['user_id'])): ?>
                                    <button onclick="addToCart(<?= $product['id'] ?>); return false;" class="btn add-to-cart-btn">В корзину</button>
                                <?php elseif (!isset($_SESSION['user_id'])): ?>
                                    <button onclick="window.location.href='login.php';" class="btn" style="width: 100%; cursor: pointer;">Войти, чтобы купить</button>
                                <?php else: ?>
                                    <button class="btn" disabled style="background: #ccc; cursor: not-allowed; width: 100%;">Товар закончился</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                </div>
                
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="catalog.php" class="btn--secondary">Весь каталог →</a>
            </div>
        </div>
    </section>
    
    <!-- новинки -->
    <section class="carousel-section" id="new" style="background: #f5f0e8;">
        <div class="container">
            <h2 class="section-title">Новинки сезона</h2>
            
            <div class="swiper productSwiper">
                <div class="swiper-wrapper">
                    
                    <?php foreach ($newProducts as $product): 
                        $available = getAvailableStock($pdo, $product['id'], $currentUserId);
                    ?>
                    <div class="swiper-slide" data-product-id="<?= $product['id'] ?>">
                        <div class="product-card product-card--new">
                            <span class="badge">NEW</span>
                            
                            <a href="product.php?id=<?= $product['id'] ?>" style="text-decoration: none; color: inherit;">
                                <?php if (!empty($product['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <img src="https://picsum.photos/seed/<?= $product['id'] ?>/300/260" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php endif; ?>
                                
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="price"><?= number_format($product['price'], 0, '.', ' ') ?> ₽ / 50г</p>
                            </a>
                            
                            <p class="stock-display" style="color: <?= $available > 0 ? '#4CAF50' : '#f44336' ?>;">
                                <?php if ($available > 0): ?>
                                    ✓ Доступно: <span class="stock-count"><?= $available ?></span> шт.
                                <?php else: ?>
                                    ✗ Нет в наличии
                                <?php endif; ?>
                            </p>
                            
                            <div class="cart-button-container">
                                <?php if ($available > 0 && isset($_SESSION['user_id'])): ?>
                                    <button onclick="addToCart(<?= $product['id'] ?>); return false;" class="btn add-to-cart-btn">В корзину</button>
                                <?php elseif (!isset($_SESSION['user_id'])): ?>
                                    <button onclick="window.location.href='login.php';" class="btn" style="width: 100%; cursor: pointer;">Войти, чтобы купить</button>
                                <?php else: ?>
                                    <button class="btn" disabled style="background: #ccc; cursor: not-allowed; width: 100%;">Товар закончился</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                </div>
                
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="catalog.php" class="btn--secondary">Весь каталог →</a>
            </div>
        </div>
    </section>

    <!-- подвал-->
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

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/js/jquery-4.0.0.min.js"></script>
    
    <script>
    // Глобальная функция добавления в корзину
    window.addToCart = function(productId) {
        console.log('Добавляем товар ID:', productId);
        
        const $slide = $('[data-product-id="' + productId + '"]');
        const $stockCount = $slide.find('.stock-count');
        const $stockDisplay = $slide.find('.stock-display');
        const $buttonContainer = $slide.find('.cart-button-container');
        
        $.post('cart_add.php', { product_id: productId, quantity: 1 }, function(response) {
            console.log('Ответ сервера:', response);
            
            try {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (data.error) {
                    alert(' ' + data.error);
                } else {
                    const newAvailable = data.new_available;
                    
                    if (newAvailable > 0) {
                        $stockCount.text(newAvailable);
                    } else {
                        $buttonContainer.html('<button class="btn" disabled style="background: #ccc; cursor: not-allowed; width: 100%;">Товар закончился</button>');
                        $stockDisplay.html('✗ Нет в наличии').css('color', '#f44336');
                    }
                    
                    alert('✅ Товар добавлен в корзину!');
                }
            } catch(e) {
                console.error('Ошибка парсинга:', e);
                alert('✅ Товар добавлен в корзину!');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX ошибка:', status, error);
            alert('Ошибка при добавлении в корзину');
        });
        
        return false;
    };
    </script>
    
</body>
</html>