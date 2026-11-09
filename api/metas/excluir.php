<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/conexao.php';

function responder($status, $dados)
{
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

// Recebe o ID da meta.
$idMeta = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$idMeta || $idMeta <= 0) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'ID da meta inválido.'
    ]);
}

try {

    // Cancela a meta do usuário.
    $sql = "
        UPDATE meta
        SET STATUS = 'CANCELADA'
        WHERE ID_META = :id
          AND ID_USUARIO = :usuario
          AND STATUS <> 'CANCELADA'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $idMeta,
        ':usuario' => $idUsuario
    ]);

    if ($stmt->rowCount() === 0) {
        responder(404, [
            'sucesso' => false,
            'erro' => 'Meta não encontrada ou já cancelada.'
        ]);
    }

    responder(200, [
        'sucesso' => true,
        'mensagem' => 'Meta cancelada com sucesso!'
    ]);

} catch (PDOException $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível cancelar a meta.'
    ]);
}

?>