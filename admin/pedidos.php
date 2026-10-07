<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos</title>
</head>
<body>
    <?php 
    $sql = "SELECT * FROM produto";

    try {
        $stmt = $conexao->prepare($sql);
        $stmt->execute();

        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($alunos as $aluno) {
            echo "<hr>";
            echo "ID: {$aluno['id']}<br>";
            echo "nome: {$aluno['nome']}<br>";
            echo "turma: {$aluno['turma']}<br>";
            echo "email: {$aluno['email']}<br>";
            echo "ativo: {$aluno['ativo']}<br><br>";
        }
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
    ?>
</body>
</html>