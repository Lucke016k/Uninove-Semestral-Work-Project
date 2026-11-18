<?php
session_start();
date_default_timezone_set("America/Sao_Paulo");

function voltarComErro(string $mensagem): void
{
    $_SESSION["auth_error"] = $mensagem;
    header("Location: esqueci_senha.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: esqueci_senha.php");
    exit;
}

$email = trim($_POST["email"] ?? "");

if ($email === "") {
    voltarComErro("Informe seu e-mail.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    voltarComErro("Digite um e-mail válido.");
}


require_once dirname(__DIR__) . "/config/conexao.php";
require_once dirname(__DIR__) . "/config/email.php";

try {


    $sql = "SELECT ID_USUARIO, NOME
            FROM usuario
            WHERE EMAIL = :email
            AND ATIVO = 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(["email" => $email]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

   

    $mensagem = "Se o e-mail estiver cadastrado, você receberá as instruções para recuperar sua senha.";

    if ($usuario) {

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash("sha256", $token);

        $expiracao = date("Y-m-d H:i:s", time() + 1800);

        $sql = "UPDATE recuperacao_senha
                SET UTILIZADO = 1
                WHERE ID_USUARIO = :id
                AND UTILIZADO = 0";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(["id" => $usuario["ID_USUARIO"]]);

        $sql = "INSERT INTO recuperacao_senha
                (ID_USUARIO, TOKEN_HASH, EXPIRA_EM)
                VALUES (:id, :token, :expiracao)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "id" => $usuario["ID_USUARIO"],
            "token" => $tokenHash,
            "expiracao" => $expiracao
        ]);

        $link = "http://localhost/Uninove-Semestral-Work-Project/PaginaLogin/nova_senha.php?token=" . urlencode($token);

        $enviado = enviarEmailRecuperacao(
            $email,
            $usuario["NOME"],
            $link
        );

        if (!$enviado) {
            error_log("Não foi possível enviar o e-mail de recuperação.");
            voltarComErro("Não foi possível enviar o e-mail. Tente novamente mais tarde.");
        }

    } else {

        voltarComErro("Não encontramos uma conta ativa com esse e-mail.");

    }

} catch (PDOException $e) {
      error_log($e->getMessage());
    voltarComErro("Não foi possível processar a solicitação. Tente novamente mais tarde.");

}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de senha - UniFinance</title>

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

            <h2>Solicitação recebida!</h2>

            <p class="auth-description">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </p>

            <div class="auth-footer">
                <a href="login.php">Voltar para o login</a>
            </div>

        </main>

    </div>

</body>
</html>