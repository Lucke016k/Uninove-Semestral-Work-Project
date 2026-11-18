/*
 * Encerra a sessão após 30 minutos sem atividade,
 * exceto quando o usuário marcou "Lembrar de mim".
 * Mantém a sessão viva no servidor enquanto o usuário está usando a tela.
 */
(function () {
    const TEMPO_INATIVIDADE = 60 * 1000; // 1 minuto
    const INTERVALO_PING    = 5 * 60 * 1000;  // renova no servidor a cada 5 min de uso
    const URL_LOGIN         = 'PaginaLogin/login.php?sessao=expirada';

    // true quando o usuário marcou "Lembrar de mim" (vem do index.php).
    const LEMBRAR = !!(window.USUARIO && window.USUARIO.lembrar);

    const fetchOriginal = window.fetch.bind(window);
    let ultimaAtividade = Date.now();
    let ultimoPing      = Date.now();
    let expirando       = false;

    function expirar() {
        if (expirando) return;
        expirando = true;
        window.location.href = URL_LOGIN;
    }

    // Qualquer resposta 401 do PHP (sessão expirada no servidor) leva ao login.
    window.fetch = async function (...args) {
        const resposta = await fetchOriginal(...args);
        if (resposta.status === 401) expirar();
        return resposta;
    };

    function registrarAtividade() {
        ultimaAtividade = Date.now();

        if (ultimaAtividade - ultimoPing > INTERVALO_PING) {
            ultimoPing = ultimaAtividade;
            fetchOriginal('api/sessao/ping.php').then(r => {
                if (r.status === 401) expirar();
            }).catch(() => {});
        }
    }

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click']
        .forEach(ev => document.addEventListener(ev, registrarAtividade, { passive: true }));

    // Confere a cada 15 s (funciona mesmo se o computador "dormir").
    // Com "Lembrar de mim", não expira por inatividade.
    setInterval(() => {
        if (!LEMBRAR && Date.now() - ultimaAtividade >= TEMPO_INATIVIDADE) expirar();
    }, 15000);
})();