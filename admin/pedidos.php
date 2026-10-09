<?php
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../login/verifica_admin.php';

$escapar = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$pesquisa = $_GET['pesquisa'] ?? '';
$erro = '';
if (!is_string($pesquisa)) {
    $pesquisa = '';
    $erro = 'Informe o nome do cliente ou o ID do pedido.';
}
$pesquisa = trim($pesquisa);
$_SESSION['csrf_pedidos_admin'] ??= bin2hex(random_bytes(32));
$mensagem = $_SESSION['mensagem_pedidos_admin'] ?? '';
unset($_SESSION['mensagem_pedidos_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!is_string($token) || !hash_equals($_SESSION['csrf_pedidos_admin'], $token)) {
        $erro = 'Recarregue a página e tente salvar novamente.';
    } elseif (alterar_status_pedido($conexao, $_POST['id_pedido'] ?? null, $_POST['status'] ?? null)) {
        $_SESSION['mensagem_pedidos_admin'] = 'Status do pedido atualizado.';
        header('Location: pedidos.php' . ($pesquisa !== '' ? '?pesquisa=' . rawurlencode($pesquisa) : ''), true, 303);
        exit;
    } else {
        $erro = 'Não foi possível alterar o status. Confira o pedido e o status e tente novamente.';
    }
}
$pedidos = listar_pedidos_admin($conexao, $pesquisa);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração de pedidos</title>
</head>
<body>
    <?php include __DIR__ . '/../include/header.php'; ?>
    <main>
        <h1>Todos os pedidos</h1>
        <a href="estatisticas.php">Administração</a>
        <form method="get" action="pedidos.php">
            <label for="pesquisa">Nome do cliente ou ID do pedido</label>
            <input type="search" name="pesquisa" id="pesquisa" value="<?= $escapar($pesquisa) ?>">
            <button type="submit">Pesquisar</button>
            <a href="pedidos.php">Ver todos</a>
        </form>

        <?php if ($mensagem): ?><p role="status"><?= $escapar($mensagem) ?></p><?php endif; ?>
        <?php if ($erro): ?><p role="alert"><?= $escapar($erro) ?></p><?php endif; ?>
        <?php if ($pedidos === false): ?>
            <p role="alert">Não foi possível carregar os pedidos. Tente novamente.</p>
        <?php elseif (!$pedidos): ?>
            <p>Nenhum pedido encontrado.</p>
        <?php else: ?>
            <table>
                <caption>Pedidos de todos os clientes</caption>
                <thead>
                    <tr>
                        <th scope="col">ID do pedido</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Data</th>
                        <th scope="col">Produto / tipo</th>
                        <th scope="col">Quantidade</th>
                        <th scope="col">Preço unitário</th>
                        <th scope="col">Total do pedido</th>
                        <th scope="col">Medidas</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td><?= $escapar($pedido['id_pedido']) ?></td>
                            <td><?= $escapar($pedido['cliente']) ?></td>
                            <td><?= $escapar($pedido['data_formatada']) ?></td>
                            <td><?= $escapar($pedido['categoria']) ?><?php if ($pedido['tipo'] !== null): ?>
                                / <?= $escapar($pedido['tipo']) ?><?php endif; ?></td>
                            <td><?= $escapar($pedido['quantidade']) ?></td>
                            <td><?= $escapar(formatar_preco($pedido['preco_unitario'])) ?></td>
                            <td><?= $escapar(formatar_preco($pedido['valor_total'])) ?></td>
                            <td>
                                <?php if ($pedido['comprimento'] !== null && $pedido['largura'] !== null): ?>
                                    <?= $escapar($pedido['comprimento']) ?> × <?= $escapar($pedido['largura']) ?>
                                <?php endif; ?>
                                <?= $escapar($pedido['tamanho'] ?? '') ?>
                                <?= $pedido['grossura'] !== null ? $escapar($pedido['grossura']) . ' mm' : '' ?>
                            </td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="id_pedido" value="<?= $escapar($pedido['id_pedido']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_pedidos_admin']) ?>">
                                    <label for="status_<?= $escapar($pedido['id_pedido']) ?>">Status do pedido <?= $escapar($pedido['id_pedido']) ?></label>
                                    <select name="status" id="status_<?= $escapar($pedido['id_pedido']) ?>">
                                        <?php if (!isset(STATUS_PEDIDOS[$pedido['status']])): ?>
                                            <option value="<?= $escapar($pedido['status']) ?>" selected disabled><?= $escapar($pedido['status']) ?></option>
                                        <?php endif; ?>
                                        <?php foreach (STATUS_PEDIDOS as $valor => $rotulo): ?>
                                            <option value="<?= $escapar($valor) ?>" <?= $pedido['status'] === $valor ? 'selected' : '' ?>><?= $escapar($rotulo) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit">Salvar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/../include/footer.php'; ?>
</body>
</html>
