<?php

// 1. Inicialização e conexão
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/conexao.php';


// 2. Função de resposta
function responder($status, $dados)
{
    http_response_code($status);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


// 3. Identificação do usuário
$idUsuario = $_SESSION['usuario']['id'] ?? null;

// Verifica a autenticação.
if (!$idUsuario) {
    responder(401, [
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);
}


// 4. Recebimento dos dados
$descricao = trim($_POST['description'] ?? '');
$valor = (float) ($_POST['value'] ?? 0);
$data = $_POST['date'] ?? '';
$categoria = trim($_POST['category'] ?? '');
$pagamento = $_POST['payment'] ?? '';
$responsavel = trim($_POST['who'] ?? 'Eu');
$parcelado = $_POST['installments'] ?? 'Não';

// Verifica os Campos obrigatórios.
// Identifica quais campos estão inválidos.
$camposInvalidos = [];

// Verifica a descrição.
if ($descricao === '') {
    $camposInvalidos[] = 'Descrição';
}

// Verifica o valor.
if ($valor <= 0) {
    $camposInvalidos[] = 'Valor';
}

// Verifica a data.
if ($data === '') {
    $camposInvalidos[] = 'Data';
}

// Verifica a categoria.
if ($categoria === '') {
    $camposInvalidos[] = 'Categoria';
}

// Retorna os campos que precisam de correção.
if (!empty($camposInvalidos)) {

    responder(400, [
        'sucesso' => false,
        'erro' => 'Campos inválidos: ' .
                  implode(', ', $camposInvalidos)
    ]);
}

// Valida o formato e a existência da data.
$partesData = explode('-', $data);

if(
    count($partesData) !== 3 || !checkdate(
        (int) $partesData[1],
        (int) $partesData[2],
        (int) $partesData[0]
    )
){
    responder(400, [
        'sucesso' => false,
        'erro' => 'Data inválida.'
    ]);
}

// Parcelamento ainda não liberado
if($parcelado === 'Sim'){
    responder(422, [
        'sucesso' => false,
        'erro' => 'O cadastro de parcelas será implementado posteriormente.'
    ]);
}

// Localiza a categoria selecionada no banco.
$sqlCategoria = "
    SELECT ID_CATEGORIA FROM categoria
    WHERE NOME = :nome
        AND (ID_USUARIO = :id_usuario or ID_USUARIO IS NULL)
        AND TIPO IN ('DESPESA', 'AMBOS')
        AND ATIVO = 1
    ORDER BY ID_USUARIO IS NULL ASC LIMIT 1
";

$stmtCategoria = $pdo->prepare($sqlCategoria);

$stmtCategoria->execute([
    ':nome' => $categoria,
    ':id_usuario' => $idUsuario
]);

$idCategoria = $stmtCategoria->fetchColumn();

//Verifica se a categoria foi encontrada.
if(!$idCategoria){
    responder(400, [
        'sucesso' => false,
        'erro' => 'Categoria não encontrada no banco de dados.'
    ]);
}

// Relaciona os nomes exibidos na interface com os nomes cadastrados no banco.
$mapaPagamento = [
    'Crédito' => 'CARTÃO DE CRÉDITO',
    'Débito' => 'CARTÃO DE DÉBITO',
    'Pix' => 'PIX',
    'Dinheiro' => 'DINHEIRO',
    'VR' => 'VR', 
    'VA' => 'VA',
    'Ticket' => 'TICKET' 
];

// Procura o nome correspondente.
$nomePagamento = $mapaPagamento[$pagamento] ?? null;

if(!$nomePagamento){
    responder(400, [
        'sucesso' => false,
        'erro' => 'Forma de pagamento inválida.'
    ]);
}

// Busca o ID da forma de pagamento.
$sqlPagamento = "
    SELECT ID_FORMA_PAGAMENTO FROM forma_pagamento
    WHERE NOME = :nome
        and ATIVO = 1
    LIMIT 1
";

$stmtPagamento = $pdo->prepare($sqlPagamento);

$stmtPagamento->execute([
    ':nome' => $nomePagamento
]);

$idPagamento = $stmtPagamento->fetchColumn();

if(!$idPagamento){
    responder(400, [
        'sucesso' => false,
        'erro' => 'Forma de pagamento não encontrada.'
    ]);
}

try{
    // Comando SQL responsável pelo cadastro de gasto.
    $sql = "
        INSERT INTO lancamento (
            ID_USUARIO,
            ID_CATEGORIA,
            ID_FORMA_PAGAMENTO,
            DESCRICAO,
            VALOR,
            DATA_LANCAMENTO,
            TIPO,
            RESPONSAVEL
        )
        VALUES(
            :usuario,
            :categoria,
            :pagamento,
            :descricao,
            :valor,
            :data,
            'DESPESA',
            :responsavel
        )
    ";


    $stmt = $pdo->prepare($sql);

    // Envia os valores para os parâmetros do SQL.
    $stmt->execute([
        ':usuario' => $idUsuario,
        ':categoria' => $idCategoria,
        ':pagamento' => $idPagamento,
        ':descricao' => $descricao,
        ':valor' => $valor,
        ':data' => $data,
        ':responsavel' => $responsavel,
    ]);

    // Recupera o ID gerado pelo AUTO_INCREMENT.
    $idNovoLancamento = $pdo->lastInsertId();

    // Retorna uma resposta positiva ao Javascript.
    responder(201, [
        'sucesso' => true,
        'mensagem' => 'Gasto cadastrado com sucesso!',
        'id' => $idNovoLancamento
    ]);
}
catch(PDOException $erro){

    // Guarda detalhes técnicos no log do servidor.
    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível cadastrar o gasto.'
    ]);
}
