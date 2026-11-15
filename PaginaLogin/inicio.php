<?php

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>UniFinance - Início</title>
</head>
<body>

    <h1>Bem-vindo ao UniFinance!</h1>

    <p>Login realizado com sucesso.</p>

    <a href="logout.php">Sair da conta</a>

</body>
</html>