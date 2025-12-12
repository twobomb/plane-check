<?php

include "includes/db.php";
require_once "includes/auth_check.php";

$type = "for_save";
if(isset($_GET["type"]))
    $type = $_GET["type"];

$uid = getUserId();
responseJson(getAllowedProjects($type,$uid));