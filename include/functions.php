<?php

require_once __DIR__ . '/../database/connect.php';

function calcTamanho($conexao, $c, $l)
{
    $tamanho = $c . $l;
};
function cproduto($conexao, $id_categoria, $quantidade, $largura, $comprimento, $tamanho, $grossura, $id_tipo)
{
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $sql = "INSERT INTO  produto (id_categoria, quantidade, largura, comprimento, tamanho, grossura, id_tipo) VALUES(:id_categoria, :quantidade, :largura, :comprimento, :tamanho, :grossura, :id_tipo)";
        try {
            $stmt = $conexao->prepare($sql);
            $stmt->bindParam(":id_categoria", $id_categoria);
            $stmt->bindParam(":quantidade", $quantidade);
            $stmt->bindParam(":largura", $largura);
            $stmt->bindParam(":comprimento", $comprimento);
            $stmt->bindParam(":tamanho", $tamanho);
            $stmt->bindParam(":grossura", $grossura);
            $stmt->bindParam(":id_tipo", $id_tipo);

            $stmt->execute();
            echo "Pedido feito com sucesso";
        } catch (PDOException $e) {
            echo "Erro: " . $e->getMessage();
        }
    }
}

function cadastrar_user($conexao, $nome, $email, $senha, $nivel)
{
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $sql = "INSERT INTO  usuarios (nome, email, senha, nivel) VALUES(:nome ,:email, :senha, :nivel)";
        try {
            $stmt = $conexao->prepare($sql);
            $stmt->bindParam(":nome", $nome);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":senha", $senha);
            $stmt->bindParam(":nivel", $nivel);


            $stmt->execute();
            echo "Usuario cadastrado com sucesso!";
        } catch (PDOException $e) {
            echo "Erro: " . $e->getMessage();
        }
    }
}

?>