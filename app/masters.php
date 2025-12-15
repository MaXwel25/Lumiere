<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Устанавливаем заголовок страницы
$page_title = "Наши мастера";
$page_subtitle = "Профессиональная команда стилистов с многолетним опытом";

// Получаем всех активных мастеров
$db = getDBConnection();
$stmt = $db->query("SELECT * FROM masters WHERE is_active = TRUE ORDER BY full_name");
$masters = $stmt->fetchAll();

// Для каждого мастера получаем статистику
foreach ($masters as &$master) {
    // Количество записей
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointments WHERE master_id = ? AND status = 'completed'");
    $stmt->execute([$master['id']]);
    $master['completed_appointments'] = $stmt->fetch()['count'];
    
    // Рейтинг (в реальном проекте считался бы из отзывов)
    $master['rating'] = 4.5 + (rand(0, 10) / 10);
    $master['reviews'] = rand(10, 100);
    
    // Специализации (преобразуем строку в массив)
    $master['specializations'] = $master['specialization'] ? 
        explode(',', $master['specialization']) : 
        ['Парикмахерские услуги'];
    
    // График работы
    $stmt = $db->prepare("
        SELECT GROUP_CONCAT(day_of_week ORDER BY day_of_week) as working_days 
        FROM work_schedule 
        WHERE master_id = ? AND is_working_day = 1
    ");
    $stmt->execute([$master['id']]);
    $schedule = $stmt->fetch();
    $master['working_days'] = $schedule['working_days'] ?? '';
}

require_once 'includes/header.php';
?>

<!-- Хлебные крошки -->
<section class="breadcrumbs">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/index.php"><i class="fas fa-home"></i> Главная</a></li>
                <li class="breadcrumb-item active" aria-current="page">Мастера</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Основной контент -->
<section class="masters-page">
    <div class="container">
        <!-- Заголовок секции -->
        <div class="section-header">
            <h1><?php echo $page_title; ?></h1>
            <p class="section-subtitle"><?php echo $page_subtitle; ?></p>
        </div>

        <!-- Фильтр мастеров -->
        <div class="masters-filter">
            <div class="filter-options">
                <div class="filter-group">
                    <label for="specializationFilter">Специализация:</label>
                    <select id="specializationFilter" class="filter-select">
                        <option value="all">Все специализации</option>
                        <option value="Мужские стрижки">Мужские стрижки</option>
                        <option value="Женские стрижки">Женские стрижки</option>
                        <option value="Окрашивание">Окрашивание</option>
                        <option value="Барберинг">Барберинг</option>
                        <option value="Укладка">Укладка</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="experienceFilter">Опыт работы:</label>
                    <select id="experienceFilter" class="filter-select">
                        <option value="all">Любой опыт</option>
                        <option value="1-3">1-3 года</option>
                        <option value="3-5">3-5 лет</option>
                        <option value="5+">Более 5 лет</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="ratingFilter">Рейтинг:</label>
                    <select id="ratingFilter" class="filter-select">
                        <option value="all">Любой рейтинг</option>
                        <option value="4.5">4.5+ звезд</option>
                        <option value="4.0">4.0+ звезд</option>
                        <option value="3.5">3.5+ звезд</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-actions">
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Применить фильтры
                </button>
                <button class="btn btn-secondary" onclick="resetFilters()">
                    <i class="fas fa-redo"></i> Сбросить
                </button>
            </div>
        </div>

        <!-- Сетка мастеров -->
        <div class="masters-grid" id="mastersContainer">
            <?php foreach ($masters as $master): 
                // Генерируем случайный опыт работы для демонстрации
                $experience_years = rand(2, 15);
                $is_available = rand(0, 1); // Для демонстрации доступности
            ?>
            <div class="master-item" 
                 data-specialization="<?php echo htmlspecialchars($master['specialization'] ?? ''); ?>"
                 data-experience="<?php echo $experience_years; ?>"
                 data-rating="<?php echo $master['rating']; ?>">
                <div class="master-card">
                    <!-- Фото мастера -->
                    <div class="master-photo">
                        <img src="https://via.placeholder.com/400x400?text=<?php echo urlencode(substr($master['full_name'], 0, 1)); ?>" 
                             alt="<?php echo htmlspecialchars($master['full_name']); ?>"
                             onerror="this.src='https://via.placeholder.com/400x400?text=MASTER'">
                        <?php if ($is_available): ?>
                            <div class="availability-badge available">
                                <i class="fas fa-check-circle"></i> Свободен
                            </div>
                        <?php else: ?>
                            <div class="availability-badge busy">
                                <i class="fas fa-clock"></i> Занят
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Информация о мастере -->
                    <div class="master-info">
                        <h3 class="master-name"><?php echo htmlspecialchars($master['full_name']); ?></h3>
                        
                        <!-- Рейтинг -->
                        <div class="master-rating">
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= floor($master['rating'])): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($i - 0.5 <= $master['rating']): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="rating-value"><?php echo number_format($master['rating'], 1); ?></span>
                                <span class="reviews-count">(<?php echo $master['reviews']; ?> отзывов)</span>
                            </div>
                        </div>
                        
                        <!-- Специализации -->
                        <div class="master-specializations">
                            <?php foreach ($master['specializations'] as $spec): ?>
                                <span class="specialization-tag"><?php echo htmlspecialchars(trim($spec)); ?></span>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Детали -->
                        <div class="master-details">
                            <div class="detail-item">
                                <i class="fas fa-briefcase"></i>
                                <span>Опыт: <?php echo $experience_years; ?> лет</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar-check"></i>
                                <span>Выполнено: <?php echo $master['completed_appointments']; ?> записей</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($master['phone']); ?></span>
                            </div>
                        </div>
                        
                        <!-- Описание -->
                        <div class="master-description">
                            <p>Профессиональный стилист с <?php echo $experience_years; ?>-летним опытом работы. 
                            Специализируется на <?php echo htmlspecialchars($master['specialization'] ?? 'парикмахерских услугах'); ?>.</p>
                        </div>
                    </div>
                    
                    <!-- Действия -->
                    <div class="master-actions">
                        <a href="booking.php?master_id=<?php echo $master['id']; ?>" class="btn btn-book">
                            <i class="fas fa-calendar-alt"></i> Записаться
                        </a>
                        <button class="btn btn-info master-schedule-btn" data-master-id="<?php echo $master['id']; ?>">
                            <i class="fas fa-clock"></i> Расписание
                        </button>
                        <button class="btn btn-secondary master-reviews-btn" data-master-id="<?php echo $master['id']; ?>">
                            <i class="fas fa-comments"></i> Отзывы
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Статистика -->
        <div class="masters-stats">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-content">
                    <h4><?php echo count($masters); ?> мастеров</h4>
                    <p>Профессиональная команда</p>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stats-content">
                    <h4>4.8 средний рейтинг</h4>
                    <p>По оценкам клиентов</p>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-content">
                    <h4>10+ лет опыта</h4>
                    <p>Средний стаж работы</p>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stats-content">
                    <h4>5000+ записей</h4>
                    <p>Успешно выполнено</p>
                </div>
            </div>
        </div>

        <!-- Процесс работы -->
        <div class="process-section">
            <h3><i class="fas fa-cogs"></i> Как проходит работа с мастером</h3>
            
            <div class="process-steps">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Консультация</h4>
                        <p>Обсуждение желаемого результата, рекомендации по стилю и уходу</p>
                    </div>
                </div>
                
                <div class="process-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Выполнение работы</h4>
                        <p>Профессиональное выполнение услуги с использованием качественных материалов</p>
                    </div>
                </div>
                
                <div class="process-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Финальная укладка</h4>
                        <p>Создание идеальной укладки и проверка результата</p>
                    </div>
                </div>
                
                <div class="process-step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4>Рекомендации</h4>
                        <p>Советы по домашнему уходу и следующему визиту</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Отзывы клиентов -->
        <div class="reviews-section">
            <h3><i class="fas fa-comment-dots"></i> Отзывы о наших мастерах</h3>
            
            <div class="reviews-slider">
                <div class="review-item">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <div class="reviewer-photo">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="reviewer-details">
                                <h5>Анна Иванова</h5>
                                <div class="review-rating">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <div class="review-date">15.12.2024</div>
                    </div>
                    <div class="review-content">
                        <p>"Мастер Мария - настоящий профессионал! Сделала идеальное окрашивание, 
                        точно подобрала цвет. Очень внимательная к деталям."</p>
                    </div>
                    <div class="review-master">
                        <i class="fas fa-user-tie"></i> Мастер: Мария Сергеевна
                    </div>
                </div>
                
                <div class="review-item">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <div class="reviewer-photo">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="reviewer-details">
                                <h5>Дмитрий Петров</h5>
                                <div class="review-rating">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                        </div>
                        <div class="review-date">10.12.2024</div>
                    </div>
                    <div class="review-content">
                        <p>"Алексей Владимирович - лучший барбер в городе! Стрижка бороды на высшем уровне. 
                        Рекомендую всем мужчинам!"</p>
                    </div>
                    <div class="review-master">
                        <i class="fas fa-user-tie"></i> Мастер: Алексей Владимирович
                    </div>
                </div>
                
                <div class="review-item">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <div class="reviewer-photo">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="reviewer-details">
                                <h5>Екатерина Смирнова</h5>
                                <div class="review-rating">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <div class="review-date">05.12.2024</div>
                    </div>
                    <div class="review-content">
                        <p>"Очень довольна стрижкой! Мастер Ирина учла все мои пожелания и сделала даже лучше, 
                        чем я ожидала. Обязательно вернусь!"</p>
                    </div>
                    <div class="review-master">
                        <i class="fas fa-user-tie"></i> Мастер: Ирина Николаевна
                    </div>
                </div>
            </div>
        </div>

        <!-- Статьи/Советы от мастеров -->
        <div class="tips-section">
            <h3><i class="fas fa-lightbulb"></i> Советы от наших мастеров</h3>
            
            <div class="tips-grid">
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-shower"></i>
                    </div>
                    <div class="tip-content">
                        <h4>Как часто мыть голову?</h4>
                        <p>Оптимально мыть голову 2-3 раза в неделю. Частое мытье может пересушить кожу головы.</p>
                    </div>
                </div>
                
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-wind"></i>
                    </div>
                    <div class="tip-content">
                        <h4>Уход за укладкой</h4>
                        <p>Для сохранения укладки используйте термозащитные спреи и не расчесывайте волосы сразу после сушки.</p>
                    </div>
                </div>
                
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-cut"></i>
                    </div>
                    <div class="tip-content">
                        <h4>Когда подстригать кончики?</h4>
                        <p>Подстригайте кончики каждые 2-3 месяца для поддержания здорового вида волос.</p>
                    </div>
                </div>
                
                <div class="tip-card">
                    <div class="tip-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div class="tip-content">
                        <h4>Выбор цвета волос</h4>
                        <p>При выборе цвета учитывайте свой цветотип и натуральный цвет волос для естественного результата.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Модальное окно с расписанием -->
