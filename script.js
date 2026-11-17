/*
 * CONTROLE FINANCEIRO | JavaScript da interface
 *
 * Lançamentos e categorias vêm do MySQL por meio da API PHP (pasta api/).
 * Metas e perfil ainda ficam no localStorage do navegador (protótipo).
 * Nunca coloque credenciais do MySQL neste arquivo.
 */

// ==========================================
// CONSTANTES (valores de demonstração)
// ==========================================

// Limite mensal de gastos e renda mensal usados nos cartões e na barra lateral.
const MONTHLY_LIMIT = 3500;
const MONTHLY_INCOME = 4500;

// Formas de pagamento exibidas na interface (os nomes são mapeados para o banco no PHP).
const PAYMENT_METHODS = ['Crédito', 'Débito', 'Pix', 'Dinheiro', 'VR', 'VA', 'Ticket'];

// Metas de exemplo. Na versão conectada, virão da tabela meta.
const defaultGoals = [
    { id: 1, name: 'Viagem dos Sonhos', icon: '✈️', target: 5000,  saved: 3000,  due: '2027-01-10' },
    { id: 2, name: 'Entrada do Apê',    icon: '🏠', target: 35000, saved: 12250, due: '2028-06-30' },
    { id: 3, name: 'Carro Novo',        icon: '🚗', target: 12000, saved: 2400,  due: '2027-10-10' },
    { id: 4, name: 'Pós-Graduação',     icon: '🎓', target: 20000, saved: 2000,  due: '2028-08-01' }
];

// ==========================================
// ESTADO
// ==========================================

let expenses = [];   // Vem de api/lancamentos/listar.php
let categories = []; // Vem de api/categorias/listar.php: { id, name, global }
let goals = [];
let aportes = [];    // Vem de api/metas/aportes.php: { id, goalId, goalName, goalIcon, value, source, date }
let income = { fixo: 0, variavel: 0 }; // Vem de api/receitas/resumo.php (mês atual)
let user = {
    name: (window.USUARIO && window.USUARIO.nome) || 'Usuário',
    email: (window.USUARIO && window.USUARIO.email) || ''
};
let page = 'home';
let editingId = null;
let paymentFilter = '';
let searchTerm = '';

// ==========================================
// UTILITÁRIOS
// ==========================================

// Atalho para buscar elemento por id.
function $(id) {
    return document.getElementById(id);
}

// Recupera dados do localStorage (usado só para metas e perfil).
function load(chave, padrao) {
    try {
        const valor = localStorage.getItem(chave);
        return valor ? JSON.parse(valor) : JSON.parse(JSON.stringify(padrao));
    } catch (erro) {
        return JSON.parse(JSON.stringify(padrao));
    }
}

// Salva metas e perfil no localStorage.
function save() {
    localStorage.setItem('cf_user', JSON.stringify(user));
}

// Formata números como valores monetários em Real brasileiro.
function money(v) {
    return Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

// Escapa caracteres HTML para evitar injeção ao montar trechos de HTML.
function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[c]));
}

// Data atual no formato AAAA-MM-DD, adequado para input type=date.
function todayISO() {
    return localISO(new Date());
}

// Converte AAAA-MM-DD para o formato brasileiro de exibição.
function prettyDate(s) {
    if (!s) return '—';
    return new Date(s + 'T12:00:00').toLocaleDateString('pt-BR');
}

// Soma o campo value de uma lista de lançamentos.
function total(arr) {
    return arr.reduce((soma, e) => soma + Number(e.value || 0), 0);
}

// ==========================================
// META DE GASTO (barra lateral e painel inicial)
// ==========================================

const SPEND_GOAL_MAX_DAYS = 30;

// Datas em AAAA-MM-DD usando o fuso do navegador (sem desvio de UTC).
function localISO(d) {
    const p = n => String(n).padStart(2, '0');
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
}
function parseISO(s) {
    const [y, m, d] = s.split('-').map(Number);
    return new Date(y, m - 1, d);
}
function addDaysISO(s, n) {
    const d = parseISO(s);
    d.setDate(d.getDate() + n);
    return localISO(d);
}

// Meta salva no navegador: { limit, start, days }. Padrão: dia 1 do mês atual, 30 dias.
let spendGoal = (function () {
    const hoje = new Date();
    const padrao = {
        limit: MONTHLY_LIMIT,
        start: localISO(new Date(hoje.getFullYear(), hoje.getMonth(), 1)),
        days: SPEND_GOAL_MAX_DAYS
    };
    const g = load('cf_spend_goal', padrao);
    const valida = g && Number(g.limit) > 0 && /^\d{4}-\d{2}-\d{2}$/.test(g.start || '')
        && Number.isInteger(Number(g.days)) && g.days >= 1 && g.days <= SPEND_GOAL_MAX_DAYS;
    return valida ? { limit: Number(g.limit), start: g.start, days: Number(g.days) } : padrao;
})();

// Soma os lançamentos dentro do período da meta e calcula o andamento.
function spendGoalInfo() {
    const fim = addDaysISO(spendGoal.start, spendGoal.days - 1);
    const hoje = localISO(new Date());
    const gasto = total(expenses.filter(e => {
        const d = String(e.date).slice(0, 10);
        return d >= spendGoal.start && d <= fim;
    }));
    const status = hoje < spendGoal.start ? 'futura' : hoje > fim ? 'encerrada' : 'ativa';
    const diasRestantes = status === 'ativa'
        ? Math.round((parseISO(fim) - parseISO(hoje)) / 86400000) + 1
        : 0;
    const pct = spendGoal.limit > 0 ? gasto / spendGoal.limit * 100 : 0;

    return { fim, gasto, status, diasRestantes, pct, restante: spendGoal.limit - gasto };
}

function spendGoalPeriodText(info) {
    const curto = s => s.split('-').reverse().slice(0, 2).join('/');
    const periodo = curto(spendGoal.start) + ' – ' + curto(info.fim);

    if (info.status === 'futura') return 'Começa em ' + curto(spendGoal.start);
    if (info.status === 'encerrada') return 'Período encerrado em ' + curto(info.fim);
    return periodo + ' · ' + info.diasRestantes + (info.diasRestantes === 1 ? ' dia restante' : ' dias restantes');
}

function spendGoalBarClass(pct) {
    return pct > 100 ? 'over' : pct >= 80 ? 'warn' : '';
}

// Nomes das categorias cadastradas no banco.
// ==========================================
// FILTRO DE PERÍODO (vale para lançamentos, categorias, relatórios e painel)
// ==========================================

const PERIOD_PRESETS = {
    month: 'Este mês',
    lastmonth: 'Mês passado',
    last30: 'Últimos 30 dias',
    year: 'Este ano',
    all: 'Todo o período'
};

// Período salvo no navegador: { preset } ou { preset: 'custom', start, end }.
let period = (function () {
    const p = load('cf_period', { preset: 'month' });
    const data = /^\d{4}-\d{2}-\d{2}$/;
    const valido = p && (PERIOD_PRESETS[p.preset] ||
        (p.preset === 'custom' && data.test(p.start || '') && data.test(p.end || '') && p.start <= p.end));

    return valido ? p : { preset: 'month' };
})();

// Datas inicial e final (AAAA-MM-DD) de cada atalho. "Todo o período" não tem limites.
function presetRange(id) {
    const hoje = new Date();
    const y = hoje.getFullYear();
    const m = hoje.getMonth();
    const faixa = {
        month: [new Date(y, m, 1), new Date(y, m + 1, 0)],
        lastmonth: [new Date(y, m - 1, 1), new Date(y, m, 0)],
        last30: [new Date(y, m, hoje.getDate() - 29), hoje],
        year: [new Date(y, 0, 1), new Date(y, 11, 31)]
    }[id];

    return faixa ? { start: localISO(faixa[0]), end: localISO(faixa[1]) } : { start: null, end: null };
}

function periodRange() {
    return period.preset === 'custom'
        ? { start: period.start, end: period.end }
        : presetRange(period.preset);
}

function inPeriod(data) {
    const d = String(data).slice(0, 10);
    const r = periodRange();

    return (!r.start || d >= r.start) && (!r.end || d <= r.end);
}

function periodExpenses() {
    return expenses.filter(e => inPeriod(e.date));
}

function periodAportes() {
    return aportes.filter(a => inPeriod(a.date));
}

// Botão "Exibindo gastos do período: ..." que abre o seletor de período.
function periodBar() {
    const r = periodRange();
    const texto = r.start
        ? 'Exibindo gastos do período: <strong>' + prettyDate(r.start) + ' – ' + prettyDate(r.end) + '</strong>'
        : 'Exibindo gastos de <strong>todo o período</strong>';

    return `
    <button type="button" class="period-btn" onclick="openPeriod()" title="Alterar o período exibido">
        <span class="period-ico" aria-hidden="true">📅</span>
        <span class="period-text">${texto}</span>
        <span class="period-change">Alterar</span>
    </button>`;
}

// Divide o período em até n faixas de datas iguais (usado nos gráficos).
function periodBuckets(n) {
    const r = periodRange();
    const datas = expenses.map(e => String(e.date).slice(0, 10)).sort();
    let ini = r.start || datas[0] || todayISO();
    let fim = r.end || datas[datas.length - 1] || todayISO();

    if (ini > fim) [ini, fim] = [fim, ini];

    const dias = Math.round((parseISO(fim) - parseISO(ini)) / 86400000) + 1;
    const qtd = Math.max(1, Math.min(n, dias));

    return Array.from({ length: qtd }, (_, i) => {
        const de = addDaysISO(ini, Math.floor(i * dias / qtd));
        const ate = addDaysISO(ini, Math.floor((i + 1) * dias / qtd) - 1);

        return { de, ate, label: de.slice(8) + '/' + de.slice(5, 7) };
    });
}

