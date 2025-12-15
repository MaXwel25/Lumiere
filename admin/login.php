<?php
require_once '../config/database.php';
require_once '../config/admin_config.php';

// Если уже авторизован, перенаправляем в админку
if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Обработка формы входа
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    // Проверяем пароль (в реальном проекте используйте хеширование!)
    if ($password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Неверный пароль!';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-login:hover {
            background: #1a252f;
        }
        .error {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 15px;
        }
        .button-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">Вход в админ-панель</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="password">Пароль администратора:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Войти</button>
        </form>
        
        <div class="button-container">
            <a href="../index.php" class="btn-home">Вернуться на главную</a>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #999;">
            Парикмахерская "Стиль" - Администрирование
        </div>
    </div>
</body>
</html>