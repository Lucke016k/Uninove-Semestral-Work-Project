<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"] ?? "");

    if ($email == "") {

        echo "Informe seu e-mail.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        echo "Digite um e-mail válido.";

    } else {

        echo "E-mail recebido! A recuperação de senha será disponibilizada após a integração com o banco de dados.";

    }

} else {

    header("Location: esqueci_senha.php");
    exit;

}

?>

<br>
<a href="login.php">Voltar para o login</a>