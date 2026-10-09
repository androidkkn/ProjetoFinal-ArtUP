<?php
require_once __DIR__ . '/credenciais.php';
$host = $ip;
$dbname = $nomeDB;
$user = $nomeUSR;
$pass = $senha;

try {
    $conexao = new PDO(
        "pgsql:host=$host;dbname=$dbname",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e){
    error_log('Erro de conexao com o PostgreSQL: ' . $e->getMessage());
    http_response_code(500);
    exit('Nao foi possivel conectar ao banco de dados.');
}
