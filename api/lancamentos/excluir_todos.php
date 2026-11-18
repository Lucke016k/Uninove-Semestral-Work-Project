
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

// Identifica o usuário autenticado.
$idUsuario = $_SESSION['usuario']['id'] ?? null;

if (!$idUsuario) {
    responder(401, [
        'sucesso' => false,
        'erro' => 'Usuário não autenticado.'
    ]);
}

try {

    // Exclui somente os lançamentos do usuário autenticado.
    $sql = "
        DELETE FROM lancamento
        WHERE ID_USUARIO = :usuario
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':usuario' => $idUsuario
    ]);

    $quantidadeExcluida = $stmt->rowCount();

    responder(200, [
        'sucesso' => true,
        'mensagem' => $quantidadeExcluida > 0
            ? "$quantidadeExcluida lançamento(s) excluído(s) com sucesso!"
            : 'Não existem lançamentos para excluir.',
        'quantidade' => $quantidadeExcluida
    ]);

} catch (PDOException $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível excluir os lançamentos.'
    ]);
}
