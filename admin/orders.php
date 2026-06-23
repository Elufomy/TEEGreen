<?php
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }
$orders = $pdo->query("SELECT o.*, u.login as user_login FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();
$userName = $_SESSION['login'] ?? 'Администратор';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Заказы — TEAGReen</title>
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
.table-wrapper { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; min-width: 700px; }
.data-table th { background: #F5F2E1; color: #58355F; padding: 15px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; }
.data-table td { padding: 15px; border-bottom: 1px solid #f5f0e8; vertical-align: middle; }
.data-table tr:hover { background: #fafafa; }
.status-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-new { background: #fee2e2; color: #dc2626; }
.status-processing { background: #fef3c7; color: #d97706; }
.status-ready { background: #dbeafe; color: #2563eb; }
.status-completed { background: #dcfce7; color: #16a34a; }
.btn { display: inline-block; padding: 8px 18px; background: #58355F; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; transition: 0.3s; cursor: pointer; }
.btn:hover { background: #472a4a; }
.btn-outline { background: transparent; color: #58355F; border: 2px solid #58355F; }
.btn-outline:hover { background: #58355F; color: white; }
.btn-sm { padding: 6px 12px; font-size: 12px; }
.actions { display: flex; gap: 8px; }
.empty-state { text-align: center; padding: 60px 20px; color: #888; font-size: 16px; background: white; border-radius: 16px; }
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
    .btn { padding: 10px 14px; font-size: 12px; }
    .actions { flex-direction: column; }
}
</style>
</head>
<body>
<button class="admin-burger" id="adminBurger">☰</button>
<a href="../index.php" class="mobile-home-btn">🏠 На главную</a>
<div class="admin-overlay" id="adminOverlay"></div>
<aside class="admin-sidebar" id="adminSidebar">
<a href="../index.php" class="logo">TEAG<span>Reen</span></a>
<div class="user-info"><div class="name"><?= htmlspecialchars($userName) ?></div><div class="role">Администратор</div></div>
<ul class="menu">
<li><a href="index.php"><span class="icon">📊</span><span>Главная</span></a></li>
<li><a href="products.php"><span class="icon"></span><span>Товары</span></a></li>
<li><a href="categories.php"><span class="icon">📂</span><span>Категории</span></a></li>
<li><a href="orders.php" class="active"><span class="icon">📋</span><span>Заказы</span></a></li>
<li><a href="pickup_points.php"><span class="icon">📍</span><span>Пункты выдачи</span></a></li>
<li><a href="sales.php"><span class="icon">📈</span><span>Продажи</span></a></li>
<li><a href="add_product.php"><span class="icon">➕</span><span>Добавить товар</span></a></li>
</ul>
<div class="logout-link"><a href="../logout.php"><span class="icon">🚪</span><span>Выйти</span></a></div>
</aside>
<main class="admin-main">
<div class="page-header"><h1>📋 Управление заказами</h1></div>
<?php if (empty($orders)): ?>
<div class="empty-state"><p style="font-size: 18px; margin-bottom: 20px;">📦 Заказов пока нет</p><a href="index.php" class="btn btn-outline">← Вернуться на главную</a></div>
<?php else: ?>
<div class="table-wrapper">
<table class="data-table">
<thead><tr><th>ID</th><th>Пользователь</th><th>Сумма</th><th>Статус</th><th>Дата</th><th>Действия</th></tr></thead>
<tbody>
<?php foreach ($orders as $order): ?>
<tr>
<td><strong>#<?= $order['id'] ?></strong></td>
<td><?= htmlspecialchars($order['user_login'] ?? 'Гость') ?></td>
<td><?= number_format($order['total_amount'], 2, '.', ' ') ?> ₽</td>
<td><span class="status-badge status-<?= $order['status'] ?>"><?php $statuses = ['new' => 'Новый', 'processing' => 'В обработке', 'ready' => 'Готов', 'completed' => 'Выполнен']; echo $statuses[$order['status']] ?? $order['status']; ?></span></td>
<td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
<td class="actions"><a href="order_view.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline">👁️ Просмотр</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
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