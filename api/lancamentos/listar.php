<?php

// Inicia a sessão.
session_start();

// Define o formato de resposta.
header('Content-Type: application/json; charset=utf-8');

// Importa conexão.
require_once __DIR__ . '/../../config/conexao.php';

// Função para padronizar as repostas.
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

try {
    // Busca os lançamentos pertencentes ao usuário.
    $sql = "
        SELECT
            l.ID_LANCAMENTO AS id,
            l.DESCRICAO AS description,
            l.VALOR AS value,
            l.DATA_LANCAMENTO AS date,
            c.NOME AS category,

            CASE
                WHEN fp.NOME = 'CARTÃO DE CRÉDITO' THEN 'Crédito'
                WHEN fp.NOME = 'CARTÃO DE DÉBITO' THEN 'Débito'
                WHEN fp.NOME = 'PIX' THEN 'Pix'
                WHEN fp.NOME = 'DINHEIRO' THEN 'Dinheiro'
                ELSE fp.NOME
            END AS payment,

            l.RESPONSAVEL AS who,
            'Não' AS installments

        FROM lancamento l
        
        LEFT JOIN categoria c
        ON c.ID_CATEGORIA = l.ID_CATEGORIA

        LEFT JOIN forma_pagamento fp
            ON fp.ID_FORMA_PAGAMENTO = l.ID_FORMA_PAGAMENTO

        WHERE l.ID_USUARIO = :usuario
            AND l.TIPO = 'DESPESA'
            AND l.STATUS <> 'CANCELADO'

        ORDER BY l.DATA_LANCAMENTO DESC, l.ID_LANCAMENTO DESC
    ";

    // Prepara a consulta.
    $stmt = $pdo->prepare($sql);

    // Executa para o usuário autenticado.
    $stmt->execute([
        ':usuario' => $idUsuario
    ]);

    // Recupera todos os registros.
    $lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Devolve os registros ao JavaScript.
    responder(200, [
        'sucesso' => true,
        'lancamentos' => $lancamentos
    ]);
}   catch (PDOException $erro){
    
    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possivel consultar os lançamentos.'
    ]);
}
