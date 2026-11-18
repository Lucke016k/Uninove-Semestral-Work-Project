<?php

require_once __DIR__ . "/../../config/auth.php";
exigirLoginApi();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/conexao.php';

function responder($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

// Identifica o usuário.
$idUsuario = $_SESSION['usuario']['id'] ?? null;

if (!$idUsuario) {
    responder(401, [
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);
}

try {

    // Cada usuário tem a sua própria meta de gasto.
    $stmt = $pdo->prepare("
        SELECT
            VALOR_LIMITE AS limite,
            DATA_INICIO AS inicio,
            DIAS AS dias
        FROM meta_gasto
        WHERE ID_USUARIO = :usuario
        LIMIT 1
    ");

    $stmt->execute([
        ':usuario' => $idUsuario
    ]);

    $meta = $stmt->fetch(PDO::FETCH_ASSOC);

    responder(200, [
        'sucesso' => true,
        // null quando o usuário ainda não definiu a meta.
        'meta' => $meta ?: null
    ]);

} catch (PDOException $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível consultar a meta de gasto.'
    ]);
}
