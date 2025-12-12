<?php
    include "includes/db.php";

    session_start();

    if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["type"]) && $_POST["type"] == "add"){

        try {
        $data =  json_decode($_POST["data"],true);
        $uid = getUserId();
        if($_POST["is_new"] == "true") {
            CORE::$db->insert("project", [
                "name" => $_POST["name"],
                "description" => $_POST["description"],
                "updated_at" => nowdate(),
                "access" => $_POST["access"],
                "center_lat" => $data["mapSettings"]["center"]["lat"],
                "center_lng" => $data["mapSettings"]["center"]["lng"],
                "zoom" => $data["mapSettings"]["zoom"],
                "scheme" => $data["mapSettings"]["mode"],
                "showLabels" => $data["mapSettings"]["showLabels"],
                "user_id" => $uid,
            ]);
            if (!is_null(CORE::$db->error))
                throw new Exception("Ошибка БД! " . CORE::$db->error);
            $pid = CORE::$db->id();
        }else{
            $pid =  $_POST["id"];
            $canAccessProjects = CORE::$db->query("SELECT id FROM project WHERE access = 'public' OR (access = 'protected' AND user_id = '$uid') OR (access = 'private' AND user_id = '$uid')")->fetchAll();
            $idsAccess = [];
            foreach ($canAccessProjects as $k){
                array_push($idsAccess,$k["id"]);
            }
            if(!in_array($pid,$idsAccess))
                throw new Exception("Вам запрещен доступ к этом проекту!");
            CORE::$db->update("project", [
                "updated_at" => nowdate()
            ],["id"=>$pid]);
            if (!is_null(CORE::$db->error))
                throw new Exception("Ошибка БД! " . CORE::$db->error);
            CORE::$db->delete("layer",["project_id"=>$pid] );
            if (!is_null(CORE::$db->error))
                throw new Exception("Ошибка БД! " . CORE::$db->error);
        }

            foreach ($data["layers"] as $layer) {
                CORE::$db->insert("layer", [
                    "name" => $layer["name"],
                    "description" => $layer["description"],
                    "visible" => $layer["visible"]?1:0,
                    "project_id"=>$pid
                ]);
                if (!is_null(CORE::$db->error))
                    throw new Exception("Ошибка БД! " . CORE::$db->error);
                $lid = CORE::$db->id();

                foreach ($layer["markers"] as $marker) {
                    CORE::$db->insert("point", [
                        "name" => $marker["name"],
                        "layer_id"=>$lid,
                        "lat" => $marker["lat"],
                        "lng" => $marker["lng"],
                        "color" => $marker["color"]
                    ]);
                    if (!is_null(CORE::$db->error))
                        throw new Exception("Ошибка БД! " . CORE::$db->error);
                }
            }
            responseJson(["result"=>"success"]); die;

        }catch (Exception $ex){
            responseJson(["result"=>"error","errors"=>$ex->getMessage()]);die;
        }

    }
    else if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["project_data"])){
        $data = json_decode($_POST["project_data"],true);
        if(isset($data["mapSettings"]) && isset($data["layers"])){
            $_SESSION["project_data"] = $data;
        }
        else{
            echo "Получены невеерные данные!";
            die;
        }
    }
    if(!isset($_SESSION["project_data"] )){
        echo "Данные экспорта не найдены!";die;
    }

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сохранение проекта - Система управления</title>
    <link href="/css/fontawesome-free-6.7.2-web/css/all.min.css" rel="stylesheet">
    <link href="/css/Inter-4.1/web/inter.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .marker-list>li {

            margin-left: 20px;
            font-size: 14px;
            font-weight: normal;
        }
        .layers-list>li{
            margin-left: 20px;
            font-size: 16px;
            font-weight: bold;

        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
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
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
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

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .content {
            padding: 32px;
        }

        .section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
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

        .data-preview {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            border: 1px solid #dee2e6;
            max-height: 300px;
            overflow-y: auto;
        }

        .preview-item {
            padding: 8px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .preview-item:last-child {
            border-bottom: none;
        }

        .preview-key {
            font-weight: 600;
            color: #2c3e50;
        }

        .preview-value {
            color: #495057;
            margin-left: 10px;
        }

        .auth-form {
            max-width: 400px;
            margin: 0 auto;
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

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 15px;
            transition: border 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .project-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .project-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            border-color: #3498db;
        }

        .project-card.selected {
            border-color: #3498db;
            background: #f0f8ff;
        }

        .project-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .project-meta {
            font-size: 14px;
            color: #6c757d;
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .project-description {
            color: #495057;
            line-height: 1.5;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .project-stats {
            display: flex;
            gap: 15px;
            font-size: 13px;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #6c757d;
        }

        .new-project-form {
            max-width: 500px;
            margin: 20px auto 0;
            background: white;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }

        .new-project-form h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }

        .radio-option {
            flex: 1;
        }

        .radio-option input[type="radio"] {
            display: none;
        }

        .radio-option label {
            display: block;
            padding: 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .radio-option input[type="radio"]:checked + label {
            border-color: #3498db;
            background: #f0f8ff;
        }

        .hidden {
            display: none;
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

        .status-info {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .loading i {
            font-size: 40px;
            margin-bottom: 15px;
            color: #3498db;
        }

        .loading-spinner {
            display: inline-block;
            width: 50px;
            height: 50px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .projects-grid {
                grid-template-columns: 1fr;
            }

            .radio-group {
                flex-direction: column;
            }
        }
        .access-type-selector.compact {
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            overflow: hidden;
        }

        .access-type-selector.compact .access-options {
            display: flex;
        }

        .access-type-selector.compact .access-option {
            flex: 1;
            padding: 12px;
            border-right: 1px solid #dee2e6;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .access-type-selector.compact .access-option:last-child {
            border-right: none;
        }

        .access-type-selector.compact .access-option:hover {
            background: #f8f9fa;
        }

        .access-type-selector.compact .access-option:has(input:checked) {
            background: #e8f4fc;
            border-bottom: 2px solid #3498db;
        }

        .access-type-selector.compact input[type="radio"] {
            display: none;
        }

        .access-type-selector.compact .option-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .access-type-selector.compact .option-content i {
            font-size: 16px;
            width: 24px;
            text-align: center;
        }

        .access-type-selector.compact .access-option:nth-child(1) i {
            color: #dc3545;
        }

        .access-type-selector.compact .access-option:nth-child(2) i {
            color: #ffc107;
        }

        .access-type-selector.compact .access-option:nth-child(3) i {
            color: #28a745;
        }

        .access-type-selector.compact .option-title {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .access-type-selector.compact .option-description {
            color: #6c757d;
            font-size: 12px;
            line-height: 1.3;
        }

        @media (max-width: 768px) {
            .access-type-selector.compact .access-options {
                flex-direction: column;
            }

            .access-type-selector.compact .access-option {
                border-right: none;
                border-bottom: 1px solid #dee2e6;
            }

            .access-type-selector.compact .access-option:last-child {
                border-bottom: none;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-save"></i> Сохранение проекта</h1>
        <div class="header-actions">
            <div class="user-info" id="userInfo">
                <!-- Информация о пользователе будет заполнена через JS -->
            </div>
            <button class="btn btn-secondary" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i> Выйти
            </button>
        </div>
    </div>

    <div class="content">
        <!-- Сообщения о статусе -->
        <div id="statusMessage" class="hidden"></div>

        <!-- Блок загрузки -->
        <div id="loading" class="loading hidden">
            <div class="loading-spinner"></div>
            <p>Обработка данных...</p>
        </div>

        <!-- Шаг 1: Предварительный просмотр данных -->
        <div class="section" id="dataPreviewSection">
            <h2><i class="fas fa-eye"></i> Предварительный просмотр данных</h2>
            <p>Были получены данные проекта. Проверьте информацию перед сохранением.</p>
            <div class="data-preview" id="dataPreview">
                <!-- Данные будут заполнены через JS -->
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <button class="btn btn-primary" id="continueBtn">
                    <i class="fas fa-arrow-right"></i> Продолжить
                </button>
            </div>
        </div>

        <!-- Шаг 2: Авторизация -->
        <div class="section hidden" id="authSection">
            <h2><i class="fas fa-user-lock"></i> Требуется авторизация</h2>
            <p>Для сохранения проекта необходимо войти в систему.</p>
            <div class="auth-form">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Логин</label>
                    <input type="text" id="username" class="form-control" placeholder="Введите логин">
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Пароль</label>
                    <input type="password" id="password" class="form-control" placeholder="Введите пароль">
                </div>
                <div style="text-align: center; margin-top: 25px;">
                    <button class="btn btn-primary" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </button>
                </div>
            </div>
        </div>

        <!-- Шаг 3: Выбор проекта -->
        <div class="section hidden" id="projectSelectionSection">
            <h2><i class="fas fa-folder-open"></i> Выбор проекта</h2>
            <p>Выберите существующий проект для перезаписи данных или создайте новый.</p>

            <div class="radio-group">
                <div class="radio-option">
                    <input type="radio" id="optionExisting" name="projectOption" value="existing" checked>
                    <label for="optionExisting">
                        <i class="fas fa-folder" style="font-size: 24px; margin-bottom: 10px;"></i><br>
                        Выбрать существующий проект
                    </label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="optionNew" name="projectOption" value="new">
                    <label for="optionNew">
                        <i class="fas fa-plus-circle" style="font-size: 24px; margin-bottom: 10px;"></i><br>
                        Создать новый проект
                    </label>
                </div>
            </div>

            <!-- Список существующих проектов -->
            <div id="existingProjects">
                <h3 style="margin: 20px 0 15px 0;">Ваши проекты</h3>
                <div class="projects-grid" id="projectsGrid">
                    <!-- Проекты будут загружены через JS -->
                </div>
            </div>

            <!-- Форма создания нового проекта -->
            <div id="newProjectForm" class="new-project-form hidden">
                <h3><i class="fas fa-plus"></i> Создание нового проекта</h3>
                <div class="form-group">
                    <label for="projectName"><i class="fas fa-pen"></i> Название проекта</label>
                    <input type="text" id="projectName" class="form-control" placeholder="Введите название проекта">
                </div>
                <div class="form-group">
                    <label for="projectDescription"><i class="fas fa-align-left"></i> Описание (необязательно)</label>
                    <textarea id="projectDescription" class="form-control" rows="3" placeholder="Краткое описание проекта"></textarea>
                </div>
                <div class="access-type-selector compact">
                    <div class="access-options">
                        <label class="access-option">
                            <input type="radio" name="access_type" value="private" checked>
                            <span class="option-content">
                <i class="fas fa-user-shield"></i>
                <div>
                    <div class="option-title">Приватный</div>
                    <div class="option-description">Видит и редактирует только создатель</div>
                </div>
            </span>
                        </label>

                        <label class="access-option">
                            <input type="radio" name="access_type" value="protected">
                            <span class="option-content">
                <i class="fas fa-eye"></i>
                <div>
                    <div class="option-title">Защищенный</div>
                    <div class="option-description">Все видят, редактирует создатель</div>
                </div>
            </span>
                        </label>

                        <label class="access-option">
                            <input type="radio" name="access_type" value="public">
                            <span class="option-content">
                <i class="fas fa-globe"></i>
                <div>
                    <div class="option-title">Публичный</div>
                    <div class="option-description">Все видят и редактируют</div>
                </div>
            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 25px;">
                <button class="btn btn-success" id="saveProjectBtn">
                    <i class="fas fa-save"></i> Сохранить проект
                </button>
                <button class="btn btn-secondary" id="cancelBtn" style="margin-left: 10px;">
                    <i class="fas fa-times"></i> Отмена
                </button>
            </div>
        </div>

        <!-- Шаг 4: Успешное сохранение -->
        <div class="section hidden" id="successSection">
            <h2><i class="fas fa-check-circle"></i> Проект успешно сохранен!</h2>
            <div style="text-align: center; padding: 30px;">
                <i class="fas fa-check-circle" style="font-size: 64px; color: #27ae60; margin-bottom: 20px;"></i>
                <h3 style="margin-bottom: 15px; color: #2c3e50;">Данные успешно сохранены</h3>
                <p id="successMessage" style="margin-bottom: 25px; color: #495057;">
                    <!-- Сообщение о результате будет заполнено через JS -->
                </p>
                <button class="btn btn-primary" id="viewProjectBtn">
                    <i class="fas fa-external-link-alt"></i> Перейти к проекту
                </button>
                <button class="btn btn-secondary" id="newSaveBtn" style="margin-left: 10px;">
                    <i class="fas fa-redo"></i> Сохранить еще
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Состояние приложения
    const appState = {
        isAuthenticated: false,
        currentUser: null,
        pendingProjectData: null,
        selectedProjectId: null,
        projects: [],
        currentStep: 'preview'
    };



    function getProjects(callback){
        const xhr2 = new XMLHttpRequest();
        xhr2.open('GET', `/get-list-projects.php?type=for_save`, true);
        xhr2.onreadystatechange = function() {
            if (xhr2.readyState === 4) {
                if (xhr2.status === 200) {
                    appState.projects = JSON.parse(xhr2.responseText);
                    if(callback != null)
                        callback();
                } else {
                    alert((`HTTP ошибка: ${xhr2.status}`));
                }
            }
        };
        xhr2.onerror = function() {
            alert(('Ошибка сети'));
        };
        xhr2.send();
    }
    // Мокап проектов пользователя
    const mockProjects = [
        { id: 1, name: "Мой первый проект", description: "Тестовый проект для демонстрации", created: "2023-09-01", updated: "2023-09-15", items: 5, isPublic: true },
        { id: 2, name: "Рабочий проект", description: "Основной рабочий проект с важными данными", created: "2023-08-10", updated: "2023-09-28", items: 12, isPublic: false },
        { id: 3, name: "Архивный проект", description: "Старый проект в архиве", created: "2023-05-15", updated: "2023-06-20", items: 8, isPublic: false },
        { id: 4, name: "Совместный проект", description: "Проект для совместной работы с коллегами", created: "2023-09-10", updated: "2023-09-25", items: 15, isPublic: true }
    ];

    // DOM элементы
    const elements = {
        userInfo: document.getElementById('userInfo'),
        logoutBtn: document.getElementById('logoutBtn'),
        statusMessage: document.getElementById('statusMessage'),
        loading: document.getElementById('loading'),
        dataPreviewSection: document.getElementById('dataPreviewSection'),
        dataPreview: document.getElementById('dataPreview'),
        continueBtn: document.getElementById('continueBtn'),
        authSection: document.getElementById('authSection'),
        username: document.getElementById('username'),
        password: document.getElementById('password'),
        loginBtn: document.getElementById('loginBtn'),
        projectSelectionSection: document.getElementById('projectSelectionSection'),
        projectsGrid: document.getElementById('projectsGrid'),
        optionExisting: document.getElementById('optionExisting'),
        optionNew: document.getElementById('optionNew'),
        existingProjects: document.getElementById('existingProjects'),
        newProjectForm: document.getElementById('newProjectForm'),
        projectName: document.getElementById('projectName'),
        projectDescription: document.getElementById('projectDescription'),
        saveProjectBtn: document.getElementById('saveProjectBtn'),
        cancelBtn: document.getElementById('cancelBtn'),
        successSection: document.getElementById('successSection'),
        successMessage: document.getElementById('successMessage'),
        viewProjectBtn: document.getElementById('viewProjectBtn'),
        newSaveBtn: document.getElementById('newSaveBtn')
    };

    // Функции для работы с сессией (localStorage в качестве примера)
    function saveToSession(key, data) {
        localStorage.setItem(key, JSON.stringify(data));
    }

    function getFromSession(key) {
        const data = localStorage.getItem(key);
        return data ? JSON.parse(data) : null;
    }

    function clearSession(key) {
        localStorage.removeItem(key);
    }
    const getSelectedAccessType = () => {
        const selectedRadio = document.querySelector('input[name="access_type"]:checked');
        return selectedRadio ? selectedRadio.value : null;
    };
    // Имитация получения POST данных
    function simulatePostRequest() {
        const xhr2 = new XMLHttpRequest();
        xhr2.open('GET', `/check_auth.php`, true);
        xhr2.onreadystatechange = function() {
            if (xhr2.readyState === 4) {
                if (xhr2.status === 200) {
                    const data = JSON.parse(xhr2.responseText);
                    //console.log(data)
                    if(data.result === "success"){
                        appState.isAuthenticated = true;
                        appState.currentUser = data.data;
                        getProjects();
                    }else{
                    }
                    updateUI();
                } else {
                    alert((`HTTP ошибка: ${xhr2.status}`));
                }
            }
        };
        xhr2.onerror = function() {
            alert(('Ошибка сети'));
        };
        xhr2.send();

    }

    // Обновление интерфейса
    function updateUI() {
        // Обновляем информацию о пользователе
        updateUserInfo();

        // Показываем соответствующий шаг
        showStep(appState.currentStep);

        // Если есть данные для предпросмотра, показываем их
        if (appState.pendingProjectData) {
            renderDataPreview();
        }
        // Если пользователь авторизован и на шаге выбора проекта, показываем проекты
        if (appState.isAuthenticated && appState.currentStep === 'selection') {
            getProjects(renderProjects);
        }
    }

    // Обновление информации о пользователе
    function updateUserInfo() {
        if (appState.isAuthenticated && appState.currentUser) {
            elements.userInfo.innerHTML = `
                    <div class="avatar">${appState.currentUser.username.charAt(0)}</div>
                    <div>
                        <div style="font-weight: 600;">${appState.currentUser.username}</div>
                    </div>
                `;
            elements.logoutBtn.classList.remove('hidden');
        } else {
            elements.userInfo.innerHTML = '<span>Не авторизован</span>';
            elements.logoutBtn.classList.add('hidden');
        }
    }

    // Показать определенный шаг
    function showStep(step) {
        // Скрываем все секции
        elements.dataPreviewSection.classList.add('hidden');
        elements.authSection.classList.add('hidden');
        elements.projectSelectionSection.classList.add('hidden');
        elements.successSection.classList.add('hidden');

        // Показываем нужную секцию
        switch(step) {
            case 'preview':
                elements.dataPreviewSection.classList.remove('hidden');
                break;
            case 'auth':
                elements.authSection.classList.remove('hidden');
                break;
            case 'selection':
                elements.projectSelectionSection.classList.remove('hidden');
                break;
            case 'success':
                elements.successSection.classList.remove('hidden');
                break;
        }

        appState.currentStep = step;
    }

    // Рендер предпросмотра данных
    function renderDataPreview() {
        const data = appState.pendingProjectData;

        let html = '<h3>Данные для сохранения:</h3><ul class="layers-list">';

        for(let i = 0 ; i < data.layers.length;i++){
            let pre = '<ol class="marker-list">';
            for(let j =0; j < data.layers[i].markers.length;j++){
                pre+= `<li>${data.layers[i].markers[j].name }</li>`;
            }
            pre+= "</ol>";

            html+=`<li>${data.layers[i].name} ${pre}</li>`;
        }
        html+='</ul>';

        // Данные проекта
        if (data.data) {
            html += `<div class="preview-item">
                    <span class="preview-key">Компонентов:</span>
                    <span class="preview-value">${data.data.components ? data.data.components.length : 0}</span>
                </div>`;

            if (data.data.metadata) {
                html += `<div class="preview-item">
                        <span class="preview-key">Источник:</span>
                        <span class="preview-value">${data.data.metadata.source}</span>
                    </div>`;
            }
        }

        elements.dataPreview.innerHTML = html;
    }

    function getModeLabel( mode) {
        switch (mode) {
            case "public":return "Публичный";
            case "protected":return "Защищенный";
            case "private":return "Приватный";
        }
        return mode;
    }
    // Рендер списка проектов
    function renderProjects() {
        if (!appState.projects || appState.projects.length === 0) {
            elements.projectsGrid.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #6c757d;">
                        <i class="far fa-folder-open" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <h3>У вас нет проектов</h3>
                        <p>Создайте новый проект для сохранения данных</p>
                    </div>
                `;
            return;
        }

        elements.projectsGrid.innerHTML = appState.projects.map(project => `
                <div class="project-card ${appState.selectedProjectId === project.id ? 'selected' : ''}"
                     data-project-id="${project.id}">
                    <div class="project-title">${project.name}</div>
                    <div class="project-meta">
                        <span>Создан: ${new Date(project.created_at).toLocaleDateString('ru-RU')}</span>
                    </div>
                    <div class="project-description">${project.description}</div>
                    <div class="project-stats">
                        <div class="stat">
                            <i class="fas ${project.access == "public" ? 'fa-globe' : 'fa-lock'}"></i>
                            <span>${getModeLabel(project.access)}</span>
                        </div>
                    </div>
                </div>
            `).join('');

        // Назначаем обработчики выбора проектов
        document.querySelectorAll('.project-card').forEach(card => {
            card.addEventListener('click', function() {
                // Снимаем выделение со всех карточек
                document.querySelectorAll('.project-card').forEach(c => {
                    c.classList.remove('selected');
                });

                // Выделяем выбранную карточку
                this.classList.add('selected');

                // Сохраняем выбранный ID
                appState.selectedProjectId = parseInt(this.dataset.projectId);
            });
        });
    }

    // Показать сообщение
    function showMessage(text, type = 'info') {
        elements.statusMessage.textContent = text;
        elements.statusMessage.className = `status-message status-${type}`;
        elements.statusMessage.classList.remove('hidden');

        // Автоматически скрыть через 5 секунд
        setTimeout(() => {
            elements.statusMessage.classList.add('hidden');
        }, 5000);
    }

    // Показать/скрыть загрузку
    function setLoading(isLoading) {
        if (isLoading) {
            elements.loading.classList.remove('hidden');
        } else {
            elements.loading.classList.add('hidden');
        }
    }

    // Имитация авторизации
    function login(username, password,ok,error) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/auth.php', true);
        var formData = new FormData();
        formData.append('login', username);
        formData.append('password', password);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) { // Запрос завершен
                if (xhr.status === 200) {
                    const xhr2 = new XMLHttpRequest();
                    xhr2.open('GET', `/check_auth.php`, true);
                    xhr2.onreadystatechange = function() {
                        if (xhr2.readyState === 4) {
                            if (xhr2.status === 200) {
                                    const data = JSON.parse(xhr2.responseText);
                                    if(data.result === "success"){
                                            appState.isAuthenticated = true;
                                            appState.currentUser = data.data;
                                            ok();
                                    }else{
                                        error();
                                    }
                            } else {
                                alert((`HTTP ошибка: ${xhr2.status}`));
                            }
                        }
                    };
                    xhr2.onerror = function() {
                        alert(('Ошибка сети'));
                    };
                    xhr2.send();

                } else {
                    alert('Ошибка сервера: ' + xhr.status);
                }
            }
        };
        xhr.onerror = function() {
            alert('Ошибка сети!');
        };
        xhr.send(formData);

    }

    // Имитация сохранения проекта
    function saveProject(projectId, projectName, isNew = false) {
        setLoading(true);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/save-project.php', true);
        var formData = new FormData();

        formData.append('type',"add");
        formData.append('data',JSON.stringify(appState.pendingProjectData));
        formData.append('is_new', isNew);
        formData.append('id', projectId);
        formData.append('name', projectName);
        formData.append('access', getSelectedAccessType());
        formData.append('description', elements.projectDescription.value);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) { // Запрос завершен
                if (xhr.status === 200) {
                    const data = JSON.parse(xhr.responseText);
                    if(data.result === "success"){
                        window.location = "/index.php";
                    }else{
                        setLoading(false);
                        alert('Ошибка ' + data.errors);
                    }
                } else {
                    alert('Ошибка сервера: ' + xhr.status);
                }
            }
        };
        xhr.onerror = function() {
            alert('Ошибка сети!');
        };
        xhr.send(formData);
    }

    // Обработчики событий
    elements.continueBtn.addEventListener('click', function() {
        if (appState.isAuthenticated) {
            // Пользователь уже авторизован, переходим к выбору проекта
            showStep('selection');
            updateUI();
        } else {
            // Требуется авторизация
            showStep('auth');
            updateUI();
        }
    });

    elements.loginBtn.addEventListener('click', function() {
        const username = elements.username.value.trim();
        const password = elements.password.value.trim();

        if (!username || !password) {
            showMessage('Введите логин и пароль', 'error');
            return;
        }

        login(username,password,function(){
            showMessage('Авторизация успешна!', 'success');
            // Переходим к выбору проекта
            showStep('selection');
            updateUI();

            // Очищаем поля формы
            elements.username.value = '';
            elements.password.value = '';

        }, function(){
            showMessage('Неверный логин или пароль', 'error');
        });
    });

    elements.logoutBtn.addEventListener('click', function() {
        appState.isAuthenticated = false;
        appState.currentUser = null;
        const xhr2 = new XMLHttpRequest();
        xhr2.open('GET', `/logout.php?saveproject=1`, true);

        xhr2.onreadystatechange = function() {
            if (xhr2.readyState === 4) {
                if (xhr2.status === 200) {
                    updateUI();
                } else {
                    alert((`HTTP ошибка: ${xhr2.status}`));
                }
            }
        };

        xhr2.onerror = function() {
            alert(('Ошибка логаута'));
        };
        xhr2.send();

        clearSession('currentUser');
        showMessage('Вы вышли из системы', 'info');
        updateUI();

        // Если мы на шаге выбора проекта, возвращаемся к предпросмотру
        if (appState.currentStep === 'selection') {
            showStep('preview');
        }
    });

    // Переключение между выбором существующего и созданием нового проекта
    elements.optionExisting.addEventListener('change', function() {
        if (this.checked) {
            elements.existingProjects.classList.remove('hidden');
            elements.newProjectForm.classList.add('hidden');
        }
    });

    elements.optionNew.addEventListener('change', function() {
        if (this.checked) {
            elements.existingProjects.classList.add('hidden');
            elements.newProjectForm.classList.remove('hidden');
            appState.selectedProjectId = null;
        }
    });

    elements.saveProjectBtn.addEventListener('click', async function() {
        const isNewProject = elements.optionNew.checked;

        if (isNewProject) {
            // Создание нового проекта
            const projectName = elements.projectName.value.trim();

            if (!projectName) {
                showMessage('Введите название проекта', 'error');
                return;
            }

            saveProject(null, projectName, true);

        } else {
            // Обновление существующего проекта
            if (!appState.selectedProjectId) {
                showMessage('Выберите проект для сохранения', 'error');
                return;
            }

             saveProject(appState.selectedProjectId);
        }
    });

    elements.cancelBtn.addEventListener('click', function() {
        // Возвращаемся к предпросмотру
        showStep('preview');

        // Сбрасываем выбор проекта
        appState.selectedProjectId = null;
        document.querySelectorAll('.project-card').forEach(card => {
            card.classList.remove('selected');
        });
    });

    elements.viewProjectBtn.addEventListener('click', function() {
        alert('В реальном приложении здесь будет переход к проекту');
        // window.location.href = `/project/${appState.selectedProjectId}`;
    });

    elements.newSaveBtn.addEventListener('click', function() {
        // Сброс состояния для нового сохранения
        appState.selectedProjectId = null;
        //appState.pendingProjectData = getFromSession('pendingProjectData') || mockProjectData;

        // Сброс формы
        elements.projectName.value = '';
        elements.projectDescription.value = '';
        elements.optionExisting.checked = true;
        elements.existingProjects.classList.remove('hidden');
        elements.newProjectForm.classList.add('hidden');

        showStep('preview');
        updateUI();
    });

    // Имитация обработки POST запроса при загрузке страницы
    window.addEventListener('DOMContentLoaded', function() {
        // Проверяем, есть ли данные в сессии (например, если пользователь перезагрузил страницу)
        const savedData = <?= json_encode($_SESSION["project_data"]) ?>;
        if (savedData) {
            appState.pendingProjectData = savedData;
        }

        // Запускаем имитацию POST запроса
        simulatePostRequest();

        // Также можно имитировать получение данных из URL параметров
        // или других источников в реальном приложении
    });

    // Обработка отправки формы авторизации по Enter
    elements.password.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            elements.loginBtn.click();
        }
    });
</script>
</body>
</html>