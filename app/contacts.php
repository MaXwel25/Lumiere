<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Устанавливаем заголовок страницы
$page_title = "Контакты";
$page_subtitle = "Свяжитесь с нами любым удобным способом";

// Контактные данные
$contacts = [
    'phone' => '+7 (861) 123-45-67',
    'email' => 'info@barbershop-style.ru',
    'address' => 'г. Краснодар, ул. Красная, 100',
    'work_hours' => [
        ['days' => 'Понедельник - Пятница', 'hours' => '9:00 - 19:00'],
        ['days' => 'Суббота', 'hours' => '10:00 - 18:00'],
        ['days' => 'Воскресенье', 'hours' => '10:00 - 16:00']
    ]
];

require_once 'includes/header.php';
?>

<!-- Хлебные крошки -->
<section class="breadcrumbs">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/index.php"><i class="fas fa-home"></i> Главная</a></li>
                <li class="breadcrumb-item active" aria-current="page">Контакты</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Основной контент -->
<section class="contacts-page">
    <div class="container">
        <!-- Заголовок секции -->
        <div class="section-header">
            <h1><?php echo $page_title; ?></h1>
            <p class="section-subtitle"><?php echo $page_subtitle; ?></p>
        </div>

        <!-- Контактная информация -->
        <div class="contact-info-section">
            <div class="contact-cards">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-content">
                        <h4>Адрес салона</h4>
                        <p><?php echo $contacts['address']; ?></p>
                        <a href="#map" class="contact-link">
                            <i class="fas fa-directions"></i> Построить маршрут
                        </a>
                    </div>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-content">
                        <h4>Телефон</h4>
                        <p><?php echo $contacts['phone']; ?></p>
                        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $contacts['phone']); ?>" class="contact-link">
                            <i class="fas fa-phone-alt"></i> Позвонить сейчас
                        </a>
                    </div>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-content">
                        <h4>Email</h4>
                        <p><?php echo $contacts['email']; ?></p>
                        <a href="mailto:<?php echo $contacts['email']; ?>" class="contact-link">
                            <i class="fas fa-paper-plane"></i> Написать письмо
                        </a>
                    </div>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="contact-content">
                        <h4>Часы работы</h4>
                        <div class="work-hours">
                            <?php foreach ($contacts['work_hours'] as $hours): ?>
                                <div class="hours-item">
                                    <span><?php echo $hours['days']; ?></span>
                                    <span><?php echo $hours['hours']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Карта и форма обратной связи -->
        <div class="map-form-section">
            <div class="map-container" id="map">
                <h3><i class="fas fa-map-marked-alt"></i> Мы на карте</h3>
                <!-- Яндекс.Карта -->
                <div class="map-wrapper">
                    <div id="yandexMap" style="width: 100%; height: 400px;"></div>
                    <!-- Альтернатива - статичная карта -->
                    <div class="map-fallback" id="staticMap">
                        <img src="https://static-maps.yandex.ru/1.x/?ll=38.974722,45.035556&size=650,400&z=15&l=map&pt=38.974722,45.035556,pm2rdm" 
                             alt="Карта расположения парикмахерской">
                        <div class="map-overlay">
                            <p>Для интерактивной карты разрешите JavaScript</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="contact-form-container">
                <h3><i class="fas fa-comments"></i> Обратная связь</h3>
                <p>Оставьте ваше сообщение, и мы свяжемся с вами в ближайшее время</p>
                
                <form id="contactForm" class="contact-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactName">Ваше имя *</label>
                            <input type="text" id="contactName" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="contactPhone">Телефон *</label>
                            <input type="tel" id="contactPhone" name="phone" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="contactEmail">Email</label>
                        <input type="email" id="contactEmail" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="contactSubject">Тема сообщения</label>
                        <select id="contactSubject" name="subject">
                            <option value="">Выберите тему</option>
                            <option value="booking">Запись на услугу</option>
                            <option value="question">Вопрос о услугах</option>
                            <option value="feedback">Отзыв о работе</option>
                            <option value="cooperation">Сотрудничество</option>
                            <option value="other">Другое</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="contactMessage">Сообщение *</label>
                        <textarea id="contactMessage" name="message" rows="5" required 
                                  placeholder="Опишите ваш вопрос или пожелание..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox">
                            <input type="checkbox" name="agree" required>
                            <span>Я согласен(а) на обработку персональных данных</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-paper-plane"></i> Отправить сообщение
                    </button>
                </form>
            </div>
        </div>

        <!-- Часто задаваемые вопросы -->
        <div class="contact-faq">
            <h3><i class="fas fa-question-circle"></i> Как добраться?</h3>
            
            <div class="transport-options">
                <div class="transport-card">
                    <div class="transport-icon">
                        <i class="fas fa-subway"></i>
                    </div>
                    <div class="transport-content">
                        <h4>Метро</h4>
                        <p>Ближайшая станция: "Центральная" (выход к ул. Красной)</p>
                        <p class="transport-time">5 минут пешком</p>
                    </div>
                </div>
                
                <div class="transport-card">
                    <div class="transport-icon">
                        <i class="fas fa-bus"></i>
                    </div>
                    <div class="transport-content">
                        <h4>Автобус</h4>
                        <p>Остановка "Улица Красная": маршруты 2, 7, 15, 23</p>
                        <p class="transport-time">2 минуты пешком</p>
                    </div>
                </div>
                
                <div class="transport-card">
                    <div class="transport-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="transport-content">
                        <h4>На автомобиле</h4>
                        <p>Рядом с салоном есть парковка на 20 мест</p>
                        <p class="transport-time">Бесплатная парковка для клиентов</p>
                    </div>
                </div>
                
                <div class="transport-card">
                    <div class="transport-icon">
                        <i class="fas fa-taxi"></i>
                    </div>
                    <div class="transport-content">
                        <h4>Такси</h4>
                        <p>Адрес для такси: <?php echo $contacts['address']; ?></p>
                        <p class="transport-time">Рекомендуем: Яндекс.Такси, Такси Максим</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Социальные сети -->
        <div class="social-section">
            <h3><i class="fas fa-hashtag"></i> Мы в социальных сетях</h3>
            <p>Подписывайтесь на наши соцсети, чтобы быть в курсе акций и новостей</p>
            
            <div class="social-links">
                <a href="#" class="social-link vk">
                    <i class="fab fa-vk"></i>
                    <span>ВКонтакте</span>
                </a>
                
                <a href="#" class="social-link instagram">
                    <i class="fab fa-instagram"></i>
                    <span>Instagram</span>
                </a>
                
                <a href="#" class="social-link telegram">
                    <i class="fab fa-telegram"></i>
                    <span>Telegram</span>
                </a>
                
                <a href="#" class="social-link youtube">
                    <i class="fab fa-youtube"></i>
                    <span>YouTube</span>
                </a>
                
                <a href="#" class="social-link ok">
                    <i class="fab fa-odnoklassniki"></i>
                    <span>Одноклассники</span>
                </a>
            </div>
        </div>

        <!-- Дополнительная информация -->
        <div class="additional-info">
            <div class="info-card">
                <h4><i class="fas fa-info-circle"></i> Полезная информация</h4>
                <ul class="info-list">
                    <li><i class="fas fa-check-circle"></i> Бесплатная консультация перед услугой</li>
                    <li><i class="fas fa-check-circle"></i> Подарочные сертификаты</li>
                    <li><i class="fas fa-check-circle"></i> Скидки постоянным клиентам</li>
                    <li><i class="fas fa-check-circle"></i> Бесплатный Wi-Fi для клиентов</li>
                    <li><i class="fas fa-check-circle"></i> Кофе и чай в зоне ожидания</li>
                </ul>
            </div>
            
            <div class="info-card">
                <h4><i class="fas fa-headset"></i> Служба поддержки</h4>
                <p>Если у вас возникли вопросы или проблемы, наша служба поддержки всегда готова помочь:</p>
                <div class="support-contacts">
                    <p><i class="fas fa-phone"></i> Телефон: <?php echo $contacts['phone']; ?></p>
                    <p><i class="fas fa-envelope"></i> Email: support@barbershop-style.ru</p>
                    <p><i class="fas fa-clock"></i> Время работы: ежедневно 8:00-20:00</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<style>
