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
    } elseif (mb_strlen($login) < 3) {
        $error = "Логин должен быть длиннее 3 символов";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        
        if ($stmt->fetch()) {
            $error = "Такой логин уже занят!";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (login, password_hash, role) VALUES (?, ?, 'user')");
            $stmt->execute([$login, $hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['role'] = 'user';
            
            header("Location: index.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация — TEAGReen</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    
    <!-- Меню -->
    <div class="menu-block">
        <nav class="main-menu">
            <ul>
                <li><a href="index.php">Главная</a></li>
                <li><a href="login.php">Вход</a></li>
            </ul>
        </nav>
    </div>
    
    <div class="register-page">
        <div class="register-form">
            <h1>Регистрация</h1>
            <p>Создайте аккаунт</p>
            
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="login">Логин</label>
                    <input type="text" id="login" name="login" required placeholder="Минимум 3 символа">
                    <p class="form-hint">Не менее 3 символов</p>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required placeholder="Введите пароль">
                </div>
                
                <button type="submit" class="btn-register">Зарегистрироваться</button>
            </form>
            
            <p class="register-login-link">
                Уже есть аккаунт? <a href="login.php">Войти</a>
            </p>
        </div>
    </div>
    
</body>
</html>