
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

    <title>Criar conta - UniFinance</title>

    <!-- CSS principal do projeto -->
    <link rel="stylesheet" href="login.css">
</head>



<body class="auth-page">



    <div class="auth-container">

        <!-- Identidade visual -->
        <header class="auth-brand">
            <div class="auth-brand-icon">
    <svg xmlns="http://www.w3.org/2000/svg"
         width="30"
         height="30"
         viewBox="0 0 24 24"
         fill="none"
         stroke="currentColor"
         stroke-width="1.8"
         stroke-linecap="round"
         stroke-linejoin="round">

        <rect x="3" y="5" width="18" height="15" rx="3"/>
        <path d="M3 9h18"/>
        <path d="M16 14h2"/>
        <path d="M7 5V4a2 2 0 0 1 2-2h8"/>
    </svg>
</div>

            <h1>UniFinance</h1>
            <p>Seus gastos, seus objetivos.</p>
        </header>

        <!-- Cartão de cadastro -->
        <main class="auth-card">

            <h2>Crie sua conta</h2>

            <p class="auth-description">
                Comece a organizar seu dinheiro e acompanhar seus objetivos.
            </p>

            <!-- Mensagem de erro -->
            <?php if (!empty($mensagemErro)): ?>
                <div class="auth-message error">
                    <?= htmlspecialchars($mensagemErro, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form
                action="processa_cadastro.php"
                method="POST"
                class="auth-form"
            >

                <!-- Nome -->
                <div class="auth-field">
                    <label for="nome">Nome completo</label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Digite seu nome"
                        maxlength="120"
                        autocomplete="name"
                        required
                    >
                </div>

                <!-- E-mail -->
                <div class="auth-field">
                    <label for="email">E-mail</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Digite seu melhor e-mail"
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
                            placeholder="Crie uma senha"
                            autocomplete="new-password"
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

                <!-- Confirmar senha -->
                <div class="auth-field">
                    <label for="confirmar_senha">Confirmar senha</label>

                    <div class="auth-password">
                        <input
                            type="password"
                            id="confirmar_senha"
                            name="confirmar_senha"
                            placeholder="Digite a senha novamente"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="auth-show-password"
                            onclick="mostrarSenha('confirmar_senha', this)"
                        >
                            Mostrar
                        </button>
                    </div>
                </div>

                <!-- Botão de cadastro -->
                <button type="submit" class="auth-button">
                    Criar minha conta
                </button>

            </form>

            <!-- Voltar para login -->
            <p class="auth-footer">
                Já possui uma conta?
                <a href="login.php">Fazer login</a>
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