// Rascunho do popup: só vira o período ativo ao clicar em "Aplicar".
let periodDraft = null;

function renderPeriodForm() {
    const { preset, start, end } = periodDraft;

    document.querySelectorAll('#periodPresets [data-preset]').forEach(b => {
        b.classList.toggle('active', b.dataset.preset === preset);
    });

    $('periodHint').textContent = preset === 'all'
        ? 'Todos os lançamentos serão exibidos, sem limite de datas.'
        : (start && end && start <= end
            ? 'Serão exibidos os lançamentos de ' + prettyDate(start) + ' até ' + prettyDate(end) + '.'
            : 'Escolha a data inicial e a data final.');
}

function openPeriod() {
    const r = periodRange();

    periodDraft = { preset: period.preset, start: r.start || '', end: r.end || '' };

    const f = $('periodForm');
    f.elements.start.value = periodDraft.start;
    f.elements.end.value = periodDraft.end;

    renderPeriodForm();
    $('periodModalBack').classList.add('show');
}

function closePeriod() {
    $('periodModalBack').classList.remove('show');
}

document.querySelectorAll('#periodPresets [data-preset]').forEach(b => {
    b.onclick = () => {
        const r = presetRange(b.dataset.preset);
        const f = $('periodForm');

        periodDraft = { preset: b.dataset.preset, start: r.start || '', end: r.end || '' };
        f.elements.start.value = periodDraft.start;
        f.elements.end.value = periodDraft.end;
        renderPeriodForm();
    };
});

// Mexer nas datas troca o atalho para "personalizado".
$('periodForm').addEventListener('input', (e) => {
    if (!['start', 'end'].includes(e.target.name)) return;

    const f = $('periodForm');

    periodDraft = { preset: 'custom', start: f.elements.start.value, end: f.elements.end.value };
    renderPeriodForm();
});

$('closePeriod').onclick = $('cancelPeriod').onclick = closePeriod;
$('periodModalBack').addEventListener('click', (e) => {
    if (e.target.id === 'periodModalBack') closePeriod();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePeriod();
});

$('periodForm').onsubmit = (e) => {
    e.preventDefault();

    const { preset, start, end } = periodDraft;

    if (preset === 'all') {
        period = { preset: 'all' };
    } else if (preset === 'custom') {
        if (!start || !end) {
            toast('Escolha a data inicial e a data final.');
            return;
        }
        if (start > end) {
            toast('A data inicial deve ser anterior à data final.');
            return;
        }
        period = { preset: 'custom', start, end };
    } else {
        period = { preset };
    }

    localStorage.setItem('cf_period', JSON.stringify(period));
    closePeriod();
    render();
};

// ==========================================
// APORTES EM METAS (saem do saldo e entram nos relatórios)
// ==========================================

const SOURCE_LABEL = { FIXA: 'Lucro fixo', VARIAVEL: 'Lucro variável' };

function aportesMes(origem = null) {
    const mes = todayISO().slice(0, 7);

    return total(aportes.filter(a =>
        String(a.date).slice(0, 7) === mes && (!origem || a.source === origem)));
}

// Saldo do mês atual: lucros − gastos − aportes. Também devolve o que resta de cada lucro.
function saldoMes() {
    const mes = todayISO().slice(0, 7);
    const gastos = total(expenses.filter(e => String(e.date).slice(0, 7) === mes));

    return {
        gastos,
        saldo: income.fixo + income.variavel - gastos - aportesMes(),
        fixo: income.fixo - aportesMes('FIXA'),
        variavel: income.variavel - aportesMes('VARIAVEL')
    };
}

function catNames() {
    return categories.map(c => c.name);
}

// Exibe uma mensagem temporária na interface.
function toast(texto) {
    const el = $('toast');
    el.textContent = texto;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2400);
}

// ==========================================
// POPUP DE CONFIRMAÇÃO
// ==========================================

let editingCategoryId = null; // ID da categoria aberta no popup (null = nova)

/*
 * Mostra o popup de confirmação e devolve uma Promise<boolean>.
 * Com info:true mostra apenas o botão de OK (aviso), sempre devolvendo true.
 */
function confirmDialog({ title = 'Excluir item?', message = '', confirmLabel = 'Excluir', cancelLabel = 'Cancelar', info = false, neutral = false } = {}) {
    return new Promise(resolve => {
        const back = $('confirmModalBack');
        const ok = $('confirmOk');
        const cancel = $('confirmCancel');

        $('confirmTitle').textContent = title;
        $('confirmText').textContent = message;
        ok.textContent = confirmLabel;
        cancel.textContent = cancelLabel;
        cancel.hidden = info;
        back.classList.toggle('info', info);
        back.classList.toggle('neutral', neutral);

        const finalizar = (resposta) => {
            back.classList.remove('show');
            document.removeEventListener('keydown', aoTeclar);
            back.removeEventListener('click', aoClicarFora);
            resolve(resposta);
        };

        const aoTeclar = (e) => {
            if (e.key === 'Escape') finalizar(info);
        };
        const aoClicarFora = (e) => {
            if (e.target === back) finalizar(info);
        };

        ok.onclick = () => finalizar(true);
        cancel.onclick = () => finalizar(false);
        document.addEventListener('keydown', aoTeclar);
        back.addEventListener('click', aoClicarFora);

        back.classList.add('show');
        (info ? ok : cancel).focus();
    });
}

// ==========================================
// API (PHP)
// ==========================================

/*
 * Chama um endpoint PHP e devolve o JSON.
 * Sem "dados" faz GET; com "dados" (FormData ou objeto simples) faz POST.
 * Lança Error com a mensagem do PHP quando a operação falha.
 */
async function callApi(url, dados = null) {
    const opcoes = {};

    if (dados) {
        const corpo = dados instanceof FormData ? dados : new FormData();

        if (!(dados instanceof FormData)) {
            Object.entries(dados).forEach(([k, v]) => corpo.append(k, v));
        }

        opcoes.method = 'POST';
        opcoes.body = corpo;
    }

    const resposta = await fetch(url, opcoes);
    const texto = await resposta.text();
    let resultado;

    try {
        resultado = JSON.parse(texto);
    } catch (erro) {
        console.error('Resposta do PHP (' + url + ', HTTP ' + resposta.status + '):', texto);
        throw new Error(
            resposta.status === 404
                ? 'Arquivo não encontrado no servidor: ' + url
                : 'O PHP retornou uma resposta inválida (' + url + ', HTTP ' + resposta.status + ').'
        );
    }

    if (!resposta.ok || !resultado.sucesso) {
        throw new Error(resultado.erro || 'Não foi possível concluir a operação.');
    }

    return resultado;
}

// Carrega os dados do usuário autenticado pela sessão PHP.
async function loadUserFromSession() {
    try {
       const resultado = await callApi('PaginaLogin/meu_perfil.php');

        user = {
            name: resultado.usuario.nome,
            email: resultado.usuario.email
        };

        $('userLabel').textContent = user.name || 'Usuário';

        render();

    } catch (erro) {
        console.error('Erro ao carregar perfil:', erro);
        toast('Não foi possível carregar os dados do usuário.');
    }
}

// Carrega os lançamentos do MySQL e atualiza a interface.
async function loadExpensesFromDatabase() {
    try {
        const resultado = await callApi('api/lancamentos/listar.php');

        expenses = resultado.lancamentos;
        render();
        updateMini();
    } catch (erro) {
        console.error('Erro ao carregar gastos:', erro);
        toast(erro.message);
    }
}

// Carrega o total de lucros fixos e variáveis do mês atual.
async function loadIncomeFromDatabase() {
    try {
        const resultado = await callApi('api/receitas/resumo.php?mes=' + todayISO().slice(0, 7));

        income = {
            fixo: Number(resultado.fixo) || 0,
            variavel: Number(resultado.variavel) || 0
        };
        render();
    } catch (erro) {
        console.error('Erro ao carregar lucros:', erro);
        toast(erro.message);
    }
}

// Renda mensal usada no saldo estimado: lucros lançados ou, sem lançamentos, o valor de demonstração.
function monthlyIncome() {
    const totalLucros = income.fixo + income.variavel;

    return totalLucros > 0 ? totalLucros : MONTHLY_INCOME;
}

// Carrega os aportes feitos em metas (valores que saíram do saldo).
async function loadAportesFromDatabase() {
    try {
        const resultado = await callApi('api/metas/aportes.php');

        aportes = resultado.aportes.map(a => ({ ...a, value: Number(a.value) }));
        render();
    } catch (erro) {
        console.error('Erro ao carregar aportes:', erro);
        toast(erro.message);
    }
}

// Carrega as categorias do MySQL. Sem categorias cadastradas, a lista fica vazia.
async function loadCategoriesFromDatabase() {
    try {
        const resultado = await callApi('api/categorias/listar.php');

        categories = resultado.categorias;
        render();
    } catch (erro) {
        console.error('Erro ao carregar categorias:', erro);
        toast(erro.message);
    }
}

// ==========================================
// COMPONENTES DE INTERFACE
// ==========================================

// Cabeçalho reutilizável das páginas internas.
function head(title, sub, button = '') {
    return `
    <div class="pagehead">
        <div><h1>${title}</h1><p>${sub}</p></div>
        ${button}
    </div>`;
}

// Cartão de indicador/resumo.
function stat(label, value, note, ico, negativo = false) {
    return `
    <div class="card">
        <div class="stat-label"><span>${ico}</span>${label}</div>
        <div class="stat-value${negativo ? ' negative' : ''}">${value}</div>
        <div class="stat-note">${note}</div>
    </div>`;
}

