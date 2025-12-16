<?php
require_once 'includes/header.php';
?>
<!-- Герой-секция -->
<section class="hero" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1509391309561-f19e0d3c7c8f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80'); background-size: cover; background-position: center;">
    <div class="container">
        <div class="hero-content">
            <h1>Контакты</h1>
            <p>Свяжитесь с нами любым удобным способом. Мы всегда рады ответить на ваши вопросы и помочь с записью.</p>
        </div>
    </div>
</section>

<!-- Контакты -->
<section class="section contacts-section">
    <div class="container">
        <h2 class="section-title">Наши контакты</h2>
        <div class="contacts-grid">
            <div class="contact-info">
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-details">
                        <h3>Наш адрес</h3>
                        <p>г. Краснодар, ул. Красная, 100</p>
                        <p style="margin-top: 10px; font-style: italic; color: var(--dark-gray);">
                            Удобное расположение в центре города с парковкой для клиентов
                        </p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-details">
                        <h3>Телефоны</h3>
                        <p><strong>+7 (861) 123-45-67</strong> - основной номер</p>
                        <p>+7 (861) 987-65-43 - WhatsApp и Telegram</p>
                        <p style="margin-top: 10px;">
                            <a href="tel:+78611234567" class="btn btn-primary" style="padding: 8px 15px; margin-right: 10px;">
                                <i class="fas fa-phone"></i> Позвонить
                            </a>
                            <a href="https://wa.me/78611234567" class="btn btn-secondary" style="padding: 8px 15px;">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                        </p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="contact-details">
                        <h3>Режим работы</h3>
                        <p><strong>Понедельник - Пятница:</strong> 9:00 - 19:00</p>
                        <p><strong>Суббота:</strong> 10:00 - 18:00</p>
                        <p><strong>Воскресенье:</strong> 10:00 - 16:00</p>
                        <p style="margin-top: 10px; color: var(--primary-color); font-weight: bold;">
                            <i class="fas fa-exclamation-circle"></i> Выходной день: Понедельник (раз в месяц)
                        </p>
                    </div>
                </div>
            </div>
            <div class="contact-form">
                <h3>Напишите нам</h3>
                <form action="#" method="POST">
                    <div class="form-group">
                        <label for="name">Ваше имя</label>
                        <input type="text" id="name" name="name" class="form-control-input" required placeholder="Введите ваше имя">
                    </div>
                    <div class="form-group">
                        <label for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone" class="form-control-input" required placeholder="+7 (___) ___-__-__">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control-input" placeholder="Введите ваш email">
                    </div>
                    <div class="form-group">
                        <label for="message">Сообщение</label>
                        <textarea id="message" name="message" class="form-control-input" rows="4" required placeholder="Введите ваше сообщение"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Отправить сообщение
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Карта -->
<section class="section" style="background-color: white;">
    <div class="container">
        <h2 class="section-title">Как нас найти</h2>
        <div style="background-color: var(--light-gray); border-radius: 10px; padding: 20px; text-align: center; margin-top: 30px;">
            <p style="font-size: 1.2rem; margin-bottom: 20px;">
                <i class="fas fa-map-marked-alt" style="color: var(--primary-color); margin-right: 10px;"></i>
                г. Краснодар, ул. Красная, 100
            </p>
            <div style="display: inline-block; background-color: white; width: 100%; max-width: 800px; height: 400px; border-radius: 10px; border: 1px solid #ddd; overflow: hidden;">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2565.042611844606!2d38.97606731542211!3d45.03554477910266!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40f04cb8334a3a8d%3A0x5f4a70b1a0b1a0b1!2z0YPQu9C40YbQsCDQn9GA0L7QstCw0L3QsNC70LjRhtCw0L3RgiwgMTAwLCDQn9GA0L7QstCw0YDRgdC60LDRjyDQv9GA0L7QsdGD0YDQsy4sINCg0LDQutGC0LjQsA!5e0!3m2!1sru!2sru!4v1640000000000!5m2!1sru!2sru" 
                        width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</section>

<!-- Дополнительная информация -->
<section class="section" style="background-color: var(--light-gray);">
    <div class="container">
        <h2 class="section-title">Дополнительная информация</h2>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-parking"></i>
                </div>
                <h3>Парковка</h3>
                <p>Бесплатная парковка для клиентов на территории салона. Всегда есть места даже в час пик.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-coffee"></i>
                </div>
                <h3>Чай и кофе</h3>
                <p>Пока вы ждете своей процедуры, наслаждайтесь ароматным кофе или чаем из нашей коллекции.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-wifi"></i>
                </div>
                <h3>Бесплатный Wi-Fi</h3>
                <p>Доступ к высокоскоростному интернету во всех зонах салона. Работайте или отдыхайте во время процедуры.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-child"></i>
                </div>
                <h3>Детская зона</h3>
                <p>Если вы пришли с ребенком, он сможет поиграть в нашей специально оборудованной детской зоне под присмотром.</p>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInputs = document.querySelectorAll('#phone, .form-control-input[type="tel"]');
        
        phoneInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length > 0) {
                    value = '+7 (' + value.substring(1, 4) + ') ' + value.substring(4, 7) + '-' + value.substring(7, 9) + '-' + value.substring(9, 11);
                }
                
                e.target.value = value.substring(0, 18);
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>