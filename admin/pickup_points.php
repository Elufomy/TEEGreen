<?php
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }
if (isset($_GET['delete'])) { $pdo->prepare("DELETE FROM pickup_points WHERE id = ?")->execute([(int)$_GET['delete']]); header("Location: pickup_points.php"); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['address'])) { $pdo->prepare("INSERT INTO pickup_points (address) VALUES (?)")->execute([trim($_POST['address'])]); header("Location: pickup_points.php"); exit; }
$pickupPoints = $pdo->query("SELECT * FROM pickup_points ORDER BY id")->fetchAll();
$userName = $_SESSION['login'] ?? 'Администратор';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Пункты выдачи — TEAGReen</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f9f6f0; overflow-x: hidden; }
.admin-burger { display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; background: #58355F; color: white; border: none; padding: 12px 16px; border-radius: 10px; cursor: pointer; font-size: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.mobile-home-btn { display: none; position: fixed; top: 15px; right: 15px; z-index: 1001; background: white; color: #58355F; border: 2px solid #58355F; padding: 10px 18px; border-radius: 10px; text-decoration: none; font-size: 14px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.admin-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
.admin-sidebar { width: 280px; background: white; box-shadow: 2px 0 20px rgba(0,0,0,0.1); padding: 30px 20px; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; z-index: 1000; transition: transform 0.3s ease; }
.admin-sidebar .logo { font-size: 28px; font-weight: 700; color: #2d5a27; text-decoration: none; display: block; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f5f0e8; }
.admin-sidebar .logo span { color: #58355F; }
.admin-sidebar .user-info { padding: 15px; background: #f5f0e8; border-radius: 12px; margin-bottom: 25px; }
.admin-sidebar .user-info .name { font-weight: 600; color: #58355F; margin-bottom: 4px; }
.admin-sidebar .user-info .role { font-size: 13px; color: #888; }
.admin-sidebar .menu { list-style: none; padding: 0; margin: 0; }
.admin-sidebar .menu li { margin-bottom: 5px; }
.admin-sidebar .menu a { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 12px; text-decoration: none; color: #555; transition: all 0.3s; font-weight: 500; font-size: 15px; }
.admin-sidebar .menu a:hover { background: #f5f0e8; color: #58355F; }
.admin-sidebar .menu a.active { background: #58355F; color: white; }
.admin-sidebar .menu a .icon { font-size: 20px; width: 28px; text-align: center; }
.admin-sidebar .logout-link { margin-top: 30px; padding-top: 20px; border-top: 2px solid #f5f0e8; }
.admin-sidebar .logout-link a { color: #dc2626 !important; }
.admin-main { margin-left: 280px; padding: 30px 40px; min-height: 100vh; transition: margin-left 0.3s ease; }
.page-header { margin-bottom: 30px; }
.page-header h1 { font-size: 32px; color: #58355F; }
.add-form { background: #f5f0e8; padding: 25px; border-radius: 12px; margin-bottom: 30px; }
.add-form-inner { display: flex; gap: 15px; flex-wrap: wrap; }
.add-form input { flex: 1; min-width: 250px; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 15px; }
.add-form input:focus { outline: none; border-color: #86A88F; }
.btn { display: inline-block; padding: 12px 24px; background: #58355F; color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; text-decoration: none; transition: 0.3s; cursor: pointer; }
.btn:hover { background: #472a4a; }
.btn-outline { background: transparent; color: #58355F; border: 2px solid #58355F; }
.btn-outline:hover { background: #58355F; color: white; }
.pickup-list { margin-top: 20px; }
.pickup-item { display: flex; justify-content: space-between; align-items: center; padding: 18px 20px; background: white; border-radius: 12px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); flex-wrap: wrap; gap: 10px; }
.pickup-item strong { color: #58355F; }
.btn-delete { background: #dc2626; color: white; padding: 8px 16px; text-decoration: none; border-radius: 8px; font-weight: 600; transition: 0.3s; }
.btn-delete:hover { background: #991b1b; }
.empty-state { text-align: center; padding: 40px; background: white; border-radius: 12px; color: #888; }
@media (max-width: 768px) {
    .admin-burger, .mobile-home-btn { display: block; }
    .admin-sidebar { transform: translateX(-100%); }
    .admin-sidebar.active { transform: translateX(0); }
    .admin-main { margin-left: 0; padding: 80px 20px 30px; }
    .page-header h1 { font-size: 24px; }
}
@media (max-width: 480px) {
    .admin-sidebar { width: 260px; }
    .admin-main { padding: 80px 15px 20px; }
    .add-form-inner { flex-direction: column; }
    .add-form input { width: 100%; }
    .btn { width: 100%; text-align: center; }
    .pickup-item { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body>
<button class="admin-burger" id="adminBurger"></button>
<a href="../index.php" class="mobile-home-btn">🏠 На главную</a>
<div class="admin-overlay" id="adminOverlay"></div>
<aside class="admin-sidebar" id="adminSidebar">
<a href="../index.php" class="logo">TEAG<span>Reen</span></a>
<div class="user-info"><div class="name"><?= htmlspecialchars($userName) ?></div><div class="role">Администратор</div></div>
<ul class="menu">
<li><a href="index.php"><span class="icon">📊</span><span>Главная</span></a></li>
<li><a href="products.php"><span class="icon">📦</span><span>Товары</span></a></li>
<li><a href="categories.php"><span class="icon">📂</span><span>Категории</span></a></li>
<li><a href="orders.php"><span class="icon">📋</span><span>Заказы</span></a></li>
<li><a href="pickup_points.php" class="active"><span class="icon">📍</span><span>Пункты выдачи</span></a></li>
<li><a href="sales.php"><span class="icon">📈</span><span>Продажи</span></a></li>
<li><a href="add_product.php"><span class="icon">➕</span><span>Добавить товар</span></a></li>
</ul>
<div class="logout-link"><a href="../logout.php"><span class="icon">🚪</span><span>Выйти</span></a></div>
</aside>
<main class="admin-main">
<div class="page-header"><h1> Управление пунктами выдачи</h1></div>
<div class="add-form">
<form method="POST" class="add-form-inner">
<input type="text" name="address" placeholder="Адрес пункта выдачи" required>
<button type="submit" class="btn">➕ Добавить пункт</button>
</form>
</div>
<div class="pickup-list">
<?php if (empty($pickupPoints)): ?>
<div class="empty-state"><p>📍 Пункты выдачи не добавлены</p></div>
<?php else: ?>
<?php foreach ($pickupPoints as $point): ?>
<div class="pickup-item">
<span><strong>ID <?= $point['id'] ?>:</strong> 📍 <?= htmlspecialchars($point['address']) ?></span>
<a href="?delete=<?= $point['id'] ?>" class="btn-delete" onclick="return confirm('Удалить пункт выдачи?')">🗑️ Удалить</a>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<div style="margin-top: 30px;"><a href="index.php" class="btn btn-outline">← Вернуться на главную</a></div>
</main>
<script>
const burger = document.getElementById('adminBurger');
const sidebar = document.getElementById('adminSidebar');
const overlay = document.getElementById('adminOverlay');
if (burger && sidebar && overlay) {
    burger.addEventListener('click', function(e) { e.stopPropagation(); sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
    overlay.addEventListener('click', function() { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
    sidebar.addEventListener('click', function(e) { e.stopPropagation(); });
}
</script>
</body>
</html>