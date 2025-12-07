<?PHP
require_once "includes/db.php";


function validatePlan($p){
    $errors = [];
    $p["name"] = trim($p["name"]);
    if(empty($p["name"]))
        array_push($errors,"Введите название!");
    if(empty(trim(strip_tags($p["content"]))))
        array_push($errors,"Введите описание!");
    else{
        $processor = new Base64ImageProcessor();
        $p["content"] = $processor->processHtml($p["content"]);
    }

    if( !in_array($p["date_type"],array_keys(CORE::$dateTypes)) )
        array_push($errors,"Неверный тип даты!");
    switch ($p["date_type"]){
        case "exact":
            if(empty(trim($p["date_value"])))
                array_push($errors,"Введите дату выполнения!");
            break;
        case "month":
            if(empty(trim($p["date_value"])))
                array_push($errors,"Введите дату выполнения!");
            else if (!preg_match('/^\d{4}-\d{2}$/', $p["date_value"])) {
                    array_push($errors,"'Неверный формат даты. Используйте YYYY-MM'!");
            }else
                $p["date_value"] = DateTime::createFromFormat('Y-m-d', $p["date_value"]. '-01')->format('Y-m-t');
            break;
        case "year":
            if(empty(trim($p["date_value"])))
                array_push($errors,"Введите дату выполнения!");
            else if (!preg_match('/^\d{4}$/', $p["date_value"]))
                array_push($errors,"'Неверный формат даты. Используйте YYYY'!");
            else
                $p["date_value"] = $p["date_value"]."-12-31";
            break;
    }
    if( !in_array($p["status"],array_keys(CORE::$statuses)) )
        array_push($errors,"Неверный статус!");

    if(CORE::$db->count("department",["id"=>$p["departments"]]) != count($p["departments"]))
        array_push($errors,"Указаны неверные подразделения!");
    if(CORE::$db->count("point",["id"=>$p["geoPoints"]]) != count($p["geoPoints"]))
        array_push($errors,"Указаны неверные геоточки!");
    if (!empty($p["parent_id"]) && CORE::$db->count("plan",["id"=>$p["parent_id"]]))
        array_push($errors,"Родительский план не найден!");

    return ["errors"=>$errors,"data"=>$p];
}

