<?php
require_once __DIR__ . '/../../include/functions.php';
require_once __DIR__ . '/../../login/verifica_user.php';
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (cproduto($conexao, 3, $_POST['quantidade'] ?? null, $_POST['larg'] ?? null,
        $_POST['compr'] ?? null, null, $_POST['grossura'] ?? null, null)) {
        header('Location: ../carrinho.php', true, 303);
        exit;
    }
    $erro = 'Não foi possível adicionar o produto. Confira a espessura, a quantidade e as dimensões e tente novamente.';
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
                <!-- Input tamanho -->
                <p>Escolha o tamanho</p>
                <input type="number" name="compr" id="compr" placeholder="Comprimento">
                x
                <input type="number" name="larg" id="larg" placeholder="Largura">
                <br>
                <!-- Input Grossura da placa -->
                <label for="grossura">Grossura da placa</label>
                <select name="grossura" id="grossura">
                    <option value="1">1mm</option>
                    <option value="2">2mm</option>
                    <option value="3">3mm</option>

                </select>
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
