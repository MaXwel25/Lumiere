    </main>

    <!-- Футер -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <!-- Информация о компании -->
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-cut"></i>
                        <div>
                            <h3>Парикмахерская "Стиль"</h3>
                            <p class="footer-subtitle">Профессиональные услуги с 2010 года</p>
                        </div>
                    </div>
                    <p class="footer-description">Мы создаем стиль и уверенность в каждом клиенте. Профессиональный подход и качественные материалы.</p>
                    <div class="footer-contact">
                        <p><i class="fas fa-phone-alt"></i> +7 (861) 123-45-67</p>
                        <p><i class="fas fa-envelope"></i> info@barbershop-style.ru</p>
                        <p><i class="fas fa-map-marker-alt"></i> г. Краснодар, ул. Красная, 100</p>
                    </div>
                </div>

                <!-- Быстрые ссылки -->
                <div class="footer-section">
                    <h4>Навигация</h4>
                    <ul class="footer-links">
                        <li><a href="/index.php"><i class="fas fa-chevron-right"></i> Главная</a></li>
                        <li><a href="/services.php"><i class="fas fa-chevron-right"></i> Услуги</a></li>
                        <li><a href="/masters.php"><i class="fas fa-chevron-right"></i> Мастера</a></li>
                        <li><a href="/booking.php"><i class="fas fa-chevron-right"></i> Онлайн запись</a></li>
                        <li><a href="/contacts.php"><i class="fas fa-chevron-right"></i> Контакты</a></li>
                    </ul>
                </div>

                <!-- Услуги -->
                <div class="footer-section">
                    <h4>Популярные услуги</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Мужские стрижки</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Женские стрижки</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Окрашивание волос</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Уход за волосами</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Стрижка бороды</a></li>
                    </ul>
                </div>

                <!-- Часы работы и соцсети -->
                <div class="footer-section">
                    <h4>Часы работы</h4>
                    <div class="working-hours">
                        <div class="hours-item">
                            <span>Понедельник - Пятница</span>
                            <span>9:00 - 19:00</span>
                        </div>
                        <div class="hours-item">
                            <span>Суббота</span>
                            <span>10:00 - 18:00</span>
                        </div>
                        <div class="hours-item">
                            <span>Воскресенье</span>
                            <span>10:00 - 16:00</span>
                        </div>
                    </div>
                    
                    <div class="social-media">
                        <h4>Мы в соцсетях</h4>
                        <div class="social-icons">
                            <a href="#" class="social-icon"><i class="fab fa-vk"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-telegram"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Копирайт -->
            <div class="footer-bottom">
                <div class="copyright">
                    <p>&copy; 2024 Парикмахерская "Стиль". Все права защищены.</p>
                </div>
                <div class="footer-links-bottom">
                    <a href="/privacy.php">Политика конфиденциальности</a>
                    <a href="/terms.php">Условия использования</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Кнопка "Наверх" -->
    <button class="scroll-to-top" onclick="scrollToTop()">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Основные скрипты -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
    
    <script>
    // Общие функции
    function toggleMobileMenu() {
        const mobileMenu = document.getElementById('mobileMenu');
        mobileMenu.classList.toggle('active');
        document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
    }
    
    // Функция для плавной прокрутки наверх
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
    
    // Показать/скрыть кнопку "Наверх"
    window.addEventListener('scroll', function() {
        const scrollButton = document.querySelector('.scroll-to-top');
        if (window.scrollY > 300) {
            scrollButton.classList.add('visible');
        } else {
            scrollButton.classList.remove('visible');
        }
    });
    
    // Анимация элементов при скролле
    function animateOnScroll() {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.2;
            
            if (elementPosition < screenPosition) {
                element.classList.add('animated');
            }
        });
    }
    
    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        // Анимация при скролле
        window.addEventListener('scroll', animateOnScroll);
        animateOnScroll(); // Первый запуск
        
        // Инициализация Flatpickr
        const datepickers = document.querySelectorAll('.datepicker');
        datepickers.forEach(el => {
            flatpickr(el, {
                locale: 'ru',
                minDate: 'today',
                dateFormat: 'Y-m-d',
                disable: [
                    function(date) {
                        return date.getDay() === 0; // Воскресенье
                    }
                ]
            });
        });
        
        // Форматирование телефонов
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 0) {
                    if (value.startsWith('8')) {
                        value = '7' + value.substring(1);
                    }
                    if (value.startsWith('7')) {
                        value = '+7' + value.substring(1, Math.min(value.length, 11));
                    }
                }
                e.target.value = value;
            });
        });
        
        // Закрытие мобильного меню при клике на ссылку
        document.querySelectorAll('.mobile-nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                toggleMobileMenu();
            });
        });
    });
    
    // Функция для показа уведомлений
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    // AJAX запросы
    async function makeRequest(url, method = 'GET', data = null) {
        try {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            };
            
            if (data) {
                options.body = new URLSearchParams(data).toString();
            }
            
            const response = await fetch(url, options);
            return await response.json();
        } catch (error) {
            console.error('Ошибка запроса:', error);
            showNotification('Ошибка соединения с сервером', 'error');
            return null;
        }
    }
    </script>
    
    <style>
        /* Дополнительные стили для футера */
        .footer {
            background: var(--secondary-color);
            color: white;
            padding: 60px 0 30px;
        }
        
        .footer-section {
            margin-bottom: 30px;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .footer-logo i {
            font-size: 32px;
            color: var(--primary-color);
        }
        
        .footer-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }
        
        .footer-description {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .footer-contact p {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .footer-contact i {
            color: var(--primary-color);
            width: 20px;
        }
        
        .footer-section h4 {
            color: white;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .working-hours {
            margin-bottom: 30px;
        }
        
        .hours-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .hours-item:last-child {
            border-bottom: none;
        }
        
        .social-media h4 {
            margin-bottom: 15px;
        }
        
        .social-icons {
            display: flex;
            gap: 15px;
        }
        
        .social-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            transition: var(--transition);
        }
        
        .social-icon:hover {
            background: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .copyright {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
        }
        
        .footer-links-bottom {
            display: flex;
            gap: 20px;
        }
        
        .footer-links-bottom a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .footer-links-bottom a:hover {
            color: var(--primary-color);
        }
        
        /* Кнопка "Наверх" */
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: var(--shadow-hover);
        }
        
        .scroll-to-top.visible {
            opacity: 1;
            visibility: visible;
        }
        
        .scroll-to-top:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
        }
        
        /* Уведомления */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-hover);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            max-width: 400px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            border-left: 4px solid var(--success-color);
        }
        
        .notification-error {
            border-left-color: var(--danger-color);
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .notification-content i {
            font-size: 20px;
        }
        
        .notification-success .notification-content i {
            color: var(--success-color);
        }
        
        .notification-error .notification-content i {
            color: var(--danger-color);
        }
        
        .notification-close {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        
        .notification-close:hover {
            color: #333;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Адаптивность */
        @media (max-width: 992px) {
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
            
            .scroll-to-top {
                bottom: 20px;
                right: 20px;
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            
            .notification {
                left: 20px;
                right: 20px;
                min-width: auto;
                max-width: none;
            }
        }
    </style>
</body>
</html>