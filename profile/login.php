<?php
require_once '../includes/header.php';

// Если клиент уже авторизован — отправляем в профиль
if (isClientLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$error = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Введите email и пароль';
    } else {
        // Ищем клиента по email
        $stmt = $db->prepare("SELECT id, full_name, password_hash FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        $client = $stmt->fetch();

        if ($client && password_verify($password, $client['password_hash'])) {
            // Успешный вход
            $_SESSION['client_id'] = $client['id'];
            $_SESSION['client_name'] = $client['full_name'];
            header('Location: profile.php');
            exit;
        } else {
            $error = 'Неверный email или пароль';
        }
    }
}
?>

<div class="container" style="max-width: 450px; margin: 80px auto;">
    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
        <h2 style="text-align: center; margin-bottom: 25px;">Вход в личный кабинет</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="email" style="display: block; margin-bottom: 5px; font-weight: 600;">Email</label>
                <input type="email" id="email" name="email" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="password" style="display: block; margin-bottom: 5px; font-weight: 600;">Пароль</label>
                <input type="password" id="password" name="password" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;">
            </div>
            <button type="submit" style="width: 100%; padding: 14px; background: #2c3e50; color: white; border: none; border-radius: 5px; font-size: 16px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-sign-in-alt"></i> Войти
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
            <p style="margin-top: 15px;"><a href="index.php">← Вернуться на главную</a></p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>