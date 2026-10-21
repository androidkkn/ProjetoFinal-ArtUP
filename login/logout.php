<?php
require_once __DIR__ . '/../include/navegacao.php';
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Use o botão Sair para encerrar a sessão.');
}
$token = $_POST['csrf_token'] ?? null;
if (!is_string($token) || !isset($_SESSION['csrf_logout'])
    || !hash_equals($_SESSION['csrf_logout'], $token)) {
    http_response_code(403);
    exit('Recarregue a página e tente sair novamente.');
}
$_SESSION = array();
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'],
    ]);
}
session_destroy();
header('Location: ' . url_app('login/login.php'), true, 303);
exit();
?>
