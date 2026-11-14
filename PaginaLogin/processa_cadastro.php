<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cadastro.php");
    exit;
}

require_once __DIR__ . "/../config/conexao.php";

$nome      = trim($_POST["nome"] ?? "");
$email     = trim($_POST["email"] ?? "");
$senha     = $_POST["senha"] ?? "";
$confirmar = $_POST["confirmar_senha"] ?? "";

function voltarComErro(string $msg): void {
    $_SESSION["auth_error"] = $msg;
    header("Location: cadastro.php");
    exit;
}

if ($nome === "" || $email === "" || $senha === "" || $confirmar === "") {
    voltarComErro("Preencha todos os campos.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    voltarComErro("Digite um e-mail válido.");
}
if (strlen($senha) < 8) {
    voltarComErro("A senha deve ter pelo menos 8 caracteres.");
}
if ($senha !== $confirmar) {
    voltarComErro("As senhas não são iguais.");
}

try {
    $stmt = $pdo->prepare("SELECT 1 FROM usuario WHERE EMAIL = :email LIMIT 1");
    $stmt->execute([":email" => $email]);

    if ($stmt->fetch()) {
        voltarComErro("Este e-mail já está cadastrado.");
    }

    $stmt = $pdo->prepare(
        "INSERT INTO usuario (NOME, EMAIL, SENHA_HASH, ATIVO)
         VALUES (:nome, :email, :hash, 1)"
    );
    $stmt->execute([
        ":nome"  => $nome,
        ":email" => $email,
        ":hash"  => password_hash($senha, PASSWORD_DEFAULT),
    ]);

    header("Location: login.php?cadastro=sucesso");
    exit;

} catch (PDOException $erro) {
    error_log($erro->getMessage());
    voltarComErro("Erro no servidor. Tente novamente.");
}