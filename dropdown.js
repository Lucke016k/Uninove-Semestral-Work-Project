/*
 * DROPDOWN PADRONIZADO
 *
 * Transforma todo <select> da página em um menu customizado (ícone + texto,
 * item selecionado em verde). O <select> original continua no DOM (escondido),
 * então valor, FormData, onchange e form.reset() seguem funcionando.
 *
 * Ícone de cada item (nesta ordem de prioridade):
 *   1. atributo data-icon na <option>
 *   2. mapa DD_ICONES abaixo (pelo texto da opção)
 *   3. atributo data-dd-icon no <select> (ícone padrão do menu)
 */
(function () {
    const DD_ICONES = {
        // Formas de pagamento
        'Pix': '💠', 'Crédito': '💳', 'Débito': '💳', 'Dinheiro': '💵',
        'VR': '🍽️', 'VA': '🍴', 'Ticket': '🎟️',
        // Parcelado
        'Sim': '✅', 'Não': '❌',
        // Categorias padrão
        'Alimentação': '🍔', 'Supermercado': '🛒', 'Vestuário': '👕', 'Lazer': '🎮',
        'Transporte': '🚌', 'Moradia': '🏠', 'Saúde': '💊', 'Outros': '📦',
        // Filtros
        'Todas as categorias': '🗂️'
    };

    const valorDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
    const CHEVRON = '<svg class="dd-chevron" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';

    let aberto = null; // dropdown atualmente aberto

    function iconeDe(sel, opt) {
        return (opt && opt.dataset.icon) || (opt && DD_ICONES[opt.textContent.trim()]) || sel.dataset.ddIcon || '';
    }

    function linha(sel, opt) {
        const ico = iconeDe(sel, opt);
        const el = document.createElement('span');
        el.className = 'dd-row';
        el.innerHTML = (ico ? `<span class="dd-ico"></span>` : '') + `<span class="dd-text"></span>`;
        if (ico) el.querySelector('.dd-ico').textContent = ico;
        el.querySelector('.dd-text').textContent = opt ? opt.textContent : '';
        return el;
    }

    function enhance(sel) {
        if (sel.dataset.dd) return;
        sel.dataset.dd = '1';

        const wrap = document.createElement('div');
        wrap.className = 'dd';
        sel.parentNode.insertBefore(wrap, sel);
        wrap.appendChild(sel);
        sel.classList.add('dd-native');
        sel.tabIndex = -1;

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'dd-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        wrap.insertBefore(trigger, sel);

        const lista = document.createElement('div');
        lista.className = 'dd-list';
        lista.setAttribute('role', 'listbox');
        lista.hidden = true;
        document.body.appendChild(lista);

        const st = { sel, wrap, trigger, lista, ativo: -1 };

        function refresh() {
            const opt = sel.options[sel.selectedIndex];
            trigger.innerHTML = '';
            trigger.appendChild(linha(sel, opt));
            trigger.insertAdjacentHTML('beforeend', CHEVRON);
            trigger.disabled = sel.disabled;
        }
        st.refresh = refresh;

        function montarLista() {
            lista.innerHTML = '';
            [...sel.options].forEach((opt, i) => {
                const it = document.createElement('div');
                it.className = 'dd-item' + (i === sel.selectedIndex ? ' selected' : '');
                it.setAttribute('role', 'option');
                it.dataset.i = i;
                it.appendChild(linha(sel, opt));
                it.addEventListener('mousedown', e => e.preventDefault());
                it.addEventListener('click', () => escolher(i));
                it.addEventListener('mousemove', () => marcar(i));
                lista.appendChild(it);
            });
        }

        function marcar(i) {
            st.ativo = i;
            [...lista.children].forEach((c, k) => c.classList.toggle('active', k === i));
            const c = lista.children[i];
            if (c) c.scrollIntoView({ block: 'nearest' });
        }

        function posicionar() {
            const r = trigger.getBoundingClientRect();
            lista.style.minWidth = r.width + 'px';
            lista.style.left = r.left + 'px';
            const h = Math.min(lista.scrollHeight, 280);
            const abaixo = window.innerHeight - r.bottom - 12;
            if (abaixo < h && r.top > abaixo) {
                lista.style.top = '';
                lista.style.bottom = (window.innerHeight - r.top + 6) + 'px';
                lista.style.maxHeight = Math.min(280, r.top - 12) + 'px';
            } else {
                lista.style.bottom = '';
                lista.style.top = (r.bottom + 6) + 'px';
                lista.style.maxHeight = Math.min(280, abaixo) + 'px';
            }
            const larguraMax = window.innerWidth - r.left - 12;
            lista.style.maxWidth = Math.max(r.width, larguraMax) + 'px';
        }

        function abrir() {
            if (sel.disabled) return;
            if (aberto && aberto !== st) aberto.fechar();
            montarLista();
            lista.hidden = false;
            wrap.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            posicionar();
            marcar(Math.max(0, sel.selectedIndex));
            aberto = st;
        }

        function fechar() {
            lista.hidden = true;
            wrap.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
            if (aberto === st) aberto = null;
        }
        st.fechar = fechar;
        st.posicionar = posicionar;

        function escolher(i) {
            const mudou = sel.selectedIndex !== i;
            sel.selectedIndex = i;
            refresh();
            fechar();
            trigger.focus();
            if (mudou) {
                sel.dispatchEvent(new Event('input', { bubbles: true }));
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        trigger.addEventListener('click', () => (lista.hidden ? abrir() : fechar()));
        trigger.addEventListener('keydown', e => {
            const n = sel.options.length;
            if (lista.hidden) {
                if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) {
                    e.preventDefault();
                    abrir();
                }
                return;
            }
            if (e.key === 'ArrowDown') { e.preventDefault(); marcar(Math.min(n - 1, st.ativo + 1)); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); marcar(Math.max(0, st.ativo - 1)); }
            else if (e.key === 'Home') { e.preventDefault(); marcar(0); }
            else if (e.key === 'End') { e.preventDefault(); marcar(n - 1); }
            else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); escolher(st.ativo); }
            else if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); fechar(); }
            else if (e.key === 'Tab') { fechar(); }
        });

        // Mudanças programáticas: select.value = ..., innerHTML, add(new Option) e reset().
        Object.defineProperty(sel, 'value', {
            configurable: true,
            get() { return valorDesc.get.call(sel); },
            set(v) { valorDesc.set.call(sel, v); refresh(); }
        });
        new MutationObserver(refresh).observe(sel, { childList: true, subtree: true, characterData: true });
        sel.addEventListener('change', refresh);
        if (sel.form) sel.form.addEventListener('reset', () => setTimeout(refresh, 0));

        // Mantém o rótulo (<label>) clicável.
        if (sel.id) {
            const lb = document.querySelector(`label[for="${sel.id}"]`);
            if (lb) lb.addEventListener('click', () => trigger.focus());
        }

        // Repassa a classe do select (ex.: .field) para o botão, para manter o layout.
        sel.className.split(/\s+/).forEach(c => { if (c && c !== 'dd-native') trigger.classList.add(c); });
        if (sel.classList.contains('field')) wrap.classList.add('dd-inline');

        st.refresh();
        sel._dd = st;
    }

    function varrer(raiz) {
        if (!raiz || raiz.nodeType !== 1) return;
        if (raiz.matches && raiz.matches('select')) enhance(raiz);
        raiz.querySelectorAll && raiz.querySelectorAll('select').forEach(enhance);
    }

    // Fecha ao clicar fora, rolar a página (fora da lista) ou redimensionar.
    document.addEventListener('mousedown', e => {
        if (aberto && !aberto.lista.contains(e.target) && !aberto.wrap.contains(e.target)) aberto.fechar();
    });
    window.addEventListener('resize', () => aberto && aberto.fechar());
    window.addEventListener('scroll', e => {
        if (aberto && !aberto.lista.contains(e.target)) aberto.fechar();
    }, true);
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && aberto) { e.stopPropagation(); aberto.fechar(); }
    }, true);

    // Aplica nos selects existentes e em todos que forem criados depois (render()).
    new MutationObserver(muts => {
        muts.forEach(m => m.addedNodes.forEach(varrer));
    }).observe(document.documentElement, { childList: true, subtree: true });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => varrer(document.body));
    } else {
        varrer(document.body);
    }
})();
