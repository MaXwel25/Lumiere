<?php
// admin/login.php
require_once '../config/database.php';
require_once '../includes/auth.php';  // содержит функции для работы с admins

// Если уже авторизован — на дашборд
if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Введите email и пароль';
    } else {
        // Используем функцию adminLogin из auth.php (работает с таблицей admins)
        $result = adminLogin($email, $password);
        if ($result['success']) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель | Lumiere</title>
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
        .btn-home {
            display: inline-block;
            width: 100%;
            text-align: center;
            background: #95a5a6;
            color: white;
            padding: 10px;
            border-radius: 5px;
            text-decoration: none;
        }
        .btn-home:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">Вход в админ-панель</h1>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email администратора:</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Войти</button>
        </form>

        <div class="button-container">
            <a href="../index.php" class="btn-home">← Вернуться на главную</a>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #999;">
            Парикмахерская "Lumiere" – Администрирование
        </div>
    </div>
</body>
</html>