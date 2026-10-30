<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MeuSaldo</title>
</head>
<body>

    <header>
        <h1>MeuSaldo</h1>
        <p>Seus gastos, seus objetivos.</p>
    </header>

    <main>

        <h2>Bem-vindo de volta!</h2>
        <p>Faça login para continuar.</p>

        <form action="processa_login.php" method="POST">

            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email"
                   placeholder="Digite seu e-mail" required>

            <br><br>

            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha"
                   placeholder="Digite sua senha" required>

            <button type="button" onclick="mostrarSenha()">
                Mostrar senha
            </button>

            <br><br>

            <label>
                <input type="checkbox" name="lembrar" value="sim">
                Lembrar de mim
            </label>

            <a href="esqueci_senha.php">Esqueceu a senha?</a>

            <br><br>

            <button type="submit">Entrar</button>

        </form>

        <hr>

        <button type="button" onclick="loginGoogle()">
            Entrar com Google
        </button>

        <p>
            Ainda não tem uma conta?
            <a href="cadastro.php">Cadastre-se</a>
        </p>

    </main>

    <script>
        function mostrarSenha() {
            const senha = document.getElementById("senha");

            if (senha.type === "password") {
                senha.type = "text";
            } else {
                senha.type = "password";
            }
        }

        function loginGoogle() {
            alert("A integração com o Google será disponibilizada posteriormente.");
        }
    </script>

</body>
</html>