<?php
// Подключение к базе данных через Medoo
require_once 'vendor/autoload.php';
require_once 'includes/db.php';
require_once 'includes/auth_check.php';


if(!canAccess(CORE::ROLE_DEPARTMENTCONTROL)){
    echo "Доступ запрещен!";
    die;
}

use Medoo\Medoo;

// Обработка AJAX запросов
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];

    switch ($action) {
        case 'get_department':
            $id = $_POST['id'] ?? 0;
            if ($id > 0) {
                $department = CORE::$db->get('department', '*', ['id' => $id]);
                if ($department) {
                    $response = ['success' => true, 'data' => $department];
                } else {
                    $response['message'] = 'Подразделение не найдено';
                }
            }
            break;

        case 'add':
            $data = [
                'name' => $_POST['name'] ?? '',
                'addr' => $_POST['addr'] ?? '',
                'parent_id' => !empty($_POST['parent_id']) ? $_POST['parent_id'] : null,
                'lat' => !empty($_POST['lat']) ? $_POST['lat'] : null,
                'lng' => !empty($_POST['lng']) ? $_POST['lng'] : null
            ];

            if (empty($data['name']) || empty($data['addr'])) {
                $response['message'] = 'Заполните обязательные поля';
                break;
            }

            // Получаем максимальный sort_id для данного родителя
            $maxSort = CORE::$db->max('department', 'sort_id', $data['parent_id'] ? ['parent_id' => $data['parent_id']] : ['parent_id' => null]);
            $data['sort_id'] = ($maxSort ?: 0) + 1;

            try {
                CORE::$db->insert('department', $data);
                $response = ['success' => true, 'id' => CORE::$db->id()];
            } catch (Exception $e) {
                $response['message'] = 'Ошибка при добавлении: ' . $e->getMessage();
            }
            break;

        case 'update':
            $id = $_POST['id'] ?? 0;
            $data = [
                'name' => $_POST['name'] ?? '',
                'addr' => $_POST['addr'] ?? '',
                'parent_id' => !empty($_POST['parent_id']) ? $_POST['parent_id'] : null,
                'lat' => !empty($_POST['lat']) ? $_POST['lat'] : null,
                'lng' => !empty($_POST['lng']) ? $_POST['lng'] : null
            ];

            if ($id > 0 && !empty($data['name']) && !empty($data['addr'])) {
                CORE::$db->update('department', $data, ['id' => $id]);
                $response = ['success' => true];
            } else {
                $response['message'] = 'Неверные данные';
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            if ($id > 0) {
                // Проверяем, есть ли дочерние подразделения
                $children = CORE::$db->count('department', ['parent_id' => $id]);
                if ($children > 0) {
                    $response['message'] = 'У подразделения есть дочерние элементы';
                    break;
                }

                CORE::$db->delete('department', ['id' => $id]);
                $response = ['success' => true];
            }
            break;

        case 'update_order':
            $orders = json_decode($_POST['orders'] ?? '[]', true);

            try {
                CORE::$db->pdo->beginTransaction();

                foreach ($orders as $order) {
                    CORE::$db->update('department', [
                        'parent_id' => $order['parent_id'] ?: null,
                        'sort_id' => $order['sort_id']
                    ], ['id' => $order['id']]);
                }

                CORE::$db->pdo->commit();
                $response = ['success' => true];
            } catch (Exception $e) {
                CORE::$db->pdo->rollBack();
                $response['message'] = 'Ошибка при обновлении порядка: ' . $e->getMessage();
            }
            break;
    }

    header('Content-Type: application/json');
    echo json_encode($response,JSON_UNESCAPED_UNICODE);
    exit;
}

// Получение всех подразделений с построением дерева
function getDepartmentsTree($parent_id = null) {
    $where = ['ORDER' => ['sort_id' => 'ASC']];

    if ($parent_id === null) {
        $where['parent_id'] = null;
    } else {
        $where['parent_id'] = $parent_id;
    }

    $departments = CORE::$db->select('department', '*', $where);

    foreach ($departments as &$dept) {
        $children = getDepartmentsTree($dept['id']);
        if (!empty($children)) {
            $dept['children'] = $children;
        }
    }

    return $departments;
}

