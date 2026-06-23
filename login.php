<?php
require 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];

    if (empty($login) || empty($password)) {
        $error = "Заполните все поля!";
    } else {
        $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            
            header("Location: index.php");
            exit;
        } else {
            $error = "Неверный логин или пароль!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — TEAGReen</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    
    <div class="menu-block">
        <nav class="main-menu">
            <ul>
                <li><a href="index.php">Главная</a></li>
                <li><a href="register.php">Регистрация</a></li>
            </ul>
        </nav>
    </div>
    
    <div class="login-page">
        <div class="login-form">
            <h1>Вход в аккаунт</h1>
            <p>Введите свои данные для входа</p>
            
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="login">Логин</label>
                    <input type="text" id="login" name="login" required placeholder="Введите логин">
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required placeholder="Введите пароль">
                </div>
                
                <button type="submit" class="btn-login">Войти</button>
            </form>
            
            <p class="login-register-link">
                Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
            </p>
        </div>
    </div>
    
</body>
</html>