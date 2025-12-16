<?php
require_once '../config/database.php';
require_once '../config/admin_config.php';
requireAdminAuth();

// обработка добавления/редактирования услуги
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? null;
    $price = $_POST['price'] ?? 0;
    $duration_min = $_POST['duration_min'] ?? 30;
    $category = $_POST['category'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id) {
        // редактирование существующей услуги
        $stmt = $db->prepare("
            UPDATE services 
            SET name = ?, description = ?, price = ?, duration_min = ?, 
                category = ?, is_active = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$name, $description, $price, $duration_min, $category, $is_active, $id]);
        $message = "Услуга успешно обновлена";
    } else {
        // добавление новой услуги
        $stmt = $db->prepare("
            INSERT INTO services (name, description, price, duration_min, category, is_active) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $price, $duration_min, $category, $is_active]);
        $message = "Услуга успешно добавлена";
    }
}

// обработка удаления услуги
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // проверяем, есть ли активные записи на эту услугу
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointments WHERE service_id = ? AND status = 'scheduled'");
    $stmt->execute([$id]);
    $hasAppointments = $stmt->fetch()['count'] > 0;
    
    if ($hasAppointments) {
        $error = "Нельзя удалить услугу с активными записями";
    } else {
        $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Услуга успешно удалена";
    }
}

// получение всех услуг
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// формируем запрос с фильтрами
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (name LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_fill(0, 2, $searchTerm);
}

if ($category) {
    $where .= " AND category = ?";
    $params[] = $category;
}

// общее количество услуг
$stmt = $db->prepare("SELECT COUNT(*) as total FROM services $where");
$stmt->execute($params);
$totalServices = $stmt->fetch()['total'];
$totalPages = ceil($totalServices / $limit);

