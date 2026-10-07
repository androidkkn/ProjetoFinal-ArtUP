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
                <!-- Input tamanho -->
                <p>Escolha o tamanho</p>
                <input type="number" name="compr" id="compr" placeholder="Comprimento">
                x
                <input type="number" name="larg" id="larg" placeholder="Largura">
                <br>
                <!-- Input acabamento -->
                <label for="acabamento">Tipo de acabamento Banner</label>
                <select name="acabamento" id="acabamento">
                    <option value="PVC">PVC</option>
                    <option value="BM">Barra de madeira</option>
                    <option value="BA">Barra de aluminho</option>
                    <option value="B">Brisilha</option>
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