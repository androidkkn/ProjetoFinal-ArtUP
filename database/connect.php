<?php 
include __DIR__ . '/credenciais.php';
$host = $ip;
$dbname = $nomeDB;
$user = $nomeUSR;
$pass = $senha;

try {
    $conexao = new PDO(
        "pgsql:host=$host;dbname=$dbname",
        $user,
        $pass
    );
    echo "Conexão realizada com sucesso!<br>";
} catch (PDOException $e){
    echo "Erro: ". $e->getMessage();
}
?>