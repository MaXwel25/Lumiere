<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Обработка формы записи
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $client_id = $_POST['client_id'] ?? null;
        $master_id = $_POST['master_id'];
        $service_id = $_POST['service_id'];
        $appointment_date = $_POST['appointment_date'];
        $start_time = $_POST['start_time'];
        $client_name = $_POST['client_name'];
        $client_phone = $_POST['client_phone'];
        $client_email = $_POST['client_email'] ?? null;
        $notes = $_POST['notes'] ?? '';

        // Если клиент новый, добавляем его
        if (!$client_id) {
            $stmt = $db->prepare("INSERT INTO clients (full_name, phone, email) VALUES (?, ?, ?)");
            $stmt->execute([$client_name, $client_phone, $client_email]);
            $client_id = $db->lastInsertId();
        }

        // Добавляем запись
        $stmt = $db->prepare("
            INSERT INTO appointments 
            (client_id, master_id, service_id, appointment_date, start_time, notes, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'scheduled')
        ");
        
        $stmt->execute([
            $client_id,
            $master_id,
            $service_id,
            $appointment_date,
            $start_time,
            $notes
        ]);
        
        $appointment_id = $db->lastInsertId();
        
        // Создаем чек
        $stmt = $db->prepare("
            INSERT INTO receipts (appointment_id, total_amount, final_amount, payment_method) 
            SELECT ?, price, price, 'cash' 
            FROM services WHERE id = ?
        ");
        $stmt->execute([$appointment_id, $service_id]);
        
        $success = "Вы успешно записаны! Номер вашей записи: #{$appointment_id}";
        
    } catch (PDOException $e) {
        $error = "Ошибка при записи: " . $e->getMessage();
    }
}

// Получаем данные для форм
$masters = $db->query("SELECT * FROM masters WHERE is_active = TRUE")->fetchAll();
$services = $db->query("SELECT * FROM services WHERE is_active = TRUE")->fetchAll();

require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Запись на прием - Парикмахерская "Стиль"</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="booking-section">
        <div class="container">
            <h1 class="section-title">Онлайн запись</h1>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="booking.php" method="POST" class="booking-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="client_name">Ваше имя *</label>
                        <input type="text" id="client_name" name="client_name" required>
                    </div>
                    <div class="form-group">
                        <label for="client_phone">Телефон *</label>
                        <input type="tel" id="client_phone" name="client_phone" required>
                    </div>
                    <div class="form-group">
                        <label for="client_email">Email</label>
                        <input type="email" id="client_email" name="client_email">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="service_id">Услуга *</label>
                        <select id="service_id" name="service_id" required onchange="updateDuration()">
                            <option value="">Выберите услугу</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" 
                                        data-price="<?php echo $service['price']; ?>"
                                        data-duration="<?php echo $service['duration_min']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> - 
                                    <?php echo number_format($service['price'], 0, ',', ' '); ?> ₽
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="master_id">Мастер *</label>
                        <select id="master_id" name="master_id" required onchange="loadAvailableTime()">
                            <option value="">Выберите мастера</option>
                            <?php foreach ($masters as $master): ?>
                                <option value="<?php echo $master['id']; ?>">
                                    <?php echo htmlspecialchars($master['full_name']); ?> - 
                                    <?php echo htmlspecialchars($master['specialization']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_date">Дата *</label>
                        <input type="text" id="appointment_date" name="appointment_date" 
                               class="datepicker" required onchange="loadAvailableTime()">
                    </div>
                    <div class="form-group">
                        <label for="start_time">Время *</label>
                        <select id="start_time" name="start_time" required>
                            <option value="">Выберите время</option>
                            <!-- Время будет загружено через JavaScript -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Длительность</label>
                        <div id="duration_display">0 минут</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Дополнительные пожелания</label>
                    <textarea id="notes" name="notes" rows="3" 
                              placeholder="Укажите ваши пожелания к стрижке..."></textarea>
                </div>

                <div class="form-summary">
                    <h3>Итого:</h3>
                    <div class="summary-item">
                        <span>Стоимость услуги:</span>
                        <span id="price_display">0 ₽</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-large">Записаться</button>
            </form>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
    <script src="assets/js/script.js"></script>
    
    <script>
    // Инициализация календаря
    flatpickr('.datepicker', {
        locale: 'ru',
        minDate: 'today',
        dateFormat: 'Y-m-d',
        disable: [
            function(date) {
                // Отключаем воскресенье
                return date.getDay() === 0;
            }
        ]
    });

    function updateDuration() {
        const serviceSelect = document.getElementById('service_id');
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        const duration = selectedOption.getAttribute('data-duration') || 0;
        const price = selectedOption.getAttribute('data-price') || 0;
        
        document.getElementById('duration_display').textContent = duration + ' минут';
        document.getElementById('price_display').textContent = 
            parseInt(price).toLocaleString('ru-RU') + ' ₽';
    }

    async function loadAvailableTime() {
        const masterId = document.getElementById('master_id').value;
        const date = document.getElementById('appointment_date').value;
        const timeSelect = document.getElementById('start_time');
        
        if (!masterId || !date) return;
        
        timeSelect.innerHTML = '<option value="">Загрузка...</option>';
        
        try {
            const response = await fetch('api/get_available_time.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `master_id=${masterId}&date=${date}`
            });
            
            const times = await response.json();
            
            timeSelect.innerHTML = '<option value="">Выберите время</option>';
            times.forEach(time => {
                const option = document.createElement('option');
                option.value = time;
                option.textContent = time;
                timeSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Error:', error);
            timeSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
        }
    }
    </script>
</body>
</html>
