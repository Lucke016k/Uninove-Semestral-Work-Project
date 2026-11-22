<?php

require_once __DIR__ . "/../../config/auth.php";
exigirLoginApi();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/verificar_usuario_ativo.php';

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

// Recebe os dados da meta de gasto.
$limiteTexto = $_POST['limit'] ?? '';
$inicio = $_POST['start'] ?? '';
$dias = filter_var(
    $_POST['days'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 30]]
);

if (!is_numeric($limiteTexto)) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Informe um limite de gastos válido.'
    ]);
}

$limite = round((float) $limiteTexto, 2);

if (!is_finite($limite) || $limite <= 0 || $limite > 9999999999.99) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Informe um limite de gastos maior que zero.'
    ]);
}

// Valida a data de início (AAAA-MM-DD).
$partesData = explode('-', $inicio);

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
        'erro' => 'Data de início inválida.'
    ]);
}

if ($dias === false || $dias === null) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'A duração deve ser de 1 a 30 dias.'
    ]);
}

try {

    // Uma linha por usuário: cria na primeira vez e atualiza nas seguintes.
    $stmt = $pdo->prepare("
        INSERT INTO meta_gasto (
            ID_USUARIO,
            VALOR_LIMITE,
            DATA_INICIO,
            DIAS
        )
        VALUES (
            :usuario,
            :limite,
            :inicio,
            :dias
        )
        ON DUPLICATE KEY UPDATE
            VALOR_LIMITE = VALUES(VALOR_LIMITE),
            DATA_INICIO = VALUES(DATA_INICIO),
            DIAS = VALUES(DIAS)
    ");

    $stmt->execute([
        ':usuario' => $idUsuario,
        ':limite' => $limite,
        ':inicio' => $inicio,
        ':dias' => $dias
    ]);

    responder(200, [
        'sucesso' => true,
        'mensagem' => 'Meta de gasto atualizada!',
        'meta' => [
            'limite' => $limite,
            'inicio' => $inicio,
            'dias' => $dias
        ]
    ]);

} catch (PDOException $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível salvar a meta de gasto.'
    ]);
}