$plan = null;
if($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET["id"])) {
        $res = CORE::$db->select("plan", "*", ["id" => $_GET["id"]]);
        if (count($res) == 0) {
            echo "План не найден!";
            die;
        }
        $plan = $res[0];
    }
}
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $r = validatePlan($_POST);
    $data = $r["data"];

    $err = $r["errors"];

    $res = ["result"=>"ok"];
    if(count($err) > 0){
        $res["result"] = 'error';
        $res["errors"] = $err;
    }
    else if (!empty($data["plan_id"])){
        try {
        $pid =$data["plan_id"];
        CORE::$db->update("plan",
            [
            "name"=>$data["name"],
            "content"=>$data["content"],
            "date_type"=>$data["date_type"],
            "status"=>$data["status"],
            "parent_id"=>empty($data["parent_id"])? null : $data["parent_id"],
            "date_value"=>empty($data["date_value"])? null : $data["date_value"]
        ],["id"=>$pid]);
            if(!is_null(CORE::$db->error))
                throw new Exception("Ошибка БД! ".CORE::$db->error);

            CORE::$db->delete("department_to_plan", ["plan_id"=>$pid]);
            if (!is_null(CORE::$db->error))
                throw new Exception("Ошибка БД! " . CORE::$db->error);

            foreach ($data["departments"] as $depid) {
                CORE::$db->insert("department_to_plan", [
                    "department_id" => $depid,
                    "plan_id" => $pid
                ]);
                if (!is_null(CORE::$db->error))
                    throw new Exception("Ошибка БД! " . CORE::$db->error);
            }


            CORE::$db->delete("point_to_plan", ["plan_id"=>$pid]);
            if (!is_null(CORE::$db->error))
                throw new Exception("Ошибка БД! " . CORE::$db->error);

            foreach ($data["geoPoints"] as $geoid) {
                CORE::$db->insert("point_to_plan", [
                    "point_id" => $geoid,
                    "plan_id" => $pid
                ]);
                if(!is_null(CORE::$db->error))
                    throw new Exception("Ошибка БД! ".CORE::$db->error);
            }


        }catch (Exception $ex){
            $res["result"] = 'error';
            $res["errors"] = [$ex->getMessage()];
        }

    }
    else {
        try {
            CORE::$db->insert("plan",[
                "name"=>$data["name"],
                "content"=>$data["content"],
                "date_type"=>$data["date_type"],
                "status"=>$data["status"],
                "user_id"=>getUserId(),
                "parent_id"=>empty($data["parent_id"])? null : $data["parent_id"],
                "date_value"=>empty($data["date_value"])? null : $data["date_value"]
            ]);
            if(!is_null(CORE::$db->error))
                throw new Exception("Ошибка БД! ".CORE::$db->error);

            $pid = CORE::$db->id();
            foreach ($data["departments"] as $depid) {
                CORE::$db->insert("department_to_plan", [
                    "department_id" => $depid,
                    "plan_id" => $pid
                ]);
                if(!is_null(CORE::$db->error))
                    throw new Exception("Ошибка БД! ".CORE::$db->error);
            }

            foreach ($data["geoPoints"] as $geoid) {
                CORE::$db->insert("point_to_plan", [
                    "point_id" => $geoid,
                    "plan_id" => $pid
                ]);
                if(!is_null(CORE::$db->error))
                    throw new Exception("Ошибка БД! ".CORE::$db->error);
            }

            $file_ids  =  [];
            foreach ($_FILES["file"]["tmp_name"] as $i=>$tmpname) {
                CORE::$db->insert("files",[
                    "url"=>saveUniFile("uploaded/planfiles/",$tmpname,$_FILES["file"]["name"][$i]),
                    "name"=>$_FILES["file"]["name"][$i]
                ]);
                if(!is_null(CORE::$db->error))
                    throw new Exception("Ошибка БД! ".CORE::$db->error);
                array_push($file_ids,CORE::$db->id());
            }
            foreach ($file_ids as $fid) {
                CORE::$db->insert("file_to_plan", [
                    "file_id" => $fid,
                    "plan_id" => $pid
                ]);
                if(!is_null(CORE::$db->error))
                    throw new Exception("Ошибка БД! ".CORE::$db->error);
            }

        }catch (Exception $ex){
            $res["result"] = 'error';
            $res["errors"] = [$ex->getMessage()];
        }
    }

    responseJson($res);
    die;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание плана - Система управления</title>
    <link rel="stylesheet" href="/css/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="/css/Inter-4.1/web/inter.css" rel="stylesheet">
    
    <!-- Библиотеки для редактора и выпадающих списков -->
    <link href="/css/quill.snow.css" rel="stylesheet">
    <link href="/css/select2.min.css" rel="stylesheet">
    <link href="/css/addplan.css" rel="stylesheet">

