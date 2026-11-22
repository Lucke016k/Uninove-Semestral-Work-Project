<?php

// Qualquer aviso/erro do PHP vai para o log e não é impresso junto com o JSON.
ini_set('display_errors', '0');
ob_start();

require_once __DIR__ . '/../../config/auth.php';
exigirLoginApi();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/verificar_usuario_ativo.php';

function responder($status, $dados)
{
    // Descarta qualquer saída acidental antes de enviar o JSON.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

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
$modo = $_POST['modo'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$valorTexto = $_POST['valor'] ?? '';
$data = $_POST['data'] ?? '';

// Valida o modo e o tipo.
if (!in_array($modo, ['adicionar', 'substituir'], true)) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Escolha entre adicionar ou substituir.'
    ]);
}

if (!in_array($tipo, ['FIXA', 'VARIAVEL'], true)) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Escolha o tipo de lucro.'
    ]);
}

// Valida o valor.
if (!is_numeric($valorTexto)) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Informe um valor válido.'
    ]);
}

$valor = round((float) $valorTexto, 2);

// Adicionar exige valor positivo; substituir aceita zero (para zerar o lucro do mês).
if (
    !is_finite($valor) ||
    $valor < 0 ||
    $valor > 9999999999.99 ||
    ($modo === 'adicionar' && $valor <= 0)
) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Informe um valor válido.'
    ]);
}

// Valida a data do lançamento (AAAA-MM-DD).
$partesData = explode('-', $data);

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
        'erro' => 'Data inválida.'
    ]);
}

// Período do mês da data informada.
$inicio = substr($data, 0, 7) . '-01';
$fim = date('Y-m-t', strtotime($inicio));

$descricao = $tipo === 'FIXA' ? 'Lucro fixo' : 'Lucro variável';

try {

    $pdo->beginTransaction();

    // Substituir: cancela os lucros do mesmo tipo já lançados no mês.
    if ($modo === 'substituir') {

        $sqlCancela = "
            UPDATE lancamento
            SET STATUS = 'CANCELADO'
            WHERE ID_USUARIO = :usuario
              AND TIPO = 'RECEITA'
              AND NATUREZA_RECEITA = :natureza
              AND STATUS <> 'CANCELADO'
              AND DATA_LANCAMENTO BETWEEN :inicio AND :fim
        ";

        $stmt = $pdo->prepare($sqlCancela);

        $stmt->execute([
            ':usuario' => $idUsuario,
            ':natureza' => $tipo,
            ':inicio' => $inicio,
            ':fim' => $fim
        ]);
    }

    // Insere o novo valor (em "substituir" com valor zero, apenas zera o mês).
    if ($valor > 0) {

        $sqlInsere = "
            INSERT INTO lancamento (
                ID_USUARIO,
                DESCRICAO,
                VALOR,
                DATA_LANCAMENTO,
                TIPO,
                NATUREZA_RECEITA,
                STATUS
            )
            VALUES (
                :usuario,
                :descricao,
                :valor,
                :data,
                'RECEITA',
                :natureza,
                'PAGO'
            )
        ";

        $stmt = $pdo->prepare($sqlInsere);

        $stmt->execute([
            ':usuario' => $idUsuario,
            ':descricao' => $descricao,
            ':valor' => $valor,
            ':data' => $data,
            ':natureza' => $tipo
        ]);
    }

    $pdo->commit();

    responder(200, [
        'sucesso' => true,
        'mensagem' => $modo === 'adicionar'
            ? 'Valor adicionado ao lucro!'
            : 'Lucro atualizado com sucesso!'
    ]);

} catch (Throwable $erro) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível salvar o lucro.'
    ]);
}