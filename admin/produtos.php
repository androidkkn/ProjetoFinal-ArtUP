<?php
require_once __DIR__ . '/../login/verifica_admin.php';
require_once __DIR__ . '/../include/functions.php';

$escapar = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$_SESSION['csrf_produtos_admin'] ??= bin2hex(random_bytes(32));
$erro = '';
$mensagem = $_SESSION['mensagem_produtos_admin'] ?? '';
unset($_SESSION['mensagem_produtos_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    $salvo = false;
    $secao = '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_produtos_admin'], $token)) {
        $erro = 'Recarregue a página e tente salvar novamente.';
    } else {
        switch ($_POST['acao'] ?? '') {
            case 'tipo':
                $salvo = editar_tipo_produto($conexao, $_POST['id_tipo'] ?? null,
                    $_POST['nome'] ?? null, $_POST['valor_base'] ?? null);
                $secao = 'tipos';
                break;
            case 'categoria':
                $salvo = editar_categoria_preco($conexao, $_POST['id_categoria'] ?? null,
                    $_POST['nome'] ?? null, $_POST['valor_metro'] ?? null);
                $secao = 'categorias';
                break;
            case 'item':
                if (($_POST['unidade_medidas'] ?? null) !== UNIDADE_MEDIDAS) {
                    $erro = 'As medidas agora são informadas em centímetros. Recarregue a página e confira as medidas.';
                    break;
                }
                $salvo = editar_item_pedido($conexao, $_POST['id_produto'] ?? null, $_POST['quantidade'] ?? null,
                    $_POST['largura'] ?? null, $_POST['comprimento'] ?? null,
                    $_POST['tamanho'] ?? null, $_POST['grossura'] ?? null);
                $secao = 'itens';
                break;
        }
        if ($salvo) {
            $_SESSION['mensagem_produtos_admin'] = 'Alterações salvas.';
            header('Location: produtos.php#' . $secao, true, 303);
            exit;
        }
        $erro = $erro ?: 'Não foi possível salvar. Confira os campos e tente novamente. Use preços não negativos com até duas casas decimais, quantidade e dimensões maiores que zero.';
    }
}
$produtos = listar_produtos_admin($conexao);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edição de Produtos — ArtUP</title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(url_app('img/logos/logo.png'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(url_app('css/estilo.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <?php include __DIR__ . '/../include/header.php' ?>
    <main id="conteudo" class="container secao-pagina" tabindex="-1">
        <div style="display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 20px;">
            <div>
                <p class="sobretitulo">ArtUP / Painel Administrativo</p>
                <h1>Tarifas e edição de produtos</h1>
                <p class="subtitulo">Ajuste os valores base por tipo, tarifas por metro quadrado e dados técnicos dos itens em pedidos.</p>
            </div>
            <a href="pedidos.php" class="botao botao-secundario botao-sm">Gerenciar pedidos &rarr;</a>
        </div>

        <nav class="nav-abas" aria-label="Seções dos produtos">
            <a href="#tipos">Tipos e valores base</a>
            <a href="#categorias">Categorias e preço por m²</a>
            <a href="#itens">Itens dos pedidos</a>
        </nav>

        <?php if ($mensagem): ?>
            <div class="alerta alerta-sucesso" role="status"><?= $escapar($mensagem) ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro" role="alert"><?= $escapar($erro) ?></div>
        <?php endif; ?>

        <?php if ($produtos === false): ?>
            <div class="alerta alerta-erro" role="alert">Não foi possível carregar os produtos. Tente novamente.</div>
        <?php else: ?>
            <section id="tipos" class="secao-admin">
                <h2>Tipos de produto e valores base</h2>
                <p>O valor base é somado ao cálculo de área (adesivos e banners) ou define o preço de uma folha A4 (papelaria).</p>
                <?php if (!$produtos['tipos']): ?>
                    <p class="texto-secundario">Nenhum tipo de produto cadastrado.</p>
                <?php else: ?>
                    <div class="grade-cards-admin">
                        <?php foreach ($produtos['tipos'] as $tipo): ?>
                            <form method="post">
                                <fieldset class="card-admin">
                                    <legend><?= $escapar($tipo['categoria']) ?> — Tipo #<?= $escapar($tipo['id']) ?></legend>
                                    <input type="hidden" name="acao" value="tipo">
                                    <input type="hidden" name="id_tipo" value="<?= $escapar($tipo['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_produtos_admin']) ?>">
                                    <div class="campo-admin">
                                        <label for="tipo_nome_<?= $escapar($tipo['id']) ?>">Nome do tipo / acabamento</label>
                                        <input type="text" name="nome" id="tipo_nome_<?= $escapar($tipo['id']) ?>" maxlength="255" value="<?= $escapar($tipo['nome']) ?>" required>
                                    </div>
                                    <div class="campo-admin">
                                        <label for="tipo_valor_<?= $escapar($tipo['id']) ?>">Valor base (R$)</label>
                                        <input type="text" inputmode="decimal" name="valor_base" id="tipo_valor_<?= $escapar($tipo['id']) ?>" value="<?= $escapar($tipo['valor_base']) ?>" required>
                                    </div>
                                    <button type="submit" class="botao botao-sm" style="width: 100%;">Salvar tipo</button>
                                </fieldset>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section id="categorias" class="secao-admin">
                <h2>Categorias e preço por m²</h2>
                <p>Deixe o preço por m² em branco quando a categoria não utilizar área. A papelaria calcula os formatos A1–A8 a partir do valor base da folha A4.</p>
                <?php if (!$produtos['categorias']): ?>
                    <p class="texto-secundario">Nenhuma categoria cadastrada.</p>
                <?php else: ?>
                    <div class="grade-cards-admin">
                        <?php foreach ($produtos['categorias'] as $categoria): ?>
                            <form method="post">
                                <fieldset class="card-admin">
                                    <legend>Categoria #<?= $escapar($categoria['id']) ?></legend>
                                    <input type="hidden" name="acao" value="categoria">
                                    <input type="hidden" name="id_categoria" value="<?= $escapar($categoria['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_produtos_admin']) ?>">
                                    <div class="campo-admin">
                                        <label for="cat_nome_<?= $escapar($categoria['id']) ?>">Nome da categoria</label>
                                        <input type="text" name="nome" id="cat_nome_<?= $escapar($categoria['id']) ?>" maxlength="255" value="<?= $escapar($categoria['nome']) ?>" required>
                                    </div>
                                    <div class="campo-admin">
                                        <label for="cat_metro_<?= $escapar($categoria['id']) ?>">Preço por m² (R$)</label>
                                        <input type="text" inputmode="decimal" name="valor_metro" id="cat_metro_<?= $escapar($categoria['id']) ?>" value="<?= $escapar($categoria['valormetro']) ?>" placeholder="Opcional">
                                    </div>
                                    <button type="submit" class="botao botao-sm" style="width: 100%;">Salvar categoria</button>
                                </fieldset>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section id="itens" class="secao-admin">
                <h2>Itens dos pedidos</h2>
                <p>Informe dimensões em centímetros (cm). Ao salvar um item, o preço do pedido é recalculado automaticamente com as tarifas vigentes.</p>
                <?php if (!$produtos['itens']): ?>
                    <p class="texto-secundario">Nenhum item de pedido encontrado.</p>
                <?php else: ?>
                    <div class="grade-cards-admin">
                        <?php foreach ($produtos['itens'] as $item): ?>
                            <form method="post">
                                <fieldset class="card-admin">
                                    <legend>Pedido #<?= $escapar($item['id_pedido']) ?> — Produto #<?= $escapar($item['id']) ?></legend>
                                    <p style="margin: 0 0 12px; font-size: 13px; color: var(--secundario);">
                                        <strong>Cliente:</strong> <?= $escapar($item['cliente']) ?><br>
                                        <strong>Serviço:</strong> <?= $escapar($item['categoria']) ?> <?= $escapar($item['tipo'] ?? '') ?>
                                    </p>
                                    <input type="hidden" name="acao" value="item">
                                    <input type="hidden" name="unidade_medidas" value="<?= $escapar(UNIDADE_MEDIDAS) ?>">
                                    <input type="hidden" name="id_produto" value="<?= $escapar($item['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_produtos_admin']) ?>">

                                    <div class="campo-admin">
                                        <label for="qtd_<?= $escapar($item['id']) ?>">Quantidade</label>
                                        <input type="number" name="quantidade" id="qtd_<?= $escapar($item['id']) ?>" min="1" step="1" value="<?= $escapar($item['quantidade']) ?>" required>
                                    </div>

                                    <?php if ($item['id_categoria'] === 2): ?>
                                        <div class="campo-admin">
                                            <label for="tam_<?= $escapar($item['id']) ?>">Tamanho do papel</label>
                                            <select name="tamanho" id="tam_<?= $escapar($item['id']) ?>">
                                                <?php foreach (range(1, 8) as $numero): ?>
                                                    <option value="A<?= $numero ?>" <?= $item['tamanho'] === 'A' . $numero ? 'selected' : '' ?>>A<?= $numero ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php else: ?>
                                        <div class="campo-admin">
                                            <label for="compr_<?= $escapar($item['id']) ?>">Comprimento (<?= $escapar(UNIDADE_MEDIDAS) ?>)</label>
                                            <input type="number" name="comprimento" id="compr_<?= $escapar($item['id']) ?>" min="0" step="any" value="<?= $escapar($item['comprimento']) ?>" required>
                                        </div>
                                        <div class="campo-admin">
                                            <label for="larg_<?= $escapar($item['id']) ?>">Largura (<?= $escapar(UNIDADE_MEDIDAS) ?>)</label>
                                            <input type="number" name="largura" id="larg_<?= $escapar($item['id']) ?>" min="0" step="any" value="<?= $escapar($item['largura']) ?>" required>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($item['id_categoria'] === 3): ?>
                                        <div class="campo-admin">
                                            <label for="gross_<?= $escapar($item['id']) ?>">Espessura</label>
                                            <select name="grossura" id="gross_<?= $escapar($item['id']) ?>">
                                                <?php foreach ([1, 2, 3] as $espessura): ?>
                                                    <option value="<?= $espessura ?>" <?= (string) $item['grossura'] === (string) $espessura ? 'selected' : '' ?>><?= $espessura ?> mm</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>

                                    <button type="submit" class="botao botao-sm" style="width: 100%; margin-top: 4px;">Salvar item e recalcular</button>
                                </fieldset>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/../include/footer.php' ?>
</body>
</html>
