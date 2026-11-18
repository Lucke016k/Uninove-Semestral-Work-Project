<?php

// Inicia a sessão.
session_start();

// Define o formato da resposta.
header('Content-Type: application/json; charset=utf-8');

// Importa a conexão com o banco.
require_once __DIR__ . '/../../config/conexao.php';

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

// Verifica o token CSRF.
$tokenSessao = $_SESSION['csrf_token'] ?? '';
$tokenRecebido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (
    $tokenSessao === '' ||
    $tokenRecebido === '' ||
    !hash_equals($tokenSessao, $tokenRecebido)
) {
    responder(403, [
        'sucesso' => false,
        'erro' => 'Solicitação não autorizada.'
    ]);
}

try {

    // Desativa somente a conta autenticada.
    $sql = "
        UPDATE usuario
        SET ATIVO = 0
        WHERE ID_USUARIO = :usuario
          AND ATIVO = 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':usuario' => $idUsuario
    ]);

    // Verifica se alguma conta foi alterada.
    if ($stmt->rowCount() !== 1) {
        responder(404, [
            'sucesso' => false,
            'erro' => 'Usuário não encontrado ou já desativado.'
        ]);
    }

    // Limpa os dados da sessão.
    $_SESSION = [];

    // Remove o cookie da sessão do navegador.
    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);
    }

    // Encerra a sessão.
    session_destroy();

    // Confirma a operação.
    responder(200, [
        'sucesso' => true,
        'mensagem' => 'Sua conta foi desativada com sucesso!'
    ]);

} catch (PDOException $erro) {

    error_log('Erro ao desativar usuário: ' . $erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível desativar sua conta.'
    ]);
}

?>