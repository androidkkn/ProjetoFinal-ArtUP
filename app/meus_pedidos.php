<?php
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../login/verifica_user.php';

$escapar = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$pesquisa = $_GET['pesquisa_produto'] ?? '';
$erro = '';
$numero_produto = null;
if (!is_string($pesquisa)) {
    $pesquisa = '';
    $erro = 'Informe um número de produto válido.';
} else {
    $pesquisa = trim($pesquisa);
    if ($pesquisa !== '') {
        $numero_produto = filter_var($pesquisa, FILTER_VALIDATE_INT);
        if (!$numero_produto || $numero_produto < 1 || $numero_produto > 2147483647) {
            $erro = 'Informe um número de produto válido.';
        }
    }
}
// O usuário vem exclusivamente do login, inclusive quando há pesquisa.
$pedidos = $erro === '' ? meus_pedidos($conexao, $_SESSION['id'], $numero_produto) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos</title>
</head>

<body>
    <?php include __DIR__ . '/../include/header.php' ?>

    <main>

        <section class="top_meusPedidos">
            <div>
                <h1>Meus pedidos</h1>
            </div>
            <div class="pesquisa">
                <form action="" method="get">
                    <label for="pesquisa_produto">Número do produto</label>
                    <input type="number" min="1" step="1" name="pesquisa_produto" id="pesquisa_produto"
                        placeholder="Pesquisa (n°produto)" value="<?= $escapar($pesquisa) ?>">
                    <button type="submit">Pesquisar</button>
                    <a href="meus_pedidos.php">Ver todos os pedidos</a>
                </form>
            </div>
        </section>

        <section class="pedidos">

            <?php if ($erro !== ''): ?>
                <p role="alert"><?= $escapar($erro) ?></p>
            <?php elseif ($pedidos === false): ?>
                <p role="alert">Não foi possível carregar seus pedidos. Tente novamente.</p>
            <?php elseif (!$pedidos): ?>
                <p><?= $numero_produto !== null ? 'Nenhum pedido encontrado para esse produto.' : 'Você ainda não possui pedidos.' ?></p>
            <?php else: ?>
                <table>
                    <caption>Pedidos do usuário logado</caption>
                    <thead>
                        <tr>
                            <th scope="col">Pedido</th>
                            <th scope="col">Nº do produto</th>
                            <th scope="col">Data</th>
                            <th scope="col">Status</th>
                            <th scope="col">Produto / tipo</th>
                            <th scope="col">Quantidade</th>
                            <th scope="col">Preço unitário</th>
                            <th scope="col">Total do pedido</th>
                            <th scope="col">Comprimento × largura</th>
                            <th scope="col">Tamanho</th>
                            <th scope="col">Espessura</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><?= $escapar($pedido['id_pedido']) ?></td>
                                <td><?= $escapar($pedido['id_produto']) ?></td>
                                <td><?= $escapar($pedido['data_formatada']) ?></td>
                                <td><?= $escapar($pedido['status']) ?></td>
                                <td><?= $escapar($pedido['categoria']) ?><?php if ($pedido['tipo'] !== null): ?>
                                    / <?= $escapar($pedido['tipo']) ?><?php endif; ?></td>
                                <td><?= $escapar($pedido['quantidade']) ?></td>
                                <td><?= $escapar(formatar_preco($pedido['preco_unitario'])) ?></td>
                                <td><?= $escapar(formatar_preco($pedido['valor_total'])) ?></td>
                                <td><?php if ($pedido['comprimento'] !== null && $pedido['largura'] !== null): ?>
                                    <?= $escapar($pedido['comprimento']) ?> × <?= $escapar($pedido['largura']) ?>
                                <?php else: ?>—<?php endif; ?></td>
                                <td><?= $escapar($pedido['tamanho'] ?? '—') ?></td>
                                <td><?= $pedido['grossura'] !== null ? $escapar($pedido['grossura']) . ' mm' : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    </main>

    <?php include __DIR__ . '/../include/footer.php' ?>
</body>

</html>
