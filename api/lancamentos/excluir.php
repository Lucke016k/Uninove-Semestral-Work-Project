<?php

require_once __DIR__ . "/../../config/auth.php";
exigirLoginApi();


// Define o formato da resposta.
header('Content-Type: application/json; charset=utf-8');

// Importa a conexão.
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/verificar_usuario_ativo.php';

// Padroniza as respostas.
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
if (!$idUsuario) {
    responder(401, [
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);
}

// Recebe e valida o ID do lançamento.
$idLancamento = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

// Verifica se o ID é válido.
if (!$idLancamento || $idLancamento <= 0) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'ID do lançamento inválido.'
    ]);
}

try {

    // Verifica se o lançamento pertence ao usuário.
    $sqlVerifica = "
        SELECT ID_LANCAMENTO, STATUS
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

    $lancamento = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica se o registro existe.
    if (!$lancamento) {
        responder(404, [
            'sucesso' => false,
            'erro' => 'Lançamento não encontrado.'
        ]);
    }

    // Verifica se o lançamento já foi cancelado.
    if ($lancamento['STATUS'] === 'CANCELADO') {
        responder(409, [
            'sucesso' => false,
            'erro' => 'Este lançamento já foi cancelado.'
        ]);
    }

    // Atualiza o status no banco.
    $sql = "
        UPDATE lancamento
        SET STATUS = 'CANCELADO'
        WHERE ID_LANCAMENTO = :id
        AND ID_USUARIO = :usuario
        AND TIPO = 'DESPESA'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id' => $idLancamento,
        ':usuario' => $idUsuario
    ]);

    // Confirma a operação.
    responder(200, [
        'sucesso' => true,
        'mensagem' => 'Lançamento excluído com sucesso!'
    ]);

    } catch (PDOException $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível excluir o lançamento.'
    ]);
}

?>