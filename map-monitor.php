<?php
// Подключение к базе данных через Medoo
require_once 'vendor/autoload.php';
include "includes/db.php";
require_once "includes/auth_check.php";
use Medoo\Medoo;

// Обработка скрытия/показа подразделения
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["type"]) && $_POST["type"] == "toggleVisibility") {
    try {
        $divisionId = (int)$_POST['divisionId'];

        if ($divisionId <= 0) {
            throw new Exception('Неверный ID подразделения');
        }

        // Получаем текущее значение is_hidden
        $current = CORE::$db->get("department", "is_hidden", ["id" => $divisionId]);

        if ($current === false) {
            throw new Exception('Подразделение не найдено');
        }

        // Инвертируем значение
        $newValue = $current == 1 ? 0 : 1;

        // Обновляем в базе данных
        $result = CORE::$db->update(
            "department",
            ["is_hidden" => $newValue],
            ["id" => $divisionId]
        );

        if ($result === false) {
            throw new Exception('Ошибка обновления в базе данных');
        }

        echo json_encode([
            'success' => true,
            'newValue' => $newValue
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Ошибка сервера: ' . $e->getMessage()
        ]);
        error_log("Toggle visibility error: " . $e->getMessage());
    }
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["type"]) && $_POST["type"] == "updateStatusSystem"){

    if ($_POST['type'] !== 'updateStatusSystem' ||
        !isset($_POST['divisionId'], $_POST['system'], $_POST['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверные данные запроса']);
        exit;
    }

    try {
        $divisionId = (int)$_POST['divisionId'];
        $system = $_POST['system'];
        if($system == "ats")
            $system = "manual_ats";
        $status = (int)$_POST['status'];

        // Безопасное имя поля
        $system = preg_replace('/[^a-zA-Z0-9_]/', '', $system);

        if ($divisionId <= 0 || empty($system)) {
            throw new Exception('Неверные параметры');
        }

        // Выполняем обновление
        $result = CORE::$db->update(
            "department",
            ["state_{$system}" => $status],
            ["id" => $divisionId]
        );

        if ($result === false) {
            throw new Exception('Ошибка обновления в базе данных');
        }

        // Успешный ответ
        echo json_encode([
            'success' => true,
            'affected_rows' => CORE::$db->rowCount()
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Ошибка сервера: ' . $e->getMessage()
        ]);
        error_log("Update error: " . $e->getMessage());
    }
}

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Карта подразделений МЧС</title>
    <link rel="stylesheet" href="/js/dist/leaflet.css" />
    <link rel="stylesheet" href="/css/fontawesome-free-6.7.2-web/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        /* Стиль для скрытого подразделения в списке */
        .division-item.division-hidden {
            background: rgba(255, 255, 255, 0.05);
            opacity: 0.6;
        }

        .division-item.division-hidden .division-name {
            color: #aaa;
        }

        /* Стиль для кнопки скрытия */
        .action-btn.hide {
            background: rgba(155, 89, 182, 0.2);
        }

        .action-btn.hide:hover {
            background: #9b59b6;
        }

        /* Стиль для скрытого маркера */
        .division-marker.hidden-marker {
            opacity: 0.3;
        }

        .division-marker.hidden-marker .division-circle {
            filter: grayscale(1);
        }
