<?php

use Medoo\Medoo;

include "includes/db.php";
require_once "includes/auth_check.php";

// Проверка параметра show_department для iframe
$show_dept_id = isset($_GET['show_department']) ? (int)$_GET['show_department'] : 0;
$initial_dept = null;


$current_user = htmlspecialchars($user['username']);

// department_additional.php
// Предполагается, что CORE::$db инициализирован (Medoo)

// Конфигурация вкладок (для генерации форм)
$tabs_config = [
    'internet' => [
        'title' => 'Интернет',
        'fields' => [
            'speed' => ['type' => 'textselect2', 'label' => 'Тариф (Мбит/с)', 'placeholder' => 'Введите или выберите скорость интернета...'],
            'provider' => ['type' => 'textselect2', 'label' => 'Провайдер', 'placeholder' => 'Введите своё или выберите из списка...', 'autocomplete' => true],
            'notes' => ['type' => 'textarea', 'label' => 'Примечание', 'placeholder' => 'Дополнительная информация']
        ]
    ],
    'telephony' => [
        'title' => 'Телефония',
        'fields' => [
            'ip_ats' => ['type' => 'text', 'label' => 'IP АТС', 'placeholder' => 'Введите ip ATC'],
            'fxo_description' => ['type' => 'textarea', 'label' => 'Описание FXO', 'placeholder' => 'Параметры FXO'],
            'notes' => ['type' => 'textarea', 'label' => 'Примечание', 'placeholder' => 'Дополнительно']
        ]
    ],
    'ved_dep' => [
        'title' => 'Ведомственная сеть',
        'fields' => [
            'coordinator_ip' => ['type' => 'text', 'label' => 'IP координатора', 'placeholder' => '10.0.0.1'],
            'subnet' => ['type' => 'text', 'label' => 'Подсеть', 'placeholder' => 'Введите например 192.168.1.0/24 ...'],
            'notes' => ['type' => 'textarea', 'label' => 'Примечание', 'placeholder' => 'Примечание']
        ]
    ],
    'contacts' => [
        'title' => 'Контакты',
        'fields' => [
            'fio' => ['type' => 'text', 'label' => 'ФИО связиста', 'placeholder' => 'Иванов Иван'],
            'phone' => ['type' => 'text', 'label' => 'Номер телефона', 'placeholder' => '+7 (XXX) XXX-XX-XX'],
            'notes' => ['type' => 'textarea', 'label' => 'Примечание', 'placeholder' => 'Примечание']
        ]
    ]
];

// Получаем список всех провайдеров для автодополнения
$providers_list = [];
//if (CORE::$db->has('department_fields')) {
    $providers_list = CORE::$db->select('department_fields', ['field_value','tab_key','field_name'], [
        'field_value[!]' => null
    ]);

//    $providers_list = array_column($providers_list, 'field_value');
//}

