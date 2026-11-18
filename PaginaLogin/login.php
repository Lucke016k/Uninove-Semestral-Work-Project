<?php
session_start();

// Recupera mensagens enviadas pelo backend, caso existam.
$mensagemErro = $_SESSION["auth_error"] ?? "";
$mensagemSucesso = $_SESSION["auth_success"] ?? "";

unset($_SESSION["auth_error"], $_SESSION["auth_success"]);

// Mensagem de cadastro concluído.
if (isset($_GET["cadastro"]) && $_GET["cadastro"] === "sucesso") {
    $mensagemSucesso = "Cadastro realizado com sucesso! Faça login para continuar.";
}
if (isset($_GET["sessao"]) && $_GET["sessao"] === "expirada") {
    $mensagemErro = "Sua sessão expirou por inatividade. Faça login novamente.";
}
if (isset($_GET["senha"]) && $_GET["senha"] === "alterada") {
    $mensagemSucesso = "Senha alterada com sucesso! Faça login.";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Entrar - UniFinance</title>

    <!-- CSS principal do projeto -->
    <script src="../theme.js"></script>
    <link rel="stylesheet" href="../theme.css">
    <link rel="stylesheet" href="login.css">
    <link rel="icon" type="image/png" href="../img/favicon.png">
</head>

<body class="auth-page">

    <div class="auth-container">

        <!-- Identidade visual -->
        <header class="auth-brand">
            <img src="../img/unifinance_icone.png" alt="UniFinance" class="auth-logo">
            <h1>UniFinance</h1>
            <p>Seus gastos, seus objetivos.</p>
        </header>

        <!-- Cartão de login -->
        <main class="auth-card">

            <h2>Bem-vindo de volta!</h2>

            <p class="auth-description">
                Faça login para continuar organizando sua vida financeira.
            </p>

            <!-- Mensagens do sistema -->
            <?php if (!empty($mensagemErro)): ?>
                <div class="auth-message error">
                    <?= htmlspecialchars($mensagemErro, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensagemSucesso)): ?>
                <div class="auth-message success">
                    <?= htmlspecialchars($mensagemSucesso, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form action="processa_login.php" method="POST" class="auth-form">

                <!-- E-mail -->
                <div class="auth-field">
                    <label for="email">E-mail</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Digite seu e-mail"
                        maxlength="254"
                        autocomplete="email"
                        required
                    >
                </div>

                <!-- Senha -->
                <div class="auth-field">
                    <label for="senha">Senha</label>

                    <div class="auth-password">
                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="auth-show-password"
                            onclick="mostrarSenha('senha', this)"
                        >
                            Mostrar
                        </button>
                    </div>
                </div>

                <!-- Opções -->
                <div class="auth-options">

                    <label class="auth-remember">
                        <input
                            type="checkbox"
                            name="lembrar"
                            value="sim"
                        >
                        Lembrar de mim
                    </label>

                    <a href="esqueci_senha.php">
                        Esqueceu a senha?
                    </a>

                </div>

                <!-- Botão -->
                <button type="submit" class="auth-button">
                    Entrar na minha conta
                </button>

            </form>

            <div class="auth-divider">
                ou
            </div>

            <!-- Login Google -->
            <a href="google_login.php" class="auth-google">
    <img
        src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
        alt="Google"
        width="20"
        height="20"
    >
    Continuar com Google
</a>

            <!-- Link para cadastro -->
            <p class="auth-footer">
                Ainda não tem uma conta?
                <a href="cadastro.php">Criar conta</a>
            </p>

        </main>

    </div>

    <script>
    function mostrarSenha(id, botao) {
        const campo = document.getElementById(id);

        if (campo.type === "password") {
            campo.type = "text";
            botao.textContent = "Ocultar";
        } else {
            campo.type = "password";
            botao.textContent = "Mostrar";
        }
    }
</script>

</body>
</html>
