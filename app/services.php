<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Устанавливаем заголовок страницы
$page_title = "Наши услуги";
$page_subtitle = "Профессиональные парикмахерские услуги по доступным ценам";

// Получаем все активные услуги
$db = getDBConnection();
$stmt = $db->query("SELECT * FROM services WHERE is_active = TRUE ORDER BY category, price");
$services = $stmt->fetchAll();

// Получаем уникальные категории
$categories = [];
foreach ($services as $service) {
    if ($service['category']) {
        $categories[$service['category']] = $service['category'];
    }
}

require_once 'includes/header.php';
?>

<!-- Хлебные крошки -->
<section class="breadcrumbs">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/index.php"><i class="fas fa-home"></i> Главная</a></li>
                <li class="breadcrumb-item active" aria-current="page">Услуги</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Основной контент -->
<section class="services-page">
    <div class="container">
        <!-- Заголовок секции -->
        <div class="section-header">
            <h1><?php echo $page_title; ?></h1>
            <p class="section-subtitle"><?php echo $page_subtitle; ?></p>
        </div>

        <!-- Категории услуг -->
        <div class="categories-filter">
            <div class="filter-buttons">
                <button class="filter-btn active" data-category="all">Все услуги</button>
                <?php foreach ($categories as $category): ?>
                    <button class="filter-btn" data-category="<?php echo htmlspecialchars($category); ?>">
                        <?php echo htmlspecialchars($category); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Сетка услуг -->
        <div class="services-grid" id="servicesContainer">
            <?php foreach ($services as $service): ?>
            <div class="service-item" data-category="<?php echo htmlspecialchars($service['category'] ?? 'other'); ?>">
                <div class="service-card">
                    <div class="service-header">
                        <div class="service-icon">
                            <?php
                            // Иконка в зависимости от категории
                            $icons = [
                                'стрижки' => 'fas fa-cut',
                                'окрашивание' => 'fas fa-paint-brush',
                                'барберинг' => 'fas fa-user-tie',
                                'уход' => 'fas fa-spa',
                                'маникюр' => 'fas fa-hand-sparkles'
                            ];
                            $icon = $icons[strtolower($service['category'])] ?? 'fas fa-concierge-bell';
                            ?>
                            <i class="<?php echo $icon; ?>"></i>
                        </div>
                        <div class="service-category">
                            <span class="badge"><?php echo htmlspecialchars($service['category'] ?? 'Разное'); ?></span>
                        </div>
                    </div>
                    
                    <div class="service-body">
                        <h3 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                        
                        <?php if ($service['description']): ?>
                            <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="service-details">
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $service['duration_min']; ?> минут</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-user-tie"></i>
                                <span>Все мастера</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="service-footer">
                        <div class="service-price">
                            <span class="price"><?php echo number_format($service['price'], 0, ',', ' '); ?> ₽</span>
                            <span class="price-note">за процедуру</span>
                        </div>
                        <div class="service-actions">
                            <a href="booking.php?service_id=<?php echo $service['id']; ?>" class="btn btn-book">
                                <i class="fas fa-calendar-alt"></i> Записаться
                            </a>
                            <button class="btn btn-info service-info-btn" data-service-id="<?php echo $service['id']; ?>">
                                <i class="fas fa-info-circle"></i> Подробнее
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Калькулятор стоимости -->
        <div class="calculator-section">
            <div class="calculator-card">
                <h3><i class="fas fa-calculator"></i> Калькулятор стоимости</h3>
                <p>Рассчитайте примерную стоимость комплексного визита</p>
                
                <div class="calculator-form">
                    <div class="form-group">
                        <label for="calcService1">Услуга 1:</label>
                        <select id="calcService1" class="calc-service-select">
                            <option value="">Выберите услугу</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['price']; ?>" 
                                        data-duration="<?php echo $service['duration_min']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> - <?php echo number_format($service['price'], 0, ',', ' '); ?> ₽
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="calcService2">Услуга 2:</label>
                        <select id="calcService2" class="calc-service-select">
                            <option value="">Дополнительная услуга</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['price']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> - <?php echo number_format($service['price'], 0, ',', ' '); ?> ₽
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="calcDiscount">Скидка:</label>
                        <select id="calcDiscount">
                            <option value="0">Без скидки</option>
                            <option value="10">Студентам 10%</option>
                            <option value="15">Пенсионерам 15%</option>
                            <option value="20">Постоянным клиентам 20%</option>
                        </select>
                    </div>
                    
                    <div class="calculator-result">
                        <h4>Итого:</h4>
                        <div class="result-details">
                            <div class="result-row">
                                <span>Стоимость услуг:</span>
                                <span id="totalPrice">0 ₽</span>
                            </div>
                            <div class="result-row">
                                <span>Скидка:</span>
                                <span id="totalDiscount">0 ₽</span>
                            </div>
                            <div class="result-row total">
                                <span>К оплате:</span>
                                <span id="finalPrice">0 ₽</span>
                            </div>
                            <div class="result-row">
                                <span>Примерное время:</span>
                                <span id="totalTime">0 мин</span>
                            </div>
                        </div>
                    </div>
                    
                    <button class="btn btn-primary btn-calculate">
                        <i class="fas fa-calculator"></i> Рассчитать
                    </button>
                </div>
            </div>
        </div>

        <!-- Популярные вопросы -->
        <div class="faq-section">
            <h3><i class="fas fa-question-circle"></i> Часто задаваемые вопросы</h3>
            
            <div class="faq-container">
                <div class="faq-item">
                    <div class="faq-question">
                        <h4>Как подготовиться к окрашиванию волос?</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>За 2-3 дня до окрашивания рекомендуется сделать питательную маску для волос. 
                        Не мойте голову в день процедуры - натуральный жир защитит кожу головы.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h4>Можно ли привести ребенка на стрижку?</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Да, у нас есть специальные детские стрижки. Мы создаем комфортную атмосферу для детей, 
                        предлагаем мультфильмы во время процедуры.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h4>Как часто нужно подстригать кончики волос?</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Рекомендуется подстригать кончики каждые 2-3 месяца для поддержания здорового вида волос 
                        и предотвращения сечения.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h4>Есть ли у вас подарочные сертификаты?</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Да, мы предлагаем подарочные сертификаты на любую сумму. Вы можете приобрести их 
                        в нашем салоне или оформить онлайн.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Акции и предложения -->
        <div class="promotions-section">
            <h3><i class="fas fa-percentage"></i> Акции и специальные предложения</h3>
            
            <div class="promotions-grid">
                <div class="promotion-card">
                    <div class="promotion-badge">Акция</div>
                    <div class="promotion-content">
                        <h4>Стрижка + Укладка = 1500 ₽</h4>
                        <p>При заказе стрижки и укладки одновременно - скидка 20% на комплекс</p>
                        <span class="promotion-period">Действует до 31.12.2025</span>
                    </div>
                </div>
                
                <div class="promotion-card">
                    <div class="promotion-badge">Новинка</div>
                    <div class="promotion-content">
                        <h4>Бесплатная консультация</h4>
                        <p>Первая консультация стилиста - бесплатно при записи на любую услугу</p>
                        <span class="promotion-period">Постоянное предложение</span>
                    </div>
                </div>
                
                <div class="promotion-card">
                    <div class="promotion-badge">Скидка</div>
                    <div class="promotion-content">
                        <h4>Скидка 15% в день рождения</h4>
                        <p>Предъявите документ в день рождения и получите скидку на любую услугу</p>
                        <span class="promotion-period">Действует весь год</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Модальное окно с деталями услуги -->
