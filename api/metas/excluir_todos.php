<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/conexao.php';

function responder($status, $dados) {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

$idUsuario = $_SESSION['usuario']['id'] ?? null;

if (!$idUsuario) {
    responder(401, [
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);
}

try {
    $sql = "DELETE FROM meta WHERE ID_USUARIO = :usuario";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':usuario' => $idUsuario]);

    responder(200, [
        'sucesso' => true,
        'mensagem' => 'Metas excluídas com sucesso!',
        'quantidade' => $stmt->rowCount()
    ]);

} catch (PDOException $erro) {
    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível excluir as metas.'
    ]);
}
