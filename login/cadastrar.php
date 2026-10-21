<?php
require_once __DIR__ . '/../include/functions.php';
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
    <title>Pagina de login</title>
</head>

<body>
    <?php include __DIR__ . '/../include/header.php'; ?>
    <main>
        <section>
            <h1>Faça o cadastro para continuar</h1>
            <form action="" method="post">
                <div>
                    <input type="text" name="nome" id="nome" placeholder="Digite o seu Nome" maxlength="255" required>
                </div>
                <div>
                    <input type="email" name="email" id="email" placeholder="Digite seu email" maxlength="255" required>
                </div>
                <div>
                    <input type="password" name="senha" id="senha" placeholder="Digite sua senha" autocomplete="new-password" aria-describedby="regra-senha" required>
                    <p id="regra-senha">Use pelo menos uma letra e um número. Não há limite mínimo ou máximo de comprimento. Espaços e símbolos são permitidos.</p>
                </div>
                <div>
                    <input type="password" name="senha_confirmacao" id="senha_confirmacao" placeholder="Confirme sua senha" autocomplete="new-password" required>
                </div>
                <br>
                <div>
                    <input type="submit" value="Cadastrar">
                </div>
            </form>
            <div>
                <a href="login.php">Caso já tenha cadastro clique aqui</a>
            </div>
        </section>
        <?php if ($erroCadastro !== ''): ?>
            <p><?= htmlspecialchars($erroCadastro, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </main>
</body>

</html>