<div class="modal" id="scheduleModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Расписание мастера</h3>
            <button class="modal-close" onclick="closeScheduleModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="scheduleContent"></div>
        </div>
    </div>
</div>

<!-- Модальное окно с отзывами -->
<div class="modal" id="reviewsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Отзывы о мастере</h3>
            <button class="modal-close" onclick="closeReviewsModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="reviewsContent"></div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<style>
/* Стили для страницы мастеров */
.masters-page {
    padding: 60px 0;
    background: #f8f9fa;
}

.section-header {
    text-align: center;
    margin-bottom: 40px;
}

.section-header h1 {
    font-size: 36px;
    color: #2c3e50;
    margin-bottom: 10px;
}

.section-subtitle {
    color: #666;
    font-size: 18px;
    max-width: 600px;
    margin: 0 auto;
}

/* Фильтр мастеров */
.masters-filter {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

.filter-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.filter-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}

.filter-select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.filter-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

/* Карточки мастеров */
.masters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

.master-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.master-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.master-photo {
    position: relative;
    height: 250px;
    overflow: hidden;
}

.master-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.availability-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

.availability-badge.available {
    background: #2ecc71;
    color: white;
}

.availability-badge.busy {
    background: #e74c3c;
    color: white;
}

.master-info {
    padding: 20px;
}

