<?php require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../login/verifica_user.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo</title>
</head>

<body>
    <?php include __DIR__ . '/../include/header.php' ?>

    <main>
        <div>
            <h1>Escolha o tipo de serviço que você deseja</h1>
        </div>
        <section class="servicos">
            <div><a href="/app/servicos/banner.php">
                    <h2>Banner</h2>
                    <img src="../img/catalogo/produtos-em-lona.png" alt="Produtos em Lona">
                </a>
            </div>
            <div><a href="/app/servicos/adesivos.php">
                    <h2>Adesivos</h2>
                    <img src="../img/catalogo/adesivos.png" alt="adesivos">
                </a>
            </div>
            <div><a href="/app/servicos/placas.php">
                    <h2>Placas</h2>
                    <img src="../img/catalogo/placas.png" alt="Placas">
                </a>
            </div>
            <div><a href="/app/servicos/papelaria.php">
                    <h2>Papelaria</h2>
                    <img src="../img/catalogo/papelaria.png" alt="Papelaria">
                </a>
            </div>
            <p>Outros produtos Em-Breve</p>
        </section>
    </main>

    <?php include __DIR__ . '/../include/footer.php' ?>
</body>

</html>