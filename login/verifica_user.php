<?php 
require_once __DIR__ . '/../include/navegacao.php';

if(session_status() == PHP_SESSION_NONE){
    session_start();
}
if(!isset($_SESSION['id'])){
    header('Location: ' . url_app('login/login.php'));
    exit();
}

