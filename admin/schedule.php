<?php
require_once '../config/database.php';
require_once '../config/admin_config.php';
requireAdminAuth();

// Получаем выбранного мастера или всех
$master_id = $_GET['master_id'] ?? null;

// Получаем список мастеров для селектора
$masters = $db->query("SELECT * FROM masters WHERE is_active = TRUE ORDER BY full_name")->fetchAll();

// Обработка сохранения расписания
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $master_id = $_POST['master_id'];
    $work_days = $_POST['work_days'] ?? [];
    $start_times = $_POST['start_times'] ?? [];
    $end_times = $_POST['end_times'] ?? [];
    
    // Удаляем старое расписание для этого мастера
    $stmt = $db->prepare("DELETE FROM work_schedule WHERE master_id = ?");
    $stmt->execute([$master_id]);
    
    // Добавляем новые рабочие дни
    foreach ($work_days as $day) {
        if (isset($start_times[$day]) && isset($end_times[$day]) && 
            !empty($start_times[$day]) && !empty($end_times[$day])) {
            
            $stmt = $db->prepare("
                INSERT INTO work_schedule (master_id, day_of_week, start_time, end_time, is_working_day) 
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$master_id, $day, $start_times[$day], $end_times[$day]]);
        } else {
            // Добавляем нерабочий день
            $stmt = $db->prepare("
                INSERT INTO work_schedule (master_id, day_of_week, is_working_day) 
                VALUES (?, ?, 0)
            ");
            $stmt->execute([$master_id, $day]);
        }
    }
    
    $message = "Расписание успешно сохранено!";
}

// Если выбран мастер, загружаем его расписание
$schedule = [];
$current_master = null;

if ($master_id) {
    $master_id = intval($master_id);
    
    // Получаем информацию о мастере
    $stmt = $db->prepare("SELECT * FROM masters WHERE id = ?");
    $stmt->execute([$master_id]);
    $current_master = $stmt->fetch();
    
    // Получаем расписание мастера
    $stmt = $db->prepare("SELECT * FROM work_schedule WHERE master_id = ? ORDER BY day_of_week");
    $stmt->execute([$master_id]);
    $schedule_data = $stmt->fetchAll();
    
    // Преобразуем в удобный формат
    foreach ($schedule_data as $day) {
        $schedule[$day['day_of_week']] = $day;
    }
}

