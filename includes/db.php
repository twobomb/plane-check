<?php

require_once "vendor/autoload.php";
require_once "Base64ImageProcessor.php";

 class CORE {
     const MAPSITE = "http://map.mchs.lnr";
     public static $db ;
     public static $dateTypes = ["exact"=>"Конкретная дата","month"=>"Месяц","year"=>"Год","without"=>"Без даты"];
     public static $statuses = ["pending"=>"Ожидание","inprogress"=>"В работе","completed"=>"Выполнен","rejected"=>"Отклонен"];
 }
 CORE::$db =new Medoo\Medoo([
     'database_type' => 'mysql',
     'database_name' => 'plan-check',
     'server' => 'localhost',
     'username' => 'root',
     'charset' => 'utf8mb4',
     'password' => '123456'
 ]);


 function getAllowedProjects($type,$uid = null){
     $projects = [];
    if($uid == null)
        $uid = getUserId();
     $projects = [];
     switch ($type){
         case "for_open":// Какие проекты мы можем октрыть
            $projects = CORE::$db->query("SELECT * FROM project WHERE access = 'public' OR access = 'protected' OR (access = 'private' AND user_id = '$uid')")->fetchAll();
            break;
         case "for_save": default:// В какие проекты пользователь может сохранять
            $projects = CORE::$db->query("SELECT * FROM project WHERE access = 'public' OR (access = 'protected' AND user_id = '$uid') OR (access = 'private' AND user_id = '$uid')")->fetchAll();
     }
     return $projects;
 }
 function addHistory($planId,$type,$value){
    CORE::$db->insert("history",[
       "user_id"=>getUserId(),
        "type"=>$type,
        "value"=>$value,
        "plan_id"=>$planId
    ]);
      return is_null(CORE::$db->error);
 }
 function getUserId(){
     return getUser()["id"];
 }

// Функция для получения информации о текущем пользователе
function getUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'login' => $_SESSION['login'],
        'username' => $_SESSION['username']
    ];
}
function getDeps(){
     $result = [];
     $db = CORE::$db;
        // 1. Получаем все отделы с parent_id = NULL, сортируем по sort_id
        $rootDepartments = $db->select('department', '*', [
            'parent_id' => null,
            'ORDER' => ['sort_id' => 'ASC']
        ]);

        // 2. Добавляем корневые отделы в результат
        foreach ($rootDepartments as $department) {
            $result[] = $department;

            // 3. Для каждого корневого отдела получаем его дочерние отделы
            $childDepartments = $db->select('department', '*', [
                'parent_id' => $department['id'],
                'ORDER' => ['sort_id' => 'ASC']
            ]);

            // 4. Добавляем дочерние отделы сразу после родителя
            foreach ($childDepartments as $child) {
                $result[] = $child;
            }
        }

        return $result;
}
function getGeopoint(){
    return CORE::$db->select("point","*",["layer_id"=>null]);
}


function responseJson($data){
    ob_clean();
    echo json_encode($data,JSON_UNESCAPED_UNICODE);
    header('Content-Type: application/json');
    die;
}

// Форматирование размера файла
function formatFileSize($bytes)
{
    if ($bytes === 0) return '0 Б';
    $k = 1024;
    $sizes = ['Б', 'КБ', 'МБ', 'ГБ'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Определение иконки по типу файла
function getFileIcon($fileName)
{
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($ext, ['pdf'])) return 'fas fa-file-pdf';
    if (in_array($ext, ['doc', 'docx'])) return 'fas fa-file-word';
    if (in_array($ext, ['xls', 'xlsx'])) return 'fas fa-file-excel';
    if (in_array($ext, ['ppt', 'pptx'])) return 'fas fa-file-powerpoint';
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) return 'fas fa-file-image';
    if (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) return 'fas fa-file-archive';
    return 'fas fa-file';
}
function addFileToList(array $file){

    // Получаем размер файла
    $fileSize = file_exists($file['url']) ? filesize($file['url']) : 0;

    // Генерируем HTML для файла
    $html = '<div class="file-item file-item-loaded" data-id="'.$file["id"].'">
                <div class="file-info">
                    <div class="file-icon">
                        <i class="' . getFileIcon($file['name']) . '"></i>
                    </div>
                    <div>
                        <div class="file-name">' . htmlspecialchars($file['name']) . '</div>
                        <div class="file-size">' . formatFileSize($fileSize) . '</div>
                    </div>
                </div>
                <div class="file-remove">
                    <i class="fas fa-times"></i>
                </div>
            </div>';

    return $html;
}

function getTimeAgo($date) {
    $now = new DateTime();
    $past = new DateTime($date);
    $interval = $now->diff($past);

    if ($interval->y > 0) {
        return formatTimeAgo($interval->y, 'год', 'года', 'лет'). ' назад';
    } elseif ($interval->m > 0) {
        return formatTimeAgo($interval->m, 'месяц', 'месяца', 'месяцев'). ' назад';
    } elseif ($interval->d > 0) {
        return formatTimeAgo($interval->d, 'день', 'дня', 'дней'). ' назад';
    } elseif ($interval->h > 0) {
        return formatTimeAgo($interval->h, 'час', 'часа', 'часов') . ' ' .
            formatTimeAgo($interval->i, 'минуту', 'минуты', 'минут') . ' назад';
    } elseif ($interval->i > 0) {
        return formatTimeAgo($interval->i, 'минуту', 'минуты', 'минут') . ' назад';
    } else {
        return 'только что';
    }
    }
    function formatTimeAgo($number, $one, $two, $many) {
        if ($number % 10 == 1 && $number % 100 != 11) {
            return "$number $one";
        } elseif ($number % 10 >= 2 && $number % 10 <= 4 && ($number % 100 < 10 || $number % 100 >= 20)) {
            return "$number $two";
        } else {
            return "$number $many";
        }
    }
    function nowdate(){
        return date('Y-m-d H:i:s');
    }
    function saveUniFile($pathToSave,$tmpFile,$name,$options = []){
        $ext = pathinfo($name)["extension"];
        if(isset($options["extension"]) && !in_array($ext,$options["extension"]))
            throw new Exception("Расширение файла ".$ext." доступные расширения(".implode(",",$options["extension"]).")");

        if(!file_exists($pathToSave))
            throw new Exception("Не существующая директория ".$pathToSave);
        do {
            $filename = md5(microtime() . rand(0, 9999)) . "." . $ext;
        } while (file_exists($pathToSave . $filename));
        $filepath = $pathToSave . $filename;
        if(!move_uploaded_file($tmpFile, $filepath))
            throw new Exception("Ошибка перемещения файла '".$tmpFile ."' в '". $filepath."''");
        return $filepath;
    }
    function unlink_if_exists($path){
        if(file_exists($path))
            unlink($path);
    }
function buildTree(array &$elements, $parentId = 0) {
    $branch = [];

    foreach ($elements as &$element) {
        if ($element['parent_id'] == $parentId) {
            // Находим детей текущего элемента
            $children = buildTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[] = $element;
            // Удаляем элемент из массива, чтобы не обрабатывать его повторно
            unset($element);
        }
    }
    return $branch;
}