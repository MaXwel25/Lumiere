<?php
// dashboard.php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdminAuth();

// получаем статистику
$stats = [];

// количество записей сегодня
$stmt = $db->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURRENT_DATE");
$stats['today_appointments'] = $stmt->fetch()['count'];

// количество активных клиентов
$stmt = $db->query("SELECT COUNT(*) as count FROM clients");
$stats['total_clients'] = $stmt->fetch()['count'];

// количество мастеров
$stmt = $db->query("SELECT COUNT(*) as count FROM masters WHERE is_active = TRUE");
$stats['active_masters'] = $stmt->fetch()['count'];

// выручка за сегодня
$stmt = $db->query("
    SELECT COALESCE(SUM(r.final_amount), 0) as total 
    FROM receipts r 
    JOIN appointments a ON r.appointment_id = a.id 
    WHERE a.appointment_date = CURRENT_DATE AND r.payment_status = 'paid'
");
$stats['today_revenue'] = $stmt->fetch()['total'];

// ближайшие записи
$stmt = $db->query("
    SELECT a.*, c.full_name as client_name, m.full_name as master_name, s.name as service_name
    FROM appointments a
    JOIN clients c ON a.client_id = c.id
    JOIN masters m ON a.master_id = m.id
    JOIN services s ON a.service_id = s.id
    WHERE a.appointment_date >= CURRENT_DATE
    ORDER BY a.appointment_date, a.start_time
    LIMIT 10
");
$upcoming_appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Панель управления</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* стили остаются без изменений */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
        }
        .admin-content {
            flex: 1;
            padding: 20px;
            background: #f5f5f5;
        }
        .admin-logo {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #34495e;
        }
        .admin-menu {
            list-style: none;
            padding: 0;
        }
        .admin-menu li {
            border-bottom: 1px solid #34495e;
        }
        .admin-menu a {
            display: block;
            padding: 15px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.3s;
        }
        .admin-menu a:hover,
        .admin-menu a.active {
            background: #34495e;
        }
        .admin-menu i {
            width: 20px;
            margin-right: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin-top: 0;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-scheduled { background: #3498db; color: white; }
        .status-completed { background: #2ecc71; color: white; }
        .status-cancelled { background: #e74c3c; color: white; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- сайдбар -->
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2><i class="fas fa-cut"></i> Админ-панель</h2>
                <small>Парикмахерская "Lumiere"</small>
            </div>
            <ul class="admin-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Дашборд</a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> Записи</a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i> Клиенты</a></li>
                <li><a href="masters.php"><i class="fas fa-user-tie"></i> Мастера</a></li>
                <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Услуги</a></li>
                <li><a href="schedule.php"><i class="fas fa-clock"></i> Расписание</a></li>
                <li><a href="receipts.php"><i class="fas fa-receipt"></i> Чеки</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выход</a></li>
            </ul>
        </div>
        
        <!-- основной контент -->
        <div class="admin-content">
            <h1>Панель управления</h1>
            <p>Добро пожаловать в админ-панель парикмахерской "Lumiere"</p>
            
            <!-- статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Записи сегодня</h3>
                    <div class="stat-number"><?php echo $stats['today_appointments']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Активные клиенты</h3>
                    <div class="stat-number"><?php echo $stats['total_clients']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Мастера</h3>
                    <div class="stat-number"><?php echo $stats['active_masters']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Выручка сегодня</h3>
                    <div class="stat-number"><?php echo number_format($stats['today_revenue'], 0, ',', ' '); ?> ₽</div>
                </div>
            </div>
            
            <!-- ближайшие записи -->
            <div class="section">
                <h2>Ближайшие записи</h2>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_appointments as $appointment): ?>
                            <tr>
                                <td>#<?php echo $appointment['id']; ?></td>
                                <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['master_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                <td>
                                    <?php 
                                    echo date('d.m.Y', strtotime($appointment['appointment_date'])) . ' ' .
                                         date('H:i', strtotime($appointment['start_time']));
                                    ?>
                                </td>
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
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>