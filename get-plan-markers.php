<?php
// Разрешаем ВСЁ всем - ставим в самое начало файла
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Credentials: false');
header('Access-Control-Max-Age: 86400');

// Если пришел OPTIONS запрос - сразу отвечаем
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    die;
}


use Medoo\Medoo;

include "includes/db.php";
include "includes/OneTimeToken.php";

if(!isset($_GET["token"])) {
    echo "Ожидается токен!";
    die;
}
$otp = new OneTimeToken();
$data = $otp->validateToken($_GET["token"]);
$userid = null;
$planId = null;
if ($data) {
    $userid = $data["user_id"];
    $planId = $data["plan_id"];
} else {
    echo "Доступ запрещен! Недействительный токен!";
    die;
}


$plan = CORE::$db->get("plan","*",["id"=>$planId]);


if(!$plan) {
    echo "Проект недоступен!";die;

}

$plan_id = $plan['id'];

//SELECT `point`.`lat`,`point`.`lng`,`point`.`id`,`point`.`color`,`point`.`name` FROM `point` LEFT JOIN department ON department.point_id = `point`.`id` LEFT JOIN department_to_plan ON department.id = department_to_plan.department_id WHERE department_to_plan.plan_id = $plan_id UNION
$points = CORE::$db->query("SELECT  `point`.`lat`,`point`.`lng`,`point`.`id`,`point`.`color`,`point`.`name`  FROM `point` LEFT JOIN point_to_plan ON `point`.`id` = point_to_plan.point_id WHERE point_to_plan.plan_id = $plan_id")->fetchAll();


$deps = CORE::$db->query("SELECT department.id,department.name,department.lat,department.lng FROM department LEFT JOIN department_to_plan ON department.id = department_to_plan.department_id WHERE department_to_plan.plan_id = $plan_id")->fetchAll();

if(count($deps) > 0){
    foreach ($deps as $dep){
        array_push($points,[
           "id"=>$dep["id"].microtime(),
           "lat"=>$dep["lat"],
           "lng"=>$dep["lng"],
           "name"=>$dep["name"],
            "color"=>"#FF0000"
        ]);
    }
}

if(count($points) == 0)
    die;

$result = [
    "mapSettings"=>[
        "project_id"=>null,
        "center"=>[
            "lat"=>doubleval($points[0]["lat"]),
            "lng"=>doubleval($points[0]["lng"]),
        ],
        "zoom"=>12,
        "mode"=>"scheme",
        "showLabels"=>true,
        "markerColor"=>"#e74c3c"
    ],
    "layers"=>[]
];


$l = [
    "id"=>$plan["id"],
    "name"=>$plan["name"],
    "active"=>true,
    "visible"=>true,
    "markers"=>[]
];
foreach ($points as $point){
    array_push ($l["markers"] , [
        "id"=>$point["id"],
        "name"=>$point["name"],
        "lat"=>doubleval($point["lat"]),
        "lng"=>doubleval($point["lng"]),
        "color"=>$point["color"],
        "showLabel"=>true
    ]);
}
array_push($result["layers"],$l);

responseJson($result);die;
