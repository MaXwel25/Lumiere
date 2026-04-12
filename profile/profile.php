<?php
// profile/profile.php
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Проверяем, что клиент авторизован
if (!isClientLoggedIn()) {
    header('Location: login.php');
    exit;
}

$clientId = $_SESSION['client_id'];
$message = '';
$error = '';

// ========== ПОЛУЧЕНИЕ ДАННЫХ КЛИЕНТА ==========
$stmt = $db->prepare("SELECT id, full_name, phone, email, created_at FROM clients WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch();

// Если клиент по какой-то причине не найден в БД — выходим
if (!$client) {
    // Очищаем сессию и перенаправляем на вход с сообщением
    session_destroy();
    header('Location: login.php?error=user_not_found');
    exit;
}

// ========== ОБРАБОТКА ОБНОВЛЕНИЯ ПРОФИЛЯ ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Валидация
    if (empty($full_name) || empty($phone)) {
        $error = 'Имя и телефон обязательны для заполнения';
    } elseif (!empty($new_password) && $new_password !== $password_confirm) {
        $error = 'Пароли не совпадают';
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        try {
            // Проверка уникальности email (исключая текущего клиента)
            if (!empty($email)) {
                $checkStmt = $db->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
                $checkStmt->execute([$email, $clientId]);
                if ($checkStmt->fetch()) {
                    throw new PDOException("Клиент с таким email уже существует", 23505);
                }
            }

            if (!empty($new_password)) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    UPDATE clients 
                    SET full_name = ?, phone = ?, email = ?, password_hash = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$full_name, $phone, $email ?: null, $hash, $clientId]);
            } else {
                $stmt = $db->prepare("
                    UPDATE clients 
                    SET full_name = ?, phone = ?, email = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$full_name, $phone, $email ?: null, $clientId]);
            }
            $_SESSION['client_name'] = $full_name;
            $message = 'Профиль успешно обновлён';

            // Обновляем данные клиента для отображения
            $client['full_name'] = $full_name;
            $client['phone'] = $phone;
            $client['email'] = $email ?: null;
        } catch (PDOException $e) {
            if ($e->getCode() == 23505) {
                $error = 'Клиент с таким email уже существует. Укажите другой email.';
            } else {
                $error = 'Ошибка базы данных: ' . $e->getMessage();
            }
        }
    }
}

