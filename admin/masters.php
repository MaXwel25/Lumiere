<?php
require_once '../config/database.php';
require_once '../config/admin_config.php';
requireAdminAuth();

// Обработка добавления/редактирования мастера
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $specialization = $_POST['specialization'] ?? null;
    $hourly_rate = $_POST['hourly_rate'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id) {
        // Редактирование существующего мастера
        $stmt = $db->prepare("
            UPDATE masters 
            SET full_name = ?, phone = ?, specialization = ?, hourly_rate = ?, is_active = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$full_name, $phone, $specialization, $hourly_rate, $is_active, $id]);
        $message = "Мастер успешно обновлен";
    } else {
        // Добавление нового мастера
        $stmt = $db->prepare("
            INSERT INTO masters (full_name, phone, specialization, hourly_rate, is_active) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$full_name, $phone, $specialization, $hourly_rate, $is_active]);
        $message = "Мастер успешно добавлен";
    }
}

// Обработка удаления мастера
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Проверяем, есть ли у мастера активные записи
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointments WHERE master_id = ? AND status = 'scheduled'");
    $stmt->execute([$id]);
    $hasAppointments = $stmt->fetch()['count'] > 0;
    
    if ($hasAppointments) {
        $error = "Нельзя удалить мастера с активными записями";
    } else {
        $stmt = $db->prepare("DELETE FROM masters WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Мастер успешно удален";
    }
}

// Получение всех мастеров
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Формируем запрос с поиском
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (full_name LIKE ? OR phone LIKE ? OR specialization LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_fill(0, 3, $searchTerm);
}

// Общее количество мастеров
$stmt = $db->prepare("SELECT COUNT(*) as total FROM masters $where");
$stmt->execute($params);
$totalMasters = $stmt->fetch()['total'];
$totalPages = ceil($totalMasters / $limit);

// Получаем мастеров для текущей страницы
$stmt = $db->prepare("
    SELECT 
        m.*,
        COUNT(DISTINCT a.id) as total_appointments,
        COUNT(DISTINCT ws.id) as working_days,
        AVG(r.final_amount) as avg_receipt
    FROM masters m
    LEFT JOIN appointments a ON m.id = a.master_id
    LEFT JOIN work_schedule ws ON m.id = ws.master_id AND ws.is_working_day = 1
    LEFT JOIN receipts r ON a.id = r.appointment_id AND r.payment_status = 'paid'
    $where
    GROUP BY m.id
    ORDER BY m.is_active DESC, m.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$masters = $stmt->fetchAll();

// Получаем мастера для редактирования
$editMaster = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM masters WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editMaster = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление мастерами - Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Стили такие же как в clients.php */
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
        .badge-danger { background: #e74c3c; color: white; }
        .badge-info { background: #3498db; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .action-buttons { display: flex; gap: 5px; }
        .status-active { color: #27ae60; }
        .status-inactive { color: #e74c3c; }
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
                <li><a href="masters.php" class="active"><i class="fas fa-user-tie"></i> Мастера</a></li>
                <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Услуги</a></li>
                <li><a href="schedule.php"><i class="fas fa-clock"></i> Расписание</a></li>
                <li><a href="receipts.php"><i class="fas fa-receipt"></i> Чеки</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выход</a></li>
            </ul>
        </div>
        
        <!-- Основной контент -->
        <div class="admin-content">
            <h1><i class="fas fa-user-tie"></i> Управление мастерами</h1>
            
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Поиск -->
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Поиск по имени, телефону или специализации..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Найти</button>
                <a href="masters.php" class="btn btn-info">Сбросить</a>
            </form>
            
            <!-- Форма добавления/редактирования -->
            <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <h2><?php echo $editMaster ? 'Редактирование мастера' : 'Добавление нового мастера'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $editMaster ? $editMaster['id'] : ''; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">ФИО *</label>
                            <input type="text" id="full_name" name="full_name" required
                                   value="<?php echo $editMaster ? htmlspecialchars($editMaster['full_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Телефон *</label>
                            <input type="tel" id="phone" name="phone" required
                                   value="<?php echo $editMaster ? htmlspecialchars($editMaster['phone']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="specialization">Специализация</label>
                            <input type="text" id="specialization" name="specialization"
                                   value="<?php echo $editMaster ? htmlspecialchars($editMaster['specialization']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="hourly_rate">Ставка в час (₽)</label>
                            <input type="number" id="hourly_rate" name="hourly_rate" min="0" step="0.01"
                                   value="<?php echo $editMaster ? $editMaster['hourly_rate'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1" 
                                   <?php echo (!$editMaster || $editMaster['is_active']) ? 'checked' : ''; ?>>
                            Активный мастер (принимает записи)
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <?php echo $editMaster ? 'Сохранить изменения' : 'Добавить мастера'; ?>
                        </button>
                        <?php if ($editMaster): ?>
                            <a href="masters.php" class="btn btn-info">Отмена</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Таблица мастеров -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ФИО</th>
                            <th>Телефон</th>
                            <th>Специализация</th>
                            <th>Ставка/час</th>
                            <th>Статус</th>
                            <th>Записей</th>
                            <th>Рабочих дней</th>
                            <th>Средний чек</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($masters)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center;">Мастера не найдены</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($masters as $master): ?>
                            <tr>
                                <td>#<?php echo $master['id']; ?></td>
                                <td><?php echo htmlspecialchars($master['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($master['phone']); ?></td>
                                <td><?php echo $master['specialization'] ? htmlspecialchars($master['specialization']) : '—'; ?></td>
                                <td><?php echo $master['hourly_rate'] ? number_format($master['hourly_rate'], 0, ',', ' ') . ' ₽' : '—'; ?></td>
                                <td>
                                    <?php if ($master['is_active']): ?>
                                        <span class="status-active"><i class="fas fa-check-circle"></i> Активен</span>
                                    <?php else: ?>
                                        <span class="status-inactive"><i class="fas fa-times-circle"></i> Неактивен</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $master['total_appointments'] > 0 ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $master['total_appointments']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo $master['working_days']; ?></span>
                                </td>
                                <td>
                                    <?php if ($master['avg_receipt']): ?>
                                        <span class="badge badge-success">
                                            <?php echo number_format($master['avg_receipt'], 0, ',', ' '); ?> ₽
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?php echo $master['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="schedule.php?master_id=<?php echo $master['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Расписание">
                                            <i class="fas fa-clock"></i>
                                        </a>
                                        <a href="appointments.php?master_id=<?php echo $master['id']; ?>" 
                                           class="btn btn-sm btn-success" title="Записи">
                                            <i class="fas fa-calendar-alt"></i>
                                        </a>
                                        <a href="?delete=<?php echo $master['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Вы уверены, что хотите удалить мастера?')"
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
                <h3>Статистика мастеров</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div>
                        <h4>Всего мастеров: <?php echo $totalMasters; ?></h4>
                    </div>
                    <div>
                        <h4>Активных: 
                            <?php 
                            $stmt = $db->query("SELECT COUNT(*) as count FROM masters WHERE is_active = 1");
                            echo $stmt->fetch()['count'];
                            ?>
                        </h4>
                    </div>
                    <div>
                        <h4>Неактивных: 
                            <?php 
                            $stmt = $db->query("SELECT COUNT(*) as count FROM masters WHERE is_active = 0");
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