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
    } elseif ($_GET['action'] === 'cancel' && $id) {
        $stmt = $db->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$id]);
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
    <title>Управление записями</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Добавьте стили из dashboard.php */
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: #2c3e50; color: white; padding: 20px 0; }
        .admin-content { flex: 1; padding: 20px; background: #f5f5f5; }
        /* ... остальные стили ... */
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <div class="admin-content">
            <h1>Управление записями</h1>
            
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
                                <?php if ($appointment['status'] === 'scheduled'): ?>
                                <a href="?action=complete&id=<?php echo $appointment['id']; ?>" 
                                   class="btn btn-sm btn-success">Выполнено</a>
                                <a href="?action=cancel&id=<?php echo $appointment['id']; ?>" 
                                   class="btn btn-sm btn-danger">Отменить</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>