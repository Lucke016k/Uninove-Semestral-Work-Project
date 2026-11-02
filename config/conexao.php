<?php
/**
 * Conexão central com o MySQL/MariaDB usando PDO.
 * Ajuste as credenciais conforme o seu ambiente.
 * Em hospedagem, não mantenha senha vazia nem credenciais no repositório público.
 */

$host = 'localhost';
$banco = 'sistema_financeiro';
$usuario = 'root';       // Padrão comum do XAMPP local.
$senha = '';             // Padrão comum do XAMPP local; altere em produção.

$dsn = "mysql:host={$host};dbname={$banco};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $usuario, $senha, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $erro) {
    // Registre detalhes no log do servidor. Não exponha senha/estrutura ao visitante.
    error_log('Falha na conexão com o banco: ' . $erro->getMessage());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados. Verifique a configuração do servidor.');
}
