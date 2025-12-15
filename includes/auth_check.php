<?php
// auth_check.php
session_start();

// Если пользователь не авторизован, перенаправляем на страницу входа

$user = getUser();
if (!$user) {
    header('Location: index.php');
    exit;
}else if($user["is_blocked"]){
    header('Location: logout.php');
    exit;
}
