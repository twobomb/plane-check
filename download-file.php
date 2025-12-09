<?php
include "includes/db.php";

if(isset($_GET["id"]) && CORE::$db->count("files",["id"=>$_GET["id"]]) > 0){
    $url = CORE::$db->select("files","url",["id"=>$_GET["id"]])[0];
    $name = CORE::$db->select("files","name",["id"=>$_GET["id"]])[0];
    $file_path = $url;
;
// Пddроверяем, что файл находится в разрешенной директории
    if ($file_path &&  file_exists($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($name) . '"');
        header('Content-Length: ' . filesize($file_path));

        readfile($file_path);
        exit;
    }
}

http_response_code(404);
echo 'Файл не найден';