<?php
session_start();
include "includes/db.php";

$user = getUser();
if($user && !$user['is_blocked']){
    responseJson(["result"=>"success","data"=>getUser()]);

}else{
responseJson(["result"=>"error"]);
}
