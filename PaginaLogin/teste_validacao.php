<?php

require_once __DIR__ . "/../config/conexao.php";

$email = "luiza.garcia@uni9.edu.br";

$sql = "SELECT SENHA_HASH FROM usuario
        WHERE EMAIL = :email AND ATIVO = 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([":email" => $email]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    exit("USUÁRIO NÃO ENCONTRADO");
}

$senha = "menina8408";

if (password_verify($senha, $usuario["SENHA_HASH"])) {
    echo "SENHA CORRETA";
} else {
    echo "SENHA INCORRETA";
}