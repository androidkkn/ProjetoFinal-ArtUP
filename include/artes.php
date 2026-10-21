<?php

const LIMITE_ARTE_BYTES = 10 * 1024 * 1024;
const LIMITE_ARTE_PIXELS = 40_000_000;

function limite_php_bytes($valor)
{
    $valor = trim($valor);
    $fator = match (strtolower(substr($valor, -1))) {
        'g' => 1024 ** 3,
        'm' => 1024 ** 2,
        'k' => 1024,
        default => 1,
    };
    return (int) ((float) $valor * $fator);
}

function limite_arte_bytes()
{
    $limites = [LIMITE_ARTE_BYTES];
    $upload = limite_php_bytes(ini_get('upload_max_filesize'));
    $post = limite_php_bytes(ini_get('post_max_size'));
    if ($upload > 0) {
        $limites[] = $upload;
    }
    if ($post > 0) {
        $limites[] = max(1, $post - 65536); // Reserva espaço para os demais campos.
    }
    return min($limites);
}

function limite_arte_formatado()
{
    return rtrim(rtrim(number_format(limite_arte_bytes() / 1048576, 2, ',', ''), '0'), ',') . ' MB';
}

function validar_upload_arte($arquivo, &$erro)
{
    $erro = '';
    if ($arquivo === null) {
        return null;
    }
    if (!is_array($arquivo) || !is_int($arquivo['error'] ?? null)) {
        $erro = 'Envie apenas um arquivo de arte.';
        return false;
    }
    if ($arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (in_array($arquivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        $erro = 'A arte deve ter no máximo ' . limite_arte_formatado() . '.';
        return false;
    }
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $erro = 'O envio do arquivo não foi concluído. Selecione a arte e tente novamente.';
        return false;
    }
    if (!is_string($arquivo['name'] ?? null) || !is_string($arquivo['tmp_name'] ?? null)
        || !is_int($arquivo['size'] ?? null) || !is_uploaded_file($arquivo['tmp_name'])) {
        $erro = 'O arquivo enviado é inválido.';
        return false;
    }
    $tamanho = filesize($arquivo['tmp_name']);
    if ($tamanho === false || $tamanho < 1 || $tamanho > limite_arte_bytes() || $tamanho !== $arquivo['size']) {
        $erro = 'Envie uma arte não vazia de até ' . limite_arte_formatado() . '.';
        return false;
    }
    $nome = basename(str_replace('\\', '/', $arquivo['name']));
    if ($nome === '' || strlen($nome) > 255 || preg_match('//u', $nome) !== 1
        || preg_match('/[\x00-\x1f\x7f]/', $nome)) {
        $erro = 'Use um nome de arquivo válido com até 255 bytes.';
        return false;
    }
    if (!class_exists('finfo')) {
        $erro = 'O servidor não está preparado para receber artes. Contate a gráfica.';
        return false;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    $extensoes = ['image/png' => ['png'], 'image/jpeg' => ['jpg', 'jpeg'],
        'image/webp' => ['webp'], 'application/pdf' => ['pdf']];
    if (!isset($extensoes[$mime])
        || !in_array(strtolower(pathinfo($nome, PATHINFO_EXTENSION)), $extensoes[$mime], true)) {
        $erro = 'Envie PNG, JPG, WEBP ou PDF. O conteúdo deve corresponder à extensão do arquivo.';
        return false;
    }
    if ($mime !== 'application/pdf') {
        $imagem = @getimagesize($arquivo['tmp_name']);
        if ($imagem === false || ($imagem['mime'] ?? null) !== $mime) {
            $erro = 'A imagem enviada está inválida ou corrompida.';
            return false;
        }
        $largura = $imagem[0];
        $altura = $imagem[1];
        if ($largura < 1 || $altura < 1 || $largura > intdiv(LIMITE_ARTE_PIXELS, $altura)) {
            $erro = 'A imagem deve ter no máximo 40 megapixels. Envie uma versão menor ou um PDF.';
            return false;
        }
        // Reserva memória antes de decodificar uma imagem compactada.
        $memoria = limite_php_bytes(ini_get('memory_limit'));
        $estimativa = $largura * $altura * 8 + $tamanho * 2 + 16 * 1024 * 1024;
        if ($memoria > 0 && memory_get_usage(true) + $estimativa > $memoria) {
            $erro = 'A imagem é grande demais para ser processada. Envie uma versão menor ou um PDF.';
            return false;
        }
    }
    $conteudo = file_get_contents($arquivo['tmp_name']);
    if ($conteudo === false || strlen($conteudo) !== $tamanho) {
        $erro = 'Não foi possível ler a arte enviada.';
        return false;
    }
    if ($mime === 'application/pdf') {
        if (preg_match('/\A%PDF-[12]\.\d[\r\n]/', $conteudo) !== 1
            || preg_match('/%%EOF[\x00\x09\x0a\x0c\x0d\x20]*\z/', $conteudo) !== 1) {
            $erro = 'O PDF enviado está inválido ou incompleto. Exporte o arquivo novamente.';
            return false;
        }
    } else {
        if (!function_exists('imagecreatefromstring')) {
            $erro = 'O servidor não está preparado para validar imagens. Contate a gráfica.';
            return false;
        }
        $aviso = false;
        set_error_handler(static function () use (&$aviso) {
            $aviso = true;
            return true;
        });
        try {
            $decodificada = imagecreatefromstring($conteudo);
        } finally {
            restore_error_handler();
        }
        $valida = $decodificada !== false && !$aviso;
        unset($decodificada);
        if (!$valida) {
            $erro = 'A imagem enviada está inválida ou corrompida. Exporte o arquivo novamente.';
            return false;
        }
    }
    return ['nome_original' => $nome, 'mime' => $mime, 'tamanho' => $tamanho, 'conteudo' => $conteudo];
}

function receber_arte_produto($conexao, $arquivo, $id_categoria, &$erro)
{
    $arte = validar_upload_arte($arquivo, $erro);
    if ($arte === false) {
        return false;
    }
    if ($arte === null) {
        return true;
    }
    $id_usuario = validar_inteiro_positivo($_SESSION['id'] ?? null);
    if ($id_usuario === false || !in_array($id_categoria, [1, 2, 3, 4], true)) {
        $erro = 'Faça login novamente para enviar a arte.';
        return false;
    }
    $transacao_iniciada = false;
    try {
        $conexao->beginTransaction();
        $transacao_iniciada = true;
        // Somente rascunhos abandonados expiram; artes de pedidos são preservadas.
        $conexao->exec("DELETE FROM artes WHERE id_produto IS NULL
            AND criado_em < CURRENT_TIMESTAMP - INTERVAL '24 hours'");
        $stmt = $conexao->prepare('INSERT INTO artes (id_usuario, nome_original, mime, tamanho, conteudo)
            VALUES (:usuario, :nome, :mime, :tamanho, :conteudo) RETURNING id');
        $stmt->bindValue(':usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->bindValue(':nome', $arte['nome_original']);
        $stmt->bindValue(':mime', $arte['mime']);
        $stmt->bindValue(':tamanho', $arte['tamanho'], PDO::PARAM_INT);
        $stmt->bindValue(':conteudo', $arte['conteudo'], PDO::PARAM_LOB);
        $stmt->execute();
        $id_arte = (int) $stmt->fetchColumn();
        $anterior = validar_inteiro_positivo($_SESSION['artes_produto'][$id_categoria] ?? null);
        if ($anterior !== false) {
            $stmt = $conexao->prepare('DELETE FROM artes WHERE id = :id AND id_usuario = :usuario AND id_produto IS NULL');
            $stmt->execute(['id' => $anterior, 'usuario' => $id_usuario]);
        }
        $conexao->commit();
        $_SESSION['artes_produto'][$id_categoria] = $id_arte;
        return true;
    } catch (PDOException $e) {
        if ($transacao_iniciada && $conexao->inTransaction()) {
            $conexao->rollBack();
        }
        error_log('Erro ao salvar arte: ' . $e->getCode());
        $erro = 'Não foi possível salvar a arte. Selecione o arquivo e tente novamente.';
        return false;
    }
}

function consultar_rascunho_arte($conexao, $id_categoria)
{
    $id_arte = validar_inteiro_positivo($_SESSION['artes_produto'][$id_categoria] ?? null);
    if ($id_arte === false) {
        return null;
    }
    $stmt = $conexao->prepare("SELECT id, nome_original, tamanho FROM artes
        WHERE id = :id AND id_usuario = :usuario AND id_produto IS NULL
          AND criado_em >= CURRENT_TIMESTAMP - INTERVAL '24 hours'");
    $stmt->execute(['id' => $id_arte, 'usuario' => $_SESSION['id']]);
    $arte = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$arte) {
        unset($_SESSION['artes_produto'][$id_categoria]);
    }
    return $arte ?: null;
}

function remover_rascunho_arte($conexao, $id_categoria, &$erro)
{
    $id_usuario = validar_inteiro_positivo($_SESSION['id'] ?? null);
    $id_arte = validar_inteiro_positivo($_SESSION['artes_produto'][$id_categoria] ?? null);
    if ($id_usuario === false || $id_arte === false) {
        $erro = 'Não há uma arte selecionada para remover.';
        return false;
    }
    try {
        $stmt = $conexao->prepare('DELETE FROM artes WHERE id = :id AND id_usuario = :usuario AND id_produto IS NULL');
        $stmt->execute(['id' => $id_arte, 'usuario' => $id_usuario]);
        if ($stmt->rowCount() !== 1) {
            $erro = 'A arte foi alterada ou já foi usada. Recarregue a página.';
            return false;
        }
        unset($_SESSION['artes_produto'][$id_categoria]);
        return true;
    } catch (PDOException $e) {
        error_log('Erro ao remover rascunho de arte: ' . $e->getCode());
        $erro = 'Não foi possível remover a arte. Tente novamente.';
        return false;
    }
}

function vincular_arte_produto($conexao, $id_arte, $id_produto, $id_usuario)
{
    $id_arte = validar_inteiro_positivo($id_arte);
    if ($id_arte === false || (int) ($_SESSION['id'] ?? 0) !== $id_usuario) {
        return false;
    }
    $stmt = $conexao->prepare("UPDATE artes SET id_produto = :produto
        WHERE id = :id AND id_usuario = :usuario AND id_produto IS NULL
          AND criado_em >= CURRENT_TIMESTAMP - INTERVAL '24 hours'");
    $stmt->execute(['produto' => $id_produto, 'id' => $id_arte, 'usuario' => $id_usuario]);
    return $stmt->rowCount() === 1;
}

function consultar_arte_produto($conexao, $id_produto)
{
    $id_produto = validar_inteiro_positivo($id_produto);
    $id_usuario = validar_inteiro_positivo($_SESSION['id'] ?? null);
    if ($id_produto === false || $id_usuario === false) {
        return false;
    }
    $stmt = $conexao->prepare('SELECT a.nome_original, a.mime, a.tamanho, a.conteudo FROM artes a
        WHERE a.id_produto = :produto AND (:admin OR (a.id_usuario = :usuario
            AND (EXISTS (SELECT 1 FROM carrinho c WHERE c.id_produto = a.id_produto AND c.id_usuario = a.id_usuario)
                OR EXISTS (SELECT 1 FROM pedido p WHERE p.id_produto = a.id_produto AND p.id_usuario = a.id_usuario))))');
    $stmt->bindValue(':produto', $id_produto, PDO::PARAM_INT);
    $stmt->bindValue(':usuario', $id_usuario, PDO::PARAM_INT);
    $stmt->bindValue(':admin', usuario_e_admin(), PDO::PARAM_BOOL);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
