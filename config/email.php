<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../vendor/autoload.php";

$credenciais = require __DIR__ . "/email_segredos.php";

function enviarEmailRecuperacao($destinatario, $nome, $link)
{
    global $credenciais;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;

        $mail->Username = $credenciais["email"];
        $mail->Password = $credenciais["senha_app"];

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = "UTF-8";

        $mail->setFrom($credenciais["email"], "MeuSaldo");
        $mail->addAddress($destinatario, $nome);

        $mail->isHTML(true);
        $mail->Subject = "Recuperação de senha - MeuSaldo";

        $nomeSeguro = htmlspecialchars($nome, ENT_QUOTES, "UTF-8");
        $linkSeguro = htmlspecialchars($link, ENT_QUOTES, "UTF-8");

        $mail->Body = "
            <h2>Olá, {$nomeSeguro}!</h2>
            <p>Recebemos uma solicitação para redefinir sua senha.</p>
            <p><a href='{$linkSeguro}'>Clique aqui para criar uma nova senha</a></p>
            <p>Este link expira em 30 minutos.</p>
            <p>Se você não solicitou a recuperação, ignore este e-mail.</p>
        ";

        $mail->AltBody = "Acesse este link para redefinir sua senha: " . $link;

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log("Erro no envio do Gmail: " . $mail->ErrorInfo);

        echo "<pre>Erro no envio do Gmail: "
            . htmlspecialchars($mail->ErrorInfo, ENT_QUOTES, "UTF-8")
            . "</pre>";

        return false;
    }
}