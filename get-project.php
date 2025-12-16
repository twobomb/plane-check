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
$proejctid = null;
if ($data) {
    $userid = $data["user_id"];
    $proejctid = $data["project_id"];
} else {
    echo "Доступ запрещен! Недействительный токен!";
    die;
}


$projects = getAllowedProjects("for_open",$userid);

$prj_ids = [];
foreach ($projects as $pj)
    array_push($prj_ids,$pj["id"]);

$project = [];
if($proejctid === "-1"){//Системный проект подразделений
    $markers = [];
    foreach (getDeps() as $k=>$m){
        array_push($markers,[
            "id"=>$m["id"],
            "name"=>$m["name"],
            "lat"=>doubleval($m["lat"]),
            "lng"=>doubleval($m["lng"]),
            "color"=>"#FF0000",
            "showLabel"=>true
        ]);
    }
    $result = [
        "mapSettings"=>[
            "project_id"=>-1,
            "center"=>[
                "lat"=>48.563665,
                "lng"=> 39.311153,
            ],
            "zoom"=>12,
            "mode"=>"scheme",
            "showLabels"=>true,
            "markerColor"=>"#e74c3c"
        ],
        "layers"=>[
            [
                "id"=>-1,
                "name"=>"Подразделения",
                "active"=>true,
                "visible"=>true,
                "markers"=>$markers
            ]
        ]
    ];
    responseJson($result);
}else {

    if (!in_array($proejctid, $prj_ids)) {
        echo "Проект недоступен!";
        die;
    }

    $project = CORE::$db->get("project", "*", ["id" => $proejctid]);

}
$result = [
    "mapSettings"=>[
        "project_id"=>$proejctid,
        "center"=>[
            "lat"=>doubleval($project["center_lat"]),
            "lng"=>doubleval($project["center_lng"]),
        ],
        "zoom"=>$project["zoom"],
        "mode"=>$project["scheme"],
        "showLabels"=>$project["showLabels"] == 1,
        "markerColor"=>"#e74c3c"
    ],
        "layers"=>[]
];

foreach (CORE::$db->select("layer","*",["project_id"=>$project["id"]]) as $layer){
    $l = [
        "id"=>$layer["id"],
        "name"=>$layer["name"],
        "active"=>true,
        "visible"=>$layer["visible"] == 1,
        "markers"=>[]
    ];
    foreach (CORE::$db->select("point","*",["layer_id"=>$layer["id"]]) as $point){
        array_push ($l["markers"] , [
           "id"=>$point["id"],
           "name"=>$point["name"],
           "lat"=>doubleval($point["lat"]),
           "lng"=>doubleval($point["lng"]),
           "color"=>$point["color"],
           "showLabel"=>$project["showLabels"] == 1
        ]);
    }
    array_push($result["layers"],$l);
}

responseJson($result);die;