// Получение всех подразделений для выпадающего списка
function getAllDepartments($excludeId = null) {
    $where = ['ORDER' => ['name' => 'ASC']];
    if ($excludeId) {
        $where['id[!]'] = $excludeId;
    }
    return CORE::$db->select('department', ['id', 'name'], $where);
}

// Подсчет подразделений без координат
function countDepartmentsWithoutCoords() {
    return CORE::$db->count('department', [
        'OR' => [
            'lat' => null,
            'lng' => null
        ]
    ]);
}

$departmentsTree = getDepartmentsTree();
$allDepartments = getAllDepartments();
$noCoordsCount = countDepartmentsWithoutCoords();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование подразделений - Система управления</title>
    <link rel="stylesheet" href="/css/fontawesome-free-6.7.2-web/css/all.min.css">
    <link rel="stylesheet" href="/js/dist/leaflet.css" />
    <link href="/css/Inter-4.1/web/inter.css" rel="stylesheet">
    <link href="/css/main.css" rel="stylesheet">

    <link rel="stylesheet" href="/js/jquery-ui-1.13.2/jquery-ui.min.css">
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
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        .content {
            padding: 32px;
            display: flex;
            gap: 32px;
        }

        .tree-container, .edit-container {
            flex: 1;
        }

        .tree-container {
            min-width: 400px;
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

        /* Предупреждение о координатах */
        .warning-banner {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #856404;
        }

        .warning-banner i {
            font-size: 20px;
            color: #f39c12;
        }

        /* Дерево подразделений */
        #departmentsTree {
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 15px;
            min-height: 300px;
            max-height: 600px;
            overflow-y: auto;
        }

        .department-item {
            padding: 12px 15px;
            margin: 8px 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            cursor: move;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
            position: relative;
        }

        .department-item:hover {
            background: #f8f9fa;
            border-color: #3498db;
        }

        .department-item.no-coords {
            border-left: 4px solid #f39c12;
            background: #fffaf0;
        }

        .department-item.no-coords:hover {
            background: #fff5e6;
        }

        .department-item.ui-sortable-helper {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .department-item.ui-state-highlight {
            height: 40px;
            background: #e9ecef;
            border: 2px dashed #3498db;
        }

        .department-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .department-name {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .department-address {
            font-size: 13px;
            color: #6c757d;
        }

        .no-coords-badge {
            background: #f39c12;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }

        .department-actions {
            display: flex;
            gap: 8px;
        }

        .tree-nested {
            margin-left: 30px;
            border-left: 2px solid #e9ecef;
            padding-left: 15px;
        }

        .tree-placeholder {
            height: 40px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            margin: 5px 0;
        }

        /* Форма редактирования */
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

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .coords-container {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        .coords-input {
            flex: 1;
        }

        .coords-input input {
            font-family: monospace;
        }

        .coords-btn {
            padding: 12px;
            background: #ecf0f1;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #495057;
            transition: all 0.2s ease;
        }

        .coords-btn:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ced4da;
        }

        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #495057;
        }

        /* Модальное окно карты */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 1000px;
            height: 80vh;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 18px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #6c757d;
        }

        .modal-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        #map {
            flex: 1;
            min-height: 0;
        }

        .map-controls {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .coords-display {
            font-family: monospace;
            font-size: 14px;
            color: #495057;
        }

        .status-message {
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
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

        @media (max-width: 1024px) {
            .content {
                flex-direction: column;
            }

            .tree-container, .edit-container {
                min-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .coords-container {
                flex-direction: column;
            }

            .tree-nested {
                margin-left: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1> <?PHP include "includes/menu.php"?> <i class="fas fa-sitemap"></i> Редактирование подразделений</h1>
        <div class="header-actions">
            <?PHP include  "includes/avatar_block.php"; ?>
            <button class="btn btn-primary" id="addNewBtn">
                <i class="fas fa-plus"></i> Новое подразделение
            </button>
        </div>
    </div>

    <div class="content">
        <div class="tree-container">
            <div class="section">
                <h2><i class="fas fa-network-wired"></i> Структура подразделений</h2>

                <?php if ($noCoordsCount > 0): ?>
                    <div class="warning-banner" id="warningBanner">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Внимание!</strong> Найдено <strong><?php echo $noCoordsCount; ?></strong> подразделений без координат.
                            <a href="javascript:void(0)" id="showNoCoords">Показать</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="departmentsTree">
                    <?php if (empty($departmentsTree)): ?>
                        <div class="empty-state">
                            <i class="fas fa-sitemap"></i>
                            <h3>Нет подразделений</h3>
                            <p>Добавьте первое подразделение</p>
                        </div>
                    <?php else: ?>
                        <!-- Дерево будет построено через JavaScript -->
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="edit-container">
            <div class="section">
                <h2><i class="fas fa-edit"></i> Редактирование подразделения</h2>

                <div id="statusMessage" class="status-message"></div>

                <form id="departmentForm">
                    <input type="hidden" id="departmentId" name="id" value="">
                    <input type="hidden" id="actionType" name="action" value="add">

                    <div class="form-group">
                        <label for="name" class="required"><i class="fas fa-signature"></i> Название</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="addr" class="required"><i class="fas fa-map-marker-alt"></i> Адрес</label>
                        <input type="text" id="addr" name="addr" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="parent_id"><i class="fas fa-level-up-alt"></i> Родительское подразделение</label>
                        <select id="parent_id" name="parent_id" class="form-control">
                            <option value="">(Корневой уровень)</option>
                            <?php foreach ($allDepartments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-globe"></i> Координаты</label>
                        <div class="coords-container">
                            <div class="coords-input">
                                <input type="text" id="lat" name="lat" class="form-control" placeholder="Широта (например: 55.7558)">
                            </div>
                            <div class="coords-input">
                                <input type="text" id="lng" name="lng" class="form-control" placeholder="Долгота (например: 37.6173)">
                            </div>
                            <button type="button" class="coords-btn" id="openMapBtn" title="Выбрать на карте">
                                <i class="fas fa-map-marked-alt"></i>
                            </button>
                        </div>
                        <div style="margin-top: 8px; font-size: 13px; color: #6c757d;">
                            Формат: 39.123456
                        </div>
                    </div>


                    <div class="form-actions">
                        <button type="button" class="btn btn-danger" id="deleteBtn" style="display: none;">
                            <i class="fas fa-trash"></i> Удалить
                        </button>
                        <button type="button" class="btn btn-warning" id="setCoordsBtn" style="display: none;">
                            <i class="fas fa-map-marker-alt"></i> Установить координаты
                        </button>
                        <div style="flex: 1;"></div>
                        <button type="button" class="btn btn-secondary" id="cancelBtn">
                            <i class="fas fa-times"></i> Отмена
                        </button>
                        <button type="submit" class="btn btn-success" id="saveBtn">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно карты -->
<div class="modal-overlay" id="mapModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-map-marked-alt"></i> Выбор координат на карте</h3>
            <button class="modal-close" id="modalClose">&times;</button>
        </div>
        <div class="modal-body">
            <div id="map"></div>
            <div class="map-controls">
                <div class="coords-display" id="coordsDisplay">
                    Кликните на карту для выбора координат
                </div>
                <button class="btn btn-primary btn-sm" id="applyCoordsBtn">
                    <i class="fas fa-check"></i> Применить координаты
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/js/jquery-3.6.0.min.js"></script>
<script src="/js/jquery-ui-1.13.2/jquery-ui.min.js"></script>
<script src="/js/dist/leaflet.js"></script>
<script src="/js/main.js"></script>
<script>
    // Переменные
    let map = null;
    let selectedCoords = null;
    let currentMarker = null;
    let currentDepartmentId = null;

    // Данные подразделений
    const departments = <?php echo json_encode($departmentsTree, JSON_UNESCAPED_UNICODE); ?>;
    const allDepartments = <?php echo json_encode($allDepartments, JSON_UNESCAPED_UNICODE); ?>;
    const noCoordsCount = <?php echo $noCoordsCount; ?>;

    // DOM элементы
    const elements = {
        departmentsTree: document.getElementById('departmentsTree'),
        departmentForm: document.getElementById('departmentForm'),
        departmentId: document.getElementById('departmentId'),
        actionType: document.getElementById('actionType'),
        nameInput: document.getElementById('name'),
        addrInput: document.getElementById('addr'),
        parentIdSelect: document.getElementById('parent_id'),
        latInput: document.getElementById('lat'),
        lngInput: document.getElementById('lng'),
        deleteBtn: document.getElementById('deleteBtn'),
        setCoordsBtn: document.getElementById('setCoordsBtn'),
        cancelBtn: document.getElementById('cancelBtn'),
        saveBtn: document.getElementById('saveBtn'),
        addNewBtn: document.getElementById('addNewBtn'),
        statusMessage: document.getElementById('statusMessage'),
        mapModal: document.getElementById('mapModal'),
        modalClose: document.getElementById('modalClose'),
        openMapBtn: document.getElementById('openMapBtn'),
        applyCoordsBtn: document.getElementById('applyCoordsBtn'),
        coordsDisplay: document.getElementById('coordsDisplay'),
        warningBanner: document.getElementById('warningBanner'),
        showNoCoords: document.getElementById('showNoCoords')
    };

    // Функция для отображения сообщения
    function showMessage(text, type = 'success') {
        elements.statusMessage.textContent = text;
        elements.statusMessage.className = `status-message status-${type}`;
        elements.statusMessage.style.display = 'block';

        setTimeout(() => {
            elements.statusMessage.style.display = 'none';
        }, 5000);
    }

    // Построение дерева подразделений
    function buildTree(departments, parentId = null) {
        let html = '';

        departments.forEach(dept => {
            if (dept.parent_id == parentId) {
                const hasChildren = dept.children && dept.children.length > 0;
                const hasCoords = dept.lat && dept.lng;
                const noCoordsClass = !hasCoords ? 'no-coords' : '';

                html += `
                        <div class="department-item ${noCoordsClass}" data-id="${dept.id}" data-parent="${dept.parent_id || ''}" data-sort="${dept.sort_id}">
                            <div class="department-info">
                                <i class="fas fa-building" style="color: ${hasCoords ? '#3498db' : '#e74c3c'}"></i>
                                <div>
                                    <div class="department-name">
                                        ${dept.name}
                                        ${!hasCoords ? '<span class="no-coords-badge">Без координат</span>' : ''}
                                    </div>
                                    <div class="department-address">${dept.addr}</div>
                                    ${hasCoords ?
                    `<div style="font-size: 12px; color: #6c757d;">
                                            <i class="fas fa-map-marker-alt"></i>
                                            ${dept.lat}, ${dept.lng}
                                        </div>` :
                    `<div style="font-size: 12px; color: #e74c3c;">
                                            <i class="fas fa-exclamation-circle"></i> Координаты не заданы
                                        </div>`
                }
                                </div>
                            </div>
                            <div class="department-actions">
                                <button class="btn btn-secondary btn-sm edit-btn" data-id="${dept.id}" title="Редактировать">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    `;

                if (hasChildren) {
                    html += `<div class="tree-nested" id="children-${dept.id}">`;
                    html += buildTree(dept.children || [], dept.id);
                    html += `</div>`;
                }
            }
        });

        return html;
    }

    // Инициализация дерева с drag&drop
    function initTree() {
        if (departments.length === 0) return;

        elements.departmentsTree.innerHTML = buildTree(departments);

        // Настраиваем сортировку для всех списков
        $('#departmentsTree').sortable({
            connectWith: '.tree-nested',
            items: '.department-item',
            placeholder: 'tree-placeholder',
            tolerance: 'pointer',
            cursor: 'move',
            opacity: 0.7,
            delay: 150,
            update: handleTreeUpdate
        });

        $('.tree-nested').sortable({
            connectWith: '#departmentsTree, .tree-nested',
            items: '.department-item',
            placeholder: 'tree-placeholder',
            tolerance: 'pointer',
            cursor: 'move',
            opacity: 0.7,
            delay: 150,
            update: handleTreeUpdate
        });

        // Обработчики для кнопок редактирования
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const deptId = this.dataset.id;
                loadDepartment(deptId);
            });
        });

        // Обработчики для клика по подразделению
        document.querySelectorAll('.department-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (!e.target.closest('.edit-btn') && !e.target.closest('.no-coords-badge')) {
                    loadDepartment(this.dataset.id);
                }
            });
        });
    }

    // Обработка обновления порядка в дереве
    function handleTreeUpdate(event, ui) {
        // Не обрабатываем обновление, если элемент еще не установился
        if (!ui.item) return;

        const orders = [];

        // Собираем данные корневого уровня
        $('#departmentsTree > .department-item').each(function(index) {
            orders.push({
                id: $(this).data('id'),
                parent_id: null,
                sort_id: index
            });
        });

        // Собираем данные вложенных уровней
        $('.tree-nested').each(function() {
            const parentId = $(this).attr('id').replace('children-', '');
            $(this).children('.department-item').each(function(index) {
                orders.push({
                    id: $(this).data('id'),
                    parent_id: parentId,
                    sort_id: index
                });
            });
        });

        // Отправляем обновления на сервер
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'update_order',
                orders: JSON.stringify(orders)
            })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showMessage(data.message || 'Ошибка при обновлении порядка', 'error');
                }
            });
    }

    // Загрузка данных подразделения для редактирования
    function loadDepartment(deptId) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_department',
                id: deptId
            })
        })
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data) {
                    const dept = result.data;
                    currentDepartmentId = dept.id;
                    elements.departmentId.value = dept.id;
                    elements.actionType.value = 'update';
                    elements.nameInput.value = dept.name || '';
                    elements.addrInput.value = dept.addr || '';
                    elements.parentIdSelect.value = dept.parent_id || '';
                    elements.latInput.value = dept.lat || '';
                    elements.lngInput.value = dept.lng || '';

                    // Показываем кнопки удаления и установки координат
                    elements.deleteBtn.style.display = 'inline-flex';
                    elements.setCoordsBtn.style.display = dept.lat && dept.lng ? 'none' : 'inline-flex';
                    elements.saveBtn.innerHTML = '<i class="fas fa-save"></i> Обновить';

                    // Обновляем опции в родительском списке
                    updateParentSelect(deptId);

                    showMessage(`Загружено подразделение: ${dept.name}`, 'success');
                } else {
                    showMessage(result.message || 'Ошибка загрузки данных', 'error');
                }
            })
            .catch(error => {
                showMessage('Ошибка сети', 'error');
            });
    }

    // Обновление списка родительских подразделений
    function updateParentSelect(excludeId = null) {
        const currentValue = elements.parentIdSelect.value;
        elements.parentIdSelect.innerHTML = '<option value="">(Корневой уровень)</option>';

        allDepartments.forEach(dept => {
            if (dept.id != excludeId) {
                const option = document.createElement('option');
                option.value = dept.id;
                option.textContent = dept.name;
                if (dept.id == currentValue) {
                    option.selected = true;
                }
                elements.parentIdSelect.appendChild(option);
            }
        });
    }

    // Сброс формы
    function resetForm() {
        currentDepartmentId = null;
        elements.departmentForm.reset();
        elements.departmentId.value = '';
        elements.actionType.value = 'add';
        elements.deleteBtn.style.display = 'none';
        elements.setCoordsBtn.style.display = 'none';
        elements.saveBtn.innerHTML = '<i class="fas fa-save"></i> Сохранить';
        updateParentSelect();
        showMessage('Форма сброшена. Готово для добавления нового подразделения.', 'success');
    }

    // Инициализация карты
    function initMap() {
        if (!map) {
            let center = {lat:48.563665, lng:39.311153};
            let zoom = 10;
            let crs =L.CRS.EPSG3395;
            map = L.map('map',{
                crs
            }).setView([center.lat,center.lng], zoom);


            L.tileLayer('http://map.mchs.lnr/downloaded/tiles/satellite/{z}/{x}/{y}.jpg', {
                attribution: false,
                crs: L.CRS.EPSG3395
            }).addTo(map);
             L.tileLayer('http://map.mchs.lnr/downloaded/tiles/hybrid/{z}/{x}/{y}.jpg', {
                attribution: false,
                crs: L.CRS.EPSG3395
            }).addTo(map);

            // Обработчик клика по карте
            map.on('click', function(e) {
                selectedCoords = e.latlng;

                // Обновляем отображение координат
                elements.coordsDisplay.textContent =
                    `Широта: ${selectedCoords.lat.toFixed(6)}, Долгота: ${selectedCoords.lng.toFixed(6)}`;

                // Удаляем предыдущий маркер
                if (currentMarker) {
                    map.removeLayer(currentMarker);
                }

                // Добавляем новый маркер
                currentMarker = L.marker(selectedCoords).addTo(map);
            });

            // Попробуем использовать текущие координаты из формы
            if (elements.latInput.value && elements.lngInput.value) {
                const lat = parseFloat(elements.latInput.value);
                const lng = parseFloat(elements.lngInput.value);
                if (!isNaN(lat) && !isNaN(lng)) {
                    map.setView([lat, lng], 15);
                    if (currentMarker) {
                        map.removeLayer(currentMarker);
                    }
                    currentMarker = L.marker([lat, lng]).addTo(map);
                }
            }
        } else {
            map.invalidateSize();
        }
    }

    // Показать подразделения без координат
    function highlightNoCoords() {
        const allItems = document.querySelectorAll('.department-item');
        allItems.forEach(item => {
            item.style.background = '';
            item.style.borderColor = '';
        });

        const noCoordsItems = document.querySelectorAll('.department-item.no-coords');
        noCoordsItems.forEach(item => {
            item.style.background = '#fffaf0';
            item.style.borderColor = '#f39c12';
        });

        if (noCoordsItems.length > 0) {
            noCoordsItems[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Обработчики событий
    elements.departmentForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const action = elements.actionType.value;

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Подразделение успешно сохранено', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage(data.message || 'Ошибка при сохранении', 'error');
                }
            })
            .catch(error => {
                showMessage('Ошибка сети', 'error');
            });
    });

    elements.deleteBtn.addEventListener('click', function() {
        if (!currentDepartmentId || !confirm('Вы уверены, что хотите удалить это подразделение?')) {
            return;
        }

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'delete',
                id: currentDepartmentId
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Подразделение удалено', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage(data.message || 'Ошибка при удалении', 'error');
                }
            });
    });

    elements.setCoordsBtn.addEventListener('click', function() {
        elements.mapModal.classList.add('active');
        setTimeout(initMap, 10);
    });

    elements.cancelBtn.addEventListener('click', resetForm);
    elements.addNewBtn.addEventListener('click', resetForm);

    // Работа с картой
    elements.openMapBtn.addEventListener('click', function() {
        elements.mapModal.classList.add('active');
        setTimeout(initMap, 10);
    });

    elements.modalClose.addEventListener('click', function() {
        elements.mapModal.classList.remove('active');
    });

    elements.applyCoordsBtn.addEventListener('click', function() {
        if (selectedCoords) {
            elements.latInput.value = selectedCoords.lat.toFixed(6);
            elements.lngInput.value = selectedCoords.lng.toFixed(6);
            elements.mapModal.classList.remove('active');
            if (currentDepartmentId) {
                elements.setCoordsBtn.style.display = 'none';
            }
            showMessage('Координаты установлены', 'success');
        }
    });

    // Закрытие модального окна по клику вне
    elements.mapModal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });

    // Показать подразделения без координат
    if (elements.showNoCoords) {
        elements.showNoCoords.addEventListener('click', highlightNoCoords);
    }

    // Закрытие по Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (elements.mapModal.classList.contains('active')) {
                elements.mapModal.classList.remove('active');
            }
        }
    });

    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        initTree();
        resetForm();

        // Если есть подразделения без координат, выделить их
        if (noCoordsCount > 0) {
            setTimeout(highlightNoCoords, 500);
        }
    });
</script>
</body>
</html>