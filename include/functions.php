<?php

require_once __DIR__ . '/../database/connect.php';

function formatar_preco($valor)
{
    if ($valor === null) {
        return 'Não calculado';
    }
    [$reais, $centavos] = explode('.', (string) $valor . '.00');
    $reais = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $reais);
    return 'R$ ' . $reais . ',' . str_pad(substr($centavos, 0, 2), 2, '0');
}

function validar_inteiro_positivo($valor)
{
    return filter_var($valor, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]);
}

function usuario_e_admin()
{
    return !empty($_SESSION['id']) && ($_SESSION['admin'] ?? false) === true;
}

function dimensao_valida($valor)
{
    if (!is_numeric($valor)) {
        return false;
    }

    $numero = (float) $valor;
    return is_finite($numero) && $numero > 0 && $numero <= 3.402823466e38;
}

function cproduto($conexao, $id_categoria, $quantidade, $largura, $comprimento, $tamanho, $grossura, $id_tipo)
{
    $id_usuario = validar_inteiro_positivo($_SESSION['id'] ?? null);
    $quantidade = validar_inteiro_positivo($quantidade);
    if ($id_usuario === false || $quantidade === false
        || !in_array($id_categoria, [1, 2, 3, 4], true)) {
        return false;
    }

    if ($id_categoria === 2 && !in_array($tamanho, ['A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8'], true)) {
        return false;
    }
    if ($id_categoria !== 2 && (!dimensao_valida($largura) || !dimensao_valida($comprimento))) {
        return false;
    }
    if ($id_categoria === 3 && (!is_scalar($grossura)
        || !in_array((string) $grossura, ['1', '2', '3'], true))) {
        return false;
    }

    $executar = static function ($sql, $parametros) use ($conexao) {
        $stmt = $conexao->prepare($sql);
        $stmt->execute($parametros);
        return $stmt;
    };

    $transacao_iniciada = false;
    try {
        // O tipo precisa pertencer à categoria escolhida; placas não têm tipo.
        if ($id_categoria !== 3) {
            $stmt = $executar('SELECT id FROM tipos_de_produto WHERE id = :id AND id_categoria = :categoria',
                ['id' => $id_tipo, 'categoria' => $id_categoria]);
            if (!$stmt->fetchColumn()) {
                return false;
            }
        } else {
            $id_tipo = null;
        }

        // O produto fica em verificação no carrinho; ainda não é um pedido.
        $conexao->beginTransaction();
        $transacao_iniciada = true;
        $id_produto = $executar('INSERT INTO produto
            (id_categoria, quantidade, largura, comprimento, tamanho, grossura, id_tipo)
            VALUES (:categoria, :quantidade, :largura, :comprimento, :tamanho, :grossura, :tipo)
            RETURNING id', [
            'categoria' => $id_categoria, 'quantidade' => $quantidade,
            'largura' => $largura, 'comprimento' => $comprimento,
            'tamanho' => $tamanho, 'grossura' => $grossura, 'tipo' => $id_tipo,
        ])->fetchColumn();

        $executar('INSERT INTO carrinho (id_usuario, id_produto) VALUES (:usuario, :produto)',
            ['usuario' => $id_usuario, 'produto' => $id_produto]);
        $conexao->commit();
        return true;
    } catch (PDOException $e) {
        if ($transacao_iniciada && $conexao->inTransaction()) {
            $conexao->rollBack();
        }
        error_log('Erro ao adicionar produto ao carrinho: ' . $e->getMessage());
        return false;
    }
}