// Ícones SVG usados nos botões de ação.
const ICON_EDIT = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
const ICON_TRASH = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';

// Ícones antigos de metas (já salvos no banco) convertidos para os novos.
const LEGACY_GOAL_ICONS = { '✈': '✈️', '⌂': '🏠', '▤': '🎓', '◎': '🎯' };
function goalIcon(i) {
    return LEGACY_GOAL_ICONS[i] || i || '🎯';
}

// Ícone visual de cada categoria (apenas decorativo).
function iconFor(c) {
    return ({
        Alimentação: '♨',
        Supermercado: '▣',
        Vestuário: '◇',
        Lazer: '◉',
        Transporte: '↗',
        Moradia: '⌂',
        Saúde: '✚',
        Outros: '◆'
    })[c] || '◆';
}

// Texto da coluna "Parcelado": "12x de R$ 100,00" (valor dividido pelas parcelas, só para exibição).
function installmentLabel(e) {
    const n = parseInt(e.installments, 10);

    return n > 0
        ? esc(n + 'x de ' + money(Number(e.value) / n))
        : esc(e.installments);
}

// Linhas da tabela de lançamentos, com ações de editar e excluir.
function expenseRows(arr) {
    if (!arr.length) {
        return `<tr><td colspan="8" class="empty">Nenhum lançamento encontrado.</td></tr>`;
    }

    return arr.map(e => `
        <tr>
            <td>
                <div class="item-name">
                    <span class="cat-icon">${iconFor(e.category)}</span>
                    <span>${esc(e.description)}</span>
                </div>
            </td>
            <td>${money(e.value)}</td>
            <td>${prettyDate(e.date)}</td>
            <td>${esc(e.who)}</td>
            <td><span class="tag tag-nowrap">${installmentLabel(e)}</span></td>
            <td><span class="tag">${esc(e.payment)}</span></td>
            <td>${esc(e.category)}</td>
            <td class="actions">
                <button class="btn-icon edit" title="Editar lançamento" aria-label="Editar lançamento" onclick="editExpense(${e.id})">${ICON_EDIT}</button>
                <button class="btn-icon delete" title="Excluir lançamento" aria-label="Excluir lançamento" onclick="deleteExpense(${e.id})">${ICON_TRASH}</button>
            </td>
        </tr>`).join('');
}

// Tabela completa de lançamentos.
function expenseTable(arr) {
    return `
    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>DESCRIÇÃO</th>
                    <th>VALOR</th>
                    <th>DATA</th>
                    <th>QUEM GASTOU</th>
                    <th>PARCELADO</th>
                    <th>PAGAMENTO</th>
                    <th>CATEGORIA</th>
                    <th>AÇÕES</th>
                </tr>
            </thead>
            <tbody>${expenseRows(arr)}</tbody>
        </table>
    </div>`;
}

// Tabela compacta dos lançamentos mais recentes (painel inicial).
function expenseTableMini(arr) {
    const linhas = arr.length
        ? arr.map(e => `
            <tr>
                <td>
                    <div class="item-name">
                        <span class="cat-icon">${iconFor(e.category)}</span>
                        ${esc(e.description)}
                    </div>
                </td>
                <td>${prettyDate(e.date)}</td>
                <td><strong>${money(e.value)}</strong></td>
            </tr>`).join('')
        : `<tr><td colspan="3" class="empty">Sem lançamentos ainda.</td></tr>`;

    return `
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>LANÇAMENTO</th>
                    <th>DATA</th>
                    <th>VALOR</th>
                </tr>
            </thead>
            <tbody>${linhas}</tbody>
        </table>
    </div>`;
}

// Barras de gastos por categoria, a partir dos lançamentos carregados.
function categoryBars() {
    const pe = periodExpenses();
    const sums = catNames()
        .map(nome => ({ name: nome, value: total(pe.filter(e => e.category === nome)) }))
        .filter(x => x.value > 0)
        .sort((a, b) => b.value - a.value);

    if (!sums.length) {
        return '<p class="muted">Adicione lançamentos para ver os dados.</p>';
    }

    const max = Math.max(...sums.map(x => x.value));

    return `
    <div class="bar-list">
        ${sums.slice(0, 6).map(x => `
        <div class="bar-row">
            <span>${esc(x.name)}</span>
            <div class="bar"><span style="width:${x.value / max * 100}%"></span></div>
            <strong>${money(x.value)}</strong>
        </div>`).join('')}
    </div>`;
}

// ==========================================
// NAVEGAÇÃO E FILTROS
// ==========================================

// Cabeçalho: data do dia e nome do usuário.
$('today').textContent = new Date().toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
$('userLabel').textContent = user.name || 'Usuário';

// Marca o botão ativo do menu principal.
function markActiveNav(p) {
    document.querySelectorAll('#nav button').forEach(b => {
        b.classList.toggle('active', b.dataset.page === p);
    });
}

// Troca a tela ativa, limpa o filtro de pagamento e redesenha o conteúdo.
function setPage(p) {
    page = p;
    paymentFilter = '';
    markActiveNav(p);
    render();
}

// Menu principal.
document.querySelectorAll('[data-page]').forEach(b => {
    b.onclick = () => setPage(b.dataset.page);
});

// Filtros rápidos por forma de pagamento (menu lateral).
document.querySelectorAll('[data-filter-pay]').forEach(b => {
    b.onclick = () => {
        paymentFilter = b.dataset.filterPay;
        page = 'expenses';
        markActiveNav('expenses');
        render();
    };
});

// Aplica o filtro de pagamento e o texto pesquisado.
function filtered() {
    const termo = searchTerm.toLowerCase();

    return expenses.filter(e => {
        const okPagamento = !paymentFilter || e.payment === paymentFilter;
        const texto = (e.description + ' ' + e.category + ' ' + e.who + ' ' + e.payment).toLowerCase();

        return inPeriod(e.date) && okPagamento && (!termo || texto.includes(termo));
    });
}

// Redesenha só a tabela de lançamentos aplicando pesquisa, categoria e o período global.
function refreshExpenseTable() {
    const categoria = $('categoryFilter').value;

    const arr = filtered()
        .filter(e => !categoria || e.category === categoria)
        .sort((a, b) => b.date.localeCompare(a.date));

    $('expenseTable').innerHTML = expenseTable(arr);
    $('expenseStats').innerHTML = expenseStats(arr);
}

// Limpa os filtros e redesenha a tela de lançamentos.
function resetFilters() {
    searchTerm = '';
    paymentFilter = '';
    render();
}

// ==========================================
// PÁGINAS
// ==========================================

// Painel inicial.
function homePage() {
    const pe = periodExpenses();
    const sum = total(pe);
    const monthSum = total(expenses.filter(e => e.date.slice(0, 7) === todayISO().slice(0, 7)));
    const recentes = pe.slice().sort((a, b) => b.date.localeCompare(a.date)).slice(0, 5);
    const meta = spendGoalInfo();

    return `
    ${head(
        'Visão geral',
        'Acompanhe suas finanças e mantenha seus objetivos em dia.',
        `<button class="btn primary" onclick="openExpense()">＋ Novo lançamento</button>`)}
    ${periodBar()}
    ${incomeCards()}
    <div class="grid stats">
        ${stat('Gastos registrados', money(sum), 'No período selecionado', '▥')}
        ${stat('Gastos do mês', money(monthSum), 'Este mês', '▤')}
        ${stat('Saldo estimado', money(saldoMes().saldo), 'Lucros do mês − gastos − aportes em metas', '◉', saldoMes().saldo < 0)}
        ${stat('Lançamentos', pe.length, 'No período selecionado', '☷')}
    </div>
    <div class="grid two-col">
        <div class="card">
            <h3 class="section-title">Últimos lançamentos</h3>
            ${expenseTableMini(recentes)}
            <div style="margin-top:12px">
                <button class="btn" onclick="setPage('expenses')">Ver todos os lançamentos →</button>
            </div>
        </div>
        <div class="grid">
            <div class="card">
                <h3 class="section-title">Gastos por categoria</h3>
                ${categoryBars()}
            </div>
            <div class="card">
                <h3 class="section-title">Meta de gasto</h3>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="muted" style="font-size:11px">Limite de gastos</span>
                    <strong style="color:var(--green)">${money(spendGoal.limit)}</strong>
                </div>
                <div class="progress" style="margin:12px 0">
                    <span class="${spendGoalBarClass(meta.pct)}" style="width:${Math.min(100, meta.pct)}%"></span>
                </div>
                <small class="muted">${money(meta.gasto)} gastos · ${meta.restante >= 0 ? money(meta.restante) + ' disponíveis' : money(-meta.restante) + ' acima do limite'}</small>
                <small class="muted" style="display:block;margin-top:4px">${spendGoalPeriodText(meta)}</small>
                <button class="btn" style="margin-top:12px" onclick="openSpendGoal()">Editar meta</button>
            </div>
        </div>
    </div>`;
}

// Cards de lucro fixo e variável. Clicar em um deles abre o popup de edição.
function incomeCards(extra = '') {
    const iconFixo = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/></svg>';
    const iconVar = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 21V11"/><path d="M12 21V4"/><path d="M19 21v-7"/></svg>';

    const card = (tipo, label, valor, ico) => `
        <button type="button" class="card income-card" onclick="openIncome('${tipo}')" title="Clique para editar" aria-label="Editar ${label}">
            <span class="income-ico">${ico}</span>
            <span class="income-info">
                <span class="income-label">${label}</span>
                <strong class="income-value">${money(valor)}</strong>
                <small class="income-note">Este mês · clique para editar</small>
            </span>
            <span class="income-edit">${ICON_EDIT}</span>
        </button>`;

    return `
    <div class="income-row${extra ? ' with-extra' : ''}">
        ${card('FIXA', 'Lucros Fixos', income.fixo, iconFixo)}
        ${card('VARIAVEL', 'Lucros Variáveis', income.variavel, iconVar)}
        ${extra}
    </div>`;
}

