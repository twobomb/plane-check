<?php
    include "includes/db.php";

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        try {
            $status = $_POST["status"];
            if(in_array($status,array_keys(CORE::$statuses))){
                CORE::$db->update("plan",["status"=>$status],["id"=>$_POST["id"]]);
                if(!is_null(CORE::$db->error))
                    throw new Exception("Ошибка БД! ".CORE::$db->error);
                addHistory($_POST["id"],"changestatus","Статус изменен на '".CORE::$statuses[$status]."'");
                responseJson([
                    "result"=>"ok"
                ]);die;
            }
            else
                throw new Exception("Неизвестный статус!");
        }catch (Exception $e){
            responseJson([
                "result"=>"error",
                "errors"=> $e->getMessage()
            ]);die;
        }
    }


    if(!isset($_GET["id"]) || CORE::$db->count("plan",["id"=>$_GET["id"]]) == 0 ){
        echo "План не найден!";die;
    }
    $pid = $_GET["id"];
    $planData = CORE::$db->select("plan","*",["id"=>$pid])[0];
    $planData["title"] = $planData["name"];
    $planData["description"] = $planData["content"];
    $planData["deadline"] = $planData["date_value"];
    $planData["deadlineType"] = $planData["date_type"];
    $planData["author"] = CORE::$db->select("user","username",["id"=>$planData["user_id"]])[0];
    $planData["created"] =$planData["create_at"];
    $planData["parentPlan"] = null;
    if(!is_null($planData["parent_id"])){
        $par = CORE::$db->select("plan",["name","id"],["id"=>$planData["parent_id"]])[0];
        $planData["parentPlan"] = [
            "id"=>$par["id"],
            "title"=>$par["name"]
            ]        ;
    }

    $filesData = [];
    $fids = CORE::$db->select("file_to_plan","file_id",["plan_id"=>$pid]);
    if(count($fids) > 0)
        foreach (CORE::$db->select("files","*",["id"=>$fids]) as $file){
            array_push($filesData,[
               "id"=>$file["id"],
               "name"=>$file["name"],
               "url"=>$file["url"],
               "download"=>"/download-file.php?id=".$file["id"],
               "size"=>file_exists($file['url']) ? filesize($file['url']) : 0,
                "type"=> strtolower(pathinfo($file['url'], PATHINFO_EXTENSION))
            ]);
        }
    $subplansData = [];

    foreach (CORE::$db->select("plan",["name","id","date_value","date_type","status"],["parent_id"=>$pid]) as $p){
        array_push($subplansData,[
            "id"=>$p["id"],
            "title"=>$p["name"],
            "deadline"=>$p["date_value"], ///тут обработать даты
            "deadlineType"=>$p["date_type"], ///тут обработать даты
            "status"=>$p["status"]
        ]);
    }
    $departmentsData = [];
    $depids =  CORE::$db->select("department_to_plan","department_id",["plan_id"=>$pid]);
    if(count($depids) > 0)
        foreach (CORE::$db->select("department",["name","addr","id"],["id"=>$depids]) as $dp){
            array_push($departmentsData,[
                "id"=>$dp["id"],
                "name"=>$dp["name"],
                "address"=>$dp["addr"]
            ]);
        }

    $geopointsData = [];
    $geoids =  CORE::$db->select("point_to_plan","point_id",["plan_id"=>$pid]);
    if(count($geoids) > 0)
        foreach (CORE::$db->select("point","*",["id"=>$geoids]) as $pnt){
            array_push($geopointsData,[
                "id"=>$pnt["id"],
                "name"=>$pnt["name"],
                "lat"=>$pnt["lat"],
                "lon"=>$pnt["lon"],
                "description"=>$pnt["description"],
                "address"=>$pnt["addr"],
            ]);
        }
    $historyData = [];

    foreach (CORE::$db->select("history","*",["plan_id"=>$pid,"ORDER"=>["date"=>"DESC"]]) as $hs){
        array_push($historyData,[
            "date"=>$hs["date"],
            "type"=>$hs["type"],
            "details"=>$hs["value"],
            "user"=> CORE::$db->select("user","username",["id"=>$hs["user_id"]])[0],
        ])  ;
    }



