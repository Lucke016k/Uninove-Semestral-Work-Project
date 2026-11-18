/*
 * Tema do sistema (claro, escuro verde, escuro roxo).
 *
 * Carregue no <head>, ANTES do CSS da página, para o tema aparecer sem "piscar".
 * A escolha fica no navegador (localStorage) e vale também para as telas de login.
 */
(function () {
    const CHAVE = 'cf_theme';

    // Temas disponíveis: id usado no data-theme, nome mostrado ao usuário e ícone.
    const TEMAS = [
        { id: 'claro',  nome: 'Claro (bege + verde)', icone: '☀️', cor: '#f5f2e7' },
        { id: 'escuro', nome: 'Escuro (verde)',        icone: '🌙', cor: '#131913' },
        { id: 'roxo',   nome: 'Escuro (roxo)',         icone: '🟣', cor: '#0d0920' }
    ];

    function valido(id) {
        return TEMAS.some(t => t.id === id);
    }

    function lerSalvo() {
        try {
            const id = localStorage.getItem(CHAVE);
            return valido(id) ? id : 'claro';
        } catch (e) {
            return 'claro';
        }
    }

    function aplicar(id) {
        const tema = TEMAS.find(t => t.id === id) || TEMAS[0];
        const html = document.documentElement;

        html.setAttribute('data-theme', tema.id);

        // Cor da barra do navegador no celular.
        let meta = document.querySelector('meta[name="theme-color"]');
        if (!meta) {
            meta = document.createElement('meta');
            meta.name = 'theme-color';
            document.head.appendChild(meta);
        }
        meta.content = tema.cor;
    }

    window.Tema = {
        lista: TEMAS,
        atual: () => document.documentElement.getAttribute('data-theme') || 'claro',

        // Troca o tema, guarda a escolha e avisa a página (gráficos precisam redesenhar).
        definir(id) {
            if (!valido(id)) return;

            aplicar(id);

            try {
                localStorage.setItem(CHAVE, id);
            } catch (e) { /* sem armazenamento: vale só nesta visita */ }

            document.dispatchEvent(new CustomEvent('tema-alterado', { detail: { tema: id } }));
        }
    };

    aplicar(lerSalvo());

    // Habilita as transições de cor só depois do primeiro desenho.
    window.addEventListener('load', () => {
        requestAnimationFrame(() => document.documentElement.classList.add('tema-pronto'));
    });
})();
