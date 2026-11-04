<?php

$servidor = "localhost";
$usuarioBanco = "root";
$senhaBanco = "";
$banco = "sistema_financeiro";

try {

    $pdo = new PDO(
        "mysql:host=$servidor;dbname=$banco;charset=utf8mb4",
        $usuarioBanco,
        $senhaBanco
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $erro) {

    error_log($erro->getMessage());

    die("Não foi possível conectar ao banco de dados.");

}

?>
