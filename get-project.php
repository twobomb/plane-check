<?php

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

if(!in_array($proejctid,$prj_ids))
    echo "Проект недоступен!";

$project = CORE::$db->get("project","*",["id"=>$proejctid]);

$result = [
    "mapSettings"=>[
        "center"=>[
            "lat"=>$project["center_lat"],
            "lng"=>$project["center_lng"],
        ],
        "zoom"=>$project["zoom"],
        "mode"=>$project["scheme"],
        "showLabels"=>$project["showLabels"] == 1,
        "markerColor"=>"#e74c3c",
        "layers"=>[]
    ]
];

foreach (CORE::$db->select("layer","*",["project_id"=>$project["id"]]) as $layer){
    $l = [
        "id"=>$layer["id"],
        "name"=>$layer["name"],
        "active"=>$layer["true"],
        "visible"=>$layer["visible"] == 1,
        "markers"=>[]
    ];
    foreach (CORE::$db->select("point","*",["layer_id"=>$layer["id"]]) as $point){
        array_push($l["markers"],[
           "id"=>$point["id"],
           "name"=>$point["name"],
           "lat"=>$point["lat"],
           "lng"=>$point["lng"],
           "color"=>$point["color"],
           "showLabel"=>$project["showLabels"] == 1
        ]);
    }
    array_push($result["layers"],$l);
}

responseJson($result);die;