// получаем услуги для текущей страницы
$stmt = $db->prepare("
    SELECT 
        s.*,
        COUNT(a.id) as total_appointments,
        COUNT(DISTINCT m.id) as masters_count
    FROM services s
    LEFT JOIN appointments a ON s.id = a.service_id
    LEFT JOIN masters m ON EXISTS (
        SELECT 1 FROM appointments a2 
        WHERE a2.service_id = s.id AND a2.master_id = m.id
    )
    $where
    GROUP BY s.id
    ORDER BY s.is_active DESC, s.category, s.price
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$services = $stmt->fetchAll();

// получаем уникальные категории для фильтра
$categories = $db->query("SELECT DISTINCT category FROM services WHERE category IS NOT NULL ORDER BY category")->fetchAll();

// получаем услугу для редактирования
$editService = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editService = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление услугами - Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* стили такие же как в clients.php */
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
        .search-form input, .search-form select { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #2ecc71; color: white; }
        .badge-danger { background: #e74c3c; color: white; }
        .badge-info { background: #3498db; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .action-buttons { display: flex; gap: 5px; }
        .status-active { color: #27ae60; }
        .status-inactive { color: #e74c3c; }
        .category-badge { display: inline-block; padding: 3px 8px; background: #e8f4fc; color: #3498db; border-radius: 3px; font-size: 12px; }
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
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Дашборд</a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> Записи</a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i> Клиенты</a></li>
                <li><a href="masters.php"><i class="fas fa-user-tie"></i> Мастера</a></li>
                <li><a href="services.php" class="active"><i class="fas fa-concierge-bell"></i> Услуги</a></li>
                <li><a href="schedule.php"><i class="fas fa-clock"></i> Расписание</a></li>
                <li><a href="receipts.php"><i class="fas fa-receipt"></i> Чеки</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выход</a></li>
            </ul>
        </div>
        
        <!-- основной контент -->
        <div class="admin-content">
            <h1><i class="fas fa-concierge-bell"></i> Управление услугами</h1>
            
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- фильтры -->
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Поиск по названию или описанию..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select name="category">
                    <option value="">Все категории</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                <?php echo ($category == $cat['category']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Найти</button>
                <a href="services.php" class="btn btn-info">Сбросить</a>
            </form>
            
            <!-- форма добавления/редактирования -->
            <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <h2><?php echo $editService ? 'Редактирование услуги' : 'Добавление новой услуги'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $editService ? $editService['id'] : ''; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Название услуги *</label>
                            <input type="text" id="name" name="name" required
                                   value="<?php echo $editService ? htmlspecialchars($editService['name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="category">Категория</label>
                            <input type="text" id="category" name="category" list="categories"
                                   value="<?php echo $editService ? htmlspecialchars($editService['category']) : ''; ?>">
                            <datalist id="categories">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Цена (₽) *</label>
                            <input type="number" id="price" name="price" min="0" step="0.01" required
                                   value="<?php echo $editService ? $editService['price'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="duration_min">Длительность (минуты) *</label>
                            <input type="number" id="duration_min" name="duration_min" min="5" max="300" step="5" required
                                   value="<?php echo $editService ? $editService['duration_min'] : '30'; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Описание услуги</label>
                        <textarea id="description" name="description" rows="3"><?php 
                            echo $editService ? htmlspecialchars($editService['description']) : ''; 
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1" 
                                   <?php echo (!$editService || $editService['is_active']) ? 'checked' : ''; ?>>
                            Активная услуга (доступна для записи)
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <?php echo $editService ? 'Сохранить изменения' : 'Добавить услугу'; ?>
                        </button>
                        <?php if ($editService): ?>
                            <a href="services.php" class="btn btn-info">Отмена</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- таблица услуг -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Категория</th>
                            <th>Цена</th>
                            <th>Длительность</th>
                            <th>Статус</th>
                            <th>Записей</th>
                            <th>Мастеров</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($services)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center;">Услуги не найдены</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($services as $service): ?>
                            <tr>
                                <td>#<?php echo $service['id']; ?></td>
                                <td>
                                    <div><strong><?php echo htmlspecialchars($service['name']); ?></strong></div>
                                    <?php if ($service['description']): ?>
                                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                            <?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>
                                            <?php if (strlen($service['description']) > 100): ?>...<?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($service['category']): ?>
                                        <span class="category-badge"><?php echo htmlspecialchars($service['category']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo number_format($service['price'], 0, ',', ' '); ?> ₽</strong>
                                </td>
                                <td><?php echo $service['duration_min']; ?> мин</td>
                                <td>
                                    <?php if ($service['is_active']): ?>
                                        <span class="status-active"><i class="fas fa-check-circle"></i> Активна</span>
                                    <?php else: ?>
                                        <span class="status-inactive"><i class="fas fa-times-circle"></i> Неактивна</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $service['total_appointments'] > 0 ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $service['total_appointments']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo $service['masters_count']; ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?php echo $service['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="appointments.php?service_id=<?php echo $service['id']; ?>" 
                                           class="btn btn-sm btn-success" title="Записи">
                                            <i class="fas fa-calendar-alt"></i>
                                        </a>
                                        <a href="?delete=<?php echo $service['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Вы уверены, что хотите удалить услугу?')"
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
            
            <!-- пагинация -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>">&laquo; Назад</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>">Вперед &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- статистика -->
            <div style="margin-top: 30px; background: white; padding: 20px; border-radius: 10px;">
                <h3>Статистика услуг</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div>
                        <h4>Всего услуг: <?php echo $totalServices; ?></h4>
                    </div>
                    <div>
                        <h4>Активных: 
                            <?php 
                            $stmt = $db->query("SELECT COUNT(*) as count FROM services WHERE is_active = 1");
                            echo $stmt->fetch()['count'];
                            ?>
                        </h4>
                    </div>
                    <div>
                        <h4>Категорий: 
                            <?php 
                            $stmt = $db->query("SELECT COUNT(DISTINCT category) as count FROM services WHERE category IS NOT NULL");
                            echo $stmt->fetch()['count'];
                            ?>
                        </h4>
                    </div>
                    <div>
                        <h4>Средняя цена: 
                            <?php 
                            $stmt = $db->query("SELECT AVG(price) as avg_price FROM services WHERE is_active = 1");
                            $avg = $stmt->fetch()['avg_price'];
                            echo number_format($avg, 0, ',', ' ') . ' ₽';
                            ?>
                        </h4>
                    </div>
                </div>
                
                <!-- топ услуг по популярности -->
                <div style="margin-top: 20px;">
                    <h4>Самые популярные услуги</h4>
                    <?php
                    $stmt = $db->query("
                        SELECT s.name, COUNT(a.id) as appointment_count
                        FROM services s
                        LEFT JOIN appointments a ON s.id = a.service_id
                        WHERE s.is_active = 1
                        GROUP BY s.id
                        ORDER BY appointment_count DESC
                        LIMIT 5
                    ");
                    $popularServices = $stmt->fetchAll();
                    ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($popularServices as $popular): ?>
                        <li style="padding: 5px 0; border-bottom: 1px solid #eee;">
                            <?php echo htmlspecialchars($popular['name']); ?>
                            <span class="badge badge-success" style="float: right;">
                                <?php echo $popular['appointment_count']; ?> записей
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>