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
    } elseif (($_POST['acao'] ?? '') === 'calcular' && calcular_precos_carrinho($conexao, $_SESSION['id'])) {
        $_SESSION['mensagem_carrinho'] = 'Preços dos itens antigos calculados. Confira os valores antes de confirmar.';
        header('Location: carrinho.php', true, 303);
        exit;
    } elseif (($_POST['acao'] ?? '') === 'confirmar' && confirmar_pedidos($conexao, $_SESSION['id'])) {
        $_SESSION['mensagem_carrinho'] = 'Pedidos confirmados. Acompanhe o andamento em Meus pedidos.';
        header('Location: carrinho.php', true, 303);
        exit;
    } else {
        $erro = 'Não foi possível concluir a ação. Confira se os itens têm preços calculados e se os preços da categoria e do tipo estão cadastrados.';
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
    <title>Meu Carrinho — ArtUP</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(url_app('css/estilo.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>

<body>
    <?php include __DIR__ . '/../include/header.php' ?>

    <main class="container secao-pagina">
        <div style="display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 20px;">
            <div>
                <p class="sobretitulo">Revisão de orçamento</p>
                <h1>Meu carrinho</h1>
                <p class="subtitulo">Confira os produtos antes de confirmar. Cada item gerará um pedido individual.</p>
            </div>
            <a href="meus_pedidos.php" class="botao botao-secundario botao-sm">Acompanhar meus pedidos &rarr;</a>
        </div>

        <?php if ($mensagem): ?>
            <div class="alerta alerta-sucesso" role="status"><?= $escapar($mensagem) ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro" role="alert"><?= $escapar($erro) ?></div>
        <?php endif; ?>

        <?php if ($produtos === false): ?>
            <div class="alerta alerta-erro" role="alert">Não foi possível carregar o carrinho. Tente novamente.</div>
        <?php elseif (!$produtos): ?>
            <div class="estado-vazio">
                <p>Seu carrinho está vazio</p>
                <p>Escolha um dos nossos serviços gráficos para configurar seu produto e solicitar um orçamento.</p>
                <a href="catalogo.php" class="botao">Ver catálogo de serviços</a>
            </div>
        <?php else: ?>
            <div class="tabela-responsiva" tabindex="0" role="region" aria-label="Produtos em verificação no carrinho">
                <table>
                    <caption>Produtos no carrinho aguardando confirmação</caption>
                    <thead>
                        <tr>
                            <th scope="col">Produto</th>
                            <th scope="col">Tipo / acabamento</th>
                            <th scope="col" class="numerico">Qtd</th>
                            <th scope="col" class="numerico">Preço unitário</th>
                            <th scope="col" class="numerico">Total</th>
                            <th scope="col">Dimensões (<?= $escapar(UNIDADE_MEDIDAS) ?>)</th>
                            <th scope="col">Tamanho</th>
                            <th scope="col">Espessura</th>
                            <th scope="col">Arte</th>
                            <th scope="col">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $produto): ?>
                            <tr>
                                <td><strong><?= $escapar($produto['categoria']) ?></strong></td>
                                <td><?= $escapar($produto['tipo'] ?? '—') ?></td>
                                <td class="numerico"><?= $escapar($produto['quantidade']) ?></td>
                                <td class="numerico"><?= $escapar(formatar_preco($produto['preco_unitario'])) ?></td>
                                <td class="numerico"><strong><?= $escapar(formatar_preco($produto['valor_total'])) ?></strong></td>
                                <td>
                                    <?php if ($produto['comprimento'] !== null && $produto['largura'] !== null): ?>
                                        <?= $escapar($produto['comprimento']) ?> × <?= $escapar($produto['largura']) ?> cm
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td><?= $escapar($produto['tamanho'] ?? '—') ?></td>
                                <td><?= $produto['grossura'] !== null ? $escapar($produto['grossura']) . ' mm' : '—' ?></td>
                                <td>
                                    <?php if ($produto['arte_nome'] !== null): ?>
                                        <a href="arte.php?produto=<?= $escapar($produto['id_produto']) ?>" title="Baixar arte">
                                            <?= $escapar($produto['arte_nome']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--secundario);">Sem arte</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form action="carrinho.php" method="post" style="margin: 0;">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id_carrinho" value="<?= $escapar($produto['id_carrinho']) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_carrinho']) ?>">
                                        <button type="submit" class="botao botao-perigo botao-sm" aria-label="Excluir <?= $escapar($produto['categoria']) ?> do carrinho">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="resumo-carrinho">
                <div>
                    <p class="total-destaque">
                        Total do carrinho: <span><?= $escapar(formatar_preco($produtos[0]['total_carrinho'])) ?></span>
                    </p>
                    <p style="margin: 4px 0 0; color: var(--secundario); font-size: 13px;">Preços fixados e calculados com base nas tarifas cadastradas.</p>
                </div>

                <div>
                    <?php if ($produtos[0]['total_carrinho'] === null): ?>
                        <div class="alerta alerta-aviso" style="margin-bottom: 12px;">
                            Há itens antigos sem preço. Calcule os valores antes de confirmar os pedidos.
                        </div>
                        <form action="carrinho.php" method="post">
                            <input type="hidden" name="acao" value="calcular">
                            <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_carrinho']) ?>">
                            <button type="submit" class="botao botao-secundario">Calcular preços dos itens</button>
                        </form>
                    <?php else: ?>
                        <form action="carrinho.php" method="post" style="margin: 0;">
                            <input type="hidden" name="acao" value="confirmar">
                            <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_carrinho']) ?>">
                            <button type="submit" class="botao" style="font-size: 15px; padding: 12px 28px;">Confirmar pedidos &rarr;</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../include/footer.php' ?>
</body>

</html>