<div class="modal" id="serviceModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalServiceTitle"></h3>
            <button class="modal-close" onclick="closeServiceModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="modalServiceContent"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="bookThisService()">
                <i class="fas fa-calendar-alt"></i> Записаться на эту услугу
            </button>
            <button class="btn btn-secondary" onclick="closeServiceModal()">Закрыть</button>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<style>
/* Стили для страницы услуг */
.services-page {
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

/* Фильтр категорий */
.categories-filter {
    margin-bottom: 30px;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}

.filter-btn {
    padding: 10px 20px;
    border: 2px solid #ddd;
    background: white;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 600;
    color: #666;
    transition: all 0.3s;
}

.filter-btn:hover,
.filter-btn.active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

/* Карточки услуг */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

.service-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.service-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.service-header {
    padding: 20px;
    background: linear-gradient(135deg, #3498db, #2c3e50);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.service-icon i {
    font-size: 40px;
}

.badge {
    padding: 5px 10px;
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.service-body {
    padding: 20px;
    flex: 1;
}

.service-title {
    font-size: 20px;
    color: #2c3e50;
    margin-bottom: 10px;
    font-weight: 700;
}

.service-description {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 15px;
}

.service-details {
    display: flex;
    gap: 15px;
    margin-top: 15px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #7f8c8d;
    font-size: 14px;
}

.detail-item i {
    color: #3498db;
}

.service-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.service-price .price {
    font-size: 24px;
    font-weight: 700;
    color: #2ecc71;
    display: block;
}

.service-price .price-note {
    font-size: 12px;
    color: #95a5a6;
}

.service-actions {
    display: flex;
    gap: 10px;
}

.btn-book {
    background: #2ecc71;
    color: white;
    padding: 8px 16px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 600;
    transition: background 0.3s;
}

.btn-book:hover {
    background: #27ae60;
}

.btn-info {
    background: #3498db;
    color: white;
    padding: 8px 16px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s;
}

.btn-info:hover {
    background: #2980b9;
}

/* Калькулятор */
.calculator-section {
    margin: 60px 0;
}

.calculator-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.calculator-card h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.calculator-form {
    display: grid;
    gap: 20px;
    margin-top: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #2c3e50;
}

.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.calculator-result {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
}

.result-details {
    margin-top: 15px;
}

.result-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.result-row.total {
    font-weight: 700;
    font-size: 18px;
    color: #2c3e50;
    border-bottom: none;
}

.btn-calculate {
    background: #3498db;
    color: white;
    padding: 12px 24px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: background 0.3s;
}

.btn-calculate:hover {
    background: #2980b9;
}

/* FAQ */
.faq-section {
    margin: 60px 0;
}

.faq-container {
    margin-top: 30px;
}

.faq-item {
    background: white;
    border-radius: 10px;
    margin-bottom: 15px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.faq-question {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    background: #f8f9fa;
}

.faq-question h4 {
    margin: 0;
    color: #2c3e50;
}

.faq-question i {
    color: #3498db;
    transition: transform 0.3s;
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 0 20px;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s, padding 0.3s;
}

.faq-item.active .faq-answer {
    padding: 20px;
    max-height: 200px;
}

/* Акции */
.promotions-section {
    margin: 60px 0;
}

.promotions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.promotion-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    position: relative;
}

.promotion-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #e74c3c;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.promotion-content {
    padding: 30px;
}

.promotion-content h4 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.promotion-period {
    display: inline-block;
    margin-top: 15px;
    padding: 5px 10px;
    background: #f8f9fa;
    border-radius: 5px;
    font-size: 12px;
    color: #666;
}

/* Модальное окно */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 15px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

/* Адаптивность */
@media (max-width: 768px) {
    .services-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-buttons {
        flex-direction: column;
    }
    
    .filter-btn {
        width: 100%;
        text-align: center;
    }
    
    .service-footer {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .modal-content {
        width: 95%;
        margin: 10px;
    }
}
</style>

<script>
// Фильтрация услуг по категориям
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Убираем активный класс у всех кнопок
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        // Добавляем активный класс текущей кнопке
        this.classList.add('active');
        
        const category = this.dataset.category;
        const services = document.querySelectorAll('.service-item');
        
        services.forEach(service => {
            if (category === 'all' || service.dataset.category === category) {
                service.style.display = 'block';
                setTimeout(() => {
                    service.style.opacity = '1';
                    service.style.transform = 'scale(1)';
                }, 50);
            } else {
                service.style.opacity = '0';
                service.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    service.style.display = 'none';
                }, 300);
            }
        });
    });
});

