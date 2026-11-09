<?php
$token = trim($_GET["token"] ?? "");

if ($token === "") {
    exit("Link inválido ou sem token.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova senha - MeuSaldo</title>

    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-page">

    <div class="auth-container">

        <header class="auth-brand">
            <div class="auth-brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="30" height="30"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="15" rx="3"/>
                    <path d="M3 9h18"/>
                    <path d="M16 14h2"/>
                    <path d="M7 5V4a2 2 0 0 1 2-2h8"/>
                </svg>
            </div>

            <h1>MeuSaldo</h1>
            <p>Controle financeiro pessoal</p>
        </header>

        <main class="auth-card">

            <h2>Criar nova senha</h2>

            <p class="auth-description">
                Escolha uma nova senha para acessar sua conta.
            </p>

            <form action="processa_nova_senha.php"
                  method="POST"
                  class="auth-form">

                <input type="hidden" name="token"
                       value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                <div class="auth-field">
                    <label for="senha">Nova senha</label>

                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua nova senha"
                        required
                        minlength="8"
                    >
                </div>

                <div class="auth-field">
                    <label for="confirmar_senha">Confirmar nova senha</label>

                    <input
                        type="password"
                        id="confirmar_senha"
                        name="confirmar_senha"
                        placeholder="Digite novamente sua senha"
                        required
                        minlength="8"
                    >
                </div>

                <button type="submit" class="auth-button">
                    Alterar senha
                </button>

            </form>

            <div class="auth-footer">
                <a href="login.php">Voltar para o login</a>
            </div>

        </main>

    </div>

</body>
</html>