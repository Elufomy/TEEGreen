<?php
if (!isset($categories)) {
    $stmtCategories = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmtCategories->fetchAll();
}
?>

<header class="site-header">
    <nav class="main-menu">
        <ul>
            <li><a href="index.php">Главная</a></li>
            <li><a href="catalog.php">Каталог</a></li>
            <li><a href="#about">О нас</a></li>
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
</header>