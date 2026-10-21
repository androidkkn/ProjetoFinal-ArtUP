<?php

declare(strict_types=1);

require_once __DIR__ . '/../login/verifica_admin.php';
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../include/estatisticas.php';

header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$escapar = static fn (mixed $valor): string => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$numero = static fn (mixed $valor): string => number_format((int) $valor, 0, ',', '.');
$periodo = periodo_estatisticas($_GET['inicio'] ?? null, $_GET['fim'] ?? null);
$erro = $periodo['erro'];
$dados = $erro === '' ? consultar_estatisticas_admin($conexao, $periodo['inicio'], $periodo['fim']) : false;
if ($erro !== '') {
    http_response_code(422);
} elseif ($dados === false) {
    http_response_code(500);
    $erro = 'Não foi possível carregar as estatísticas. Tente novamente em instantes.';
}
$rotulo_periodo = $erro === ''
    ? date('d/m/Y', strtotime($periodo['inicio'])) . ' a ' . date('d/m/Y', strtotime($periodo['fim']))
    : 'Escolha o período que deseja acompanhar';
$maior_mes = $dados !== false ? max(1, ...array_column($dados['por_mes'], 'pedidos')) : 1;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Estatísticas de pedidos | ArtUP</title>
    <link rel="stylesheet" href="<?= $escapar(url_app('css/estilo.css')) ?>">
    <link rel="stylesheet" href="<?= $escapar(url_app('admin/estatisticas.css')) ?>">
