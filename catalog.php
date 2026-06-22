<?php
require 'includes/db.php';

// Получаем все категории
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Получаем выбранную категорию из URL
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;

// Получаем тип сортировки
$sort = $_GET['sort'] ?? 'name_asc';

// Формируем SQL-запрос
$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";
$params = [];

// Если выбрана категория — добавляем фильтр
if ($categoryId) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryId;
}

// Добавляем сортировку (безопасно через switch)
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY p.name DESC";
        break;
    default:
        $sql .= " ORDER BY p.name ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Получаем название текущей категории (для заголовка)
$currentCategoryName = 'Все товары';
if ($categoryId) {
    $stmtCat = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmtCat->execute([$categoryId]);
    $currentCategoryName = $stmtCat->fetchColumn() ?: 'Все товары';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог - <?= htmlspecialchars($currentCategoryName) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .catalog-container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .catalog-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .catalog-filters { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 30px; }
        .filter-group { display: flex; gap: 10px; align-items: center; }
        .filter-group label { font-weight: bold; }
        .filter-group select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .category-links { display: flex; gap: 10px; flex-wrap: wrap; }
        .category-link { padding: 8px 16px; background: #f5f0e8; text-decoration: none; color: #333; border-radius: 20px; }
        .category-link.active { background: #4CAF50; color: white; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .product-card { background: white; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; transition: transform 0.2s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .product-card img { width: 100%; height: 200px; object-fit: cover; }
        .product-card-content { padding: 15px; }
        .product-card h3 { margin: 0 0 10px 0; font-size: 18px; }
        .product-card .price { font-size: 20px; font-weight: bold; color: #4CAF50; margin: 10px 0; }
        .badge { position: absolute; top: 10px; right: 10px; background: #ff5722; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .product-card-wrapper { position: relative; }
    </style>
</head>
<body>
    
    <!-- Подключаем меню (можно вынести в includes/header.php) -->
    <?php require 'includes/header.php'; ?>
    
    <div class="catalog-container">
        <div class="catalog-header">
            <h1><?= htmlspecialchars($currentCategoryName) ?></h1>
            <p>Найдено товаров: <?= count($products) ?></p>
        </div>
        
        <!-- Фильтры по категориям -->
        <div class="catalog-filters">
            <div class="category-links">
                <a href="catalog.php" class="category-link <?= !$categoryId ? 'active' : '' ?>">Все</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="catalog.php?category=<?= $cat['id'] ?>" 
                       class="category-link <?= $categoryId == $cat['id'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Сортировка -->
        <div class="catalog-filters">
            <div class="filter-group">
                <label>Сортировка:</label>
                <select onchange="window.location.href=this.value">
                    <option value="catalog.php?<?= $categoryId ? "category=$categoryId&" : '' ?>sort=name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>По названию (А-Я)</option>
                    <option value="catalog.php?<?= $categoryId ? "category=$categoryId&" : '' ?>sort=name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>По названию (Я-А)</option>
                    <option value="catalog.php?<?= $categoryId ? "category=$categoryId&" : '' ?>sort=price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>По цене (дешевые)</option>
                    <option value="catalog.php?<?= $categoryId ? "category=$categoryId&" : '' ?>sort=price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>По цене (дорогие)</option>
                </select>
            </div>
        </div>
        
        <!-- Сетка товаров -->
        <div class="products-grid">
            <?php if (empty($products)): ?>
                <p>В этой категории пока нет товаров.</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                <div class="product-card-wrapper">
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
                            
                            <div class="product-card-content">
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p style="color: #666; font-size: 14px;"><?= htmlspecialchars($product['category_name'] ?? 'Без категории') ?></p>
                                <p class="price"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</p>
                                <p style="color: <?= $product['stock'] > 0 ? '#4CAF50' : '#f44336' ?>; font-size: 14px;">
                                    <?= $product['stock'] > 0 ? "В наличии: {$product['stock']} шт." : 'Нет в наличии' ?>
                                </p>
                            </div>
                        </a>
                        
                        <?php if (isset($_SESSION['user_id']) && $product['stock'] > 0): ?>
                            <form action="cart_add.php" method="POST" onsubmit="event.preventDefault(); addToCart(<?= $product['id'] ?>);" style="padding: 0 15px 15px;">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" class="btn" style="width: 100%;">В корзину</button>
                            </form>
                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                            <div style="padding: 0 15px 15px;">
                                <a href="login.php" class="btn" style="width: 100%; display: block; text-align: center;">Войти, чтобы купить</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
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