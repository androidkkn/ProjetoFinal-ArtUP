<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adesivos</title>
</head>

<body>
    <?php include __DIR__ . '/../../include/header.php' ?>

    <main>
        <section class="form">
            <form action="" method="post">
                <!-- Input tamanho -->
                <p>Escolha o tamanho</p>
                <input type="number" name="compr" id="compr" placeholder="Comprimento">
                x
                <input type="number" name="larg" id="larg" placeholder="Largura">
                <br>
                <!-- Input tipo de adesivo -->
                <label for="tipo">Tipo de adesivo</label>
                <select name="tipo" id="tipo">
                    <option value="V">Vinil</option>
                    <option value="VU">Vinil UV</option>
                    <option value="B">BOPP</option>
                </select>
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