.master-name {
    font-size: 24px;
    color: #2c3e50;
    margin-bottom: 10px;
    font-weight: 700;
}

.master-rating {
    margin-bottom: 15px;
}

.stars {
    display: flex;
    align-items: center;
    gap: 5px;
}

.stars i {
    color: #f39c12;
}

.rating-value {
    margin-left: 10px;
    font-weight: 600;
    color: #2c3e50;
}

.reviews-count {
    color: #95a5a6;
    font-size: 14px;
}

.master-specializations {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
}

.specialization-tag {
    padding: 5px 10px;
    background: #e8f4fc;
    color: #3498db;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.master-details {
    margin-bottom: 15px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    color: #666;
    font-size: 14px;
}

.detail-item i {
    color: #3498db;
    width: 16px;
}

.master-description {
    color: #7f8c8d;
    font-size: 14px;
    line-height: 1.6;
    border-top: 1px solid #eee;
    padding-top: 15px;
    margin-top: 15px;
}

.master-actions {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-book {
    background: #2ecc71;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: background 0.3s;
    flex: 1;
    text-align: center;
}

.btn-book:hover {
    background: #27ae60;
}

.master-schedule-btn,
.master-reviews-btn {
    flex: 1;
    padding: 10px 20px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s;
}

.master-schedule-btn {
    background: #3498db;
    color: white;
}

.master-schedule-btn:hover {
    background: #2980b9;
}

.master-reviews-btn {
    background: #f8f9fa;
    color: #2c3e50;
    border: 1px solid #ddd;
}

.master-reviews-btn:hover {
    background: #e9ecef;
}

/* Статистика */
.masters-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 60px 0;
}

