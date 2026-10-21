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
    <title>Edição de Produtos</title>
</head>
<body>
    <?php include __DIR__ . '/../include/header.php' ?>
    <main>
        <h1>Edição de produtos</h1>
        <nav aria-label="Seções dos produtos">
            <a href="#tipos">Tipos e valores base</a>
            <a href="#categorias">Categorias e preço por m²</a>
            <a href="#itens">Itens dos pedidos</a>
        </nav>
        <?php if ($mensagem): ?><p role="status"><?= $escapar($mensagem) ?></p><?php endif; ?>
        <?php if ($erro): ?><p role="alert"><?= $escapar($erro) ?></p><?php endif; ?>
        <?php if ($produtos === false): ?>
            <p role="alert">Não foi possível carregar os produtos. Tente novamente.</p>
        <?php else: ?>
            <section id="tipos">
                <h2>Tipos de produto e valores base</h2>
                <?php if (!$produtos['tipos']): ?><p>Nenhum tipo de produto cadastrado.</p><?php endif; ?>
                <?php foreach ($produtos['tipos'] as $tipo): ?>
                    <form method="post">
                        <fieldset>
                            <legend><?= $escapar($tipo['categoria']) ?> — Tipo #<?= $escapar($tipo['id']) ?></legend>
                            <input type="hidden" name="acao" value="tipo">
                            <input type="hidden" name="id_tipo" value="<?= $escapar($tipo['id']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_produtos_admin']) ?>">
                            <label>Nome <input type="text" name="nome" maxlength="255" value="<?= $escapar($tipo['nome']) ?>" required></label>
                            <label>Valor base (R$) <input type="text" inputmode="decimal" name="valor_base" value="<?= $escapar($tipo['valor_base']) ?>" required></label>
                            <button type="submit">Salvar tipo</button>
                        </fieldset>
                    </form>
                <?php endforeach; ?>
            </section>
            <section id="categorias">
                <h2>Categorias e preço por m²</h2>
                <p>Deixe o preço em branco quando não houver preço por m² definido. A papelaria usa o valor base do tipo como preço de uma folha A4.</p>
                <?php if (!$produtos['categorias']): ?><p>Nenhuma categoria cadastrada.</p><?php endif; ?>
                <?php foreach ($produtos['categorias'] as $categoria): ?>
                    <form method="post">
                        <fieldset>
                            <legend>Categoria #<?= $escapar($categoria['id']) ?></legend>
                            <input type="hidden" name="acao" value="categoria">
                            <input type="hidden" name="id_categoria" value="<?= $escapar($categoria['id']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_produtos_admin']) ?>">
                            <label>Nome <input type="text" name="nome" maxlength="255" value="<?= $escapar($categoria['nome']) ?>" required></label>
                            <label>Preço por m² (R$) <input type="text" inputmode="decimal" name="valor_metro" value="<?= $escapar($categoria['valormetro']) ?>"></label>
                            <button type="submit">Salvar categoria</button>
                        </fieldset>
                    </form>
                <?php endforeach; ?>
            </section>
            <section id="itens">
                <h2>Itens dos pedidos</h2>
                <p>Informe comprimento e largura em centímetros (cm). Ao salvar um item, o preço será recalculado com os valores atuais da categoria e do tipo.</p>
                <?php if (!$produtos['itens']): ?><p>Nenhum item de pedido encontrado.</p><?php endif; ?>
                <?php foreach ($produtos['itens'] as $item): ?>
                    <form method="post">
                        <fieldset>
                            <legend>Pedido #<?= $escapar($item['id_pedido']) ?> — Produto #<?= $escapar($item['id']) ?> — <?= $escapar($item['cliente']) ?></legend>
                            <p><?= $escapar($item['categoria']) ?> <?= $escapar($item['tipo'] ?? '') ?></p>
                            <input type="hidden" name="acao" value="item">
                            <input type="hidden" name="unidade_medidas" value="<?= $escapar(UNIDADE_MEDIDAS) ?>">
                            <input type="hidden" name="id_produto" value="<?= $escapar($item['id']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_produtos_admin']) ?>">
                            <label>Quantidade <input type="number" name="quantidade" min="1" step="1" value="<?= $escapar($item['quantidade']) ?>" required></label>
                            <?php if ($item['id_categoria'] === 2): ?>
                                <label>Tamanho do papel
                                    <select name="tamanho">
                                        <?php foreach (range(1, 8) as $numero): ?>
                                            <option value="A<?= $numero ?>" <?= $item['tamanho'] === 'A' . $numero ? 'selected' : '' ?>>A<?= $numero ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            <?php else: ?>
                                <label>Comprimento (<?= $escapar(UNIDADE_MEDIDAS) ?>) <input type="number" name="comprimento" min="0" step="any" value="<?= $escapar($item['comprimento']) ?>" required></label>
                                <label>Largura (<?= $escapar(UNIDADE_MEDIDAS) ?>) <input type="number" name="largura" min="0" step="any" value="<?= $escapar($item['largura']) ?>" required></label>
                            <?php endif; ?>
                            <?php if ($item['id_categoria'] === 3): ?>
                                <label>Espessura
                                    <select name="grossura">
                                        <?php foreach ([1, 2, 3] as $espessura): ?>
                                            <option value="<?= $espessura ?>" <?= (string) $item['grossura'] === (string) $espessura ? 'selected' : '' ?>><?= $espessura ?> mm</option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            <?php endif; ?>
                            <button type="submit">Salvar item</button>
                        </fieldset>
                    </form>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/../include/footer.php' ?>
</body>
</html>
