<?php
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../login/verifica_user.php';

$_SESSION['csrf_carrinho'] ??= bin2hex(random_bytes(32));
$erro = '';
$mensagem = $_SESSION['mensagem_carrinho'] ?? '';
unset($_SESSION['mensagem_carrinho']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!is_string($token) || !hash_equals($_SESSION['csrf_carrinho'], $token)) {
        $erro = 'Recarregue a página e tente novamente.';
    } elseif (($_POST['acao'] ?? '') === 'excluir'
        && excluir_produto_carrinho($conexao, $_SESSION['id'], $_POST['id_carrinho'] ?? null)) {
        $_SESSION['mensagem_carrinho'] = 'Produto excluído do carrinho.';
        header('Location: carrinho.php', true, 303);
        exit;
    } elseif (($_POST['acao'] ?? '') === 'confirmar' && confirmar_pedidos($conexao, $_SESSION['id'])) {
        $_SESSION['mensagem_carrinho'] = 'Pedidos confirmados. Acompanhe o andamento em Meus pedidos.';
        header('Location: carrinho.php', true, 303);
        exit;
    } else {
        $erro = 'Não foi possível concluir a ação. Recarregue o carrinho e tente novamente.';
    }
}

$produtos = carrinho($conexao, $_SESSION['id']);
$escapar = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho</title>
</head>

<body>
    <?php include __DIR__ . '/../include/header.php' ?>
    <main>
        <section>
            <h1>Meu carrinho</h1>
            <p>Itens em verificação. Confira os produtos antes de confirmar; você pode excluí-los enquanto estiverem no carrinho.</p>
            <a href="meus_pedidos.php">Acompanhar meus pedidos</a>
            <?php if ($mensagem): ?><p role="status"><?= $escapar($mensagem) ?></p><?php endif; ?>
            <?php if ($erro): ?><p role="alert"><?= $escapar($erro) ?></p><?php endif; ?>
            <?php if ($produtos === false): ?>
                <p role="alert">Não foi possível carregar o carrinho. Tente novamente.</p>
            <?php elseif (!$produtos): ?>
                <p>Seu carrinho está vazio.</p>
                <a href="catalogo.php">Ver produtos</a>
            <?php else: ?>
                <table>
                    <caption>Produtos em verificação</caption>
                    <thead>
                        <tr>
                            <th scope="col">Produto</th>
                            <th scope="col">Tipo / acabamento</th>
                            <th scope="col">Quantidade</th>
                            <th scope="col">Preço unitário</th>
                            <th scope="col">Total do produto</th>
                            <th scope="col">Comprimento × largura</th>
                            <th scope="col">Tamanho</th>
                            <th scope="col">Espessura</th>
                            <th scope="col">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $produto): ?>
                            <tr>
                                <td><?= $escapar($produto['categoria']) ?></td>
                                <td><?= $escapar($produto['tipo'] ?? '—') ?></td>
                                <td><?= $escapar($produto['quantidade']) ?></td>
                                <td><?= $escapar(formatar_preco($produto['preco_unitario'])) ?></td>
                                <td><?= $escapar(formatar_preco($produto['valor_total'])) ?></td>
                                <td><?php if ($produto['comprimento'] !== null && $produto['largura'] !== null): ?>
                                    <?= $escapar($produto['comprimento']) ?> × <?= $escapar($produto['largura']) ?>
                                <?php else: ?>—<?php endif; ?></td>
                                <td><?= $escapar($produto['tamanho'] ?? '—') ?></td>
                                <td><?= $produto['grossura'] !== null ? $escapar($produto['grossura']) . ' mm' : '—' ?></td>
                                <td>
                                    <form action="carrinho.php" method="post">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id_carrinho" value="<?= $escapar($produto['id_carrinho']) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_carrinho']) ?>">
                                        <button type="submit" aria-label="Excluir <?= $escapar($produto['categoria']) ?> do carrinho">Excluir produto</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><strong>Total do carrinho: <?= $escapar(formatar_preco($produtos[0]['total_carrinho'])) ?></strong></p>
                <form action="carrinho.php" method="post">
                    <input type="hidden" name="acao" value="confirmar">
                    <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_carrinho']) ?>">
                    <button type="submit">Confirmar pedidos</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
    <?php include __DIR__ . '/../include/footer.php' ?>
</body>
</html>