// ========== ОТМЕНА ЗАПИСИ ==========
if (isset($_GET['cancel_appointment'])) {
    $appointmentId = intval($_GET['cancel_appointment']);
    $stmt = $db->prepare("
        UPDATE appointments 
        SET status = 'cancelled' 
        WHERE id = ? AND client_id = ? AND status = 'scheduled' AND appointment_date >= CURRENT_DATE
    ");
    $stmt->execute([$appointmentId, $clientId]);
    if ($stmt->rowCount() > 0) {
        $message = 'Запись успешно отменена';
    } else {
        $error = 'Невозможно отменить эту запись';
    }
}

// ========== ПОЛУЧЕНИЕ ЗАПИСЕЙ КЛИЕНТА ==========
$filter = $_GET['filter'] ?? 'upcoming';
$whereStatus = '';
if ($filter === 'upcoming') {
    $whereStatus = " AND a.appointment_date >= CURRENT_DATE AND a.status = 'scheduled'";
} elseif ($filter === 'past') {
    $whereStatus = " AND (a.appointment_date < CURRENT_DATE OR a.status IN ('completed','cancelled','no_show'))";
}

$appointmentsStmt = $db->prepare("
    SELECT 
        a.id, a.appointment_date, a.start_time, a.end_time, a.status, a.notes,
        s.name as service_name, s.price, s.duration_min,
        m.full_name as master_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN masters m ON a.master_id = m.id
    WHERE a.client_id = ? $whereStatus
    ORDER BY a.appointment_date DESC, a.start_time DESC
    LIMIT 50
");
$appointmentsStmt->execute([$clientId]);
$appointments = $appointmentsStmt->fetchAll();

// Функция для форматирования статуса
function getStatusLabel($status) {
    $labels = [
        'scheduled' => 'Запланирована',
        'completed' => 'Выполнена',
        'cancelled' => 'Отменена',
        'no_show' => 'Неявка'
    ];
    return $labels[$status] ?? $status;
}
?>

<div class="container" style="margin-top: 30px; margin-bottom: 50px;">
    <h1>Личный кабинет</h1>

    <?php if ($message): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
        <!-- Левая колонка: форма редактирования профиля -->
        <div class="profile-form" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h2>Мои данные</h2>
            <form method="POST">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">ФИО *</label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($client['full_name']) ?>" 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Телефон *</label>
                    <input type="tel" name="phone" required value="<?= htmlspecialchars($client['phone']) ?>" 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($client['email'] ?? '') ?>" 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <hr>
                <h3>Сменить пароль</h3>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Новый пароль</label>
                    <input type="password" name="new_password" 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"
                           placeholder="Оставьте пустым, чтобы не менять">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Подтверждение пароля</label>
                    <input type="password" name="password_confirm" 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary" 
                        style="background: #2c3e50; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
                    Сохранить изменения
                </button>
            </form>
        </div>

        <!-- Правая колонка: история записей -->
        <div class="profile-appointments" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">Мои записи</h2>
                <div class="filter-buttons">
                    <a href="?filter=upcoming" class="btn <?= $filter === 'upcoming' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" 
                       style="padding: 5px 10px; text-decoration: none; background: <?= $filter === 'upcoming' ? '#3498db' : '#95a5a6' ?>; color: white; border-radius: 5px; margin-right: 5px;">Предстоящие</a>
                    <a href="?filter=past" class="btn <?= $filter === 'past' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" 
                       style="padding: 5px 10px; text-decoration: none; background: <?= $filter === 'past' ? '#3498db' : '#95a5a6' ?>; color: white; border-radius: 5px; margin-right: 5px;">Прошедшие</a>
                    <a href="?filter=all" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" 
                       style="padding: 5px 10px; text-decoration: none; background: <?= $filter === 'all' ? '#3498db' : '#95a5a6' ?>; color: white; border-radius: 5px;">Все</a>
                </div>
            </div>

            <?php if (empty($appointments)): ?>
                <p>У вас пока нет записей.</p>
                <a href="../booking.php" class="btn btn-primary" style="background: #2c3e50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Записаться сейчас</a>
            <?php else: ?>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                <th style="padding: 12px; text-align: left;">Дата</th>
                                <th style="padding: 12px; text-align: left;">Время</th>
                                <th style="padding: 12px; text-align: left;">Услуга</th>
                                <th style="padding: 12px; text-align: left;">Мастер</th>
                                <th style="padding: 12px; text-align: left;">Статус</th>
                                <th style="padding: 12px; text-align: left;">Сумма</th>
                                <th style="padding: 12px; text-align: left;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $app): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px;"><?= date('d.m.Y', strtotime($app['appointment_date'])) ?></td>
                                <td style="padding: 12px;"><?= substr($app['start_time'], 0, 5) ?></td>
                                <td style="padding: 12px;"><?= htmlspecialchars($app['service_name']) ?></td>
                                <td style="padding: 12px;"><?= htmlspecialchars($app['master_name']) ?></td>
                                <td style="padding: 12px;">
                                    <span class="status-badge status-<?= $app['status'] ?>" style="display: inline-block; padding: 4px 8px; border-radius: 20px; font-size: 12px; font-weight: 600; 
                                          background: <?= 
                                              $app['status'] === 'scheduled' ? '#3498db' : 
                                              ($app['status'] === 'completed' ? '#2ecc71' : 
                                              ($app['status'] === 'cancelled' ? '#e74c3c' : '#f39c12')) ?>; color: white;">
                                        <?= getStatusLabel($app['status']) ?>
                                    </span>
                                </td>
                                <td style="padding: 12px;"><?= number_format($app['price'], 0, ',', ' ') ?> ₽</td>
                                <td style="padding: 12px;">
                                    <?php if ($app['status'] === 'scheduled' && strtotime($app['appointment_date']) >= strtotime('today')): ?>
                                        <a href="?cancel_appointment=<?= $app['id'] ?>" 
                                           class="btn btn-danger btn-sm"
                                           style="background: #e74c3c; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 12px;"
                                           onclick="return confirm('Вы уверены, что хотите отменить запись?')">
                                            Отменить
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>