?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр плана - Система управления</title>

    <!-- Подключаем библиотеки -->
    <link href="/css/fontawesome-free-6.7.2-web/css/all.min.css" rel="stylesheet">
    <link href="/css/Inter-4.1/web/inter.css" rel="stylesheet">
    <link href="/css/quill.snow.css" rel="stylesheet">

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
            flex-wrap: wrap;
            gap: 20px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 300px;
        }

        .header h1 i {
            color: #3498db;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
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
            text-decoration: none;
            font-size: 14px;
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
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .content {
            padding: 32px;
        }

        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
            flex-wrap: wrap;
            gap: 20px;
        }

        .plan-title-section {
            flex: 1;
            min-width: 300px;
        }

        .plan-title {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .plan-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6c757d;
            font-size: 14px;
        }

        .meta-item i {
            color: #3498db;
            width: 16px;
        }

        .plan-status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        /* НОВАЯ СТРУКТУРА ГРИДА */
        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 1100px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }

        .two-columns-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 900px) {
            .two-columns-grid {
                grid-template-columns: 1fr;
            }
        }

        .full-width-section {
            grid-column: 1 / -1;
        }

        .section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 0;
            border: 1px solid #e9ecef;
            height: 100%;
        }

        .section h2 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section h2 i {
            color: #3498db;
        }

        .plan-description {
            line-height: 1.8;
            color: #495057;
            font-size: 16px;
        }

        .plan-description img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 15px 0;
        }

        .plan-description ul, .plan-description ol {
            padding-left: 20px;
            margin: 10px 0;
        }

        .plan-description li {
            margin-bottom: 5px;
        }

        .files-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .file-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .file-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #3498db;
        }

        .file-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .pdf-file {
            background: #f8d7da;
            color: #721c24;
        }

        .doc-file {
            background: #cce5ff;
            color: #004085;
        }

        .xls-file {
            background: #d1ecf1;
            color: #0c5460;
        }

        .img-file {
            background: #d4edda;
            color: #155724;
        }

        .zip-file {
            background: #e2e3e5;
            color: #383d41;
        }

        .file-info {
            flex: 1;
            overflow: hidden;
        }

        .file-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-meta {
            font-size: 12px;
            color: #6c757d;
            display: flex;
            gap: 10px;
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

        .departments-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .department-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #e9ecef;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }

        .department-card:hover {
            transform: translateY(-3px);
            border-color: #3498db;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .department-icon {
            width: 40px;
            height: 40px;
            background: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            color: #3498db;
        }

        .department-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .department-info {
            font-size: 13px;
            color: #6c757d;
        }

        .geopoints-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .geopoint-item {
            background: white;
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e9ecef;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }

        .geopoint-item:hover {
            background: #e8f4fc;
            border-color: #3498db;
        }

        .geopoint-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .geopoint-name {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .geopoint-coords {
            font-size: 12px;
            color: #6c757d;
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .geopoint-description {
            font-size: 14px;
            color: #495057;
            line-height: 1.5;
        }

        .map-container {
            height: 400px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }

        .map-placeholder {
            height: 100%;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            text-align: center;
            padding: 20px;
        }

        .map-placeholder i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #adb5bd;
        }

        .map-placeholder h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #495057;
        }

        .history-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .history-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #e9ecef;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .history-type {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .history-date {
            font-size: 13px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .history-details {
            color: #495057;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .history-user {
            font-size: 14px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 5px;
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

        .empty-state p {
            color: #6c757d;
        }

        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .tag {
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 13px;
            color: #495057;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .tag i {
            font-size: 12px;
        }

        /* Модальное окно изменения статуса */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: modalAppear 0.3s ease;
        }

        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 24px 32px;
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
            font-size: 24px;
            color: #6c757d;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: #dc3545;
        }

        .modal-body {
            padding: 32px;
        }

        .modal-footer {
            padding: 24px 32px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .status-options-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .status-option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-option:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .status-option.selected {
            border-color: #3498db;
            background: #f1f8ff;
        }

        .status-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .status-pending .status-icon {
            background: #fff3cd;
            color: #856404;
        }

        .status-inprogress .status-icon {
            background: #cce5ff;
            color: #004085;
        }

        .status-completed .status-icon {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected .status-icon {
            background: #f8d7da;
            color: #721c24;
        }

        .status-info {
            flex: 1;
        }

        .status-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .status-description {
            font-size: 14px;
            color: #6c757d;
        }

        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .header-actions {
                width: 100%;
                justify-content: center;
            }

            .plan-header {
                flex-direction: column;
            }

            .modal {
                max-width: 100%;
            }

            .status-option {
                padding: 15px;
            }

            .status-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Шапка страницы -->
    <div class="header">
        <h1><i class="fas fa-eye"></i> Просмотр плана</h1>
        <div class="header-actions">
            <a href="plan.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Назад к списку
            </a>
            <a href="addplan.php?id=<?=$pid?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Редактировать
            </a>
            <button class="btn btn-success" id="changeStatusBtn">
                <i class="fas fa-flag"></i> Изменить статус
            </button>
        </div>
    </div>

    <div class="content">
        <!-- Заголовок плана и мета-информация -->
        <div class="plan-header">
            <div class="plan-title-section">
                <div class="plan-title" id="planTitle">Запуск нового продукта на рынок</div>
                <div class="tags-container">
                        <span class="tag">
                            <i class="far fa-calendar"></i> Тип делайна: <?= CORE::$dateTypes[$planData["date_type"]] ?>
                        </span>
                    <span class="tag">
                            <i class="fas fa-user"></i> <?= $planData["author"] ?>
                        </span>
                    <span class="tag">
                            <i class="fas fa-clock"></i> Создан <?=  DateTime::createFromFormat('Y-m-d H:i:s', $planData["created"])->format("d M Y H:i") ?>
                        </span>
                </div>
            </div>
            <div class="plan-status-badge status-inprogress">
                <i class="fas fa-sync-alt fa-spin"></i> В работе
            </div>
        </div>

        <!-- Первый ряд: описание + правая панель -->
        <div class="main-grid">
            <!-- Описание плана (широкая часть) -->
            <div class="section">
                <h2><i class="fas fa-align-left"></i> Описание плана</h2>
                <div class="plan-description" id="planDescription">
                    <p><strong>Цель:</strong> Успешный запуск нового продукта "SmartSolution Pro" на российском рынке с достижением доли рынка 15% в течение первого года.</p>

                    <p><strong>Задачи:</strong></p>
                    <ul>
                        <li>Разработка маркетинговой стратегии</li>
                        <li>Проведение рекламной кампании в digital и офлайн</li>
                        <li>Обучение отдела продаж (100+ сотрудников)</li>
                        <li>Настройка логистики и дистрибуции</li>
                        <li>Запуск производства первой партии (10,000 единиц)</li>
                    </ul>

                    <p><strong>Ожидаемые результаты:</strong></p>
                    <ul>
                        <li>Продажи: 5,000 единиц в первый квартал</li>
                        <li>Рост узнаваемости бренда на 25%</li>
                        <li>Выход на окупаемость через 8 месяцев</li>
                    </ul>

                    <p><strong>Бюджет:</strong> 15 млн. рублей</p>

                    <p><strong>Сроки:</strong> Октябрь 2023 - Март 2024</p>
                </div>
            </div>

            <!-- Правая панель: детали плана и файлы -->
            <div class="right-panel">
                <!-- Детали плана -->
                <div class="section">
                    <h2><i class="fas fa-info-circle"></i> Детали плана</h2>
                    <div class="plan-meta" style="flex-direction: column; gap: 15px;">
                        <div class="meta-item">
                            <i class="fas fa-calendar-day"></i>
                            <span><strong>Дедлайн:</strong> 15 октября 2023</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-user-plus"></i>
                            <span><strong>Автор:</strong> Иван Петров</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-plus"></i>
                            <span><strong>Создан:</strong> 10 сентября 2023, 14:30</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-sitemap"></i>
                            <span><strong>Родительский план:</strong> <a href="#" style="color: #3498db; text-decoration: none;">Стратегия развития 2024</a></span>
                        </div>
                    </div>
                </div>

                <!-- Файлы -->
                <div class="section">
                    <h2><i class="fas fa-paperclip"></i> Прикрепленные файлы</h2>
                    <div class="files-grid" id="filesList">
                        <!-- Файлы будут загружены через JS -->
                    </div>
                    <div class="empty-state" id="noFilesMessage" style="display: none;">
                        <i class="far fa-folder-open"></i>
                        <p>Файлы не прикреплены</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Второй ряд: подпланы + подразделения -->
        <div class="two-columns-grid">
            <!-- Подпланы -->
            <div class="section">
                <h2><i class="fas fa-project-diagram"></i> Подпланы</h2>
                <div class="subplans-list" id="subplansList">
                    <!-- Подпланы будут загружены через JS -->
                </div>
                <div class="empty-state" id="noSubplansMessage" style="display: none;">
                    <i class="fas fa-sitemap"></i>
                    <p>Подпланы не созданы</p>
                </div>
            </div>

            <!-- История изменений -->
            <div class="section">
                <h2><i class="fas fa-history"></i> История изменений</h2>
                <div class="history-list" id="historyList">
                    <!-- История будет загружена через JS -->
                </div>
            </div>

        </div>

        <!-- Третий ряд: история изменений + геоточки -->
        <div class="two-columns-grid">

            <!-- Подразделения -->
            <div class="section">
                <h2><i class="fas fa-sitemap"></i> Подразделения</h2>
                <div class="departments-grid" id="departmentsList">
                    <!-- Подразделения будут загружены через JS -->
                </div>
                <div class="empty-state" id="noDepartmentsMessage" style="display: none;">
                    <i class="fas fa-building"></i>
                    <p>Подразделения не привязаны</p>
                </div>
            </div>
            <!-- Геоточки -->
            <div class="section">
                <h2><i class="fas fa-map-marker-alt"></i> Геоточки</h2>
                <div class="geopoints-list" id="geopointsList">
                    <!-- Геоточки будут загружены через JS -->
                </div>
                <div class="empty-state" id="noGeopointsMessage" style="display: none;">
                    <i class="fas fa-map"></i>
                    <p>Геоточки не привязаны</p>
                </div>
            </div>
        </div>

        <!-- Карта на всю ширину -->
        <div class="full-width-section">
            <div class="section">
                <h2><i class="fas fa-map-marked-alt"></i> Карта плана</h2>
                <div class="map-container">
                    <div class="map-placeholder" id="mapPlaceholder">
                        <i class="fas fa-map-marked-alt"></i>
                        <h3>Карта геоточек плана</h3>
                        <p>Здесь будет отображаться карта со всеми привязанными геоточками плана</p>
                        <div style="margin-top: 30px; padding: 15px; background: white; border-radius: 8px; border: 1px dashed #dee2e6; max-width: 600px;">
                            <div style="font-family: monospace; font-size: 12px; margin-bottom: 10px;">
                                &lt;iframe src="КАРТА_URL" width="100%" height="400" frameborder="0"&gt;&lt;/iframe&gt;
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно изменения статуса -->
<div class="modal-overlay" id="statusModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-flag"></i> Изменить статус плана</h3>
            <button class="modal-close" id="closeModal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="status-options-grid" id="statusOptions">
                <!-- Опции статусов будут заполнены через JS -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="cancelStatusChange">Отмена</button>
            <button class="btn btn-primary" id="saveStatusChange">Сохранить</button>
        </div>
    </div>
</div>

<!-- Подключаем jQuery -->
<script src="/js/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        // Моковые данные для плана
        const planData = <?= json_encode($planData,JSON_UNESCAPED_UNICODE)?>;
        const filesData = <?= json_encode($filesData,JSON_UNESCAPED_UNICODE)?>;
        const subplansData = <?= json_encode($subplansData,JSON_UNESCAPED_UNICODE)?>;
        const departmentsData = <?= json_encode($departmentsData,JSON_UNESCAPED_UNICODE)?>;
        const geopointsData = <?= json_encode($geopointsData,JSON_UNESCAPED_UNICODE)?>;
        const historyData = <?= json_encode($historyData,JSON_UNESCAPED_UNICODE)?>;


        // Данные для статусов
        const statusOptionsData = [
            {
                id: "pending",
                title: "Ожидание",
                description: "План ожидает начала выполнения",
                icon: "fas fa-clock"
            },
            {
                id: "inprogress",
                title: "В работе",
                description: "План находится в процессе выполнения",
                icon: "fas fa-sync-alt"
            },
            {
                id: "completed",
                title: "Выполнен",
                description: "План успешно завершен",
                icon: "fas fa-check-circle"
            },
            {
                id: "rejected",
                title: "Отклонен",
                description: "План отклонен или отменен",
                icon: "fas fa-times-circle"
            }
        ];

        // Функция форматирования даты
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

        // Функция форматирования размера файла
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Б';
            const k = 1024;
            const sizes = ['Б', 'КБ', 'МБ', 'ГБ'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Функция получения иконки для типа файла
        function getFileIconClass(fileType) {
            switch(fileType) {
                case 'pdf': return 'fas fa-file-pdf pdf-file';
                case 'doc': case 'docx': return 'fas fa-file-word doc-file';
                case 'xls': case 'xlsx': return 'fas fa-file-excel xls-file';
                case 'jpg': case 'jpeg': case 'png': case 'gif': return 'fas fa-file-image img-file';
                case 'zip': case 'rar': case '7z': return 'fas fa-file-archive zip-file';
                default: return 'fas fa-file';
            }
        }

        // Функция получения текста статуса
        function getStatusText(status) {
            const statusMap = {
                'pending': 'Ожидание',
                'inprogress': 'В работе',
                'completed': 'Выполнен',
                'rejected': 'Отклонен'
            };
            return statusMap[status] || status;
        }

        // Функция получения класса статуса
        function getStatusClass(status) {
            return `status-${status}`;
        }

        // Функция получения иконки для типа события в истории
        function getHistoryIcon(type) {
            switch(type) {
                case 'create': return 'fas fa-plus-circle';
                case 'edit': return 'fas fa-edit';
                case 'changestatus': return 'fas fa-flag';
                case 'file': return 'fas fa-paperclip';
                case 'department': return 'fas fa-sitemap';
                default: return 'fas fa-info-circle';
            }
        }

        // Функция отображения файлов
        function renderFiles() {
            const container = $('#filesList');
            const emptyMessage = $('#noFilesMessage');

            if (filesData.length === 0) {
                container.hide();
                emptyMessage.show();
                return;
            }

            container.empty();
            filesData.forEach(file => {
                const fileCard = `
                        <a href="${file.download}" class="file-card" >
                            <div class="file-icon ${file.type}-file">
                                <i class="${getFileIconClass(file.type)}"></i>
                            </div>
                            <div class="file-info">
                                <div class="file-name" title="${file.name}">${file.name}</div>
                                <div class="file-meta">
                                    <span>${formatFileSize(file.size)}</span>
                                    <span>${file.type.toUpperCase()}</span>
                                </div>
                            </div>
                            <i class="fas fa-download" style="color: #6c757d;"></i>
                        </a>
                    `;
                container.append(fileCard);
            });

            container.show();
            emptyMessage.hide();
        }

        // Функция отображения подпланов
        function renderSubplans() {
            const container = $('#subplansList');
            const emptyMessage = $('#noSubplansMessage');

            if (subplansData.length === 0) {
                container.hide();
                emptyMessage.show();
                return;
            }

            container.empty();
            subplansData.forEach(plan => {
                const subplanItem = `
                        <a href="showplan.php?id=${plan.id}" class="subplan-item">
                            <div class="subplan-info">
                                <div class="subplan-title">${plan.title}</div>
                                <div class="subplan-date">
                                    <i class="far fa-calendar"></i>
                                    Дедлайн: ${formatDate(plan.deadline,plan.deadlineType)}
                                </div>
                            </div>
                            <div class="subplan-status ${getStatusClass(plan.status)}">
                                ${getStatusText(plan.status)}
                            </div>
                        </a>
                    `;
                container.append(subplanItem);
            });

            container.show();
            emptyMessage.hide();
        }

        // Функция отображения подразделений
        function renderDepartments() {
            const container = $('#departmentsList');
            const emptyMessage = $('#noDepartmentsMessage');

            if (departmentsData.length === 0) {
                container.hide();
                emptyMessage.show();
                return;
            }

            container.empty();
            departmentsData.forEach(dept => {
                const deptCard = `
                        <a href="department.html?id=${dept.id}" class="department-card">
                            <div class="department-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="department-name">${dept.name}</div>
                            <div class="department-info">
                                <div style="margin-top: 5px; font-size: 12px;">${dept.address}</div>
                            </div>
                        </a>
                    `;
                container.append(deptCard);
            });

            container.show();
            emptyMessage.hide();
        }

        // Функция отображения геоточек
        function renderGeopoints() {
            const container = $('#geopointsList');
            const emptyMessage = $('#noGeopointsMessage');

            if (geopointsData.length === 0) {
                container.hide();
                emptyMessage.show();
                return;
            }

            container.empty();
            geopointsData.forEach(point => {
                const geopointItem = `
                        <a href="geopoint.html?id=${point.id}" class="geopoint-item">
                            <div class="geopoint-header">
                                <div class="geopoint-name">
                                    <i class="fas fa-map-marker-alt" style="color: #dc3545;"></i>
                                    ${point.name}
                                </div>
                                <div class="geopoint-coords">
                                    ${point.lat}, ${point.lon}
                                </div>
                            </div>
                            <div class="geopoint-description">
                                ${point.description}
                                <div style="font-size: 13px; color: #6c757d; margin-top: 5px;">
                                    <i class="fas fa-map-pin"></i> ${point.address}
                                </div>
                            </div>
                        </a>
                    `;
                container.append(geopointItem);
            });

            container.show();
            emptyMessage.hide();
        }

        // Функция отображения истории изменений (упрощенная)
        function renderHistory() {
            const container = $('#historyList');

            if (historyData.length === 0) {
                container.html('<div class="empty-state"><i class="fas fa-history"></i><p>История изменений отсутствует</p></div>');
                return;
            }

            container.empty();
            historyData.forEach(item => {
                const historyItem = `
                        <div class="history-item">
                            <div class="history-header">
                                <div class="history-type">
                                    <i class="${getHistoryIcon(item.type)}"></i>
                                    ${item.details}
                                </div>
                                <div class="history-date">
                                    <i class="far fa-clock"></i>
                                    ${formatDate(item.date)}
                                </div>
                            </div>
                            <div class="history-user">
                                <i class="fas fa-user"></i> ${item.user}
                            </div>
                        </div>
                    `;
                container.append(historyItem);
            });
        }

        // Функция загрузки данных плана
        function loadPlanData() {
            // Устанавливаем заголовок
            $('#planTitle').text(planData.title);

            // Устанавливаем описание
            $('#planDescription').html(planData.description);

            // Устанавливаем статус
            updateStatusDisplay(planData.status);

            // Обновляем мета-информацию
            $('.plan-meta').html(`
                    <div class="meta-item">
                        <i class="fas fa-calendar-day"></i>
                        <span><strong>Дедлайн:</strong> ${formatDate(planData.deadline,planData.deadlineType)}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user-plus"></i>
                        <span><strong>Автор:</strong> ${planData.author}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-plus"></i>
                        <span><strong>Создан:</strong> ${formatDate(planData.created)}</span>
                    </div>
                    ${planData.parentPlan ? `
                    <div class="meta-item">
                        <i class="fas fa-sitemap"></i>
                        <span><strong>Родительский план:</strong> <a href="showplan.php?id=${planData.parentPlan.id}" style="color: #3498db; text-decoration: none;">${planData.parentPlan.title}</a></span>
                    </div>
                    ` : ''}
                `);
        }

        // Функция обновления отображения статуса
        function updateStatusDisplay(status) {
            const statusBadge = $('.plan-status-badge');
            statusBadge.removeClass('status-pending status-inprogress status-completed status-rejected');
            statusBadge.addClass(getStatusClass(status));

            // Обновляем иконку в зависимости от статуса
            let icon = '';
            switch(status) {
                case 'pending': icon = 'fas fa-clock'; break;
                case 'inprogress': icon = 'fas fa-sync-alt fa-spin'; break;
                case 'completed': icon = 'fas fa-check-circle'; break;
                case 'rejected': icon = 'fas fa-times-circle'; break;
            }

            statusBadge.html(`<i class="${icon}"></i> ${getStatusText(status)}`);
        }

        // Функция отображения модального окна изменения статуса
        function showStatusModal() {
            const modal = $('#statusModal');
            const statusOptions = $('#statusOptions');

            // Очищаем предыдущие опции
            statusOptions.empty();

            // Заполняем опции статусов
            statusOptionsData.forEach(status => {
                const isSelected = status.id === planData.status;
                const statusOption = `
                        <div class="status-option status-${status.id} ${isSelected ? 'selected' : ''}" data-status="${status.id}">
                            <div class="status-icon">
                                <i class="${status.icon}"></i>
                            </div>
                            <div class="status-info">
                                <div class="status-title">${status.title}</div>
                                <div class="status-description">${status.description}</div>
                            </div>
                            ${isSelected ? '<i class="fas fa-check" style="color: #28a745; font-size: 20px;"></i>' : ''}
                        </div>
                    `;
                statusOptions.append(statusOption);
            });

            // Добавляем обработчики выбора статуса
            $('.status-option').on('click', function() {
                $('.status-option').removeClass('selected');
                $(this).addClass('selected');

                // Добавляем иконку галочки к выбранному статусу
                $('.status-option').find('.fa-check').remove();
                $(this).append('<i class="fas fa-check" style="color: #28a745; font-size: 20px;"></i>');
            });

            // Показываем модальное окно
            modal.addClass('active');
        }

        // Обработчики кнопок
        $('#deleteBtn').on('click', function() {
            if (confirm('Вы уверены, что хотите удалить этот план? Это действие нельзя отменить.')) {
                alert('План удален! В реальном приложении здесь будет отправка запроса на удаление.');
                // window.location.href = 'plans_list.html';
            }
        });

        // Обработчик кнопки изменения статуса
        $('#changeStatusBtn').on('click', showStatusModal);

        // Обработчики модального окна
        $('#closeModal, #cancelStatusChange').on('click', function() {
            $('#statusModal').removeClass('active');
        });

        // Обработчик сохранения изменения статуса
        $('#saveStatusChange').on('click', function() {
            const selectedStatus = $('.status-option.selected').data('status');

            if (selectedStatus && selectedStatus !== planData.status) {
                $.ajax({
                    type: "POST",
                    url: "",
                    data: {
                        id:<?=$pid?>,
                        status:selectedStatus
                    },
                    success: function (e){
                        if(e.result == "ok")
                            window.location.reload();
                        else{
                            if(e.errors == null)
                                alert("Прозошла какая-то ошибка");
                            else if(Array.isArray(e.errors))
                                alert("Ошибки:\r\n"+e.errors.join("\r\n"));
                            else
                                alert("Ошибка: "+e.errors);

                        }
                    }
                });

            } else {
                alert('Выберите новый статус');
            }
        });

        // Закрытие модального окна по клику вне его
        $('#statusModal').on('click', function(e) {
            if (e.target === this) {
                $(this).removeClass('active');
            }
        });


        // Инициализация страницы
        loadPlanData();
        renderFiles();
        renderSubplans();
        renderDepartments();
        renderGeopoints();
        renderHistory();
    });
</script>
</body>
</html>