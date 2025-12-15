<?php
require_once '../config/database.php';
require_once '../config/admin_config.php';
requireAdminAuth();

// Обработка действий
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
                                        <?php if ($appointment['status'] === 'scheduled'): ?>
                                        <a href="?action=complete&id=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-sm btn-success" title="Пометить как выполненное">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="?action=cancel&id=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-sm btn-danger" title="Отменить запись"
                                           onclick="return confirm('Вы уверены, что хотите отменить эту запись?')">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        <?php else: ?>
                                            <span class="badge"><?php echo $statuses[$appointment['status']]; ?></span>
                                        <?php endif; ?>
                                        
                                        <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </a>
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
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointments WHERE status = ?");
                        $stmt->execute([$status]);
                        $count = $stmt->fetch()['count'];
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
</body>
</html>