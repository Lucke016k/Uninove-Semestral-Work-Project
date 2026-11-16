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

// Recebe os dados do formulário.
$idMeta = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);
    
$nome = trim($_POST['name'] ?? '');
$targetTexto = $_POST['target'] ?? '';
$savedTexto = $_POST['saved'] ?? '';
$due = $_POST['due'] ?? '';
$icone = trim($_POST['icon'] ?? '◎');

// Valida os valores.
if (
    !$idMeta ||
    $idMeta <= 0 ||
    $nome === '' ||
    strlen($nome) > 480 ||
    !is_numeric($targetTexto) ||
    !is_numeric($savedTexto)
) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Verifique os dados informados.'
    ]);
}

$target = (float) $targetTexto;
$saved = (float) $savedTexto;

if (
    !is_finite($target) ||
    !is_finite($saved) ||
    $target <= 0 ||
    $saved < 0
) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Os valores da meta são inválidos.'
    ]);
}

// Valida a data.
$partesData = explode('-', $due);

if (
    count($partesData) !== 3 ||
    !checkdate(
        (int) $partesData[1],
        (int) $partesData[2],
        (int) $partesData[0]
    )
) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Prazo inválido.'
    ]);
}

// Valida o ícone.
if (preg_match('/^.{1,20}$/us', $icone) !== 1) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Ícone inválido.'
    ]);
}

// Recalcula a situação da meta.
$status = $saved >= $target
    ? 'CONCLUIDA'
    : 'EM_ANDAMENTO';

try {

    // Confere se a meta existe e pertence ao usuário.
    $sqlBusca = "
        SELECT ID_META
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

    if ($stmt->fetchColumn() === false) {
        responder(404, [
            'sucesso' => false,
            'erro' => 'Meta não encontrada.'
        ]);
    }

    // Atualiza todos os campos da meta.
    $sql = "
        UPDATE meta
        SET
            TITULO = :nome,
            VALOR_OBJETIVO = :target,
            VALOR_ACUMULADO = :saved,
            PRAZO = :prazo,
            ICONE = :icone,
            STATUS = :status
        WHERE ID_META = :id
          AND ID_USUARIO = :usuario
          AND STATUS <> 'CANCELADA'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':nome' => $nome,
        ':target' => $target,
        ':saved' => $saved,
        ':prazo' => $due,
        ':icone' => $icone,
        ':status' => $status,
        ':id' => $idMeta,
        ':usuario' => $idUsuario
    ]);

    responder(200, [
        'sucesso' => true,
        'mensagem' => 'Meta atualizada com sucesso!',
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