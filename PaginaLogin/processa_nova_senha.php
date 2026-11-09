<?php

session_start();
date_default_timezone_set("America/Sao_Paulo");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/conexao.php";

$token = trim($_POST["token"] ?? "");
$senha = $_POST["senha"] ?? "";
$confirmar = $_POST["confirmar_senha"] ?? "";

if ($token === "" || $senha === "" || $confirmar === "") {
    exit("Preencha todos os campos.");
}

if (strlen($senha) < 8) {
    exit("A senha deve ter pelo menos 8 caracteres.");
}

if ($senha !== $confirmar) {
    exit("As senhas não são iguais.");
}

try {

    $tokenHash = hash("sha256", $token);

    $stmt = $pdo->prepare(
        "SELECT ID_RECUPERACAO, ID_USUARIO, EXPIRA_EM
         FROM recuperacao_senha
         WHERE TOKEN_HASH = :token
         AND UTILIZADO = 0
         LIMIT 1"
    );
    $stmt->execute([":token" => $tokenHash]);
    $registro = $stmt->fetch();

    if (!$registro || strtotime($registro["EXPIRA_EM"]) < time()) {
        exit("Link inválido ou expirado. Solicite um novo.");
    }

    $novoHash = password_hash($senha, PASSWORD_BCRYPT);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "UPDATE usuario SET SENHA_HASH = :hash WHERE ID_USUARIO = :id"
    );
    $stmt->execute([
        ":hash" => $novoHash,
        ":id" => $registro["ID_USUARIO"]
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
    exit("Não foi possível alterar a senha. Tente novamente.");
}