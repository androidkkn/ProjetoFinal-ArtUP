<?php
require_once __DIR__ . '/../login/verifica_admin.php';
require_once __DIR__ . '/../include/functions.php';

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
    $salvo = false;
    $mensagem_sucesso = '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_pedidos_admin'], $token)) {
        $erro = 'Recarregue a página e tente salvar novamente.';
    } else {
        switch ($_POST['acao'] ?? 'status') {
            case 'status':
                $salvo = alterar_status_pedido($conexao, $_POST['id_pedido'] ?? null, $_POST['status'] ?? null);
                $mensagem_sucesso = 'Status do pedido atualizado.';
                break;
            case 'aprovar_cancelamento':
            case 'recusar_cancelamento':
                $aprovar = $_POST['acao'] === 'aprovar_cancelamento';
                $salvo = responder_cancelamento_pedido($conexao, $_POST['id_solicitacao'] ?? null, $aprovar ? 'aprovar' : 'recusar');
                $mensagem_sucesso = $aprovar ? 'Cancelamento aprovado. O pedido foi cancelado.' : 'Solicitação recusada. O status do pedido foi mantido.';
                break;
            case 'excluir':
                if (($_POST['confirmar_exclusao'] ?? null) === '1') {
                    $salvo = excluir_pedido_admin($conexao, $_POST['id_pedido'] ?? null);
                }
                $mensagem_sucesso = 'Pedido excluído.';
                break;
        }
        if ($salvo) {
            $_SESSION['mensagem_pedidos_admin'] = $mensagem_sucesso;
            header('Location: pedidos.php' . ($pesquisa !== '' ? '?pesquisa=' . rawurlencode($pesquisa) : ''), true, 303);
            exit;
        }
        $erro = 'Não foi possível concluir a ação. Confira se o pedido ou a solicitação ainda estão disponíveis. Para excluir, use Confirmar exclusão.';
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
                        <th scope="col">Medidas (<?= $escapar(UNIDADE_MEDIDAS) ?>) / papel / espessura</th>
                        <th scope="col">Arte</th>
                        <th scope="col">Status</th>
                        <th scope="col">Solicitação de cancelamento</th>
                        <th scope="col">Excluir</th>
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
                            <td><?php if ($pedido['arte_nome'] !== null): ?>
                                <a href="../app/arte.php?produto=<?= $escapar($pedido['id_produto']) ?>">Baixar <?= $escapar($pedido['arte_nome']) ?></a>
                            <?php else: ?>Sem arte<?php endif; ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="acao" value="status">
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
                            <td>
                                <?php if ($pedido['cancelamento_status'] !== null): ?>
                                    <p><?= $escapar(STATUS_CANCELAMENTO[$pedido['cancelamento_status']]) ?>
                                        — <?= $escapar($pedido['cancelamento_data']) ?></p>
                                    <?php if ($pedido['cancelamento_motivo'] !== ''): ?><p><?= $escapar($pedido['cancelamento_motivo']) ?></p><?php endif; ?>
                                    <?php if ($pedido['cancelamento_status'] === 'pendente'): ?>
                                        <form method="post">
                                            <input type="hidden" name="id_solicitacao" value="<?= $escapar($pedido['id_cancelamento']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_pedidos_admin']) ?>">
                                            <button type="submit" name="acao" value="aprovar_cancelamento" <?= !in_array($pedido['status'], PEDIDOS_CANCELAVEIS, true) ? 'disabled' : '' ?>>Aprovar cancelamento</button>
                                            <button type="submit" name="acao" value="recusar_cancelamento">Recusar solicitação</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>Nenhuma solicitação<?php endif; ?>
                            </td>
                            <td>
                                <details>
                                    <summary>Excluir pedido #<?= $escapar($pedido['id_pedido']) ?></summary>
                                    <p>A exclusão deste pedido é definitiva.</p>
                                    <form method="post">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id_pedido" value="<?= $escapar($pedido['id_pedido']) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_pedidos_admin']) ?>">
                                        <button type="submit" name="confirmar_exclusao" value="1">Confirmar exclusão</button>
                                    </form>
                                </details>
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
