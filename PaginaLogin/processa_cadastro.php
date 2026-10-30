<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmar_senha = $_POST["confirmar_senha"] ?? "";

    if ($nome == "" || $email == "" ||
        $senha == "" || $confirmar_senha == "") {

        echo "Preencha todos os campos.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        echo "Digite um e-mail válido.";

    } elseif (strlen($senha) < 6) {

        echo "A senha deve possuir pelo menos 6 caracteres.";

    } elseif ($senha != $confirmar_senha) {

        echo "As senhas não são iguais.";

    } else {

        echo "<h2>Dados validados com sucesso!</h2>";

        echo "<p>O cadastro será concluído após a integração com o banco de dados.</p>";

    }

} else {

    header("Location: cadastro.php");
    exit;

}

?>

<br>
<a href="login.php">Voltar para o login</a>