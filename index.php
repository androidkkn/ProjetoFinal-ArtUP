<?php require_once __DIR__ . '/login/verifica_user.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtUP — Gráfica</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(url_app('css/estilo.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>

<body>

<?php include __DIR__ . '/include/header.php' ?>

<main id="conteudo" tabindex="-1">
<section class="hero">
    <div class="hero-conteudo">
        <div>
            <p class="sobretitulo">Impressão sob medida</p>
            <h1>Sua ideia merece uma boa impressão.</h1>
            <p>Configure adesivos, banners, placas e papelaria, calcule o preço na hora, envie sua arte e acompanhe cada pedido.</p>
            <div class="acoes"><a href="<?= htmlspecialchars(url_app('app/catalogo.php'), ENT_QUOTES, 'UTF-8') ?>" class="botao">Ver catálogo</a>
                <a href="<?= htmlspecialchars(url_app('app/meus_pedidos.php'), ENT_QUOTES, 'UTF-8') ?>" class="botao botao-secundario">Acompanhar pedidos</a>
            </div>
        </div>
        <figure class="hero-figura">
            <img src="<?= htmlspecialchars(url_app('img/grafica-colecao.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Coleção de produtos gráficos: cartões, adesivos, folhetos e placas" class="hero-img" width="1536" height="1024" fetchpriority="high">
            <figcaption>Imagens ilustrativas</figcaption>
        </figure>
    </div>
</section>




    <section class="container servicos-home">
        <div class="cabecalho-secao"><div><p class="sobretitulo">O que vamos imprimir hoje?</p><h2>Nossos serviços</h2><p>Escolha o produto e confira o orçamento antes de confirmar.</p></div></div>
        <div class="grade-catalogo">
            <div class="card">
                <a href="<?= htmlspecialchars(url_app('app/servicos/adesivos.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars(url_app('img/adesivos.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Adesivos em vinil" width="1536" height="1024" loading="lazy" decoding="async">
                    <div class="card-body">
                        <h3>Adesivos</h3>
                        <p>Vinil, BOPP e outros acabamentos em medidas personalizadas.</p>
                        <span class="card-acao">Configurar produto <span aria-hidden="true">↗</span></span>
                    </div>
                </a>
            </div>
            <div class="card">
                <a href="<?= htmlspecialchars(url_app('app/servicos/banner.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars(url_app('img/banner.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Banner impresso" width="1536" height="1024" loading="lazy" decoding="async">
                    <div class="card-body">
                        <h3>Banners</h3>
                        <p>Impressão em lona com acabamento para fixação e exposição.</p>
                        <span class="card-acao">Configurar produto <span aria-hidden="true">↗</span></span>
                    </div>
                </a>
            </div>
            <div class="card">
                <a href="<?= htmlspecialchars(url_app('app/servicos/placas.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars(url_app('img/placas.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Placas rígidas impressas" width="1536" height="1024" loading="lazy" decoding="async">
                    <div class="card-body">
                        <h3>Placas</h3>
                        <p>PVC rígido em três espessuras com impressão direta.</p>
                        <span class="card-acao">Configurar produto <span aria-hidden="true">↗</span></span>
                    </div>
                </a>
            </div>
            <div class="card">
                <a href="<?= htmlspecialchars(url_app('app/servicos/papelaria.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars(url_app('img/papelaria.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Impressos em papel: folhetos e cartões" width="1536" height="1024" loading="lazy" decoding="async">
                    <div class="card-body">
                        <h3>Papelaria</h3>
                        <p>Impressão em diversos formatos, de A1 a A8.</p>
                        <span class="card-acao">Configurar produto <span aria-hidden="true">↗</span></span>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <section class="secao-passos">
        <h2>Como funciona</h2>
        <div class="grade-passos">
            <div class="passo">
                <div class="passo-numero">1</div>
                <h3>Configure o produto</h3>
                <p>Escolha o serviço, informe medidas em centímetros e quantidade. A arte é opcional.</p>
            </div>
            <div class="passo">
                <div class="passo-numero">2</div>
                <h3>Confira o preço</h3>
                <p>Calcule a prévia e veja o valor unitário e total antes de adicionar ao carrinho.</p>
            </div>
            <div class="passo">
                <div class="passo-numero">3</div>
                <h3>Confirme e acompanhe</h3>
                <p>Finalize seus pedidos e acompanhe o andamento pela área de pedidos.</p>
            </div>
        </div>
    </section>

</main>

<?php include __DIR__ . '/include/footer.php' ?>
</body>

</html>
