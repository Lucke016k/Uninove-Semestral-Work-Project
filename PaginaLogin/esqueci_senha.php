<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperar senha - MeuSaldo</title>
</head>
<body>

    <h1>Recuperar senha</h1>

    <p>Informe seu e-mail para iniciar a recuperação.</p>

    <form action="processa_recuperacao.php" method="POST">

        <label for="email">E-mail:</label>

        <input type="email" id="email" name="email"
               placeholder="Digite seu e-mail" required>

        <button type="submit">Enviar</button>

    </form>

    <a href="login.php">Voltar para o login</a>

</body>
</html>