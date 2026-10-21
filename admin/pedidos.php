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
    <title>Administração de Pedidos — ArtUP</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(url_app('css/estilo.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <?php include __DIR__ . '/../include/header.php'; ?>
    <main id="conteudo" class="container secao-pagina" tabindex="-1">
        <div style="display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 20px;">
            <div>
                <p class="sobretitulo">ArtUP / Painel Administrativo</p>
                <h1>Gestão de pedidos</h1>
                <p class="subtitulo">Acompanhe todos os pedidos de clientes, altere status de produção, aprove cancelamentos e gerencie itens.</p>
            </div>
            <a href="estatisticas.php" class="botao botao-secundario botao-sm">Ver estatísticas &rarr;</a>
        </div>

        <section class="barra-pesquisa">
            <form method="get" action="pedidos.php">
                <label for="pesquisa">Pesquisar cliente ou ID:</label>
                <input type="search" name="pesquisa" id="pesquisa" placeholder="Nome do cliente ou ID do pedido" value="<?= $escapar($pesquisa) ?>">
                <button type="submit" class="botao botao-sm">Pesquisar</button>
                <?php if ($pesquisa !== ''): ?>
                    <a href="pedidos.php" class="botao botao-secundario botao-sm">Ver todos os pedidos</a>
                <?php endif; ?>
            </form>
        </section>

        <?php if ($mensagem): ?>
            <div class="alerta alerta-sucesso" role="status"><?= $escapar($mensagem) ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro" role="alert"><?= $escapar($erro) ?></div>
        <?php endif; ?>

        <?php if ($pedidos === false): ?>
            <div class="alerta alerta-erro" role="alert">Não foi possível carregar os pedidos. Tente novamente.</div>
        <?php elseif (!$pedidos): ?>
            <div class="estado-vazio">
                <p><?= $pesquisa !== '' ? 'Nenhum pedido encontrado para a busca "' . $escapar($pesquisa) . '".' : 'Nenhum pedido cadastrado no sistema.' ?></p>
                <p>Assim que os clientes confirmarem seus carrinhos, os pedidos aparecerão nesta listagem.</p>
                <?php if ($pesquisa !== ''): ?>
                    <a href="pedidos.php" class="botao">Limpar busca</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="tabela-responsiva" tabindex="0" role="region" aria-label="Tabela de pedidos administrativos">
                <table>
                    <caption>Listagem completa de pedidos</caption>
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Data</th>
                            <th scope="col">Produto / Tipo</th>
                            <th scope="col" class="numerico">Qtd</th>
                            <th scope="col" class="numerico">Unitário</th>
                            <th scope="col" class="numerico">Total</th>
                            <th scope="col">Dimensões / Detalhes</th>
                            <th scope="col">Arte</th>
                            <th scope="col">Status</th>
                            <th scope="col">Cancelamento</th>
                            <th scope="col">Excluir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><strong>#<?= $escapar($pedido['id_pedido']) ?></strong></td>
                                <td><?= $escapar($pedido['cliente']) ?></td>
                                <td><?= $escapar($pedido['data_formatada']) ?></td>
                                <td>
                                    <strong><?= $escapar($pedido['categoria']) ?></strong>
                                    <?php if ($pedido['tipo'] !== null): ?>
                                        <br><small class="texto-secundario"><?= $escapar($pedido['tipo']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="numerico"><?= $escapar($pedido['quantidade']) ?></td>
                                <td class="numerico"><?= $escapar(formatar_preco($pedido['preco_unitario'])) ?></td>
                                <td class="numerico"><strong><?= $escapar(formatar_preco($pedido['valor_total'])) ?></strong></td>
                                <td>
                                    <?php if ($pedido['comprimento'] !== null && $pedido['largura'] !== null): ?>
                                        <?= $escapar($pedido['comprimento']) ?> × <?= $escapar($pedido['largura']) ?> cm
                                    <?php endif; ?>
                                    <?= $escapar($pedido['tamanho'] ?? '') ?>
                                    <?= $pedido['grossura'] !== null ? $escapar($pedido['grossura']) . ' mm' : '' ?>
                                </td>
                                <td>
                                    <?php if ($pedido['arte_nome'] !== null): ?>
                                        <a href="../app/arte.php?produto=<?= $escapar($pedido['id_produto']) ?>" title="Baixar arte enviada">
                                            <?= $escapar($pedido['arte_nome']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="texto-secundario">Sem arte</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" class="form-inline">
                                        <input type="hidden" name="acao" value="status">
                                        <input type="hidden" name="id_pedido" value="<?= $escapar($pedido['id_pedido']) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_pedidos_admin']) ?>">
                                        <select name="status" id="status_<?= $escapar($pedido['id_pedido']) ?>" aria-label="Status do pedido <?= $escapar($pedido['id_pedido']) ?>">
                                            <?php if (!isset(STATUS_PEDIDOS[$pedido['status']])): ?>
                                                <option value="<?= $escapar($pedido['status']) ?>" selected disabled><?= $escapar($pedido['status']) ?></option>
                                            <?php endif; ?>
                                            <?php foreach (STATUS_PEDIDOS as $valor => $rotulo): ?>
                                                <option value="<?= $escapar($valor) ?>" <?= $pedido['status'] === $valor ? 'selected' : '' ?>><?= $escapar($rotulo) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="botao botao-secundario botao-sm">Salvar</button>
                                    </form>
                                </td>
                                <td>
                                    <?php if ($pedido['cancelamento_status'] !== null): ?>
                                        <div class="box-cancelamento">
                                            <strong><?= $escapar(STATUS_CANCELAMENTO[$pedido['cancelamento_status']]) ?></strong><br>
                                            <small class="texto-secundario"><?= $escapar($pedido['cancelamento_data']) ?></small>
                                            <?php if ($pedido['cancelamento_motivo'] !== ''): ?>
                                                <p style="margin: 4px 0 0; font-style: italic;">"<?= $escapar($pedido['cancelamento_motivo']) ?>"</p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($pedido['cancelamento_status'] === 'pendente'): ?>
                                            <form method="post" style="display: flex; gap: 6px; margin-top: 6px;">
                                                <input type="hidden" name="id_solicitacao" value="<?= $escapar($pedido['id_cancelamento']) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_pedidos_admin']) ?>">
                                                <button type="submit" name="acao" value="aprovar_cancelamento" class="botao botao-sm botao-perigo" <?= !in_array($pedido['status'], PEDIDOS_CANCELAVEIS, true) ? 'disabled' : '' ?>>Aprovar</button>
                                                <button type="submit" name="acao" value="recusar_cancelamento" class="botao botao-sm botao-secundario">Recusar</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="texto-secundario">Nenhuma</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <details class="detalhes-exclusao">
                                        <summary>Excluir</summary>
                                        <p>A exclusão é definitiva. Confirma?</p>
                                        <form method="post" style="margin: 0;">
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id_pedido" value="<?= $escapar($pedido['id_pedido']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_pedidos_admin']) ?>">
                                            <button type="submit" name="confirmar_exclusao" value="1" class="botao botao-perigo botao-sm">Confirmar</button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/../include/footer.php'; ?>
</body>
</html>
