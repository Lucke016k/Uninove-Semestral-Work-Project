<?php

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/google_segredos.php';
require_once __DIR__ . '/../config/conexao.php';

$googleClient = new Google\Client();

$googleClient->setClientId(GOOGLE_CLIENT_ID);
$googleClient->setClientSecret(GOOGLE_CLIENT_SECRET);
$googleClient->setRedirectUri(GOOGLE_REDIRECT_URI);

if (!isset($_GET['code'])) {
    header('Location: login.php');
    exit;
}

try {

    $token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        throw new Exception('Falha na autenticação do Google.');
    }

    $googleClient->setAccessToken($token);

    $googleService = new Google\Service\Oauth2($googleClient);
    $dadosGoogle = $googleService->userinfo->get();

    $email = $dadosGoogle->email;
    $nome = $dadosGoogle->name;

    // Procura o usuário pelo e-mail
    $sql = "SELECT ID_USUARIO, NOME, EMAIL
            FROM usuario
            WHERE EMAIL = :email
            AND ATIVO = 1
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $email
    ]);

    $usuario = $stmt->fetch();

    if (!$usuario) {
        $_SESSION['auth_error'] = 'Não encontramos uma conta ativa com esse e-mail. Cadastre-se primeiro.';
        header('Location: login.php');
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'id' => $usuario['ID_USUARIO'],
        'nome' => $usuario['NOME'],
        'email' => $usuario['EMAIL']
    ];

    header('Location: ../index.php');
    exit;

} catch (Throwable $erro) {

    error_log('Erro no login Google: ' . $erro->getMessage());

    $_SESSION['auth_error'] = 'Não foi possível entrar com o Google. Tente novamente.';

    header('Location: login.php');
    exit;
}