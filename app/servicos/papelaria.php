<?php require_once __DIR__ . '/../../include/functions.php'; ?>

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
        <?php 
        if ($_SERVER['REQUEST_METHOD'] == "POST"){
            if ($_POST['tipo'] == "Sulfite") {
                $id_tipo = 8;
            } elseif ($_POST['tipo'] == "Offset") {
                $id_tipo = 11;
            } elseif ($_POST['tipo'] == "Couché") {
                $id_tipo = 10;
            } elseif ($_POST['tipo'] == "Fotográfico") {
                $id_tipo = 9;
            } else {
                echo "Opção Inválida, tente novamente.";
            };
            cproduto($conexao, 2, $_POST['quantidade'], null, null, $_POST['tamanho'], null, $id_tipo);
        };
        

        ?>
    </main>

    <?php include __DIR__ . '/../../include/footer.php' ?>
</body>

</html>