<?php
session_start();
date_default_timezone_set("America/Sao_Paulo");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/conexao.php";

$token     = trim($_POST["token"] ?? "");
$senha     = $_POST["senha"] ?? "";
$confirmar = $_POST["confirmar_senha"] ?? "";

// Volta para a tela de nova senha com a mensagem em vermelho.
// Se o token estiver inválido ou expirado, a própria tela mostra o aviso de link.
function voltarComErro(string $mensagem, string $token): void
{
    $_SESSION["auth_error"] = $mensagem;
    header("Location: nova_senha.php?token=" . urlencode($token));
    exit;
}

if ($token === "") {
    header("Location: nova_senha.php");
    exit;
}

if ($senha === "" || $confirmar === "") {
    voltarComErro("Preencha todos os campos.", $token);
}

if (strlen($senha) < 8) {
    voltarComErro("A senha deve ter pelo menos 8 caracteres.", $token);
}

if ($senha !== $confirmar) {
    voltarComErro("As senhas não são iguais.", $token);
}

try {

    $stmt = $pdo->prepare(
        "SELECT ID_RECUPERACAO, ID_USUARIO, EXPIRA_EM
         FROM recuperacao_senha
         WHERE TOKEN_HASH = :token
         AND UTILIZADO = 0
         LIMIT 1"
    );
    $stmt->execute([":token" => hash("sha256", $token)]);
    $registro = $stmt->fetch();

    // Link inválido, já usado ou expirado: a tela nova_senha.php explica e orienta.
    if (!$registro || strtotime($registro["EXPIRA_EM"]) < time()) {
        header("Location: nova_senha.php?token=" . urlencode($token));
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "UPDATE usuario SET SENHA_HASH = :hash WHERE ID_USUARIO = :id"
    );
    $stmt->execute([
        ":hash" => password_hash($senha, PASSWORD_DEFAULT),
        ":id"   => $registro["ID_USUARIO"]
    ]);

    $stmt = $pdo->prepare(
        "UPDATE recuperacao_senha SET UTILIZADO = 1
         WHERE ID_USUARIO = :id"
    );
    $stmt->execute([":id" => $registro["ID_USUARIO"]]);

    $pdo->commit();

    header("Location: login.php?senha=alterada");
    exit;

} catch (PDOException $erro) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($erro->getMessage());
    voltarComErro("Não foi possível alterar a senha. Tente novamente.", $token);
}