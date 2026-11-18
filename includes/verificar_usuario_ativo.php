<?php

// Garante que exista uma sessão.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Recupera o usuário autenticado.
$idUsuario = $_SESSION['usuario']['id'] ?? null;

// Verifica a existência da sessão.
if (!$idUsuario) {

    http_response_code(401);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'sucesso' => false,
        'erro' => 'Sessão não autenticada.'
    ]);

    exit;
}

// Importa a conexão.
require_once __DIR__ . '/../config/conexao.php';

// Consulta a situação da conta diretamente no banco.
$sql = "
    SELECT ATIVO
    FROM usuario
    WHERE ID_USUARIO = :usuario
    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':usuario' => $idUsuario
]);

$ativo = $stmt->fetchColumn();

// Se a conta não existir ou estiver desativada,
// encerra a sessão e bloqueia a operação.
if ($ativo === false || (int) $ativo !== 1) {

    $_SESSION = [];

    session_destroy();

    http_response_code(403);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'sucesso' => false,
        'erro' => 'Sua conta está desativada ou não possui autorização.'
    ]);

    exit;
}