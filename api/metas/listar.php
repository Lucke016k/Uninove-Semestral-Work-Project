<?php

require_once __DIR__ . "/../../config/auth.php";
exigirLoginApi();


header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/verificar_usuario_ativo.php';

// Padroniza as respostas.
function responder($status, $dados)
{
    http_response_code($status);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
    );

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

    // Consulta as metas do usuário.
    $sql = "
        SELECT
            ID_META AS id,
            TITULO AS name,
            ICONE AS icon,
            VALOR_OBJETIVO AS target,
            VALOR_ACUMULADO AS saved,
            PRAZO AS due,
            STATUS AS status

        FROM meta

        WHERE ID_USUARIO = :usuario
          AND STATUS <> 'CANCELADA'

        ORDER BY PRAZO ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':usuario' => $idUsuario
    ]);

    $metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    responder(200, [
        'sucesso' => true,
        'metas' => $metas
    ]);

} catch (PDOException $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível consultar as metas.'
    ]);
}

?>