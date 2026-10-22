<?php 
require_once __DIR__ . '/../include/navegacao.php';

if(session_status() == PHP_SESSION_NONE){
    session_start();
}
if(!isset($_SESSION['id'])){
    header('Location: ' . url_app('login/login.php'));
    exit();
}

// Revalida o perfil para que promoções e remoções de acesso valham nesta requisição.
require_once __DIR__ . '/../include/functions.php';
try {
    $stmt_perfil = $conexao->prepare('SELECT admin FROM usuarios WHERE id = :id');
    $stmt_perfil->bindValue(':id', (int) $_SESSION['id'], PDO::PARAM_INT);
    $stmt_perfil->execute();
    $perfil_atual = $stmt_perfil->fetch(PDO::FETCH_ASSOC);
    if ($perfil_atual === false) {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: ' . url_app('login/login.php'));
        exit;
    }
    $_SESSION['admin'] = $perfil_atual['admin'] === true;
} catch (PDOException $erro_perfil) {
    error_log('Erro ao verificar perfil da sessao: ' . $erro_perfil->getCode());
    http_response_code(500);
    exit('Não foi possível verificar seu acesso. Tente novamente em instantes.');
}
