<?php
// auth.php - Функции аутентификации и авторизации (адаптировано под PostgreSQL)

require_once __DIR__ . '/../config/database.php';

session_start();

// конфигурация безопасности
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 минут в секундах

// ========== АДМИНИСТРАТОРЫ (таблица admins) ==========

/**
 * Проверка, авторизован ли администратор
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Вход администратора через БД
 */
function adminLogin($email, $password) {
    global $db;
    
    // проверка блокировки по IP
    if (isAdminLockedOut()) {
        return [
            'success' => false,
            'message' => 'Слишком много попыток входа. Попробуйте позже.'
        ];
    }
    
    // ищем админа по email
    $stmt = $db->prepare("SELECT id, full_name, password_hash FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
 
    /** 
    // ОТЛАДКА
    echo '<pre>';
    var_dump($admin);
    var_dump(password_verify($password, $admin['password_hash'] ?? ''));
    echo '</pre>';
    exit;
    */

    if ($admin && password_verify($password, $admin['password_hash'])) {
        // успешный вход
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_login_time'] = time();
        $_SESSION['login_attempts'] = 0;
        
        logAdminLogin($_SERVER['REMOTE_ADDR'], true);
        
        return [
            'success' => true,
            'message' => 'Вход выполнен успешно'
        ];
    } else {
        incrementLoginAttempts();
        logAdminLogin($_SERVER['REMOTE_ADDR'], false);
        
        $attempts_left = MAX_LOGIN_ATTEMPTS - ($_SESSION['login_attempts'] ?? 0);
        return [
            'success' => false,
            'message' => 'Неверный email или пароль. Осталось попыток: ' . $attempts_left
        ];
    }
}

// ========== КЛИЕНТЫ (таблица clients) ==========

/**
 * Проверка, авторизован ли клиент
 */
function isClientLoggedIn() {
    return isset($_SESSION['client_id']) && $_SESSION['client_id'] > 0;
}

/**
 * Получить ID текущего клиента
 */
function getCurrentClientId() {
    return $_SESSION['client_id'] ?? null;
}

/**
 * Получить данные текущего клиента
 */
function getCurrentClientData($db) {
    if (!isClientLoggedIn()) {
        return null;
    }
    $stmt = $db->prepare("SELECT id, full_name, phone, email, created_at FROM clients WHERE id = ?");
    $stmt->execute([$_SESSION['client_id']]);
    return $stmt->fetch();
}

/**
 * Вход клиента по телефону и имени (регистрация при отсутствии)
 */
function clientLogin($db, $phone, $name = null) {
    // поиск клиента по телефону
    $stmt = $db->prepare("SELECT * FROM clients WHERE phone = ?");
    $stmt->execute([$phone]);
    $client = $stmt->fetch();
    
    if ($client) {
        $_SESSION['client_id'] = $client['id'];
        $_SESSION['client_name'] = $client['full_name'];
        $_SESSION['client_phone'] = $client['phone'];
        return ['success' => true, 'message' => 'Вход выполнен успешно', 'client' => $client];
    } elseif ($name) {
        // создание нового клиента (без пароля и email – используем временные значения)
        $tempEmail = 'client_' . uniqid() . '@temp.local';
        $tempHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO clients (full_name, phone, email, password_hash) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $phone, $tempEmail, $tempHash])) {
            $client_id = $db->lastInsertId();
            $_SESSION['client_id'] = $client_id;
            $_SESSION['client_name'] = $name;
            $_SESSION['client_phone'] = $phone;
            
            return [
                'success' => true,
                'message' => 'Новый клиент зарегистрирован',
                'client' => ['id' => $client_id, 'full_name' => $name, 'phone' => $phone]
            ];
        }
    }
    return ['success' => false, 'message' => 'Не удалось выполнить вход'];
}

/**
 * Быстрый вход/регистрация клиента (используется при бронировании)
 */