// Cards de indicadores da tela de lançamentos (seguem os filtros aplicados).
function expenseStats(arr) {
    const totalFiltrado = total(arr);
    const saldo = saldoMes().saldo;

    // Forma de pagamento com maior gasto dentro do filtro.
    const porPagamento = {};
    arr.forEach(e => {
        const nome = e.payment || 'Sem forma';
        porPagamento[nome] = (porPagamento[nome] || 0) + Number(e.value || 0);
    });
    const topo = Object.entries(porPagamento).sort((a, b) => b[1] - a[1])[0];

    return `
        ${stat('Total filtrado', money(totalFiltrado), 'Soma dos lançamentos exibidos', '▥')}
        ${stat('Quantidade', arr.length, 'Lançamentos encontrados', '☷')}
        ${stat('Saldo estimado', money(saldo), 'Lucros do mês − gastos − aportes em metas', '◉', saldo < 0)}
        ${stat('Maior gasto por forma', topo ? esc(topo[0]) : '—',
            topo ? money(topo[1]) + ' · ' + Math.round(topo[1] / totalFiltrado * 100) + '% do total filtrado' : 'Sem lançamentos no filtro', '▦')}`;
}

// Tela de lançamentos, indicadores e controles de filtro.
function expensesPage() {
    const arr = filtered().slice().sort((a, b) => b.date.localeCompare(a.date));
    const catSelecionada = new URLSearchParams(location.search).get('cat');

    return `
    ${head(
        'Lançamentos',
        'Organize e acompanhe todas as suas despesas.',
        `<button class="btn primary" onclick="openExpense()">＋ Novo lançamento</button>`)}
    ${periodBar()}
    ${incomeCards()}
    <div class="grid stats" id="expenseStats">${expenseStats(arr)}</div>
    <div class="toolbar">
        <input class="field search" id="searchExpense" placeholder="⌕ Pesquisar por nome, gasto, categoria..." value="${esc(searchTerm)}">
        <select class="field" id="categoryFilter" data-dd-icon="🏷️">
            <option value="">Todas as categorias</option>
            ${catNames().map(c => `<option${catSelecionada === c ? ' selected' : ''}>${esc(c)}</option>`).join('')}
        </select>
        ${paymentFilter ? `<button class="chip active" onclick="paymentFilter='';render()">${esc(paymentFilter)} ×</button>` : ''}
        <button class="btn" onclick="resetFilters()">Limpar filtros</button>
    </div>
    <div id="expenseTable">${expenseTable(arr)}</div>`;
}

// Relatórios e gráficos. Os totais são derivados dos lançamentos.
function reportsPage() {
    const pe = periodExpenses();
    const ap = periodAportes();
    const sum = total(pe);

    const byPay = PAYMENT_METHODS
        .map(p => ({ p, v: total(pe.filter(e => e.payment === p)) }))
        .filter(x => x.v);

    const top = catNames()
        .map(c => ({ c, v: total(pe.filter(e => e.category === c)) }))
        .sort((a, b) => b.v - a.v)
        .slice(0, 5);

    return `
    ${head(
        'Relatórios e gráficos',
        'Visualize seus gastos e entenda para onde vai seu dinheiro.')}
    ${periodBar()}
    <div class="grid stats">
        ${stat('Total de gastos', money(sum), 'No período selecionado', '↓')}
        ${stat('Aportes em metas', money(total(ap)), ap.length + (ap.length === 1 ? ' aporte' : ' aportes') + ' no período', '◎')}
        ${stat('Média por lançamento', money(pe.length ? sum / pe.length : 0), 'Valor médio', '◉')}
        ${stat('Categorias utilizadas', new Set(pe.map(e => e.category)).size, 'Com gastos registrados', '◆')}
    </div>
    <div class="grid two-col">
        <div class="card">
            <h3 class="section-title">Evolução financeira</h3>
            <canvas class="chart" id="lineChart"></canvas>
            <div class="chart-legend">
                <span><i class="dot" style="background:#315f47"></i>Gastos</span>
                <span><i class="dot" style="background:#a6b99e"></i>Limite estimado</span>
            </div>
        </div>
        <div class="card">
            <h3 class="section-title">Gastos por categoria</h3>
            ${categoryBars()}
            <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
            <h3 class="section-title">Forma de pagamento</h3>
            ${byPay.length ? byPay.map(x => `
                <div class="payrow">
                    <span><span class="cat-icon" style="display:inline-grid;vertical-align:middle;margin-right:8px;width:27px;height:27px">▣</span>${x.p}</span>
                    <strong>${money(x.v)}</strong>
                </div>`).join('') : '<p class="muted">Sem dados disponíveis.</p>'}
        </div>
    </div>
    <div class="grid two-col" style="margin-top:14px">
        <div class="card">
            <h3 class="section-title">Despesas por período</h3>
            <canvas class="chart" id="barChart"></canvas>
        </div>
        <div class="card">
            <h3 class="section-title">Maiores categorias</h3>
            ${top.map((x, i) => `
                <div class="payrow">
                    <span><span class="tag">${i + 1}</span> ${esc(x.c)}</span>
                    <strong>${money(x.v)}</strong>
                </div>`).join('')}
        </div>
    </div>
    <div class="card" style="margin-top:14px">
        <h3 class="section-title">Aportes em metas</h3>
        ${aporteList(ap)}
    </div>`;
}

// Lista dos aportes feitos em metas (valores que saíram do saldo).
function aporteList(arr) {
    if (!arr.length) {
        return '<p class="muted">Nenhum aporte em metas neste período.</p>';
    }

    return arr.map(a => `
        <div class="payrow">
            <span>
                <span class="cat-icon" style="display:inline-grid;vertical-align:middle;margin-right:8px;width:27px;height:27px">${esc(goalIcon(a.goalIcon))}</span>${esc(a.goalName)}
                <small class="muted">· ${prettyDate(a.date)} · ${SOURCE_LABEL[a.source] || ''}</small>
            </span>
            <strong>${money(a.value)}</strong>
        </div>`).join('');
}

// Desenha os gráficos nos canvas usando os lançamentos em memória.
// idx 0 = linha e idx 1 = barras; ambos usam faixas de datas do período escolhido.
function drawCharts() {
    ['lineChart', 'barChart'].forEach((id, idx) => {
        const canvas = $(id);
        if (!canvas) return;

        const rect = canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;

        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;

        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);

        const w = rect.width;
        const h = rect.height;
        const pad = 28;

        ctx.clearRect(0, 0, w, h);

        // Linhas de grade.
        for (let i = 0; i < 4; i++) {
            const y = pad + (h - pad * 1.5) * i / 3;
            ctx.strokeStyle = '#e8e9df';
            ctx.beginPath();
            ctx.moveTo(pad, y);
            ctx.lineTo(w - 8, y);
            ctx.stroke();
        }

        // O período escolhido é dividido em faixas de datas iguais.
        const faixas = periodBuckets(idx ? 5 : 7);
        const labels = faixas.map(f => f.label);

        const vals = faixas.map(f => total(expenses.filter(e => {
            const d = String(e.date).slice(0, 10);

            return d >= f.de && d <= f.ate;
        })));

        const max = Math.max(1, ...vals) * 1.2;
        const xx = i => pad + (w - pad - 12) * (i / (vals.length - 1 || 1));
        const yy = v => h - pad - (h - pad * 1.5) * v / max;

        const desenharRotulo = i => {
            ctx.fillStyle = '#7d887b';
            ctx.font = '10px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(labels[i], xx(i), h - 5);
        };

        if (idx) {
            // Barras.
            const bw = (w - pad - 12) / vals.length * 0.48;

            vals.forEach((v, i) => {
                ctx.fillStyle = '#52765e';
                ctx.fillRect(xx(i) - bw / 2, yy(v), bw, h - pad - yy(v));
                desenharRotulo(i);
            });
        } else {
            // Linha com pontos.
            ctx.beginPath();
            vals.forEach((v, i) => (i ? ctx.lineTo(xx(i), yy(v)) : ctx.moveTo(xx(i), yy(v))));
            ctx.strokeStyle = '#315f47';
            ctx.lineWidth = 2;
            ctx.stroke();

            vals.forEach((v, i) => {
                ctx.beginPath();
                ctx.arc(xx(i), yy(v), 3, 0, Math.PI * 2);
                ctx.fillStyle = '#315f47';
                ctx.fill();
                desenharRotulo(i);
            });
        }
    });
}

