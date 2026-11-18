<?php
require_once __DIR__ . "/../../config/auth.php";
exigirLoginApi();


// Define o formato da resposta.
header('Content-Type: application/json; charset=utf-8');

// Importa a conexão.
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/verificar_usuario_ativo.php';

// Padroniza as respostas enviadas ao Javascript.
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

try {
    // Lista as categorias ativas do usuário e as globais (ID_USUARIO NULL).
    $sql = "
        SELECT
            ID_CATEGORIA AS id,
            NOME AS name,
            (ID_USUARIO IS NULL) AS global
        FROM categoria
        WHERE (ID_USUARIO = :usuario OR ID_USUARIO IS NULL)
            AND TIPO IN ('DESPESA', 'AMBOS')
            AND ATIVO = 1
        ORDER BY NOME ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':usuario' => $idUsuario
    ]);

    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Converte os campos numéricos para os tipos corretos no JSON.
    foreach ($categorias as &$categoria) {
        $categoria['id'] = (int) $categoria['id'];
        $categoria['global'] = (bool) $categoria['global'];
    }
    unset($categoria);

    responder(200, [
        'sucesso' => true,
        'categorias' => $categorias
    ]);
} catch (PDOException $erro) {

    error_log($erro->getMessage());

    responder(500, [
        'sucesso' => false,
        'erro' => 'Não foi possível consultar as categorias.'
    ]);
}