function quickClientAuth($db, $name, $phone) {
    // очистка и форматирование телефона
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 11 && $phone[0] === '8') {
        $phone = '7' . substr($phone, 1);
    }
    
    // проверка существования клиента
    $stmt = $db->prepare("SELECT * FROM clients WHERE phone = ?");
    $stmt->execute([$phone]);
    $client = $stmt->fetch();
    
    if ($client) {
        // обновляем имя при необходимости
        if ($client['full_name'] !== $name) {
            $stmt = $db->prepare("UPDATE clients SET full_name = ? WHERE id = ?");
            $stmt->execute([$name, $client['id']]);
        }
        $_SESSION['client_id'] = $client['id'];
        $_SESSION['client_name'] = $name;
        $_SESSION['client_phone'] = $phone;
        return $client['id'];
    } else {
        // создаём клиента с временными email и паролем
        $tempEmail = 'client_' . uniqid() . '@temp.local';
        $tempHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO clients (full_name, phone, email, password_hash) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $phone, $tempEmail, $tempHash])) {
            $client_id = $db->lastInsertId();
            $_SESSION['client_id'] = $client_id;
            $_SESSION['client_name'] = $name;
            $_SESSION['client_phone'] = $phone;
            return $client_id;
        }
    }
    return false;
}

// ========== ВЫХОД ==========

function adminLogout() {
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_login_time']);
    session_destroy();
}

function clientLogout() {
    unset($_SESSION['client_id'], $_SESSION['client_name'], $_SESSION['client_phone']);
}

// ========== БЛОКИРОВКА ПОПЫТОК (для админки) ==========

function isAdminLockedOut() {
    if (!isset($_SESSION['lockout_time'])) return false;
    if (time() - $_SESSION['lockout_time'] < LOCKOUT_TIME) return true;
    // сброс
    unset($_SESSION['lockout_time']);
    $_SESSION['login_attempts'] = 0;
    return false;
}

function incrementLoginAttempts() {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['lockout_time'] = time();
    }
}

// ========== ЛОГИРОВАНИЕ ==========

function logAdminLogin($ip, $success) {
    $log_file = __DIR__ . '/../logs/admin_login.log';
    $timestamp = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'FAILED';
    $message = "[{$timestamp}] IP: {$ip} - {$status}\n";
    
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
    file_put_contents($log_file, $message, FILE_APPEND | LOCK_EX);
}

// ========== CSRF ==========

function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ========== ЗАЩИТА СТРАНИЦ ==========

function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        header('Location: /admin/login.php');
        exit();
    }
    // время сессии 2 часа
    if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time'] > 7200)) {
        adminLogout();
        header('Location: /admin/login.php?session=expired');
        exit();
    }
}

function requireClientAuth() {
    if (!isClientLoggedIn()) {
        header('Location: /booking.php?auth=required');
        exit();
    }
}

// ========== ХЕШИРОВАНИЕ ПАРОЛЕЙ ==========

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========

function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) $data[$key] = sanitizeInput($value);
        return $data;
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPhone($phone) {
    $pattern = '/^(\+7|8)[\s\-]?\(?\d{3}\)?[\s\-]?\d{3}[\s\-]?\d{2}[\s\-]?\d{2}$/';
    return preg_match($pattern, $phone);
}

function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 11) {
        if ($phone[0] === '8') $phone = '7' . substr($phone, 1);
        return '+7 (' . substr($phone, 1, 3) . ') ' . 
               substr($phone, 4, 3) . '-' . 
               substr($phone, 7, 2) . '-' . 
               substr($phone, 9, 2);
    }
    return $phone;
}

function logAction($action, $details = null, $user_id = null, $user_type = 'admin') {
    $log_file = __DIR__ . '/../logs/actions.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $log_entry = "[{$timestamp}] {$user_type}:{$user_id} - {$action}";
    if ($details) $log_entry .= " - " . (is_array($details) ? json_encode($details) : $details);
    $log_entry .= " [IP: {$ip}, User-Agent: {$user_agent}]\n";
    
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Инициализация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    generateCsrfToken();
}