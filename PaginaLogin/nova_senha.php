<?php
session_start();
date_default_timezone_set("America/Sao_Paulo");

require_once __DIR__ . "/../config/conexao.php";

$token = trim($_GET["token"] ?? "");

// Mensagem de erro vinda do processa_nova_senha.php (ex.: senhas diferentes).
$mensagemErro = $_SESSION["auth_error"] ?? "";
unset($_SESSION["auth_error"]);

// Estados possíveis: "ok", "sem_token", "invalido" ou "expirado".
$estado = "ok";

if ($token === "") {
    $estado = "sem_token";
} else {
    try {
        $stmt = $pdo->prepare(
            "SELECT EXPIRA_EM, UTILIZADO
             FROM recuperacao_senha
             WHERE TOKEN_HASH = :token
             LIMIT 1"
        );
        $stmt->execute([":token" => hash("sha256", $token)]);
        $registro = $stmt->fetch();

        if (!$registro || (int)$registro["UTILIZADO"] === 1) {
            $estado = "invalido";
        } elseif (strtotime($registro["EXPIRA_EM"]) < time()) {
            $estado = "expirado";
        }
    } catch (PDOException $erro) {
        error_log($erro->getMessage());
        $estado = "invalido";
    }
}

$mensagensLink = [
    "sem_token" => "Este link está incompleto. Abra o link que enviamos por e-mail ou solicite um novo.",
    "invalido"  => "Este link é inválido ou já foi utilizado. Cada link só pode ser usado uma vez.",
    "expirado"  => "Este link expirou. Por segurança, ele é válido por apenas 30 minutos."
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova senha - MeuSaldo</title>

    <link rel="stylesheet" href="./login.css">
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

        <?php if ($estado !== "ok"): ?>

            <h2>Link não é mais válido</h2>

            <div class="auth-message error">
                <?= htmlspecialchars($mensagensLink[$estado], ENT_QUOTES, "UTF-8") ?>
            </div>

            <p class="auth-description">
                Não se preocupe, é rápido resolver:
            </p>

            <ol class="auth-steps">
                <li>Clique em <strong>Solicitar novo link</strong>.</li>
                <li>Informe o e-mail da sua conta.</li>
                <li>Abra o e-mail novo (olhe também o spam) e use o link em até 30 minutos.</li>
            </ol>

            <a href="esqueci_senha.php" class="auth-button auth-button-link">
                Solicitar novo link
            </a>

            <div class="auth-footer">
                <a href="login.php">Voltar para o login</a>
            </div>

        <?php else: ?>

            <h2>Criar nova senha</h2>

            <p class="auth-description">
                Escolha uma nova senha para acessar sua conta.
            </p>

            <?php if (!empty($mensagemErro)): ?>
                <div class="auth-message error">
                    <?= htmlspecialchars($mensagemErro, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

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
                        autocomplete="new-password"
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
                        autocomplete="new-password"
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

        <?php endif; ?>

        </main>

    </div>

</body>
</html>