// Gerenciamento de categorias, uso e totais por categoria.
function categoriesPage() {
    const names = catNames();
    const pe = periodExpenses();

    const linhas = categories.length
        ? categories.map(c => `
            <tr>
                <td>
                    <div class="item-name">
                        <span class="cat-icon">${iconFor(c.name)}</span>
                        <strong>${esc(c.name)}</strong>
                    </div>
                </td>
                <td>${pe.filter(e => e.category === c.name).length}</td>
                <td>${money(total(pe.filter(e => e.category === c.name)))}</td>
                <td><span class="tag">Ativa</span></td>
                <td class="actions">${c.global
                    ? '<span class="muted" style="font-size:11px">Padrão do sistema</span>'
                    : `<button class="btn-icon edit" onclick="renameCategory(${c.id})" title="Editar categoria" aria-label="Editar categoria">${ICON_EDIT}</button>
                       <button class="btn-icon delete" onclick="removeCategory(${c.id})" title="Excluir categoria" aria-label="Excluir categoria">${ICON_TRASH}</button>`}
                </td>
            </tr>`).join('')
        : `<tr><td colspan="5" class="empty">Nenhuma categoria cadastrada. Clique em "Nova categoria" para começar.</td></tr>`;

    return `
    ${head(
        'Categorias',
        'Gerencie as categorias usadas para organizar seus gastos.',
        `<button class="btn primary" onclick="addCategory()">＋ Nova categoria</button>`)}
    ${periodBar()}
    <div class="grid stats">
        ${stat('Categorias', categories.length, 'Cadastradas', '◆')}
        ${stat('Com movimentação', names.filter(c => pe.some(e => e.category === c)).length, 'No período', '▥')}
        ${stat('Sem movimentação', names.filter(c => !pe.some(e => e.category === c)).length, 'Sem gastos no período', '◉')}
        ${stat('Total de gastos', money(total(pe)), 'No período selecionado', '↓')}
    </div>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>CATEGORIA</th>
                        <th>LANÇAMENTOS</th>
                        <th>TOTAL GASTO</th>
                        <th>STATUS</th>
                        <th>AÇÕES</th>
                    </tr>
                </thead>
                <tbody>${linhas}</tbody>
            </table>
        </div>
    </div>`;
}

// Metas e progresso. Os cards de lucro vêm de api/receitas/resumo.php.
function goalsPage() {
    const lista = goals.length
        ? goals.map(g => {
            const p = Math.min(100, g.saved / g.target * 100);

            return `
            <div class="goal-line">
                <div class="bigicon">${esc(goalIcon(g.icon))}</div>
                <div>
                    <strong>${esc(g.name)}</strong>
                    <small>${money(g.saved)} guardados de ${money(g.target)}</small>
                    <div class="progress" style="margin-top:9px"><span style="width:${p}%"></span></div>
                </div>
                <div class="goal-amount">
                    <strong>${money(g.target)}</strong>
                    <small>${prettyDate(g.due)}</small>
                    <span class="tag">${Math.round(p)}% concluído</span>
                </div>
                <div class="actions">
                    <button class="btn-icon edit" onclick="editGoal(${g.id})" title="Editar meta" aria-label="Editar meta"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>
                    <button class="btn-icon delete" onclick="deleteGoal(${g.id})" title="Excluir meta" aria-label="Excluir meta"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg></button>
                </div>
            </div>`;
        }).join('')
        : '<div class="empty">Você ainda não criou metas.</div>';

    return `
    ${head(
        'Metas',
        'Defina seus objetivos e acompanhe seu progresso.',
        `<button class="btn primary" onclick="openGoal()">＋ Criar nova meta</button>`)}
    ${incomeCards(stat('Saldo estimado', money(saldoMes().saldo), 'Lucros do mês − gastos − aportes em metas', '◉', saldoMes().saldo < 0))}
    <div class="card">
        <h3 class="section-title">Minhas metas</h3>
        <p class="muted" style="font-size:11px;margin-top:-7px">Acompanhe o progresso das suas metas e mantenha o foco nos seus objetivos.</p>
        ${lista}
    </div>`;
}

// Preferências, perfil e ações de exportação/limpeza.
function settingsPage() {
    return `
    ${head('Configurações', 'Personalize sua experiência e gerencie suas preferências.')}
    <div class="grid settings-layout">
        <div class="grid">
            <div class="card">
                <h3 class="section-title">Preferências do sistema</h3>
                <div class="setting-row">
                    <div><strong>Aparência</strong><small>Escolha o tema visual do aplicativo</small></div>
                    <span class="tag">Bege + verde</span>
                </div>
                <div class="setting-row">
                    <div><strong>Excluir Lançamentos</strong><small>Apaga todos os seus lançamentos.</small></div>
                    <button type="button" onclick="deleteAllExpenses()" class="btn btn-danger"> <i class="bi bi-trash"></i>Excluir lançamentos </button>
                </div>
                <div class="setting-row">
                    <div><strong>Excluir Metas</strong><small>Apaga todas as metas.</small></div>
                    <button type="button" onclick="deleteAllGoals()" class="btn btn-danger"> <i class="bi bi-trash"></i>Excluir metas </button>
                </div>
                
            </div>
        </div>
        <div class="grid">
            <div class="card profile">
                <div class="avatar">♙</div>
                <h3>Minha conta</h3>
                <p class="muted" style="font-size:11px">Gerencie suas informações pessoais e de acesso.</p>
                <div class="setting-row">
                    <div><strong>Nome</strong><small>${esc(user.name || 'Usuário')}</small></div>
                    <button class="btn" onclick="editProfile()">Editar</button>
                </div>
                <div class="setting-row">
                    <div><strong>E-mail</strong><small>${esc(user.email || 'Não informado')}</small></div>
                </div>
                <button class="btn" style="width:100%;margin-top:12px" onclick="editProfile()">Editar perfil</button>
            </div>
            <div class="card">
                <h3 class="section-title">Sessão</h3>
                <p class="muted" style="font-size:12px">Você está usando o protótipo local do Controle Financeiro.</p>
                <button class="btn danger" onclick="window.location.href='PaginaLogin/logout.php'">Sair da conta</button>
            </div>
        </div>
    </div>`;
}

// ==========================================
// RENDERIZAÇÃO
// ==========================================

// Desenha a página atual e registra os eventos dos filtros e gráficos.
function render() {
    const paginas = {
        home: homePage,
        expenses: expensesPage,
        reports: reportsPage,
        categories: categoriesPage,
        goals: goalsPage,
        settings: settingsPage
    };

    $('page').innerHTML = (paginas[page] || homePage)();

    if (page === 'expenses') {
        $('searchExpense').oninput = e => {
            searchTerm = e.target.value;
            refreshExpenseTable();
        };
        $('categoryFilter').onchange = refreshExpenseTable;
    }

    if (page === 'reports') {
        drawCharts();
    }
}

// Atualiza a barra lateral com o gasto do mês e o limite mensal.
function updateMini() {
    const info = spendGoalInfo();
    const barra = $('mini-progress');

    $('mini-goal').textContent = money(info.gasto) + ' de ' + money(spendGoal.limit);
    barra.style.width = Math.min(100, info.pct) + '%';
    barra.className = spendGoalBarClass(info.pct);
    $('mini-note').textContent = info.restante >= 0
        ? money(info.restante) + ' restantes'
        : money(-info.restante) + ' acima do limite';
    $('mini-period').textContent = spendGoalPeriodText(info);
}

// ---- Popup para definir limite, início e duração (até 30 dias) ----
function spendGoalFormHint() {
    const f = $('spendGoalForm');
    const dias = Number(f.elements.days.value);
    const inicio = f.elements.start.value;

    if (!inicio || !Number.isInteger(dias) || dias < 1 || dias > SPEND_GOAL_MAX_DAYS) {
        $('spendGoalHint').textContent = 'Escolha de 1 a ' + SPEND_GOAL_MAX_DAYS + ' dias de duração.';
        return;
    }
    const fim = addDaysISO(inicio, dias - 1);
    $('spendGoalHint').textContent = 'A meta vale de ' + prettyDate(inicio) + ' até ' + prettyDate(fim) +
        ' (' + dias + (dias === 1 ? ' dia' : ' dias') + '). Os lançamentos desse período entram na soma.';
}

function openSpendGoal() {
    const f = $('spendGoalForm');

    f.elements.limit.value = spendGoal.limit;
    f.elements.start.value = spendGoal.start;
    f.elements.days.value = spendGoal.days;
    spendGoalFormHint();
    $('spendGoalModalBack').classList.add('show');
    f.elements.limit.focus();
}

function closeSpendGoal() {
    $('spendGoalModalBack').classList.remove('show');
}

$('closeSpendGoal').onclick = $('cancelSpendGoal').onclick = closeSpendGoal;
$('spendGoalModalBack').addEventListener('click', (e) => {
    if (e.target.id === 'spendGoalModalBack') closeSpendGoal();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeSpendGoal();
});
$('spendGoalForm').addEventListener('input', spendGoalFormHint);

$('spendGoalForm').onsubmit = (e) => {
    e.preventDefault();

    const f = e.currentTarget;
    const limite = Number(f.elements.limit.value);
    const dias = Number(f.elements.days.value);

    if (!(limite > 0)) {
        toast('Informe um limite de gastos maior que zero.');
        return;
    }
    if (!f.elements.start.value) {
        toast('Informe a data de início.');
        return;
    }
    if (!Number.isInteger(dias) || dias < 1 || dias > SPEND_GOAL_MAX_DAYS) {
        toast('A duração deve ser de 1 a ' + SPEND_GOAL_MAX_DAYS + ' dias.');
        return;
    }

    spendGoal = { limit: limite, start: f.elements.start.value, days: dias };
    localStorage.setItem('cf_spend_goal', JSON.stringify(spendGoal));

    closeSpendGoal();
    updateMini();
    render();
    toast('Meta de gasto atualizada.');
};

$('goalMini').addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openSpendGoal();
    }
});

// ==========================================
// LANÇAMENTOS (criar, editar, excluir)
// ==========================================

