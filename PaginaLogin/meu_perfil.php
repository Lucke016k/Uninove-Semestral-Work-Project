<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

// Verifica se existe usuário conectado.
if (!isset($_SESSION["usuario"]["id"])) {
    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Usuário não autenticado."
    ]);

    exit;
}

require_once __DIR__ . "/../config/conexao.php";

try {

    // Busca os dados do usuário conectado no banco.
    $sql = "SELECT ID_USUARIO, NOME, EMAIL
            FROM usuario
            WHERE ID_USUARIO = :id
            AND ATIVO = 1
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":id" => $_SESSION["usuario"]["id"]
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        http_response_code(404);

        echo json_encode([
            "sucesso" => false,
            "erro" => "Usuário não encontrado."
        ]);

        exit;
    }

    // Retorna os dados para o JavaScript.
    echo json_encode([
        "sucesso" => true,
        "usuario" => [
            "id" => $usuario["ID_USUARIO"],
            "nome" => $usuario["NOME"],
            "email" => $usuario["EMAIL"]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $erro) {

    error_log($erro->getMessage());

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Erro ao consultar os dados do usuário."
    ]);

}