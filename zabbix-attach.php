<?php
// Подключение к базе данных через Medoo
require_once 'vendor/autoload.php';
include "includes/db.php";
require_once "includes/auth_check.php";
use Medoo\Medoo;

// AJAX обработчики
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $response = ['success' => false, 'message' => ''];

    // Обработка привязки через AJAX
    if (isset($_POST['action']) && $_POST['action'] === 'bind_node') {
        if (isset($_POST['node_id']) && isset($_POST['department_id'])) {
            $nodeId = (int)$_POST['node_id'];
            $deptId = (int)$_POST['department_id'];

            CORE::$db->delete("department_to_zabbix_node",["zabbix_hosts_hostid"=>$nodeId]);
            CORE::$db->insert("department_to_zabbix_node",
                ["zabbix_hosts_hostid"=>$nodeId,
                    "department_id"=>$deptId ]);

            $response['success'] = true;
            $response['message'] = 'Узел успешно привязан';
        }
    }

    // Обработка отвязки через AJAX
    if (isset($_POST['action']) && $_POST['action'] === 'unbind_all') {
        if (isset($_POST['node_id'])) {
            $nodeId = (int)$_POST['node_id'];
            CORE::$db->delete("department_to_zabbix_node",["zabbix_hosts_hostid"=>$nodeId]);

            $response['success'] = true;
            $response['message'] = 'Узел успешно отвязан';
        }
    }

    // Получение списка подразделений через AJAX
    if (isset($_POST['action']) && $_POST['action'] === 'get_departments') {
        $searchDept = isset($_POST['search_dept']) ? strtolower(trim($_POST['search_dept'])) : '';

        $allDepartments = CORE::$db->select("department",["name","id"]);
        $attachedIds = CORE::$db->select("department_to_zabbix_node","*");

        foreach ($allDepartments  as &$dept) {
            $dept['nodes'] = [];
            foreach ($attachedIds  as $att)
                if($att["department_id"] == $dept["id"])
                    array_push($dept['nodes'],intval($att["zabbix_hosts_hostid"]));
        }

        // Фильтрация по поиску
        if (!empty($searchDept)) {
            $allDepartments = array_filter($allDepartments, function($dept) use ($searchDept) {
                // mb_stripos - регистронезависимый поиск для юникода
                return mb_stripos($dept['name'], $searchDept, 0, 'UTF-8') !== false;
            });
        }

        $response['success'] = true;
        $response['departments'] = array_values($allDepartments);
        echo json_encode($response);
        exit;
    }

    // Обновление статистики
    if (isset($_POST['action']) && $_POST['action'] === 'get_stats') {
        //Возвращает узлы в виде таблицы, hostid, host, host_name - имя хоста,  host_groups - группы ('ATS','Repeaters','Coordinators')
        $allNodes = CORE::$dbZabbix->query("SELECT h.hostid as id,    h.host,    h.name as name,    GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as `group` FROM hosts h LEFT JOIN hosts_groups hg ON h.hostid = hg.hostid LEFT JOIN hstgrp g ON hg.groupid = g.groupid LEFT JOIN items i ON h.hostid = i.hostid AND i.status = 0 WHERE h.status = 0     AND h.flags IN (0, 4)     AND h.host NOT LIKE '{#%}'     AND h.name NOT LIKE '{#%}'     AND (g.internal = 0 OR g.internal IS NULL) GROUP BY h.hostid, h.host, h.name, h.status HAVING `group` IS NOT NULL and `group` in ('ATS','Repeaters','Coordinators') ORDER BY h.name;")->fetchAll();

        $attachedIds = CORE::$db->select("department_to_zabbix_node","zabbix_hosts_hostid");

        $totalNodes = count($allNodes);
        $boundNodes = count($attachedIds);
        $unboundNodes = $totalNodes - $boundNodes;

        $response['success'] = true;
        $response['stats'] = [
            'total' => $totalNodes,
            'bound' => $boundNodes,
            'unbound' => $unboundNodes
        ];
        echo json_encode($response);
        exit;
    }

    echo json_encode($response);
    exit;
}

