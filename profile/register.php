<?php
// profile/register.php
require_once '../includes/header.php';

// Если клиент уже авторизован — отправляем в профиль
if (isClientLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$error = '';
$success = '';

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Валидация
    if (empty($full_name) || empty($phone) || empty($email) || empty($password)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email адрес';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif ($password !== $password_confirm) {
        $error = 'Пароли не совпадают';
    } else {
        try {
            // Проверяем, не занят ли email
            $checkStmt = $db->prepare("SELECT id FROM clients WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $error = 'Клиент с таким email уже существует. Пожалуйста, укажите другой email.';
            } else {
                // Создаём клиента
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO clients (full_name, phone, email, password_hash) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$full_name, $phone, $email, $hash]);
                $client_id = $db->lastInsertId();

                // Автоматический вход
                $_SESSION['client_id'] = $client_id;
                $_SESSION['client_name'] = $full_name;

                // Перенаправляем в личный кабинет
                header('Location: profile.php');
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23505) {
                $error = 'Клиент с таким email уже существует.';
            } else {
                $error = 'Ошибка базы данных: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="container" style="max-width: 500px; margin: 80px auto;">
    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
        <h2 style="text-align: center; margin-bottom: 25px;">Регистрация</h2>

        <?php if ($error): ?>
            <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="full_name" style="display: block; margin-bottom: 5px; font-weight: 600;">ФИО *</label>
                <input type="text" id="full_name" name="full_name" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;"
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="phone" style="display: block; margin-bottom: 5px; font-weight: 600;">Телефон *</label>
                <input type="tel" id="phone" name="phone" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="email" style="display: block; margin-bottom: 5px; font-weight: 600;">Email *</label>
                <input type="email" id="email" name="email" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="password" style="display: block; margin-bottom: 5px; font-weight: 600;">Пароль *</label>
                <input type="password" id="password" name="password" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="password_confirm" style="display: block; margin-bottom: 5px; font-weight: 600;">Подтверждение пароля *</label>
                <input type="password" id="password_confirm" name="password_confirm" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;">
            </div>
            <button type="submit" style="width: 100%; padding: 14px; background: #2c3e50; color: white; border: none; border-radius: 5px; font-size: 16px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-user-plus"></i> Зарегистрироваться
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
            <p style="margin-top: 15px;"><a href="../index.php">← Вернуться на главную</a></p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>