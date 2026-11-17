<?php

require_once __DIR__ . "/../../config/auth.php";
exigirLoginApi();

// Define o formato da resposta.
header('Content-Type: application/json; charset=utf-8');

// Importa a conexão.
require_once __DIR__ . '/../../config/conexao.php';

// Padroniza as repostas enviadas ao Javascript.
function responder($status, $dados)
{
    http_response_code($status);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// Recupera o usuário autenticado.
$idUsuario = $_SESSION['usuario']['id'] ?? null;

// Verifica a autenticação.
if (!$idUsuario){
    responder(401, [
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);
}

// Recebe o ID do lançamento.
$idLancamento = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

// Recebe os dados do formulário.
$descricao = trim($_POST['description'] ?? '');
$valorTexto = $_POST['value'] ?? '';
$data = $_POST['date'] ?? '';
$categoria = trim($_POST['category'] ?? '');
$pagamento = $_POST['payment'] ?? '';
$responsavel = trim($_POST['who'] ?? 'Eu');
$parcelado = $_POST['installments'] ?? 'Não';

// Converte o valor para o número.
$valor = is_numeric($valorTexto) ? (float) $valorTexto : 0;

// Verifica os campos obrigatórios.
$camposInvalidos = [];

if (!$idLancamento || $idLancamento <= 0) {
    $camposInvalidos[] = 'ID do lançamento';
}

if ($descricao === '') {
    $camposInvalidos[] = 'Descrição';
}

if ($valor <= 0) {
    $camposInvalidos[] = 'Valor';
}

if ($data === '') {
    $camposInvalidos[] = 'Data';
}

if ($categoria === '') {
    $camposInvalidos[] = 'Categoria';
}

if (!empty($camposInvalidos)) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'Campos inválidos: ' .
                  implode(', ', $camposInvalidos)
    ]);
}

// Valida a data.
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

// A quantidade de parcelas é só uma informação para o usuário ver:
// fica guardada como texto na observação ("Parcelado em 12x") e não gera parcelas.
$observacao = null;

if ($parcelado === 'Sim') {
    $qtdParcelas = filter_var(
        $_POST['installment_count'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 2, 'max_range' => 48]]
    );

    if ($qtdParcelas === false || $qtdParcelas === null) {
        responder(400, [
            'sucesso' => false,
            'erro' => 'Informe a quantidade de parcelas (de 2 a 48).'
        ]);
    }

    $observacao = 'Parcelado em ' . $qtdParcelas . 'x';
}

try {

    // Verifica se o lançamento pertence ao usuário.
    $sqlVerifica = "
        SELECT ID_LANCAMENTO
        FROM lancamento
        WHERE ID_LANCAMENTO = :id
          AND ID_USUARIO = :usuario
          AND TIPO = 'DESPESA'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sqlVerifica);

    $stmt->execute([
        ':id' => $idLancamento,
        ':usuario' => $idUsuario
    ]);

    if (!$stmt->fetchColumn()) {
        responder(404, [
            'sucesso' => false,
            'erro' => 'Lançamento não encontrado.'
        ]);
    }
        // Localiza o ID da categoria.
    $sqlCategoria = "
        SELECT ID_CATEGORIA
        FROM categoria
        WHERE NOME = :nome
        AND (ID_USUARIO = :usuario OR ID_USUARIO IS NULL)
        AND TIPO IN ('DESPESA', 'AMBOS')
        AND ATIVO = 1
        ORDER BY ID_USUARIO IS NULL ASC
        LIMIT 1
    ";

    $stmtCategoria = $pdo->prepare($sqlCategoria);

    $stmtCategoria->execute([
        ':nome' => $categoria,
        ':usuario' => $idUsuario
    ]);

    $idCategoria = $stmtCategoria->fetchColumn();

    if (!$idCategoria) {
        responder(400, [
            'sucesso' => false,
            'erro' => 'Categoria não encontrada.'
        ]);
    }

    // Relaciona os nomes do formulário aos nomes do banco.
    $mapaPagamento = [
        'Crédito' => 'CARTÃO DE CRÉDITO',
        'Débito' => 'CARTÃO DE DÉBITO',
        'Pix' => 'PIX',
        'Dinheiro' => 'DINHEIRO',
        'VR' => 'VR',
        'VA' => 'VA',
        'Ticket' => 'TICKET'
    ];

    $nomePagamento = $mapaPagamento[$pagamento] ?? null;

    if (!$nomePagamento) {
        responder(400, [
            'sucesso' => false,
            'erro' => 'Forma de pagamento inválida.'
        ]);
    }

    // Busca o ID da forma de pagamento.
    $sqlPagamento = "
        SELECT ID_FORMA_PAGAMENTO
        FROM forma_pagamento
        WHERE NOME = :nome
        AND ATIVO = 1
        LIMIT 1
    ";

    $stmtPagamento = $pdo->prepare($sqlPagamento);

    $stmtPagamento->execute([
        ':nome' => $nomePagamento
    ]);

    $idPagamento = $stmtPagamento->fetchColumn();

    if (!$idPagamento) {
        responder(400, [
            'sucesso' => false,
            'erro' => 'Forma de pagamento não encontrada.'
        ]);
    }

    // Atualiza os dados do lançamento.
    $sql = "
        UPDATE lancamento
        SET
            ID_CATEGORIA = :categoria,
            ID_FORMA_PAGAMENTO = :pagamento,
            DESCRICAO = :descricao,
            VALOR = :valor,
            DATA_LANCAMENTO = :data,
            RESPONSAVEL = :responsavel,
            OBSERVACAO = :observacao

        WHERE ID_LANCAMENTO = :id
        AND ID_USUARIO = :usuario
        AND TIPO = 'DESPESA'
    ";

    // Prepara a consulta.
    $stmt = $pdo->prepare($sql);

    // Executa a atualização.
    $stmt->execute([
        ':categoria' => $idCategoria,
        ':pagamento' => $idPagamento,
        ':descricao' => $descricao,
        ':valor' => $valor,
        ':data' => $data,
        ':responsavel' => $responsavel,
        ':observacao' => $observacao,
        ':id' => $idLancamento,
        ':usuario' => $idUsuario
    ]);

    // Retorna a confirmação.
    responder(200, [
        'sucesso' => true,
        'mensagem' => 'Lançamento atualizado com sucesso!',
        'id' => $idLancamento
    ]);

    } catch (PDOException $erro) {

    // Registra o erro técnico no servidor.
    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível atualizar o lançamento.'
    ]);
}