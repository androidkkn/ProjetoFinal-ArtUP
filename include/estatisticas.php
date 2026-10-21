<?php

declare(strict_types=1);

function data_estatistica_valida(mixed $valor): bool
{
    if (!is_string($valor) || strlen($valor) !== 10 || !preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $valor)) {
        return false;
    }
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
    return $data !== false && $data->format('Y-m-d') === $valor && (int) $data->format('Y') >= 1;
}

/** @return array{inicio:string, fim:string, erro:string} */
function periodo_estatisticas(mixed $inicio, mixed $fim, ?DateTimeImmutable $hoje = null): array
{
    $hoje ??= new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo'));
    $inicio ??= $hoje->format('Y-m-01');
    $fim ??= $hoje->format('Y-m-d');
    $periodo = [
        'inicio' => is_string($inicio) ? trim($inicio) : '',
        'fim' => is_string($fim) ? trim($fim) : '',
        'erro' => '',
    ];
    if (!data_estatistica_valida($periodo['inicio']) || !data_estatistica_valida($periodo['fim'])) {
        $periodo['erro'] = 'Informe datas válidas para o início e o fim do período.';
    } elseif ($periodo['inicio'] > $periodo['fim']) {
        $periodo['erro'] = 'A data inicial deve ser anterior ou igual à data final.';
    } elseif (new DateTimeImmutable($periodo['fim']) > (new DateTimeImmutable($periodo['inicio']))->modify('+5 years')) {
        $periodo['erro'] = 'Selecione um período de até cinco anos.';
    }
    return $periodo;
}

/**
 * Uma única consulta mantém todos os agregados no mesmo snapshot.
 * Valores monetários em JSON são texto para evitar conversão de NUMERIC em float.
 */
function consultar_estatisticas_admin(PDO $conexao, string $inicio, string $fim): array|false
{
    if (!usuario_e_admin() || periodo_estatisticas($inicio, $fim)['erro'] !== '') {
        return false;
    }
    $sql = <<<'SQL'
        WITH parametros AS (
            SELECT CAST(:inicio AS date) AS inicio, CAST(:fim AS date) AS fim
        ), base AS (
            SELECT pe.id, pe.id_usuario, pe.status, pe.valor_total, p.quantidade,
                   cat.id AS id_categoria, cat.nome AS categoria,
                   date_trunc('month', pe.data)::date AS mes,
                   EXISTS (SELECT 1 FROM solicitacoes_cancelamento sc
                       WHERE sc.id_pedido = pe.id AND sc.status = 'pendente') AS cancelamento_pendente
            FROM pedido pe
            JOIN produto p ON p.id = pe.id_produto
            JOIN categorias cat ON cat.id = p.id_categoria
            CROSS JOIN parametros f
            WHERE pe.status <> 'carrinho' AND pe.data >= f.inicio
              AND pe.data < f.fim + INTERVAL '1 day'
        ), resumo AS (
            SELECT count(*) AS pedidos,
                   count(*) FILTER (WHERE status IN ('pendente', 'em producao')) AS em_aberto,
                   count(*) FILTER (WHERE status = 'concluido') AS concluidos,
                   count(*) FILTER (WHERE status = 'cancelado') AS cancelados,
                   count(DISTINCT id_usuario) AS clientes,
                   count(*) FILTER (WHERE cancelamento_pendente) AS cancelamentos_pendentes,
                   count(*) FILTER (WHERE valor_total IS NULL) AS sem_valor,
                   coalesce(sum(valor_total) FILTER (WHERE status <> 'cancelado'), 0)::text AS valor_nao_cancelado,
                   coalesce(sum(valor_total) FILTER (WHERE status = 'concluido'), 0)::text AS valor_concluido,
                   coalesce(sum(valor_total) FILTER (WHERE status = 'cancelado'), 0)::text AS valor_cancelado,
                   coalesce(round(avg(valor_total) FILTER (WHERE status <> 'cancelado'), 2), 0)::text AS ticket_medio
            FROM base
        ), por_status AS (
            SELECT status, count(*) AS pedidos,
                   coalesce(sum(valor_total), 0)::text AS valor,
                   count(*) FILTER (WHERE valor_total IS NULL) AS sem_valor
            FROM base GROUP BY status
        ), por_categoria AS (
            SELECT id_categoria, categoria, count(*) AS pedidos,
                   coalesce(sum(quantidade) FILTER (WHERE status <> 'cancelado'), 0) AS unidades,
                   coalesce(sum(valor_total) FILTER (WHERE status <> 'cancelado'), 0) AS valor
            FROM base GROUP BY id_categoria, categoria
        ), por_mes AS (
            SELECT mes, count(*) AS pedidos,
                   count(*) FILTER (WHERE status = 'cancelado') AS cancelados,
                   coalesce(sum(valor_total) FILTER (WHERE status <> 'cancelado'), 0) AS valor
            FROM base GROUP BY mes
        ), calendario AS (
            SELECT generate_series(date_trunc('month', f.inicio), date_trunc('month', f.fim),
                       INTERVAL '1 month')::date AS mes FROM parametros f
        )
        SELECT r.*,
               coalesce((SELECT jsonb_agg(jsonb_build_object('status', s.status, 'pedidos', s.pedidos,
                   'valor', s.valor, 'sem_valor', s.sem_valor) ORDER BY s.status) FROM por_status s), '[]'::jsonb) AS por_status,
               coalesce((SELECT jsonb_agg(jsonb_build_object('categoria', c.categoria, 'pedidos', c.pedidos,
                   'unidades', c.unidades, 'valor', c.valor::text) ORDER BY c.valor DESC, c.categoria, c.id_categoria)
                   FROM por_categoria c), '[]'::jsonb) AS por_categoria,
               (SELECT jsonb_agg(jsonb_build_object('mes', to_char(c.mes, 'YYYY-MM'),
                   'pedidos', coalesce(m.pedidos, 0), 'cancelados', coalesce(m.cancelados, 0),
                   'valor', coalesce(m.valor, 0)::text) ORDER BY c.mes)
                   FROM calendario c LEFT JOIN por_mes m ON m.mes = c.mes) AS por_mes
        FROM resumo r
        SQL;
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->execute(['inicio' => $inicio, 'fim' => $fim]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dados === false) {
            return false;
        }
        foreach (['por_status', 'por_categoria', 'por_mes'] as $campo) {
            $dados[$campo] = json_decode($dados[$campo], true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        }
        $status = [];
        foreach (STATUS_PEDIDOS as $codigo => $rotulo) {
            $status[$codigo] = ['status' => $codigo, 'pedidos' => 0, 'valor' => '0', 'sem_valor' => 0];
        }
        foreach ($dados['por_status'] as $linha) {
            $status[$linha['status']] = $linha;
        }
        $dados['por_status'] = array_values($status);
        return $dados;
    } catch (PDOException | JsonException $erro) {
        error_log('Erro ao consultar estatisticas: ' . $erro->getCode());
        return false;
    }
}
