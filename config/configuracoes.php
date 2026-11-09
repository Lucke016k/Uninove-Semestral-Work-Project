<?php
session_start();

// Verifica se existe um usuário conectado
if (!isset($_SESSION["usuario"])) {
    header("Location: PaginaLogin/login.php");
    exit;
}

// Recupera os dados cadastrados na sessão
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";
exit;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta</title>
</head>

<body>

    <main class="configuracoes">

        <h1>Configurações</h1>

        <section class="minha-conta">

            <h2>Minha conta</h2>

            <p>Gerencie suas informações pessoais.</p>

            <div class="dados-conta">

                <div class="campo">
                    <label>Nome completo</label>
                    <p>
                        <?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>

                <div class="campo">
                    <label>E-mail</label>
                    <p>
                        <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>

            </div>

            <button type="button" onclick="alert('Em breve: edição de perfil!')">
                Editar perfil
            </button>

        </section>

    </main>

</body>
</html>