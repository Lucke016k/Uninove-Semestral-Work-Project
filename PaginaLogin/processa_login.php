
<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $lembrar = isset($_POST["lembrar"]);

    if ($email == "" || $senha == "") {

        echo "Preencha todos os campos.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        echo "Digite um e-mail válido.";

    } elseif (strlen($senha) < 6) {

        echo "A senha deve possuir pelo menos 6 caracteres.";

    } else {

        echo "<h2>Dados recebidos com sucesso!</h2>";

        if ($lembrar) {
            echo "Opção lembrar de mim selecionada.";
        } else {
            echo "Opção lembrar de mim não selecionada.";
        }

        echo "<p>O login será confirmado após a integração com o banco de dados.</p>";
    }

} else {

    header("Location: login.php");
    exit;

}

?>

<br>
<a href="login.php">Voltar para o login</a>