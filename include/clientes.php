<?php

declare(strict_types=1);

const CLIENTES_POR_PAGINA = 25;

/** Altera somente o perfil, preservando pelo menos uma conta administrativa. */
function alterar_perfil_cliente_admin(PDO $conexao, mixed $id_usuario, mixed $administrador): string
{
    $id_admin = validar_inteiro_positivo($_SESSION['id'] ?? null);
    $id_usuario = validar_inteiro_positivo($id_usuario);
    if (!usuario_e_admin() || $id_admin === false) {
        return 'proibido';
    }
    if ($id_usuario === false || !in_array($administrador, ['true', 'false'], true)) {
        return 'invalido';
    }
    $transacao_iniciada = false;
    try {
        $conexao->beginTransaction();
        $transacao_iniciada = true;
        // Serializa mudanças de perfil pelos administradores ativos, sempre na mesma ordem.
        $stmt = $conexao->prepare('SELECT id, admin FROM usuarios
            WHERE admin IS TRUE OR id IN (:autor, :usuario) ORDER BY id FOR UPDATE');
        $stmt->execute(['autor' => $id_admin, 'usuario' => $id_usuario]);
        $contas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (($contas[$id_admin] ?? false) !== true) {
            $conexao->rollBack();
            $_SESSION['admin'] = false;
            return 'proibido';
        }
        if (!array_key_exists($id_usuario, $contas)) {
            $conexao->rollBack();
            return 'inexistente';
        }
        if ($administrador === 'false' && $contas[$id_usuario] === true
            && count(array_filter($contas, static fn($perfil) => $perfil === true)) < 2) {
            $conexao->rollBack();
            return 'ultimo_admin';
        }

        $stmt = $conexao->prepare('UPDATE usuarios SET admin = :admin WHERE id = :id');
        $stmt->bindValue(':admin', $administrador === 'true', PDO::PARAM_BOOL);
        $stmt->bindValue(':id', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        $conexao->commit();
        if ($id_usuario === $id_admin) {
            $_SESSION['admin'] = $administrador === 'true';
        }
        return 'salvo';
    } catch (PDOException $erro) {
        if ($transacao_iniciada && $conexao->inTransaction()) {
            $conexao->rollBack();
        }
        error_log('Erro ao alterar perfil do usuario: ' . $erro->getCode());
        return 'erro';
    }
}

// As duas agregações são independentes: pedidos e carrinho não multiplicam valores entre si.
// usuarios_selecionados contém somente os IDs da página ou do cliente em consulta.
const SQL_RESUMO_CLIENTES = <<<'SQL'
    , pedidos_cliente AS (
        SELECT pe.id_usuario, count(*) AS total_pedidos,
               count(*) FILTER (WHERE pe.status IN ('pendente', 'em producao')) AS em_aberto,
               count(*) FILTER (WHERE pe.status = 'concluido') AS concluidos,
               count(*) FILTER (WHERE pe.status = 'cancelado') AS cancelados,
               count(*) FILTER (WHERE pe.valor_total IS NULL) AS sem_valor,
               sum(pe.valor_total) FILTER (WHERE pe.status <> 'cancelado') AS valor_nao_cancelado,
               round(avg(pe.valor_total) FILTER (WHERE pe.status <> 'cancelado'), 2) AS ticket_medio,
               max(pe.data) AS ultimo_pedido,
               count(*) FILTER (WHERE EXISTS (
                   SELECT 1 FROM solicitacoes_cancelamento sc
                   WHERE sc.id_pedido = pe.id AND sc.status = 'pendente'
               )) AS cancelamentos_pendentes
        FROM pedido pe
        JOIN usuarios_selecionados u ON u.id = pe.id_usuario
        WHERE pe.status <> 'carrinho'
        GROUP BY pe.id_usuario
    ), carrinho_cliente AS (
        SELECT c.id_usuario, count(*) AS itens_carrinho
        FROM carrinho c JOIN usuarios_selecionados u ON u.id = c.id_usuario
        GROUP BY c.id_usuario
    )
    SELECT u.id, u.nome, u.email, u.admin,
           (SELECT count(*) FROM usuarios WHERE admin IS TRUE) AS administradores_ativos,
           coalesce(p.total_pedidos, 0) AS total_pedidos,
           coalesce(p.em_aberto, 0) AS em_aberto,
           coalesce(p.concluidos, 0) AS concluidos,
           coalesce(p.cancelados, 0) AS cancelados,
           coalesce(p.sem_valor, 0) AS sem_valor,
           coalesce(p.valor_nao_cancelado, 0)::text AS valor_nao_cancelado,
           coalesce(p.ticket_medio, 0)::text AS ticket_medio,
           to_char(p.ultimo_pedido, 'DD/MM/YYYY HH24:MI') AS ultimo_pedido,
           coalesce(p.cancelamentos_pendentes, 0) AS cancelamentos_pendentes,
           coalesce(c.itens_carrinho, 0) AS itens_carrinho
    FROM usuarios_selecionados u
    LEFT JOIN pedidos_cliente p ON p.id_usuario = u.id
    LEFT JOIN carrinho_cliente c ON c.id_usuario = u.id
    ORDER BY u.id DESC
    SQL;

/** @return array{clientes:array, total:int, pagina:int, paginas:int}|false */
function listar_clientes_admin(PDO $conexao, string $pesquisa = '', int $pagina = 1): array|false
{
    if (!usuario_e_admin() || $pagina < 1 || str_contains($pesquisa, "\0")
        || preg_match('/\A.{0,255}\z/us', $pesquisa) !== 1) {
        return false;
    }
    $pesquisa = trim($pesquisa);
    $filtro = '';
    $parametros = [];
    if ($pesquisa !== '') {
        // Trata %, _ e ! como texto da busca, mantendo todos os valores parametrizados.
        $termo = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $pesquisa) . '%';
        $filtro = " WHERE (u.nome ILIKE :nome ESCAPE '!' OR u.email ILIKE :email ESCAPE '!' OR u.id = :id)";
        $parametros = ['nome' => $termo, 'email' => $termo, 'id' => validar_inteiro_positivo($pesquisa) ?: 0];
    }
    try {
        $stmt = $conexao->prepare('SELECT count(*) FROM usuarios u' . $filtro);
        $stmt->execute($parametros);
        $total = (int) $stmt->fetchColumn();
        $paginas = max(1, (int) ceil($total / CLIENTES_POR_PAGINA));
        $pagina = min($pagina, $paginas);
        $sql = 'WITH usuarios_selecionados AS (
            SELECT u.id, u.nome, u.email, u.admin FROM usuarios u' . $filtro . '
            ORDER BY u.id DESC LIMIT :limite OFFSET :deslocamento
        )' . SQL_RESUMO_CLIENTES;
        $stmt = $conexao->prepare($sql);
        foreach ($parametros as $nome => $valor) {
            $stmt->bindValue(':' . $nome, $valor, $nome === 'id' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', CLIENTES_POR_PAGINA, PDO::PARAM_INT);
        $stmt->bindValue(':deslocamento', ($pagina - 1) * CLIENTES_POR_PAGINA, PDO::PARAM_INT);
        $stmt->execute();
        return ['clientes' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total,
            'pagina' => $pagina, 'paginas' => $paginas];
    } catch (PDOException $erro) {
        error_log('Erro ao listar clientes: ' . $erro->getCode());
        return false;
    }
}

/** Retorna null para usuário inexistente e false para falha ou acesso negado. */
function consultar_cliente_admin(PDO $conexao, int $id_usuario, int $pagina = 1): array|false|null
{
    if (!usuario_e_admin() || $id_usuario < 1 || $pagina < 1) {
        return false;
    }
    try {
        $stmt = $conexao->prepare('WITH usuarios_selecionados AS (
            SELECT id, nome, email, admin FROM usuarios WHERE id = :usuario
        )' . SQL_RESUMO_CLIENTES);
        $stmt->execute(['usuario' => $id_usuario]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente === false) {
            return null;
        }
        $paginas = max(1, (int) ceil((int) $cliente['total_pedidos'] / CLIENTES_POR_PAGINA));
        $pagina = min($pagina, $paginas);
        $stmt = $conexao->prepare("SELECT pe.id AS id_pedido, pe.status, pe.preco_unitario, pe.valor_total,
                to_char(pe.data, 'DD/MM/YYYY HH24:MI') AS data_formatada,
                p.id AS id_produto, p.quantidade, cat.nome AS categoria, t.nome AS tipo,
                p.largura::text::numeric * 100 AS largura, p.comprimento::text::numeric * 100 AS comprimento,
                p.tamanho, p.grossura, a.nome_original AS arte_nome, sc.status AS cancelamento_status
            FROM pedido pe
            JOIN produto p ON p.id = pe.id_produto
            JOIN categorias cat ON cat.id = p.id_categoria
            LEFT JOIN tipos_de_produto t ON t.id = p.id_tipo
            LEFT JOIN artes a ON a.id_produto = p.id
            LEFT JOIN LATERAL (SELECT status FROM solicitacoes_cancelamento
                WHERE id_pedido = pe.id ORDER BY id DESC LIMIT 1) sc ON TRUE
            WHERE pe.id_usuario = :usuario AND pe.status <> 'carrinho'
            ORDER BY pe.data DESC, pe.id DESC LIMIT :limite OFFSET :deslocamento");
        $stmt->bindValue(':usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', CLIENTES_POR_PAGINA, PDO::PARAM_INT);
        $stmt->bindValue(':deslocamento', ($pagina - 1) * CLIENTES_POR_PAGINA, PDO::PARAM_INT);
        $stmt->execute();
        return ['cliente' => $cliente, 'pedidos' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'pagina' => $pagina, 'paginas' => $paginas];
    } catch (PDOException $erro) {
        error_log('Erro ao consultar cliente: ' . $erro->getCode());
        return false;
    }
}
