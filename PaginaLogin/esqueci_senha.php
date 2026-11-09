<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha - MeuSaldo</title>

    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-page">

    <div class="auth-container">

        <header class="auth-brand">
            <div class="auth-brand-icon">
    <svg
        xmlns="http://www.w3.org/2000/svg"
        width="30"
        height="30"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
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

            <h2>Recuperar senha</h2>

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