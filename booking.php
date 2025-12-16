<?php
require_once 'includes/header.php';

// получаем активных мастеров для выбора
$stmt = $db->query("SELECT * FROM masters WHERE is_active = TRUE ORDER BY full_name");
$activeMasters = $stmt->fetchAll();

// получаем активные услуги для выбора
$stmt = $db->query("SELECT * FROM services WHERE is_active = TRUE ORDER BY name");
$activeServices = $stmt->fetchAll();

// обработка формы записи
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $client_name = $_POST['client_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $master_id = $_POST['master_id'] ?? '';
        $service_id = $_POST['service_id'] ?? '';
        $appointment_date = $_POST['appointment_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        
        // валидация данных
        if (empty($client_name) || empty($phone) || empty($master_id) || empty($service_id) || empty($appointment_date) || empty($start_time)) {
            throw new Exception('Пожалуйста, заполните все поля формы');
        }
        
        if (!preg_match('/^\+7\s?\(\d{3}\)\s?\d{3}-\d{2}-\d{2}$/', $phone)) {
            throw new Exception('Неверный формат номера телефона');
        }
        
        if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
            throw new Exception('Нельзя записаться на прошедшую дату');
        }
        
        // проверяем, есть ли такой клиент
        $client_stmt = $db->prepare("SELECT id FROM clients WHERE phone = ?");
        $client_stmt->execute([$phone]);
        $client = $client_stmt->fetch();
        
        $client_id = 0;
        if ($client) {
            $client_id = $client['id'];
        } else {
            // создаем нового клиента
            $insert_client = $db->prepare("INSERT INTO clients (full_name, phone) VALUES (?, ?)");
            $insert_client->execute([$client_name, $phone]);
            $client_id = $db->lastInsertId();
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
        $notes = "Онлайн-запись через сайт";
        $insert_appointment->execute([$client_id, $master_id, $service_id, $appointment_date, $start_time, $end_time, $notes]);
        
        $message = "Спасибо за запись! Мы свяжемся с вами для подтверждения. Ваша запись на " . date('d.m.Y', strtotime($appointment_date)) . " в " . $start_time;
        
    } catch (Exception $e) {
        $message = "Ошибка: " . $e->getMessage();
    }
}
?>
<!-- герой-секция -->
<section class="hero" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1560066984-138dadb4c035?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80'); background-size: cover; background-position: center;">
    <div class="container">
        <div class="hero-content">
            <h1>Онлайн запись</h1>
            <p>Выберите удобное время и мастера для вашей процедуры. Мы свяжемся с вами для подтверждения записи.</p>
        </div>
    </div>
</section>

<!-- форма записи -->
<section class="section booking-section">
    <div class="container">
        <div class="booking-form">
            <h2 style="margin-bottom: 30px; font-family: 'Playfair Display', serif; color: white;">Записаться на процедуру</h2>
            
            <?php if ($message): ?>
            <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                <p style="color: <?php echo strpos($message, 'Ошибка') !== false ? '#e74c3c' : '#2ecc71'; ?>;">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <form method="POST" id="bookingForm">
                <div class="form-group">
                    <label for="client_name">Ваше имя</label>
                    <input type="text" id="client_name" name="client_name" class="form-control" required 
                           value="<?php echo $_POST['client_name'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone" class="form-control" required 
                           placeholder="+7 (___) ___-__-__"
                           value="<?php echo $_POST['phone'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="master_id">Выберите мастера</label>
                    <select id="master_id" name="master_id" class="form-control" required>
                        <option value="">-- Выберите мастера --</option>
                        <?php foreach ($activeMasters as $master): ?>
                        <option value="<?php echo $master['id']; ?>" <?php echo isset($_POST['master_id']) && $_POST['master_id'] == $master['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($master['full_name']); ?> - <?php echo htmlspecialchars($master['specialization']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="service_id">Выберите услугу</label>
                    <select id="service_id" name="service_id" class="form-control" required>
                        <option value="">-- Выберите услугу --</option>
                        <?php foreach ($activeServices as $service): ?>
                        <option value="<?php echo $service['id']; ?>" <?php echo isset($_POST['service_id']) && $_POST['service_id'] == $service['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($service['name']); ?> - <?php echo number_format($service['price'], 0, ',', ' '); ?> ₽ (<?php echo $service['duration_min']; ?> мин)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="appointment_date">Дата записи</label>
                    <input type="date" id="appointment_date" name="appointment_date" class="form-control" required
                           min="<?php echo date('Y-m-d'); ?>"
                           value="<?php echo $_POST['appointment_date'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="start_time">Время начала</label>
                    <input type="time" id="start_time" name="start_time" class="form-control" required
                           value="<?php echo $_POST['start_time'] ?? '10:00'; ?>">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('phone');
        
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 0) {
                value = '+7 (' + value.substring(1, 4) + ') ' + value.substring(4, 7) + '-' + value.substring(7, 9) + '-' + value.substring(9, 11);
            }
            
            e.target.value = value.substring(0, 18);
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>