// Обычная загрузка страницы (не AJAX)
//Возвращает узлы в виде таблицы, hostid, host, host_name - имя хоста,  host_groups - группы ('ATS','Repeaters','Coordinators')
$allNodes = CORE::$dbZabbix->query("SELECT h.hostid as id,    h.host,    h.name as name,    GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as `group` FROM hosts h LEFT JOIN hosts_groups hg ON h.hostid = hg.hostid LEFT JOIN hstgrp g ON hg.groupid = g.groupid LEFT JOIN items i ON h.hostid = i.hostid AND i.status = 0 WHERE h.status = 0     AND h.flags IN (0, 4)     AND h.host NOT LIKE '{#%}'     AND h.name NOT LIKE '{#%}'     AND (g.internal = 0 OR g.internal IS NULL) GROUP BY h.hostid, h.host, h.name, h.status HAVING `group` IS NOT NULL and `group` in ('ATS','Repeaters','Coordinators') ORDER BY h.name;")->fetchAll();

$allDepartments = CORE::$db->select("department",["name","id"]);
$attachedIds = CORE::$db->select("department_to_zabbix_node","*");
foreach ($allDepartments  as &$dept) {
    $dept['nodes'] = [];
    foreach ($attachedIds  as $att)
        if($att["department_id"] == $dept["id"])
            array_push($dept['nodes'],$att["zabbix_hosts_hostid"]);
}

// Получаем ID всех привязанных узлов
$boundNodeIds = [];
foreach ($allDepartments as $dept) {
    $boundNodeIds = array_merge($boundNodeIds, $dept['nodes']);
}
$boundNodeIds = array_unique($boundNodeIds);

