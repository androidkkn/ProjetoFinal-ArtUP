<?php require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../login/verifica_user.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo — ArtUP</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(url_app('css/estilo.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>

<body>
    <?php include __DIR__ . '/../include/header.php' ?>

    <main class="container">
        <section class="secao-pagina">
            <h1>Escolha o tipo de serviço</h1>
            <p>Selecione uma categoria para configurar seu produto, calcular o preço e enviar sua arte.</p>
        </section>

        <div class="grade-catalogo">
            <div class="card">
                <a href="<?= htmlspecialchars(url_app('app/servicos/banner.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars(url_app('img/banner.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Banners em lona">
                    <div class="card-body">
                        <h3>Banners</h3>
                        <p>Impressão em lona com medidas personalizadas e acabamento para fixação.</p>
                    </div>
                </a>
            </div>
            <div class="card">
                <a href="<?= htmlspecialchars(url_app('app/servicos/adesivos.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars(url_app('img/adesivos.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Adesivos em vinil">
                    <div class="card-body">
                        <h3>Adesivos</h3>
                        <p>Vinil, BOPP e outros acabamentos em diversas medidas.</p>
                    </div>
                </a>
            </div>
            <div class="card">
                <a href="<?= htmlspecialchars(url_app('app/servicos/placas.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars(url_app('img/placas.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Placas rígidas em PVC">
                    <div class="card-body">
                        <h3>Placas</h3>
                        <p>PVC rígido com impressão direta, disponível em 1, 2 e 3 mm.</p>
                    </div>
                </a>
            </div>
            <div class="card">
                <a href="<?= htmlspecialchars(url_app('app/servicos/papelaria.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars(url_app('img/papelaria.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Papelaria impressa">
                    <div class="card-body">
                        <h3>Papelaria</h3>
                        <p>Folhetos, cartões e impressos de A1 a A8.</p>
                    </div>
                </a>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../include/footer.php' ?>
</body>

</html>
