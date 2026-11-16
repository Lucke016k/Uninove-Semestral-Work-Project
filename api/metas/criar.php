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
$nome = trim($_POST['name'] ?? '');
$targetTexto = $_POST['target'] ?? '';
$savedTexto = $_POST['saved'] ?? '0';
$due = $_POST['due'] ?? '';
$icone = trim($_POST['icon'] ?? '◎');

// Valida os valores.
if (
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

// Verifica se os valores são válidos.
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

// Define a situação inicial da meta.
$status = $saved >= $target
    ? 'CONCLUIDA'
    : 'EM_ANDAMENTO';

try {

    // Insere a nova meta.
    $sql = "
        INSERT INTO meta (
            ID_USUARIO,
            TITULO,
            VALOR_OBJETIVO,
            VALOR_ACUMULADO,
            PRAZO,
            ICONE,
            STATUS
        )
        VALUES (
            :usuario,
            :nome,
            :target,
            :saved,
            :prazo,
            :icone,
            :status
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':usuario' => $idUsuario,
        ':nome' => $nome,
        ':target' => $target,
        ':saved' => $saved,
        ':prazo' => $due,
        ':icone' => $icone,
        ':status' => $status
    ]);

    responder(201, [
        'sucesso' => true,
        'mensagem' => 'Meta criada com sucesso!',
        'id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível criar a meta.'
    ]);
}

?>