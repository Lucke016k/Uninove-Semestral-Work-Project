<?php

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cadastro.php");
    exit;
}

// Conexão com o banco
require_once __DIR__ . "/../config/conexao.php";

// Recebendo os dados
$nome = trim($_POST["nome"] ?? "");
$email = trim($_POST["email"] ?? "");
$senha = $_POST["senha"] ?? "";
$confirmar_senha = $_POST["confirmar_senha"] ?? "";

// Validação dos campos
if (
    $nome === "" ||
    $email === "" ||
    $senha === "" ||
    $confirmar_senha === ""
) {
    die("Preencha todos os campos.");

} elseif (mb_strlen($nome) > 120) {

    die("O nome deve possuir no máximo 120 caracteres.");

} elseif (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

    die("Digite um e-mail válido.");

} elseif (strlen($senha) < 6) {

    die("A senha deve possuir pelo menos 6 caracteres.");

} elseif ($senha !== $confirmar_senha) {

    die("As senhas não são iguais.");

} else {

    try {

        // Verificando se o e-mail já está cadastrado
        $sql = "SELECT ID_USUARIO FROM usuario WHERE EMAIL = :email";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":email" => $email
        ]);

        if ($stmt->fetch()) {

            die("Este e-mail já está cadastrado.");

        }

        // Gerando o hash da senha com BCRYPT
        $senha_hash = password_hash($senha, PASSWORD_BCRYPT);

        // Inserindo o usuário no banco
        $sql = "INSERT INTO usuario (NOME, EMAIL, SENHA_HASH)
                VALUES (:nome, :email, :senha_hash)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":nome" => $nome,
            ":email" => $email,
            ":senha_hash" => $senha_hash
        ]);

        // Cadastro realizado
        header("Location: login.php?cadastro=sucesso");
        exit;

    } catch (PDOException $erro) {

        error_log($erro->getMessage());

        // Trata também eventual duplicidade causada por duas
        // tentativas simultâneas de cadastro.
        if ($erro->getCode() === "23000") {
            die("Este e-mail já está cadastrado.");
        }

        die("Não foi possível realizar o cadastro. Tente novamente.");

    }

}

?>
