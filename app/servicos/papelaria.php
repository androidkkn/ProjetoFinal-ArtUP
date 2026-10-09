<?php
require_once __DIR__ . '/../../include/functions.php';
require_once __DIR__ . '/../../login/verifica_user.php';
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipos = ['Sulfite' => 8, 'Offset' => 11, 'Couché' => 10, 'Fotográfico' => 9];
    $tipo = $_POST['tipo'] ?? '';
    $id_tipo = is_string($tipo) ? ($tipos[$tipo] ?? null) : null;
    if ($id_tipo !== null && cproduto($conexao, 2, $_POST['quantidade'] ?? null,
        null, null, $_POST['tamanho'] ?? null, null, $id_tipo)) {
        header('Location: ../carrinho.php', true, 303);
        exit;
    }
    $erro = 'Não foi possível adicionar o produto. Confira o papel, o tamanho e a quantidade e tente novamente.';
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos em lona</title>
</head>

<body>
    <?php include __DIR__ . '/../../include/header.php' ?>

    <main>
        <section class="form">
            <?php if ($erro): ?><p role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <form action="" method="post">
                <!--  -->
                <label for="tamanho">Escolha o tamanho da impressão</label>
                <br>
                <select name="tamanho" id="tamanho">
                    <option value="A1">A1</option>
                    <option value="A2">A2</option>
                    <option value="A3">A3</option>
                    <option value="A4">A4</option>
                    <option value="A5">A5</option>
                    <option value="A6">A6</option>
                    <option value="A7">A7</option>
                    <option value="A8">A8</option>
                </select>
                <br>
                <!--  -->
                <label for="tipo">Escolha o tipo de folha e gramatura</label>
                <br>
                <select name="tipo" id="tipo">
                    <option value="Sulfite">Sulfite</option>
                    <option value="Offset">Offset</option>
                    <option value="Couché">Couché</option>
                    <option value="Fotográfico">Fotográfico</option>
                </select>
                </input>
                <br>
                <!-- Quantidade -->
                 <label for="quantidade">Quantidade</label>
                 <input type="number" name="quantidade" id="quantidade">
                 <br>
                <!-- Input da arte -->
                <label for="arte">Insira sua Arte aqui</label>
                <input type="file" name="arte" id="arte" accept="image/*">
                <br>
                <!-- Envio do formulario -->
                 <input type="submit" value="Adicionar ao carrinho">
                
            </form>
        </section>
    </main>

    <?php include __DIR__ . '/../../include/footer.php' ?>
</body>

</html>
