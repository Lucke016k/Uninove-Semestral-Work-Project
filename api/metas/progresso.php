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

$idUsuario = $_SESSION['usuario']['id'] ?? null;

if (!$idUsuario) {
    responder(401, [
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);
}

// Recebe os dados.
$idMeta = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

$savedTexto = $_POST['saved'] ?? '';

if (
    !$idMeta ||
    $idMeta <= 0 ||
    !is_numeric($savedTexto)
) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Dados inválidos.'
    ]);
}

$saved = (float) $savedTexto;

if (!is_finite($saved) || $saved < 0) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Valor acumulado inválido.'
    ]);
}

try {

    // Confere a propriedade da meta e recupera o objetivo.
    $sqlBusca = "
        SELECT VALOR_OBJETIVO
        FROM meta
        WHERE ID_META = :id
          AND ID_USUARIO = :usuario
          AND STATUS <> 'CANCELADA'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sqlBusca);

    $stmt->execute([
        ':id' => $idMeta,
        ':usuario' => $idUsuario
    ]);

    $target = $stmt->fetchColumn();

    if ($target === false) {
        responder(404, [
            'sucesso' => false,
            'erro' => 'Meta não encontrada.'
        ]);
    }

    // Atualiza automaticamente a situação.
    $status = $saved >= (float) $target
        ? 'CONCLUIDA'
        : 'EM_ANDAMENTO';

    $sql = "
        UPDATE meta
        SET
            VALOR_ACUMULADO = :saved,
            STATUS = :status
        WHERE ID_META = :id
          AND ID_USUARIO = :usuario
          AND STATUS <> 'CANCELADA'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':saved' => $saved,
        ':status' => $status,
        ':id' => $idMeta,
        ':usuario' => $idUsuario
    ]);

    responder(200, [
        'sucesso' => true,
        'mensagem' => 'Progresso da meta atualizado!',
        'status' => $status
    ]);

} catch (PDOException $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível atualizar a meta.'
    ]);
}

?>