/* Стили для страницы контактов */
.contacts-page {
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

/* Контактные карточки */
.contact-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-bottom: 60px;
}

.contact-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s;
}

.contact-card:hover {
    transform: translateY(-5px);
}

.contact-icon {
    font-size: 40px;
    color: #3498db;
    margin-bottom: 20px;
}

.contact-content h4 {
    color: #2c3e50;
    margin-bottom: 10px;
    font-size: 20px;
}

.contact-content p {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.6;
}

.contact-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #3498db;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s;
}

.contact-link:hover {
    color: #2980b9;
}

.work-hours {
    margin-top: 15px;
}

.hours-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.hours-item:last-child {
    border-bottom: none;
}

/* Карта и форма */
.map-form-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 60px;
}

@media (max-width: 992px) {
    .map-form-section {
        grid-template-columns: 1fr;
    }
}

.map-container,
.contact-form-container {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.map-container h3,
.contact-form-container h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.map-container p {
    color: #666;
    margin-bottom: 20px;
}

.map-wrapper {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 20px;
}

.map-fallback {
    position: relative;
}

.map-fallback img {
    width: 100%;
    height: 400px;
    object-fit: cover;
}

.map-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
}

/* Форма обратной связи */
.contact-form {
    margin-top: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 576px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3498db;
}

.checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 14px;
    color: #666;
}

.checkbox input {
    width: auto;
}

.btn-submit {
    background: #2ecc71;
    color: white;
    padding: 15px 30px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: background 0.3s;
    width: 100%;
}

.btn-submit:hover {
    background: #27ae60;
}

/* Транспорт */
.contact-faq {
    margin: 60px 0;
}

.transport-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.transport-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: flex-start;
    gap: 20px;
    transition: transform 0.3s;
}

.transport-card:hover {
    transform: translateY(-3px);
}

.transport-icon {
    font-size: 30px;
    color: #3498db;
    min-width: 40px;
}

.transport-content h4 {
    color: #2c3e50;
    margin-bottom: 10px;
    font-size: 18px;
}

.transport-content p {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 5px;
}

.transport-time {
    color: #2ecc71 !important;
    font-weight: 600;
}

/* Социальные сети */
.social-section {
    margin: 60px 0;
    text-align: center;
}

.social-links {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
}

.social-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 25px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: transform 0.3s, box-shadow 0.3s;
    min-width: 150px;
    justify-content: center;
}

.social-link:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.social-link.vk {
    background: #4c75a3;
    color: white;
}

.social-link.instagram {
    background: linear-gradient(45deg, #405de6, #5851db, #833ab4, #c13584, #e1306c, #fd1d1d);
    color: white;
}

.social-link.telegram {
    background: #0088cc;
    color: white;
}

.social-link.youtube {
    background: #ff0000;
    color: white;
}

.social-link.ok {
    background: #ee8208;
    color: white;
}

/* Дополнительная информация */
.additional-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 60px;
}

.info-card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.info-card h4 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 22px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-list {
    list-style: none;
    padding: 0;
}

.info-list li {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #666;
}

.info-list li:last-child {
    border-bottom: none;
}

.info-list li i {
    color: #2ecc71;
}

.support-contacts {
    margin-top: 20px;
}

.support-contacts p {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    color: #666;
}

.support-contacts i {
    color: #3498db;
    width: 20px;
}

/* Уведомления формы */
.form-message {
    padding: 15px;
    border-radius: 5px;
    margin-top: 20px;
    display: none;
}

.form-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.form-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.loading {
    text-align: center;
    padding: 20px;
}

