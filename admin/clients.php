<?php
require_once '../config/database.php';
require_once '../config/admin_config.php';
requireAdminAuth();

// Обработка добавления/редактирования клиента
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? null;
    
    if ($id) {
        // Редактирование существующего клиента
        $stmt = $db->prepare("
            UPDATE clients 
            SET full_name = ?, phone = ?, email = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$full_name, $phone, $email, $id]);
        $message = "Клиент успешно обновлен";
    } else {
        // Добавление нового клиента
        $stmt = $db->prepare("
            INSERT INTO clients (full_name, phone, email) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$full_name, $phone, $email]);
        $message = "Клиент успешно добавлен";
    }
}

// Обработка удаления клиента
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Проверяем, есть ли у клиента активные записи
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointments WHERE client_id = ? AND status = 'scheduled'");
    $stmt->execute([$id]);
    $hasAppointments = $stmt->fetch()['count'] > 0;
    
    if ($hasAppointments) {
        $error = "Нельзя удалить клиента с активными записями";
    } else {
        $stmt = $db->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Клиент успешно удален";
    }
}

// Получение всех клиентов
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Формируем запрос с поиском
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (full_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_fill(0, 3, $searchTerm);
}

// Общее количество клиентов
$stmt = $db->prepare("SELECT COUNT(*) as total FROM clients $where");
$stmt->execute($params);
$totalClients = $stmt->fetch()['total'];
$totalPages = ceil($totalClients / $limit);

// Получаем клиентов для текущей страницы
$stmt = $db->prepare("
    SELECT 
        c.*,
        COUNT(a.id) as total_appointments,
        MAX(a.appointment_date) as last_visit
    FROM clients c
    LEFT JOIN appointments a ON c.id = a.client_id
    $where
    GROUP BY c.id
    ORDER BY c.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$clients = $stmt->fetchAll();

// Получаем клиента для редактирования
$editClient = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editClient = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление клиентами - Админ-панель</title>
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
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-actions { display: flex; gap: 10px; margin-top: 20px; }
        .pagination { display: flex; justify-content: center; margin-top: 20px; gap: 5px; }
        .pagination a, .pagination span { display: inline-block; padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: 5px; text-decoration: none; color: #333; }
        .pagination a:hover { background: #3498db; color: white; }
        .pagination .current { background: #3498db; color: white; border-color: #3498db; }
        .search-form { margin-bottom: 20px; display: flex; gap: 10px; }
        .search-form input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #2ecc71; color: white; }
        .badge-info { background: #3498db; color: white; }
        .badge-warning { background: #f39c12; color: white; }
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
                <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> Записи</a></li>
                <li><a href="clients.php" class="active"><i class="fas fa-users"></i> Клиенты</a></li>
                <li><a href="masters.php"><i class="fas fa-user-tie"></i> Мастера</a></li>
                <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Услуги</a></li>
                <li><a href="schedule.php"><i class="fas fa-clock"></i> Расписание</a></li>
                <li><a href="receipts.php"><i class="fas fa-receipt"></i> Чеки</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выход</a></li>
            </ul>
        </div>
        
        <!-- Основной контент -->
        <div class="admin-content">
            <h1><i class="fas fa-users"></i> Управление клиентами</h1>
            
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Поиск -->
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Поиск по имени, телефону или email..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Найти</button>
                <a href="clients.php" class="btn btn-info">Сбросить</a>
            </form>
            
            <!-- Форма добавления/редактирования -->
            <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <h2><?php echo $editClient ? 'Редактирование клиента' : 'Добавление нового клиента'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $editClient ? $editClient['id'] : ''; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">ФИО *</label>
                            <input type="text" id="full_name" name="full_name" required
                                   value="<?php echo $editClient ? htmlspecialchars($editClient['full_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Телефон *</label>
                            <input type="tel" id="phone" name="phone" required
                                   value="<?php echo $editClient ? htmlspecialchars($editClient['phone']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email"
                                   value="<?php echo $editClient ? htmlspecialchars($editClient['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <?php echo $editClient ? 'Сохранить изменения' : 'Добавить клиента'; ?>
                        </button>
                        <?php if ($editClient): ?>
                            <a href="clients.php" class="btn btn-info">Отмена</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Таблица клиентов -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ФИО</th>
                            <th>Телефон</th>
                            <th>Email</th>
                            <th>Записей</th>
                            <th>Последний визит</th>
                            <th>Дата регистрации</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">Клиенты не найдены</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>#<?php echo $client['id']; ?></td>
                                <td><?php echo htmlspecialchars($client['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($client['phone']); ?></td>
                                <td><?php echo $client['email'] ? htmlspecialchars($client['email']) : '—'; ?></td>
                                <td>
                                    <?php if ($client['total_appointments'] > 0): ?>
                                        <span class="badge badge-success"><?php echo $client['total_appointments']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $client['last_visit'] ? date('d.m.Y', strtotime($client['last_visit'])) : '—'; ?>
                                </td>
                                <td><?php echo date('d.m.Y', strtotime($client['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?php echo $client['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="appointments.php?client_id=<?php echo $client['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Записи клиента">
                                            <i class="fas fa-calendar-alt"></i>
                                        </a>
                                        <a href="?delete=<?php echo $client['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Вы уверены, что хотите удалить клиента?')"
                                           title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Пагинация -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">&laquo; Назад</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Вперед &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Статистика -->
            <div style="margin-top: 30px; background: white; padding: 20px; border-radius: 10px;">
                <h3>Статистика клиентов</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div>
                        <h4>Всего клиентов: <?php echo $totalClients; ?></h4>
                    </div>
                    <div>
                        <h4>Новых сегодня: 
                            <?php 
                            $stmt = $db->query("SELECT COUNT(*) as count FROM clients WHERE DATE(created_at) = CURDATE()");
                            echo $stmt->fetch()['count'];
                            ?>
                        </h4>
                    </div>
                    <div>
                        <h4>С email: 
                            <?php 
                            $stmt = $db->query("SELECT COUNT(*) as count FROM clients WHERE email IS NOT NULL");
                            echo $stmt->fetch()['count'];
                            ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Автоматическое форматирование телефона
    document.getElementById('phone')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 0) {
            value = '+7' + value.substring(1, Math.min(value.length, 11));
        }
        e.target.value = value;
    });
    </script>
</body>
</html>