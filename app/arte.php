<?php
require_once __DIR__ . '/../login/verifica_user.php';
require_once __DIR__ . '/../include/functions.php';

try {
    $arte = consultar_arte_produto($conexao, $_GET['produto'] ?? null);
} catch (PDOException $e) {
    error_log('Erro ao consultar arte: ' . $e->getCode());
    http_response_code(500);
    exit('Não foi possível carregar a arte. Tente novamente.');
}
if (!$arte) {
    http_response_code(404);
    exit('Arte não encontrada.');
}
$extensao = pathinfo($arte['nome_original'], PATHINFO_EXTENSION);
$nome_simples = 'arte-' . (int) $_GET['produto'] . '.' . strtolower($extensao);
header('Content-Type: ' . $arte['mime']);
header('Content-Disposition: attachment; filename="' . $nome_simples . '"; filename*=UTF-8\'\'' . rawurlencode($arte['nome_original']));
header('Content-Length: ' . $arte['tamanho']);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
session_write_close();
if (is_resource($arte['conteudo'])) {
    fpassthru($arte['conteudo']);
} else {
    echo $arte['conteudo'];
}
exit;