.loader {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
// Яндекс.Карта
function initMap() {
    try {
        // Координаты парикмахерской (Краснодар, ул. Красная, 100)
        const coordinates = [38.974722, 45.035556];
        
        // Создаем карту
        const map = new ymaps.Map('yandexMap', {
            center: coordinates,
            zoom: 16,
            controls: ['zoomControl', 'fullscreenControl']
        });
        
        // Добавляем метку
        const placemark = new ymaps.Placemark(coordinates, {
            hintContent: 'Парикмахерская "Стиль"',
            balloonContent: `
                <div class="map-balloon">
                    <h4>Парикмахерская "Стиль"</h4>
                    <p>г. Краснодар, ул. Красная, 100</p>
                    <p>Телефон: <?php echo $contacts['phone']; ?></p>
                    <a href="https://yandex.ru/maps/?pt=${coordinates[0]},${coordinates[1]}&z=16&l=map" 
                       target="_blank" class="map-link">
                        <i class="fas fa-external-link-alt"></i> Открыть в Яндекс.Картах
                    </a>
                </div>
            `
        }, {
            iconLayout: 'default#image',
            iconImageHref: 'https://cdn-icons-png.flaticon.com/512/684/684908.png',
            iconImageSize: [40, 40],
            iconImageOffset: [-20, -40]
        });
        
        map.geoObjects.add(placemark);
        
        // Скрываем статичную карту
        document.getElementById('staticMap').style.display = 'none';
        
    } catch (error) {
        console.error('Ошибка инициализации карты:', error);
        document.getElementById('staticMap').style.display = 'block';
    }
}

// Загрузка Яндекс.Карт
if (typeof ymaps !== 'undefined') {
    ymaps.ready(initMap);
} else {
    // Если API не загрузилось, показываем статичную карту
    document.getElementById('staticMap').style.display = 'block';
}

// Форма обратной связи
document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const form = this;
    const submitBtn = form.querySelector('.btn-submit');
    const originalText = submitBtn.innerHTML;
    
    // Показываем лоадер
    submitBtn.innerHTML = '<div class="loader"></div> Отправка...';
    submitBtn.disabled = true;
    
    // Собираем данные формы
    const formData = new FormData(form);
    
    try {
        // В реальном проекте здесь будет отправка на сервер
        // Для демонстрации имитируем отправку
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // Показываем сообщение об успехе
        showFormMessage('Ваше сообщение успешно отправлено! Мы свяжемся с вами в ближайшее время.', 'success');
        
        // Сбрасываем форму
        form.reset();
        
    } catch (error) {
        // Показываем сообщение об ошибке
        showFormMessage('Произошла ошибка при отправке сообщения. Пожалуйста, попробуйте еще раз.', 'error');
        console.error('Ошибка отправки формы:', error);
    } finally {
        // Восстанавливаем кнопку
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
});

// Функция для показа сообщений формы
function showFormMessage(message, type) {
    // Удаляем старые сообщения
    const oldMessage = document.querySelector('.form-message');
    if (oldMessage) {
        oldMessage.remove();
    }
    
    // Создаем новое сообщение
    const messageDiv = document.createElement('div');
    messageDiv.className = `form-message ${type}`;
    messageDiv.innerHTML = `
        <div class="message-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Вставляем сообщение перед кнопкой
    const submitBtn = document.querySelector('.btn-submit');
    submitBtn.parentNode.insertBefore(messageDiv, submitBtn);
    
    // Автоматически скрываем через 5 секунд
    setTimeout(() => {
        messageDiv.style.opacity = '0';
        messageDiv.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 300);
    }, 5000);
}

// Форматирование телефона
document.getElementById('contactPhone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 0) {
        value = '+7' + value.substring(1, Math.min(value.length, 11));
    }
    e.target.value = value;
});

// Копирование контактных данных
document.querySelectorAll('.contact-link').forEach(link => {
    if (link.href.includes('tel:')) {
        link.addEventListener('click', function(e) {
            if (!isMobileDevice()) {
                e.preventDefault();
                const phone = this.getAttribute('href').replace('tel:', '');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(phone).then(() => {
                        showNotification('Номер телефона скопирован в буфер обмена');
                    });
                }
            }
        });
    }
});

// Проверка мобильного устройства
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Всплывающее уведомление
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 3000);
}

// Стили для уведомлений
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #2ecc71;
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        transform: translateX(100%);
        opacity: 0;
        transition: transform 0.3s, opacity 0.3s;
        z-index: 10000;
    }
    
    .notification.show {
        transform: translateX(0);
        opacity: 1;
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .notification i {
        font-size: 20px;
    }
    
    @media (max-width: 768px) {
        .notification {
            left: 20px;
            right: 20px;
            transform: translateY(-100%);
        }
        
        .notification.show {
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(notificationStyles);

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Анимация появления карточек
    const cards = document.querySelectorAll('.contact-card, .transport-card, .info-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>