.stats-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.stats-icon {
    font-size: 40px;
    color: #3498db;
    margin-bottom: 15px;
}

.stats-content h4 {
    color: #2c3e50;
    margin-bottom: 5px;
    font-size: 24px;
}

.stats-content p {
    color: #7f8c8d;
    font-size: 14px;
}

/* Процесс работы */
.process-section {
    margin: 60px 0;
}

.process-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.process-step {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    position: relative;
}

.step-number {
    position: absolute;
    top: -15px;
    left: -15px;
    width: 40px;
    height: 40px;
    background: #3498db;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 20px;
}

.step-content h4 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.step-content p {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
}

/* Отзывы */
.reviews-section {
    margin: 60px 0;
}

.reviews-slider {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.review-item {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.reviewer-photo {
    width: 50px;
    height: 50px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #3498db;
}

.reviewer-details h5 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.review-rating i {
    color: #f39c12;
    font-size: 14px;
}

.review-date {
    color: #95a5a6;
    font-size: 14px;
}

.review-content {
    color: #666;
    line-height: 1.6;
    margin-bottom: 15px;
    font-style: italic;
}

.review-master {
    padding-top: 15px;
    border-top: 1px solid #eee;
    color: #3498db;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Советы */
.tips-section {
    margin: 60px 0;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.tip-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: flex-start;
    gap: 20px;
}

.tip-icon {
    font-size: 30px;
    color: #3498db;
    min-width: 40px;
}

.tip-content h4 {
    color: #2c3e50;
    margin-bottom: 10px;
    font-size: 18px;
}

.tip-content p {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
}

/* Адаптивность */
@media (max-width: 768px) {
    .masters-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-options {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        flex-direction: column;
    }
    
    .master-actions {
        flex-direction: column;
    }
    
    .masters-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .process-steps {
        grid-template-columns: 1fr;
    }
    
    .reviews-slider {
        grid-template-columns: 1fr;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Фильтрация мастеров
function applyFilters() {
    const specialization = document.getElementById('specializationFilter').value;
    const experience = document.getElementById('experienceFilter').value;
    const rating = document.getElementById('ratingFilter').value;
    
    const masters = document.querySelectorAll('.master-item');
    
    masters.forEach(master => {
        const masterSpec = master.dataset.specialization;
        const masterExp = parseInt(master.dataset.experience);
        const masterRating = parseFloat(master.dataset.rating);
        
        let show = true;
        
        // Фильтр по специализации
        if (specialization !== 'all' && specialization !== '' && !masterSpec.includes(specialization)) {
            show = false;
        }
        
        // Фильтр по опыту
        if (experience !== 'all') {
            if (experience === '1-3' && (masterExp < 1 || masterExp > 3)) show = false;
            if (experience === '3-5' && (masterExp < 3 || masterExp > 5)) show = false;
            if (experience === '5+' && masterExp < 5) show = false;
        }
        
        // Фильтр по рейтингу
        if (rating !== 'all' && masterRating < parseFloat(rating)) {
            show = false;
        }
        
        // Показ/скрытие элемента
        if (show) {
            master.style.display = 'block';
            setTimeout(() => {
                master.style.opacity = '1';
                master.style.transform = 'scale(1)';
            }, 50);
        } else {
            master.style.opacity = '0';
            master.style.transform = 'scale(0.8)';
            setTimeout(() => {
                master.style.display = 'none';
            }, 300);
        }
    });
}

function resetFilters() {
    document.getElementById('specializationFilter').value = 'all';
    document.getElementById('experienceFilter').value = 'all';
    document.getElementById('ratingFilter').value = 'all';
    
    const masters = document.querySelectorAll('.master-item');
    masters.forEach(master => {
        master.style.display = 'block';
        setTimeout(() => {
            master.style.opacity = '1';
            master.style.transform = 'scale(1)';
        }, 50);
    });
}

// Модальное окно с расписанием
document.querySelectorAll('.master-schedule-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const masterId = this.dataset.masterId;
        
        // Показываем лоадер
        document.getElementById('scheduleContent').innerHTML = '<div class="loader"></div>';
        document.getElementById('scheduleModal').style.display = 'flex';
        
        try {
            const response = await fetch(`api/get_master_schedule.php?id=${masterId}`);
            const data = await response.json();
            
            if (data.success) {
                let content = '<div class="schedule-details">';
                
                if (data.schedule && data.schedule.length > 0) {
                    const days = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
                    
                    data.schedule.forEach(day => {
                        content += `
                            <div class="schedule-day ${day.is_working_day ? 'working' : 'day-off'}">
                                <div class="day-name">${days[day.day_of_week - 1]}</div>
                                <div class="day-hours">
                                    ${day.is_working_day ? 
                                        `${day.start_time} - ${day.end_time}` : 
                                        '<span class="day-off-text">Выходной</span>'}
                                </div>
                            </div>
                        `;
                    });
                } else {
                    content += '<p>Расписание не найдено</p>';
                }
                
                content += '</div>';
                document.getElementById('scheduleContent').innerHTML = content;
            } else {
                document.getElementById('scheduleContent').innerHTML = 
                    '<p class="error">Не удалось загрузить расписание</p>';
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('scheduleContent').innerHTML = 
                '<p class="error">Ошибка загрузки данных</p>';
        }
    });
});

function closeScheduleModal() {
    document.getElementById('scheduleModal').style.display = 'none';
}

// Модальное окно с отзывами
document.querySelectorAll('.master-reviews-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const masterId = this.dataset.masterId;
        
        // В реальном проекте здесь был бы запрос к API
        // Для демонстрации показываем фиктивные отзывы
        document.getElementById('reviewsContent').innerHTML = `
            <div class="reviews-list">
                <div class="review-item">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <div class="reviewer-photo">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="reviewer-details">
                                <h5>Анна Иванова</h5>
                                <div class="review-rating">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <div class="review-date">15.12.2024</div>
                    </div>
                    <div class="review-content">
                        <p>"Отличный мастер! Очень внимательный и профессиональный."</p>
                    </div>
                </div>
                
                <div class="review-item">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <div class="reviewer-photo">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="reviewer-details">
                                <h5>Дмитрий Петров</h5>
                                <div class="review-rating">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                        </div>
                        <div class="review-date">10.12.2024</div>
                    </div>
                    <div class="review-content">
                        <p>"Все понравилось, буду рекомендовать друзьям."</p>
                    </div>
                </div>
                
                <div class="review-item">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <div class="reviewer-photo">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="reviewer-details">
                                <h5>Екатерина Смирнова</h5>
                                <div class="review-rating">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <div class="review-date">05.12.2024</div>
                    </div>
                    <div class="review-content">
                        <p>"Спасибо за качественную работу и хорошее настроение!"</p>
                    </div>
                </div>
            </div>
            
            <div class="add-review-form">
                <h4>Оставить отзыв</h4>
                <form id="reviewForm">
                    <div class="form-group">
                        <label for="reviewName">Ваше имя</label>
                        <input type="text" id="reviewName" required>
                    </div>
                    <div class="form-group">
                        <label for="reviewRating">Оценка</label>
                        <select id="reviewRating" required>
                            <option value="5">5 звезд</option>
                            <option value="4">4 звезды</option>
                            <option value="3">3 звезды</option>
                            <option value="2">2 звезды</option>
                            <option value="1">1 звезда</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reviewText">Ваш отзыв</label>
                        <textarea id="reviewText" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Отправить отзыв</button>
                </form>
            </div>
        `;
        
        document.getElementById('reviewsModal').style.display = 'flex';
        
        // Обработка формы отзыва
        document.getElementById('reviewForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Спасибо за ваш отзыв! После модерации он будет опубликован.');
            document.getElementById('reviewForm').reset();
        });
    });
});

function closeReviewsModal() {
    document.getElementById('reviewsModal').style.display = 'none';
}

// Закрытие модальных окон при клике вне их
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            if (this.id === 'scheduleModal') closeScheduleModal();
            if (this.id === 'reviewsModal') closeReviewsModal();
        }
    });
});

// Анимация появления при загрузке
document.addEventListener('DOMContentLoaded', function() {
    const masterItems = document.querySelectorAll('.master-item');
    masterItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>