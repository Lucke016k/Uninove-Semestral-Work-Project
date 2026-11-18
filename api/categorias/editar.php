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

// Recebe e valida os dados.
$idCategoria = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

$nome = trim($_POST['name'] ?? '');

if (!$idCategoria || $idCategoria <= 0) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'ID da categoria inválido.'
    ]);
}

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
    // A categoria precisa ser do usuário (as globais não podem ser alteradas).
    $stmt = $pdo->prepare("
        SELECT ID_CATEGORIA
        FROM categoria
        WHERE ID_CATEGORIA = :id
            AND ID_USUARIO = :usuario
            AND ATIVO = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $idCategoria,
        ':usuario' => $idUsuario
    ]);

    if (!$stmt->fetchColumn()) {
        responder(404, [
            'sucesso' => false,
            'erro' => 'Categoria não encontrada.'
        ]);
    }

    // Impede nome repetido em outra categoria.
    $stmt = $pdo->prepare("
        SELECT ID_CATEGORIA
        FROM categoria
        WHERE NOME = :nome
            AND ID_CATEGORIA <> :id
            AND (ID_USUARIO = :usuario OR ID_USUARIO IS NULL)
            AND TIPO IN ('DESPESA', 'AMBOS')
            AND ATIVO = 1
        LIMIT 1
    ");

    $stmt->execute([
        ':nome' => $nome,
        ':id' => $idCategoria,
        ':usuario' => $idUsuario
    ]);

    if ($stmt->fetchColumn()) {
        responder(409, [
            'sucesso' => false,
            'erro' => 'Já existe uma categoria com esse nome.'
        ]);
    }

    // Atualiza o nome. Os lançamentos acompanham pelo ID.
    $stmt = $pdo->prepare("
        UPDATE categoria
        SET NOME = :nome
        WHERE ID_CATEGORIA = :id
            AND ID_USUARIO = :usuario
    ");

    $stmt->execute([
        ':nome' => $nome,
        ':id' => $idCategoria,
        ':usuario' => $idUsuario
    ]);

    responder(200, [
        'sucesso' => true,
        'mensagem' => 'Categoria renomeada com sucesso!',
        'id' => $idCategoria
    ]);
} catch (PDOException $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível renomear a categoria.'
    ]);
}
