<?php

session_start();

// Aceita somente requisições POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

// Conexão com o banco
require_once __DIR__ . "/conexao.php";

// Função para exibir mensagens de erro
function erroLogin($mensagem) {
    echo "
    <!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='UTF-8'>
        <title>Erro no login</title>
    </head>
    <body>
        <h2>" . htmlspecialchars($mensagem) . "</h2>
        <a href='login.php'>Voltar para o login</a>
    </body>
    </html>";
    exit;
}

// Recebendo os dados
$email = trim($_POST["email"] ?? "");
$senha = $_POST["senha"] ?? "";

// Validação
if ($email === "" || $senha === "") {
    erroLogin("Preencha todos os campos.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    erroLogin("Digite um e-mail válido.");
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

    // Verificando se o usuário existe e se a senha está correta
    if (!$usuario || !password_verify($senha, $usuario["SENHA_HASH"])) {
        erroLogin("E-mail ou senha incorretos.");
    }

    // Criando uma nova identificação de sessão
    session_regenerate_id(true);

    // Armazenando os dados do usuário na sessão
    $_SESSION["usuario"] = [
        "id" => $usuario["ID_USUARIO"],
        "nome" => $usuario["NOME"],
        "email" => $usuario["EMAIL"]
    ];

    // Redirecionando para a página inicial
    header("Location: inicio.php");
    exit;

} catch (PDOException $erro) {

    error_log($erro->getMessage());

    erroLogin("Não foi possível realizar o login. Tente novamente.");

}

?>
