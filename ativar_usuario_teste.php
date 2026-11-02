<?php 
session_start();
require_once __DIR__ . '/config/conexao.php';

try{
    // Dados do usuário utilizado nos testes.
    $nome = 'Usuário Teste';
    $email = 'teste@local.dev';

    // Verifica see o usuário já existe.
    $sql = "SELECT ID_USUARIO FROM usuario 
            Where EMAIL = :email
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);

    $idUsuario = $stmt->fetchColumn();

    // Caso não exista, cria o usuário.
    if (!$idUsuario) {
        // Gera uma senha criptograficamente segura.
        $senhaHash = password_hash(
            'Teste@123',
            PASSWORD_DEFAULT
        );

        $sql = "INSERT INTO usuario (NOME, EMAIL, SENHA_HASH)
                VALUES (:nome, :email, :senha)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':senha' => $senhaHash
        ]);

        $idUsuario = $pdo->lastInsertId();
    }

    // Simula a sessão de um usuário autenticado.
    $_SESSION['ID_USUARIO'] = (int) $idUsuario;

    // Remova o identificador da sessão.
    session_regenerate_id(true);

    echo "Usuário de teste ativado com sucesso!";

    echo "<br><a href='index.php'>Acessar sistema</a>";
} catch (PDOException $e){
    error_log($e->getMessage());

    echo "Erro ao ativar usuário de teste.";
}
?>