<?php

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/google_segredos.php';

$googleClient = new Google\Client();

$googleClient->setClientId(GOOGLE_CLIENT_ID);
$googleClient->setClientSecret(GOOGLE_CLIENT_SECRET);
$googleClient->setRedirectUri(GOOGLE_REDIRECT_URI);

$googleClient->addScope('email');
$googleClient->addScope('profile');

$googleClient->setPrompt('select_account');

$url = $googleClient->createAuthUrl();

header('Location: ' . $url);
exit;