// Обработка AJAX-запросов
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    // Получение данных для всех вкладок по department_id
    if ($action === 'get_tabs' && isset($_GET['dept_id'])) {
        $dept_id = (int)$_GET['dept_id'];
        $data = [];

        // Получаем все поля из таблицы department_fields
        $fields = CORE::$db->select('department_fields', '*', ['department_id' => $dept_id]);
        foreach ($fields as $row) {
            $tab = $row['tab_key'];
            $field = $row['field_name'];
            $data[$tab][$field] = $row['field_value'];
        }

        // Заметки
        $notes = CORE::$db->select('department_notes', ['id', 'note', 'created_by', 'created_at'], [
            'department_id' => $dept_id,
            'ORDER' => ['created_at' => 'DESC']
        ]);

        echo json_encode(['tabs' => $data, 'notes' => $notes]);
        exit;
    }

    // Сохранение данных вкладок (кроме заметок)
    if ($action === 'save_tabs' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $dept_id = (int)($_POST['department_id'] ?? 0);
        if (!$dept_id) {
            echo json_encode(['success' => false, 'error' => 'No department']);
            exit;
        }

        // Удаляем старые записи для этого департамента (кроме заметок)
        CORE::$db->delete('department_fields', ['department_id' => $dept_id]);

        $inserted = 0;
        // Проходим по каждой вкладке и каждому полю
        foreach ($tabs_config as $tab_key => $tab) {
            foreach (array_keys($tab['fields']) as $field_name) {
                $post_key = $tab_key . '_' . $field_name;
                if (isset($_POST[$post_key]) && $_POST[$post_key] !== '') {
                    $value = $_POST[$post_key];
                    CORE::$db->insert('department_fields', [
                        'department_id' => $dept_id,
                        'tab_key' => $tab_key,
                        'field_name' => $field_name,
                        'field_value' => $value
                    ]);
                    $inserted++;
                }
            }
        }

        echo json_encode(['success' => true, 'inserted' => $inserted]);
        exit;
    }

    // Добавление заметки
    if ($action === 'add_note' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $dept_id = (int)($_POST['department_id'] ?? 0);
        $note_text = trim($_POST['note'] ?? '');
        if ($dept_id && $note_text) {
            $created_by = $current_user;
            CORE::$db->insert('department_notes', [
                'department_id' => $dept_id,
                'note' => $note_text,
                'created_by' => $created_by,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $note_id = CORE::$db->id();
            $new_note = CORE::$db->get('department_notes', ['id', 'note', 'created_by', 'created_at'], ['id' => $note_id]);
            echo json_encode(['success' => true, 'note' => $new_note]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Empty note or department']);
        }
        exit;
    }

    // Удаление заметки
    if ($action === 'delete_note' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $note_id = (int)($_POST['note_id'] ?? 0);
        if ($note_id) {
            CORE::$db->delete('department_notes', ['id' => $note_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid note ID']);
        }
        exit;
    }

    // Поиск провайдеров для автодополнения
    if ($action === 'search_providers') {
        $term = $_GET['term'] ?? '';
        $tab_key = $_GET['tab_key'] ?? '';
        $field_name = $_GET['field_name'] ?? '';
        $providers = CORE::$db->select('department_fields', 'field_value', [
            'tab_key' => $tab_key,
            'field_name' => $field_name,
            'field_value[~]' => $term,
            'LIMIT' => 20
        ]);
        echo json_encode(array_values(array_unique($providers)),JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['error' => 'Invalid action']);
    exit;
}

// Получаем список подразделений
$departments = CORE::$db->select('department', ['id', 'name', 'parent_id', 'sort_id'], [
    'ORDER' => ['parent_id' => 'ASC', 'sort_id' => 'ASC']
]);

if ($show_dept_id) {
    // Ищем подразделение в загруженном списке
    foreach ($departments as $dept) {
        if ($dept['id'] == $show_dept_id) {
            $initial_dept = $dept;
            break;
        }
    }
    // Если подразделение не найдено – игнорируем параметр (покажем обычный вид)
    if (!$initial_dept) {
        $show_dept_id = 0;
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дополнительная информация подразделений</title>
    <link rel="stylesheet" href="/css/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="/css/Inter-4.1/web/inter.css" rel="stylesheet">
    <link href="/css/select2.min.css" rel="stylesheet">

    <link href="/css/main.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        .custom-clear{
            float: right;
            height: 38px;
            background: no-repeat;
            border: none;
            color: red;
            cursor: pointer;
}
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }
        .notification {
            padding: 12px 24px;
            border-radius: 4px;
            color: white;
            font-size: 14px;
            font-family: sans-serif;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            pointer-events: auto;
            animation: slideIn 0.3s ease forwards;
            cursor: default;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 250px;
            max-width: 350px;
        }
        .notification.success {
            background-color: #4caf50;
        }
        .notification.error {
            background-color: #f44336;
        }
        .notification .close-btn {
            margin-left: 16px;
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.7;
            padding: 0;
            line-height: 1;
        }
        .notification .close-btn:hover {
            opacity: 1;
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
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        .notification.hide {
            animation: slideOut 0.3s ease forwards;
        }

        body {
            height: 100vh;
            padding: 20px;
            box-sizing: border-box;
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .select2-container--default .select2-selection--single .select2-selection__clear{

            height: 40px;
        }
        /* Для одиночного выбора (single) */
        .select2-container .select2-selection--single {
            height: 40px; /* Нужная высота */
        }

        /* Чтобы текст по центру был */
        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 40px; /* Должен совпадать с высотой */
        }

        /* Поправить стрелочку, чтобы по центру была */
        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 40px; /* Должен совпадать с высотой */
        }

        /* Для множественного выбора (multiple) */
        .select2-container .select2-selection--multiple {
            min-height: 40px; /* Минимальная высота */
        }
        .container {
            height: 100%;
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        <?PHP

        if($show_dept_id):?>
        .tabs-header{
            background: #eaeaea;
        }
        .right-panel{
            background: #f9f9f9;
        }
        body{
            background: transparent;
            box-shadow: none;
        }
        .container{
            border: 2px solid #f1f1f1;
            box-shadow: none;
        }
        <?PHP
        endif;
 ?>
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
        .header h1 i { color: #3498db; }
        .header-actions .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ecf0f1;
            color: #2c3e50;
        }
        .header-actions .btn:hover {
            background: #d5dbdb;
            transform: translateY(-2px);
        }
        .main-panels {
            flex: 1;
            display: flex;
            min-height: 0;  /* необходимо для корректной работы скроллов внутри */
        }
        .left-panel {
            width: 320px;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .search-header {
            padding: 20px;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .search-header input {
            width: 100%;
            padding: 12px 16px 12px 40px;
            border: 1px solid #cbd5e1;
            border-radius: 30px;
            font-size: 15px;
            background: white url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%2364778b" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>') no-repeat 15px center;
            background-size: 16px;
            outline: none;
            transition: 0.2s;
        }
        .search-header input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.2);
        }
        .dept-list {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        .dept-item {
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            margin-bottom: 2px;
            transition: background 0.2s;
            user-select: none;
            font-size: 14px;
        }
        .dept-item:hover { background: #e2e8f0; }
        .dept-item.selected {
            background: #3498db;
            color: white;
            font-weight: 500;
        }
        .dept-item.child {
            margin-left: 24px;
            position: relative;
        }
        .dept-item.child::before {
            content: "└";
            position: absolute;
            left: -16px;
            color: #94a3b8;
        }
        .empty-search {
            color: #64748b;
            text-align: center;
            padding: 40px 0;
            font-style: italic;
        }
        .right-panel {
            flex: 1;
            background: white;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .selected-dept-header {
            padding: 12px 24px;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .selected-dept-header i { color: #3498db; }
        .tabs-header {
            display: flex;
            border-bottom: 2px solid #e2e8f0;
            background: white;
            padding: 0 20px;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 16px 24px;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        .tab-btn i { font-size: 16px; }
        .tab-btn.active {
            color: #3498db;
            border-bottom-color: #3498db;
        }
        .tab-btn .note-count {
            background: #ef4444;
            color: white;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 6px;
        }
        .tab-content {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            position: relative;
        }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        .placeholder-message {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: white;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 18px;
            gap: 16px;
            text-align: center;
        }
        .placeholder-message i { font-size: 64px; opacity: 0.5; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            transition: 0.2s;
            font-family: 'Inter', sans-serif;
            background: white;
        }
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }
        textarea.form-control { min-height: 80px; resize: vertical; }
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }
        .footer-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            border-top: 1px solid #e2e8f0;
            padding: 20px 24px;
            background: #f8fafc;
        }
        .save-button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .save-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .save-button:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            transform: none;
        }
        .notes-container {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        #tabContent{
            padding-bottom: 0px;
        }
        .notes-list {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 16px;
            padding-right: 8px;
        }
        .note-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            border-left: 4px solid #3498db;
            position: relative;
        }
        .note-item .note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .note-item .note-date {
            font-size: 12px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .note-item .note-author {
            font-size: 12px;
            font-weight: 600;
            color: #2c3e50;
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 12px;
        }
        .note-item .note-text {
            white-space: pre-wrap;
            line-height: 1.5;
        }
        .delete-note {
            color: #ef4444;
            cursor: pointer;
            opacity: 0.6;
            transition: 0.2s;
            background: none;
            border: none;
            font-size: 16px;
        }
        .delete-note:hover { opacity: 1; }
        .add-note-area {
            background: white;
            display: flex;
            gap: 10px;
            align-items: flex-start;
            position: sticky;
            bottom: 0;
            background: #f8fafc;
            padding: 16px 0 0;
            margin-top: 16px;
            border-top: 1px solid #e2e8f0;
        }
        .add-note-area textarea { flex: 1; }
        .add-note-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .add-note-btn:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        .hidden { display: none !important; }
    </style>
</head>
<body>
<div class="notification-container"></div>
<div class="container">
    <?php if (!$show_dept_id): ?>
    <div class="header">

        <h1><?PHP include "includes/menu.php"?><i class="fas fa-sitemap"></i> Дополнительная информация подразделений</h1>

        <div class="header-actions">
            <button class="btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Обновить</button>
        </div>
            <?PHP include  "includes/avatar_block.php"; ?>
    </div>

    <?php endif; ?>
    <div class="main-panels">
        <?php if (!$show_dept_id): ?>
        <div class="left-panel">
            <div class="search-header">
                <input type="text" id="deptSearch" placeholder="Поиск подразделений...">
            </div>
            <div class="dept-list" id="deptList"></div>
        </div>

        <?php endif; ?>
        <div class="right-panel">
            <div class="selected-dept-header">
                <i class="fas fa-building"></i>
                <span id="selectedDeptName">Подразделение не выбрано</span>
            </div>

            <div class="tabs-header" id="tabsHeader">
                <?php foreach ($tabs_config as $key => $tab): ?>
                    <div class="tab-btn" data-tab="<?= $key ?>">
                        <i class="fas fa-<?= ($key == 'internet' ? 'wifi' : ($key == 'telephony' ? 'phone' : ($key == 'ved_dep' ? 'network-wired' : ($key == 'contacts' ? 'address-book' : 'folder')))) ?>"></i>
                        <?= $tab['title'] ?>
                    </div>
                <?php endforeach; ?>
                <div class="tab-btn" data-tab="notes">
                    <i class="fas fa-sticky-note"></i> Заметки
                    <span class="note-count hidden" id="notesCount">0</span>
                </div>
            </div>

            <div class="tab-content" id="tabContent">
                <div id="placeholderMessage" class="placeholder-message">
                    <i class="fas fa-arrow-left"></i>
                    <span>Выберите подразделение из списка слева</span>
                </div>

                <?php foreach ($tabs_config as $key => $tab): ?>
                    <div class="tab-pane" id="tab-<?= $key ?>" data-tab="<?= $key ?>">

                        <form class="tab-form" data-tab="<?= $key ?>">
                            <?php foreach ($tab['fields'] as $field_name => $field_config):
                                $input_id = $key . '_' . $field_name;
                                ?>

                                <div class="form-group">
                                    <label for="<?= $input_id ?>"><?= $field_config['label'] ?></label>
                                    <?php if ($field_config['type'] === 'textarea'): ?>
                                        <textarea class="form-control" id="<?= $input_id ?>" name="<?= $input_id ?>" placeholder="<?= $field_config['placeholder'] ?? '' ?>"></textarea>
                                    <?php elseif ($field_config['type'] === 'select'): ?>
                                        <select class="form-control" id="<?= $input_id ?>" name="<?= $input_id ?>">
                                            <option value=""><?= $field_config['placeholder'] ?? 'Выберите...' ?></option>
                                            <?php foreach ($field_config['options'] as $opt): ?>
                                                <option value="<?= $opt ?>"><?= $opt ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                    <?php elseif ($field_config['type'] === 'textselect2'): ?>
                                        <select class="form-control typeselect2text" data-tab-key="<?=$key?>" data-field-name="<?= $field_name?>"  id="<?= $input_id ?>" name="<?= $input_id ?>">
                                            <option value=""><?= $field_config['placeholder'] ?? 'Выберите...' ?></option>
                                            <?php foreach ($field_config['options'] as $opt): ?>
                                                <option value="<?= $opt ?>"><?= $opt ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                    <?php else: ?>
                                        <input type="<?= $field_config['type'] ?>" class="form-control" id="<?= $input_id ?>" name="<?= $input_id ?>" placeholder="<?= $field_config['placeholder'] ?? '' ?>" <?= isset($field_config['autocomplete']) ? 'autocomplete="off"' : '' ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </form>
                    </div>
                <?php endforeach; ?>

                <div class="tab-pane" id="tab-notes" data-tab="notes">
                    <div class="notes-container">
                        <div class="notes-list" id="notesList"></div>
                        <div class="add-note-area">
                            <textarea class="form-control" id="newNoteText" placeholder="Текст заметки..."></textarea>
                            <button class="add-note-btn" id="addNoteBtn"><i class="fas fa-plus"></i> Добавить</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-actions" id="saveButtonContainer">
                <button class="save-button" id="saveTabsBtn"><i class="fas fa-save"></i> Сохранить изменения</button>
            </div>
        </div>
    </div>
</div>


<script src="/js/jquery-3.6.0.min.js"></script>
<script src="/js/select2.min.js"></script>
<?PHP
if(!$show_dept_id):
?>
<script src="/js/main.js"></script>
<?PHP endif; ?>

<script>
    const departments = <?= json_encode($departments) ?>;
    const initialDept = <?= $initial_dept ? json_encode($initial_dept) : 'null' ?>;
    const providersList = <?= json_encode($providers_list) ?>;
    const currentUser = <?= json_encode($current_user) ?>;

    let currentDeptId = null;
    let notesCount = 0;

    function renderDeptList(filterText = '') {
        const container = $('#deptList');
        container.empty();

        const byParent = {};
        departments.forEach(d => {
            const pid = d.parent_id || 'root';
            if (!byParent[pid]) byParent[pid] = [];
            byParent[pid].push(d);
        });
        for (let pid in byParent) {
            byParent[pid].sort((a, b) => a.sort_id - b.sort_id);
        }

        function matchesFilter(dept, text) {
            text = text.toLowerCase();
            return dept.name.toLowerCase().includes(text);
        }

        function buildItems(parentId = 'root', level = 0, parentPassedFilter = false) {
            const items = byParent[parentId] || [];
            items.forEach(dept => {
                const pass = parentPassedFilter || matchesFilter(dept, filterText);
                let childrenPass = false;
                const children = byParent[dept.id] || [];
                if (children.length > 0 && level === 0) {
                    childrenPass = children.some(child => matchesFilter(child, filterText));
                }

                if (pass || childrenPass) {
                    const $item = $('<div>')
                        .addClass('dept-item')
                        .addClass(level === 1 ? 'child' : '')
                        .attr('data-id', dept.id)
                        .text(dept.name)
                        .appendTo(container);
                    if (currentDeptId == dept.id) $item.addClass('selected');

                    if (children.length > 0 && level === 0) {
                        children.forEach(child => {
                            const childPass = matchesFilter(child, filterText);
                            if (pass || childPass) {
                                const $child = $('<div>')
                                    .addClass('dept-item child')
                                    .attr('data-id', child.id)
                                    .text(child.name)
                                    .appendTo(container);
                                if (currentDeptId == child.id) $child.addClass('selected');
                            }
                        });
                    }
                }
            });
        }

        buildItems('root', 0, filterText === '');

        if (container.children().length === 0) {
            container.append('<div class="empty-search">Ничего не найдено</div>');
        }
    }

    function clearForms() {
        $('.tab-form input, .tab-form textarea, .tab-form select').val('').trigger('change');
    }

    function togglePlaceholder(show) {
        if (show) {
            $('#placeholderMessage').show();
            $('.tab-pane').removeClass('active');
            $('#saveButtonContainer').hide();
            $('#selectedDeptName').text('Подразделение не выбрано');
        } else {
            $('#placeholderMessage').hide();
            $('#saveButtonContainer').show();
        }
    }

    function updateSaveButtonVisibility() {
        if (!currentDeptId) {
            $('#saveButtonContainer').hide();
            return;
        }
        const activeTab = $('.tab-btn.active').data('tab');
        if (activeTab === 'notes') {
            $('#saveButtonContainer').hide();
        } else {
            $('#saveButtonContainer').show();
        }
    }

    function loadDepartmentData(deptId, deptName) {
        currentDeptId = deptId;
        $('#selectedDeptName').text(deptName);
        togglePlaceholder(false);

        clearForms();

        $.getJSON('?action=get_tabs', { dept_id: deptId }, function(res) {
            if (res.tabs) {
                Object.keys(res.tabs).forEach(tabKey => {
                    const fields = res.tabs[tabKey];
                    Object.keys(fields).forEach(fieldName => {
                        const $field = $(`#${tabKey}_${fieldName}`);
                        if ($field.length) {
                            $field.val(fields[fieldName]).trigger('change');
                        }
                    });
                });
            }

            if (res.notes) {
                notesCount = res.notes.length;
                renderNotes(res.notes);
            } else {
                notesCount = 0;
                renderNotes([]);
            }

            // Принудительно активируем вкладку "Интернет" после загрузки нового подразделения
            $('.tab-btn[data-tab="internet"]').click();

            updateSaveButtonVisibility();
        }).fail(function() {
            showMessage("error",'Ошибка загрузки данных');
        });
    }

    function showMessage(type, message) {
        const $container = $('.notification-container');
        const $notification = $(`
        <div class="notification ${type}">
            <span>${message}</span>
            <button class="close-btn">&times;</button>
        </div>
    `);

        // Добавляем в контейнер
        $container.append($notification);

        // Обработчик закрытия по кнопке
        $notification.find('.close-btn').on('click', function() {
            closeNotification($notification);
        });

        // Автоматическое закрытие через 3 секунды
        const timeout = setTimeout(() => {
            closeNotification($notification);
        }, 3000);

        // Функция закрытия с анимацией
        function closeNotification($el) {
            clearTimeout(timeout);
            $el.addClass('hide');
            $el.on('animationend', function() {
                $el.remove();
            });
        }
    }
    function renderNotes(notes) {
        const container = $('#notesList');
        container.empty();
        if (notes.length === 0) {
            container.append('<div class="empty-search">Нет заметок</div>');
        } else {
            notes.forEach(n => {
                const date = new Date(n.created_at).toLocaleString('ru-RU', {
                    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                });
                container.append(`
                        <div class="note-item" data-id="${n.id}">
                            <div class="note-header">
                                <div class="note-date"><i class="far fa-clock"></i> ${date}</div>
                                <div class="note-author"><i class="far fa-user"></i> ${escapeHtml(n.created_by)}</div>
                                <button class="delete-note" title="Удалить"><i class="fas fa-trash-alt"></i></button>
                            </div>
                            <div class="note-text">${escapeHtml(n.note)}</div>
                        </div>
                    `);
            });
        }
        if (notesCount > 0) {
            $('#notesCount').text(notesCount).removeClass('hidden');
        } else {
            $('#notesCount').addClass('hidden');
        }
    }

    function escapeHtml(unsafe) {
        return unsafe.replace(/[&<>"]/g, function(m) {
            if(m === '&') return '&amp;';
            if(m === '<') return '&lt;';
            if(m === '>') return '&gt;';
            if(m === '"') return '&quot;';
            return m;
        });
    }

    function saveTabs() {
        if (!currentDeptId) {
            showMessage("error",'Выберите подразделение');
            return;
        }
        let formData = { department_id: currentDeptId };
        $('.tab-form').each(function() {
            $(this).serializeArray().forEach(item => {
                formData[item.name] = item.value;
            });
        });

        $.post('?action=save_tabs', formData, function(res) {
            if (res.success) {
                showMessage("success",'Данные сохранены');
            } else {
                showMessage("error",'Ошибка: ' + (res.error || 'Неизвестная ошибка'));
            }
        }, 'json');
    }

    function addNote() {
        if (!currentDeptId) {
            showMessage("error",'Выберите подразделение');
            return;
        }
        const noteText = $('#newNoteText').val().trim();
        if (!noteText) {
            showMessage("error",'Введите текст заметки');
            return;
        }
        $.post('?action=add_note', { department_id: currentDeptId, note: noteText }, function(res) {
            if (res.success && res.note) {
                $('#newNoteText').val('');
                notesCount++;
                const date = new Date(res.note.created_at).toLocaleString('ru-RU', {
                    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
                });
                const noteHtml = `
                        <div class="note-item" data-id="${res.note.id}">
                            <div class="note-header">
                                <div class="note-date"><i class="far fa-clock"></i> ${date}</div>
                                <div class="note-author"><i class="far fa-user"></i> ${escapeHtml(res.note.created_by)}</div>
                                <button class="delete-note" title="Удалить"><i class="fas fa-trash-alt"></i></button>
                            </div>
                            <div class="note-text">${escapeHtml(res.note.note)}</div>
                        </div>
                    `;
                const container = $('#notesList');
                if (container.children().first().hasClass('empty-search')) {
                    container.html(noteHtml);
                } else {
                    container.prepend(noteHtml);
                }
                $('#notesCount').text(notesCount).removeClass('hidden');
            } else {
                showMessage("error",'Ошибка добавления заметки');
            }
        }, 'json');
    }

    function deleteNote(noteId, element) {
        if (!confirm('Удалить заметку?')) return;
        $.post('?action=delete_note', { note_id: noteId }, function(res) {
            if (res.success) {
                element.closest('.note-item').remove();
                notesCount--;
                if (notesCount === 0) {
                    $('#notesList').html('<div class="empty-search">Нет заметок</div>');
                    $('#notesCount').addClass('hidden');
                } else {
                    $('#notesCount').text(notesCount);
                }
            } else {
                showMessage("error",'Ошибка удаления');
            }
        }, 'json');
    }

    function initSelect2() {

        $(".typeselect2text").each((n,e)=>{
            let tab_key = $(e).attr("data-tab-key");
            let field_name = $(e).attr("data-field-name");
            $(e).select2({
                tags: true,
                width: '100%',
                templateSelection: function(data, container) {
                    // Стандартное отображение
                    var selection = data.text;
                    // Добавляем кастомную кнопку очистки
                    if (data.id) {
                        return $('<span>' + selection +
                            ' <button type="button" class="custom-clear">✖</button></span>')
                            .on('click', '.custom-clear', function(e1) {
                                $(e).val(null).trigger('change');
                                e1.stopPropagation();
                            });
                    }
                    return selection;
                },
                allowClear: false,
                data: providersList.filter(p=> p.tab_key == tab_key && p.field_name == field_name).map(p => p.field_value).reduce((acc, item) => {
                    if (!acc.includes(item)) {
                        acc.push(item);
                    }
                    return acc;
                }, []).map(p => ({ id: p, text: p })),
                ajax: {
                    url: `?action=search_providers&tab_key=${tab_key}&field_name=${field_name}`,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { term: params.term };
                    },
                    processResults: function (data) {
                        return { results: data.map(item => ({ id: item, text: item })) };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });
        });
    }

    $(document).ready(function() {
        renderDeptList();
        togglePlaceholder(true);
        initSelect2();

        let searchTimeout;
        $('#deptSearch').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                renderDeptList($(this).val());
            }, 300);
        });

        $(document).on('click', '.dept-item', function() {
            $('.dept-item').removeClass('selected');
            $(this).addClass('selected');
            const deptId = $(this).data('id');
            const deptName = $(this).text();
            loadDepartmentData(deptId, deptName);
        });

        $('.tab-btn').on('click', function() {
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            const tabKey = $(this).data('tab');
            $('.tab-pane').removeClass('active');
            $(`#tab-${tabKey}`).addClass('active');

            updateSaveButtonVisibility();
        });

        $('#saveTabsBtn').on('click', saveTabs);
        $('#addNoteBtn').on('click', addNote);

        $(document).on('click', '.delete-note', function(e) {
            e.stopPropagation();
            const noteId = $(this).closest('.note-item').data('id');
            deleteNote(noteId, $(this));
        });


        if (initialDept) {
            // Небольшая задержка, чтобы убедиться, что DOM и Select2 готовы
            setTimeout(() => {
                loadDepartmentData(initialDept.id, initialDept.name);
            }, 100);
        }
    });
</script>
</body>
</html>