<?php
// logout.php
session_start();

$bak = null;
if(isset($_GET["saveproject"]) && $_GET["saveproject"] == "1" && isset($_SESSION["project_data"]))
    $bak = $_SESSION["project_data"];

// Удаляем все данные сессии
$_SESSION = [];
if($bak != null)
    $_SESSION["project_data"] = $bak;

// Если используется cookie для сессии, удаляем её
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Удаляем куку "запомнить меня"
setcookie('remember_token', '', time() - 3600, '/');

// Уничтожаем сессию
session_destroy();

// Перенаправляем на страницу авторизации
header('Location: index.php');
exit;