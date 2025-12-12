<?php
session_start();
include "includes/db.php";

if(isset($_SESSION['user_id'])){
    responseJson(["result"=>"success","data"=>[

        'id' => $_SESSION['user_id'],
        'login' => $_SESSION['login'],
        'username' => $_SESSION['username']
    ]]);

}else{
responseJson(["result"=>"error"]);
}
