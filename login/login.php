<?php
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../include/navegacao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$erroLogin = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = normalizar_email($_POST['email'] ?? null);
    $senhaInformada = $_POST['senha'] ?? '';
    $usuario = $email !== false && is_string($senhaInformada)
        ? consulta_user($conexao, $email)
        : false;

    $senhaValida = false;
    if ($usuario && is_string($senhaInformada) && $senhaInformada !== '') {
        // As contas antigas podem ter sido cadastradas sem hash.
        $senhaSemHash = password_get_info($usuario['senha'])['algoName'] === 'unknown';
        $senhaValida = $senhaSemHash
            ? hash_equals($usuario['senha'], $senhaInformada)
            : password_verify($senhaInformada, $usuario['senha']);
    }

    if ($senhaValida) {
        session_regenerate_id(true);
        $_SESSION['id'] = $usuario['id'];
        $_SESSION['admin'] = (bool) $usuario['admin'];
        header('Location: ' . url_app('index.php'));
        exit();
    }

    $erroLogin = 'Usuario ou senha invalidos!';
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — ArtUP</title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(url_app('img/logos/logo.png'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(url_app('css/estilo.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <?php include __DIR__ . '/../include/header.php'; ?>
    <main id="conteudo" class="auth-wrapper" tabindex="-1">
        <section class="auth-card">
            <div class="auth-cabecalho">
                <p class="sobretitulo">ArtUP Gráfica</p>
                <h1>Faça seu login</h1>
                <p>Entre com sua conta para acessar catálogo, pedidos e orçamentos.</p>
            </div>

            <?php if ($erroLogin !== ''): ?>
                <div class="alerta alerta-erro" role="alert">
                    <?= htmlspecialchars($erroLogin, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="" method="post">
                <div class="campo-grupo">
                    <label for="email">E-mail</label>
                    <input type="email" name="email" id="email" placeholder="seuemail@exemplo.com" autocomplete="email" required>
                </div>
                <div class="campo-grupo">
                    <label for="senha">Senha</label>
                    <input type="password" name="senha" id="senha" placeholder="Digite sua senha" autocomplete="current-password" required>
                </div>
                <button type="submit" class="botao auth-enviar">Entrar</button>
            </form>

            <div class="auth-rodape">
                Não tem uma conta? <a href="cadastrar.php">Cadastre-se aqui</a>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/../include/footer.php'; ?>
</body>
</html>
