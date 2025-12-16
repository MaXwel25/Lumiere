<?php
// logout.php
session_start();

// уничтожаем все данные сессии
$_SESSION = array();

// если требуется уничтожить куки сессии, удаляем также куки сессии
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// уничтожаем сессию
session_destroy();

// перенаправляем на страницу входа с сообщением
header('Location: login.php?logout=1');
exit();
?>