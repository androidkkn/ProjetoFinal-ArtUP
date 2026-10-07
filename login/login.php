<?php require_once __DIR__ . '/../../include/functions.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina de login</title>
</head>
<body>
    <header>
        <p>Para acessar a barra de navegação faça o seu login</p>
    </header>
    <main>
        <section>
            <h1>Faça o login para continuar</h1>
            <form action="" method="post">
                <div>
                    <input type="email" name="email" id="email" placeholder="Digite seu email">
                </div>
                <div>
                    <input type="password" name="senha" id="senha" placeholder="digite sua senha">
                </div>
                <div>
                    <input type="submit" value="Logar">
                </div>
            </form>
            <div>
                <a href="cadastrar.php">Caso não tenha cadastro clique aqui</a>
            </div>
        </section>
    </main>
</body>
</html>