function meus_pedidos($conexao, $id_usuario, $id_produto = null)
{
    $filtros = ['usuario' => $id_usuario];
    if ($id_produto !== null) {
        $filtros['produto'] = $id_produto;
    }
    foreach ($filtros as $nome => $valor) {
        $id = validar_inteiro_positivo($valor);
        if ($id === false) {
            return false;
        }
        $filtros[$nome] = $id;
    }

    $sql = "SELECT pe.id AS id_pedido, p.id AS id_produto,
                   to_char(pe.data, 'DD/MM/YYYY HH24:MI') AS data_formatada, pe.status,
                   pe.preco_unitario, pe.valor_total,
                   cat.nome AS categoria, t.nome AS tipo, p.quantidade,
                   p.largura, p.comprimento, p.tamanho, p.grossura
            FROM pedido pe
            JOIN produto p ON p.id = pe.id_produto
            JOIN categorias cat ON cat.id = p.id_categoria
            LEFT JOIN tipos_de_produto t ON t.id = p.id_tipo
            WHERE pe.id_usuario = :usuario AND pe.status <> 'carrinho'";
    if ($id_produto !== null) {
        $sql .= ' AND p.id = :produto';
    }
    $sql .= ' ORDER BY pe.data DESC, pe.id DESC';

    try {
        $stmt = $conexao->prepare($sql);
        foreach ($filtros as $nome => $id) {
            $stmt->bindValue(':' . $nome, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erro ao consultar pedidos: ' . $e->getMessage());
        return false;
    }
}

function carrinho($conexao, $id_usuario)
{
    $sql = 'SELECT c.id AS id_carrinho, p.id AS id_produto, cat.nome AS categoria,
                   t.nome AS tipo, p.quantidade, p.largura, p.comprimento, p.tamanho, p.grossura,
                   c.preco_unitario, c.valor_total,
                   CASE WHEN COUNT(*) OVER () = COUNT(c.valor_total) OVER ()
                        THEN SUM(c.valor_total) OVER () END AS total_carrinho
            FROM carrinho c
            JOIN produto p ON p.id = c.id_produto
            JOIN categorias cat ON cat.id = p.id_categoria
            LEFT JOIN tipos_de_produto t ON t.id = p.id_tipo
            WHERE c.id_usuario = :usuario
            ORDER BY c.id DESC';
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':usuario', (int) $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erro ao consultar carrinho: ' . $e->getMessage());
        return false;
    }
}

function excluir_produto_carrinho($conexao, $id_usuario, $id_carrinho)
{
    $id_usuario = validar_inteiro_positivo($id_usuario);
    $id_carrinho = validar_inteiro_positivo($id_carrinho);
    if ($id_usuario === false || $id_carrinho === false) {
        return false;
    }

    $transacao_iniciada = false;
    try {
        $conexao->beginTransaction();
        $transacao_iniciada = true;
        $stmt = $conexao->prepare('DELETE FROM carrinho WHERE id = :carrinho AND id_usuario = :usuario RETURNING id_produto');
        $stmt->execute(['carrinho' => $id_carrinho, 'usuario' => $id_usuario]);
        $id_produto = $stmt->fetchColumn();
        if (!$id_produto) {
            $conexao->rollBack();
            return false;
        }
        $stmt = $conexao->prepare('DELETE FROM produto WHERE id = :produto
            AND NOT EXISTS (SELECT 1 FROM pedido WHERE id_produto = produto.id)
            AND NOT EXISTS (SELECT 1 FROM carrinho WHERE id_produto = produto.id)');
        $stmt->execute(['produto' => $id_produto]);
        $conexao->commit();
        return true;
    } catch (PDOException $e) {
        if ($transacao_iniciada && $conexao->inTransaction()) {
            $conexao->rollBack();
        }
        error_log('Erro ao excluir produto do carrinho: ' . $e->getMessage());
        return false;
    }
}

function confirmar_pedidos($conexao, $id_usuario)
{
    $id_usuario = validar_inteiro_positivo($id_usuario);
    if ($id_usuario === false
        || (int) ($_SESSION['id'] ?? 0) !== $id_usuario) {
        return false;
    }
    try {
        // A transferência acontece em uma única operação: falhas mantêm o carrinho.
        $stmt = $conexao->prepare("WITH itens AS (
                DELETE FROM carrinho WHERE id_usuario = :usuario
                RETURNING id_usuario, id_produto, preco_unitario, valor_total
            ), pedidos AS (
                INSERT INTO pedido (id_usuario, id_produto, data, status, preco_unitario, valor_total)
                SELECT id_usuario, id_produto, CURRENT_TIMESTAMP, 'pendente', preco_unitario, valor_total
                FROM itens RETURNING id
            ) SELECT COUNT(*) FROM pedidos");
        $stmt->execute(['usuario' => $id_usuario]);
        return (int) $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log('Erro ao confirmar pedidos: ' . $e->getMessage());
        return false;
    }
}

// Funções de pedidos do administrador
const STATUS_PEDIDOS = [
    'pendente' => 'Pendente',
    'em producao' => 'Em produção',
    'concluido' => 'Concluído',
    'cancelado' => 'Cancelado',
];

function listar_pedidos_admin($conexao, $pesquisa = '')
{
    if (!usuario_e_admin() || !is_string($pesquisa)) {
        return false;
    }

    $pesquisa = trim($pesquisa);
    $sql = "SELECT pe.id AS id_pedido, u.nome AS cliente,
                   to_char(pe.data, 'DD/MM/YYYY HH24:MI') AS data_formatada, pe.status,
                   pe.preco_unitario, pe.valor_total,
                   cat.nome AS categoria, t.nome AS tipo, p.quantidade,
                   p.largura, p.comprimento, p.tamanho, p.grossura
            FROM pedido pe
            JOIN usuarios u ON u.id = pe.id_usuario
            JOIN produto p ON p.id = pe.id_produto
            JOIN categorias cat ON cat.id = p.id_categoria
            LEFT JOIN tipos_de_produto t ON t.id = p.id_tipo
            WHERE pe.status <> 'carrinho'";
    if ($pesquisa !== '') {
        $sql .= ' AND (u.nome ILIKE :nome OR pe.id = :id)';
    }
    $sql .= ' ORDER BY pe.data DESC, pe.id DESC';

    try {
        $stmt = $conexao->prepare($sql);
        if ($pesquisa !== '') {
            $id = validar_inteiro_positivo($pesquisa) ?: 0;
            $stmt->bindValue(':nome', '%' . $pesquisa . '%');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erro ao listar pedidos do administrador: ' . $e->getMessage());
        return false;
    }
}

function alterar_status_pedido($conexao, $id_pedido, $status)
{
    $id_pedido = validar_inteiro_positivo($id_pedido);
    if (!usuario_e_admin() || $id_pedido === false
        || !is_string($status) || !isset(STATUS_PEDIDOS[$status])) {
        return false;
    }

    try {
        $stmt = $conexao->prepare('UPDATE pedido SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id_pedido]);
        return $stmt->rowCount() === 1;
    } catch (PDOException $e) {
        error_log('Erro ao alterar status do pedido: ' . $e->getMessage());
        return false;
    }
}

// Funções de edição de produtos pelo administrador
function validar_preco($valor)
{
    if (!is_string($valor) && !is_int($valor) && !is_float($valor)) {
        return false;
    }
    $valor = str_replace(',', '.', trim((string) $valor));
    return preg_match('/\A\d{1,10}(?:\.\d{1,2})?\z/', $valor) ? $valor : false;
}

function listar_produtos_admin($conexao)
{
    if (!usuario_e_admin()) {
        return false;
    }
    try {
        return [
            'tipos' => $conexao->query('SELECT t.id, t.nome, t.valor_base, c.nome AS categoria
                FROM tipos_de_produto t JOIN categorias c ON c.id = t.id_categoria
                ORDER BY c.id, t.id')->fetchAll(PDO::FETCH_ASSOC),
            'categorias' => $conexao->query('SELECT c.id, c.nome, pr.valormetro
                FROM categorias c LEFT JOIN precodimensoes pr ON pr.id_categoria = c.id
                ORDER BY c.id')->fetchAll(PDO::FETCH_ASSOC),
            'itens' => $conexao->query('SELECT p.id, p.id_categoria, p.quantidade, p.largura,
                p.comprimento, p.tamanho, p.grossura, pe.id AS id_pedido,
                u.nome AS cliente, c.nome AS categoria, t.nome AS tipo
                FROM pedido pe JOIN usuarios u ON u.id = pe.id_usuario
                JOIN produto p ON p.id = pe.id_produto
                JOIN categorias c ON c.id = p.id_categoria
                LEFT JOIN tipos_de_produto t ON t.id = p.id_tipo
                WHERE pe.status <> \'carrinho\'
                ORDER BY pe.data DESC, pe.id DESC')->fetchAll(PDO::FETCH_ASSOC),
        ];
    } catch (PDOException $e) {
        error_log('Erro ao listar produtos do administrador: ' . $e->getMessage());
        return false;
    }
}

function editar_tipo_produto($conexao, $id_tipo, $nome, $valor_base)
{
    $id_tipo = validar_inteiro_positivo($id_tipo);
    $valor_base = validar_preco($valor_base);
    if (!usuario_e_admin() || $id_tipo === false || $valor_base === false
        || !is_string($nome) || !preg_match('/\A.{1,255}\z/us', trim($nome))) {
        return false;
    }
    try {
        $stmt = $conexao->prepare('UPDATE tipos_de_produto SET nome = :nome, valor_base = :valor WHERE id = :id');
        $stmt->execute(['nome' => trim($nome), 'valor' => $valor_base, 'id' => $id_tipo]);
        return $stmt->rowCount() === 1;
    } catch (PDOException $e) {
        error_log('Erro ao editar tipo de produto: ' . $e->getMessage());
        return false;
    }
}

function editar_categoria_preco($conexao, $id_categoria, $nome, $valor_metro)
{
    $id_categoria = validar_inteiro_positivo($id_categoria);
    $valor_metro = is_string($valor_metro) && trim($valor_metro) === '' ? null : validar_preco($valor_metro);
    if (!usuario_e_admin() || $id_categoria === false || $valor_metro === false
        || !is_string($nome) || !preg_match('/\A.{1,255}\z/us', trim($nome))) {
        return false;
    }
    $transacao_iniciada = false;
    try {
        $conexao->beginTransaction();
        $transacao_iniciada = true;
        $stmt = $conexao->prepare('UPDATE categorias SET nome = :nome WHERE id = :id');
        $stmt->execute(['nome' => trim($nome), 'id' => $id_categoria]);
        if ($stmt->rowCount() !== 1) {
            $conexao->rollBack();
            return false;
        }
        $stmt = $conexao->prepare('INSERT INTO precodimensoes (id_categoria, valormetro)
            VALUES (:categoria, :valor) ON CONFLICT (id_categoria)
            DO UPDATE SET valormetro = EXCLUDED.valormetro');
        $stmt->execute(['categoria' => $id_categoria, 'valor' => $valor_metro]);
        $conexao->commit();
        return true;
    } catch (PDOException $e) {
        if ($transacao_iniciada && $conexao->inTransaction()) {
            $conexao->rollBack();
        }
        error_log('Erro ao editar categoria e preco: ' . $e->getMessage());
        return false;
    }
}

function editar_item_pedido($conexao, $id_produto, $quantidade, $largura, $comprimento, $tamanho, $grossura)
{
    $id_produto = validar_inteiro_positivo($id_produto);
    $quantidade = validar_inteiro_positivo($quantidade);
    if (!usuario_e_admin() || $id_produto === false || $quantidade === false) {
        return false;
    }
    try {
        $stmt = $conexao->prepare('SELECT id_categoria FROM produto WHERE id = :id
            AND EXISTS (SELECT 1 FROM pedido WHERE id_produto = produto.id AND status <> \'carrinho\')');
        $stmt->execute(['id' => $id_produto]);
        $categoria = $stmt->fetchColumn();
        if (!in_array($categoria, [1, 2, 3, 4], true)) {
            return false;
        }
        if ($categoria === 2) {
            if (!in_array($tamanho, ['A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8'], true)) {
                return false;
            }
            $largura = $comprimento = null;
        } else {
            if (!dimensao_valida($largura) || !dimensao_valida($comprimento)) {
                return false;
            }
            $tamanho = null;
        }
        if ($categoria === 3 && (!is_scalar($grossura) || !in_array((string) $grossura, ['1', '2', '3'], true))) {
            return false;
        }
        $grossura = $categoria === 3 ? $grossura : null;
        $stmt = $conexao->prepare('UPDATE produto SET quantidade = :quantidade, largura = :largura,
            comprimento = :comprimento, tamanho = :tamanho, grossura = :grossura WHERE id = :id');
        $stmt->execute(['quantidade' => $quantidade, 'largura' => $largura, 'comprimento' => $comprimento,
            'tamanho' => $tamanho, 'grossura' => $grossura, 'id' => $id_produto]);
        return $stmt->rowCount() === 1;
    } catch (PDOException $e) {
        error_log('Erro ao editar item do pedido: ' . $e->getMessage());
        return false;
    }
}

// Funções de login

function normalizar_email($valor)
{
    if (!is_string($valor)) {
        return false;
    }
    $email = strtolower(trim($valor));
    return strlen($email) <= 255 && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

function validar_senha($senha)
{
    return is_string($senha) && !str_contains($senha, "\0")
        && preg_match('/\p{L}/u', $senha) === 1 && preg_match('/[0-9]/', $senha) === 1;
}

function cadastrar_user($conexao, $nome, $email, $senha, $admin, &$erro = null)
{
    $erro = '';
    $email = normalizar_email($email);
    if (!is_string($nome) || trim($nome) === '' || strlen($nome) > 255 || $email === false) {
        $erro = 'Informe nome e e-mail válidos.';
        return false;
    }
    if (!validar_senha($senha)) {
        $erro = 'A senha deve conter pelo menos uma letra e um número.';
        return false;
    }
    if (!defined('PASSWORD_ARGON2ID')) {
        error_log('O cadastro exige PHP com suporte a Argon2id para preservar senhas longas.');
        $erro = 'Não foi possível cadastrar o usuário. Contate o administrador.';
        return false;
    }
    $sql = "INSERT INTO usuarios (nome, email, senha, admin) VALUES (:nome, :email, :senha, :admin)";
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':nome', trim($nome));
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':senha', password_hash($senha, PASSWORD_ARGON2ID));
        $stmt->bindValue(':admin', (bool) $admin, PDO::PARAM_BOOL);
        return $stmt->execute();
    } catch (Throwable $e) {
        if ($e instanceof PDOException && (string) $e->getCode() === '23505') {
            $erro = 'Este e-mail já está cadastrado.';
            return false;
        }
        error_log('Erro ao cadastrar usuario: ' . $e->getMessage());
        $erro = 'Não foi possível cadastrar o usuário. Tente novamente.';
        return false;
    }
}

function consulta_user($conexao, $email)
{
    $email = normalizar_email($email);
    if ($email === false) {
        return false;
    }
    $sql = "SELECT id, email, senha, admin FROM usuarios WHERE lower(trim(email)) = :email";
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erro ao consultar usuario: ' . $e->getMessage());
        return false;
    }
}