// Abre o formulário de lançamento. Se receber ID, preenche os campos para edição.
function openExpense(id = null) {
    // Sem categorias no banco não há como lançar um gasto: leva o usuário a cadastrar a primeira.
    if (!categories.length) {
        confirmDialog({
            title: 'Nenhuma categoria cadastrada',
            message: 'Para registrar um lançamento você precisa ter pelo menos uma categoria. Deseja criar uma agora?',
            confirmLabel: 'Sim, criar agora',
            cancelLabel: 'Não, criar mais tarde',
            neutral: true
        }).then(criar => {
            if (criar) {
                setPage('categories');
                addCategory();
            }
        });
        return;
    }

    editingId = id;

    const f = $('expenseForm');
    f.reset();

    $('expenseCategory').innerHTML = categories.map(c => `<option>${esc(c.name)}</option>`).join('');
    f.elements.date.value = todayISO();
    f.elements.who.value = user.name || 'Eu';
    $('modalTitle').textContent = id ? 'Editar lançamento' : 'Novo lançamento';

    if (id) {
        const e = expenses.find(x => x.id === id);

        if (e) {
            f.elements.description.value = e.description;
            f.elements.value.value = e.value;
            f.elements.date.value = e.date;
            f.elements.category.value = e.category;
            f.elements.payment.value = e.payment;
            f.elements.who.value = e.who;
            // Parcelado chega como texto ("12x"); "Não" quando é à vista.
            const parcelas = parseInt(e.installments, 10);

            f.elements.installments.value = parcelas ? 'Sim' : 'Não';
            f.elements.installment_count.value = parcelas || '';
        }
    }

    updateInstallmentField();
    $('modalBack').classList.add('show');
}

// Encaminha a edição para openExpense() com o identificador selecionado.
function editExpense(id) {
    openExpense(id);
}

// Fecha o modal de lançamento.
$('closeModal').onclick = $('cancelModal').onclick = () => $('modalBack').classList.remove('show');

// Cadastra ou edita o lançamento enviando o formulário ao PHP.
// Mostra o campo "Quantidade de parcelas" só quando "Parcelado?" = Sim.
function updateInstallmentField() {
    const f = $('expenseForm');
    const parcelado = f.elements.installments.value === 'Sim';
    const campo = f.elements.installment_count;

    $('installmentCountGroup').hidden = !parcelado;
    campo.disabled = !parcelado;
    campo.required = parcelado;

    if (!parcelado) {
        campo.value = '';
        $('installmentHint').textContent = '';
        return;
    }

    const n = Number(campo.value);
    const valor = Number(f.elements.value.value);
    $('installmentHint').textContent = (n >= 2 && valor > 0)
        ? n + 'x de ' + money(valor / n)
        : 'Informe de 2 a 48 parcelas.';
}

$('expenseForm').addEventListener('change', e => {
    if (e.target.name === 'installments') {
        updateInstallmentField();
        if (e.target.value === 'Sim') $('expenseForm').elements.installment_count.focus();
    }
});
$('expenseForm').addEventListener('input', e => {
    if (['installment_count', 'value'].includes(e.target.name)) updateInstallmentField();
});
$('expenseForm').addEventListener('reset', () => setTimeout(updateInstallmentField, 0));

$('expenseForm').onsubmit = async e => {
    e.preventDefault();

    const form = e.currentTarget;

    if (form.elements.installments.value === 'Sim') {
        const n = Number(form.elements.installment_count.value);

        if (!Number.isInteger(n) || n < 2 || n > 48) {
            toast('Informe a quantidade de parcelas (de 2 a 48).');
            form.elements.installment_count.focus();
            return;
        }
    }

    const dados = new FormData(form);
    const editando = editingId !== null;
    const url = editando ? 'api/lancamentos/editar.php' : 'api/lancamentos/criar.php';

    if (editando) {
        dados.append('id', editingId);
    }

    try {
        const resultado = await callApi(url, dados);

        $('modalBack').classList.remove('show');
        form.reset();
        editingId = null;
        toast(resultado.mensagem);

        await loadExpensesFromDatabase();
    } catch (erro) {
        console.error('Erro ao salvar lançamento:', erro);
        toast(erro.message);
    }
};

// Exclui (cancela) um lançamento no banco após confirmação.
async function deleteExpense(id) {
    const lancamento = expenses.find(e => Number(e.id) === Number(id));

    const confirmado = await confirmDialog({
        title: 'Excluir lançamento?',
        message: `Tem certeza que deseja excluir o lançamento "${lancamento?.description || id}"? Esta ação não poderá ser desfeita.`
    });

    if (!confirmado) {
        return;
    }

    try {
        const resultado = await callApi('api/lancamentos/excluir.php', { id });

        toast(resultado.mensagem);
        await loadExpensesFromDatabase();
    } catch (erro) {
        console.error('Erro ao excluir:', erro);
        toast(erro.message);
    }
}


async function deleteAllExpenses() {

    const confirmado = await confirmDialog({
        title: 'Excluir todos os lançamentos?',
        message: 'Tem certeza que deseja excluir TODOS os seus lançamentos? Esta ação não poderá ser desfeita.'
    });

    if (!confirmado) {
        return;
    }

    try {

        const resultado = await callApi(
            'api/lancamentos/excluir_todos.php',
            {}
        );

        toast(resultado.mensagem);

        await loadExpensesFromDatabase();

    } catch (erro) {

        console.error('Erro ao excluir todos os lançamentos:', erro);

        toast(erro.message);
    }
}
async function deleteAllGoals() {
    const confirmado = await confirmDialog({
        title: 'Excluir todas as metas?',
        message: 'Tem certeza que deseja excluir TODAS as suas metas financeiras? Essa ação não poderá ser desfeita.'
    });

    if (!confirmado) return;

    try {
        const resultado = await callApi(
            'api/metas/excluir_todos.php',
            {}
        );

        toast(resultado.mensagem);

        await loadGoalsFromDatabase();

    } catch (erro) {
         console.error('Erro ao excluir todas metas:', erro);

        toast(erro.message);
    }
}


// ==========================================
// CATEGORIAS (criar, editar, remover)
// ==========================================

// Abre o popup de categoria. Sem ID cria uma nova; com ID edita a existente.
function openCategory(id = null) {
    editingCategoryId = id;

    const f = $('categoryForm');
    f.reset();

    const categoria = id !== null ? categories.find(c => c.id === id) : null;
    const editando = Boolean(categoria);

    $('categoryModal').classList.toggle('modal-edit', editando);
    $('categoryModalIcon').hidden = !editando;
    $('categoryModalTitle').textContent = editando ? 'Editar categoria' : 'Nova categoria';
    $('categoryModalSubtitle').textContent = editando ? categoria.name : 'Organize seus gastos por categoria';
    $('categorySubmit').textContent = editando ? 'Salvar alterações' : 'Criar categoria';

    if (editando) {
        $('categoryModalIcon').textContent = iconFor(categoria.name);
        f.elements.name.value = categoria.name;
    }

    $('categoryModalBack').classList.add('show');
    f.elements.name.focus();
}

function closeCategory() {
    $('categoryModalBack').classList.remove('show');
    editingCategoryId = null;
}

$('closeCategory').onclick = $('cancelCategory').onclick = closeCategory;

// Fecha ao clicar no fundo escurecido ou pressionar Esc.
$('categoryModalBack').addEventListener('click', (e) => {
    if (e.target.id === 'categoryModalBack') closeCategory();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeCategory();
});

// Mantidas com os nomes antigos porque o restante da tela chama estas funções.
function addCategory() {
    openCategory();
}

function renameCategory(id) {
    openCategory(id);
}

// Grava a categoria (nova ou editada) no banco.
$('categoryForm').onsubmit = async (e) => {
    e.preventDefault();

    const nome = e.currentTarget.elements.name.value.trim();

    if (!nome) {
        toast('Informe o nome da categoria.');
        return;
    }

    const id = editingCategoryId;
    const categoria = id !== null ? categories.find(c => c.id === id) : null;

    // Nada mudou: apenas fecha o popup.
    if (categoria && categoria.name === nome) {
        closeCategory();
        return;
    }

    try {
        const resultado = categoria
            ? await callApi('api/categorias/editar.php', { id, name: nome })
            : await callApi('api/categorias/criar.php', { name: nome });

        closeCategory();
        toast(resultado.mensagem);

        await loadCategoriesFromDatabase();

        // Lançamentos acompanham o novo nome da categoria.
        if (categoria) {
            await loadExpensesFromDatabase();
        }
    } catch (erro) {
        console.error('Erro ao salvar categoria:', erro);
        toast(erro.message);
    }
};

// Remove a categoria no banco; o PHP também recusa se houver lançamentos usando-a.
async function removeCategory(id) {
    const categoria = categories.find(c => c.id === id);
    if (!categoria) return;

    if (expenses.some(e => e.category === categoria.name)) {
        await confirmDialog({
            title: 'Não é possível excluir',
            message: 'Esta categoria possui lançamentos. Reatribua ou exclua esses lançamentos antes de removê-la.',
            confirmLabel: 'Entendi',
            info: true
        });
        return;
    }

    const confirmado = await confirmDialog({
        title: 'Excluir categoria?',
        message: 'Tem certeza que deseja excluir a categoria "' + categoria.name + '"? Esta ação não poderá ser desfeita.'
    });

    if (!confirmado) return;

    try {
        const resultado = await callApi('api/categorias/excluir.php', { id });

        toast(resultado.mensagem);
        await loadCategoriesFromDatabase();
    } catch (erro) {
        console.error('Erro ao remover categoria:', erro);
        toast(erro.message);
    }
}

// ==========================================
// METAS (ainda no localStorage)
// ==========================================

// Abre e prepara o formulário de criação de meta.
function openGoal() {
    const f = $('goalForm');

    f.reset();
    f.elements.due.value = todayISO();
    $('goalModalBack').classList.add('show');
}

