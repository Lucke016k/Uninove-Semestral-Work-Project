<?php
session_start();

// Recupera mensagens enviadas pelo backend.
$mensagemErro = $_SESSION["auth_error"] ?? "";

unset($_SESSION["auth_error"]);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha - UniFinance</title>

    <script src="../theme.js"></script>
    <link rel="stylesheet" href="../theme.css">
    <link rel="stylesheet" href="./login.css">
    <link rel="icon" type="image/png" href="../img/favicon.png">
</head>

<body class="auth-page">

    <div class="auth-container">

        <header class="auth-brand">
            <img src="../img/unifinance_icone.png" alt="UniFinance" class="auth-logo">
            <h1>UniFinance</h1>
            <p>Controle financeiro pessoal</p>
        </header>

        <main class="auth-card">

            <h2>Recuperar senha</h2>

            <?php if (!empty($mensagemErro)): ?>
                <div class="auth-message error">
                    <?= htmlspecialchars($mensagemErro, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <p class="auth-description">
                Informe seu e-mail para receber o link de recuperação da sua senha.
            </p>

            <form action="processa_recuperacao.php" method="POST" class="auth-form">

                <div class="auth-field">
                    <label for="email">E-mail</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Digite seu e-mail"
                        required
                    >
                </div>

                <button type="submit" class="auth-button">
                    Enviar link de recuperação
                </button>

            </form>

            <div class="auth-footer">
                <a href="login.php">Voltar para o login</a>
            </div>

        </main>

    </div>

</body>
</html>