<?php
require_once __DIR__ . '/../login/verifica_user.php';
require_once __DIR__ . '/../include/functions.php';

$escapar = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$pesquisa = $_GET['pesquisa_produto'] ?? '';
$erro = '';
$_SESSION['csrf_meus_pedidos'] ??= bin2hex(random_bytes(32));
$mensagem = $_SESSION['mensagem_meus_pedidos'] ?? '';
unset($_SESSION['mensagem_meus_pedidos']);
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!is_string($token) || !hash_equals($_SESSION['csrf_meus_pedidos'], $token)) {
        $erro = 'Recarregue a página e tente enviar novamente.';
    } elseif (($_POST['acao'] ?? '') === 'solicitar_cancelamento'
        && solicitar_cancelamento_pedido($conexao, $_POST['id_pedido'] ?? null, $_POST['motivo'] ?? '')) {
        $_SESSION['mensagem_meus_pedidos'] = 'Solicitação de cancelamento enviada. Aguarde a análise da gráfica.';
        header('Location: meus_pedidos.php' . ($pesquisa !== '' ? '?pesquisa_produto=' . rawurlencode($pesquisa) : ''), true, 303);
        exit;
    } else {
        $erro = 'Não foi possível solicitar o cancelamento. O pedido deve ser seu, estar pendente ou em produção e não ter outra solicitação em análise. O motivo pode ter até 1000 caracteres.';
    }
}
// O usuário vem exclusivamente do login, inclusive quando há pesquisa.
$pedidos = $erro === '' ? meus_pedidos($conexao, $_SESSION['id'], $numero_produto) : [];

