<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Обработка добавления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $address = trim($_POST['address']);
        $work_hours = trim($_POST['work_hours']);
        
        if (!empty($address)) {
            $stmt = $pdo->prepare("INSERT INTO pickup_points (address, work_hours) VALUES (?, ?)");
            $stmt->execute([$address, $work_hours]);
            $message = "✅ Пункт выдачи добавлен!";
        } else {
            $error = "❌ Введите адрес";
        }
    }
    
    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("DELETE FROM pickup_points WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        $message = "✅ Пункт выдачи удалён!";
    }
}

// Получаем все пункты выдачи
$pickupPoints = $pdo->query("SELECT * FROM pickup_points ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пункты выдачи — Админ-панель TEAGReen</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-body { background: #f9f6f0; min-height: 100vh; display: flex; }
        .admin-sidebar { width: 250px; background: white; box-shadow: 2px 0 10px rgba(0,0,0,0.05); padding: 30px 20px; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; z-index: 100; }
        .admin-sidebar .logo { font-size: 24px; font-weight: bold; color: #2d5a27; text-decoration: none; display: block; margin-bottom: 30px; }
        .admin-sidebar .logo span { color: var(--accent); }
        .admin-sidebar .menu { list-style: none; padding: 0; }
        .admin-sidebar .menu li { margin-bottom: 5px; }
        .admin-sidebar .menu a { display: block; padding: 12px 16px; border-radius: 12px; text-decoration: none; color: #333; transition: 0.3s; font-weight: 500; }
        .admin-sidebar .menu a:hover { background: #f5f0e8; color: var(--accent); }
        .admin-sidebar .menu a.active { background: var(--accent); color: white; }
        .admin-sidebar .menu a .icon { margin-right: 12px; }
        .admin-sidebar .logout-link { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        .admin-sidebar .logout-link a { color: #dc2626 !important; }
        .admin-sidebar .logout-link a:hover { background: #fee2e2 !important; }
        .admin-main { margin-left: 250px; padding: 30px; width: 100%; }
        .admin-main h1 { font-size: 32px; color: var(--accent); margin-bottom: 10px; }
        .admin-main .welcome { color: #666; margin-bottom: 30px; }
        .admin-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .admin-card h3 { font-size: 20px; color: var(--accent); margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 5px; color: #333; }
        .form-group input { width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 16px; transition: 0.3s; box-sizing: border-box; }
        .form-group input:focus { outline: none; border-color: var(--accent); }
        .btn-admin { padding: 12px 30px; background: var(--accent); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-admin:hover { background: #472a4a; }
        .btn-admin-danger { background: #dc2626; }
        .btn-admin-danger:hover { background: #b91c1c; }
        .pickup-list { display: flex; flex-direction: column; gap: 10px; }
        .pickup-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #f0f0f0; }
        .pickup-item:last-child { border-bottom: none; }
        .pickup-item .address { font-weight: 500; }
        .pickup-item .hours { color: #666; font-size: 14px; }
        .pickup-item .actions { display: flex; gap: 10px; }
        .btn-sm { padding: 6px 14px; font-size: 14px; border-radius: 8px; border: none; cursor: pointer; transition: 0.3s; }
        .btn-sm-edit { background: #86A88F; color: white; }
        .btn-sm-edit:hover { background: #6d8a75; }
        .btn-sm-delete { background: #dc2626; color: white; }
        .btn-sm-delete:hover { background: #b91c1c; }
        .message { padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        .message-success { background: #dcfce7; color: #16a34a; }
        .message-error { background: #fee2e2; color: #dc2626; }
        .empty-text { color: #999; text-align: center; padding: 30px 0; }
        @media (max-width: 768px) {
            .admin-sidebar { width: 200px; padding: 20px 15px; }
            .admin-main { margin-left: 200px; padding: 20px; }
            .pickup-item { flex-wrap: wrap; gap: 10px; }
        }
        @media (max-width: 480px) {
            .admin-sidebar { width: 60px; padding: 15px 10px; }
            .admin-sidebar .logo { font-size: 0; }
            .admin-sidebar .logo::after { content: "TE"; font-size: 20px; font-weight: bold; color: #2d5a27; }
            .admin-sidebar .menu a span { display: none; }
            .admin-sidebar .menu a .icon { margin-right: 0; font-size: 20px; }
            .admin-main { margin-left: 60px; padding: 15px; }
        }
    </style>
</head>
<body>
    
    <!-- Сайдбар -->
    <aside class="admin-sidebar">
        <a href="../index.php" class="logo">TEAG<span>Reen</span></a>
        <ul class="menu">
            <li><a href="index.php"><span class="icon"></span> <span>Главная</span></a></li>
            <li><a href="products.php"><span class="icon"></span> <span>Товары</span></a></li>
            <li><a href="categories.php"><span class="icon"></span> <span>Категории</span></a></li>
            <li><a href="orders.php"><span class="icon"></span> <span>Заказы</span></a></li>
            <li><a href="pickup_points.php" class="active"><span class="icon"></span> <span>Пункты выдачи</span></a></li>
            <li><a href="add_product.php"><span class="icon">➕</span> <span>Добавить товар</span></a></li>
        </ul>
        <div class="logout-link">
            <a href="../logout.php"><span class="icon">🚪</span> <span>Выйти</span></a>
        </div>
    </aside>
    
    <!-- Основной контент -->
    <main class="admin-main">
        <h1>Пункты выдачи</h1>
        <p class="welcome">Управляйте пунктами самовывоза для заказов</p>
        
        <?php if (isset($message)): ?>
            <div class="message message-success"><?= $message ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="message message-error"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- Форма добавления -->
        <div class="admin-card">
            <h3>➕ Добавить пункт выдачи</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="address">Адрес</label>
                    <input type="text" id="address" name="address" placeholder="ул. Ленина, 10, офис 5" required>
                </div>
                <div class="form-group">
                    <label for="work_hours">Часы работы (необязательно)</label>
                    <input type="text" id="work_hours" name="work_hours" placeholder="10:00 - 20:00">
                </div>
                <button type="submit" class="btn-admin">Добавить пункт</button>
            </form>
        </div>
        
        <!-- Список пунктов -->
        <div class="admin-card">
            <h3>Список пунктов выдачи</h3>
            <?php if (empty($pickupPoints)): ?>
                <p class="empty-text">Пунктов выдачи пока нет</p>
            <?php else: ?>
                <div class="pickup-list">
                    <?php foreach ($pickupPoints as $point): ?>
                    <div class="pickup-item">
                        <div>
                            <div class="address">📍 <?= htmlspecialchars($point['address']) ?></div>
                            <?php if (!empty($point['work_hours'])): ?>
                                <div class="hours">🕐 <?= htmlspecialchars($point['work_hours']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="actions">
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить пункт выдачи?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $point['id'] ?>">
                                <button type="submit" class="btn-sm btn-sm-delete">🗑️</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
</body>
</html>