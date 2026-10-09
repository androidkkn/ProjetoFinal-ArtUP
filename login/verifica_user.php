<?php 

if(session_status() == PHP_SESSION_NONE){
    session_start();
}
if(!isset($_SESSION['id'])){
    $raiz = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
    if (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'servicos') {
        $raiz = str_replace('\\', '/', dirname($raiz));
    }
    header('Location: ' . rtrim($raiz, '/') . '/login/login.php');
    exit();
}

