<?php
// booking.php
require_once 'includes/header.php';
require_once 'includes/auth.php';

// Проверяем, авторизован ли клиент
if (!isClientLoggedIn()) {
    header('Location: profile/login.php');
    exit;
}

$clientId = $_SESSION['client_id'];
$message = '';
$error = '';

// Получаем данные клиента для отображения
$stmt = $db->prepare("SELECT full_name, phone, email FROM clients WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch();
if (!$client) {
    // Если клиент не найден (странная ситуация), разлогиниваем
    session_destroy();
    header('Location: profile/login.php');
    exit;
}

// получаем активных мастеров для выбора
$stmt = $db->query("SELECT * FROM masters WHERE is_active = TRUE ORDER BY full_name");
$activeMasters = $stmt->fetchAll();

// получаем активные услуги для выбора
$stmt = $db->query("SELECT * FROM services WHERE is_active = TRUE ORDER BY name");
$activeServices = $stmt->fetchAll();

// обработка формы записи
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $master_id = $_POST['master_id'] ?? '';
        $service_id = $_POST['service_id'] ?? '';
        $appointment_date = $_POST['appointment_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';

        // валидация данных
        if (empty($master_id) || empty($service_id) || empty($appointment_date) || empty($start_time)) {
            throw new Exception('Пожалуйста, заполните все обязательные поля');
        }

        if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
            throw new Exception('Нельзя записаться на прошедшую дату');
        }

        // получаем продолжительность услуги
        $service_stmt = $db->prepare("SELECT duration_min FROM services WHERE id = ?");
        $service_stmt->execute([$service_id]);
        $service = $service_stmt->fetch();
        $duration_min = $service['duration_min'] ?? 60;

        // рассчитываем время окончания
        $end_time = date('H:i', strtotime($start_time) + $duration_min * 60);

        // проверяем, доступен ли мастер в указанное время
        $check_stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM appointments 
            WHERE master_id = ? 
            AND appointment_date = ? 
            AND (
                (? BETWEEN start_time AND end_time) OR
                (? BETWEEN start_time AND end_time) OR
                (start_time BETWEEN ? AND ?)
            )
            AND status != 'cancelled'
        ");
        $check_stmt->execute([$master_id, $appointment_date, $start_time, $end_time, $start_time, $end_time]);
        $result = $check_stmt->fetch();

        if ($result['count'] > 0) {
            throw new Exception('К сожалению, мастер занят в указанное время. Пожалуйста, выберите другое время.');
        }

        // создаем запись
        $insert_appointment = $db->prepare("
            INSERT INTO appointments 
            (client_id, master_id, service_id, appointment_date, start_time, end_time, status, notes) 
            VALUES (?, ?, ?, ?, ?, ?, 'scheduled', ?)
        ");
        $notes = "Онлайн-запись через личный кабинет";
        $insert_appointment->execute([$clientId, $master_id, $service_id, $appointment_date, $start_time, $end_time, $notes]);

        $message = "Спасибо за запись! Ваша запись на " . date('d.m.Y', strtotime($appointment_date)) . " в " . $start_time . " успешно создана.";

    } catch (Exception $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}
?>
<!-- герой-секция -->
<section class="hero" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1560066984-138dadb4c035?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80'); background-size: cover; background-position: center;">
    <div class="container">
        <div class="hero-content">
            <h1>Онлайн запись</h1>
            <p>Выберите удобное время и мастера для вашей процедуры.</p>
        </div>
    </div>
</section>

<!-- форма записи -->
<section class="section booking-section">
    <div class="container">
        <div class="booking-form">
            <h2 style="margin-bottom: 30px; font-family: 'Playfair Display', serif; color: white;">Записаться на процедуру</h2>

            <?php if ($message): ?>
            <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; color: #2ecc71;">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; color: #e74c3c;">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Информация о клиенте -->
            <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 5px; margin-bottom: 20px; color: white;">
                <p><i class="fas fa-user"></i> <strong><?= htmlspecialchars($client['full_name']) ?></strong></p>
                <p><i class="fas fa-phone"></i> <?= htmlspecialchars($client['phone']) ?></p>
                <?php if (!empty($client['email'])): ?>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($client['email']) ?></p>
                <?php endif; ?>
            </div>

            <form method="POST" id="bookingForm">
                <div class="form-group">
                    <label for="master_id">Выберите мастера</label>
                    <select id="master_id" name="master_id" class="form-control" required>
                        <option value="">-- Выберите мастера --</option>
                        <?php foreach ($activeMasters as $master): ?>
                        <option value="<?= $master['id'] ?>" <?= isset($_POST['master_id']) && $_POST['master_id'] == $master['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($master['full_name']) ?> - <?= htmlspecialchars($master['specialization']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="service_id">Выберите услугу</label>
                    <select id="service_id" name="service_id" class="form-control" required>
                        <option value="">-- Выберите услугу --</option>
                        <?php foreach ($activeServices as $service): ?>
                        <option value="<?= $service['id'] ?>" <?= isset($_POST['service_id']) && $_POST['service_id'] == $service['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($service['name']) ?> - <?= number_format($service['price'], 0, ',', ' ') ?> ₽ (<?= $service['duration_min'] ?> мин)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="appointment_date">Дата записи</label>
                    <input type="date" id="appointment_date" name="appointment_date" class="form-control" required
                           min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($_POST['appointment_date'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="start_time">Время начала</label>
                    <input type="time" id="start_time" name="start_time" class="form-control" required
                           value="<?= htmlspecialchars($_POST['start_time'] ?? '10:00') ?>">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; background-color: #d4af37; color: #2c3e50; font-weight: bold; padding: 15px;">
                    <i class="fas fa-calendar-check"></i> Записаться
                </button>
            </form>
        </div>
    </div>
</section>

<!-- правила записи -->
<section class="section" style="background-color: var(--light-gray);">
    <div class="container">
        <h2 class="section-title">Правила записи</h2>
        <div class="services-grid">
            <div class="service-card">
                <h3><i class="fas fa-clock" style="color: var(--primary-color); margin-right: 10px;"></i> Отмена записи</h3>
                <p>Пожалуйста, предупреждайте об отмене записи минимум за 2 часа до назначенного времени. Это позволит нам предложить время другим клиентам.</p>
            </div>
            <div class="service-card">
                <h3><i class="fas fa-mobile-alt" style="color: var(--primary-color); margin-right: 10px;"></i> Подтверждение</h3>
                <p>Мы обязательно свяжемся с вами для подтверждения записи. Если вы не получили звонок в течение часа, пожалуйста, перезвоните нам.</p>
            </div>
            <div class="service-card">
                <h3><i class="fas fa-shield-alt" style="color: var(--primary-color); margin-right: 10px;"></i> Гарантия качества</h3>
                <p>Мы гарантируем качество наших услуг. Если вы не довольны результатом, мы бесплатно сделаем корректировку в течение 7 дней.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>