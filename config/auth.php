<?php
/**
 * Controle de sessão: encerra após 30 minutos sem atividade.
 * Se mudar o tempo aqui, mude também em sessao.js.
 */

const TEMPO_INATIVIDADE = 60; // 30 minutos, em segundos.

function iniciarSessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // Evita que o PHP apague o arquivo da sessão antes dos 30 minutos.
        ini_set("session.gc_maxlifetime", (string) TEMPO_INATIVIDADE);
        session_start();
    }
}

function encerrarSessao(): void
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), "", time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }

    session_destroy();
}

// Retorna "ok", "sem_login" ou "expirada" e renova o horário da última atividade.
function verificarSessao(): string
{
    iniciarSessao();

    if (!isset($_SESSION["usuario"])) {
        return "sem_login";
    }

    $agora = time();

    if (isset($_SESSION["ultima_atividade"])
        && ($agora - $_SESSION["ultima_atividade"]) > TEMPO_INATIVIDADE) {
        encerrarSessao();
        return "expirada";
    }

    $_SESSION["ultima_atividade"] = $agora;
    return "ok";
}

// Para páginas HTML: manda para o login. Ex.: exigirLoginPagina("PaginaLogin/login.php");
function exigirLoginPagina(string $urlLogin): void
{
    $estado = verificarSessao();

    if ($estado !== "ok") {
        $destino = $urlLogin . ($estado === "expirada" ? "?sessao=expirada" : "");
        header("Location: " . $destino);
        exit;
    }
}

// Para endpoints que respondem JSON (pasta api/ e meu_perfil.php).
function exigirLoginApi(): void
{
    $estado = verificarSessao();

    if ($estado !== "ok") {
        http_response_code(401);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "sucesso" => false,
            "erro"    => $estado === "expirada"
                ? "Sua sessão expirou por inatividade."
                : "Usuário não autenticado.",
            "expirada" => $estado === "expirada"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}