</head>
<body class="pagina-estatisticas">
    <?php include __DIR__ . '/../include/header.php'; ?>
    <main id="conteudo" class="estatisticas" tabindex="-1">
        <div class="titulo-pagina">
            <div>
                <p class="sobretitulo">ArtUP / Administração</p>
                <h1>Estatísticas</h1>
                <p class="subtitulo">Uma visão dos pedidos da gráfica, do orçamento confirmado à conclusão.</p>
            </div>
            <a class="botao botao-secundario" href="<?= $escapar(url_app('admin/pedidos.php')) ?>">Gerenciar pedidos <span aria-hidden="true">↗</span></a>
        </div>

        <form class="filtro-periodo" method="get" action="<?= $escapar(url_app('admin/estatisticas.php')) ?>" aria-label="Filtrar estatísticas por período">
            <div class="campo">
                <label for="inicio">Data inicial</label>
                <input type="date" id="inicio" name="inicio" value="<?= $escapar($periodo['inicio']) ?>" required aria-describedby="ajuda-periodo">
            </div>
            <div class="campo">
                <label for="fim">Data final</label>
                <input type="date" id="fim" name="fim" value="<?= $escapar($periodo['fim']) ?>" required aria-describedby="ajuda-periodo">
            </div>
            <button class="botao" type="submit">Aplicar período</button>
            <a class="link-discreto" href="<?= $escapar(url_app('admin/estatisticas.php')) ?>">Mês atual</a>
            <p id="ajuda-periodo">Inclui as duas datas. Consulte até cinco anos por vez.</p>
        </form>

        <?php if ($erro !== ''): ?>
            <div class="aviso aviso-erro" role="alert"><strong>Não foi possível mostrar o painel.</strong> <?= $escapar($erro) ?></div>
        <?php else: ?>
            <div class="cabecalho-resumo">
                <h2>Resumo do período</h2>
                <p><?= $escapar($rotulo_periodo) ?></p>
            </div>
            <div class="indicadores" aria-label="Indicadores de pedidos">
                <article class="indicador">
                    <h3>Pedidos confirmados</h3>
                    <p class="valor-indicador"><?= $numero($dados['pedidos']) ?></p>
                    <p>Inclui os cancelados do período</p>
                </article>
                <article class="indicador">
                    <h3>Em aberto</h3>
                    <p class="valor-indicador"><?= $numero($dados['em_aberto']) ?></p>
                    <p>Pendentes ou em produção</p>
                </article>
                <article class="indicador indicador-destaque">
                    <h3>Valor não cancelado</h3>
                    <p class="valor-indicador valor-monetario"><?= $escapar(formatar_preco($dados['valor_nao_cancelado'])) ?></p>
                    <p>Soma dos valores registrados</p>
                </article>
                <article class="indicador">
                    <h3>Ticket médio</h3>
                    <p class="valor-indicador valor-monetario"><?= $escapar(formatar_preco($dados['ticket_medio'])) ?></p>
                    <p>Pedidos não cancelados com valor</p>
                </article>
                <article class="indicador">
                    <h3>Clientes com pedidos</h3>
                    <p class="valor-indicador"><?= $numero($dados['clientes']) ?></p>
                    <p>Clientes distintos neste período</p>
                </article>
                <article class="indicador">
                    <h3>Cancelamentos em análise</h3>
                    <p class="valor-indicador"><?= $numero($dados['cancelamentos_pendentes']) ?></p>
                    <p>Solicitações dos pedidos do período</p>
                </article>
            </div>

            <p class="nota-valores">Os valores representam pedidos e não comprovam pagamentos recebidos. Cancelados ficam fora do valor não cancelado e do ticket médio.</p>
            <?php if ((int) $dados['sem_valor'] > 0): ?>
                <div class="aviso" role="status"><?= $numero($dados['sem_valor']) ?> pedido(s) estão sem valor registrado. Eles entram nas contagens, mas não nas somas ou médias monetárias.</div>
            <?php endif; ?>

            <?php if ((int) $dados['pedidos'] === 0): ?>
                <section class="painel estado-vazio">
                    <p class="sobretitulo">Sem movimento no período</p>
                    <h2>Nenhum pedido encontrado</h2>
                    <p>Experimente outras datas. Itens que ainda estão no carrinho não aparecem nas estatísticas.</p>
                </section>
            <?php else: ?>
                <div class="grade-paineis">
                    <section class="painel" aria-labelledby="titulo-status">
                        <div class="titulo-painel"><h2 id="titulo-status">Pedidos por status</h2><span class="etiqueta">Situação atual</span></div>
                        <ul class="lista-status">
                            <?php foreach ($dados['por_status'] as $linha): ?>
                                <?php $rotulo_status = STATUS_PEDIDOS[$linha['status']] ?? $linha['status']; ?>
                                <li>
                                    <div><span><?= $escapar($rotulo_status) ?></span><strong><?= $numero($linha['pedidos']) ?></strong></div>
                                    <meter min="0" max="<?= $escapar(max(1, (int) $dados['pedidos'])) ?>" value="<?= $escapar($linha['pedidos']) ?>" aria-label="<?= $escapar('Pedidos: ' . $rotulo_status) ?>"><?= $numero($linha['pedidos']) ?></meter>
                                    <p><?= $escapar(formatar_preco($linha['valor'])) ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>

                    <section class="painel" aria-labelledby="titulo-valores">
                        <div class="titulo-painel"><h2 id="titulo-valores">Valores dos pedidos</h2></div>
                        <dl class="lista-valores">
                            <div><dt>Não cancelados</dt><dd><?= $escapar(formatar_preco($dados['valor_nao_cancelado'])) ?></dd></div>
                            <div><dt>Concluídos</dt><dd><?= $escapar(formatar_preco($dados['valor_concluido'])) ?></dd></div>
                            <div><dt>Cancelados</dt><dd><?= $escapar(formatar_preco($dados['valor_cancelado'])) ?></dd></div>
                        </dl>
                        <p class="nota-painel">O valor concluído já está incluído no valor não cancelado. Os preços são os registrados nos pedidos, sem recalcular a tabela de tarifas.</p>
                        <?php if ((int) $dados['cancelamentos_pendentes'] > 0): ?>
                            <a class="botao botao-secundario" href="<?= $escapar(url_app('admin/pedidos.php')) ?>">Analisar cancelamentos</a>
                        <?php endif; ?>
                    </section>
                </div>

                <section class="painel" aria-labelledby="titulo-meses">
                    <div class="titulo-painel"><h2 id="titulo-meses">Evolução mensal</h2><span class="etiqueta">Data de criação do pedido</span></div>
                    <div class="tabela-responsiva tabela-estatisticas" tabindex="0" role="region" aria-label="Evolução mensal dos pedidos">
                        <table>
                            <caption>Pedidos criados no período selecionado, agrupados por mês</caption>
                            <thead><tr><th scope="col">Mês</th><th scope="col">Pedidos</th><th scope="col">Cancelados</th><th scope="col">Valor não cancelado</th></tr></thead>
                            <tbody>
                                <?php foreach ($dados['por_mes'] as $linha): ?>
                                    <?php $mes = substr($linha['mes'], 5, 2) . '/' . substr($linha['mes'], 0, 4); ?>
                                    <tr>
                                        <th scope="row"><?= $escapar($mes) ?></th>
                                        <td><div class="barra-mensal"><meter min="0" max="<?= $escapar($maior_mes) ?>" value="<?= $escapar($linha['pedidos']) ?>" aria-label="<?= $escapar('Pedidos em ' . $mes) ?>"><?= $numero($linha['pedidos']) ?></meter><strong><?= $numero($linha['pedidos']) ?></strong></div></td>
                                        <td><?= $numero($linha['cancelados']) ?></td>
                                        <td><?= $escapar(formatar_preco($linha['valor'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="painel" aria-labelledby="titulo-categorias">
                    <div class="titulo-painel"><h2 id="titulo-categorias">Pedidos por categoria</h2><span class="etiqueta">Por valor não cancelado</span></div>
                    <div class="tabela-responsiva tabela-estatisticas" tabindex="0" role="region" aria-label="Pedidos por categoria de produto">
                        <table>
                            <caption>Movimento das categorias no período</caption>
                            <thead><tr><th scope="col">Categoria</th><th scope="col">Pedidos</th><th scope="col">Unidades não canceladas</th><th scope="col">Valor não cancelado</th></tr></thead>
                            <tbody>
                                <?php foreach ($dados['por_categoria'] as $linha): ?>
                                    <tr><th scope="row"><?= $escapar($linha['categoria']) ?></th><td><?= $numero($linha['pedidos']) ?></td><td><?= $numero($linha['unidades']) ?></td><td><?= $escapar(formatar_preco($linha['valor'])) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
            <p class="nota-valores">Cada pedido corresponde a um item confirmado. O filtro usa a data de criação; status e solicitações mostram a situação atual. Pedidos excluídos deixam de compor o painel.</p>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/../include/footer.php'; ?>
</body>
</html>
