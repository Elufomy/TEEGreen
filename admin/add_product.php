<?php
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }
$error = '';
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category_id = (int)$_POST['category_id'];
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) { $error = "Разрешены только: " . implode(', ', $allowed); }
        else {
            $newFilename = uniqid('product_') . '.' . $ext;
            $destination = $uploadDir . $newFilename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) { $imagePath = 'uploads/' . $newFilename; }
            else { $error = "Ошибка при загрузке файла"; }
        }
    }
    if (empty($error) && !empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category_id, is_new, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $stock, $category_id, $is_new, $imagePath]);
        header("Location: products.php"); exit;
    }
}
$userName = $_SESSION['login'] ?? 'Администратор';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Добавить товар — TEAGReen</title>
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
.form-container { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); max-width: 800px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 15px; transition: border-color 0.3s; box-sizing: border-box; font-family: inherit; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #86A88F; }
.form-group textarea { min-height: 120px; resize: vertical; }
.error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
.form-actions { display: flex; gap: 15px; margin-top: 25px; flex-wrap: wrap; }
.btn { display: inline-block; padding: 12px 30px; background: #58355F; color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; text-decoration: none; transition: 0.3s; cursor: pointer; }
.btn:hover { background: #472a4a; }
.btn-outline { background: transparent; color: #666; border: 2px solid #ddd; }
.btn-outline:hover { background: #f5f5f5; color: #333; }
.checkbox-group { display: flex; align-items: center; gap: 10px; padding: 15px; background: #f9f6f0; border-radius: 10px; }
.checkbox-group input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
.checkbox-group label { margin: 0; cursor: pointer; }
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
    .form-container { padding: 20px; }
    .form-actions { flex-direction: column; }
    .btn { width: 100%; text-align: center; }
}
</style>
</head>
<body>
<button class="admin-burger" id="adminBurger">☰</button>
<a href="../index.php" class="mobile-home-btn"> На главную</a>
<div class="admin-overlay" id="adminOverlay"></div>
<aside class="admin-sidebar" id="adminSidebar">
<a href="../index.php" class="logo">TEAG<span>Reen</span></a>
<div class="user-info"><div class="name"><?= htmlspecialchars($userName) ?></div><div class="role">Администратор</div></div>
<ul class="menu">
<li><a href="index.php"><span class="icon">📊</span><span>Главная</span></a></li>
<li><a href="products.php"><span class="icon">📦</span><span>Товары</span></a></li>
<li><a href="categories.php"><span class="icon">📂</span><span>Категории</span></a></li>
<li><a href="orders.php"><span class="icon">📋</span><span>Заказы</span></a></li>
<li><a href="pickup_points.php"><span class="icon">📍</span><span>Пункты выдачи</span></a></li>
<li><a href="sales.php"><span class="icon">📈</span><span>Продажи</span></a></li>
<li><a href="add_product.php" class="active"><span class="icon">➕</span><span>Добавить товар</span></a></li>
</ul>
<div class="logout-link"><a href="../logout.php"><span class="icon">🚪</span><span>Выйти</span></a></div>
</aside>
<main class="admin-main">
<div class="page-header"><h1>➕ Добавить новый товар</h1></div>
<?php if ($error): ?><div class="error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="form-container">
<form method="POST" enctype="multipart/form-data">
<div class="form-group"><label>Название товара *</label><input type="text" name="name" required></div>
<div class="form-group"><label>Описание</label><textarea name="description"></textarea></div>
<div class="form-group"><label>Категория *</label><select name="category_id" required><option value="">-- Выберите категорию --</option><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label>Цена (₽) *</label><input type="number" name="price" step="0.01" min="0" required></div>
<div class="form-group"><label>Остаток на складе (шт.) *</label><input type="number" name="stock" min="0" required></div>
<div class="form-group"><label>Картинка товара</label><input type="file" name="image" accept="image/*"></div>
<div class="form-group"><div class="checkbox-group"><input type="checkbox" name="is_new" value="1" id="is_new"><label for="is_new">🔥 Отметить как новинку</label></div></div>
<div class="form-actions"><button type="submit" class="btn">✅ Добавить товар</button><a href="products.php" class="btn btn-outline">↩️ Отмена</a></div>
</form>
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