<?php
require_once '../config/database.php';
require_once '../config/admin_config.php';
requireAdminAuth();

// Обработка изменений статуса
if (isset($_POST['change_status'])) {
    $id = intval($_POST['id']);
    $new_status = $_POST['status'];
    
    // Проверяем, что статус допустим
    $allowed_statuses = ['scheduled', 'completed', 'cancelled', 'no_show'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        $message = "Статус записи успешно изменен";
    } else {
        $error_message = "Неверный статус";
    }
}

// Обработка быстрых действий
if (isset($_GET['action'])) {
    $id = $_GET['id'] ?? null;
    
    if ($_GET['action'] === 'complete' && $id) {
        $stmt = $db->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Запись успешно помечена как выполненная";
    } elseif ($_GET['action'] === 'cancel' && $id) {
        $stmt = $db->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Запись успешно отменена";
    } elseif ($_GET['action'] === 'no_show' && $id) {
        $stmt = $db->prepare("UPDATE appointments SET status = 'no_show' WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Запись помечена как неявка";
    } elseif ($_GET['action'] === 'reschedule' && $id) {
        $stmt = $db->prepare("UPDATE appointments SET status = 'scheduled' WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Запись возвращена в запланированные";
    }
}

// Получаем все записи
$stmt = $db->query("
    SELECT a.*, 
           c.full_name as client_name, 
           m.full_name as master_name, 
           s.name as service_name,
           s.price
    FROM appointments a
    JOIN clients c ON a.client_id = c.id
    JOIN masters m ON a.master_id = m.id
    JOIN services s ON a.service_id = s.id
    ORDER BY a.appointment_date DESC, a.start_time DESC
");
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление записями - Админ-панель</title>
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
        .btn-warning { background: #f39c12; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-scheduled { background: #3498db; color: white; }
        .status-completed { background: #2ecc71; color: white; }
        .status-cancelled { background: #e74c3c; color: white; }
        .status-no_show { background: #f39c12; color: white; }
        .action-buttons { display: flex; gap: 5px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #3498db; color: white; }
        
        /* Стили для формы изменения статуса */
        .status-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .status-select {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 12px;
            min-width: 120px;
        }
        
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }
        
        .quick-action-btn {
            padding: 3px 8px;
            font-size: 11px;
            border-radius: 3px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 10px;
            min-width: 300px;
            max-width: 500px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .modal-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin: 0;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
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
                <li><a href="appointments.php" class="active"><i class="fas fa-calendar-alt"></i> Записи</a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i> Клиенты</a></li>
                <li><a href="masters.php"><i class="fas fa-user-tie"></i> Мастера</a></li>
                <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Услуги</a></li>
                <li><a href="schedule.php"><i class="fas fa-clock"></i> Расписание</a></li>
                <li><a href="receipts.php"><i class="fas fa-receipt"></i> Чеки</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выход</a></li>
            </ul>
        </div>
        
        <!-- Основной контент -->
        <div class="admin-content">
            <h1><i class="fas fa-calendar-alt"></i> Управление записями</h1>
            
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Клиент</th>
                            <th>Мастер</th>
                            <th>Услуга</th>
                            <th>Дата и время</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">Записи не найдены</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td>#<?php echo $appointment['id']; ?></td>
                                <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['master_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($appointment['appointment_date'] . ' ' . $appointment['start_time'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                        <?php 
                                        $statuses = [
                                            'scheduled' => 'Запланировано',
                                            'completed' => 'Выполнено',
                                            'cancelled' => 'Отменено',
                                            'no_show' => 'Неявка'
                                        ];
                                        echo $statuses[$appointment['status']] ?? $appointment['status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- Форма для изменения статуса -->
                                        <form method="POST" class="status-form" onsubmit="return confirmStatusChange(<?php echo $appointment['id']; ?>, this)">
                                            <input type="hidden" name="id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="change_status" value="1">
                                            <select name="status" class="status-select" onchange="this.form.submit()">
                                                <option value="scheduled" <?php echo $appointment['status'] == 'scheduled' ? 'selected' : ''; ?>>Запланировано</option>
                                                <option value="completed" <?php echo $appointment['status'] == 'completed' ? 'selected' : ''; ?>>Выполнено</option>
                                                <option value="cancelled" <?php echo $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>Отменено</option>
                                                <option value="no_show" <?php echo $appointment['status'] == 'no_show' ? 'selected' : ''; ?>>Неявка</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>
                                        
                                        <!-- Быстрые действия -->
                                        <div class="quick-actions">
                                            <?php if ($appointment['status'] === 'scheduled'): ?>
                                            <a href="?action=complete&id=<?php echo $appointment['id']; ?>" 
                                               class="btn btn-sm btn-success quick-action-btn" 
                                               title="Пометить как выполненное">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="?action=cancel&id=<?php echo $appointment['id']; ?>" 
                                               class="btn btn-sm btn-danger quick-action-btn" 
                                               title="Отменить запись">
                                                <i class="fas fa-times"></i>
                                            </a>
                                            <a href="?action=no_show&id=<?php echo $appointment['id']; ?>" 
                                               class="btn btn-sm btn-warning quick-action-btn" 
                                               title="Отметить как неявка">
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                            <?php else: ?>
                                            <a href="?action=reschedule&id=<?php echo $appointment['id']; ?>" 
                                               class="btn btn-sm btn-info quick-action-btn" 
                                               title="Вернуть в запланированные">
                                                <i class="fas fa-calendar-plus"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Статистика записей -->
            <div style="margin-top: 30px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h3>Статистика записей</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-top: 15px;">
                    <?php
                    $statusStats = [
                        'scheduled' => 'Запланировано',
                        'completed' => 'Выполнено', 
                        'cancelled' => 'Отменено',
                        'no_show' => 'Неявка'
                    ];
                    
                    foreach ($statusStats as $status => $label):
                        // Используем новую переменную для запроса, чтобы не перезаписывать $stmt
                        $countStmt = $db->prepare("SELECT COUNT(*) as count FROM appointments WHERE status = ?");
                        $countStmt->execute([$status]);
                        $result = $countStmt->fetch();
                        $count = $result['count'] ?? 0;
                    ?>
                    <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <div style="font-size: 24px; font-weight: bold; color: #2c3e50;"><?php echo $count; ?></div>
                        <div style="color: #7f8c8d; margin-top: 5px;"><?php echo $label; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для подтверждения изменения статуса -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Изменение статуса записи</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalBody">
                <!-- Контент будет добавлен через JavaScript -->
            </div>
            <div class="modal-actions">
                <button onclick="confirmStatusChange()" class="btn btn-success">Подтвердить</button>
                <button onclick="closeModal()" class="btn btn-danger">Отмена</button>
            </div>
        </div>
    </div>
    
    <script>
    // Переменные для хранения данных модального окна
    let currentAppointmentId = null;
    let currentForm = null;
    let newStatus = null;
    
    // Функция для подтверждения изменения статуса
    function confirmStatusChange(appointmentId, form) {
        if (!form) return true; // Если форма не передана, отправляем как есть
        
        const select = form.querySelector('select[name="status"]');
        const currentStatus = select.selectedOptions[0].text;
        const newStatusValue = select.value;
        const newStatusText = select.options[select.selectedIndex].text;
        
        // Проверяем, изменился ли статус
        if (select.value === '<?php echo isset($appointment) ? $appointment["status"] : ""; ?>') {
            return false; // Статус не изменился
        }
        
        // Отображаем модальное окно
        document.getElementById('modalBody').innerHTML = `
            <p>Вы действительно хотите изменить статус записи #${appointmentId}?</p>
            <p><strong>Текущий статус:</strong> ${currentStatus}</p>
            <p><strong>Новый статус:</strong> ${newStatusText}</p>
        `;
        
        currentAppointmentId = appointmentId;
        currentForm = form;
        newStatus = newStatusValue;
        
        document.getElementById('statusModal').style.display = 'flex';
        return false; // Предотвращаем отправку формы
    }
    
    // Функция подтверждения изменения статуса из модального окна
    function confirmStatusChangeModal() {
        if (currentForm) {
            // Устанавливаем выбранное значение в селект
            const select = currentForm.querySelector('select[name="status"]');
            select.value = newStatus;
            
            // Отправляем форму
            currentForm.submit();
        }
        closeModal();
    }
    
    // Функция закрытия модального окна
    function closeModal() {
        document.getElementById('statusModal').style.display = 'none';
        currentAppointmentId = null;
        currentForm = null;
        newStatus = null;
    }
    
    // Закрытие модального окна при клике вне его
    window.onclick = function(event) {
        const modal = document.getElementById('statusModal');
        if (event.target === modal) {
            closeModal();
        }
    }
    
    // Подтверждение быстрых действий
    document.querySelectorAll('.quick-action-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const action = this.getAttribute('href').split('action=')[1].split('&')[0];
            let message = '';
            
            switch(action) {
                case 'complete':
                    message = 'Вы уверены, что хотите отметить запись как выполненную?';
                    break;
                case 'cancel':
                    message = 'Вы уверены, что хотите отменить запись?';
                    break;
                case 'no_show':
                    message = 'Вы уверены, что хотите отметить запись как неявку?';
                    break;
                case 'reschedule':
                    message = 'Вы уверены, что хотите вернуть запись в запланированные?';
                    break;
                default:
                    message = 'Вы уверены, что хотите выполнить это действие?';
            }
            
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Автоматическое скрытие уведомлений через 5 секунд
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 500);
        });
    }, 5000);
    </script>
</body>
</html>