<?php
require_once __DIR__ . "/../../config/auth.php";
exigirLoginApi();

header('Content-Type: application/json; charset=utf-8');

function responder(int $status, array $dados): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION["usuario"]["id"])) {
    responder(401, ["sucesso" => false, "erro" => "Usuário não autenticado."]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(405, ["sucesso" => false, "erro" => "Método não permitido."]);
}

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . '/../../includes/verificar_usuario_ativo.php';

$idUsuario = $_SESSION["usuario"]["id"];
$nome      = trim($_POST["name"] ?? "");
$email     = trim($_POST["email"] ?? "");

if ($nome === "" || $email === "") {
    responder(422, ["sucesso" => false, "erro" => "Preencha nome e e-mail."]);
}

if (mb_strlen($nome) > 120) {
    responder(422, ["sucesso" => false, "erro" => "O nome deve ter no máximo 120 caracteres."]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responder(422, ["sucesso" => false, "erro" => "Digite um e-mail válido."]);
}

try {

    // Impede usar o e-mail de outra conta.
    $stmt = $pdo->prepare(
        "SELECT 1 FROM usuario
         WHERE EMAIL = :email AND ID_USUARIO <> :id
         LIMIT 1"
    );
    $stmt->execute([":email" => $email, ":id" => $idUsuario]);

    if ($stmt->fetch()) {
        responder(409, ["sucesso" => false, "erro" => "Este e-mail já está em uso por outra conta."]);
    }

    $stmt = $pdo->prepare(
        "UPDATE usuario
         SET NOME = :nome, EMAIL = :email
         WHERE ID_USUARIO = :id AND ATIVO = 1"
    );
    $stmt->execute([
        ":nome"  => $nome,
        ":email" => $email,
        ":id"    => $idUsuario
    ]);

    // Atualiza a sessão, senão o index.php continua mostrando o nome antigo após o F5.
    $_SESSION["usuario"]["nome"]  = $nome;
    $_SESSION["usuario"]["email"] = $email;

    responder(200, [
        "sucesso"  => true,
        "mensagem" => "Perfil atualizado.",
        "usuario"  => ["nome" => $nome, "email" => $email]
    ]);

} catch (PDOException $erro) {

    error_log($erro->getMessage());
    responder(500, ["sucesso" => false, "erro" => "Não foi possível atualizar o perfil."]);
}