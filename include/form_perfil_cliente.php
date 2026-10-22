<?php
$perfil_proprio = (int) $cliente_perfil['id'] === (int) $_SESSION['id'];
$ultimo_admin = $cliente_perfil['admin'] === true && (int) $cliente_perfil['administradores_ativos'] < 2;
?>
    <form method="post" action="<?= $url($contexto_retorno) ?>" class="form-perfil-cliente">
        <input type="hidden" name="acao" value="alterar_perfil">
        <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_clientes_admin']) ?>">
        <input type="hidden" name="id_usuario" value="<?= $escapar($cliente_perfil['id']) ?>">
        <div class="campo-grupo">
            <label for="perfil-<?= $escapar($cliente_perfil['id']) ?>">Perfil de acesso</label>
            <select name="admin" id="perfil-<?= $escapar($cliente_perfil['id']) ?>" aria-label="Perfil de acesso de <?= $escapar($cliente_perfil['nome']) ?>">
                <option value="false" <?= $cliente_perfil['admin'] !== true ? 'selected' : '' ?> <?= $ultimo_admin ? 'disabled' : '' ?>>Cliente</option>
                <option value="true" <?= $cliente_perfil['admin'] === true ? 'selected' : '' ?>>Administrador</option>
            </select>
        </div>
        <button type="submit" class="botao botao-secundario botao-sm" aria-label="Salvar perfil de <?= $escapar($cliente_perfil['nome']) ?>">Salvar perfil</button>
    </form>
<?php if ($ultimo_admin): ?>
    <p class="texto-secundario perfil-proprio">Esta é a única conta administrativa. Promova outra conta antes de mudar este perfil para Cliente.</p>
<?php elseif ($perfil_proprio): ?>
    <p class="texto-secundario perfil-proprio">Esta é sua conta. Ao mudar para Cliente, você sairá da administração.</p>
<?php endif; ?>