.leaflet-control-attribution{
    display: none;
}
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }
        .subplans-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .subplan-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .subplan-item:hover {
            transform: translateX(5px);
            border-color: #3498db;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .subplan-info {
            flex: 1;
        }

        .subplan-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .subplan-date {
            font-size: 13px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .subplan-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-inprogress {
            background: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        /* В разделе стилей модального окна добавьте: */

        .status-control-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: center;
        }

        .status-btn {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            min-width: 100px;
            color: white;
            font-size: 14px;
        }

        .status-btn-unknown {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
        }

        .status-btn-working {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }

        .status-btn-problems {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }

        .status-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .status-btn.active {
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.3);
            transform: scale(0.98);
        }
        #map {
            position: absolute;
            top: 0;
            left: 350px;
            right: 0;
            bottom: 0;
            transition: left 0.3s ease;
        }

        .map-fullscreen {
            left: 0 !important;
        }

        /* Кнопка открытия панели (видна когда панель скрыта) */
        .open-panel-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: white;
            border: none;
            border-radius: 8px;
            padding: 12px 15px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
        }

        .open-panel-btn.visible {
            opacity: 1;
            visibility: visible;
        }

        .open-panel-btn:hover {
            background: #f8f9fa;
        }

        /* Панель управления */
        .control-panel {
            position: absolute;
            top: 0;
            left: 0;
            width: 400px;
            height: 100%;
            background: linear-gradient(135deg, #2c3e50 0%, #1a1a2e 100%);
            color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .panel-collapsed {
            transform: translateX(-400px);
        }

        .panel-header {
            padding: 15px 20px;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-header-content {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 100%;
        }

        .back-to-site {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .back-to-site:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .panel-title {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .toggle-panel {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .toggle-panel:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .panel-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        /* Поиск */
        .search-container {
            margin-bottom: 20px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #4dabf7;
            background: rgba(255, 255, 255, 0.15);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
        }

        /* Секции панели */
        .panel-section {
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 500;
            color: #74b9ff;
        }

        .section-title i {
            font-size: 18px;
        }

        /* Список подразделений */
        .division-list {
            max-height: 500px;
            overflow-x: hidden;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .division-item {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 12px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
            cursor: pointer;
        }

        .division-item:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateX(5px);
        }

        .division-info {
            flex: 1;
        }

        .division-name {
            font-weight: 500;
            margin-bottom: 8px;
            color: #f1f1f1;
        }

        .division-status-icons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .status-icon {
            font-size: 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
        }

        .status-icon i {
            font-size: 16px;
        }

        .coordinator-icon.active-0 { color: #ff4444; }
        .coordinator-icon.active-1 { color: #44ff44; }
        .coordinator-icon.active-2 { color: #ffffff; }

        .ats-icon.active-0 { color: #ff4444; }
        .ats-icon.active-1 { color: #44ff44; }
        .ats-icon.active-2 { color: #ffffff; }

        .radio-icon.active-0 { color: #ff4444; }
        .radio-icon.active-1 { color: #44ff44; }
        .radio-icon.active-2 { color: #ffffff; }

        .fxo-icon.active-0 { color: #ff4444; }
        .fxo-icon.active-1 { color: #44ff44; }
        .fxo-icon.active-2 { color: #ffffff; }

        .status-label {
            font-size: 8px;
            opacity: 0.8;
        }

        .division-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: #4dabf7;
            transform: scale(1.1);
        }

        .action-btn.info {
            background: rgba(45, 206, 137, 0.2);
        }

        .action-btn.info:hover {
            background: #2dce89;
        }

        .action-btn.focus {
            background: rgba(77, 171, 247, 0.2);
        }

        .action-btn.focus:hover {
            background: #4dabf7;
        }

        /* Управление отображаемыми системами - Круг с 4 частями */
        .system-circle-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .system-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            cursor: pointer;
        }

        .system-quarter {
            position: absolute;
            width: 50%;
            height: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            opacity: 0.7;
        }

        .system-quarter:hover {
            opacity: 1;
            font-weight:bold;
        }

        .system-quarter.active {
            opacity: 1;
        }

        .system-quarter .system-wrap{
            display: flex;
            flex-direction: column;
            box-sizing: content-box;
            position: absolute;
            height: 50px;
        }
        .system-quarter-4 .system-wrap{

            right: 15px;
            top: 10px;
        }
        .system-quarter-3 .system-wrap{
            left: 15px;
            top: 10px;
        }
        .system-quarter-2 .system-wrap{
            bottom: 4px;
            left: 18px;
        }
        .system-quarter-1 .system-wrap{
            bottom: 4px;
            right: 19px;
        }
        .system-quarter span{

            position: absolute;
            bottom: 8px;
            font-size: 12px;
        }
        .system-quarter-1, .system-quarter-4, .system-quarter-2, .system-quarter-3 {
            opacity: 0.3;

            display: flex;
            flex-direction: column;
        }

        .system-quarter-1 { top: 0; left: 0;
            border-right: 1px solid white;
        }
        .system-quarter-2 { top: 0; right: 0; }
        .system-quarter-3 { bottom: 0; right: 0;    border-top: 1px solid white;}
        .system-quarter-4 { bottom: 0; left: 0;
            border-right: 1px solid white;
            border-top: 1px solid white;}


        .system-quarter.active.system-quarter-1,.system-quarter.active.system-quarter-2,.system-quarter.active.system-quarter-3,.system-quarter.active.system-quarter-4 {
            background-color:rgb(75 ,77 ,101);
        }

        .system-quarter i {
            font-size: 24px;
            color: white;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
        }

        .system-labels {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            width: 100%;
        }

        .system-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            padding: 8px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.05);
            justify-content: center;
        }

        .system-label i {
            font-size: 16px;
        }

        /* Фильтры статусов */
        .status-filters {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
        }

        .filter-select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 14px;
        }

        .filter-select option {
            background: #2c3e50;
        }

        /* Кнопки действий */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4dabf7 0%, #339af0 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #339af0 0%, #228be6 100%);
            transform: translateY(-2px);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ff6b6b 0%, #fa5252 100%);
            color: white;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #fa5252 0%, #e03131 100%);
            transform: translateY(-2px);
        }

        /* Кнопки управления картой */
        .map-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .layer-control {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .layer-btn {
            padding: 12px 20px;
            border: none;
            background: white;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            width: 150px;
        }

        .layer-btn:hover {
            background: #f8f9fa;
        }

        .layer-btn.active {
            background: #4dabf7;
            color: white;
        }

        .layer-btn i {
            font-size: 18px;
        }

        .map-action-btn {
            background: rgb(75, 77 ,101);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 12px 20px;
            border: none;
            cursor: pointer;
            margin: auto;
            margin-bottom: 15px;
            color: white;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
        }

        .map-action-btn:hover {
            background: rgb(49 , 50 ,65);
        }
        /* Тонкий темно-синий скроллбар */
        ::-webkit-scrollbar {
            width: 6px; /* Толщина вертикального скроллбара */
            height: 6px; /* Толщина горизонтального скроллбара */
        }

        ::-webkit-scrollbar-track {
            background: #64809a; /* Цвет трека */
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: #1e3a8a; /* Темно-синий цвет */
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #1e40af; /* Более светлый синий при наведении */
        }
        /* Маркеры */

        .division-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid #2c3e50;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        #map.division-circle-s1 .division-circle{
            width: 35px;
            height: 35px;
        }
        #map.division-circle-s2 .division-circle{
            width: 30px;
            height: 30px;
            border: 2px solid #2c3e50;
        }
        #map.division-circle-s3 .division-circle{
            width: 25px;
            height: 25px;
            border: 2px solid #2c3e50;
        }
        #map.division-circle-s4 .division-circle{
            width: 20px;
            height: 20px;
            border: 1px solid #2c3e50;
        }

        .quarter {
            position: absolute;
            transition: all 0.3s;
        }

        .quarter-1 { top: 0; left: 0; width: 50%; height: 50%; }
        .quarter-2 { top: 0; right: 0; width: 50%; height: 50%; }
        .quarter-3 { bottom: 0; right: 0; width: 50%; height: 50%; }
        .quarter-4 { bottom: 0; left: 0; width: 50%; height: 50%; }

        .status-0 { background-color: #ff4444; } /* Неполадки */
        .status-1 { background-color: #44ff44; } /* Работает */
        .status-2 { background-color: #ffffff; } /* Неизвестно */

        .division-label {
            position: absolute;
            top: 45px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(44, 62, 80, 0.95);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            pointer-events: none;
            z-index: 1;
            transition: opacity 0.3s;
        }
        #map.division-circle-s4 .division-label ,
        #map.division-circle-s3 .division-label ,
        #map.division-circle-s2 .division-label {
            padding: 0px 2px;
            font-size: 10px;
        }
        .labels-hidden .division-label {
            opacity: 0;
            pointer-events: none;
        }

        /* Легенда статусов */
        .status-legend {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            padding: 5px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.05);
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        /* Модальное окно */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            width: 90%;
            max-width: 800px;
            max-height: 90%;
            background: white;
            display: flex;
            border-radius: 15px;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            transform: translateY(30px);
            transition: transform 0.4s;
        }

        .modal-overlay.active .modal {
            transform: translateY(0);
        }

        .modal-header {
            background: linear-gradient(135deg, #2c3e50 0%, #1a1a2e 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
        }

        .modal-content {
            padding: 25px;
            overflow-y: auto;
        }

        .modal-section {
            margin-bottom: 25px;
        }

        .modal-section h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f1f1;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-display {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .status-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            padding: 15px;
            border-radius: 10px;
            background: #f8f9fa;
        }

        .status-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }

        .status-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .placeholder-data {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            color: #666;
            font-style: italic;
            margin: 20px 0;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .control-panel {
                width: 300px;
            }

            #map {
                left: 300px;
            }

            .system-labels {
                grid-template-columns: 1fr;
            }

            .open-panel-btn {
                left: 10px;
                top: 10px;
                padding: 10px 12px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<!-- Кнопка открытия панели (видна когда панель скрыта) -->
<button class="open-panel-btn" id="openPanelBtn">
    <i class="fas fa-bars"></i> Меню
</button>

<!-- Панель управления -->
<div class="control-panel" id="controlPanel">
    <div class="panel-header">
        <div class="panel-header-content">
            <button class="back-to-site" id="backToSite">
                <i class="fas fa-arrow-left"></i> Вернуться
            </button>
            <div class="panel-title">
                <i class="fas fa-fire-extinguisher"></i> Подразделения
            </div>
            <button class="toggle-panel" id="togglePanel">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
    </div>

    <div class="panel-content">
        <!-- Поиск -->
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" id="searchInput" placeholder="Поиск подразделения...">
        </div>

        <!-- Список подразделений -->
        <div class="panel-section">
            <div class="section-title">
                <i class="fas fa-list"></i>
                <span>Список подразделений</span>
                <span class="status-count" id="divisionCount">5</span>
            </div>
            <div class="division-list" id="divisionList">
                <!-- Список будет заполнен JavaScript -->
            </div>
        </div>

        <!-- Управление отображаемыми системами -->
        <div class="panel-section">
            <div class="section-title">
                <i class="fas fa-layer-group"></i>
                <span>Отображаемые системы</span>
            </div>
            <button class="map-action-btn" id="toggleLabelsBtn">
                <i class="fas fa-tag"></i> Подписи меток: ВКЛ
            </button>
            <div class="system-circle-container">
                <div class="system-circle" id="systemCircle">
                    <div class="system-quarter system-quarter-1 active" data-system="coordinator">
                        <div class="system-wrap">
                            <i class="fas fa-satellite-dish"></i><span>Коорд.</span></div>
                    </div>
                    <div class="system-quarter system-quarter-2 active" data-system="ats">
                        <div class="system-wrap">
                            <i class="fas fa-phone-alt"></i>
                            <span>АТС</span>
                        </div>
                    </div>
                    <div class="system-quarter system-quarter-3 active" data-system="radio">
                        <div class="system-wrap">
                            <i class="fas fa-broadcast-tower"></i>
                            <span>Радио</span>
                        </div>
                    </div>
                    <div class="system-quarter system-quarter-4 active" data-system="fxo">
                        <div class="system-wrap">
                            <i class="fas fa-phone"></i>
                            <span>FXO</span>
                        </div>
                    </div>
                </div>

                <div class="system-labels">
                    <div class="system-label">
                        <i class="fas fa-satellite-dish"></i>
                        <span>Координаторы</span>
                    </div>
                    <div class="system-label">
                        <i class="fas fa-phone-alt"></i>
                        <span>АТС</span>
                    </div>
                    <div class="system-label">
                        <i class="fas fa-broadcast-tower"></i>
                        <span>Радиосвязь</span>
                    </div>
                    <div class="system-label">
                        <i class="fas fa-phone"></i>
                        <span>FXO</span>
                    </div>
                </div>
            </div>

            <!-- Легенда статусов -->
            <div class="status-legend">
                <div class="legend-item">
                    <div class="legend-color status-0"></div>
                    <span>Неполадки</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color status-1"></div>
                    <span>Работает</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color status-2"></div>
                    <span>Неизвестно</span>
                </div>
            </div>
        </div>

        <!-- Фильтры статусов -->
        <div class="panel-section">
            <div class="section-title">
                <i class="fas fa-filter"></i>
                <span>Фильтрация меток</span>
            </div>
            <div class="status-filters">
                <!-- Фильтр для каждой системы -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-satellite-dish"></i>
                        Координаторы:
                    </label>
                    <select class="filter-select" id="filterCoordinator">
                        <option value="all">Все</option>
                        <option value="1">Работает</option>
                        <option value="0">Неполадки</option>
                        <option value="2">Неизвестно</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-phone-alt"></i>
                        АТС:
                    </label>
                    <select class="filter-select" id="filterATS">
                        <option value="all">Все</option>
                        <option value="1">Работает</option>
                        <option value="0">Неполадки</option>
                        <option value="2">Неизвестно</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-broadcast-tower"></i>
                        Радиосвязь:
                    </label>
                    <select class="filter-select" id="filterRadio">
                        <option value="all">Все</option>
                        <option value="1">Работает</option>
                        <option value="0">Неполадки</option>
                        <option value="2">Неизвестно</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-phone"></i>
                        FXO:
                    </label>
                    <select class="filter-select" id="filterFXO">
                        <option value="all">Все</option>
                        <option value="1">Работает</option>
                        <option value="0">Неполадки</option>
                        <option value="2">Неизвестно</option>
                    </select>
                </div>
            </div>

            <!-- Кнопки действий -->
            <div class="action-buttons">
                <button class="btn btn-primary" id="applyFilters">
                    <i class="fas fa-check"></i> Применить фильтры
                </button>
                <button class="btn btn-warning" id="resetFilters">
                    <i class="fas fa-redo"></i> Сбросить все фильтры
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Карта -->
<div id="map" class="labels-visible"></div>

<!-- Управление слоями карты -->
<div class="map-controls">
    <div class="layer-control">
        <button class="layer-btn active" data-layer="streets">
            <i class="fas fa-map"></i> Схема
        </button>
        <button class="layer-btn" data-layer="satellite">
            <i class="fas fa-satellite"></i> Спутник
        </button>
        <button class="layer-btn" data-layer="hybrid">
            <i class="fas fa-layer-group"></i> Гибрид
        </button>
    </div>


</div>

<!-- Модальное окно -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Информация о подразделении</h3>
            <button class="close-modal" id="closeModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-content" id="modalContent">
            <!-- Контент будет заполнен JavaScript -->
        </div>
    </div>
</div>

<script src="/js/dist/leaflet.js"></script>
<script>
    // Начальные координаты
    const initialCoords = [48.563665, 39.311153];
    // В начало раздела JavaScript, после let markers = [];
    let hiddenDivisions = {};
    // Функция переключения видимости подразделения
    function toggleDivisionVisibility(divisionId, buttonElement) {
        // Находим подразделение
        const division = divisions.find(d => d.id === divisionId);
        if (!division) return;

        // Отправляем запрос на сервер
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                type: 'toggleDivisionVisibility',
                divisionId: divisionId
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Обновляем свойство подразделения
                    division.is_hidden = data.newValue;

                    // Обновляем иконку кнопки
                    const icon = buttonElement.querySelector('i');
                    if (division.is_hidden) {
                        icon.className = 'fas fa-eye-slash';
                        buttonElement.title = 'Показать подразделение';
                    } else {
                        icon.className = 'fas fa-eye';
                        buttonElement.title = 'Скрыть подразделение';
                    }

                    // Обновляем стиль элемента в списке
                    const divisionItem = buttonElement.closest('.division-item');
                    if (division.is_hidden) {
                        divisionItem.classList.add('division-hidden');
                    } else {
                        divisionItem.classList.remove('division-hidden');
                    }

                    // Обновляем маркеры на карте
                    updateMarkers();
                } else {
                    alert('Ошибка при обновлении: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Ошибка сети');
            });
    }
    // Типы карт
    const mapLayers = {
        streets: {
            url: 'http://map.mchs.lnr/tile/{z}/{x}/{y}.png',
            crs: L.CRS.EPSG3857,
            attribution: false,
            layers: []
        },
        satellite: {
            url: 'http://map.mchs.lnr/downloaded/tiles/satellite/{z}/{x}/{y}.jpg',
            crs: L.CRS.EPSG3395,
            attribution: false,
            layers: []
        },
        hybrid: {
            url: 'http://map.mchs.lnr/downloaded/tiles/satellite/{z}/{x}/{y}.jpg',
            overlay: 'http://map.mchs.lnr/downloaded/tiles/hybrid/{z}/{x}/{y}.jpg',
            crs: L.CRS.EPSG3395,
            attribution: false,
            layers: []
        }
    };
<?PHP

$allDepartments = CORE::$db->select("department",["name","id","lat","lng","addr","state_fxo","state_radio",'is_hidden','state_manual_ats'], [
    'ORDER' => ['through_sort' => 'ASC']
]);
$sortedDeps = [];
$i = 0;


// Получение недоступных хостов
function getUnavailableHosts( $maxAgeHours = 2) {
    $maxAgeSeconds = $maxAgeHours * 3600;

    $sql = "
    SELECT DISTINCT
        h.hostid
    FROM hosts h
    JOIN hosts_groups hg ON h.hostid = hg.hostid
    JOIN hstgrp g ON hg.groupid = g.groupid
    JOIN items i ON h.hostid = i.hostid
        AND i.key_ IN ('icmpping', 'icmppingsec', 'agent.ping')
        AND i.status = 0
        AND i.value_type IN (0, 3)
    JOIN history_uint hu ON i.itemid = hu.itemid
    WHERE
        g.name IN ('ATS','Repeaters','Coordinators')
        AND h.status = 0
        AND hu.value = 0
        AND hu.clock = (
            SELECT MAX(clock)
            FROM history_uint hu2
            WHERE hu2.itemid = hu.itemid
            AND hu2.clock > UNIX_TIMESTAMP() - $maxAgeSeconds
        )
        AND hu.clock > UNIX_TIMESTAMP() - $maxAgeSeconds
    ORDER BY
        FIELD(g.name, 'Coordinators', 'ATS'),
        h.host";

    return CORE::$dbZabbix->query($sql)->fetchAll(PDO::FETCH_COLUMN, 0);
}

$allNodes = CORE::$dbZabbix->query("SELECT h.hostid as id, GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as `group` FROM hosts h LEFT JOIN hosts_groups hg ON h.hostid = hg.hostid LEFT JOIN hstgrp g ON hg.groupid = g.groupid LEFT JOIN items i ON h.hostid = i.hostid AND i.status = 0 WHERE h.status = 0     AND h.flags IN (0, 4)     AND h.host NOT LIKE '{#%}'     AND h.name NOT LIKE '{#%}'     AND (g.internal = 0 OR g.internal IS NULL) GROUP BY h.hostid, h.host, h.name, h.status HAVING `group` IS NOT NULL and `group` in ('ATS','Repeaters','Coordinators') ORDER BY h.name;")->fetchAll();
$allNodes = array_column($allNodes, 'group', 'id');
$unavailableHosts = getUnavailableHosts();


foreach ($allDepartments as $dep){
    if(is_null($dep["lat"]) || is_null($dep["lng"])){
        $dep["lat"] = 0;
        $dep["lng"] = 0;
    }
   $dep["address"] = $dep["addr"];
   $dep["id"] = intval($dep["id"]);
   $dep["lat"] = floatval($dep["lat"]);
   $dep["lng"] = floatval($dep["lng"]);
   $dep["is_hidden"] = intval($dep["is_hidden"]);
   $coordinatorState = 2;
   $AtsState = 2;

   $AtsStateIsManual = true;///Какой статус будет браться, автоматом из заббикса или вручную, если нет привязки к узлу с группой АТС то будет ручной


    foreach (CORE::$db->select("department_to_zabbix_node","zabbix_hosts_hostid",["department_id"=>$dep["id"]]) as $hostid){
        if(isset($allNodes[$hostid]) && $allNodes[$hostid] == "Coordinators")
            $coordinatorState =  in_array($hostid,$unavailableHosts)?0:1;

        if(isset($allNodes[$hostid]) && $allNodes[$hostid] == "ATS") {
            $AtsState = in_array($hostid, $unavailableHosts) ? 0 : 1;
            $AtsStateIsManual = false;
        }
    }

    if($AtsStateIsManual){
        $AtsState = intval($dep["state_manual_ats"]);
    }

    $dep["plans"] =  CORE::$db->query("select id,name,date_type,date_value,status from plan left JOIN department_to_plan as dtp ON plan.id = dtp.plan_id WHERE dtp.department_id  = $dep[id] ORDER BY create_at DESC")->fetchAll();

   $dep["ats_state_is_manual"] = $AtsStateIsManual;
   $dep["status"] = [
       "coordinator"=>$coordinatorState,
       "ats"=>$AtsState,
       "fxo"=>intval($dep["state_fxo"]),
       "radio"=>intval($dep["state_radio"])
   ];
    array_push($sortedDeps,$dep);
}

?>
    // Данные подразделений с новыми названиями статусов
    let divisions = <?= json_encode($sortedDeps,JSON_UNESCAPED_UNICODE) ?>;
    let currentMap = null;
    let currentLayer = 'streets';
    let currentCenter = initialCoords;
    let currentZoom = 13;
    let markers = [];
    let showLabels = true;
    let panelCollapsed = false;

    // Фильтры для каждой системы
    let activeFilters = {
        coordinator: 'all',
        ats: 'all',
        radio: 'all',
        fxo: 'all'
    };

    // Какие системы отображать в круге
    let visibleSystems = {
        coordinator: true,
        ats: true,
        radio: true,
        fxo: true
    };

    // Названия систем для отображения
    const systemNames = {
        coordinator: 'Координаторы',
        ats: 'АТС',
        radio: 'Радиосвязь',
        fxo: 'FXO'
    };

    // Названия статусов
    const statusNames = {
        0: 'Неполадки',
        1: 'Работает',
        2: 'Неизвестно'
    };

    // Иконки для систем
    const systemIcons = {
        coordinator: 'fa-satellite-dish',
        ats: 'fa-phone-alt',
        radio: 'fa-broadcast-tower',
        fxo: 'fa-phone'
    };

    // Инициализация карты
    function initMap(layerType, center = currentCenter, zoom = currentZoom) {
        const layerConfig = mapLayers[layerType];

        // Удаляем старую карту если она существует
        if (currentMap) {
            currentMap.remove();
        }

        // Создаем новую карту с правильным CRS
        currentMap = L.map('map', {
            crs: layerConfig.crs,
            attribution: false,
            zoomControl: false, // Отключаем кнопки приближения
            zoomSnap: 0.5,
            zoomDelta: 0.5
        }).setView(center, zoom);

        currentMap.on('zoomend', function(e) {
            const currentZoom = currentMap.getZoom();
            console.log('Zoom изменился на:', currentZoom);
            let map  = document.querySelector("#map");
            map.classList.remove("division-circle-s1");
            map.classList.remove("division-circle-s2");
            map.classList.remove("division-circle-s3");
            map.classList.remove("division-circle-s4");
            if(currentZoom <= 11 && currentZoom > 10 )
                map.classList.add("division-circle-s1");
            else if(currentZoom <= 10  && currentZoom > 9 )
                map.classList.add("division-circle-s2");
            else if(currentZoom <= 9 && currentZoom > 8)
                map.classList.add("division-circle-s3");
            else if(currentZoom <= 8 )
                map.classList.add("division-circle-s4");

        });
        // Добавляем основной слой
        const baseLayer = L.tileLayer(layerConfig.url, {
            attribution: false
        }).addTo(currentMap);

        // Для гибридного слоя добавляем полупрозрачный слой поверх
        if (layerType === 'hybrid') {
            const overlayLayer = L.tileLayer(layerConfig.overlay, {
                attribution: false,
            }).addTo(currentMap);
            layerConfig.layers = [baseLayer, overlayLayer];
        } else {
            layerConfig.layers = [baseLayer];
        }

        // Добавляем маркеры
        addDivisionMarkers();

        // Сохраняем текущий слой
        currentLayer = layerType;

        // Обновляем активную кнопку слоя
        updateLayerButtons(layerType);

        // Обновляем подписи
        updateLabels();

        // Обновляем кнопки системы
        updateSystemCircle();

        // Обработчик кликов по кнопкам статуса (делегирование событий)
        document.getElementById('modalContent').addEventListener('click', function(e) {
            if (e.target.classList.contains('status-btn')) {
                const btn = e.target;
                const system = btn.dataset.system;
                const status = parseInt(btn.dataset.status);
                const divisionId = parseInt(btn.dataset.divisionId);

                // Находим подразделение
                const division = divisions.find(d => d.id === divisionId);
                if (!division) return;

                // Обновляем статус локально
                division.status[system] = status;

                // Отправляем AJAX запрос (заглушка)
                updateSystemStatus(divisionId, system, status);

                // Обновляем отображение в модальном окне
                openModal(division);

                // Обновляем маркер на карте
                updateDivisionMarker(division);
            }
        });

// Функция для обновления маркера конкретного подразделения
        function updateDivisionMarker(division) {
            // Находим и удаляем старый маркер
            const markerIndex = markers.findIndex(m =>
                m.getLatLng().lat === division.lat &&
                m.getLatLng().lng === division.lng
            );

            if (markerIndex !== -1 && currentMap) {
                currentMap.removeLayer(markers[markerIndex]);
                markers.splice(markerIndex, 1);

                // Создаем и добавляем новый маркер
                const newMarker = createDivisionMarker(division);
                newMarker.addTo(currentMap);
                markers.push(newMarker);
            }
        }
        function updateSystemStatus(divisionId, system, status) {
            // Данные для отправки
            var postData = {
                type: "updateStatusSystem",
                divisionId: divisionId,
                system: system,
                status: status
            };

            // Создаем запрос
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', window.location.href, true);

            // Устанавливаем заголовок
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            // Обработка ответа
                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                try {
                                    var response = JSON.parse(xhr.responseText);
                                    console.log('Успешно:', response);
                                } catch (e) {
                                }
                            } else {
                                alert('Ошибка:', xhr.status);
                            }
                        };

            // Обработка ошибок сети
                        xhr.onerror = function() {
                            alert('Ошибка сети!');
                        };

            // Преобразуем объект в строку параметров и отправляем
                        var params = new URLSearchParams(postData).toString();
                        xhr.send(params);
        }


        // Обработчик кликов по кнопкам статуса (делегирование событий)
        document.getElementById('modalContent').addEventListener('click', function(e) {
            if (e.target.classList.contains('status-btn')) {
                const btn = e.target;
                const system = btn.dataset.system;
                const status = parseInt(btn.dataset.status);
                const divisionId = parseInt(btn.dataset.divisionId);

                // Находим подразделение
                const division = divisions.find(d => d.id === divisionId);
                if (!division) return;

                // Обновляем статус локально
                division.status[system] = status;

                // Отправляем AJAX запрос (заглушка)
                updateSystemStatus(divisionId, system, status);

                // Обновляем отображение в модальном окне
                openModal(division);

                // Обновляем маркер на карте
                updateDivisionMarker(division);
            }
        });

        // Функция для обновления маркера конкретного подразделения
                function updateDivisionMarker(division) {
                    // Находим и удаляем старый маркер
                    const markerIndex = markers.findIndex(m =>
                        m.getLatLng().lat === division.lat &&
                        m.getLatLng().lng === division.lng
                    );

                    if (markerIndex !== -1 && currentMap) {
                        currentMap.removeLayer(markers[markerIndex]);
                        markers.splice(markerIndex, 1);

                        // Создаем и добавляем новый маркер
                        const newMarker = createDivisionMarker(division);
                        newMarker.addTo(currentMap);
                        markers.push(newMarker);
                    }
                }

    }

    // Создание HTML для маркера с учетом видимых систем
    function createMarkerHTML(division) {
        const status = division.status;

        // Порядок систем в круге
        const systems = [
            { key: 'coordinator', className: 'quarter quarter-1', visible: visibleSystems.coordinator },
            { key: 'ats', className: 'quarter quarter-2', visible: visibleSystems.ats },
            { key: 'radio', className: 'quarter quarter-3', visible: visibleSystems.radio },
            { key: 'fxo', className: 'quarter quarter-4', visible: visibleSystems.fxo }
        ];

        // Считаем сколько систем видно
        const visibleCount = Object.values(visibleSystems).filter(v => v).length;

        let html = '<div class="division-circle" style="';

        // Если видна только одна система - заливаем весь круг цветом этой системы
        if (visibleCount === 1) {
            const visibleSystem = systems.find(s => s.visible);
            if (visibleSystem) {
                const statusValue = status[visibleSystem.key];
                html += `background-color: ${getStatusColor(statusValue)};`;
            }
        }

        html += '">';

        // Если видно больше одной системы - рисуем сектора
        if (visibleCount > 1) {
            systems.forEach(system => {
                if (system.visible) {
                    const statusValue = status[system.key];
                    html += `<div class="${system.className} status-${statusValue}"></div>`;
                }
            });
        }

        html += '</div>';

        // Добавляем подпись если включено
        if (showLabels) {
            html += `<div class="division-label">${division.name}</div>`;
        }

        return html;
    }

    // Получение цвета по статусу
    function getStatusColor(state) {

        switch(state) {
            case 0: return '#ff4444'; // Неполадки
            case 1: return '#44ff44'; // Работает
            case 2: return '#ffffff'; // Неизвестно
            default:
                return '#cccccc';
        }
    }

    // Создание маркера подразделения
    function createDivisionMarker(division) {
        // Проверяем, скрыто ли подразделение
        if (division.is_hidden && division.is_hidden == 1) {
            return null; // Не создаем маркер для скрытых подразделений
        }

        // Создаем контейнер для маркера
        const container = L.divIcon({
            className: 'division-marker',
            html: createMarkerHTML(division),
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        // Создаем маркер
        const marker = L.marker([division.lat, division.lng], {
            icon: container
        });

        // Добавляем обработчик клика для открытия модального окна
        marker.on('click', function() {
            openModal(division);
        });

        return marker;
    }

    // Добавление всех маркеров на карту с учетом фильтров
    function addDivisionMarkers() {
        markers.forEach(marker => {
            if (currentMap && marker) {
                currentMap.removeLayer(marker);
            }
        });

        markers = [];

        divisions.forEach(division => {
            // Применяем фильтры
            if (!shouldShowMarker(division)) {
                return;
            }

            const marker = createDivisionMarker(division);
            marker.addTo(currentMap);
            markers.push(marker);
        });

        updateDivisionCount();
    }

    // Проверка, должен ли маркер отображаться с учетом фильтров
    function shouldShowMarker(division) {
        // Если подразделение скрыто - не показываем маркер
        if (division.is_hidden && division.is_hidden == 1) {
            return false;
        }

        const status = division.status;

        // Проверяем фильтры для каждой системы
        for (const system in activeFilters) {
            if (system === 'hideStatus') continue;

            const filterValue = activeFilters[system];
            if (filterValue !== 'all' && status[system].toString() !== filterValue) {
                return false;
            }
        }

        return true;
    }

    // Обновление маркеров
    function updateMarkers() {
        // Сохраняем текущий центр и зум
        if (currentMap) {
            currentCenter = currentMap.getCenter();
            currentZoom = currentMap.getZoom();
        }

        markers.forEach(marker => {
            if (currentMap) {
                currentMap.removeLayer(marker);
            }
        });

        markers = [];
        addDivisionMarkers();

        // Восстанавливаем позицию
        if (currentMap) {
            currentMap.setView(currentCenter, currentZoom);
        }
    }

    // Обновление счетчика подразделений
    function updateDivisionCount() {
        const visibleCount = divisions.filter(division => shouldShowMarker(division)).length;
        document.getElementById('divisionCount').textContent = visibleCount;
    }

    // Обновление активных кнопок слоев
    function updateLayerButtons(activeLayer) {
        document.querySelectorAll('.layer-btn').forEach(btn => {
            if (btn.dataset.layer === activeLayer) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    // Обновление подписей
    function updateLabels() {
        const mapContainer = document.getElementById('map');
        if (showLabels) {
            mapContainer.classList.remove('labels-hidden');
            mapContainer.classList.add('labels-visible');
            document.getElementById('toggleLabelsBtn').innerHTML = '<i class="fas fa-tag"></i> Подписи меток: ВКЛ';
        } else {
            mapContainer.classList.remove('labels-visible');
            mapContainer.classList.add('labels-hidden');
            document.getElementById('toggleLabelsBtn').innerHTML = '<i class="fas fa-tag"></i> Подписи меток: ВЫКЛ';
        }

        // Обновляем маркеры чтобы применить изменения подписей
        updateMarkers();
    }

    // Обновление круга систем
    function updateSystemCircle() {
        document.querySelectorAll('.system-quarter').forEach(quarter => {
            const system = quarter.dataset.system;
            if (visibleSystems[system]) {
                quarter.classList.add('active');
            } else {
                quarter.classList.remove('active');
            }
        });
    }
    function formatDate(dateValue,dateType) {
        if (dateType === 'without') return 'Без даты';

        if (dateType === 'exact') {
            const date = new Date(dateValue);
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }

        if (dateType === 'month') {
            const date = new Date(dateValue);
            let s=  date.toLocaleDateString('ru-RU', {
                month: 'long',
                year: 'numeric'
            });
            if(s.length > 1)
                s =  s[0].toUpperCase()+s.substr(1,s.length-1);
            return s;
        }
        if (dateType === 'year') {
            const date = new Date(dateValue);
            return date.toLocaleDateString('ru-RU', {
                year: 'numeric'
            });
        }


        return dateValue;
    }
    function getStatusText(status) {
        const statusMap = {
            'pending': 'Ожидание',
            'inprogress': 'В работе',
            'completed': 'Выполнен',
            'rejected': 'Отклонен'
        };
        return statusMap[status] || status;
    }
    // Открытие модального окна
    function openModal(division) {
        const modalOverlay = document.getElementById('modalOverlay');
        const modalTitle = document.getElementById('modalTitle');
        const modalContent = document.getElementById('modalContent');

        modalTitle.textContent = division.name;

        const status = division.status;
// В функции openModal, замените секцию "Статусы систем":

        modalContent.innerHTML = `
    <div class="modal-section">
        <h4><i class="fas fa-info-circle"></i> Основная информация</h4>
        <p><strong>Адрес:</strong> ${division.address || 'Не указан'}</p>
        <p><strong>Координаты:</strong> ${division.lat.toFixed(6)}, ${division.lng.toFixed(6)}</p>
  <p><strong>Статус:</strong> ${division.is_hidden == 1 ? '<span style="color:#ff6b6b">Скрыто</span>' : '<span style="color:#2ecc71">Видимо</span>'}</p>
    </div>

    <div class="modal-section">
        <h4><i class="fas fa-chart-pie"></i> Статусы систем</h4>
        <div class="status-display">
            ${Object.entries(status).map(([system, value]) => {
            // Для систем radio и fxo добавляем кнопки управления
            if (system === 'radio' || system === 'fxo' || (system == 'ats' && division.ats_state_is_manual)) {
                return `
                        <div class="status-item">
                            <div class="status-circle" style="background-color: ${getStatusColor(value)};">
                                <i class="fas ${systemIcons[system]}"></i>
                            </div>
                            <div class="status-name">${systemNames[system]}</div>
                            <span style="font-weight: bold; color: ${getStatusTextColor(value)}">
                                ${statusNames[value]}
                            </span>

                            <!-- Кнопки управления статусом -->
                <div class="status-control-buttons">
                <button class="status-btn status-btn-unknown ${value === 2 ? 'active' : ''}"
                data-system="${system}"
                data-status="2"
                data-division-id="${division.id}">
                Неизвестно
                </button>
                <button class="status-btn status-btn-working ${value === 1 ? 'active' : ''}"
                data-system="${system}"
                data-status="1"
                data-division-id="${division.id}">
                Работает
                </button>
                <button class="status-btn status-btn-problems ${value === 0 ? 'active' : ''}"
                data-system="${system}"
                data-status="0"
                data-division-id="${division.id}">
                Неполадки
                </button>
                </div>
                </div>
                `;
                } else {
                    // Для остальных систем просто отображаем статус
                    return `
                <div class="status-item">
                <div class="status-circle" style="background-color: ${getStatusColor(value)};">
                <i class="fas ${systemIcons[system]}"></i>
                </div>
                <div class="status-name">${systemNames[system]}</div>
                <span style="font-weight: bold; color: ${getStatusTextColor(value)}">
                    ${statusNames[value]}
                </span>
                <span style="font-size: 12px;font-style: italic;color: #ababab; ">
                    Автоматом получает из Zabbix
                </span>
                </div>
                `;
                }
            }).join('')}
        </div>
    </div>

    <div class="modal-section">
        <h4><i class="fas fa-project-diagram"></i> Планы с участием подразделения</h4>
        <div class="subplans-list">
            ${
                    (function () {
                        let str = "";
                        if( division.plans.length == 0)
                            return `<h3 style="text-align: center"> <i class="fa fa-ban"></i> Привязанных планов не найдено! </h3>`;
                        for(let i= 0; i < division.plans.length;i++){
                                str+=`
                          <a href="showplan.php?id=${division.plans[i].id}" class="subplan-item">
                                    <div class="subplan-info">
                                        <div class="subplan-title">${division.plans[i].name}</div>
                                        <div class="subplan-date">
                                            <i class="far fa-calendar"></i>
                                            Дедлайн: ${formatDate(division.plans[i].date_value,division.plans[i].date_type)}
                                        </div>
                                    </div>
                                    <div class="subplan-status status-${division.plans[i].status}">
                                        ${getStatusText(division.plans[i].status)}
                                    </div>
                                </a>`;
                        }
                        return str;
                    })()
                }
        </div>
    </div>
`;

        modalOverlay.classList.add('active');
    }

    // Получение цвета текста для статуса
    function getStatusTextColor(status) {
        switch(status) {
            case 0: return '#ff4444';
            case 1: return '#44ff44';
            case 2: return '#666666';
            default: return '#cccccc';
        }
    }

    // Закрытие модального окна
    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('active');
    }

    // Переключение панели управления
    function togglePanel() {
        panelCollapsed = !panelCollapsed;
        const panel = document.getElementById('controlPanel');
        const map = document.getElementById('map');
        const toggleIcon = document.querySelector('#togglePanel i');
        const openPanelBtn = document.getElementById('openPanelBtn');

        if (panelCollapsed) {
            panel.classList.add('panel-collapsed');
            map.classList.add('map-fullscreen');
            toggleIcon.className = 'fas fa-chevron-right';
            openPanelBtn.classList.add('visible');
        } else {
            panel.classList.remove('panel-collapsed');
            map.classList.remove('map-fullscreen');
            toggleIcon.className = 'fas fa-chevron-left';
            openPanelBtn.classList.remove('visible');
        }

        // Обновляем размер карты после анимации
        setTimeout(() => {
            if (currentMap) {
                currentMap.invalidateSize();
                // Восстанавливаем позицию
                currentMap.setView(currentCenter, currentZoom);
            }
        }, 300);
    }

    // Фокусировка на подразделении
    function focusOnDivision(divisionId) {
        const division = divisions.find(d => d.id === divisionId);
        if (division && currentMap) {
            currentMap.setView([division.lat, division.lng], 16);
        }
    }
// В функции updateSystemStatus добавьте аналогичный обработчик
            function toggleDivisionVisibility(divisionId, buttonElement) {
                // Данные для отправки
                var postData = {
                    type: "toggleVisibility",
                    divisionId: divisionId
                };

                // Создаем запрос
                var xhr = new XMLHttpRequest();
                xhr.open('POST', window.location.href, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                // Обработка ответа
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                // Находим подразделение
                                const division = divisions.find(d => d.id === divisionId);
                                if (division) {
                                    division.is_hidden = response.newValue;

                                    // Обновляем иконку кнопки
                                    const icon = buttonElement.querySelector('i');
                                    if (response.newValue == 1) {
                                        icon.className = 'fas fa-eye-slash';
                                        buttonElement.title = 'Показать подразделение';
                                    } else {
                                        icon.className = 'fas fa-eye';
                                        buttonElement.title = 'Скрыть подразделение';
                                    }

                                    // Обновляем стиль элемента в списке
                                    const divisionItem = buttonElement.closest('.division-item');
                                    if (response.newValue == 1) {
                                        divisionItem.classList.add('division-hidden');
                                    } else {
                                        divisionItem.classList.remove('division-hidden');
                                    }

                                    // Обновляем маркеры на карте
                                    updateMarkers();
                                }
                            } else {
                                alert('Ошибка: ' + response.error);
                            }
                        } catch (e) {
                            console.error('Ошибка парсинга JSON:', e);
                        }
                    } else {
                        alert('Ошибка сервера: ' + xhr.status);
                    }
                };

                // Обработка ошибок сети
                xhr.onerror = function() {
                    alert('Ошибка сети!');
                };

                // Преобразуем объект в строку параметров и отправляем
                var params = new URLSearchParams(postData).toString();
                xhr.send(params);
            }
    // Поиск подразделений
    function searchDivisions(query) {
        const divisionList = document.getElementById('divisionList');
        const filtered = divisions.filter(division =>
            division.name.toLowerCase().includes(query.toLowerCase()) ||
            division.address.toLowerCase().includes(query.toLowerCase())
        );

        renderDivisionList(filtered);
    }

    // Рендер списка подразделений с иконками статусов
            function renderDivisionList(divisionsToShow = divisions) {
                const divisionList = document.getElementById('divisionList');

                divisionList.innerHTML = divisionsToShow.map(division => {
                    const status = division.status;
                    const isHidden = division.is_hidden == 1;

                    return `
            <div class="division-item ${isHidden ? 'division-hidden' : ''}" data-id="${division.id}">
                <div class="division-info">
                    <div class="division-name">${division.name}</div>
                    <div class="division-status-icons">
                        <div class="status-icon coordinator-icon active-${status.coordinator}">
                            <i class="fas ${systemIcons.coordinator}"></i>
                            <div class="status-label">Коорд.</div>
                        </div>
                        <div class="status-icon ats-icon active-${status.ats}">
                            <i class="fas ${systemIcons.ats}"></i>
                            <div class="status-label">АТС</div>
                        </div>
                        <div class="status-icon radio-icon active-${status.radio}">
                            <i class="fas ${systemIcons.radio}"></i>
                            <div class="status-label">Радио</div>
                        </div>
                        <div class="status-icon fxo-icon active-${status.fxo}">
                            <i class="fas ${systemIcons.fxo}"></i>
                            <div class="status-label">FXO</div>
                        </div>
                    </div>
                </div>
                <div class="division-actions">
                    <button class="action-btn info" onclick="openModal(${JSON.stringify(division).replace(/"/g, '&quot;')})">
                        <i class="fas fa-info"></i>
                    </button>
                    <button class="action-btn focus" onclick="focusOnDivision(${division.id})">
                        <i class="fas fa-crosshairs"></i>
                    </button>
                    <button class="action-btn hide" onclick="toggleDivisionVisibility(${division.id}, this)"
                            title="${isHidden ? 'Показать подразделение' : 'Скрыть подразделение'}">
                        <i class="fas ${isHidden ? 'fa-eye-slash' : 'fa-eye'}"></i>
                    </button>
                </div>
            </div>
        `;
                }).join('');

                updateDivisionCount();
            }

    // Применение фильтров
    function applyFilters() {
        activeFilters.coordinator = document.getElementById('filterCoordinator').value;
        activeFilters.ats = document.getElementById('filterATS').value;
        activeFilters.radio = document.getElementById('filterRadio').value;
        activeFilters.fxo = document.getElementById('filterFXO').value;
        updateMarkers();
    }

    // Сброс всех фильтров
    function resetFilters() {
        // Сбрасываем фильтры
        document.getElementById('filterCoordinator').value = 'all';
        document.getElementById('filterATS').value = 'all';
        document.getElementById('filterRadio').value = 'all';
        document.getElementById('filterFXO').value = 'all';

        // Сбрасываем активные фильтры
        activeFilters = {
            coordinator: 'all',
            ats: 'all',
            radio: 'all',
            fxo: 'all'
        };

        // Включаем все системы
        visibleSystems = {
            coordinator: true,
            ats: true,
            radio: true,
            fxo: true
        };

        // Обновляем круг систем
        updateSystemCircle();

        // Обновляем маркеры
        updateMarkers();
    }

    // Переключение подписей
    function toggleLabels() {
        showLabels = !showLabels;
        updateLabels();
    }

    // Назад на сайт
    function goBackToSite() {
///        window.history.back();
        window.location.href = '/';
        // Или можно использовать: window.location.href = '/';
    }

    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        // Создаем карту
        initMap('streets', currentCenter, currentZoom);

        // Рендерим список подразделений
        renderDivisionList();

        // Обработчики для кнопок слоев
        document.querySelectorAll('.layer-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Сохраняем текущую позицию перед переключением
                if (currentMap) {
                    currentCenter = currentMap.getCenter();
                    currentZoom = currentMap.getZoom();
                }

                const layerType = this.dataset.layer;
                initMap(layerType, currentCenter, currentZoom);
            });
        });

        // Обработчики для кнопок систем (частей круга)
        document.querySelectorAll('.system-quarter').forEach(quarter => {
            quarter.addEventListener('click', function() {
                const system = this.dataset.system;
                visibleSystems[system] = !visibleSystems[system];
                this.classList.toggle('active');
                updateMarkers();
            });
        });

        // Обработчики для панели управления
        document.getElementById('togglePanel').addEventListener('click', togglePanel);
        document.getElementById('openPanelBtn').addEventListener('click', function() {
            panelCollapsed = true;
            togglePanel(); // Это переключит обратно, так как panelCollapsed уже true
        });

        // Обработчик кнопки "Назад на сайт"
        document.getElementById('backToSite').addEventListener('click', goBackToSite);

        // Обработчик поиска
        document.getElementById('searchInput').addEventListener('input', function() {
            searchDivisions(this.value);
        });

        // Обработчик фильтров
        document.getElementById('applyFilters').addEventListener('click', applyFilters);
        document.getElementById('resetFilters').addEventListener('click', resetFilters);
        document.getElementById('toggleLabelsBtn').addEventListener('click', toggleLabels);

        // Обработчик модального окна
        document.getElementById('closeModal').addEventListener('click', closeModal);
        document.getElementById('modalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Закрытие модального окна по ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Сохраняем позицию карты при перемещении
        if (currentMap) {
            currentMap.on('moveend', function() {
                currentCenter = currentMap.getCenter();
                currentZoom = currentMap.getZoom();
            });
        }
    });
</script>
</body>
</html>