// Названия дней недели
$days_of_week = [
    1 => 'Понедельник',
    2 => 'Вторник',
    3 => 'Среда',
    4 => 'Четверг',
    5 => 'Пятница',
    6 => 'Суббота',
    7 => 'Воскресенье'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление расписанием - Админ-панель</title>
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
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn { padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: 600; cursor: pointer; border: none; transition: all 0.3s; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
        .form-group select, .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        .schedule-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .schedule-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .schedule-table { width: 100%; border-collapse: collapse; }
        .schedule-table th, .schedule-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        .schedule-table th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
        .schedule-table tr:hover { background: #f9f9f9; }
        .time-inputs { display: flex; gap: 10px; align-items: center; }
        .time-inputs input { width: 120px; }
        .non-working-day { color: #999; font-style: italic; }
        .checkbox-container { display: flex; align-items: center; gap: 10px; }
        .checkbox-container input[type="checkbox"] { width: auto; }
        .master-info { background: #e8f4fc; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .master-info h3 { margin-top: 0; color: #2c3e50; }
        .master-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 5px; text-align: center; }
        .stat-value { font-size: 24px; font-weight: bold; color: #3498db; }
        .stat-label { font-size: 14px; color: #666; margin-top: 5px; }
        .day-off { background: #ffeaea; }
        .working-day { background: #eaffea; }
        .today { background: #fff8e1; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Сайдбар -->
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2><i class="fas fa-cut"></i> Админ-панель</h2>
                <small>Парикмахерская "Стиль"</small>
            </div>
            <ul class="admin-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Дашборд</a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> Записи</a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i> Клиенты</a></li>
                <li><a href="masters.php"><i class="fas fa-user-tie"></i> Мастера</a></li>
                <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Услуги</a></li>
                <li><a href="schedule.php" class="active"><i class="fas fa-clock"></i> Расписание</a></li>
                <li><a href="receipts.php"><i class="fas fa-receipt"></i> Чеки</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выход</a></li>
            </ul>
        </div>
        
        <!-- Основной контент -->
        <div class="admin-content">
            <h1><i class="fas fa-clock"></i> Управление расписанием</h1>
            
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <!-- Выбор мастера -->
            <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <form method="GET" style="display: flex; gap: 15px; align-items: center;">
                    <div style="flex: 1;">
                        <label for="master_id">Выберите мастера:</label>
                        <select id="master_id" name="master_id" onchange="this.form.submit()" required>
                            <option value="">-- Выберите мастера --</option>
                            <?php foreach ($masters as $master): ?>
                                <option value="<?php echo $master['id']; ?>" 
                                        <?php echo ($master_id == $master['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($master['full_name']); ?> 
                                    (<?php echo htmlspecialchars($master['specialization']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Загрузить расписание</button>
                    </div>
                </form>
            </div>
            
            <?php if ($current_master): ?>
                <!-- Информация о мастере -->
                <div class="master-info">
                    <h3>Мастер: <?php echo htmlspecialchars($current_master['full_name']); ?></h3>
                    <p>Специализация: <?php echo htmlspecialchars($current_master['specialization']); ?></p>
                    <p>Телефон: <?php echo htmlspecialchars($current_master['phone']); ?></p>
                    
                    <?php
                    // Статистика мастера
                    $today = date('N'); // Текущий день недели (1-7)
                    
                    // Записи на сегодня
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as count 
                        FROM appointments 
                        WHERE master_id = ? 
                        AND appointment_date = CURDATE() 
                        AND status = 'scheduled'
                    ");
                    $stmt->execute([$master_id]);
                    $today_appointments = $stmt->fetch()['count'];
                    
                    // Записи на эту неделю
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as count 
                        FROM appointments 
                        WHERE master_id = ? 
                        AND appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                        AND status = 'scheduled'
                    ");
                    $stmt->execute([$master_id]);
                    $week_appointments = $stmt->fetch()['count'];
                    ?>
                    
                    <div class="master-stats">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $today_appointments; ?></div>
                            <div class="stat-label">Записей сегодня</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $week_appointments; ?></div>
                            <div class="stat-label">Записей на неделю</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">
                                <?php 
                                $working_days = count(array_filter($schedule, function($day) {
                                    return $day['is_working_day'] == 1;
                                }));
                                echo $working_days;
                                ?>
                            </div>
                            <div class="stat-label">Рабочих дней</div>
                        </div>
                    </div>
                </div>
                
                <!-- Форма расписания -->
                <form method="POST">
                    <input type="hidden" name="master_id" value="<?php echo $master_id; ?>">
                    
                    <div class="schedule-container">
                        <div class="schedule-header">
                            <h2>Настройка расписания</h2>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Сохранить расписание
                            </button>
                        </div>
                        
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th width="200">День недели</th>
                                    <th width="100">Рабочий день</th>
                                    <th width="300">Время работы</th>
                                    <th>Примечание</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($day = 1; $day <= 7; $day++): 
                                    $day_data = $schedule[$day] ?? [
                                        'day_of_week' => $day,
                                        'is_working_day' => 0,
                                        'start_time' => '09:00',
                                        'end_time' => '18:00'
                                    ];
                                    $is_today = ($day == $today);
                                ?>
                                <tr class="<?php echo $is_today ? 'today' : ''; ?> <?php echo $day_data['is_working_day'] ? 'working-day' : 'day-off'; ?>">
                                    <td>
                                        <strong><?php echo $days_of_week[$day]; ?></strong>
                                        <?php if ($is_today): ?>
                                            <span style="color: #ffc107; margin-left: 10px;">(Сегодня)</span>
                                        <?php endif; ?>
                                        <input type="hidden" name="work_days[]" value="<?php echo $day; ?>">
                                    </td>
                                    <td>
                                        <div class="checkbox-container">
                                            <input type="checkbox" 
                                                   id="work_day_<?php echo $day; ?>" 
                                                   name="work_days_check[]" 
                                                   value="<?php echo $day; ?>"
                                                   <?php echo $day_data['is_working_day'] ? 'checked' : ''; ?>
                                                   onchange="toggleTimeInputs(<?php echo $day; ?>)">
                                            <label for="work_day_<?php echo $day; ?>">Работает</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="time-inputs" id="time_inputs_<?php echo $day; ?>">
                                            <input type="time" 
                                                   name="start_times[<?php echo $day; ?>]" 
                                                   value="<?php echo $day_data['is_working_day'] ? $day_data['start_time'] : '09:00'; ?>"
                                                   <?php echo !$day_data['is_working_day'] ? 'disabled' : ''; ?>>
                                            <span>—</span>
                                            <input type="time" 
                                                   name="end_times[<?php echo $day; ?>]" 
                                                   value="<?php echo $day_data['is_working_day'] ? $day_data['end_time'] : '18:00'; ?>"
                                                   <?php echo !$day_data['is_working_day'] ? 'disabled' : ''; ?>>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($day_data['is_working_day']): ?>
                                            <span style="color: #27ae60;">
                                                Рабочий день: <?php echo $day_data['start_time']; ?> - <?php echo $day_data['end_time']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="non-working-day">Выходной</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                            <h3>Примечания:</h3>
                            <ul style="margin: 10px 0; padding-left: 20px;">
                                <li>Для установки выходного дня снимите галочку "Работает"</li>
                                <li>Расписание сохраняется отдельно для каждого мастера</li>
                                <li>Изменения вступают в силу немедленно</li>
                                <li>Записи вне рабочего времени будут недоступны для клиентов</li>
                            </ul>
                        </div>
                    </div>
                </form>
                
                <!-- Быстрые действия -->
                <div style="margin-top: 30px; display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="appointments.php?master_id=<?php echo $master_id; ?>" class="btn btn-primary">
                        <i class="fas fa-calendar-alt"></i> Записи мастера
                    </a>
                    <a href="masters.php?edit=<?php echo $master_id; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Редактировать профиль
                    </a>
                    <button onclick="setStandardSchedule()" class="btn btn-info">
                        <i class="fas fa-clock"></i> Установить стандартное расписание
                    </button>
                </div>
            <?php else: ?>
                <!-- Сообщение, если мастер не выбран -->
                <div style="text-align: center; padding: 50px 20px; background: white; border-radius: 10px;">
                    <i class="fas fa-clock" style="font-size: 60px; color: #ddd; margin-bottom: 20px;"></i>
                    <h2>Выберите мастера</h2>
                    <p>Пожалуйста, выберите мастера из списка выше, чтобы управлять его расписанием.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    // Функция для включения/выключения полей ввода времени
    function toggleTimeInputs(day) {
        const checkbox = document.getElementById('work_day_' + day);
        const timeInputs = document.getElementById('time_inputs_' + day);
        const inputs = timeInputs.getElementsByTagName('input');
        
        for (let input of inputs) {
            input.disabled = !checkbox.checked;
        }
        
        // Изменяем класс строки
        const row = checkbox.closest('tr');
        if (checkbox.checked) {
            row.classList.remove('day-off');
            row.classList.add('working-day');
        } else {
            row.classList.remove('working-day');
            row.classList.add('day-off');
        }
    }
    
    // Установить стандартное расписание (Пн-Пт: 9:00-18:00, Сб: 10:00-17:00, Вс: выходной)
    function setStandardSchedule() {
        if (!confirm('Установить стандартное расписание? Текущие настройки будут перезаписаны.')) {
            return;
        }
        
        // Пн-Пт: 9:00-18:00
        for (let day = 1; day <= 5; day++) {
            document.getElementById('work_day_' + day).checked = true;
            document.querySelector('input[name="start_times[' + day + ']"]').value = '09:00';
            document.querySelector('input[name="end_times[' + day + ']"]').value = '18:00';
            toggleTimeInputs(day);
        }
        
        // Сб: 10:00-17:00
        document.getElementById('work_day_6').checked = true;
        document.querySelector('input[name="start_times[6]"]').value = '10:00';
        document.querySelector('input[name="end_times[6]"]').value = '17:00';
        toggleTimeInputs(6);
        
        // Вс: выходной
        document.getElementById('work_day_7').checked = false;
        toggleTimeInputs(7);
        
        alert('Стандартное расписание установлено. Не забудьте сохранить изменения!');
    }
    
    // Автоматически включаем/выключаем поля при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        for (let day = 1; day <= 7; day++) {
            const checkbox = document.getElementById('work_day_' + day);
            if (checkbox) {
                toggleTimeInputs(day);
            }
        }
    });
    </script>
</body>
</html>