// Калькулятор стоимости
document.querySelector('.btn-calculate').addEventListener('click', function() {
    const service1 = document.getElementById('calcService1');
    const service2 = document.getElementById('calcService2');
    const discount = document.getElementById('calcDiscount');
    
    let totalPrice = 0;
    let totalTime = 0;
    
    // Стоимость первой услуги
    if (service1.value) {
        totalPrice += parseFloat(service1.value);
        const duration = service1.options[service1.selectedIndex].dataset.duration || 0;
        totalTime += parseInt(duration);
    }
    
    // Стоимость второй услуги
    if (service2.value) {
        totalPrice += parseFloat(service2.value);
    }
    
    // Расчет скидки
    const discountPercent = parseInt(discount.value);
    const discountAmount = totalPrice * (discountPercent / 100);
    const finalPrice = totalPrice - discountAmount;
    
    // Обновление интерфейса
    document.getElementById('totalPrice').textContent = Math.round(totalPrice).toLocaleString('ru-RU') + ' ₽';
    document.getElementById('totalDiscount').textContent = Math.round(discountAmount).toLocaleString('ru-RU') + ' ₽';
    document.getElementById('finalPrice').textContent = Math.round(finalPrice).toLocaleString('ru-RU') + ' ₽';
    document.getElementById('totalTime').textContent = totalTime + ' мин';
});

