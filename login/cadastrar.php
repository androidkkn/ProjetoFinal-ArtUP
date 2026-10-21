<?php
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../include/navegacao.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erroCadastro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : '';
    $email = normalizar_email($_POST['email'] ?? null);
    $senhaInformada = $_POST['senha'] ?? '';
    $confirmacaoSenha = $_POST['senha_confirmacao'] ?? null;

    if ($nome === '' || strlen($nome) > 255 || $email === false) {
        $erroCadastro = 'Informe nome e e-mail válidos.';
    } elseif (!validar_senha($senhaInformada)) {
        $erroCadastro = 'A senha deve conter pelo menos uma letra e um número.';
    } elseif (!is_string($confirmacaoSenha) || $senhaInformada !== $confirmacaoSenha) {
        $erroCadastro = 'A confirmação da senha deve ser igual à senha informada.';
    } elseif (cadastrar_user($conexao, $nome, $email, $senhaInformada, false, $erroCadastro)) {
        header('Location: login.php');
        exit();
    } else {
        $erroCadastro = $erroCadastro ?: 'Não foi possível cadastrar o usuário.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar conta — ArtUP</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(url_app('css/estilo.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>

<body>
    <?php include __DIR__ . '/../include/header.php'; ?>
    <main class="auth-wrapper">
        <section class="auth-card">
            <div class="auth-cabecalho">
                <p class="sobretitulo">ArtUP Gráfica</p>
                <h1>Crie sua conta</h1>
                <p>Cadastre-se para calcular orçamentos, enviar artes e fazer pedidos.</p>
            </div>

            <?php if ($erroCadastro !== ''): ?>
                <div class="alerta alerta-erro" role="alert">
                    <?= htmlspecialchars($erroCadastro, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="" method="post">
                <div class="campo-grupo">
                    <label for="nome">Nome completo</label>
                    <input type="text" name="nome" id="nome" placeholder="Digite seu nome completo" maxlength="255" autocomplete="name" required>
                </div>
                <div class="campo-grupo">
                    <label for="email">E-mail</label>
                    <input type="email" name="email" id="email" placeholder="seuemail@exemplo.com" maxlength="255" autocomplete="email" required>
                </div>
                <div class="campo-grupo">
                    <label for="senha">Senha</label>
                    <input type="password" name="senha" id="senha" placeholder="Crie uma senha segura" autocomplete="new-password" aria-describedby="regra-senha" required>
                    <p class="ajuda" id="regra-senha">Use pelo menos uma letra e um número. Espaços e símbolos são permitidos.</p>
                </div>
                <div class="campo-grupo">
                    <label for="senha_confirmacao">Confirme sua senha</label>
                    <input type="password" name="senha_confirmacao" id="senha_confirmacao" placeholder="Repita a senha digitada" autocomplete="new-password" required>
                </div>
                <button type="submit" class="botao" style="width: 100%; margin-top: 8px;">Cadastrar</button>
            </form>

            <div class="auth-rodape">
                Já possui uma conta? <a href="login.php">Faça login aqui</a>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/../include/footer.php'; ?>
</body>

</html>
