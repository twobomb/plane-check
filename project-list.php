<?php

use Medoo\Medoo;

include "includes/db.php";
require_once "includes/auth_check.php";
include "includes/OneTimeToken.php";

if(isset($_GET["gen-link-for"])){
    $id_proj = $_GET["gen-link-for"];

    $otp = new OneTimeToken();
    $token = $otp->generateToken([
        'user_id' => getUserId(),
        'project_id' => $id_proj
    ], 1, 15); // Однократное использование, 15 минут жизни

    header("location:".CORE::MAPSITE."?load-token=$token");die;
}

$projects = getAllowedProjects("for_open");
$result = [];
foreach ($projects as $prj){
    array_push($result,[
        "id"=>$prj["id"],
       "title"=>$prj["name"],
       "description"=>$prj["description"],
       "author"=>CORE::$db->get("user","username",["id"=>$prj["user_id"]]),
       "created"=>$prj["created_at"],
       "updated"=>$prj["updated_at"],
       "access"=>$prj["access"],
       "layers"=>CORE::$db->query("SELECT name, (SELECT COUNT(*) FROM point WHERE point.layer_id = layer.id) as points FROM layer WHERE project_id = '$prj[id]'")->fetchAll()
    ]);
}

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проекты карт - Система управления</title><link rel="stylesheet" href="/css/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="/css/Inter-4.1/web/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/main.css" >
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

        .search-container {
            display: flex;
            gap: 12px;
            max-width: 600px;
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

        .search-btn {
            padding: 12px 24px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-btn:hover {
            background: #1a252f;
        }

        .projects-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .projects-header h2 {
            font-size: 22px;
            color: #2c3e50;
        }

        .projects-count {
            background: #ecf0f1;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
        }

        .project-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .project-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            position: relative;
        }

        .project-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .project-meta {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .project-author {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .project-access {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .access-private {
            background: #f8d7da;
            color: #721c24;
        }

        .access-protected {
            background: #fff3cd;
            color: #856404;
        }

        .access-public {
            background: #d4edda;
            color: #155724;
        }

        .project-dates {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #6c757d;
            padding-top: 10px;
            border-top: 1px solid #f1f1f1;
        }

        .date-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .project-content {
            padding: 20px;
            flex-grow: 1;
        }

        .project-description {
            color: #495057;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 14px;
        }

        .project-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            min-width: 80px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        }

        .layers-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            border: 1px solid #e9ecef;
        }

        .layers-list h4 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .layer-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }

        .layer-item:last-child {
            border-bottom: none;
        }

        .layer-name {
            color: #495057;
        }

        .layer-points {
            color: #3498db;
            font-weight: 600;
        }

        .project-footer {
            padding: 16px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .project-actions {
            display: flex;
            gap: 8px;
        }

        .icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px solid #dee2e6;
            color: #495057;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .icon-btn:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
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

        .sort-options {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .sort-btn {
            padding: 6px 12px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            font-size: 13px;
            color: #495057;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .sort-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
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

            .search-container {
                flex-direction: column;
            }

            .search-btn {
                width: 100%;
                justify-content: center;
            }

            .project-stats {
                flex-direction: column;
                gap: 10px;
            }

            .stat-item {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-map"></i> Проекты карт</h1>
        <div class="header-actions">
            <div class="header-profile">
                <span class="profile-username"><?= getUser()["username"]?></span>
                <a href="/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Выход
                </a>
            </div>
            <button class="btn btn-primary">
                <i class="fas fa-plus"></i> Новый проект
            </button>
        </div>
    </div>

    <div class="content">
        <div class="filters-section">
            <h2><i class="fas fa-search"></i> Поиск проектов</h2>
            <div class="search-container">
                <input type="text" class="search-input" id="searchInput" placeholder="Введите название проекта...">
                <button class="search-btn" id="searchBtn">
                    <i class="fas fa-search"></i> Найти
                </button>
            </div>
            <div class="sort-options">
                <button class="sort-btn active" data-sort="newest">Сначала новые</button>
                <button class="sort-btn" data-sort="oldest">Сначала старые</button>
                <button class="sort-btn" data-sort="name">По названию</button>
            </div>
        </div>

        <div class="projects-header">
            <h2>Доступные проекты для просмотра</h2>
            <div class="projects-count">Найдено: 7 проектов</div>
        </div>

        <div class="projects-grid" id="projectsContainer">
            <!-- Проекты будут загружены через JavaScript -->
        </div>

    </div>
</div>

<script>
    // Моковые данные для проектов карт
    const projectsData = <?= json_encode($result,JSON_UNESCAPED_UNICODE) ?>;

    // Функция для форматирования даты
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }

    // Функция для отображения типа доступа
    function getAccessText(access) {
        const accessMap = {
            'private': 'Приватный',
            'protected': 'Защищенный',
            'public': 'Публичный'
        };
        return accessMap[access] || access;
    }

    // Функция для получения общего количества точек
    function getTotalPoints(layers) {
        return layers.reduce((total, layer) => total + parseInt(layer.points), 0);
    }

    // Функция для отрисовки проектов
    function renderProjects(projects) {
        const container = document.getElementById('projectsContainer');

        if (projects.length === 0) {
            container.innerHTML = `
                    <div class="empty-state">
                        <i class="far fa-map"></i>
                        <h3>Проекты не найдены</h3>
                        <p>Попробуйте изменить поисковый запрос</p>
                    </div>
                `;
            return;
        }

        container.innerHTML = projects.map(project => {
            const totalPoints = getTotalPoints(project.layers);
            const totalLayers = project.layers.length;

            return `
                <div class="project-card">
                    <div class="project-header">
                        <div class="project-title">${project.title}</div>
                        <div class="project-meta">
                            <div class="project-author">
                                <i class="fas fa-user"></i>
                                ${project.author}
                            </div>
                            <div class="project-access access-${project.access}">
                                ${getAccessText(project.access)}
                            </div>
                        </div>
                        <div class="project-dates">
                            <div class="date-item">
                                <i class="far fa-calendar-plus"></i>
                                Создан: ${formatDate(project.created)}
                            </div>
                            <div class="date-item">
                                <i class="far fa-calendar-check"></i>
                                Изменен: ${formatDate(project.updated)}
                            </div>
                        </div>
                    </div>

                    <div class="project-content">
                        ${project.description ? `
                        <div class="project-description">
                            ${project.description}
                        </div>
                        ` : ''}

                        <div class="project-stats">
                            <div class="stat-item">
                                <div class="stat-value">${totalLayers}</div>
                                <div class="stat-label">Слоев</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${totalPoints}</div>
                                <div class="stat-label">Всего точек</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${Math.round(totalPoints / totalLayers)}</div>
                                <div class="stat-label">Среднее на слой</div>
                            </div>
                        </div>

                        <div class="layers-list">
                            <h4><i class="fas fa-layer-group"></i> Слои проекта</h4>
                            ${project.layers.map(layer => `
                                <div class="layer-item">
                                    <span class="layer-name">${layer.name}</span>
                                    <span class="layer-points">${layer.points} точек</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>

                    <div class="project-footer">
                        <div class="project-actions">
                            <button class="icon-btn" title="Открыть карту">
                                <i class="fas fa-map-marked-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `}).join('');

        // Обновляем счетчик
        document.querySelector('.projects-count').textContent = `Найдено: ${projects.length} проектов`;

        // Назначаем обработчики для кнопок
        document.querySelectorAll('.icon-btn[title="Открыть карту"]').forEach((btn, index) => {
            btn.addEventListener('click', () => {
                window.location = "/project-list.php?gen-link-for="+ projects[index].id;
            });
        });

        document.querySelectorAll('.icon-btn[title="Редактировать"]').forEach((btn, index) => {
            btn.addEventListener('click', () => {
                alert(`Редактируем проект: "${projects[index].title}"`);
            });
        });

        document.querySelectorAll('.icon-btn[title="Поделиться"]').forEach((btn, index) => {
            btn.addEventListener('click', () => {
                alert(`Поделиться проектом: "${projects[index].title}"`);
            });
        });

        document.querySelectorAll('.icon-btn[title="Экспорт"]').forEach((btn, index) => {
            btn.addEventListener('click', () => {
                alert(`Экспорт проекта: "${projects[index].title}"`);
            });
        });
    }

    // Функция для поиска проектов
    function searchProjects() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
        const sortType = document.querySelector('.sort-options .active').dataset.sort;

        let filteredProjects = [...projectsData];

        // Фильтрация по названию
        if (searchTerm) {
            filteredProjects = filteredProjects.filter(project =>
                project.title.toLowerCase().includes(searchTerm)
            );
        }

        // Сортировка
        filteredProjects.sort((a, b) => {
            switch(sortType) {
                case 'newest':
                    return new Date(b.created) - new Date(a.created);
                case 'oldest':
                    return new Date(a.created) - new Date(b.created);
                case 'name':
                    return a.title.localeCompare(b.title);
                default:
                    return 0;
            }
        });

        renderProjects(filteredProjects);
    }

    // Инициализация
    document.addEventListener('DOMContentLoaded', function() {
        // Первоначальная отрисовка
        renderProjects(projectsData);

        // Назначение обработчиков событий для поиска
        document.getElementById('searchBtn').addEventListener('click', searchProjects);

        // Поиск при нажатии Enter в поле ввода
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProjects();
            }
        });

        // Live search при вводе (опционально)
        document.getElementById('searchInput').addEventListener('input', function() {
            // Для live search раскомментируйте следующую строку:
            // searchProjects();
        });

        // Обработчики для сортировки
        document.querySelectorAll('.sort-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Убираем активный класс со всех кнопок
                document.querySelectorAll('.sort-btn').forEach(b => {
                    b.classList.remove('active');
                });

                // Добавляем активный класс к нажатой кнопке
                this.classList.add('active');

                // Выполняем поиск с новой сортировкой
                searchProjects();
            });
        });

        // Обработчик для кнопки "Новый проект"
        document.querySelector('.btn-primary').addEventListener('click', function() {
            window.location = "<?= CORE::MAPSITE ?>"
        });


        // Обработчики для пагинации
        document.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Убираем активный класс со всех кнопок
                document.querySelectorAll('.page-btn').forEach(b => {
                    b.classList.remove('active');
                });

                // Добавляем активный класс к нажатой кнопке
                this.classList.add('active');

                // В реальном приложении здесь будет загрузка соответствующей страницы
                alert('Загрузка страницы проектов...');
            });
        });
    });
</script>
</body>
</html>