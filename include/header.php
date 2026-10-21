<?php
require_once __DIR__ . '/navegacao.php';
$logado = !empty($_SESSION['id']);
$administrador = $logado && ($_SESSION['admin'] ?? false) === true;
$url_menu = static fn($caminho) => htmlspecialchars(url_app($caminho), ENT_QUOTES, 'UTF-8');
$pagina_menu = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$pagina_atual = static fn($caminho) => $pagina_menu === url_app($caminho);
$em_servico = str_contains($pagina_menu, '/app/servicos/');
$em_administracao = str_contains($pagina_menu, '/admin/');
if ($logado) {
    $_SESSION['csrf_logout'] ??= bin2hex(random_bytes(32));
}
?>
<a class="pular-conteudo" href="#conteudo">Ir para o conteúdo</a>
<header>
    <div class="header-logo">
        <a class="logo-link" href="<?= $url_menu('index.php') ?>" aria-label="ArtUP — Início">
            <img src="<?= $url_menu('img/logos/logo.png') ?>" alt="Logo ArtUP" width="144" height="80">
        </a>
    </div>
    <div>
        <nav aria-label="Navegação principal">
            <a href="<?= $url_menu('index.php') ?>" <?= $pagina_atual('index.php') ? 'aria-current="page"' : '' ?>>Início</a>
            <?php if ($logado): ?>
                <a href="<?= $url_menu('app/catalogo.php') ?>" <?= $pagina_atual('app/catalogo.php') ? 'aria-current="page"' : ($em_servico ? 'aria-current="location"' : '') ?>>Catálogo</a>
                <a href="<?= $url_menu('app/meus_pedidos.php') ?>" <?= $pagina_atual('app/meus_pedidos.php') ? 'aria-current="page"' : '' ?>>Meus pedidos</a>
                <a href="<?= $url_menu('app/carrinho.php') ?>" <?= $pagina_atual('app/carrinho.php') ? 'aria-current="page"' : '' ?>>Carrinho</a>
                <?php if ($administrador): ?>
                    <a href="<?= $url_menu('admin/estatisticas.php') ?>" <?= $em_administracao ? 'aria-current="location"' : '' ?>>Administração</a>
                <?php endif; ?>
                <form action="<?= $url_menu('login/logout.php') ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_logout'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit">Sair</button>
                </form>
            <?php else: ?>
                <a href="<?= $url_menu('login/login.php') ?>" <?= $pagina_atual('login/login.php') ? 'aria-current="page"' : '' ?>>Entrar</a>
                <a href="<?= $url_menu('login/cadastrar.php') ?>" <?= $pagina_atual('login/cadastrar.php') ? 'aria-current="page"' : '' ?>>Cadastrar</a>
            <?php endif; ?>
        </nav>
    </div>
    <?php if ($administrador) { include __DIR__ . '/HeaderAdmin.php'; } ?>
</header>
