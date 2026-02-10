<?php
// Подключение к базе данных через Medoo
require_once 'vendor/autoload.php';
include "includes/db.php";
require_once "includes/auth_check.php";
use Medoo\Medoo;



// Проверка авторизации
$isLoggedIn = false;
$currentUser = null;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $currentUser = CORE::$db->get('user', '*', ['id' => $userId]);

    if ($currentUser) {
        $isLoggedIn = true;
    }
}

// Если пользователь не авторизован - показываем ошибку
if (!$isLoggedIn) {
    die('Вы не авторизованы. Пожалуйста, войдите в систему.');
}

// Обработка POST запросов
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_profile':
            // Обновление имени пользователя
            $newUsername = trim($_POST['username'] ?? '');

            if (empty($newUsername)) {
                $message = 'Имя пользователя не может быть пустым';
                $messageType = 'error';
                break;
            }

            // Проверяем, не занято ли имя другим пользователем
            $existingUser = CORE::$db->get('user', 'id', [
                'username' => $newUsername,
                'id[!]' => $currentUser['id']
            ]);

            if ($existingUser) {
                $message = 'Это имя пользователя уже занято';
                $messageType = 'error';
                break;
            }

            CORE::$db->update('user', [
                'username' => $newUsername
            ], ['id' => $currentUser['id']]);

            $currentUser['username'] = $newUsername;
            $message = 'Имя пользователя успешно обновлено';
            $messageType = 'success';
            break;

        case 'change_password':
            // Смена пароля
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $message = 'Заполните все поля пароля';
                $messageType = 'error';
                break;
            }

            // Проверка текущего пароля
            if (!password_verify($currentPassword, $currentUser['pwd_hash'])) {
                $message = 'Текущий пароль указан неверно';
                $messageType = 'error';
                break;
            }

            // Проверка совпадения новых паролей
            if ($newPassword !== $confirmPassword) {
                $message = 'Новые пароли не совпадают';
                $messageType = 'error';
                break;
            }

            // Проверка сложности пароля (минимум 8 символов)
            if (strlen($newPassword) < 8) {
                $message = 'Пароль должен содержать минимум 8 символов';
                $messageType = 'error';
                break;
            }

            // Хеширование и сохранение нового пароля
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            CORE::$db->update('user', [
                'pwd_hash' => $newPasswordHash
            ], ['id' => $currentUser['id']]);

            $currentUser['pwd_hash'] = $newPasswordHash;
            $message = 'Пароль успешно изменен';
            $messageType = 'success';
            break;

    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой профиль - Система управления</title>

    <link rel="stylesheet" href="/css/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="/css/Inter-4.1/web/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/main.css" >
    <link rel="icon" href="favicon.ico" type="image/x-icon">
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
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(90deg, #2c3e50 0%, #4a6491 100%);
            color: white;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1 i {
            color: #3498db;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .content {
            padding: 32px;
        }

        .section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e9ecef;
        }

        .section h2 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 13px;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 500;
            padding: 8px 0;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-blocked {
            background: #f8d7da;
            color: #721c24;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }

        .form-group label.required::after {
            content: " *";
            color: #e74c3c;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-help {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
        }

        .password-strength {
            margin-top: 10px;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            position: relative;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak {
            background: #e74c3c;
            width: 25%;
        }

        .strength-fair {
            background: #f39c12;
            width: 50%;
        }

        .strength-good {
            background: #3498db;
            width: 75%;
        }

        .strength-strong {
            background: #27ae60;
            width: 100%;
        }

        .password-strength-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
            text-align: right;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #ecf0f1;
            color: #2c3e50;
        }

        .btn-secondary:hover {
            background: #d5dbdb;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #219653;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .status-message {
            padding: 15px;
            border-radius: 8px;
            margin: 0 0 20px 0;
            text-align: center;
            font-weight: 500;
            display: none;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .token-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .token-display {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 14px;
            word-break: break-all;
            color: #495057;
            position: relative;
        }

        .copy-token-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            cursor: pointer;
            color: #495057;
            transition: all 0.2s ease;
        }

        .copy-token-btn:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .copy-success {
            background: #d4edda;
            color: #155724;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .user-info-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 20px;
            }

            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1><?PHP include "includes/menu.php"?><i class="fas fa-user-circle"></i> Мой профиль</h1>
            <div style="margin-top: 10px; font-size: 14px; opacity: 0.9;">
                Управление вашими персональными данными
            </div>
        </div>
            <?PHP include  "includes/avatar_block.php"; ?>
    </div>

    <div class="content">
        <?php if ($message): ?>
            <div class="status-message status-<?php echo $messageType; ?>" id="statusMessage">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Информация о пользователе -->
        <div class="section">
            <h2><i class="fas fa-id-card"></i> Общая информация</h2>

            <div class="user-info-grid">
                <div class="info-item">
                    <div class="info-label">Логин</div>
                    <div class="info-value"><?php echo htmlspecialchars($currentUser['login']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Имя пользователя</div>
                    <div class="info-value"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Дата регистрации</div>
                    <div class="info-value">
                        <?php echo date('d.m.Y H:i', strtotime($currentUser['create_at'])); ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Статус</div>
                    <div class="info-value">
                            <span class="status-badge <?php echo $currentUser['is_blocked'] ? 'status-blocked' : 'status-active'; ?>">
                                <i class="fas <?php echo $currentUser['is_blocked'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                <?php echo $currentUser['is_blocked'] ? 'Заблокирован' : 'Активен'; ?>
                            </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Форма изменения имени -->
        <div class="section">
            <h2><i class="fas fa-edit"></i> Изменить имя пользователя</h2>

            <form id="usernameForm" method="POST">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label for="username" class="required"><i class="fas fa-user"></i> Имя пользователя</label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                    <div class="form-help">Имя, которое будет отображаться в системе</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Обновить имя
                    </button>
                </div>
            </form>
        </div>

        <!-- Форма изменения пароля -->
        <div class="section">
            <h2><i class="fas fa-lock"></i> Изменить пароль</h2>

            <form id="passwordForm" method="POST">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label for="current_password" class="required"><i class="fas fa-key"></i> Текущий пароль</label>
                    <div class="password-toggle">
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                        <button type="button" class="password-toggle-btn" data-target="current_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password" class="required"><i class="fas fa-key"></i> Новый пароль</label>
                    <div class="password-toggle">
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                        <button type="button" class="password-toggle-btn" data-target="new_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                    <div class="password-strength-text" id="passwordStrengthText"></div>
                    <div class="form-help">Минимум 8 символов. Используйте буквы, цифры и специальные символы</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="required"><i class="fas fa-key"></i> Подтвердите новый пароль</label>
                    <div class="password-toggle">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        <button type="button" class="password-toggle-btn" data-target="confirm_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-help">Повторите новый пароль для подтверждения</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-lock"></i> Изменить пароль
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
<script src="/js/main.js"></script>
<script>
    // DOM элементы
    const elements = {
        statusMessage: document.getElementById('statusMessage'),
        usernameForm: document.getElementById('usernameForm'),
        passwordForm: document.getElementById('passwordForm'),
        currentPassword: document.getElementById('current_password'),
        newPassword: document.getElementById('new_password'),
        confirmPassword: document.getElementById('confirm_password'),
        passwordStrengthBar: document.getElementById('passwordStrengthBar'),
        passwordStrengthText: document.getElementById('passwordStrengthText'),
        copyTokenBtn: document.getElementById('copyTokenBtn')
    };

    // Функция для проверки сложности пароля
    function checkPasswordStrength(password) {
        let strength = 0;
        let text = '';
        let className = '';

        if (!password) {
            elements.passwordStrengthBar.className = 'password-strength-bar';
            elements.passwordStrengthBar.style.width = '0%';
            elements.passwordStrengthText.textContent = '';
            return;
        }

        // Проверка длины
        if (password.length >= 8) strength += 1;
        if (password.length >= 12) strength += 1;

        // Проверка наличия символов разного типа
        if (/[a-z]/.test(password)) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 1;

        // Определение уровня сложности
        if (strength <= 2) {
            text = 'Слабый';
            className = 'strength-weak';
        } else if (strength <= 4) {
            text = 'Средний';
            className = 'strength-fair';
        } else if (strength <= 5) {
            text = 'Хороший';
            className = 'strength-good';
        } else {
            text = 'Надежный';
            className = 'strength-strong';
        }

        elements.passwordStrengthBar.className = `password-strength-bar ${className}`;
        elements.passwordStrengthText.textContent = text;
    }

    // Функция для проверки совпадения паролей
    function checkPasswordMatch() {
        const password = elements.newPassword.value;
        const confirm = elements.confirmPassword.value;

        if (!confirm) return;

        if (password !== confirm) {
            elements.confirmPassword.style.borderColor = '#e74c3c';
            elements.confirmPassword.style.boxShadow = '0 0 0 3px rgba(231, 76, 60, 0.1)';
        } else {
            elements.confirmPassword.style.borderColor = '#27ae60';
            elements.confirmPassword.style.boxShadow = '0 0 0 3px rgba(39, 174, 96, 0.1)';
        }
    }

    // Функция для показа/скрытия пароля
    function setupPasswordToggle() {
        document.querySelectorAll('.password-toggle-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            });
        });
    }

    // Копирование токена в буфер обмена
    function setupTokenCopy() {
        if (!elements.copyTokenBtn) return;

        elements.copyTokenBtn.addEventListener('click', function() {
            const tokenElement = this.parentElement;
            const tokenText = tokenElement.textContent.trim();

            // Удаляем текст кнопки из токена
            const cleanToken = tokenText.replace('Копировать', '').trim();

            // Копируем в буфер обмена
            navigator.clipboard.writeText(cleanToken).then(() => {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Скопировано!';
                this.classList.add('copy-success');

                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('copy-success');
                }, 2000);
            }).catch(err => {
                alert('Не удалось скопировать токен. Пожалуйста, скопируйте его вручную.');
            });
        });
    }

    // Валидация формы изменения имени
    function setupUsernameValidation() {
        elements.usernameForm.addEventListener('submit', function(e) {
            const username = this.querySelector('#username').value.trim();

            if (!username) {
                e.preventDefault();
                alert('Введите имя пользователя');
                return;
            }

            if (username.length < 2) {
                e.preventDefault();
                alert('Имя пользователя должно содержать минимум 2 символа');
                return;
            }
        });
    }

    // Валидация формы изменения пароля
    function setupPasswordValidation() {
        elements.passwordForm.addEventListener('submit', function(e) {
            const currentPass = elements.currentPassword.value;
            const newPass = elements.newPassword.value;
            const confirmPass = elements.confirmPassword.value;

            if (!currentPass || !newPass || !confirmPass) {
                e.preventDefault();
                alert('Заполните все поля пароля');
                return;
            }

            if (newPass.length < 8) {
                e.preventDefault();
                alert('Новый пароль должен содержать минимум 8 символов');
                return;
            }

            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('Новые пароли не совпадают');
                return;
            }
        });
    }

    // Показать сообщение об успехе/ошибке
    function showStatusMessage() {
        if (elements.statusMessage && elements.statusMessage.textContent.trim()) {
            elements.statusMessage.style.display = 'block';

            // Автоматически скрыть через 5 секунд
            setTimeout(() => {
                elements.statusMessage.style.display = 'none';
            }, 5000);
        }
    }

    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        // Настройка переключения видимости пароля
        setupPasswordToggle();

        // Настройка копирования токена
        setupTokenCopy();

        // Настройка валидации форм
        setupUsernameValidation();
        setupPasswordValidation();

        // Показать сообщение (если есть)
        showStatusMessage();

        // Обработчики для проверки сложности пароля
        if (elements.newPassword) {
            elements.newPassword.addEventListener('input', function() {
                checkPasswordStrength(this.value);
                checkPasswordMatch();
            });
        }

        // Обработчик для проверки совпадения паролей
        if (elements.confirmPassword) {
            elements.confirmPassword.addEventListener('input', checkPasswordMatch);
        }

        // Фокус на поле текущего пароля при открытии
        if (elements.currentPassword) {
            elements.currentPassword.focus();
        }
    });
</script>
</body>
</html>