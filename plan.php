<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Планы - Система управления</title>
    <link rel="stylesheet" href="/css/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="/css/Inter-4.1/web/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/plan.css" >
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> Управление планами</h1>
            <div class="header-actions">
                <button class="btn btn-secondary">
                    <i class="fas fa-download"></i> Экспорт
                </button>
                <button class="btn btn-primary" id="newPlanBtn">
                    <i class="fas fa-plus"></i> Новый план
                </button>
            </div>
        </div>
        
        <div class="content">
            <div class="filters-section">
                <h2><i class="fas fa-filter"></i> Фильтры и сортировка</h2>
                
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="departmentFilter"><i class="fas fa-sitemap"></i> Подразделение</label>
                        <select id="departmentFilter" class="filter-select" multiple>
                            <option value="all">Все подразделения</option>
                            <option value="Маркетинг">Маркетинг</option>
                            <option value="Продажи">Продажи</option>
                            <option value="ИТ">ИТ</option>
                            <option value="HR">HR</option>
                            <option value="Администрация">Администрация</option>
                            <option value="Аналитика">Аналитика</option>
                            <option value="Операции">Операции</option>
                            <option value="Руководство">Руководство</option>
                            <option value="Стратегия">Стратегия</option>
                            <option value="Производство">Производство</option>
                            <option value="Качество">Качество</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="far fa-calendar-alt"></i> Период создания</label>
                        <div class="date-range">
                            <input type="date" id="dateFrom" class="filter-input" placeholder="Дата с">
                            <span>—</span>
                            <input type="date" id="dateTo" class="filter-input" placeholder="Дата по">
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-flag"></i> Статус</label>
                        <div class="status-filter">
                            <button class="status-btn status-all active" data-status="all">Все</button>
                            <button class="status-btn status-pending" data-status="pending">Ожидание</button>
                            <button class="status-btn status-inprogress" data-status="inprogress">В работе</button>
                            <button class="status-btn status-completed" data-status="completed">Выполнен</button>
                            <button class="status-btn status-rejected" data-status="rejected">Отклонен</button>
                        </div>
                    </div>
                </div>
                
                <div class="filters-bottom">
                    <div class="sorting-group">
                        <span>Сортировка:</span>
                        <button class="sort-btn active" data-sort="created-desc">
                            <i class="fas fa-sort-amount-down"></i> Сначала новые
                        </button>
                        <button class="sort-btn" data-sort="created-asc">
                            <i class="fas fa-sort-amount-up"></i> Сначала старые
                        </button>
                        <button class="sort-btn" data-sort="deadline-desc">
                            <i class="fas fa-calendar-times"></i> Ближайший дедлайн
                        </button>
                        <button class="sort-btn" data-sort="deadline-asc">
                            <i class="fas fa-calendar-check"></i> Дальний дедлайн
                        </button>
                    </div>
                    
                    <div class="filter-actions">
                        <button class="btn btn-reset" id="resetFiltersBtn">
                            <i class="fas fa-redo"></i> Сбросить всё
                        </button>
                        <button class="btn btn-filter" id="applyFiltersBtn">
                            <i class="fas fa-search"></i> Применить
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="plans-header">
                <h2>Все планы</h2>
                <div class="plans-count">Найдено: 8 планов</div>
            </div>
            
            <div class="plans-grid" id="plansContainer">
                <!-- Планы будут загружены через JavaScript -->
            </div>
            
            <div class="pagination">
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>

    <script>
        // Моковые данные для планов
        const plansData = [
            {
                id: 1,
                title: "Запуск нового продукта",
                description: "Подготовка к запуску нового продукта на рынок, включая маркетинговую кампанию и обучение отдела продаж.",
                dateType: "exact",
                dateValue: "2023-10-15",
                deadlineDate: "2023-10-15",
                status: "inprogress",
                files: 3,
                geoPoints: 2,
                departments: ["Маркетинг", "Продажи"],
                subplans: 2,
                created: "2023-09-10",
                updated: "2023-09-28"
            },
            {
                id: 2,
                title: "Обновление ИТ-инфраструктуры",
                description: "Модернизация серверного оборудования и переход на новую версию CRM системы.",
                dateType: "month",
                dateValue: "2023-11",
                deadlineDate: "2023-11-30",
                status: "pending",
                files: 5,
                geoPoints: 1,
                departments: ["ИТ"],
                subplans: 0,
                created: "2023-09-05",
                updated: "2023-09-05"
            },
            {
                id: 3,
                title: "Корпоративное мероприятие",
                description: "Организация ежегодного корпоративного мероприятия для сотрудников компании.",
                dateType: "exact",
                dateValue: "2023-12-20",
                deadlineDate: "2023-12-20",
                status: "pending",
                files: 8,
                geoPoints: 3,
                departments: ["HR", "Администрация"],
                subplans: 4,
                created: "2023-08-15",
                updated: "2023-09-25"
            },
            {
                id: 4,
                title: "Оптимизация бизнес-процессов",
                description: "Анализ и оптимизация ключевых бизнес-процессов для повышения эффективности.",
                dateType: "year",
                dateValue: "2023",
                deadlineDate: "2023-12-31",
                status: "completed",
                files: 12,
                geoPoints: 0,
                departments: ["Аналитика", "Операции"],
                subplans: 3,
                created: "2023-01-10",
                updated: "2023-08-30"
            },
            {
                id: 5,
                title: "Стратегическое планирование",
                description: "Разработка стратегии развития компании на следующий год.",
                dateType: "month",
                dateValue: "2023-10",
                deadlineDate: "2023-10-31",
                status: "inprogress",
                files: 6,
                geoPoints: 0,
                departments: ["Руководство", "Стратегия"],
                subplans: 1,
                created: "2023-09-01",
                updated: "2023-09-20"
            },
            {
                id: 6,
                title: "Исследование рынка",
                description: "Проведение исследования новых рынков для возможной экспансии.",
                dateType: "without",
                dateValue: null,
                deadlineDate: null,
                status: "pending",
                files: 4,
                geoPoints: 5,
                departments: ["Аналитика", "Маркетинг"],
                subplans: 0,
                created: "2023-09-15",
                updated: "2023-09-15"
            },
            {
                id: 7,
                title: "Обучение персонала",
                description: "Проведение тренингов для сотрудников отдела продаж.",
                dateType: "exact",
                dateValue: "2023-11-05",
                deadlineDate: "2023-11-05",
                status: "completed",
                files: 7,
                geoPoints: 1,
                departments: ["HR", "Продажи"],
                subplans: 0,
                created: "2023-08-20",
                updated: "2023-09-10"
            },
            {
                id: 8,
                title: "Внедрение системы контроля качества",
                description: "Внедрение новой системы контроля качества продукции.",
                dateType: "exact",
                dateValue: "2023-10-30",
                deadlineDate: "2023-10-30",
                status: "rejected",
                files: 3,
                geoPoints: 2,
                departments: ["Производство", "Качество"],
                subplans: 2,
                created: "2023-08-05",
                updated: "2023-09-12"
            }
        ];

        // Все доступные подразделения
        const allDepartments = ["Маркетинг", "Продажи", "ИТ", "HR", "Администрация", "Аналитика", "Операции", "Руководство", "Стратегия", "Производство", "Качество"];

        // Состояние фильтров
        let filterState = {
            departments: ['all'],
            dateFrom: null,
            dateTo: null,
            status: 'all',
            sortBy: 'created-desc'
        };

        // Функция для форматирования даты
        function formatDate(dateType, dateValue) {
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
                const date = new Date(dateValue + '-01');
                return date.toLocaleDateString('ru-RU', { 
                    month: 'long', 
                    year: 'numeric' 
                });
            }
            
            if (dateType === 'year') {
                return dateValue;
            }
            
            return dateValue;
        }

        // Функция для отображения статуса
        function getStatusText(status) {
            const statusMap = {
                'pending': 'Ожидание',
                'inprogress': 'В работе',
                'completed': 'Выполнен',
                'rejected': 'Отклонен'
            };
            return statusMap[status] || status;
        }

        // Функция для сортировки планов
        function sortPlans(plans, sortBy) {
            const sortedPlans = [...plans];
            
            switch(sortBy) {
                case 'created-desc':
                    sortedPlans.sort((a, b) => new Date(b.created) - new Date(a.created));
                    break;
                case 'created-asc':
                    sortedPlans.sort((a, b) => new Date(a.created) - new Date(b.created));
                    break;
                case 'deadline-desc':
                    // Сначала планы с дедлайном, потом без, сортировка по ближайшему
                    sortedPlans.sort((a, b) => {
                        if (!a.deadlineDate && !b.deadlineDate) return 0;
                        if (!a.deadlineDate) return 1;
                        if (!b.deadlineDate) return -1;
                        return new Date(a.deadlineDate) - new Date(b.deadlineDate);
                    });
                    break;
                case 'deadline-asc':
                    // Сначала планы с дедлайном, потом без, сортировка по дальнему
                    sortedPlans.sort((a, b) => {
                        if (!a.deadlineDate && !b.deadlineDate) return 0;
                        if (!a.deadlineDate) return 1;
                        if (!b.deadlineDate) return -1;
                        return new Date(b.deadlineDate) - new Date(a.deadlineDate);
                    });
                    break;
            }
            
            return sortedPlans;
        }

        // Функция для фильтрации планов
        function filterPlans() {
            let filteredPlans = [...plansData];
            
            // Фильтрация по подразделениям
            if (!filterState.departments.includes('all') && filterState.departments.length > 0) {
                filteredPlans = filteredPlans.filter(plan => {
                    return plan.departments.some(dept => filterState.departments.includes(dept));
                });
            }
            
            // Фильтрация по периоду создания
            if (filterState.dateFrom) {
                const fromDate = new Date(filterState.dateFrom);
                filteredPlans = filteredPlans.filter(plan => new Date(plan.created) >= fromDate);
            }
            
            if (filterState.dateTo) {
                const toDate = new Date(filterState.dateTo);
                toDate.setHours(23, 59, 59, 999);
                filteredPlans = filteredPlans.filter(plan => new Date(plan.created) <= toDate);
            }
            
            // Фильтрация по статусу
            if (filterState.status !== 'all') {
                filteredPlans = filteredPlans.filter(plan => plan.status === filterState.status);
            }
            
            // Сортировка
            filteredPlans = sortPlans(filteredPlans, filterState.sortBy);
            
            return filteredPlans;
        }

        // Функция для отрисовки планов
        function renderPlans(plans) {
            const container = document.getElementById('plansContainer');
            
            if (plans.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="far fa-calendar-times"></i>
                        <h3>Планы не найдены</h3>
                        <p>Попробуйте изменить параметры фильтрации</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = plans.map(plan => `
                <div class="plan-card">
                    <div class="plan-header">
                        <div class="plan-title">${plan.title}</div>
                        <div class="plan-meta">
                            <div class="plan-date">
                                <i class="far fa-calendar"></i>
                                ${formatDate(plan.dateType, plan.dateValue)}
                            </div>
                            <div class="plan-status status-${plan.status}">
                                ${getStatusText(plan.status)}
                            </div>
                        </div>
                        ${plan.departments.length > 0 ? `
                        <div class="department-tags">
                            ${plan.departments.map(dept => `<span class="department-tag">${dept}</span>`).join('')}
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="plan-content">
                        <div class="plan-description">
                            ${plan.description}
                        </div>
                        
                        <div class="plan-features">
                            <div class="feature-tag">
                                <i class="fas fa-paperclip"></i> Файлов: ${plan.files}
                            </div>
                            <div class="feature-tag">
                                <i class="fas fa-map-marker-alt"></i> Геоточек: ${plan.geoPoints}
                            </div>
                            ${plan.subplans > 0 ? `
                            <div class="feature-tag">
                                <i class="fas fa-project-diagram"></i> Подпланов: ${plan.subplans}
                            </div>
                            ` : ''}
                            <div class="feature-tag">
                                <i class="fas fa-calendar-plus"></i> Создан: ${new Date(plan.created).toLocaleDateString('ru-RU')}
                            </div>
                        </div>
                    </div>
                    
                    <div class="plan-footer">
                        <div class="plan-history">
                            <div>Изменен: ${new Date(plan.updated).toLocaleDateString('ru-RU')}</div>
                        </div>
                        <div class="plan-actions">
                            <button class="icon-btn" title="Просмотреть">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="icon-btn" title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="icon-btn" title="Привязать файлы">
                                <i class="fas fa-paperclip"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Обновляем счетчик
            document.querySelector('.plans-count').textContent = `Найдено: ${plans.length} планов`;
            
            // Добавляем обработчики событий для кнопок действий
            document.querySelectorAll('.plan-actions .icon-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.getAttribute('title');
                    alert(`Действие: ${action}. В полной версии будет реализовано.`);
                });
            });
        }

        // Функция для обновления UI фильтров
        function updateFilterUI() {
            // Статусы
            document.querySelectorAll('.status-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.status === filterState.status) {
                    btn.classList.add('active');
                }
            });
            
            // Сортировка
            document.querySelectorAll('.sort-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.sort === filterState.sortBy) {
                    btn.classList.add('active');
                }
            });
            
            // Даты
            document.getElementById('dateFrom').value = filterState.dateFrom || '';
            document.getElementById('dateTo').value = filterState.dateTo || '';
            
            // Подразделения (упрощенно)
            const departmentSelect = document.getElementById('departmentFilter');
            Array.from(departmentSelect.options).forEach(option => {
                option.selected = filterState.departments.includes(option.value);
            });
        }

        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            // Первоначальная отрисовка
            const initialPlans = filterPlans();
            renderPlans(initialPlans);
            
            // Обработчики для кнопок статусов
            document.querySelectorAll('.status-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    filterState.status = this.dataset.status;
                    updateFilterUI();
                    renderPlans(filterPlans());
                });
            });
            
            // Обработчики для кнопок сортировки
            document.querySelectorAll('.sort-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    filterState.sortBy = this.dataset.sort;
                    updateFilterUI();
                    renderPlans(filterPlans());
                });
            });
            
            // Обработчики для фильтра по датам
            document.getElementById('dateFrom').addEventListener('change', function() {
                filterState.dateFrom = this.value;
            });
            
            document.getElementById('dateTo').addEventListener('change', function() {
                filterState.dateTo = this.value;
            });
            
            // Обработчик для фильтра по подразделениям
            document.getElementById('departmentFilter').addEventListener('change', function() {
                const selectedOptions = Array.from(this.selectedOptions);
                filterState.departments = selectedOptions.map(option => option.value);
                if (filterState.departments.length === 0) {
                    filterState.departments = ['all'];
                }
            });
            
            // Обработчик для кнопки "Применить"
            document.getElementById('applyFiltersBtn').addEventListener('click', function() {
                renderPlans(filterPlans());
            });
            
            // Обработчик для кнопки "Сбросить всё"
            document.getElementById('resetFiltersBtn').addEventListener('click', function() {
                filterState = {
                    departments: ['all'],
                    dateFrom: null,
                    dateTo: null,
                    status: 'all',
                    sortBy: 'created-desc'
                };
                
                updateFilterUI();
                renderPlans(filterPlans());
            });
            
            // Обработчик для кнопки "Новый план"
            document.getElementById('newPlanBtn').addEventListener('click', function() {
                alert('Форма создания нового плана будет открыта в следующей версии');
            });
            
            // Инициализация UI фильтров
            updateFilterUI();
        });
    </script>
</body>
</html>