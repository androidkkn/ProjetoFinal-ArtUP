# Shared layouts

## include/header.php

```php
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
    <div><a href="<?= $url_menu('index.php') ?>" aria-label="ArtUP — Início"><img src="<?= $url_menu('img/logos/logo.png') ?>" alt="Logo ArtUP" width="10%"></a></div>
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

```

## include/HeaderAdmin.php

```php
<?php
if (empty($_SESSION['id']) || ($_SESSION['admin'] ?? false) !== true) {
    return;
}
require_once __DIR__ . '/navegacao.php';
$url_admin = static fn($caminho) => htmlspecialchars(url_app($caminho), ENT_QUOTES, 'UTF-8');
$pagina_admin = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<nav aria-label="Administração">
    <a href="<?= $url_admin('admin/estatisticas.php') ?>" <?= $pagina_admin === 'estatisticas.php' ? 'aria-current="page"' : '' ?>>Estatísticas</a>
    <a href="<?= $url_admin('admin/pedidos.php') ?>" <?= $pagina_admin === 'pedidos.php' ? 'aria-current="page"' : '' ?>>Pedidos</a>
    <a href="<?= $url_admin('admin/produtos.php') ?>" <?= $pagina_admin === 'produtos.php' ? 'aria-current="page"' : '' ?>>Produtos</a>
</nav>

```

## include/footer.php

```php
<footer>
    <hr>
    <div><p>TEM Q FAZER O FOOTER VIU KAUAN</p></div>
</footer>
```

## include/navegacao.php

```php
<?php

function url_app($caminho)
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $diretorio = str_replace('\\', '/', dirname($script));
    if (basename($diretorio) === 'servicos' && basename(dirname($diretorio)) === 'app') {
        $diretorio = dirname($diretorio, 2);
    } elseif (in_array(basename($diretorio), ['app', 'admin', 'login', 'include'], true)) {
        $diretorio = dirname($diretorio);
    }
    $diretorio = str_replace('\\', '/', $diretorio);
    $base = $diretorio === '.' ? '' : rtrim($diretorio, '/');
    return $base . '/' . ltrim($caminho, '/');
}

```

