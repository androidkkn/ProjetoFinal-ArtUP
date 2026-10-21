<?php

const STATUS_CANCELAMENTO = ['pendente' => 'Em análise', 'aprovado' => 'Aprovado', 'recusado' => 'Recusado'];
const PEDIDOS_CANCELAVEIS = ['pendente', 'em producao'];

// Usada após remover um item do carrinho ou um pedido, dentro da transação do chamador.
// Os vínculos restantes preservam o produto e, por consequência, sua arte.
const SQL_EXCLUIR_PRODUTO_SEM_VINCULOS = 'DELETE FROM produto WHERE id = :produto
            AND NOT EXISTS (SELECT 1 FROM pedido WHERE id_produto = produto.id)
            AND NOT EXISTS (SELECT 1 FROM carrinho WHERE id_produto = produto.id)';

function solicitar_cancelamento_pedido($conexao, $id_pedido, $motivo = '')
{
    $id_pedido = validar_inteiro_positivo($id_pedido);
    $id_usuario = validar_inteiro_positivo($_SESSION['id'] ?? null);
    if ($id_pedido === false || $id_usuario === false || !is_string($motivo)
        || str_contains($motivo, "\0") || preg_match('/\A.{0,1000}\z/us', trim($motivo)) !== 1) {
        return false;
    }
    $transacao_iniciada = false;
    try {
        $conexao->beginTransaction();
        $transacao_iniciada = true;
        $stmt = $conexao->prepare("SELECT id FROM pedido WHERE id = :pedido AND id_usuario = :usuario
            AND status IN ('pendente', 'em producao') FOR UPDATE");
        $stmt->execute(['pedido' => $id_pedido, 'usuario' => $id_usuario]);
        if (!$stmt->fetchColumn()) {
            $conexao->rollBack();
            return false;
        }
        $stmt = $conexao->prepare("INSERT INTO solicitacoes_cancelamento (id_pedido, motivo)
            SELECT :pedido, :motivo WHERE NOT EXISTS
                (SELECT 1 FROM solicitacoes_cancelamento WHERE id_pedido = :consulta AND status = 'pendente')
            RETURNING id");
        $stmt->execute(['pedido' => $id_pedido, 'consulta' => $id_pedido, 'motivo' => trim($motivo)]);
        $salvo = $stmt->fetchColumn() !== false;
        $conexao->commit();
        return $salvo;
    } catch (PDOException $e) {
        if ($transacao_iniciada && $conexao->inTransaction()) {
            $conexao->rollBack();
        }
        error_log('Erro ao solicitar cancelamento: ' . $e->getCode());
        return false;
    }
}

function responder_cancelamento_pedido($conexao, $id_solicitacao, $decisao)
{
    $id_solicitacao = validar_inteiro_positivo($id_solicitacao);
    $id_admin = validar_inteiro_positivo($_SESSION['id'] ?? null);
    if (!usuario_e_admin() || $id_admin === false || $id_solicitacao === false
        || !in_array($decisao, ['aprovar', 'recusar'], true)) {
        return false;
    }
    $transacao_iniciada = false;
    try {
        $conexao->beginTransaction();
        $transacao_iniciada = true;
        $stmt = $conexao->prepare("SELECT id_pedido FROM solicitacoes_cancelamento
            WHERE id = :id AND status = 'pendente'");
        $stmt->execute(['id' => $id_solicitacao]);
        $id_pedido = $stmt->fetchColumn();
        if (!$id_pedido) {
            $conexao->rollBack();
            return false;
        }
        // Todas as decisões bloqueiam primeiro o pedido e depois a solicitação.
        $stmt = $conexao->prepare('SELECT status FROM pedido WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id_pedido]);
        $status_pedido = $stmt->fetchColumn();
        if ($status_pedido === false || ($decisao === 'aprovar' && !in_array($status_pedido, PEDIDOS_CANCELAVEIS, true))) {
            $conexao->rollBack();
            return false;
        }
        $stmt = $conexao->prepare("UPDATE solicitacoes_cancelamento
            SET status = :status, respondido_em = CURRENT_TIMESTAMP, id_administrador = :admin
            WHERE id = :id AND status = 'pendente'");
        $stmt->execute(['status' => $decisao === 'aprovar' ? 'aprovado' : 'recusado',
            'admin' => $id_admin, 'id' => $id_solicitacao]);
        if ($stmt->rowCount() !== 1) {
            $conexao->rollBack();
            return false;
        }
        if ($decisao === 'aprovar') {
            $stmt = $conexao->prepare("UPDATE pedido SET status = 'cancelado' WHERE id = :id");
            $stmt->execute(['id' => $id_pedido]);
        }
        $conexao->commit();
        return true;
    } catch (PDOException $e) {
        if ($transacao_iniciada && $conexao->inTransaction()) {
            $conexao->rollBack();
        }
        error_log('Erro ao responder cancelamento: ' . $e->getCode());
        return false;
    }
}

function excluir_pedido_admin($conexao, $id_pedido)
{
    $id_pedido = validar_inteiro_positivo($id_pedido);
    if (!usuario_e_admin() || $id_pedido === false) {
        return false;
    }
    $transacao_iniciada = false;
    try {
        $conexao->beginTransaction();
        $transacao_iniciada = true;
        // A edição de produtos usa a mesma ordem de bloqueio: produto, depois pedido.
        $stmt = $conexao->prepare("SELECT p.id FROM produto p JOIN pedido pe ON pe.id_produto = p.id
            WHERE pe.id = :pedido AND pe.status <> 'carrinho' FOR UPDATE OF p");
        $stmt->execute(['pedido' => $id_pedido]);
        $id_produto = $stmt->fetchColumn();
        if (!$id_produto) {
            $conexao->rollBack();
            return false;
        }
        $stmt = $conexao->prepare("DELETE FROM pedido WHERE id = :pedido AND id_produto = :produto
            AND status <> 'carrinho'");
        $stmt->execute(['pedido' => $id_pedido, 'produto' => $id_produto]);
        if ($stmt->rowCount() !== 1) {
            $conexao->rollBack();
            return false;
        }
        $stmt = $conexao->prepare(SQL_EXCLUIR_PRODUTO_SEM_VINCULOS);
        $stmt->execute(['produto' => $id_produto]);
        $conexao->commit();
        return true;
    } catch (PDOException $e) {
        if ($transacao_iniciada && $conexao->inTransaction()) {
            $conexao->rollBack();
        }
        error_log('Erro ao excluir pedido: ' . $e->getCode());
        return false;
    }
}
