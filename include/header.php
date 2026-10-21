<?php
require_once __DIR__ . '/navegacao.php';
$logado = !empty($_SESSION['id']);
$administrador = $logado && ($_SESSION['admin'] ?? false) === true;
$url_menu = static fn($caminho) => htmlspecialchars(url_app($caminho), ENT_QUOTES, 'UTF-8');
if ($logado) {
    $_SESSION['csrf_logout'] ??= bin2hex(random_bytes(32));
}
?>
<header>
    <div class="header-logo">
        <a class="logo-link" href="<?= $url_menu('index.php') ?>" aria-label="ArtUP — Início">
            <img src="<?= $url_menu('img/logos/logo.png') ?>" alt="Logo ArtUP">
        </a>
    </div>
    <div>
        <nav aria-label="Navegação principal">
            <a href="<?= $url_menu('index.php') ?>">Início</a>
            <?php if ($logado): ?>
                <a href="<?= $url_menu('app/catalogo.php') ?>">Catálogo</a>
                <a href="<?= $url_menu('app/meus_pedidos.php') ?>">Meus pedidos</a>
                <a href="<?= $url_menu('app/carrinho.php') ?>">Carrinho</a>
                <?php if ($administrador): ?>
                    <a href="<?= $url_menu('admin/estatisticas.php') ?>">Administração</a>
                <?php endif; ?>
                <form action="<?= $url_menu('login/logout.php') ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_logout'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit">Sair</button>
                </form>
            <?php else: ?>
                <a href="<?= $url_menu('login/login.php') ?>">Entrar</a>
                <a href="<?= $url_menu('login/cadastrar.php') ?>">Cadastrar</a>
            <?php endif; ?>
        </nav>
    </div>
    <?php if ($administrador) { include __DIR__ . '/HeaderAdmin.php'; } ?>
</header>
