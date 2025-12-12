<?php

include  "includes/db.php";
session_start();

use Medoo\Medoo;

/*
$hashed_password = password_hash('PlanCheckAdmin25!', PASSWORD_DEFAULT);
CORE::$db->insert('user', [
    'login' => 'twobomb',
    'pwd_hash' => $hashed_password,
    'username' => 'Администратор'
]);
if(is_null(CORE::$db->error))
    echo "Пользователь создан";
else
    echo "Ошиька".CORE::$db->error;
die;*/


// Конфигурация базы данных
$database = CORE::$db;

// Инициализация переменных
$error = '';
$success = '';
$login = '';
$remember = false;

// Если пользователь уже авторизован, перенаправляем
if (isset($_SESSION['user_id'])) {
    header('Location: /plan.php');
    exit;
}

// Проверка наличия куков для "запомнить меня"
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    // Ищем пользователя по токену
    $user = $database->get('user',
        ['id', 'login', 'username', 'remember_token'],
        ['remember_token' => $token]
    );

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login'] = $user['login'];
        $_SESSION['username'] = $user['username'];

        header('Location: /plan.php');
        exit;
    } else {
        // Неверный токен - удаляем куку
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// Обработка формы авторизации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Валидация
    if (empty($login) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        // Поиск пользователя в базе
        $user = $database->get('user',
            ['id', 'login', 'username', 'pwd_hash'],
            ['login' => $login]
        );

        if ($user && password_verify($password, $user['pwd_hash'])) {
            // Успешная авторизация
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login'] = $user['login'];
            $_SESSION['username'] = $user['username'];

            // Обработка "Запомнить меня"
            if ($remember) {
                // Генерация уникального токена
                $remember_token = bin2hex(random_bytes(32));
                $expire = time() + 30 * 24 * 60 * 60; // 30 дней

                // Сохраняем токен в базе
                $database->update('user',
                    ['remember_token' => $remember_token],
                    ['id' => $user['id']]
                );

                // Устанавливаем куку
                setcookie('remember_token', $remember_token, $expire, '/');
            }

            // Перенаправление на страницу планов
            header('Location: /plan.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация - Система управления планами</title>

    <!-- Подключаем библиотеки -->
    <link href="/css/fontawesome-free-6.7.2-web/css/all.min.css" rel="stylesheet">
    <link href="/css/Inter-4.1/web/inter.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 450px;
        }

        .auth-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .auth-header {
            background: linear-gradient(90deg, #2c3e50 0%, #4a6491 100%);
            color: white;
            padding: 32px;
            text-align: center;
        }

        .auth-header h1 {
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .auth-header h1 i {
            color: #3498db;
        }

        .auth-header p {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 10px;
        }

        .auth-content {
            padding: 40px 32px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
            font-size: 15px;
        }

        .form-group label.required:after {
            content: " *";
            color: #dc3545;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 16px;
        }

        .toggle-password:hover {
            color: #3498db;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #3498db;
            cursor: pointer;
        }

        .remember-me label {
            cursor: pointer;
            color: #495057;
            font-size: 15px;
            user-select: none;
        }

        .auth-btn {
            width: 100%;
            padding: 14px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .auth-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .auth-btn:active {
            transform: translateY(0);
        }

        .auth-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }

        .auth-footer a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-icon {
            font-size: 18px;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .demo-credentials {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin-top: 24px;
            border: 1px solid #e9ecef;
            font-size: 14px;
        }

        .demo-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .demo-content {
            color: #495057;
            line-height: 1.6;
        }

        @media (max-width: 480px) {
            .auth-header {
                padding: 24px 20px;
            }

            .auth-content {
                padding: 30px 20px;
            }

            .auth-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1><i class="fas fa-calendar-alt"></i> Plan-check</h1>
            <p>Войдите в свою учетную запись</p>
        </div>

        <div class="auth-content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-content">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-content">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="login" class="required">Логин</label>
                    <input type="text"
                           id="login"
                           name="login"
                           class="form-input"
                           placeholder="Введите ваш логин"
                           value="<?php echo htmlspecialchars($login); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="password" class="required">Пароль</label>
                    <div class="password-container">
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-input"
                               placeholder="Введите ваш пароль"
                               required>
                        <button type="button" class="toggle-password" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="remember-me">
                    <input type="checkbox"
                           id="remember"
                           name="remember"
                        <?php echo $remember ? 'checked' : ''; ?>>
                    <label for="remember">Запомнить меня</label>
                </div>

                <button type="submit" class="auth-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Войти в систему
                </button>
            </form>


            <div class="auth-footer">
                <p>Нет учетной записи? <a href="/fuck.jpg">До свидания!</a></p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Переключение видимости пароля
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Меняем иконку
                const icon = this.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        }

        // Автофокус на поле логина при загрузке
        const loginInput = document.getElementById('login');
        if (loginInput && !loginInput.value) {
            loginInput.focus();
        }

        // Валидация формы перед отправкой
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                const login = document.getElementById('login').value.trim();
                const password = document.getElementById('password').value;


                if (!login || !password) {
                    e.preventDefault();
                    alert('Пожалуйста, заполните все поля');
                    return false;
                }

                // Показываем индикатор загрузки
                const submitBtn = this.querySelector('.auth-btn');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Вход...';
                submitBtn.disabled = true;

                // Через 5 секунд восстанавливаем кнопку (на случай ошибки)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);

                return true;
            });
        }

    });
</script>
</body>
</html>