// FAQ аккордеон
document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', function() {
        const faqItem = this.parentElement;
        const isActive = faqItem.classList.contains('active');
        
        // Закрываем все открытые вопросы
        document.querySelectorAll('.faq-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Открываем текущий вопрос, если он был закрыт
        if (!isActive) {
            faqItem.classList.add('active');
        }
    });
});

// Модальное окно с деталями услуги
let currentServiceId = null;

document.querySelectorAll('.service-info-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const serviceId = this.dataset.serviceId;
        currentServiceId = serviceId;
        
        // Показываем лоадер
        document.getElementById('modalServiceTitle').textContent = 'Загрузка...';
        document.getElementById('modalServiceContent').innerHTML = '<div class="loader"></div>';
        document.getElementById('serviceModal').style.display = 'flex';
        
        try {
            const response = await fetch(`api/get_service_details.php?id=${serviceId}`);
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('modalServiceTitle').textContent = data.service.name;
                
                let content = `
                    <div class="service-details-modal">
                        <div class="detail-row">
                            <strong>Категория:</strong> ${data.service.category || 'Не указана'}
                        </div>
                        <div class="detail-row">
                            <strong>Длительность:</strong> ${data.service.duration_min} минут
                        </div>
                        <div class="detail-row">
                            <strong>Стоимость:</strong> ${parseInt(data.service.price).toLocaleString('ru-RU')} ₽
                        </div>
                `;
                
                if (data.service.description) {
                    content += `
                        <div class="detail-row">
                            <strong>Описание:</strong>
                            <p>${data.service.description}</p>
                        </div>
                    `;
                }
                
                // Рекомендации
                content += `
                        <div class="recommendations">
                            <h4>Рекомендации:</h4>
                            <ul>
                                <li>Запись рекомендуется за 1-2 дня до желаемой даты</li>
                                <li>Приходите за 10 минут до назначенного времени</li>
                                <li>Сообщите мастеру о своих предпочтениях</li>
                            </ul>
                        </div>
                    </div>
                `;
                
                document.getElementById('modalServiceContent').innerHTML = content;
            } else {
                document.getElementById('modalServiceContent').innerHTML = 
                    '<p class="error">Не удалось загрузить информацию об услуге</p>';
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('modalServiceContent').innerHTML = 
                '<p class="error">Ошибка загрузки данных</p>';
        }
    });
});

function closeServiceModal() {
    document.getElementById('serviceModal').style.display = 'none';
    currentServiceId = null;
}

function bookThisService() {
    if (currentServiceId) {
        window.location.href = `booking.php?service_id=${currentServiceId}`;
    }
}

// Закрытие модального окна при клике вне его
document.getElementById('serviceModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeServiceModal();
    }
});

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Анимация появления элементов
    const serviceItems = document.querySelectorAll('.service-item');
    serviceItems.forEach((item, index) => {
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