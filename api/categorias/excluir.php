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

// Recebe e valida o ID.
$idCategoria = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$idCategoria || $idCategoria <= 0) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'ID da categoria inválido.'
    ]);
}

try {
    // A categoria precisa ser do usuário (as globais não podem ser removidas).
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

    // Bloqueia a remoção se houver lançamentos ativos usando a categoria.
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM lancamento
        WHERE ID_CATEGORIA = :id
            AND ID_USUARIO = :usuario
            AND STATUS <> 'CANCELADO'
    ");

    $stmt->execute([
        ':id' => $idCategoria,
        ':usuario' => $idUsuario
    ]);

    if ((int) $stmt->fetchColumn() > 0) {
        responder(409, [
            'sucesso' => false,
            'erro' => 'Esta categoria possui lançamentos. Reatribua ou exclua esses lançamentos antes de removê-la.'
        ]);
    }

    // Desativa a categoria (preserva o histórico de lançamentos cancelados).
    $stmt = $pdo->prepare("
        UPDATE categoria
        SET ATIVO = 0
        WHERE ID_CATEGORIA = :id
            AND ID_USUARIO = :usuario
    ");

    $stmt->execute([
        ':id' => $idCategoria,
        ':usuario' => $idUsuario
    ]);

    responder(200, [
        'sucesso' => true,
        'mensagem' => 'Categoria removida com sucesso!'
    ]);
} catch (PDOException $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível remover a categoria.'
    ]);
}
