<?PHP
require_once "includes/db.php";
require_once "includes/auth_check.php";

if(!canAccess(CORE::ROLE_USERCONTROL)){
    echo "Доступ запрещен!";
    die;
}

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - Система управления</title>
    <link rel="stylesheet" href="/css/fontawesome-free-6.7.2-web/css/all.min.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
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
            padding: 20px;
        }

        .container {
            max-width: 1400px;
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

        .header-actions {
            display: flex;
            gap: 16px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        .content {
            padding: 32px;
        }

        .filters-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
            border: 1px solid #e9ecef;
        }

        .filters-section h2 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
            font-size: 14px;
        }

        .filter-select, .filter-input {
            padding: 12px 16px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 15px;
            transition: border 0.3s ease;
        }

        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .search-container {
            display: flex;
            gap: 12px;
        }

        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 15px;
            transition: border 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .users-header h2 {
            font-size: 22px;
            color: #2c3e50;
        }

        .users-count {
            background: #ecf0f1;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .users-table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .users-table thead {
            background: #f8f9fa;
        }

        .users-table th {
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .users-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
            vertical-align: middle;
        }

        .users-table tr:hover {
            background: #f8f9fa;
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        .user-avatar2 {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 16px;
        }

        .user-info2 {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-details2 {
            display: flex;
            flex-direction: column;
        }

        .user-name2 {
            font-weight: 600;
            color: #2c3e50;
        }

        .user-login2 {
            font-size: 13px;
            color: #6c757d;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-blocked {
            background: #f8d7da;
            color: #721c24;
        }

        .roles-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #e9ecef;
            color: #495057;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .role-admin {
            background: #d4edda;
            color: #155724;
        }

        .role-editor {
            background: #cce5ff;
            color: #004085;
        }

        .role-viewer {
            background: #fff3cd;
            color: #856404;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ced4da;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 12px;
            color: #495057;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 40px;
            gap: 8px;
        }

        .page-btn {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px solid #dee2e6;
            color: #495057;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .page-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .page-btn:hover:not(.active) {
            background: #f8f9fa;
        }

        /* Модальное окно */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal {
            transform: translateY(0);
        }

        .modal-header {
            padding: 24px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #6c757d;
            cursor: pointer;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: #f8f9fa;
            color: #2c3e50;
        }

        .modal-body {
            padding: 24px;
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

        .checkbox-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .checkbox-item label {
            margin: 0;
            font-weight: normal;
        }

        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .status-message {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            font-weight: 500;
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

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .users-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .action-buttons {
                flex-wrap: wrap;
            }
        }
    </style>
    <link href="/css/main.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <div class="header">
        <h1><?PHP include "includes/menu.php"?><i class="fas fa-users-cog"></i> Управление пользователями</h1>

        <div class="header-actions">
            <?PHP include  "includes/avatar_block.php"; ?>
            <button class="btn btn-primary" id="addUserBtn">
                <i class="fas fa-user-plus"></i> Добавить пользователя
            </button>
        </div>
    </div>

    <div class="content">
        <?php
        // Подключение к базе данных с использованием Medoo
        use Medoo\Medoo;

        // Конфигурация базы данных
        $database =CORE::$db;
        // Обработка POST запросов
        $message = '';
        $message_type = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'add_user':
                        // Добавление нового пользователя
                        $login = $_POST['login'] ?? '';
                        $username = $_POST['username'] ?? '';
                        $password = $_POST['password'] ?? '';
                        $is_blocked = isset($_POST['is_blocked']) ? 1 : 0;
                        $roles = $_POST['roles'] ?? [];

                        if (!empty($login) && !empty($username) && !empty($password)) {
                            // Проверка, существует ли пользователь с таким логином
                            $existing = $database->get('user', 'id', ['login' => $login]);

                            if (!$existing) {
                                // Хеширование пароля
                                $pwd_hash = password_hash($password, PASSWORD_DEFAULT);

                                // Вставка пользователя
                                $database->insert('user', [
                                    'login' => $login,
                                    'username' => $username,
                                    'pwd_hash' => $pwd_hash,
                                    'is_blocked' => $is_blocked
                                ]);

                                $user_id = $database->id();

                                // Добавление ролей
                                if (!empty($roles)) {
                                    foreach ($roles as $role) {
                                        $database->insert('role', [
                                            'user_id' => $user_id,
                                            'name' => $role
                                        ]);
                                    }
                                }

                                $message = 'Пользователь успешно добавлен';
                                $message_type = 'success';
                            } else {
                                $message = 'Пользователь с таким логином уже существует';
                                $message_type = 'error';
                            }
                        } else {
                            $message = 'Заполните все обязательные поля';
                            $message_type = 'error';
                        }
                        break;

                    case 'edit_user':
                        // Редактирование пользователя
                        $user_id = $_POST['user_id'] ?? 0;
                        $username = $_POST['username'] ?? '';
                        $is_blocked = isset($_POST['is_blocked']) ? 1 : 0;
                        $roles = $_POST['roles'] ?? [];
                        $change_password = isset($_POST['change_password']);

                        if ($user_id > 0 && !empty($username)) {
                            $data = [
                                'username' => $username,
                                'is_blocked' => $is_blocked
                            ];

                            // Обновление пароля, если нужно
                            if ($change_password && !empty($_POST['password'])) {
                                $data['pwd_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                            }

                            $database->update('user', $data, ['id' => $user_id]);

                            // Удаление старых ролей и добавление новых
                            $database->delete('role', ['user_id' => $user_id]);

                            if (!empty($roles)) {
                                foreach ($roles as $role) {
                                    $database->insert('role', [
                                        'user_id' => $user_id,
                                        'name' => $role
                                    ]);
                                }
                            }

                            $message = 'Пользователь успешно обновлен';
                            $message_type = 'success';
                        } else {
                            $message = 'Неверные данные пользователя';
                            $message_type = 'error';
                        }
                        break;

                    case 'toggle_block':
                        // Блокировка/разблокировка пользователя
                        $user_id = $_POST['user_id'] ?? 0;

                        if ($user_id > 0) {
                            $current = $database->get('user', 'is_blocked', ['id' => $user_id]);
                            $database->update('user', ['is_blocked' => $current ? 0 : 1], ['id' => $user_id]);
                            $message = 'Статус пользователя изменен';
                            $message_type = 'success';
                        }
                        break;

                    case 'delete_user':
                        // Удаление пользователя
                        $user_id = $_POST['user_id'] ?? 0;

                        if ($user_id > 0) {
                            // Удаление ролей пользователя
                            $database->delete('role', ['user_id' => $user_id]);
                            // Удаление пользователя
                            $database->delete('user', ['id' => $user_id]);
                            $message = 'Пользователь удален';
                            $message_type = 'success';
                        }
                        break;
                }
            }
        }

        // Отображение сообщения
        if (!empty($message)) {
            echo '<div class="status-message status-' . $message_type . '">' . $message . '</div>';
        }
        ?>

        <div class="filters-section">
            <h2><i class="fas fa-filter"></i> Фильтры пользователей</h2>
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="statusFilter"><i class="fas fa-user-check"></i> Статус</label>
                    <select id="statusFilter" class="filter-select">
                        <option value="all">Все пользователи</option>
                        <option value="active">Только активные</option>
                        <option value="blocked">Только заблокированные</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="roleFilter"><i class="fas fa-user-tag"></i> Роль</label>
                    <select id="roleFilter" class="filter-select">
                        <option value="all">Все роли</option>
                        <option value="<?= CORE::ROLE_USERCONTROL ?>">Доступ к управлению пользователями</option>
                        <option value="<?= CORE::ROLE_DEPARTMENTCONTROL ?>">Доступ к управлению подразделениями</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="searchInput"><i class="fas fa-search"></i> Поиск</label>
                    <div class="search-container">
                        <input type="text" id="searchInput" class="search-input" placeholder="Поиск по логину или имени...">
                        <button class="btn btn-primary" id="searchBtn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="users-header">
            <h2>Список пользователей</h2>
            <div class="users-count">
                <?php
                // Получение количества пользователей
                $total_users = $database->count('user');
                echo "Всего: " . $total_users . " пользователей";
                ?>
            </div>
        </div>

        <div class="users-table-container">
            <table class="users-table">
                <thead>
                <tr>
                    <th>Пользователь</th>
                    <th>Статус</th>
                    <th>Роли</th>
                    <th>Дата регистрации</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                <?php
                // Получение всех пользователей с их ролями
                $users = $database->select('user', '*');

                foreach ($users as $user) {
                    // Получение ролей пользователя
                    $roles = $database->select('role', 'name', ['user_id' => $user['id']]);

                    // Определение цвета аватара по первой букве имени
                    $first_letter = mb_strtoupper(mb_substr($user['username'], 0, 1));
                    $avatar_color = '#' . substr(md5($user['username']), 0, 6);

                    // Форматирование даты
                    $created_date = date('d.m.Y', strtotime($user['created_at'] ?? 'now'));
                    ?>
                    <tr data-user-id="<?php echo $user['id']; ?>">
                        <td>
                            <div class="user-info2">
                                <div class="user-avatar2" style="background-color: <?php echo $avatar_color; ?>">
                                    <?php echo $first_letter; ?>
                                </div>
                                <div class="user-details2">
                                    <div class="user-name2"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="user-login2">@<?php echo htmlspecialchars($user['login']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                                    <span class="status-badge <?php echo $user['is_blocked'] ? 'status-blocked' : 'status-active'; ?>">
                                        <i class="fas <?php echo $user['is_blocked'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                        <?php echo $user['is_blocked'] ? 'Заблокирован' : 'Активен'; ?>
                                    </span>
                        </td>
                        <td>
                            <div class="roles-container">
                                <?php
                                if (!empty($roles)) {
                                    foreach ($roles as $role) {
                                        $role_class = 'role-' . strtolower($role);
                                        echo '<span class="role-badge ' . $role_class . '">';
                                        echo '<i class="fas fa-user-tag"></i>';
                                        echo htmlspecialchars($role);
                                        echo '</span>';
                                    }
                                } else {
                                    echo '<span style="color: #6c757d; font-style: italic;">Нет ролей</span>';
                                }
                                ?>
                            </div>
                        </td>
                        <td><?php echo $created_date; ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-warning btn-sm edit-user-btn"
                                        data-user-id="<?php echo $user['id']; ?>"
                                        data-login="<?php echo htmlspecialchars($user['login']); ?>"
                                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                        data-is-blocked="<?php echo $user['is_blocked']; ?>"
                                        data-roles="<?php echo htmlspecialchars(json_encode($roles)); ?>">
                                    <i class="fas fa-edit"></i> Редактировать
                                </button>
                                <button class="btn btn-<?php echo $user['is_blocked'] ? 'success' : 'danger'; ?> btn-sm toggle-block-btn"
                                        data-user-id="<?php echo $user['id']; ?>"
                                        data-is-blocked="<?php echo $user['is_blocked']; ?>">
                                    <i class="fas <?php echo $user['is_blocked'] ? 'fa-unlock' : 'fa-lock'; ?>"></i>
                                    <?php echo $user['is_blocked'] ? 'Разблокировать' : 'Блокировать'; ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php
                }

                if (empty($users)) {
                    echo '<tr><td colspan="5"><div class="empty-state">';
                    echo '<i class="fas fa-users-slash"></i>';
                    echo '<h3>Пользователи не найдены</h3>';
                    echo '<p>Добавьте первого пользователя</p>';
                    echo '</div></td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>


    </div>
</div>

<!-- Модальное окно добавления/редактирования пользователя -->
<div class="modal-overlay" id="userModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-edit"></i> <span id="modalTitle">Добавить пользователя</span></h3>
            <button class="modal-close" id="modalClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="userForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add_user">
            <input type="hidden" name="user_id" id="formUserId" value="">

            <div class="modal-body">
                <div class="form-group">
                    <label for="modalLogin" class="required"><i class="fas fa-user"></i> Логин</label>
                    <input type="text" id="modalLogin" name="login" class="form-control" required>
                    <div class="form-help">Уникальный идентификатор для входа в систему</div>
                </div>

                <div class="form-group">
                    <label for="modalUsername" class="required"><i class="fas fa-user-circle"></i> Имя пользователя</label>
                    <input type="text" id="modalUsername" name="username" class="form-control" required>
                    <div class="form-help">Отображаемое имя пользователя</div>
                </div>

                <div class="form-group">
                    <label for="modalPassword" id="passwordLabel" class="required"><i class="fas fa-lock"></i> Пароль</label>
                    <input type="password" id="modalPassword" name="password" class="form-control">
                    <div class="form-help" id="passwordHelp">Минимум 8 символов</div>
                </div>

                <div class="form-group" id="changePasswordGroup" style="display: none;">
                    <div class="checkbox-item">
                        <input type="checkbox" id="changePassword" name="change_password">
                        <label for="changePassword">Изменить пароль</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="modalIsBlocked" name="is_blocked" value="1">
                        <label for="modalIsBlocked">Заблокировать пользователя</label>
                    </div>
                    <div class="form-help">Заблокированные пользователи не могут войти в систему</div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-tag"></i> Роли пользователя</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="roleAdmin" name="roles[]" value="<?= CORE::ROLE_USERCONTROL ?>">
                            <label for="roleAdmin">Доступ к управлению пользователями</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="roleDepartmentControl" name="roles[]" value="<?= CORE::ROLE_DEPARTMENTCONTROL ?>">
                            <label for="roleDepartmentControl">Доступ к управлению подразделениями</label>
                        </div>
                    </div>
                    <div class="form-help">Выберите одну или несколько ролей для пользователя</div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="modalCancel">Отмена</button>
                <button type="submit" class="btn btn-primary" id="modalSubmit">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно подтверждения удаления -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Подтверждение</h3>
            <button class="modal-close" id="confirmModalClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage">Вы уверены, что хотите удалить этого пользователя?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="confirmCancel">Отмена</button>
            <button type="button" class="btn btn-danger" id="confirmDelete">Удалить</button>
        </div>
    </div>
</div>

<script src="/js/main.js"></script>
<script>
    // DOM элементы
    const elements = {
        addUserBtn: document.getElementById('addUserBtn'),
        userModal: document.getElementById('userModal'),
        confirmModal: document.getElementById('confirmModal'),
        modalClose: document.getElementById('modalClose'),
        confirmModalClose: document.getElementById('confirmModalClose'),
        modalCancel: document.getElementById('modalCancel'),
        confirmCancel: document.getElementById('confirmCancel'),
        confirmDelete: document.getElementById('confirmDelete'),
        userForm: document.getElementById('userForm'),
        modalTitle: document.getElementById('modalTitle'),
        formAction: document.getElementById('formAction'),
        formUserId: document.getElementById('formUserId'),
        modalLogin: document.getElementById('modalLogin'),
        modalUsername: document.getElementById('modalUsername'),
        modalPassword: document.getElementById('modalPassword'),
        passwordLabel: document.getElementById('passwordLabel'),
        passwordHelp: document.getElementById('passwordHelp'),
        changePasswordGroup: document.getElementById('changePasswordGroup'),
        changePassword: document.getElementById('changePassword'),
        modalIsBlocked: document.getElementById('modalIsBlocked'),
        roleAdmin: document.getElementById('roleAdmin'),
        roleDepartmentControl: document.getElementById('roleDepartmentControl'),
        confirmMessage: document.getElementById('confirmMessage'),
        searchInput: document.getElementById('searchInput'),
        searchBtn: document.getElementById('searchBtn'),
        statusFilter: document.getElementById('statusFilter'),
        roleFilter: document.getElementById('roleFilter')
    };

    // Текущий пользователь для удаления
    let currentDeleteUserId = null;

    // Открытие модального окна для добавления пользователя
    elements.addUserBtn.addEventListener('click', function() {
        openUserModal('add');
    });

    // Открытие модального окна для редактирования пользователя
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-user-btn')) {
            const btn = e.target.closest('.edit-user-btn');
            openUserModal('edit', {
                id: btn.dataset.userId,
                login: btn.dataset.login,
                username: btn.dataset.username,
                is_blocked: btn.dataset.isBlocked === '1',
                roles: JSON.parse(btn.dataset.roles || '[]')
            });
        }

        if (e.target.closest('.toggle-block-btn')) {
            const btn = e.target.closest('.toggle-block-btn');
            toggleUserBlock(btn.dataset.userId);
        }

        if (e.target.closest('.delete-user-btn')) {
            const btn = e.target.closest('.delete-user-btn');
            confirmDeleteUser(btn.dataset.userId, btn.dataset.username);
        }
    });

    // Закрытие модальных окон
    elements.modalClose.addEventListener('click', closeUserModal);
    elements.confirmModalClose.addEventListener('click', closeConfirmModal);
    elements.modalCancel.addEventListener('click', closeUserModal);
    elements.confirmCancel.addEventListener('click', closeConfirmModal);

    // Обработка отправки формы пользователя
    elements.userForm.addEventListener('submit', function(e) {
        // Валидация
        if (elements.formAction.value === 'add_user') {
            if (!elements.modalPassword.value) {
                e.preventDefault();
                alert('Введите пароль для нового пользователя');
                return;
            }
        }

        // Если редактирование и пароль не меняется, удаляем поле пароля
        if (elements.formAction.value === 'edit_user' && elements.changePassword && !elements.changePassword.checked) {
            const passwordInput = elements.userForm.querySelector('input[name="password"]');
            if (passwordInput) {
                passwordInput.remove();
            }
        }
    });

    // Подтверждение удаления
    elements.confirmDelete.addEventListener('click', function() {
        if (currentDeleteUserId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_user';

            const userIdInput = document.createElement('input');
            userIdInput.type = 'hidden';
            userIdInput.name = 'user_id';
            userIdInput.value = currentDeleteUserId;

            form.appendChild(actionInput);
            form.appendChild(userIdInput);
            document.body.appendChild(form);
            form.submit();
        }
    });

    // Поиск пользователей
    elements.searchBtn.addEventListener('click', filterUsers);
    elements.searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            filterUsers();
        }
    });

    // Фильтрация по статусу и роли
    elements.statusFilter.addEventListener('change', filterUsers);
    elements.roleFilter.addEventListener('change', filterUsers);

    // Функции
    function openUserModal(mode, userData = null) {
        // Сброс формы
        elements.userForm.reset();
        elements.changePasswordGroup.style.display = 'none';
        elements.changePassword.checked = false;

        if (mode === 'add') {
            elements.modalTitle.textContent = 'Добавить пользователя';
            elements.formAction.value = 'add_user';
            elements.formUserId.value = '';
            elements.modalLogin.disabled = false;
            elements.modalLogin.required = true;
            elements.modalPassword.required = true;
            elements.passwordLabel.classList.add('required');
            elements.passwordHelp.textContent = 'Минимум 8 символов';

            // Сброс чекбоксов ролей
            elements.roleAdmin.checked = false;
            elements.roleDepartmentControl.checked = false;
            elements.modalIsBlocked.checked = false;
        } else if (mode === 'edit' && userData) {
            elements.modalTitle.textContent = 'Редактировать пользователя';
            elements.formAction.value = 'edit_user';
            elements.formUserId.value = userData.id;
            elements.modalLogin.value = userData.login;
            elements.modalLogin.disabled = true;
            elements.modalUsername.value = userData.username;
            elements.modalPassword.required = false;
            elements.passwordLabel.classList.remove('required');
            elements.passwordHelp.textContent = 'Оставьте пустым, чтобы не менять пароль';
            elements.changePasswordGroup.style.display = 'block';

            // Установка чекбоксов ролей
            elements.roleAdmin.checked = userData.roles.includes('<?= CORE::ROLE_USERCONTROL ?>');
            elements.roleDepartmentControl.checked = userData.roles.includes('<?= CORE::ROLE_DEPARTMENTCONTROL ?>');
            elements.modalIsBlocked.checked = userData.is_blocked;
        }

        elements.userModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeUserModal() {
        elements.userModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    function closeConfirmModal() {
        elements.confirmModal.classList.remove('active');
        document.body.style.overflow = 'auto';
        currentDeleteUserId = null;
    }

    function toggleUserBlock(userId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'toggle_block';

        const userIdInput = document.createElement('input');
        userIdInput.type = 'hidden';
        userIdInput.name = 'user_id';
        userIdInput.value = userId;

        form.appendChild(actionInput);
        form.appendChild(userIdInput);
        document.body.appendChild(form);
        form.submit();
    }

    function confirmDeleteUser(userId, username) {
        currentDeleteUserId = userId;
        elements.confirmMessage.textContent = `Вы уверены, что хотите удалить пользователя "${username}"? Это действие нельзя отменить.`;
        elements.confirmModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function filterUsers() {
        const searchTerm = elements.searchInput.value.toLowerCase();
        const statusFilter = elements.statusFilter.value;
        const roleFilter = elements.roleFilter.value;

        const rows = document.querySelectorAll('.users-table tbody tr');

        rows.forEach(row => {
            if (row.querySelector('.empty-state')) return;

            const userName = row.querySelector('.user-name2').textContent.toLowerCase();
            const userLogin = row.querySelector('.user-login2').textContent.toLowerCase();
            const userStatus = row.querySelector('.status-badge').textContent.toLowerCase();
            const userRoles = row.querySelector('.roles-container').textContent.toLowerCase();

            let visible = true;

            // Поиск
            if (searchTerm) {
                visible = userName.includes(searchTerm) || userLogin.includes(searchTerm);
            }

            // Фильтр по статусу
            if (visible && statusFilter !== 'all') {
                if (statusFilter === 'active') {
                    visible = !userStatus.includes('заблокирован');
                } else if (statusFilter === 'blocked') {
                    visible = userStatus.includes('заблокирован');
                }
            }

            // Фильтр по роли
            if (visible && roleFilter !== 'all') {
                if (roleFilter === '<?= CORE::ROLE_USERCONTROL ?>') {
                    visible = userRoles.includes('<?= CORE::ROLE_USERCONTROL ?>');
                }
                else if (roleFilter === '<?= CORE::ROLE_DEPARTMENTCONTROL ?>') {
                    visible = userRoles.includes('<?= CORE::ROLE_DEPARTMENTCONTROL ?>');
                }
                // } else if (roleFilter === 'viewer') {
                //     visible = userRoles.includes('наблюдатель');
                // }
            }

            row.style.display = visible ? '' : 'none';
        });

        // Обновление счетчика
        const visibleCount = Array.from(rows).filter(row => row.style.display !== 'none').length;
        document.querySelector('.users-count').textContent = `Показано: ${visibleCount} из ${rows.length} пользователей`;
    }

    // Инициализация фильтров при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        // Инициализация tooltips или других элементов при необходимости
    });
</script>
</body>
</html>