// Статистика
$totalNodes = count($allNodes);
$boundNodes = count($boundNodeIds);
$unboundNodes = $totalNodes - $boundNodes;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Привязка узлов к подразделениям</title>
    <link rel="stylesheet" href="/css/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="/css/Inter-4.1/web/inter.css" rel="stylesheet">

    <link href="/css/main.css" rel="stylesheet">
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
            max-width: 1600px;
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

        .stats {
            display: flex;
            gap: 20px;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 20px;
            border-radius: 8px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.9;
        }

        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 32px;
            min-height: calc(100vh - 200px);
        }

        @media (max-width: 1200px) {
            .content {
                grid-template-columns: 1fr;
            }
        }

        .panel {
            background: #f8f9fa;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
        }

        .panel-header {
            background: #495057;
            color: white;
            padding: 18px 24px;
        }

        .panel-header h2 {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-body {
            flex-grow: 1;
            padding: 24px;
            overflow-y: auto;
        }

        .filters {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            background: white;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .filter-tabs {
            display: flex;
            gap: 5px;
            background: #ecf0f1;
            padding: 4px;
            border-radius: 8px;
        }

        .filter-tab {
            padding: 8px 16px;
            border: none;
            background: transparent;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #495057;
            font-size: 14px;
            position: relative;
        }

        .filter-tab:hover {
            background: #d5dbdb;
        }

        .filter-tab.active {
            background: #3498db;
            color: white;
        }

        @keyframes blink {
            0%, 100% { background-color: #dc3545; }
            50% { background-color: #ff6b7a; }
        }

        .filter-tab.blink {
            animation: blink 1s infinite;
        }

        .node-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .node-item {
            background: white;
            border-radius: 8px;
            padding: 16px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .node-item:hover {
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2);
        }

        .node-item.selected {
            border-color: #2c3e50;
            background: #f8f9fa;
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.1);
        }

        .node-item.unbound {
            border-color: #dc3545;
            background: #fff5f5;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }

        .node-info {
            flex-grow: 1;
        }

        .node-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .node-group {
            font-size: 14px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .unbound-badge {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .node-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            text-decoration: none;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .search-box {
            margin-bottom: 20px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
            transition: border 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .department-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .department-item {
            background: white;
            border-radius: 8px;
            padding: 16px;
            border: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .department-item.bound {
            border-color: #3498db;
            background: #e8f4fc;
        }

        .department-info {
            flex-grow: 1;
        }

        .department-name {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .node-count {
            font-size: 12px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .department-actions {
            display: flex;
            gap: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #ced4da;
        }

        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #495057;
        }

        .selection-info {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
            text-align: center;
        }

        .selected-node {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #e8f4fc;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        .selected-node strong {
            color: #2c3e50;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .node-count-badge {
            background: #3498db;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .no-results {
            padding: 40px 20px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            background: #28a745;
            color: white;
            font-weight: 500;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast.error {
            background: #dc3545;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">

        <h1><?PHP include "includes/menu.php"?><i class="fas fa-network-wired"></i> Привязка узлов сети к подразделениям</h1>
        <div class="stats">

            <div class="stat-item">
                <div class="stat-value" id="totalNodes"><?php echo $totalNodes; ?></div>
                <div class="stat-label">Всего узлов</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="boundNodes"><?php echo $boundNodes; ?></div>
                <div class="stat-label">Привязано</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="unboundNodes"><?php echo $unboundNodes; ?></div>
                <div class="stat-label">Не привязано</div>
            </div>
            <?PHP include  "includes/avatar_block.php"; ?>
        </div>
    </div>


    <div class="content">
        <!-- Левая панель: Узлы сети -->
        <div class="panel">
            <div class="panel-header">
                <h2><i class="fas fa-server"></i> Узлы сети (Zabbix)</h2>
            </div>

            <div class="panel-body">
                <div class="filters">
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-filter="all">
                            Все узлы
                        </button>
                        <button class="filter-tab" data-filter="unbound">
                            Непривязанные
                            <?php if ($unboundNodes > 0): ?>
                                <span class="node-count-badge"><?php echo $unboundNodes; ?></span>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>

                <?php if (empty($allNodes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>Узлы не найдены</h3>
                        <p>Попробуйте изменить фильтр</p>
                    </div>
                <?php else: ?>
                    <div class="node-list" id="nodeList">
                        <?php foreach ($allNodes as $node):
                            $isBound = in_array($node['id'], $boundNodeIds);
                            ?>
                            <div class="node-item <?php echo !$isBound ? 'unbound' : ''; ?>"
                                 data-node-id="<?php echo $node['id']; ?>"
                                 data-bound="<?php echo $isBound ? '1' : '0'; ?>">
                                <div class="node-info">
                                    <div class="node-name">
                                        <?php echo htmlspecialchars($node['name']); ?>
                                        <?php if (!$isBound): ?>
                                            <span class="unbound-badge">Нет привязки</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="node-group">
                                        <i class="fas fa-tag"></i>
                                        <?php echo htmlspecialchars($node['group']); ?>
                                    </div>
                                </div>

                                <div class="node-actions">
                                    <button class="btn btn-primary btn-sm bind-btn"
                                            data-node-id="<?php echo $node['id']; ?>"
                                            data-node-name="<?php echo htmlspecialchars($node['name']); ?>"
                                            data-node-group="<?php echo htmlspecialchars($node['group']); ?>">
                                        <i class="fas fa-link"></i> Привязать
                                    </button>

                                    <?php if ($isBound): ?>
                                        <button class="btn btn-danger btn-sm unbind-btn"
                                                data-node-id="<?php echo $node['id']; ?>"
                                                data-node-name="<?php echo htmlspecialchars($node['name']); ?>">
                                            <i class="fas fa-unlink"></i> Отвязать
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Правая панель: Подразделения -->
        <div class="panel">
            <div class="panel-header">
                <h2><i class="fas fa-building"></i> Подразделения</h2>
            </div>

            <div class="panel-body" id="departmentsPanel">
                <div class="empty-state">
                    <i class="fas fa-mouse-pointer"></i>
                    <h3>Выберите узел слева</h3>
                    <p>Для привязки к подразделению выберите узел из списка слева</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>

<script src="/js/jquery-3.6.0.min.js"></script>
<script src="/js/main.js"></script>
<script>
    $(document).ready(function() {
        let currentSelectedNodeId = null;
        let searchTimeout;

        // Обработчик фильтрации узлов
        $('.filter-tabs button').on('click', function() {
            const filter = $(this).data('filter');

            // Активируем выбранную кнопку
            $('.filter-tabs button').removeClass('active');
            $(this).addClass('active');

            // Применяем фильтр
            applyNodeFilter(filter);
        });

        // Функция фильтрации узлов
        function applyNodeFilter(filter) {
            $('.node-item').each(function() {
                const $node = $(this);
                const isBound = $node.data('bound') == 1;

                if (filter === 'all') {
                    $node.show();
                } else if (filter === 'unbound') {
                    if (!isBound) {
                        $node.show();
                    } else {
                        $node.hide();
                    }
                }
            });
        }

        // Обработчик кнопки "Привязать" на узле
        $(document).on('click', '.bind-btn', function() {
            const nodeId = $(this).data('node-id');
            const nodeName = $(this).data('node-name');
            const nodeGroup = $(this).data('node-group');

            // Снимаем выделение со всех узлов
            $('.node-item').removeClass('selected');
            // Выделяем текущий узел
            $(this).closest('.node-item').addClass('selected');

            // Устанавливаем текущий выбранный узел
            currentSelectedNodeId = nodeId;

            // Отображаем информацию о выбранном узле в правой панели
            showNodeSelection(nodeId, nodeName, nodeGroup);

            // Загружаем подразделения
            loadDepartments(nodeId);
        });

        // Показать информацию о выбранном узле в правой панели
        function showNodeSelection(nodeId, nodeName, nodeGroup) {
            const html = `
            <div class="selection-info">
                <div class="selected-node">
                    <div>
                        <strong>Выбран узел:</strong><br>
                        <span style="font-size: 16px;">${escapeHtml(nodeName)}</span>
                        <span style="color: #6c757d; font-size: 14px;">(${escapeHtml(nodeGroup)})</span>
                    </div>
                    <button class="btn btn-secondary" id="cancelSelection">
                        <i class="fas fa-times"></i> Отменить выбор
                    </button>
                </div>
                <div id="nodeStatusInfo"></div>
            </div>

            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text"
                       id="departmentSearch"
                       class="search-input"
                       placeholder="Поиск подразделений...">
            </div>

            <div id="departmentsList">
                <div class="empty-state">
                    <div class="loading"></div>
                    <h3>Загрузка подразделений...</h3>
                </div>
            </div>
        `;

            $('#departmentsPanel').html(html);

            // Инициализируем поиск
            $('#departmentSearch').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (currentSelectedNodeId) {
                        loadDepartments(currentSelectedNodeId, $(this).val());
                    }
                }, 500);
            });

            // Обработчик кнопки отмены выбора
            $('#cancelSelection').on('click', cancelSelection);
        }

        // Отменить выбор узла
        function cancelSelection() {
            // Снимаем выделение со всех узлов
            $('.node-item').removeClass('selected');

            // Сбрасываем текущий выбранный узел
            currentSelectedNodeId = null;

            // Показываем первоначальное состояние правой панели
            $('#departmentsPanel').html(`
            <div class="empty-state">
                <i class="fas fa-mouse-pointer"></i>
                <h3>Выберите узел слева</h3>
                <p>Для привязки к подразделению выберите узел из списка слева</p>
            </div>
        `);
        }

        // Загрузка списка подразделений
        function loadDepartments(nodeId, searchTerm = '') {
            if (!$('#departmentsList').length) return;

            $('#departmentsList').html(`
            <div class="empty-state">
                <div class="loading"></div>
                <h3>Загрузка подразделений...</h3>
            </div>
        `);

            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    ajax: 1,
                    action: 'get_departments',
                    search_dept: searchTerm
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.departments) {
                        renderDepartments(response.departments, nodeId);
                    }
                },
                error: function() {
                    $('#departmentsList').html(`
                    <div class="no-results">
                        Ошибка загрузки подразделений
                    </div>
                `);
                }
            });
        }

        // Отображение списка подразделений
        function renderDepartments(departments, nodeId) {
            if (!departments || departments.length === 0) {
                $('#departmentsList').html(`
                <div class="no-results">
                    <i class="fas fa-search"></i><br>
                    Подразделения не найдены. Попробуйте другой поисковый запрос.
                </div>
            `);
                return;
            }

            let html = '<div class="department-list">';
            let boundCount = 0;

            departments.forEach(function(dept) {
                const isBound = dept.nodes && dept.nodes.includes(parseInt(nodeId));
                if (isBound) boundCount++;

                html += `
                <div class="department-item ${isBound ? 'bound' : ''}" data-dept-id="${dept.id}">
                    <div class="department-info">
                        <div class="department-name">
                            ${escapeHtml(dept.name)}
                        </div>
                        <div class="node-count">
                            <i class="fas fa-server"></i>
                            Привязано узлов: ${dept.nodes ? dept.nodes.length : 0}
                        </div>
                    </div>

                    <div class="department-actions">
                        ${isBound ?
                    `<span style="color: #28a745; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                                <i class="fas fa-check-circle"></i> Привязан
                            </span>` :
                    `<button class="btn btn-primary btn-sm bind-to-dept-btn"
                                    data-node-id="${nodeId}"
                                    data-dept-id="${dept.id}"
                                    data-dept-name="${escapeHtml(dept.name)}">
                                <i class="fas fa-link"></i> Привязать
                            </button>`
                }
                    </div>
                </div>
            `;
            });

            html += '</div>';
            $('#departmentsList').html(html);

            // Показать информацию о привязках узла
            if (boundCount > 0) {
                $('#nodeStatusInfo').html(`
                <div class="alert alert-info" style="margin-top: 10px;">
                    <i class="fas fa-info-circle"></i>
                    Узел привязан к ${boundCount} подразделению(ям)
                </div>
            `);
            } else {
                $('#nodeStatusInfo').html('');
            }

            // Обработчик кнопки привязки к подразделению
            $('.bind-to-dept-btn').on('click', function() {
                const nodeId = $(this).data('node-id');
                const deptId = $(this).data('dept-id');
                const deptName = $(this).data('dept-name');

                bindNodeToDepartment(nodeId, deptId, deptName);
            });
        }

        // Привязка узла к подразделению
        function bindNodeToDepartment(nodeId, deptId, deptName) {
            const $btn = $(`.bind-to-dept-btn[data-node-id="${nodeId}"][data-dept-id="${deptId}"]`);
            const originalText = $btn.html();

            $btn.prop('disabled', true).html('<div class="loading" style="width: 16px; height: 16px; border-width: 2px;"></div>');
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    ajax: 1,
                    action: 'bind_node',
                    node_id: nodeId,
                    department_id: deptId
                },
                dataType: 'json',
                success: function(response) {

                    if (response.success) {
                        showToast(`Узел успешно привязан к "${deptName}"`, 'success');

                        // Обновляем список подразделений
                        if (currentSelectedNodeId == nodeId) {
                            loadDepartments(nodeId, $('#departmentSearch').val());
                        }

                        // Обновляем статистику
                        updateStats();


                        // Обновляем UI левой панели
                        updateNodeUI(nodeId);

                        // Обновляем фильтр "непривязанные"
                        updateUnboundFilter();
                    } else {
                        showToast(response.message || 'Ошибка привязки', 'error');
                        $btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    showToast('Ошибка сервера', 'error');
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        }

        // Обработчик кнопки отвязки
        $(document).on('click', '.unbind-btn', function() {
            const nodeId = $(this).data('node-id');
            const nodeName = $(this).data('node-name');

            if (confirm(`Отвязать узел "${nodeName}" от всех подразделений?`)) {
                unbindNode(nodeId, nodeName);
            }
        });

        // Отвязка узла
        function unbindNode(nodeId, nodeName) {
            const $btn = $(`.unbind-btn[data-node-id="${nodeId}"]`);
            const originalText = $btn.html();

            $btn.prop('disabled', true).html('<div class="loading" style="width: 16px; height: 16px; border-width: 2px;"></div>');

            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    ajax: 1,
                    action: 'unbind_all',
                    node_id: nodeId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast(`Узел "${nodeName}" отвязан от всех подразделений`, 'success');

                        // Обновляем список подразделений если узел выбран
                        if (currentSelectedNodeId == nodeId) {
                            loadDepartments(nodeId, $('#departmentSearch').val());
                        }

                        // Обновляем статистику
                        updateStats();

                        // Обновляем UI левой панели
                        updateNodeUI(nodeId);

                        // Обновляем фильтр "непривязанные"
                        updateUnboundFilter();
                    } else {
                        showToast(response.message || 'Ошибка отвязки', 'error');
                        $btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    showToast('Ошибка сервера', 'error');
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        }

        // Обновление UI узла
        function updateNodeUI(nodeId) {
            const $nodeItem = $(`.node-item[data-node-id="${nodeId}"]`);
            const $btn = $(`.bind-btn[data-node-id="${nodeId}"]`);
            const nodeName = $btn.data('node-name');
            const nodeGroup = $btn.data('node-group');

            // Запрашиваем обновленные данные об узле
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    ajax: 1,
                    action: 'get_departments',
                    search_dept: ''
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.departments) {
                        let isBound = false;
                        response.departments.forEach(function(dept) {
                            if (dept.nodes && dept.nodes.includes(parseInt(nodeId))) {
                                isBound = true;
                            }
                        });

                        // Обновляем состояние узла
                        if (isBound) {
                            $nodeItem.removeClass('unbound');
                            $nodeItem.data('bound', 1);
                            $nodeItem.find('.unbound-badge').remove();

                            // Добавляем кнопку отвязки если её нет
                            if (!$nodeItem.find('.unbind-btn').length) {
                                $nodeItem.find('.node-actions').append(`
                                <button class="btn btn-danger btn-sm unbind-btn"
                                        data-node-id="${nodeId}"
                                        data-node-name="${nodeName}">
                                    <i class="fas fa-unlink"></i> Отвязать
                                </button>
                            `);
                            }
                        } else {
                            $nodeItem.addClass('unbound');
                            $nodeItem.data('bound', 0);
                            if (!$nodeItem.find('.unbound-badge').length) {
                                $nodeItem.find('.node-name').append(
                                    '<span class="unbound-badge">Нет привязки</span>'
                                );
                            }
                            $nodeItem.find('.unbind-btn').remove();
                        }
                    }
                }
            });
        }

        // Обновление статистики
        function updateStats() {
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    ajax: 1,
                    action: 'get_stats'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.stats) {
                        console.log(response.stats)
                        $('#totalNodes').text(response.stats.total);
                        $('#boundNodes').text(response.stats.bound);
                        $('#unboundNodes').text(response.stats.unbound);

                        // Обновляем бейдж непривязанных узлов
                        const $unboundTab = $('.filter-tab[data-filter="unbound"]');
                        const $badge = $unboundTab.find('.node-count-badge');

                        if (response.stats.unbound > 0) {
                            if ($badge.length) {
                                $badge.text(response.stats.unbound);
                            } else {
                                $unboundTab.append(
                                    `<span class="node-count-badge">${response.stats.unbound}</span>`
                                );
                            }
                        } else {
                            $badge.remove();
                        }
                    }
                }
            });
        }

        // Обновление фильтра "непривязанные"
        function updateUnboundFilter() {
            const activeFilter = $('.filter-tabs button.active').data('filter');
            if (activeFilter === 'unbound') {
                applyNodeFilter('unbound');
            }
        }

        // Показ уведомлений
        function showToast(message, type = 'success') {
            const toast = $('#toast');
            toast.removeClass('error');

            if (type === 'error') {
                toast.addClass('error');
            }

            toast.text(message).addClass('show');

            setTimeout(() => {
                toast.removeClass('show');
            }, 3000);
        }

        // Экранирование HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
</script>
</body>
</html>