$('closeGoal').onclick = $('cancelGoal').onclick = () => $('goalModalBack').classList.remove('show');

// ==========================================
// CADASTRAR META
// ==========================================

document.getElementById('goalForm').onsubmit = async (e) => {

    e.preventDefault();

    const form = e.currentTarget;

    // Captura os campos do formulário.
    const dados = new FormData(form);

    try {

        const resposta = await fetch(
            'api/metas/criar.php',
            {
                method: 'POST',
                body: dados
            }
        );

        const texto = await resposta.text();

        let resultado;

        try {

            resultado = JSON.parse(texto);

        } catch (erro) {

            console.error('Resposta do PHP:', texto);

            throw new Error(
                'O PHP retornou uma resposta inválida.'
            );
        }

        if (!resposta.ok || !resultado.sucesso) {
            throw new Error(
                resultado.erro || 'Erro ao criar meta.'
            );
        }

        // Fecha o modal.
        document.getElementById('goalModalBack')
            .classList.remove('show');

        form.reset();

        // Atualiza os dados consultando o MySQL.
        await loadGoalsFromDatabase();

        toast(resultado.mensagem);

    } catch (erro) {

        console.error('Erro ao criar meta:', erro);

        toast(erro.message);
    }
};

// ==========================================
// ATUALIZAR PROGRESSO DA META
// ==========================================

// Calcula o novo valor guardado conforme o modo (adicionar ou substituir).
function goalEditNewSaved() {
    const f = $('goalEditForm');
    const meta = goals.find(g => String(g.id) === String(f.elements.id.value));
    const atual = meta ? Number(meta.saved) : 0;
    const texto = f.elements.valor.value;
    const valor = texto === '' ? null : Number(texto);

    if (f.elements.modo.value === 'adicionar') {
        return atual + (valor || 0);
    }
    return valor === null ? atual : valor;
}

// Atualiza barra, percentual e dica enquanto o usuário digita.
function updateGoalEditPreview() {
    const f = $('goalEditForm');
    const novo = goalEditNewSaved();
    const alvo = Number(f.elements.target.value) || 0;
    const p = alvo > 0 ? Math.max(0, Math.min(100, novo / alvo * 100)) : 0;

    $('goalEditBar').style.width = p + '%';
    $('goalEditPercent').textContent = Math.round(p) + '% concluído';
    $('goalEditHint').textContent = (f.elements.modo.value === 'adicionar'
        ? 'O valor será somado ao guardado atual. '
        : 'O valor trocará todo o guardado atual. ') + 'Novo total: ' + money(novo) + '.';
}

['input', 'change'].forEach(ev => {
    $('goalEditForm').addEventListener(ev, e => {
        if (['valor', 'modo', 'target'].includes(e.target.name)) updateGoalEditPreview();
    });
});

function editGoal(id) {

    // Localiza a meta.
    const meta = goals.find(
        g => String(g.id) === String(id)
    );

    if (!meta) {
        toast('Meta não encontrada.');
        return;
    }

    // Preenche o popup com os valores atuais.
    const f = $('goalEditForm');

    f.elements.id.value = meta.id;
    f.elements.name.value = meta.name;
    f.elements.target.value = meta.target;
    f.elements.due.value = meta.due;
    f.elements.saved.value = meta.saved;
    f.elements.valor.value = '';
    f.elements.modo.value = 'adicionar';
    $('goalEditCurrent').textContent = money(meta.saved);
    updateGoalEditPreview();

    const iconeAtual = goalIcon(meta.icon);
    const selIcon = f.elements.icon;

    if (![...selIcon.options].some(o => o.value === iconeAtual)) {
        selIcon.add(new Option(iconeAtual, iconeAtual));
    }
    selIcon.value = iconeAtual;

    $('goalEditIcon').textContent = iconeAtual;
    $('goalEditSubtitle').textContent = meta.name;

    $('goalEditModalBack').classList.add('show');
    f.elements.name.focus();
}

function closeGoalEdit() {
    $('goalEditModalBack').classList.remove('show');
}

$('closeGoalEdit').onclick = $('cancelGoalEdit').onclick = closeGoalEdit;

// Fecha ao clicar no fundo escurecido ou pressionar Esc.
$('goalEditModalBack').addEventListener('click', (e) => {
    if (e.target.id === 'goalEditModalBack') closeGoalEdit();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeGoalEdit();
});

// Salva todos os campos da meta editados no popup.
$('goalEditForm').onsubmit = async (e) => {

    e.preventDefault();

    const f = e.currentTarget;

    // Modo "substituir" exige o novo valor.
    if (f.elements.modo.value === 'substituir' && f.elements.valor.value === '') {
        toast('Informe o novo valor guardado.');
        f.elements.valor.focus();
        return;
    }

    // Adicionar valor: o dinheiro sai do saldo, então pergunta de qual lucro e confere o saldo.
    const valorAdicionado = Math.round((Number(f.elements.valor.value) || 0) * 100) / 100;

    if (f.elements.modo.value === 'adicionar' && valorAdicionado > 0) {
        const meta = goals.find(g => String(g.id) === String(f.elements.id.value));

        if (!meta) {
            toast('Meta não encontrada.');
            return;
        }

        openAporte(meta, valorAdicionado, {
            name: f.elements.name.value.trim(),
            target: f.elements.target.value,
            due: f.elements.due.value,
            icon: f.elements.icon.value
        });
        return;
    }

    // O banco recebe sempre o valor final já calculado (somado ou substituído).
    f.elements.saved.value = goalEditNewSaved().toFixed(2);

    // Captura os campos do formulário (id, name, target, saved, due, icon).
    const dados = new FormData(f);

    try {

        const resposta = await fetch(
            'api/metas/editar.php',
            {
                method: 'POST',
                body: dados
            }
        );

        const texto = await resposta.text();

        let resultado;

        try {
            resultado = JSON.parse(texto);
        } catch (erro) {
            console.error('Resposta do PHP:', texto);
            throw new Error('Resposta inválida do PHP.');
        }

        if (!resposta.ok || !resultado.sucesso) {
            throw new Error(
                resultado.erro || 'Erro ao atualizar meta.'
            );
        }

        closeGoalEdit();

        // Busca novamente os registros.
        await loadGoalsFromDatabase();

        toast(resultado.mensagem);

    } catch (erro) {

        console.error('Erro ao atualizar meta:', erro);

        toast(erro.message);
    }
};


// ==========================================
// APORTE EM META: ORIGEM (LUCRO FIXO/VARIÁVEL) E SALDO
// ==========================================

let pendingAporte = null; // { meta, valor, campos }

// Abre o popup que pergunta de qual lucro sai o valor e confere se há saldo.
function openAporte(meta, valor, campos) {
    const s = saldoMes();
    const semLucro = income.fixo + income.variavel <= 0;
    const f = $('aporteForm');
    const folga = 0.004;
    let escolhida = null;

    pendingAporte = { meta, valor, campos };

    $('aporteIcon').textContent = goalIcon(meta.icon);
    $('aporteSub').textContent = 'Guardar ' + money(valor) + ' em “' + meta.name + '”';

    ['FIXA', 'VARIAVEL'].forEach(tipo => {
        const disponivel = tipo === 'FIXA' ? s.fixo : s.variavel;
        const radio = f.querySelector('input[value="' + tipo + '"]');
        const ok = valor <= disponivel + folga && valor <= s.saldo + folga;

        radio.checked = false;
        radio.disabled = !ok;
        $('aporteAvail' + tipo).textContent = 'Disponível: ' + money(Math.max(0, disponivel)) +
            (valor > disponivel + folga ? ' · saldo insuficiente' : '');

        if (ok && !escolhida) escolhida = radio;
    });

    if (escolhida) escolhida.checked = true;

    const hint = $('aporteHint');

    hint.classList.toggle('error', !escolhida);
    $('confirmAporte').disabled = !escolhida;

    if (escolhida) {
        hint.textContent = 'Saldo do mês: ' + money(s.saldo) + ' → ' + money(s.saldo - valor) + ' após o aporte.';
    } else if (semLucro) {
        hint.textContent = 'Cadastre seu lucro fixo ou variável do mês para poder guardar valores nas metas.';
    } else if (valor > s.saldo + folga) {
        hint.textContent = 'Saldo insuficiente: o saldo do mês é de ' + money(Math.max(0, s.saldo)) +
            ' (lucros − gastos − aportes) e você tentou guardar ' + money(valor) + '.';
    } else {
        hint.textContent = 'Nenhum dos lucros tem saldo suficiente para guardar ' + money(valor) + '.';
    }

    $('aporteModalBack').classList.add('show');
}

function closeAporte() {
    $('aporteModalBack').classList.remove('show');
    pendingAporte = null;
}

$('closeAporte').onclick = $('cancelAporte').onclick = closeAporte;
$('aporteModalBack').addEventListener('click', (e) => {
    if (e.target.id === 'aporteModalBack') closeAporte();
});

// Esc fecha só este popup (a edição da meta continua aberta por baixo).
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && $('aporteModalBack').classList.contains('show')) {
        e.stopPropagation();
        closeAporte();
    }
}, true);