</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-plus"></i> <?= $plan == null?"Создание нового плана":"Редактирование плана ID #$plan[id]" ?></h1>
            <div class="header-actions">
                <button class="btn btn-secondary" id="cancelBtn">
                    <i class="fas fa-times"></i> Отмена
                </button>
                <button class="btn btn-primary" id="savePlanBtn">
                    <i class="fas fa-check"></i> Сохранить план
                </button>
            </div>
        </div>
        
        <div class="content">
            <div class="form-container">
                <div class="left-column">
                    <!-- Основная информация -->
                    <div class="form-section">
                        <h2><i class="fas fa-info-circle"></i> Основная информация</h2>
                        
                        <div class="form-group">
                            <label for="planTitle" class="required">Название плана</label>
                            <input type="text" id="planTitle" class="form-input" placeholder="Введите название плана" value="<?=$plan?$plan["name"]:""?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="planDescription" class="required">Описание плана</label>
                            <div class="editor-container">
                                <div id="editor"><?=$plan?$plan["content"]:""?></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="dateType" class="required">Тип даты</label>
                            <div class="date-options">
                                <label class="date-option">
                                    <input type="radio" name="dateType" value="exact" <?=($plan && $plan["date_type"] === "exact")?"checked":""  ?>>
                                    Конкретная дата
                                </label>
                                <label class="date-option">
                                    <input type="radio" name="dateType" value="month" <?=($plan && $plan["date_type"] === "month" || !$plan)?"checked":""  ?>>
                                    Месяц
                                </label>
                                <label class="date-option">
                                    <input type="radio" name="dateType" value="year" <?=($plan && $plan["date_type"] === "year")?"checked":""  ?>>
                                    Год
                                </label>
                                <label class="date-option">
                                    <input type="radio" name="dateType" value="without"  <?=($plan && $plan["date_type"] === "without")?"checked":""  ?>>
                                    Без даты
                                </label>
                            </div>
                            
                            <div class="date-inputs" id="exactDateInput">
                                <label for="exactDate">Дата выполнения</label>
                                <input type="date" id="exactDate" class="form-date" value="<?=($plan)?DateTime::createFromFormat('Y-m-d', $plan["date_value"])->format("Y-m-d"):((new DateTime())->format("Y-m-d"))  ?>">
                            </div>
                            
                            <div class="date-inputs hidden" id="monthDateInput">
                                <label for="monthDate">Месяц и год</label>
                                <input type="month" id="monthDate" class="form-date" value="<?=($plan)?DateTime::createFromFormat('Y-m-d', $plan["date_value"])->format("Y-m"):((new DateTime())->format("Y-m"))  ?>">
                            </div>
                            
                            <div class="date-inputs hidden" id="yearDateInput">
                                <label for="yearDate">Год</label>
                                <input type="number" id="yearDate" class="form-input" min="2025" max="2030" value="<?=($plan)?DateTime::createFromFormat('Y-m-d', $plan["date_value"])->format("Y"):((new DateTime())->format("Y"))  ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Статус плана -->
                    <div class="form-section">
                        <h2><i class="fas fa-flag"></i> Статус плана</h2>
                        
                        <div class="status-options">
                            <div class="status-option status-pending <?=($plan && $plan["status"] === "pending" || !$plan)?"selected":""  ?>" data-status="pending">
                                Ожидание
                            </div>
                            <div class="status-option status-inprogress" data-status="inprogress"  <?=($plan && $plan["status"] === "inprogress")?"selected":""  ?>>
                                В работе
                            </div>
                            <div class="status-option status-completed" data-status="completed"  <?=($plan && $plan["status"] === "completed")?"selected":""  ?>>
                                Выполнен
                            </div>
                            <div class="status-option status-rejected" data-status="rejected"  <?=($plan && $plan["status"] === "rejected")?"selected":""  ?>>
                                Отклонен
                            </div>
                        </div>
                    </div>
                    
                    <!-- История изменений -->
                    <div class="form-section history-section">
                        <h2><i class="fas fa-history"></i> История изменений</h2>
                        
                        <div id="historyList">
                            <div class="history-item">
                                <div class="history-icon">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                                <div class="history-content">
                                    <div class="history-text">План создан</div>
                                    <div class="history-date">10 сентября 2023, 14:30 • Иван Петров</div>
                                </div>
                            </div>
                            <div class="history-item">
                                <div class="history-icon">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div class="history-content">
                                    <div class="history-text">Изменено название плана</div>
                                    <div class="history-date">12 сентября 2023, 10:15 • Анна Смирнова</div>
                                </div>
                            </div>
                            <div class="history-item">
                                <div class="history-icon">
                                    <i class="fas fa-flag"></i>
                                </div>
                                <div class="history-content">
                                    <div class="history-text">Статус изменен на "В работе"</div>
                                    <div class="history-date">15 сентября 2023, 09:45 • Иван Петров</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="right-column">
                    <!-- Привязка к подразделениям -->
                    <div class="form-section">
                        <h2><i class="fas fa-sitemap"></i> Подразделения</h2>
                        
                        <div class="form-group">
                            <label for="departmentsSelect">Выберите подразделения</label>
                            <select id="departmentsSelect" class="form-select" multiple="multiple">
                                <!--<option value="1" selected>Маркетинг</option>-->
                                <?PHP
                                    $depdis =[];
                                    if($plan)
                                        $depdis = array_values(CORE::$db->select("department_to_plan","department_id",["plan_id"=>$plan["id"]]));

                                    foreach (getDeps() as $dep):
                                        $selected = in_array($dep["id"],$depdis)?"selected":"";
                                        ?>
                                        <option value="<?= $dep["id"]?>" <?= $selected ?> ><?= $dep["name"]?></option>
                                <?PHP
                                    endforeach;
                                ?>

                            </select>
                            <div style="margin-top: 8px; font-size: 13px; color: #6c757d;">
                                <i class="fas fa-info-circle"></i> Вы можете выбрать несколько подразделений. Начните ввод чтобы выполнился поиск
                            </div>
                        </div>
                    </div>
                    
                    <!-- Привязка геоточек -->
                    <div class="form-section">
                        <h2><i class="fas fa-map-marker-alt"></i> Геоточки</h2>
                        
                        <div class="form-group">
                            <label for="geoPointsSelect">Выберите геоточки</label>
                            <select id="geoPointsSelect" class="form-select" multiple="multiple">
                                <?PHP

                                $geoids =[];
                                if($plan)
                                    $geoids = array_values(CORE::$db->select("point_to_plan","point_id",["plan_id"=>$plan["id"]]));
                                foreach (getGeopoint() as $point):
                                    $selected = in_array($point["id"],$geoids)?"selected":"";
                                    ?>
                                    <option value="<?= $point["id"]?>" <?=$selected ?> ><?= $point["name"]?></option>
                                <?PHP
                                endforeach;
                                ?>
                            </select>
                            <div style="margin-top: 8px; font-size: 13px; color: #6c757d;">
                                <i class="fas fa-info-circle"></i> Вы можете выбрать несколько геоточек
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button class="btn btn-secondary" id="addGeoPointBtn" style="width: 100%;">
                                <i class="fas fa-plus"></i> Добавить новую геоточку
                            </button>
                        </div>
                    </div>
                    
                    <!-- Прикрепление файлов -->
                    <div class="form-section">
                        <h2><i class="fas fa-paperclip"></i> Файлы</h2>
                        
                        <div class="form-group">
                            <label>Прикрепите файлы</label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <h3>Перетащите файлы сюда</h3>
                                <p>или нажмите для выбора файлов</p>
                                <input type="file" id="fileInput" multiple style="display: none;">
                            </div>
                            
                            <div class="file-list" id="fileList">
                               <?PHP
                               if($plan) {

                                   $fileids = CORE::$db->select("file_to_plan", "file_id", ["plan_id" => $plan["id"]]);
                                   $files = CORE::$db->select("files", "*", ["id" => $fileids]);
                                   foreach ($files as $file)
                                       echo addFileToList($file);

                               }

                               ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Привязка к другим планам -->
                    <div class="form-section subplans-section">
                        <h2><i class="fas fa-project-diagram"></i> Связь с другими планами</h2>
                        
                        <div class="form-group">
                            <label for="parentPlanSelect">Родительский план</label>
                            <select id="parentPlanSelect" class="form-select">
                                <option value="">Не выбран</option>
                                <option value="1">Стратегическое планирование на 2024 год</option>
                                <option value="2" selected>Развитие продуктовой линейки</option>
                                <option value="3">Оптимизация бизнес-процессов</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Подпланы (зависимые)</label>
                            <div class="subplans-list" id="subplansList">
                                <div class="subplan-item">
                                    <div class="subplan-title">Маркетинговая кампания</div>
                                    <div class="subplan-status status-inprogress">В работе</div>
                                </div>
                                <div class="subplan-item">
                                    <div class="subplan-title">Обучение отдела продаж</div>
                                    <div class="subplan-status status-pending">Ожидание</div>
                                </div>
                            </div>
                            
                            <button class="btn btn-secondary" id="addSubplanBtn" style="width: 100%;">
                                <i class="fas fa-link"></i> Привязать существующий план
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <div>
                    <button class="btn btn-danger" id="deletePlanBtn">
                        <i class="fas fa-trash"></i> Удалить план
                    </button>
                </div>
                <div style="display: flex; gap: 15px;">
                    <button class="btn btn-secondary" id="cancelBottomBtn">
                        <i class="fas fa-times"></i> Отмена
                    </button>
                    <button class="btn btn-primary" id="savePlanBottomBtn">
                        <i class="fas fa-check"></i> Сохранить план
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Подключение библиотек -->
    <script src="/js/quill.min.js"></script>
    <script src="/js/jquery-3.6.0.min.js"></script>
    <script src="/js/select2.min.js"></script>
    
    <script>

        let fileToLoad = [];
        // Инициализация Quill редактора
        const quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'header': 1 }, { 'header': 2 }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'direction': 'rtl' }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'font': [] }],
                    [{ 'align': [] }],
                    ['image', 'video', 'link'],
                    ['clean']
                ]
            },
            placeholder: 'Введите описание плана...'
        });

        // Инициализация Select2 для подразделений
        $('#departmentsSelect').select2({
            placeholder: "Выберите подразделения",
            allowClear: true,
            language: {
                noResults: function() {
                    return "Результатов не найдено";
                },
                searching: function() {
                    return "Поиск...";
                }
            }
        });

        // Инициализация Select2 для геоточек
        $('#geoPointsSelect').select2({
            placeholder: "Выберите геоточки",
            allowClear: true,
            language: {
                noResults: function() {
                    return "Результатов не найдено";
                },
                searching: function() {
                    return "Поиск...";
                }
            }
        });

        // Инициализация Select2 для родительского плана
        $('#parentPlanSelect').select2({
            placeholder: "Выберите родительский план",
            allowClear: true,
            language: {
                noResults: function() {
                    return "Результатов не найдено";
                },
                searching: function() {
                    return "Поиск...";
                }
            }
        });

        // Управление выбором типа даты
        const dateTypeRadios = document.querySelectorAll('input[name="dateType"]');
        const dateInputs = {
            exact: document.getElementById('exactDateInput'),
            month: document.getElementById('monthDateInput'),
            year: document.getElementById('yearDateInput')
        };

        function showDateInput(type) {
            // Скрыть все поля ввода даты
            Object.values(dateInputs).forEach(input => {
                input.classList.add('hidden');
            });
            
            // Показать нужное поле
            if (dateInputs[type]) {
                dateInputs[type].classList.remove('hidden');
            }
        }

        // Назначение обработчиков для радиокнопок типа даты
        dateTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                showDateInput(this.value);
            });
        });

        // Инициализация отображения правильного поля даты
        const initialDateType = document.querySelector('input[name="dateType"]:checked').value;
        showDateInput(initialDateType);

        // Управление выбором статуса
        const statusOptions = document.querySelectorAll('.status-option');
        
        statusOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Удаляем класс selected у всех опций
                statusOptions.forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Добавляем класс selected к выбранной опции
                this.classList.add('selected');
            });
        });

        // Управление загрузкой файлов
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');

        fileUploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#3498db';
            this.style.background = '#e8f4fc';
        });

        fileUploadArea.addEventListener('dragleave', function() {
            this.style.borderColor = '#dee2e6';
            this.style.background = '#f8f9fa';
        });

        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#dee2e6';
            this.style.background = '#f8f9fa';
            
            const files = e.dataTransfer.files;
            handleFiles(files);
        });

        fileInput.addEventListener('change', function() {
            handleFiles(this.files);
        });

        function handleFiles(files) {
            for (let i = 0; i < files.length; i++) {
                addFileToList(files[i]);
            }
        }

        function addFileToList(file) {
            fileToLoad.push(file);
            // Форматирование размера файла
            const formatFileSize = (bytes) => {
                if (bytes === 0) return '0 Б';
                const k = 1024;
                const sizes = ['Б', 'КБ', 'МБ', 'ГБ'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            };

            // Определение иконки по типу файла
            const getFileIcon = (fileName) => {
                const ext = fileName.split('.').pop().toLowerCase();
                if (['pdf'].includes(ext)) return 'fas fa-file-pdf';
                if (['doc', 'docx'].includes(ext)) return 'fas fa-file-word';
                if (['xls', 'xlsx'].includes(ext)) return 'fas fa-file-excel';
                if (['ppt', 'pptx'].includes(ext)) return 'fas fa-file-powerpoint';
                if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(ext)) return 'fas fa-file-image';
                if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return 'fas fa-file-archive';
                return 'fas fa-file';
            };

            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <div class="file-info">
                    <div class="file-icon">
                        <i class="${getFileIcon(file.name)}"></i>
                    </div>
                    <div>
                        <div class="file-name">${file.name}</div>
                        <div class="file-size">${formatFileSize(file.size)}</div>
                    </div>
                </div>
                <div class="file-remove">
                    <i class="fas fa-times"></i>
                </div>
            `;

            // Добавление обработчика удаления файла
            const removeBtn = fileItem.querySelector('.file-remove');
            removeBtn.addEventListener('click', function() {
                fileItem.remove();
                if(fileToLoad.indexOf(file) !== -1)
                    fileToLoad.splice(fileToLoad.indexOf(file),1);
            });
            
            fileList.appendChild(fileItem);
        }

        // Удаление файлов из списка
        document.querySelectorAll('#fileList .file-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.file-item').remove();
            });
        });

        // Добавление новой геоточки
        document.getElementById('addGeoPointBtn').addEventListener('click', function() {
            alert("Позже..")
        });

        // Добавление подплана
        document.getElementById('addSubplanBtn').addEventListener('click', function() {
            alert('В полной версии будет открыт диалог выбора существующего плана для привязки как подплана.');
        });

        // Обработчики кнопок сохранения
        function savePlan() {
            // Сбор данных формы
            const planData = {
                plan_id: <?=  !is_null($plan) ?$plan["id"]: "null" ?> ,
                name: document.getElementById('planTitle').value,
                content: quill.root.innerHTML,
                date_type: document.querySelector('input[name="dateType"]:checked').value,
                date_value: getDateValue(),
                status: document.querySelector('.status-option.selected').dataset.status,
                departments: $('#departmentsSelect').val(),
                geoPoints: $('#geoPointsSelect').val(),
                parent_id: $('#parentPlanSelect').val()
            };

            let formData = new FormData();

            formData.append("plan_id",planData.plan_id);
            formData.append("name",planData.name);
            formData.append("content",planData.content);
            formData.append("date_type",planData.date_type);
            formData.append("date_value",planData.date_value);
            formData.append("date_value",planData.date_value);
            formData.append("status",planData.status);
            formData.append("parent_id",planData.status);
            for(let inx in planData.departments)
                formData.append("departments[]",planData.departments[inx]);

            for(let inx in planData.geoPoints)
                formData.append("geoPoints[]",planData.geoPoints[inx]);
            for(let  inx in fileToLoad)
                formData.append("file[]", fileToLoad[inx]);

            $.ajax({
                type: "POST",
                url: "",
                processData: false,
                contentType: false,
                cache: false,
                data: formData,
                success: function (e){
                    if(e.result == "ok")
                        window.location = "/plan.php";
                    else{
                        alert("Ошибки:\r\n"+e.errors.join("\r\n"));
                    }
                }
            });

        }

        function getDateValue() {
            const dateType = document.querySelector('input[name="dateType"]:checked').value;
            
            switch(dateType) {
                case 'exact':
                    return document.getElementById('exactDate').value;
                case 'month':
                    return document.getElementById('monthDate').value;
                case 'year':
                    return document.getElementById('yearDate').value;
                default:
                    return null;
            }
        }

        // Назначение обработчиков для кнопок сохранения
        document.getElementById('savePlanBtn').addEventListener('click', () => savePlan());
        document.getElementById('savePlanBottomBtn').addEventListener('click', () => savePlan());

        // Обработчики кнопок отмены
        function cancelEditing() {
            if (confirm('Вы уверены, что хотите отменить создание плана? Все несохраненные изменения будут потеряны.')) {
                // В реальном приложении здесь будет перенаправление на список планов
                window.location.href = 'plans_list.html';
            }
        }

        document.getElementById('cancelBtn').addEventListener('click', cancelEditing);
        document.getElementById('cancelBottomBtn').addEventListener('click', cancelEditing);

        // Обработчик кнопки удаления
        document.getElementById('deletePlanBtn').addEventListener('click', function() {
            if (confirm('Вы уверены, что хотите удалить этот план? Это действие нельзя отменить.')) {
                alert('План удален!');
                // В реальном приложении здесь будет API запрос на удаление и перенаправление
                window.location.href = 'plans_list.html';
            }
        });

        // Добавление истории изменений при редактировании полей
        const formInputs = document.querySelectorAll('.form-input, .form-date, #editor');
        
        formInputs.forEach(input => {
            if (input.id !== 'editor') {
                input.addEventListener('blur', function() {
                    if (this.value.trim() !== '' && this.dataset.originalValue !== this.value) {
                        addHistoryEntry(`Изменено поле "${this.previousElementSibling?.textContent || 'неизвестное поле'}"`);
                        this.dataset.originalValue = this.value;
                    }
                });
            }
        });

        // Для редактора Quill
        quill.on('text-change', function() {
            if (!quill.hasFocus()) return;
            
            clearTimeout(window.quillChangeTimeout);
            window.quillChangeTimeout = setTimeout(function() {
                addHistoryEntry('Изменено описание плана');
            }, 1000);
        });

        function addHistoryEntry(text) {
            const now = new Date();
            const formattedDate = now.toLocaleDateString('ru-RU', { 
                day: 'numeric', 
                month: 'long', 
                year: 'numeric' 
            }) + ', ' + now.toLocaleTimeString('ru-RU', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            const historyItem = document.createElement('div');
            historyItem.className = 'history-item';
            historyItem.innerHTML = `
                <div class="history-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="history-content">
                    <div class="history-text">${text}</div>
                    <div class="history-date">${formattedDate} • Текущий пользователь</div>
                </div>
            `;
            
            const historyList = document.getElementById('historyList');
            historyList.insertBefore(historyItem, historyList.firstChild);
        }
    </script>
</body>
</html>