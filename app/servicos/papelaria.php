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
                <label for="tamanhos">Escolha o tamanho da impressão</label>
                <br>
                <select name="tamanhos" id="tamanhos">
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
                <label for="tipos">Escolha o tipo de folha e gramatura</label>
                <br>
                <input type="text" name="tipos" id="tipos" list="lsttipopp">
                <datalist id="lsttipopp">
                    <option value="Sulfite">75g (padrão)</option>
                    <option value="Sulfite">90g</option>
                    <option value="Sulfite">120g</option>
                    <option value="Sulfite">180g</option>
                    <option value="Offset">75g</option>
                    <option value="Offset">90g (padrão)</option>
                    <option value="Offset">120g</option>
                    <option value="Offset">150g</option>
                    <option value="Offset">170g</option>
                    <option value="Offset">180g</option>
                    <option value="Offset">240g</option>
                    <option value="Couché">90g (panfletos)</option>
                    <option value="Couché">115g (panfletos)</option>
                    <option value="Couché">150g (catálogos e pequenos cartazes)</option>
                    <option value="Couché">170g (catálogos e pequenos cartazes)</option>
                    <option value="Couché">250g (cartões de visita, convites e capas de revistas)</option>
                    <option value="Couché">300g (cartões de visita, convites e capas de revistas)</option>
                    <option value="Fotográfico">90g</option>
                    <option value="Fotográfico">135g</option>
                    <option value="Fotográfico">180g (padrão)</option>
                    <option value="Fotográfico">230g</option>
                    <option value="Fotográfico">260g</option>
                </datalist>
                </input>
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