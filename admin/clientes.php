<?php

declare(strict_types=1);

require_once __DIR__ . '/../login/verifica_admin.php';

header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit('Esta página permite apenas consultas.');
}

require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../include/clientes.php';

$escapar = static fn(mixed $valor): string => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$numero = static fn(mixed $valor): string => number_format((int) $valor, 0, ',', '.');
$url = static fn(array $parametros = []): string => htmlspecialchars(
    url_app('admin/clientes.php') . ($parametros ? '?' . http_build_query($parametros, '', '&', PHP_QUERY_RFC3986) : ''),
    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'
);
$pesquisa = is_string($_GET['pesquisa'] ?? '') ? trim($_GET['pesquisa'] ?? '') : '';
$pagina_lista = validar_inteiro_positivo($_GET['pagina'] ?? 1);
$pagina_pedidos = validar_inteiro_positivo($_GET['pagina_pedidos'] ?? 1);
$id_cliente = array_key_exists('id', $_GET) ? validar_inteiro_positivo($_GET['id']) : null;
$erro = '';
$dados = false;
if (!is_string($_GET['pesquisa'] ?? '') || str_contains($pesquisa, "\0")
    || preg_match('/\A.{0,255}\z/us', $pesquisa) !== 1
    || $pagina_lista === false || $pagina_pedidos === false || $id_cliente === false) {
    http_response_code(422);
    $erro = 'Confira a busca (até 255 caracteres), o ID do usuário e os números de página.';
} else {
    $dados = $id_cliente === null
        ? listar_clientes_admin($conexao, $pesquisa, $pagina_lista)
        : consultar_cliente_admin($conexao, $id_cliente, $pagina_pedidos);
    if ($dados === null) {
        http_response_code(404);
        $erro = 'Usuário não encontrado.';
    } elseif ($dados === false) {
        http_response_code(500);
        $erro = 'Não foi possível carregar os clientes. Tente novamente em instantes.';
    }
}
$contexto_lista = ['pesquisa' => $pesquisa, 'pagina' => $pagina_lista ?: 1];
$status_classe = ['pendente' => 'status-pendente', 'em producao' => 'status-producao',
    'retirada' => 'status-retirada', 'concluido' => 'status-concluido', 'cancelado' => 'status-cancelado'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $id_cliente === null ? 'Clientes' : 'Detalhes do cliente' ?> — ArtUP</title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(url_app('img/logos/logo.png'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= $escapar(url_app('css/estilo.css')) ?>">
    <link rel="stylesheet" href="<?= $escapar(url_app('admin/clientes.css')) ?>">
</head>
<body>
    <?php include __DIR__ . '/../include/header.php'; ?>
    <main id="conteudo" class="container secao-pagina pagina-clientes" tabindex="-1">
        <div class="cabecalho-secao">
            <div>
                <p class="sobretitulo">ArtUP / Administração</p>
                <h1><?= $id_cliente === null ? 'Clientes' : 'Detalhes do cliente' ?></h1>
                <p class="subtitulo"><?= $id_cliente === null
                    ? 'Consulte todos os usuários e acompanhe o relacionamento de cada um com a gráfica.'
                    : 'Dados de cadastro, resumo dos valores e histórico de pedidos.' ?></p>
            </div>
            <?php if ($id_cliente !== null): ?>
                <a class="botao botao-secundario" href="<?= $url($contexto_lista) ?>">← Voltar aos clientes</a>
            <?php endif; ?>
        </div>

        <?php if ($id_cliente === null): ?>
            <section class="barra-pesquisa" aria-label="Busca de clientes">
                <form method="get" action="<?= $url() ?>">
                    <label for="pesquisa">Nome, e-mail ou ID</label>
                    <input type="search" name="pesquisa" id="pesquisa" value="<?= $escapar($pesquisa) ?>" placeholder="Buscar usuário" maxlength="255">
                    <button class="botao" type="submit">Pesquisar</button>
                    <?php if ($pesquisa !== ''): ?><a class="botao botao-secundario" href="<?= $url() ?>">Limpar busca</a><?php endif; ?>
                </form>
            </section>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <p class="alerta alerta-erro" role="alert"><?= $escapar($erro) ?></p>
        <?php elseif ($id_cliente === null): ?>
            <p class="texto-secundario"><?= $numero($dados['total']) ?> usuário(s) encontrado(s). Inclui contas administrativas e usuários sem pedidos.</p>
            <?php if (!$dados['clientes']): ?>
                <section class="estado-vazio">
                    <h2>Nenhum usuário encontrado</h2>
                    <p><?= $pesquisa !== '' ? 'Tente outro nome, e-mail ou ID.' : 'Os usuários aparecerão aqui após o cadastro.' ?></p>
                </section>
            <?php else: ?>
                <div class="tabela-responsiva" tabindex="0" role="region" aria-label="Clientes e resumo de pedidos">
                    <table>
                        <caption>Contas cadastradas e valores de pedidos não cancelados</caption>
                        <thead><tr>
                            <th scope="col">ID</th><th scope="col">Usuário</th><th scope="col">Perfil</th>
                            <th scope="col" class="numerico">Pedidos</th><th scope="col" class="numerico">Em aberto</th>
                            <th scope="col" class="numerico">Valor não cancelado</th><th scope="col">Último pedido</th><th scope="col">Histórico</th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($dados['clientes'] as $linha): ?>
                                <tr>
                                    <th scope="row">#<?= $escapar($linha['id']) ?></th>
                                    <td class="cliente-identidade"><strong><?= $escapar($linha['nome']) ?></strong><span><?= $escapar($linha['email']) ?></span></td>
                                    <td><span class="badge <?= $linha['admin'] === true ? 'status-producao' : 'status-desconhecido' ?>"><?= $linha['admin'] === true ? 'Administrador' : 'Cliente' ?></span></td>
                                    <td class="numerico"><?= $numero($linha['total_pedidos']) ?></td>
                                    <td class="numerico"><?= $numero($linha['em_aberto']) ?></td>
                                    <td class="numerico"><?= $escapar(formatar_preco($linha['valor_nao_cancelado'])) ?></td>
                                    <td><?= $escapar($linha['ultimo_pedido'] ?? 'Sem pedidos') ?></td>
                                    <td><a class="botao botao-secundario botao-sm" href="<?= $url(['id' => $linha['id'], 'pesquisa' => $pesquisa, 'pagina' => $dados['pagina']]) ?>" aria-label="Ver histórico de <?= $escapar($linha['nome']) ?>">Ver detalhes</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <?php $cliente = $dados['cliente']; ?>
            <section class="cliente-cadastro" aria-labelledby="nome-cliente">
                <div><p class="sobretitulo">Usuário #<?= $escapar($cliente['id']) ?></p><h2 id="nome-cliente"><?= $escapar($cliente['nome']) ?></h2></div>
                <dl>
                    <div><dt>E-mail</dt><dd><?= $escapar($cliente['email']) ?></dd></div>
                    <div><dt>Perfil</dt><dd><?= $cliente['admin'] === true ? 'Administrador' : 'Cliente' ?></dd></div>
                    <div><dt>Último pedido</dt><dd><?= $escapar($cliente['ultimo_pedido'] ?? 'Sem pedidos') ?></dd></div>
                </dl>
            </section>
            <div class="cliente-indicadores" aria-label="Resumo do histórico do cliente">
                <article><h2>Pedidos registrados</h2><p><?= $numero($cliente['total_pedidos']) ?></p></article>
                <article><h2>Em aberto</h2><p><?= $numero($cliente['em_aberto']) ?></p></article>
                <article><h2>Entregues</h2><p><?= $numero($cliente['concluidos']) ?></p></article>
                <article><h2>Cancelados</h2><p><?= $numero($cliente['cancelados']) ?></p></article>
                <article class="cliente-destaque"><h2>Valor não cancelado</h2><p><?= $escapar(formatar_preco($cliente['valor_nao_cancelado'])) ?></p></article>
                <article><h2>Ticket médio</h2><p><?= $escapar(formatar_preco($cliente['ticket_medio'])) ?></p></article>
                <article><h2>Itens no carrinho</h2><p><?= $numero($cliente['itens_carrinho']) ?></p></article>
                <article><h2>Cancelamentos em análise</h2><p><?= $numero($cliente['cancelamentos_pendentes']) ?></p></article>
            </div>
            <?php if ((int) $cliente['sem_valor'] > 0): ?>
                <p class="alerta alerta-aviso" role="status"><?= $numero($cliente['sem_valor']) ?> pedido(s) sem valor registrado entram nas contagens, mas não nas somas e médias.</p>
            <?php endif; ?>
            <section aria-labelledby="titulo-historico">
                <div class="cabecalho-secao"><h2 id="titulo-historico">Histórico de pedidos</h2><span class="texto-secundario">Do mais recente ao mais antigo</span></div>
                <?php if (!$dados['pedidos']): ?>
                    <div class="estado-vazio"><h3>Este usuário ainda não confirmou pedidos</h3><p>Os itens no carrinho aparecem somente no indicador acima.</p></div>
                <?php else: ?>
                    <div class="tabela-responsiva" tabindex="0" role="region" aria-label="Histórico de pedidos deste cliente">
                        <table>
                            <caption>Pedidos do usuário #<?= $escapar($cliente['id']) ?>, incluindo cancelados</caption>
                            <thead><tr><th scope="col">Pedido / data</th><th scope="col">Produto</th><th scope="col">Status</th>
                                <th scope="col" class="numerico">Qtd.</th><th scope="col">Medidas / formato</th>
                                <th scope="col" class="numerico">Unitário</th><th scope="col" class="numerico">Total</th>
                                <th scope="col">Arte</th><th scope="col">Cancelamento</th><th scope="col">Gestão</th></tr></thead>
                            <tbody>
                                <?php foreach ($dados['pedidos'] as $pedido): ?>
                                    <tr>
                                        <th scope="row">#<?= $escapar($pedido['id_pedido']) ?><span class="cliente-data"><?= $escapar($pedido['data_formatada']) ?></span></th>
                                        <td><strong><?= $escapar($pedido['categoria']) ?></strong><?php if ($pedido['tipo'] !== null): ?><span class="cliente-data"><?= $escapar($pedido['tipo']) ?></span><?php endif; ?></td>
                                        <td><span class="badge <?= $status_classe[$pedido['status']] ?? 'status-desconhecido' ?>"><?= $escapar(STATUS_PEDIDOS[$pedido['status']] ?? $pedido['status']) ?></span></td>
                                        <td class="numerico"><?= $numero($pedido['quantidade']) ?></td>
                                        <td><?php if ($pedido['comprimento'] !== null && $pedido['largura'] !== null): ?><?= $escapar($pedido['comprimento']) ?> × <?= $escapar($pedido['largura']) ?> cm<?php else: ?><?= $escapar($pedido['tamanho'] ?? '—') ?><?php endif; ?><?php if ($pedido['grossura'] !== null): ?><span class="cliente-data"><?= $escapar($pedido['grossura']) ?> mm</span><?php endif; ?></td>
                                        <td class="numerico"><?= $escapar(formatar_preco($pedido['preco_unitario'])) ?></td>
                                        <td class="numerico"><?= $escapar(formatar_preco($pedido['valor_total'])) ?></td>
                                        <td><?php if ($pedido['arte_nome'] !== null): ?><a href="<?= $escapar(url_app('app/arte.php') . '?produto=' . $pedido['id_produto']) ?>"><?= $escapar($pedido['arte_nome']) ?></a><?php else: ?><span class="texto-secundario">Sem arte</span><?php endif; ?></td>
                                        <td><?= $escapar($pedido['cancelamento_status'] !== null ? (STATUS_CANCELAMENTO[$pedido['cancelamento_status']] ?? $pedido['cancelamento_status']) : 'Sem solicitação') ?></td>
                                        <td><a class="botao botao-secundario botao-sm" href="<?= $escapar(url_app('admin/pedidos.php') . '?pesquisa=' . $pedido['id_pedido']) ?>" aria-label="Gerenciar pedido <?= $escapar($pedido['id_pedido']) ?>">Gerenciar</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($erro === '' && $dados['paginas'] > 1): ?>
            <?php
            $chave_pagina = $id_cliente === null ? 'pagina' : 'pagina_pedidos';
            $contexto = $id_cliente === null ? ['pesquisa' => $pesquisa] : $contexto_lista + ['id' => $id_cliente];
            ?>
            <nav class="paginacao-clientes" aria-label="<?= $id_cliente === null ? 'Páginas de clientes' : 'Páginas de pedidos do cliente' ?>">
                <?php if ($dados['pagina'] > 1): ?><a class="botao botao-secundario" rel="prev" href="<?= $url(array_merge($contexto, [$chave_pagina => $dados['pagina'] - 1])) ?>">← Anterior</a><?php endif; ?>
                <p>Página <?= $numero($dados['pagina']) ?> de <?= $numero($dados['paginas']) ?></p>
                <?php if ($dados['pagina'] < $dados['paginas']): ?><a class="botao botao-secundario" rel="next" href="<?= $url(array_merge($contexto, [$chave_pagina => $dados['pagina'] + 1])) ?>">Próxima →</a><?php endif; ?>
            </nav>
        <?php endif; ?>
        <p class="nota-clientes">Os valores representam pedidos, não pagamentos recebidos. Cancelados ficam fora do valor não cancelado e do ticket médio. Pedidos excluídos deixam de compor o histórico; itens no carrinho não são pedidos confirmados.</p>
    </main>
    <?php include __DIR__ . '/../include/footer.php'; ?>
</body>
</html>
