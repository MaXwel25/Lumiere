<?php
// admin/admins.php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdminAuth();

$message = '';
$error = '';

// Обработка добавления/редактирования администратора
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($full_name) || empty($email)) {
        $error = 'Имя и email обязательны для заполнения';
    } else {
        // Определяем, меняется ли пароль
        $changingPassword = !empty($password) || !empty($password_confirm);

        if ($id) {
            // Редактирование
            if ($changingPassword) {
                if ($password !== $password_confirm) {
                    $error = 'Пароли не совпадают';
                } elseif (strlen($password) < 6) {
                    $error = 'Пароль должен быть не менее 6 символов';
                }
            }

            if (!$error) {
                try {
                    if ($changingPassword) {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE admins SET full_name = ?, email = ?, password_hash = ? WHERE id = ?");
                        $stmt->execute([$full_name, $email, $hash, $id]);
                    } else {
                        $stmt = $db->prepare("UPDATE admins SET full_name = ?, email = ? WHERE id = ?");
                        $stmt->execute([$full_name, $email, $id]);
                    }
                    $message = 'Администратор обновлён';
                } catch (PDOException $e) {
                    $error = ($e->getCode() == 23505) ? 'Email уже используется' : 'Ошибка БД: ' . $e->getMessage();
                }
            }
        } else {
            // Новый администратор
            if (empty($password)) {
                $error = 'Пароль обязателен';
            } elseif ($password !== $password_confirm) {
                $error = 'Пароли не совпадают';
            } elseif (strlen($password) < 6) {
                $error = 'Пароль должен быть не менее 6 символов';
            }

            if (!$error) {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO admins (full_name, email, password_hash) VALUES (?, ?, ?)");
                    $stmt->execute([$full_name, $email, $hash]);
                    $message = 'Администратор добавлен';
                } catch (PDOException $e) {
                    $error = ($e->getCode() == 23505) ? 'Email уже используется' : 'Ошибка БД: ' . $e->getMessage();
                }
            }
        }
    }
}

// Удаление администратора
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id == $_SESSION['admin_id']) {
        $error = 'Нельзя удалить самого себя';
    } else {
        $stmt = $db->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Администратор удалён';
    }
}

// Получение списка администраторов (без created_at/updated_at)
$admins = $db->query("SELECT id, full_name, email FROM admins ORDER BY id")->fetchAll();

// Данные для редактирования
$editAdmin = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT id, full_name, email FROM admins WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editAdmin = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление администраторами - Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: #2c3e50; color: white; padding: 20px 0; }
        .admin-content { flex: 1; padding: 20px; background: #f5f5f5; }
        .admin-logo { text-align: center; padding: 20px; border-bottom: 1px solid #34495e; }
        .admin-menu { list-style: none; padding: 0; }
        .admin-menu li { border-bottom: 1px solid #34495e; }
        .admin-menu a { display: block; padding: 15px 20px; color: #ecf0f1; text-decoration: none; transition: background 0.3s; }
        .admin-menu a:hover, .admin-menu a.active { background: #34495e; }
        .admin-menu i { width: 20px; margin-right: 10px; }
        .table-responsive { overflow-x: auto; background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
        .btn { padding: 8px 16px; border-radius: 5px; text-decoration: none; font-weight: 600; cursor: pointer; border: none; transition: all 0.3s; }
        .btn-sm { padding: 5px 10px; font-size: 14px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-actions { display: flex; gap: 10px; margin-top: 20px; }
        .action-buttons { display: flex; gap: 5px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Сайдбар -->
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2><i class="fas fa-cut"></i> Админ-панель</h2>
                <small>Парикмахерская "Lumiere"</small>
            </div>
            <ul class="admin-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Дашборд</a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> Записи</a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i> Клиенты</a></li>
                <li><a href="masters.php"><i class="fas fa-user-tie"></i> Мастера</a></li>
                <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Услуги</a></li>
                <li><a href="schedule.php"><i class="fas fa-clock"></i> Расписание</a></li>
                <li><a href="receipts.php"><i class="fas fa-receipt"></i> Чеки</a></li>
                <li><a href="admins.php" class="active"><i class="fas fa-user-shield"></i> Администраторы</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выход</a></li>
            </ul>
        </div>

        <!-- Основной контент -->
        <div class="admin-content">
            <h1><i class="fas fa-user-shield"></i> Управление администраторами</h1>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Форма добавления/редактирования -->
            <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <h2><?= $editAdmin ? 'Редактирование администратора' : 'Добавить нового администратора' ?></h2>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $editAdmin['id'] ?? '' ?>">
                    <div class="form-group">
                        <label for="full_name">Полное имя *</label>
                        <input type="text" id="full_name" name="full_name" required value="<?= htmlspecialchars($editAdmin['full_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($editAdmin['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Пароль <?= $editAdmin ? '(оставьте пустым, чтобы не менять)' : '*' ?></label>
                        <input type="password" id="password" name="password" <?= $editAdmin ? '' : 'required' ?>>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Подтверждение пароля</label>
                        <input type="password" id="password_confirm" name="password_confirm" <?= $editAdmin ? '' : 'required' ?>>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success"><?= $editAdmin ? 'Сохранить' : 'Добавить' ?></button>
                        <?php if ($editAdmin): ?>
                            <a href="admins.php" class="btn btn-info">Отмена</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Таблица администраторов -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admins)): ?>
                            <tr><td colspan="4" style="text-align: center;">Нет администраторов</td></tr>
                        <?php else: ?>
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td>#<?= $admin['id'] ?></td>
                                <td><?= htmlspecialchars($admin['full_name']) ?></td>
                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?= $admin['id'] ?>" class="btn btn-sm btn-primary" title="Редактировать"><i class="fas fa-edit"></i></a>
                                        <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                            <a href="?delete=<?= $admin['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить администратора?')" title="Удалить"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>