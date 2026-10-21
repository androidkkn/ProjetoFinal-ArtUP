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
    <a href="<?= $url_admin('admin/clientes.php') ?>" <?= $pagina_admin === 'clientes.php' ? 'aria-current="page"' : '' ?>>Clientes</a>
    <a href="<?= $url_admin('admin/produtos.php') ?>" <?= $pagina_admin === 'produtos.php' ? 'aria-current="page"' : '' ?>>Produtos</a>
</nav>
