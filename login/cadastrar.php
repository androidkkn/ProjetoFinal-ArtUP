<?php
require_once __DIR__ . '/../include/functions.php';

$erroCadastro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senhaInformada = $_POST['senha'] ?? '';

    if ($nome === '' || strlen($nome) > 255 || strlen($email) > 255
        || !filter_var($email, FILTER_VALIDATE_EMAIL) || $senhaInformada === '') {
        $erroCadastro = 'Preencha nome, email valido e senha.';
    } elseif (cadastrar_user($conexao, $nome, $email, $senhaInformada, false)) {
        header('Location: login.php');
        exit();
    } else {
        $erroCadastro = 'Nao foi possivel cadastrar o usuario.';
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
    <main>
        <section>
            <h1>Faça o cadastro para continuar</h1>
            <form action="" method="post">
                <div>
                    <input type="text" name="nome" id="nome" placeholder="Digite o seu Nome">
                </div>
                <div>
                    <input type="email" name="email" id="email" placeholder="Digite seu email">
                </div>
                <div>
                    <input type="password" name="senha" id="senha" placeholder="digite sua senha">
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
