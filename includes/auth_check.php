<?php
// auth_check.php
session_start();

// Если пользователь не авторизован, перенаправляем на страницу входа
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
