// основной скрипт приложения

// анимация при прокрутке
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
        }
    });
}, observerOptions);

// наблюдаем за элементами с классом .animate-on-scroll
document.addEventListener('DOMContentLoaded', function() {
    const animateElements = document.querySelectorAll('.animate-on-scroll');
    animateElements.forEach(el => observer.observe(el));
    
    // анимация появления карточек
    const cards = document.querySelectorAll('.card');
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

// функция для валидации форм
function validateForm(form) {
    let isValid = true;
    const requiredInputs = form.querySelectorAll('[required]');
    
    requiredInputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
            
            // Создаем сообщение об ошибке
            let errorMsg = input.nextElementSibling;
            if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                errorMsg = document.createElement('div');
                errorMsg.className = 'error-message text-danger mt-1';
                errorMsg.textContent = 'Это поле обязательно для заполнения';
                input.parentNode.appendChild(errorMsg);
            }
        } else {
            input.classList.remove('error');
            const errorMsg = input.nextElementSibling;
            if (errorMsg && errorMsg.classList.contains('error-message')) {
                errorMsg.remove();
            }
        }
    });
    
    return isValid;
}

// функция для AJAX запросов
async function makeRequest(url, method = 'GET', data = null) {
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        if (data) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error('Ошибка запроса:', error);
        showNotification('Ошибка соединения с сервером', 'error');
        return null;
    }
}

// экспорт функций для использования в других файлах
window.app = {
    validateForm,
    makeRequest,
    showNotification: window.showNotification
};

// функция для отладки - показывает какие кнопки кликаются
document.addEventListener('DOMContentLoaded', function() {
    const actionButtons = document.querySelectorAll('.action-buttons .btn, .action-buttons a.btn');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            console.log('Кнопка нажата:', this.className, 'Href:', this.href);
            
            // предотвращаем стандартное поведение для кнопок-ссылок с href="#"
            if (this.getAttribute('href') === '#' || 
                this.getAttribute('href') === 'javascript:void(0)') {
                e.preventDefault();
            }
        });
    });
    
    // убедимся, что все кнопки имеют правильный курсор
    const allButtons = document.querySelectorAll('button, a.btn');
    allButtons.forEach(btn => {
        btn.style.cursor = 'pointer';
    });
});

// улучшенная функция для печати чека
function printReceipt(receiptId) {
    if (!receiptId) {
        alert('Ошибка: ID чека не указан');
        return;
    }
    
    console.log('Печать чека:', receiptId);
    
    // открываем новое окно для печати
    const printWindow = window.open('print_receipt.php?id=' + receiptId, '_blank');
    
    if (!printWindow) {
        alert('Пожалуйста, разрешите всплывающие окна для печати чека');
        return;
    }
    
    printWindow.focus();
}

// улучшенная функция для просмотра деталей чека
function showReceiptDetails(receiptId) {
    if (!receiptId) {
        alert('Ошибка: ID чека не указан');
        return;
    }
    
    console.log('Просмотр чека:', receiptId);
    
    // показываем загрузку
    document.getElementById('receiptDetails').innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Загрузка деталей чека...</p>
        </div>
    `;
    
    // показываем модальное окно
    document.getElementById('receiptModal').style.display = 'block';
    
    // загружаем данные
    fetch('get_receipt_details.php?id=' + receiptId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка загрузки');
            }
            return response.text();
        })
        .then(html => {
            document.getElementById('receiptDetails').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('receiptDetails').innerHTML = `
                <div class="alert alert-danger">
                    <h4>Ошибка загрузки</h4>
                    <p>Не удалось загрузить детали чека.</p>
                    <p>${error.message}</p>
                </div>
            `;
        });
}

// альтернативная версия функции showReceiptDetails без fetch (если файла нет)
function showReceiptDetailsSimple(receiptId) {
    const receipt = {
        id: receiptId,
        date: new Date().toLocaleDateString('ru-RU'),
        client: 'Иванов Иван Иванович',
        master: 'Петрова Мария Сергеевна',
        service: 'Женская стрижка',
        amount: '1200 ₽',
        status: 'Оплачено'
    };
    
    const details = `
        <div class="receipt-details-modal">
            <h3>Чек #${receipt.id}</h3>
            <div class="detail-item">
                <strong>Дата создания:</strong> ${receipt.date}
            </div>
            <div class="detail-item">
                <strong>Клиент:</strong> ${receipt.client}
            </div>
            <div class="detail-item">
                <strong>Мастер:</strong> ${receipt.master}
            </div>
            <div class="detail-item">
                <strong>Услуга:</strong> ${receipt.service}
            </div>
            <div class="detail-item">
                <strong>Сумма:</strong> ${receipt.amount}
            </div>
            <div class="detail-item">
                <strong>Статус:</strong> ${receipt.status}
            </div>
        </div>
    `;
    
    document.getElementById('receiptDetails').innerHTML = details;
    document.getElementById('receiptModal').style.display = 'block';
}

// зункция для закрытия модального окна
function closeReceiptModal() {
    document.getElementById('receiptModal').style.display = 'none';
}

// закрытие модального окна при клике вне его
document.getElementById('receiptModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeReceiptModal();
    }
});