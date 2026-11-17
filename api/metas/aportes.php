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

    // Lista os aportes do usuário (metas canceladas não entram).
    $sql = "
        SELECT
            a.ID_APORTE AS id,
            a.ID_META AS goalId,
            m.TITULO AS goalName,
            m.ICONE AS goalIcon,
            a.VALOR AS value,
            a.ORIGEM AS source,
            a.DATA_APORTE AS date
        FROM aporte_meta a
        INNER JOIN meta m
            ON m.ID_META = a.ID_META
        WHERE a.ID_USUARIO = :usuario
          AND m.STATUS <> 'CANCELADA'
        ORDER BY a.DATA_APORTE DESC, a.ID_APORTE DESC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':usuario' => $idUsuario
    ]);

    responder(200, [
        'sucesso' => true,
        'aportes' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (PDOException $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível consultar os aportes.'
    ]);
}