$status_classe = [
    'pendente' => 'status-pendente',
    'em producao' => 'status-producao',
    'concluido' => 'status-concluido',
    'retirada' => 'status-retirada',
    'cancelado' => 'status-cancelado',
];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos — ArtUP</title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(url_app('img/logos/logo.png'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(url_app('css/estilo.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>

<body>
    <?php include __DIR__ . '/../include/header.php' ?>

    <main id="conteudo" class="container secao-pagina" tabindex="-1">
        <div style="display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 20px;">
            <div>
                <p class="sobretitulo">Área do Cliente</p>
                <h1>Meus pedidos</h1>
                <p class="subtitulo">Acompanhe a produção, baixe as artes enviadas e solicite cancelamentos.</p>
            </div>
            <a href="catalogo.php" class="botao botao-secundario botao-sm">+ Novo pedido</a>
        </div>

        <section class="barra-pesquisa">
            <form action="" method="get">
                <label for="pesquisa_produto">Filtrar por produto:</label>
                <input type="number" min="1" step="1" name="pesquisa_produto" id="pesquisa_produto"
                    placeholder="Digite o número do produto" value="<?= $escapar($pesquisa) ?>">
                <button type="submit" class="botao botao-sm">Pesquisar</button>
                <?php if ($pesquisa !== ''): ?>
                    <a href="meus_pedidos.php" class="botao botao-secundario botao-sm">Limpar filtro</a>
                <?php endif; ?>
            </form>
        </section>

        <?php if ($mensagem !== ''): ?>
            <div class="alerta alerta-sucesso" role="status"><?= $escapar($mensagem) ?></div>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <div class="alerta alerta-erro" role="alert"><?= $escapar($erro) ?></div>
        <?php elseif ($pedidos === false): ?>
            <div class="alerta alerta-erro" role="alert">Não foi possível carregar seus pedidos. Tente novamente.</div>
        <?php elseif (!$pedidos): ?>
            <div class="estado-vazio">
                <p><?= $numero_produto !== null ? 'Nenhum pedido encontrado para esse produto.' : 'Você ainda não possui pedidos cadastrados.' ?></p>
                <p>Navegue pelo nosso catálogo e solicite seu primeiro trabalho gráfico.</p>
                <a href="catalogo.php" class="botao">Explorar catálogo</a>
            </div>
        <?php else: ?>
            <div class="tabela-responsiva" tabindex="0" role="region" aria-label="Histórico de pedidos do cliente">
                <table>
                    <caption>Seus pedidos confirmados e em produção</caption>
                    <thead>
                        <tr>
                            <th scope="col">Pedido</th>
                            <th scope="col">Nº Prod.</th>
                            <th scope="col">Data</th>
                            <th scope="col">Status</th>
                            <th scope="col">Produto / Tipo</th>
                            <th scope="col" class="numerico">Qtd</th>
                            <th scope="col" class="numerico">Unitário</th>
                            <th scope="col" class="numerico">Total</th>
                            <th scope="col">Dimensões (<?= $escapar(UNIDADE_MEDIDAS) ?>)</th>
                            <th scope="col">Formato</th>
                            <th scope="col">Espessura</th>
                            <th scope="col">Arte</th>
                            <th scope="col">Cancelamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><strong>#<?= $escapar($pedido['id_pedido']) ?></strong></td>
                                <td><?= $escapar($pedido['id_produto']) ?></td>
                                <td><?= $escapar($pedido['data_formatada']) ?></td>
                                <td>
                                    <?php
                                    $classe = $status_classe[$pedido['status']] ?? 'status-desconhecido';
                                    $rotulo = STATUS_PEDIDOS[$pedido['status']] ?? $pedido['status'];
                                    ?>
                                    <span class="badge <?= $classe ?>"><?= $escapar($rotulo) ?></span>
                                </td>
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
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td><?= $escapar($pedido['tamanho'] ?? '—') ?></td>
                                <td><?= $pedido['grossura'] !== null ? $escapar($pedido['grossura']) . ' mm' : '—' ?></td>
                                <td>
                                    <?php if ($pedido['arte_nome'] !== null): ?>
                                        <a href="arte.php?produto=<?= $escapar($pedido['id_produto']) ?>" title="Baixar arte">
                                            <?= $escapar($pedido['arte_nome']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="texto-secundario">Sem arte</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($pedido['cancelamento_status'] !== null): ?>
                                        <div class="box-cancelamento">
                                            <strong>Status:</strong> <?= $escapar(STATUS_CANCELAMENTO[$pedido['cancelamento_status']]) ?><br>
                                            <small class="texto-secundario"><?= $escapar($pedido['cancelamento_data']) ?></small>
                                            <?php if ($pedido['cancelamento_motivo'] !== ''): ?>
                                                <p style="margin-top: 4px; font-style: italic;">"<?= $escapar($pedido['cancelamento_motivo']) ?>"</p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (in_array($pedido['status'], PEDIDOS_CANCELAVEIS, true) && $pedido['cancelamento_status'] !== 'pendente'): ?>
                                        <form method="post" style="margin: 4px 0 0;">
                                            <input type="hidden" name="acao" value="solicitar_cancelamento">
                                            <input type="hidden" name="id_pedido" value="<?= $escapar($pedido['id_pedido']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_meus_pedidos']) ?>">
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <label for="motivo_<?= $escapar($pedido['id_pedido']) ?>" style="font-size: 11px; font-weight: 600; color: var(--secundario);">Motivo (opcional):</label>
                                                <textarea name="motivo" id="motivo_<?= $escapar($pedido['id_pedido']) ?>" maxlength="1000" rows="1" placeholder="Motivo do cancelamento" style="font-size: 12px; padding: 4px 8px; border: 1px solid var(--borda-foco); border-radius: 6px;"></textarea>
                                                <button type="submit" class="botao botao-secundario botao-sm" style="color: var(--erro); border-color: #e8afb5;">Solicitar cancelamento</button>
                                            </div>
                                        </form>
                                    <?php elseif ($pedido['cancelamento_status'] === 'pendente'): ?>
                                        <small class="texto-secundario">Aguardando análise da gráfica.</small>
                                    <?php elseif ($pedido['cancelamento_status'] === null): ?>
                                        <small class="texto-secundario">Não cancelável</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../include/footer.php' ?>
</body>

</html>
