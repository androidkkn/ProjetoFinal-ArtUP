<?php
if (!isset($id_categoria, $titulo)) {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../login/verifica_user.php';

$escapar = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$_SESSION['csrf_produto'] ??= bin2hex(random_bytes(32));
$dados = ['quantidade' => '1', 'larg' => '', 'compr' => '', 'tamanho' => 'A4', 'grossura' => '1', 'tipo' => ''];
$erro = '';
$mensagem_arte = '';
$previa = null;
$arte = null;
try {
    $stmt = $conexao->prepare('SELECT id, nome, valor_base FROM tipos_de_produto WHERE id_categoria = :categoria ORDER BY id');
    $stmt->execute(['categoria' => $id_categoria]);
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dados['tipo'] = (string) ($tipos[0]['id'] ?? '');
    $arte = consultar_rascunho_arte($conexao, $id_categoria);
} catch (PDOException $e) {
    error_log('Erro ao consultar tipos de produto: ' . $e->getMessage());
    $tipos = [];
    $erro = 'Não foi possível carregar os preços. Tente novamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    $limite_post = limite_php_bytes(ini_get('post_max_size'));
    if ($limite_post > 0 && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $limite_post) {
        $erro = 'A arte deve ter no máximo ' . limite_arte_formatado() . '. Selecione um arquivo menor.';
    }
    foreach ($dados as $campo => $padrao) {
        $valor = $_POST[$campo] ?? $padrao;
        if (!is_string($valor)) {
            $erro = 'Confira os campos do produto e tente novamente.';
            break;
        }
        $dados[$campo] = trim($valor);
    }
    $token = $_POST['csrf_token'] ?? null;
    if ($erro === '' && (!is_string($token) || !hash_equals($_SESSION['csrf_produto'], $token))) {
        $erro = 'Recarregue a página e tente novamente.';
    }
    if ($erro === '' && ($_POST['unidade_medidas'] ?? null) !== UNIDADE_MEDIDAS) {
        $erro = 'As medidas agora são informadas em centímetros. Recarregue a página e confira as medidas.';
    }
    $acao = $_POST['acao'] ?? 'calcular';
    if ($erro === '' && (!is_string($acao) || !in_array($acao, ['calcular', 'adicionar', 'remover_arte'], true))) {
        $erro = 'Escolha calcular a prévia, adicionar o produto ou remover a arte.';
    }
    if ($erro === '' && in_array($acao, ['adicionar', 'remover_arte'], true)) {
        $referencia = $_POST['arte_referencia'] ?? null;
        if (!is_string($referencia) || $referencia !== (string) ($arte['id'] ?? '')) {
            $erro = 'A arte foi alterada ou expirou. Confira o arquivo e calcule a prévia novamente antes de adicionar.';
        }
    }
    if ($erro === '' && $acao === 'remover_arte') {
        if (remover_rascunho_arte($conexao, $id_categoria, $erro)) {
            $mensagem_arte = 'Arte removida. Você pode escolher outra ou continuar sem arquivo.';
        }
    }
    if ($erro === '' && $acao !== 'remover_arte') {
        receber_arte_produto($conexao, $_FILES['arte'] ?? null, $id_categoria, $erro);
    }
    if ($erro === '' && $acao !== 'remover_arte') {
        $previa = calcular_preco_produto($conexao, $id_categoria, $dados['quantidade'],
            $dados['larg'], $dados['compr'], $dados['tamanho'], $dados['grossura'], $dados['tipo']);
        if ($previa === false) {
            $previa = null;
            $erro = 'Não foi possível calcular. Confira os campos e os preços cadastrados.';
        } elseif ($acao === 'adicionar') {
            $anterior = $_SESSION['previas_produto'][$id_categoria] ?? null;
            if ($anterior !== ['dados' => $dados, 'preco' => $previa]) {
                $erro = 'Os dados ou preços mudaram. Confira a nova prévia antes de adicionar.';
            } elseif (cproduto($conexao, $id_categoria, $dados['quantidade'],
                $id_categoria === 2 ? null : $dados['larg'],
                $id_categoria === 2 ? null : $dados['compr'],
                $id_categoria === 2 ? $dados['tamanho'] : null,
                $id_categoria === 3 ? $dados['grossura'] : null,
                $id_categoria === 3 ? null : $dados['tipo'], $previa,
                $_SESSION['artes_produto'][$id_categoria] ?? null)) {
                unset($_SESSION['previas_produto'][$id_categoria], $_SESSION['artes_produto'][$id_categoria]);
                header('Location: ../carrinho.php', true, 303);
                exit;
            } else {
                $erro = 'Não foi possível adicionar. Calcule a prévia novamente e tente outra vez.';
                $previa = null;
            }
        }
    }
    unset($_SESSION['previas_produto'][$id_categoria]);
    if ($previa !== null) {
        $_SESSION['previas_produto'][$id_categoria] = ['dados' => $dados, 'preco' => $previa];
    }
}
$arte = null;
try {
    $arte = consultar_rascunho_arte($conexao, $id_categoria);
} catch (PDOException $e) {
    error_log('Erro ao consultar rascunho de arte: ' . $e->getCode());
    $erro = 'Não foi possível carregar a arte enviada. Recarregue a página e tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $escapar($titulo) ?></title>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    <main>
        <h1><?= $escapar($titulo) ?></h1>
        <?php if ($id_categoria === 2): ?>
            <p>O valor base corresponde a uma folha A4. A3 custa o dobro, A5 custa a metade, e assim por diante.</p>
        <?php else: ?>
            <p>Informe as medidas em centímetros (cm). A área em m² é comprimento × largura ÷ 10.000. O preço por unidade é essa área multiplicada pelo preço por m²<?= $id_categoria === 3 ? '.' : ', mais o valor base do tipo ou acabamento.' ?></p>
            <?php if ($id_categoria === 3): ?><p>O preço por m² é o mesmo para as três espessuras.</p><?php endif; ?>
        <?php endif; ?>
        <?php if ($erro): ?><p role="alert"><?= $escapar($erro) ?></p><?php endif; ?>
        <?php if ($mensagem_arte): ?><p role="status"><?= $escapar($mensagem_arte) ?></p><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="unidade_medidas" value="<?= $escapar(UNIDADE_MEDIDAS) ?>">
            <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_produto']) ?>">
            <input type="hidden" name="arte_referencia" value="<?= $escapar($arte['id'] ?? '') ?>">
            <?php if ($id_categoria === 2): ?>
                <label for="tamanho">Tamanho do papel</label>
                <select name="tamanho" id="tamanho">
                    <?php foreach (range(1, 8) as $numero): ?>
                        <option value="A<?= $numero ?>" <?= $dados['tamanho'] === 'A' . $numero ? 'selected' : '' ?>>A<?= $numero ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <label for="compr">Comprimento (<?= $escapar(UNIDADE_MEDIDAS) ?>)</label>
                <input type="number" name="compr" id="compr" min="0" step="any" value="<?= $escapar($dados['compr']) ?>" required>
                <label for="larg">Largura (<?= $escapar(UNIDADE_MEDIDAS) ?>)</label>
                <input type="number" name="larg" id="larg" min="0" step="any" value="<?= $escapar($dados['larg']) ?>" required>
            <?php endif; ?>
            <br>
            <?php if ($id_categoria === 3): ?>
                <label for="grossura">Espessura</label>
                <select name="grossura" id="grossura">
                    <?php foreach ([1, 2, 3] as $espessura): ?>
                        <option value="<?= $espessura ?>" <?= $dados['grossura'] === (string) $espessura ? 'selected' : '' ?>><?= $espessura ?> mm</option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <label for="tipo"><?= $id_categoria === 2 ? 'Tipo de papel / preço de uma folha A4' : 'Tipo / acabamento / valor base' ?></label>
                <select name="tipo" id="tipo" required>
                    <?php foreach ($tipos as $tipo): ?>
                        <option value="<?= $escapar($tipo['id']) ?>" <?= $dados['tipo'] === (string) $tipo['id'] ? 'selected' : '' ?>><?= $escapar($tipo['nome']) ?> — <?= $escapar(formatar_preco($tipo['valor_base'])) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <br>
            <label for="quantidade">Quantidade</label>
            <input type="number" name="quantidade" id="quantidade" min="1" max="2147483647" step="1" value="<?= $escapar($dados['quantidade']) ?>" required>
            <br>
            <label for="arte">Arte (opcional)</label>
            <input type="hidden" name="MAX_FILE_SIZE" value="<?= limite_arte_bytes() ?>">
            <input type="file" name="arte" id="arte" accept=".png,.jpg,.jpeg,.webp,.pdf,image/png,image/jpeg,image/webp,application/pdf" aria-describedby="ajuda-arte">
            <p id="ajuda-arte">Envie PNG, JPG, WEBP ou PDF de até <?= $escapar(limite_arte_formatado()) ?>.</p>
            <p>Imagens podem ter até 40 megapixels. Para resoluções maiores, envie PDF.</p>
            <?php if ($arte !== null): ?>
                <p role="status">Arte recebida: <strong><?= $escapar($arte['nome_original']) ?></strong>. Ela será anexada ao produto. Selecione outro arquivo para substituí-la.</p>
                <button type="submit" name="acao" value="remover_arte" formnovalidate>Remover arte</button>
            <?php endif; ?>
            <br>
            <button type="submit" name="acao" value="calcular">Calcular prévia</button>
            <?php if ($previa !== null): ?>
                <p>Preço unitário: <strong><?= $escapar(formatar_preco($previa['preco_unitario'])) ?></strong></p>
                <p><?= $escapar($dados['quantidade']) ?> unidade(s) — Total do produto: <strong><?= $escapar(formatar_preco($previa['valor_total'])) ?></strong></p>
                <button type="submit" name="acao" value="adicionar">Adicionar ao carrinho</button>
            <?php endif; ?>
        </form>
    </main>
    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
