<?php

// Qualquer aviso/erro do PHP vai para o log e não é impresso junto com o JSON.
ini_set('display_errors', '0');
ob_start();

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/conexao.php';

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

// Mês consultado (AAAA-MM). Sem parâmetro, usa o mês atual do servidor.
$mes = $_GET['mes'] ?? date('Y-m');

if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes) !== 1) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Mês inválido.'
    ]);
}

$inicio = $mes . '-01';
$fim = date('Y-m-t', strtotime($inicio));

try {

    // Soma as receitas do mês separadas por natureza (fixa ou variável).
    $sql = "
        SELECT
            NATUREZA_RECEITA AS natureza,
            COALESCE(SUM(VALOR), 0) AS total
        FROM lancamento
        WHERE ID_USUARIO = :usuario
          AND TIPO = 'RECEITA'
          AND STATUS <> 'CANCELADO'
          AND DATA_LANCAMENTO BETWEEN :inicio AND :fim
        GROUP BY NATUREZA_RECEITA
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':usuario' => $idUsuario,
        ':inicio' => $inicio,
        ':fim' => $fim
    ]);

    $fixo = 0.0;
    $variavel = 0.0;

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        if ($linha['natureza'] === 'FIXA') {
            $fixo = (float) $linha['total'];
        } elseif ($linha['natureza'] === 'VARIAVEL') {
            $variavel = (float) $linha['total'];
        }
    }

    responder(200, [
        'sucesso' => true,
        'mes' => $mes,
        'fixo' => $fixo,
        'variavel' => $variavel,
        'total' => $fixo + $variavel
    ]);

} catch (Throwable $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível consultar os lucros.'
    ]);
}