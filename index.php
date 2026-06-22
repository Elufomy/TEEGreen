<?php
require 'includes/db.php';
$stmtCategories = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmtCategories->fetchAll();

$stmtNew = $pdo->query("SELECT * FROM products WHERE is_new = 1 ORDER BY id DESC LIMIT 10");
$newProducts = $stmtNew->fetchAll();

$stmtPopular = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 10");
$popularProducts = $stmtPopular->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAGReen — premium чай</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
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
                </button>
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
                    
                    <?php foreach ($popularProducts as $product): ?>
                    <div class="swiper-slide">
                        <a href="product.php?id=<?= $product['id'] ?>" style="text-decoration: none; color: inherit; display: block;">
                            <div class="product-card">
                                <?php if ($product['is_new']): ?>
                                    <span class="badge">NEW</span>
                                <?php endif; ?>
                                
                                <?php if (!empty($product['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <img src="https://picsum.photos/seed/<?= $product['id'] ?>/300/260" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php endif; ?>
                                
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="price"><?= number_format($product['price'], 0, '.', ' ') ?> ₽ / 50г</p>
                                
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <form onsubmit="event.preventDefault(); addToCart(<?= $product['id'] ?>);" style="padding: 0 15px 15px;">
                                        <button type="submit" class="btn" style="width: 100%;">В корзину</button>
                                    </form>
                                <?php else: ?>
                                    <div style="padding: 0 15px 15px;">
                                        <a href="login.php" class="btn" style="width: 100%; display: block; text-align: center;">Войти, чтобы купить</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
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
                    
                    <?php foreach ($newProducts as $product): ?>
                    <div class="swiper-slide">
                        <a href="product.php?id=<?= $product['id'] ?>" style="text-decoration: none; color: inherit; display: block;">
                            <div class="product-card product-card--new">
                                <span class="badge">NEW</span>
                                
                                <?php if (!empty($product['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <img src="https://picsum.photos/seed/<?= $product['id'] ?>/300/260" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php endif; ?>
                                
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="price"><?= number_format($product['price'], 0, '.', ' ') ?> ₽ / 50г</p>
                                
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <form onsubmit="event.preventDefault(); addToCart(<?= $product['id'] ?>);" style="padding: 0 15px 15px;">
                                        <button type="submit" class="btn" style="width: 100%;">В корзину</button>
                                    </form>
                                <?php else: ?>
                                    <div style="padding: 0 15px 15px;">
                                        <a href="login.php" class="btn" style="width: 100%; display: block; text-align: center;">Войти, чтобы купить</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
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
    function addToCart(productId) {
        $.post('cart_add.php', { product_id: productId, quantity: 1 }, function(response) {
            alert('✅ Товар добавлен в корзину!');
        });
    }
    </script>
</body>
</html>