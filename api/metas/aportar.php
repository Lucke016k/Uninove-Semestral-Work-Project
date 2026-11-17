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

// Formata um valor em reais para as mensagens de erro.
function reais($valor)
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

// Identifica o usuário.
$idUsuario = $_SESSION['usuario']['id'] ?? null;

if (!$idUsuario) {
    responder(401, [
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);
}

// Recebe os dados do aporte.
$idMeta = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$origem = $_POST['origem'] ?? '';
$valorTexto = $_POST['valor'] ?? '';
$data = $_POST['data'] ?? '';

if (!$idMeta || $idMeta <= 0) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'ID da meta inválido.'
    ]);
}

if (!in_array($origem, ['FIXA', 'VARIAVEL'], true)) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Escolha se o valor sai do lucro fixo ou variável.'
    ]);
}

if (!is_numeric($valorTexto)) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Informe um valor válido.'
    ]);
}

$valor = round((float) $valorTexto, 2);

if (!is_finite($valor) || $valor <= 0 || $valor > 9999999999.99) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Informe um valor maior que zero.'
    ]);
}

// Valida a data do aporte (AAAA-MM-DD).
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

// O saldo é calculado dentro do mês da data do aporte.
$inicio = substr($data, 0, 7) . '-01';
$fim = date('Y-m-t', strtotime($inicio));

try {

    $pdo->beginTransaction();

    // Trava o usuário para que dois aportes simultâneos não passem do saldo.
    $stmt = $pdo->prepare("
        SELECT ID_USUARIO
        FROM usuario
        WHERE ID_USUARIO = :usuario
        FOR UPDATE
    ");
    $stmt->execute([':usuario' => $idUsuario]);

    // Confere se a meta existe e pertence ao usuário.
    $stmt = $pdo->prepare("
        SELECT VALOR_OBJETIVO, VALOR_ACUMULADO
        FROM meta
        WHERE ID_META = :id
          AND ID_USUARIO = :usuario
          AND STATUS <> 'CANCELADA'
        LIMIT 1
    ");
    $stmt->execute([
        ':id' => $idMeta,
        ':usuario' => $idUsuario
    ]);

    $meta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$meta) {
        $pdo->rollBack();

        responder(404, [
            'sucesso' => false,
            'erro' => 'Meta não encontrada.'
        ]);
    }

    // Lucros do mês por natureza (fixa ou variável).
    $stmt = $pdo->prepare("
        SELECT
            NATUREZA_RECEITA AS natureza,
            COALESCE(SUM(VALOR), 0) AS total
        FROM lancamento
        WHERE ID_USUARIO = :usuario
          AND TIPO = 'RECEITA'
          AND STATUS <> 'CANCELADO'
          AND DATA_LANCAMENTO BETWEEN :inicio AND :fim
        GROUP BY NATUREZA_RECEITA
    ");
    $stmt->execute([
        ':usuario' => $idUsuario,
        ':inicio' => $inicio,
        ':fim' => $fim
    ]);

    $lucro = ['FIXA' => 0.0, 'VARIAVEL' => 0.0];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        if (isset($lucro[$linha['natureza']])) {
            $lucro[$linha['natureza']] = (float) $linha['total'];
        }
    }

    // Gastos do mês.
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(VALOR), 0)
        FROM lancamento
        WHERE ID_USUARIO = :usuario
          AND TIPO = 'DESPESA'
          AND STATUS <> 'CANCELADO'
          AND DATA_LANCAMENTO BETWEEN :inicio AND :fim
    ");
    $stmt->execute([
        ':usuario' => $idUsuario,
        ':inicio' => $inicio,
        ':fim' => $fim
    ]);

    $gastos = (float) $stmt->fetchColumn();

    // Aportes já feitos no mês (metas canceladas devolvem o valor ao saldo).
    $stmt = $pdo->prepare("
        SELECT
            a.ORIGEM AS origem,
            COALESCE(SUM(a.VALOR), 0) AS total
        FROM aporte_meta a
        INNER JOIN meta m
            ON m.ID_META = a.ID_META
        WHERE a.ID_USUARIO = :usuario
          AND m.STATUS <> 'CANCELADA'
          AND a.DATA_APORTE BETWEEN :inicio AND :fim
        GROUP BY a.ORIGEM
    ");
    $stmt->execute([
        ':usuario' => $idUsuario,
        ':inicio' => $inicio,
        ':fim' => $fim
    ]);

    $aportado = ['FIXA' => 0.0, 'VARIAVEL' => 0.0];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        if (isset($aportado[$linha['origem']])) {
            $aportado[$linha['origem']] = (float) $linha['total'];
        }
    }

    // Comparações em centavos para evitar erro de arredondamento.
    $valorCentavos = (int) round($valor * 100);
    $saldoCentavos = (int) round(
        ($lucro['FIXA'] + $lucro['VARIAVEL'] - $gastos - $aportado['FIXA'] - $aportado['VARIAVEL']) * 100
    );
    $origemCentavos = (int) round(
        ($lucro[$origem] - $aportado[$origem]) * 100
    );

    $nomeOrigem = $origem === 'FIXA' ? 'lucro fixo' : 'lucro variável';

    if ($valorCentavos > $origemCentavos) {
        $pdo->rollBack();

        responder(400, [
            'sucesso' => false,
            'erro' => 'Saldo insuficiente no ' . $nomeOrigem . '. Disponível: ' .
                reais(max(0, $origemCentavos) / 100) . '.'
        ]);
    }

    if ($valorCentavos > $saldoCentavos) {
        $pdo->rollBack();

        responder(400, [
            'sucesso' => false,
            'erro' => 'Saldo insuficiente. O saldo do mês é de ' .
                reais(max(0, $saldoCentavos) / 100) . '.'
        ]);
    }

    // Registra o aporte.
    $stmt = $pdo->prepare("
        INSERT INTO aporte_meta (
            ID_USUARIO,
            ID_META,
            VALOR,
            ORIGEM,
            DATA_APORTE
        )
        VALUES (
            :usuario,
            :meta,
            :valor,
            :origem,
            :data
        )
    ");
    $stmt->execute([
        ':usuario' => $idUsuario,
        ':meta' => $idMeta,
        ':valor' => $valor,
        ':origem' => $origem,
        ':data' => $data
    ]);

    // Soma o valor na meta e recalcula a situação.
    $novoAcumulado = round((float) $meta['VALOR_ACUMULADO'] + $valor, 2);
    $status = $novoAcumulado >= (float) $meta['VALOR_OBJETIVO']
        ? 'CONCLUIDA'
        : 'EM_ANDAMENTO';

    $stmt = $pdo->prepare("
        UPDATE meta
        SET
            VALOR_ACUMULADO = :acumulado,
            STATUS = :status
        WHERE ID_META = :id
          AND ID_USUARIO = :usuario
    ");
    $stmt->execute([
        ':acumulado' => $novoAcumulado,
        ':status' => $status,
        ':id' => $idMeta,
        ':usuario' => $idUsuario
    ]);

    $pdo->commit();

    responder(200, [
        'sucesso' => true,
        'mensagem' => 'Valor guardado na meta!',
        'acumulado' => $novoAcumulado,
        'status' => $status
    ]);

} catch (PDOException $erro) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível guardar o valor na meta.'
    ]);
}
