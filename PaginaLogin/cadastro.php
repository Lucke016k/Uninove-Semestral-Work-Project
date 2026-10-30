<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - MeuSaldo</title>
</head>
<body>

    <h1>Criar conta - MeuSaldo</h1>

    <form action="processa_cadastro.php" method="POST">

        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" required>

        <br><br>

        <label for="email">E-mail:</label>
        <input type="email" id="email" name="email" required>

        <br><br>

        <label for="senha">Senha:</label>
        <input type="password" id="senha" name="senha" required>

        <br><br>

        <label for="confirmar_senha">Confirmar senha:</label>
        <input type="password" id="confirmar_senha"
               name="confirmar_senha" required>

        <br><br>

        <button type="submit">Criar conta</button>

    </form>

    <a href="login.php">Já tenho uma conta</a>

</body>
</html>