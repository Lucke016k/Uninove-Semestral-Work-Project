<?php
require_once __DIR__ . "/../../config/auth.php";
exigirLoginApi();

// Define o formato da resposta.
header('Content-Type: application/json; charset=utf-8');

// Importa a conexão.
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/verificar_usuario_ativo.php';

// Padroniza as respostas enviadas ao Javascript.
function responder($status, $dados)
{
    http_response_code($status);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// Recupera o usuário autenticado.
$idUsuario = $_SESSION['usuario']['id'] ?? null;

// Verifica a autenticação.
if (!$idUsuario) {
    responder(401, [
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);
}

// Recebe e valida o nome.
$nome = trim($_POST['name'] ?? '');

if ($nome === '') {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Informe o nome da categoria.'
    ]);
}

if (mb_strlen($nome) > 80) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'O nome da categoria pode ter no máximo 80 caracteres.'
    ]);
}

try {
    // Impede nomes repetidos (do usuário ou globais).
    $sqlExiste = "
        SELECT ID_CATEGORIA
        FROM categoria
        WHERE NOME = :nome
            AND (ID_USUARIO = :usuario OR ID_USUARIO IS NULL)
            AND TIPO IN ('DESPESA', 'AMBOS')
            AND ATIVO = 1
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sqlExiste);

    $stmt->execute([
        ':nome' => $nome,
        ':usuario' => $idUsuario
    ]);

    if ($stmt->fetchColumn()) {
        responder(409, [
            'sucesso' => false,
            'erro' => 'Já existe uma categoria com esse nome.'
        ]);
    }

    // Grava a nova categoria.
    $sql = "
        INSERT INTO categoria (ID_USUARIO, NOME, TIPO)
        VALUES (:usuario, :nome, 'DESPESA')
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':usuario' => $idUsuario,
        ':nome' => $nome
    ]);

    responder(201, [
        'sucesso' => true,
        'mensagem' => 'Categoria cadastrada com sucesso!',
        'id' => (int) $pdo->lastInsertId()
    ]);
} catch (PDOException $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível cadastrar a categoria.'
    ]);
}