$('aporteForm').onsubmit = async (e) => {
    e.preventDefault();

    const p = pendingAporte;
    const marcada = $('aporteForm').querySelector('input[name="origem"]:checked');

    if (!p || !marcada) return;

    const origem = marcada.value;
    const botao = $('confirmAporte');

    botao.disabled = true;

    try {
        // Dados da meta (nome, valor desejado, prazo, ícone) que também foram editados.
        const mudou = p.campos.name !== p.meta.name
            || Number(p.campos.target) !== Number(p.meta.target)
            || p.campos.due !== p.meta.due
            || p.campos.icon !== goalIcon(p.meta.icon);

        if (mudou) {
            await callApi('api/metas/editar.php', {
                id: p.meta.id,
                name: p.campos.name,
                target: p.campos.target,
                saved: Number(p.meta.saved).toFixed(2),
                due: p.campos.due,
                icon: p.campos.icon
            });
        }

        // O servidor confere o saldo de novo antes de gravar o aporte.
        await callApi('api/metas/aportar.php', {
            id: p.meta.id,
            valor: p.valor.toFixed(2),
            origem,
            data: todayISO()
        });

        const nome = p.meta.name;
        const valor = p.valor;

        closeAporte();
        closeGoalEdit();
        await loadGoalsFromDatabase();

        toast(money(valor) + ' guardados em “' + nome + '” (saiu do ' + SOURCE_LABEL[origem].toLowerCase() + ').');
    } catch (erro) {
        console.error('Erro ao guardar valor na meta:', erro);
        toast(erro.message);
    } finally {
        botao.disabled = false;
    }
};

// ==========================================
// CANCELAR META
// ==========================================

async function deleteGoal(id) {

    const meta = goals.find(
        g => String(g.id) === String(id)
    );

    if (!meta) {
        toast('Meta não encontrada.');
        return;
    }

    const confirmado = await confirmDialog({
        title: 'Excluir meta?',
        message: 'Tem certeza que deseja excluir a meta "' + meta.name + '"? Esta ação não poderá ser desfeita.'
    });

    if (!confirmado) {
        return;
    }

    const dados = new FormData();

    dados.append('id', id);

    try {

        const resposta = await fetch(
            'api/metas/excluir.php',
            {
                method: 'POST',
                body: dados
            }
        );

        const texto = await resposta.text();

        let resultado;

        try {
            resultado = JSON.parse(texto);
        } catch (erro) {
            console.error('Resposta do PHP:', texto);
            throw new Error('Resposta inválida do PHP.');
        }

        if (!resposta.ok || !resultado.sucesso) {
            throw new Error(
                resultado.erro || 'Erro ao cancelar meta.'
            );
        }

        await loadGoalsFromDatabase();

        toast(resultado.mensagem);

    } catch (erro) {

        console.error('Erro ao cancelar meta:', erro);

        toast(erro.message);
    }
}

// ==========================================
// CARREGAR METAS DO MYSQL
// ==========================================

async function loadGoalsFromDatabase() {

    try {

        const resposta = await fetch(
            'api/metas/listar.php'
        );

        const texto = await resposta.text();

        let resultado;

        // Converte a resposta do PHP.
        try {

            resultado = JSON.parse(texto);

        } catch (erro) {

            console.error('Resposta do PHP:', texto);

            throw new Error(
                'O PHP retornou uma resposta inválida.'
            );
        }

        if (!resposta.ok || !resultado.sucesso) {
            throw new Error(
                resultado.erro || 'Erro ao carregar metas.'
            );
        }

        // Atualiza o array.
        goals = resultado.metas.map(g => ({
            ...g,
            target: Number(g.target),
            saved: Number(g.saved)
        }));

        // Atualiza a tela.
        render();

        // Metas canceladas devolvem o valor ao saldo, então os aportes são recarregados junto.
        await loadAportesFromDatabase();

    } catch (erro) {

        console.error('Erro ao carregar metas:', erro);

        toast(erro.message);
    }
}

// ==========================================
// PERFIL, DADOS E LOGIN
// ==========================================

// Abre o popup para editar o nome da conta.
function editProfile() {
    const f = $('profileForm');

    f.elements.name.value = user.name || '';
    f.elements.email.value = user.email || '';
    $('profileModalBack').classList.add('show');
    f.elements.name.focus();
    f.elements.name.select();
}

function closeProfile() {
    $('profileModalBack').classList.remove('show');
}

$('closeProfile').onclick = $('cancelProfile').onclick = closeProfile;

$('profileModalBack').addEventListener('click', (e) => {
    if (e.target.id === 'profileModalBack') closeProfile();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeProfile();
});

// Salva o novo nome no banco (api/perfil/editar.php) e atualiza a tela.
$('profileForm').onsubmit = async (e) => {
    e.preventDefault();

    const f = e.currentTarget;
    const nome = f.elements.name.value.trim();

    if (!nome) {
        toast('Informe o seu nome.');
        f.elements.name.focus();
        return;
    }

    if (nome === (user.name || '')) {
        closeProfile();
        return;
    }

    const botao = $('saveProfile');
    botao.disabled = true;

    try {
        const resultado = await callApi('api/perfil/editar.php', {
            name: nome,
            email: user.email || f.elements.email.value
        });

        user = {
            name: resultado.usuario.nome,
            email: resultado.usuario.email
        };

        $('userLabel').textContent = user.name;
        save();
        closeProfile();
        render();
        toast('Nome da conta atualizado.');
    } catch (erro) {
        console.error('Erro ao atualizar perfil:', erro);
        toast(erro.message);
    } finally {
        botao.disabled = false;
    }
};

// Apaga as metas e o perfil salvos no navegador. Não apaga dados do MySQL.
function clearData() {
    if (confirm('Isso apagará as metas e o perfil salvos neste navegador. Deseja continuar?')) {
        localStorage.removeItem('cf_goals');
        localStorage.removeItem('cf_user');
        goals = [];
        user = {
    name: (window.USUARIO && window.USUARIO.nome) || 'Usuário',
    email: (window.USUARIO && window.USUARIO.email) || ''
};
        $('userLabel').textContent = user.name;
        render();
        toast('Dados locais apagados.');
    }
}




$('userPill').onclick = () => setPage('settings');
window.addEventListener('resize', () => {
    if (page === 'reports') drawCharts();
});

// ==========================================
// LUCROS (fixo e variável)
// ==========================================

// Converte "1.000,50" ou "1000.50" em número. Devolve NaN se não for válido.
function parseMoney(texto) {
    let t = String(texto).trim().replace(/\s|R\$/g, '');

    if (t === '') return NaN;

    // Com vírgula: formato brasileiro (ponto = milhar, vírgula = decimal).
    if (t.includes(',')) {
        t = t.replace(/\./g, '').replace(',', '.');
    } else if (/^\d{1,3}(\.\d{3})+$/.test(t)) {
        // Sem vírgula e só pontos de milhar (ex.: 2.500): remove os pontos.
        t = t.replace(/\./g, '');
    }

    return /^\d+(\.\d+)?$/.test(t) ? Number(t) : NaN;
}

// Atualiza o resumo (total, fixo, variável) e a dica do popup.
function refreshIncomePopup() {
    const totalLucros = income.fixo + income.variavel;
    const pctFixo = totalLucros > 0 ? Math.round(income.fixo / totalLucros * 100) : 0;
    const pctVar = totalLucros > 0 ? 100 - pctFixo : 0;

    $('incomeTotal').textContent = money(totalLucros);
    $('incomeFixed').textContent = money(income.fixo);
    $('incomeVariable').textContent = money(income.variavel);
    $('incomeFixedPct').textContent = '(' + pctFixo + '%)';
    $('incomeVariablePct').textContent = '(' + pctVar + '%)';

    const f = $('incomeForm');
    const nomeTipo = f.elements.tipo.value === 'FIXA' ? 'lucro fixo' : 'lucro variável';

    $('incomeHint').textContent = f.elements.modo.value === 'adicionar'
        ? 'O valor será somado ao ' + nomeTipo + ' deste mês.'
        : 'O valor trocará todo o ' + nomeTipo + ' deste mês.';
}

// Abre o popup de lucros com o tipo (FIXA ou VARIAVEL) já selecionado.
function openIncome(tipo = 'FIXA') {
    const f = $('incomeForm');

    f.reset();
    f.elements.tipo.value = tipo;
    refreshIncomePopup();

    $('incomeModalBack').classList.add('show');
    f.elements.valor.focus();
}

function closeIncome() {
    $('incomeModalBack').classList.remove('show');
}

$('closeIncome').onclick = $('cancelIncome').onclick = closeIncome;

// Fecha ao clicar no fundo escurecido ou pressionar Esc.
$('incomeModalBack').addEventListener('click', (e) => {
    if (e.target.id === 'incomeModalBack') closeIncome();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeIncome();
});

// A dica muda ao trocar entre adicionar/substituir e fixo/variável.
$('incomeForm').addEventListener('change', refreshIncomePopup);

// Grava o valor informado no banco.
$('incomeForm').onsubmit = async (e) => {
    e.preventDefault();

    const f = e.currentTarget;
    const modo = f.elements.modo.value;
    const valor = parseMoney(f.elements.valor.value);

    if (!Number.isFinite(valor) || valor < 0 || (modo === 'adicionar' && valor <= 0)) {
        toast('Informe um valor válido.');
        return;
    }

    try {
        const resultado = await callApi('api/receitas/salvar.php', {
            modo,
            tipo: f.elements.tipo.value,
            valor,
            data: todayISO()
        });

        closeIncome();
        toast(resultado.mensagem);

        await loadIncomeFromDatabase();
    } catch (erro) {
        console.error('Erro ao salvar lucro:', erro);
        toast(erro.message);
    }
};

// ==========================================
// INICIALIZAÇÃO
// ==========================================

updateMini();
render();

loadUserFromSession();
loadCategoriesFromDatabase();
loadExpensesFromDatabase();
loadIncomeFromDatabase();
loadGoalsFromDatabase();