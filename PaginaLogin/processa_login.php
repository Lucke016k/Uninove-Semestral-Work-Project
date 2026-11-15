<?php

session_start();

// Limpa os dados do usuário anterior
unset($_SESSION["usuario"]);

// Limpa possíveis erros antigos
unset($_SESSION["auth_error"]);

// Aceita somente requisições POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

function voltarComErro(string $mensagem): void
{
    $_SESSION["auth_error"] = $mensagem;
    header("Location: login.php");
    exit;
}

// Conexão central do projeto
require_once __DIR__ . "/../config/conexao.php";

// Recebendo os dados
$email = trim($_POST["email"] ?? "");
$senha = $_POST["senha"] ?? "";


// Validação dos campos
if ($email === "" || $senha === "") {
    $_SESSION["auth_error"] = "Preencha todos os campos.";
    header("Location: login.php");
    exit;
}

// Validação do e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION["auth_error"] = "Digite um e-mail válido.";
    header("Location: login.php");
    exit;
}

try {

    // Procurando o usuário no banco
    $sql = "SELECT ID_USUARIO, NOME, EMAIL, SENHA_HASH
            FROM usuario
            WHERE EMAIL = :email
            AND ATIVO = 1
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":email" => $email
    ]);

    $usuario = $stmt->fetch();

    // Verificando usuário e senha
    // Verificando se o usuário foi encontrado
if (!$usuario) {
    voltarComErro("Usuário ou senha incorretos");
}


// Verificando se a senha confere
if (!password_verify($senha, $usuario["SENHA_HASH"])) {
    voltarComErro("Usuário ou senha incorretos");
}
    // Proteção da sessão
    session_regenerate_id(true);

    // Armazenando dados na sessão
    $_SESSION["usuario"] = [
        "id" => $usuario["ID_USUARIO"],
        "nome" => $usuario["NOME"],
        "email" => $usuario["EMAIL"]
    ];

    // Redirecionando para o dashboard
    header("Location: ../index.php");
    exit;

} catch (PDOException $erro) {

    error_log($erro->getMessage());

    $_SESSION["auth_error"] = "Erro no servidor. Tente novamente.";
    header("Location: login.php");
    exit;
}