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
    { id: 1, name: 'Viagem dos Sonhos', icon: '✈',  target: 5000,  saved: 3000,  due: '2027-01-10' },
    { id: 2, name: 'Entrada do Apê',    icon: '⌂',  target: 35000, saved: 12250, due: '2028-06-30' },
    { id: 3, name: 'Carro Novo',        icon: '🚗', target: 12000, saved: 2400,  due: '2027-10-10' },
    { id: 4, name: 'Pós-Graduação',     icon: '▤',  target: 20000, saved: 2000,  due: '2028-08-01' }
];

// ==========================================
// ESTADO
// ==========================================

let expenses = [];   // Vem de api/lancamentos/listar.php
let categories = []; // Vem de api/categorias/listar.php: { id, name, global }
let goals = [];
let user = load('cf_user', { name: 'Usuário', email: '' });
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
    return new Date().toISOString().slice(0, 10);
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

// Nomes das categorias cadastradas no banco.
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
        console.error('Resposta do PHP:', texto);
        throw new Error('O PHP retornou uma resposta inválida.');
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
function stat(label, value, note, ico) {
    return `
    <div class="card">
        <div class="stat-label"><span>${ico}</span>${label}</div>
        <div class="stat-value">${value}</div>
        <div class="stat-note">${note}</div>
    </div>`;
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
            <td><span class="tag">${esc(e.installments)}</span></td>
            <td><span class="tag">${esc(e.payment)}</span></td>
            <td>${esc(e.category)}</td>
            <td class="actions">
                <button title="Editar" onclick="editExpense(${e.id})">✎</button>
                <button title="Excluir" onclick="deleteExpense(${e.id})">⌫</button>
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
    const sums = catNames()
        .map(nome => ({ name: nome, value: total(expenses.filter(e => e.category === nome)) }))
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

        return okPagamento && (!termo || texto.includes(termo));
    });
}

// Redesenha só a tabela de lançamentos aplicando pesquisa, categoria e período.
function refreshExpenseTable() {
    const categoria = $('categoryFilter').value;
    const mes = $('monthFilter').value;

    const arr = filtered()
        .filter(e => (!categoria || e.category === categoria) && (!mes || e.date.startsWith(mes)))
        .sort((a, b) => b.date.localeCompare(a.date));

    $('expenseTable').innerHTML = expenseTable(arr);
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
    const sum = total(expenses);
    const monthSum = total(expenses.filter(e => e.date.slice(0, 7) === todayISO().slice(0, 7)));
    const recentes = expenses.slice().sort((a, b) => b.date.localeCompare(a.date)).slice(0, 5);
    const restante = MONTHLY_LIMIT - monthSum;

    return `
    ${head(
        'Visão geral',
        'Acompanhe suas finanças e mantenha seus objetivos em dia.',
        `<button class="btn primary" onclick="openExpense()">＋ Novo lançamento</button>`)}
    <div class="grid stats">
        ${stat('Gastos registrados', money(sum), '↗ Atualizado com seus lançamentos', '▥')}
        ${stat('Gastos do mês', money(monthSum), 'Este mês', '▤')}
        ${stat('Saldo estimado', money(MONTHLY_INCOME - sum), 'Considerando renda mensal de ' + money(MONTHLY_INCOME), '◉')}
        ${stat('Lançamentos', expenses.length, 'Registros cadastrados', '☷')}
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
                <h3 class="section-title">Meta mensal</h3>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="muted" style="font-size:11px">Limite de gastos</span>
                    <strong style="color:var(--green)">${money(MONTHLY_LIMIT)}</strong>
                </div>
                <div class="progress" style="margin:12px 0">
                    <span style="width:${Math.min(100, monthSum / MONTHLY_LIMIT * 100)}%"></span>
                </div>
                <small class="muted">${restante > 0 ? money(restante) + ' disponíveis' : money(-restante) + ' acima do limite'}</small>
            </div>
        </div>
    </div>`;
}

// Tela de lançamentos, indicadores e controles de filtro.
function expensesPage() {
    const arr = filtered().slice().sort((a, b) => b.date.localeCompare(a.date));
    const catSelecionada = new URLSearchParams(location.search).get('cat');
    const periodos = [...new Set(expenses.map(e => e.date.slice(0, 7)))].sort().reverse();

    return `
    ${head(
        'Lançamentos',
        'Organize e acompanhe todas as suas despesas.',
        `<button class="btn primary" onclick="openExpense()">＋ Novo lançamento</button>`)}
    <div class="grid stats">
        ${stat('Total filtrado', money(total(arr)), 'Soma dos lançamentos exibidos', '▥')}
        ${stat('Quantidade', arr.length, 'Lançamentos encontrados', '☷')}
        ${stat('Crédito', money(total(arr.filter(e => e.payment === 'Crédito'))), 'Compras no crédito', '▣')}
        ${stat('Pix', money(total(arr.filter(e => e.payment === 'Pix'))), 'Pagamentos via Pix', '▦')}
    </div>
    <div class="toolbar">
        <input class="field search" id="searchExpense" placeholder="⌕ Pesquisar por nome, gasto, categoria..." value="${esc(searchTerm)}">
        <select class="field" id="categoryFilter">
            <option value="">Todas as categorias</option>
            ${catNames().map(c => `<option${catSelecionada === c ? ' selected' : ''}>${esc(c)}</option>`).join('')}
        </select>
        <select class="field" id="monthFilter">
            <option value="">Todos os períodos</option>
            ${periodos.map(m => `<option value="${m}">${m.split('-').reverse().join('/')}</option>`).join('')}
        </select>
        ${paymentFilter ? `<button class="chip active" onclick="paymentFilter='';render()">${esc(paymentFilter)} ×</button>` : ''}
        <button class="btn" onclick="resetFilters()">Limpar filtros</button>
    </div>
    <div id="expenseTable">${expenseTable(arr)}</div>`;
}

// Relatórios e gráficos. Os totais são derivados dos lançamentos.
function reportsPage() {
    const sum = total(expenses);
    const month = expenses.filter(e => e.date.slice(0, 7) === todayISO().slice(0, 7));

    const byPay = PAYMENT_METHODS
        .map(p => ({ p, v: total(expenses.filter(e => e.payment === p)) }))
        .filter(x => x.v);

    const top = catNames()
        .map(c => ({ c, v: total(expenses.filter(e => e.category === c)) }))
        .sort((a, b) => b.v - a.v)
        .slice(0, 5);

    return `
    ${head(
        'Relatórios e gráficos',
        'Visualize seus gastos e entenda para onde vai seu dinheiro.',
        `<select class="field" id="reportPeriod">
            <option>Mensal</option>
            <option>Semanal</option>
            <option>Diário</option>
            <option>Anual</option>
        </select>`)}
    <div class="grid stats">
        ${stat('Total de gastos', money(sum), 'Em todos os lançamentos', '↓')}
        ${stat('Gastos do período', money(total(month)), 'Mês atual', '▥')}
        ${stat('Média por lançamento', money(expenses.length ? sum / expenses.length : 0), 'Valor médio', '◉')}
        ${stat('Categorias utilizadas', new Set(expenses.map(e => e.category)).size, 'Com gastos registrados', '◆')}
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
    </div>`;
}

// Desenha os gráficos nos canvas usando os lançamentos em memória.
// idx 0 = linha (gastos por faixa de dias); idx 1 = barras (gastos por semana).
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

        const labels = idx
            ? ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4', 'Sem 5']
            : ['01', '05', '10', '15', '20', '25', '30'];

        const vals = labels.map((_, i) => total(expenses.filter(e => {
            const dia = Number(e.date.slice(-2));

            return idx
                ? Math.min(4, Math.floor((dia - 1) / 7)) === i
                : Math.abs(dia - (i * 5 + 1)) < 5;
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

    const linhas = categories.length
        ? categories.map(c => `
            <tr>
                <td>
                    <div class="item-name">
                        <span class="cat-icon">${iconFor(c.name)}</span>
                        <strong>${esc(c.name)}</strong>
                    </div>
                </td>
                <td>${expenses.filter(e => e.category === c.name).length}</td>
                <td>${money(total(expenses.filter(e => e.category === c.name)))}</td>
                <td><span class="tag">Ativa</span></td>
                <td class="actions">${c.global
                    ? '<span class="muted" style="font-size:11px">Padrão do sistema</span>'
                    : `<button onclick="renameCategory(${c.id})">✎ Renomear</button>
                       <button onclick="removeCategory(${c.id})">⌫</button>`}
                </td>
            </tr>`).join('')
        : `<tr><td colspan="5" class="empty">Nenhuma categoria cadastrada. Clique em "Nova categoria" para começar.</td></tr>`;

    return `
    ${head(
        'Categorias',
        'Gerencie as categorias usadas para organizar seus gastos.',
        `<button class="btn primary" onclick="addCategory()">＋ Nova categoria</button>`)}
    <div class="grid stats">
        ${stat('Categorias', categories.length, 'Cadastradas', '◆')}
        ${stat('Com movimentação', names.filter(c => expenses.some(e => e.category === c)).length, 'Em uso', '▥')}
        ${stat('Sem movimentação', names.filter(c => !expenses.some(e => e.category === c)).length, 'Disponíveis para uso', '◉')}
        ${stat('Total de gastos', money(total(expenses)), 'Todos os registros', '↓')}
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

// Metas e progresso. Os valores de renda fixa/variável ainda são exemplos.
function goalsPage() {
    const fixed = goals
        .filter(g => g.name.toLowerCase().includes('salário') || g.name.toLowerCase().includes('fixo'))
        .reduce((soma, g) => soma + g.target, 0) || 2850;
    const variable = 1250;

    const lista = goals.length
        ? goals.map(g => {
            const p = Math.min(100, g.saved / g.target * 100);

            return `
            <div class="goal-line">
                <div class="bigicon">${esc(g.icon || '◎')}</div>
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
                    <button onclick="editGoal(${g.id})" title="Atualizar">✎</button>
                    <button onclick="deleteGoal(${g.id})" title="Excluir">⌫</button>
                </div>
            </div>`;
        }).join('')
        : '<div class="empty">Você ainda não criou metas.</div>';

    return `
    ${head(
        'Metas',
        'Defina seus objetivos e acompanhe seu progresso.',
        `<button class="btn primary" onclick="openGoal()">＋ Criar nova meta</button>`)}
    <div class="grid goal-cards">
        <div class="card goal-card">
            <div class="bigicon">▣</div>
            <div>
                <span class="muted" style="font-size:11px">Renda fixa mensal</span>
                <strong>${money(fixed)}</strong>
                <div class="progress" style="width:160px"><span style="width:72%"></span></div>
            </div>
        </div>
        <div class="card goal-card">
            <div class="bigicon">＄</div>
            <div>
                <span class="muted" style="font-size:11px">Renda variável</span>
                <strong>${money(variable)}</strong>
                <div class="progress" style="width:160px"><span style="width:30%"></span></div>
            </div>
        </div>
    </div>
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
                    <div><strong>Notificações</strong><small>Gerencie avisos e lembretes</small></div>
                    <label><input type="checkbox" id="notifications" checked> Ativas</label>
                </div>
                <div class="setting-row">
                    <div><strong>Moeda</strong><small>Moeda padrão para seus gastos</small></div>
                    <span>Real (R$)</span>
                </div>
                <div class="setting-row">
                    <div><strong>Idioma</strong><small>Idioma de exibição do sistema</small></div>
                    <span>Português (Brasil)</span>
                </div>
                <div class="setting-row">
                    <div><strong>Backup</strong><small>Baixe uma cópia dos seus dados em JSON</small></div>
                    <button class="btn" onclick="exportData()">Exportar dados</button>
                </div>
            </div>
            <div class="card">
                <h3 class="section-title">Dados e privacidade</h3>
                <div class="setting-row">
                    <div><strong>Exportar dados</strong><small>Baixe seus lançamentos e metas em JSON</small></div>
                    <button class="btn" onclick="exportData()">Exportar</button>
                </div>
                <div class="setting-row">
                    <div><strong>Limpar dados locais</strong><small>Apague as metas e o perfil salvos neste navegador</small></div>
                    <button class="btn danger" onclick="clearData()">Limpar</button>
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
                <div class="setting-row">
                    <div><strong>Plano atual</strong><small>Gratuito</small></div>
                    <span class="tag">Ativo</span>
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
        $('monthFilter').onchange = refreshExpenseTable;
    }

    if (page === 'reports') {
        drawCharts();
        $('reportPeriod').onchange = e => toast('Visualização selecionada: ' + e.target.value);
    }
}

// Atualiza a barra lateral com o gasto do mês e o limite mensal.
function updateMini() {
    const spent = total(expenses.filter(e => e.date.startsWith(todayISO().slice(0, 7))));

    $('mini-progress').style.width = Math.min(100, spent / MONTHLY_LIMIT * 100) + '%';
    $('mini-note').textContent = spent < MONTHLY_LIMIT
        ? money(MONTHLY_LIMIT - spent) + ' restantes'
        : money(spent - MONTHLY_LIMIT) + ' acima do limite';
}

// ==========================================
// LANÇAMENTOS (criar, editar, excluir)
// ==========================================

// Abre o formulário de lançamento. Se receber ID, preenche os campos para edição.
function openExpense(id = null) {
    // Sem categorias no banco não há como lançar um gasto: leva o usuário a cadastrar a primeira.
    if (!categories.length) {
        if (confirm('Você ainda não tem categorias cadastradas. Deseja cadastrar uma agora?')) {
            setPage('categories');
            addCategory();
        }
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
            f.elements.place.value = e.place;
            f.elements.installments.value = e.installments;
        }
    }

    $('modalBack').classList.add('show');
}

// Encaminha a edição para openExpense() com o identificador selecionado.
function editExpense(id) {
    openExpense(id);
}

// Fecha o modal de lançamento.
$('closeModal').onclick = $('cancelModal').onclick = () => $('modalBack').classList.remove('show');

// Cadastra ou edita o lançamento enviando o formulário ao PHP.
$('expenseForm').onsubmit = async e => {
    e.preventDefault();

    const form = e.currentTarget;
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

    if (!confirm(`Deseja excluir o lançamento "${lancamento?.description || id}"?`)) {
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

// ==========================================
// CATEGORIAS (criar, renomear, remover)
// ==========================================

// Solicita o nome e grava a nova categoria no banco.
async function addCategory() {
    const nome = prompt('Nome da nova categoria:');
    if (!nome || !nome.trim()) return;

    try {
        const resultado = await callApi('api/categorias/criar.php', { name: nome.trim() });

        toast(resultado.mensagem);
        await loadCategoriesFromDatabase();
    } catch (erro) {
        console.error('Erro ao cadastrar categoria:', erro);
        toast(erro.message);
    }
}

// Renomeia a categoria no banco; os lançamentos acompanham pelo ID.
async function renameCategory(id) {
    const categoria = categories.find(c => c.id === id);
    if (!categoria) return;

    const nome = prompt('Novo nome para ' + categoria.name + ':', categoria.name);
    if (!nome || !nome.trim() || nome.trim() === categoria.name) return;

    try {
        const resultado = await callApi('api/categorias/editar.php', { id, name: nome.trim() });

        toast(resultado.mensagem);
        await loadCategoriesFromDatabase();
        await loadExpensesFromDatabase();
    } catch (erro) {
        console.error('Erro ao renomear categoria:', erro);
        toast(erro.message);
    }
}

// Remove a categoria no banco; o PHP também recusa se houver lançamentos usando-a.
async function removeCategory(id) {
    const categoria = categories.find(c => c.id === id);
    if (!categoria) return;

    if (expenses.some(e => e.category === categoria.name)) {
        alert('Esta categoria possui lançamentos. Reatribua ou exclua esses lançamentos antes de removê-la.');
        return;
    }

    if (!confirm('Remover a categoria ' + categoria.name + '?')) return;

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

async function editGoal(id) {

    // Localiza a meta.
    const meta = goals.find(
        g => String(g.id) === String(id)
    );

    if (!meta) {
        toast('Meta não encontrada.');
        return;
    }

    // Solicita o novo valor acumulado.
    const valor = prompt(
        'Quanto você já guardou para ' + meta.name + '? (R$)',
        meta.saved
    );

    if (valor === null) {
        return;
    }

    // Aceita valores com vírgula ou ponto decimal.
    const valorNumerico = Number(valor.replace(',', '.'));

    if (
        valor.trim() === '' ||
        !Number.isFinite(valorNumerico) ||
        valorNumerico < 0
    ) {
        toast('Informe um valor válido.');
        return;
    }

    const dados = new FormData();

    dados.append('id', id);
    dados.append('saved', valorNumerico);

    try {

        const resposta = await fetch(
            'api/metas/progresso.php',
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

        // Busca novamente os registros.
        await loadGoalsFromDatabase();

        toast(resultado.mensagem);

    } catch (erro) {

        console.error('Erro ao atualizar meta:', erro);

        toast(erro.message);
    }
}

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

    if (!confirm('Deseja cancelar a meta "' + meta.name + '"?')) {
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

    } catch (erro) {

        console.error('Erro ao carregar metas:', erro);

        toast(erro.message);
    }
}

// ==========================================
// PERFIL, DADOS E LOGIN
// ==========================================

// Edita nome/e-mail localmente. Na versão real, virão da sessão e da tabela usuario.
function editProfile() {
    const nome = prompt('Seu nome:', user.name || '');
    if (nome === null) return;

    const email = prompt('Seu e-mail:', user.email || '');
    if (email === null) return;

    user = { name: nome.trim() || 'Usuário', email: email.trim() };
    $('userLabel').textContent = user.name;
    save();
    render();
    toast('Perfil atualizado.');
}

// Exporta os dados carregados para um arquivo JSON.
function exportData() {
    const blob = new Blob(
        [JSON.stringify({ expenses, goals, categories, user }, null, 2)],
        { type: 'application/json' }
    );
    const link = document.createElement('a');

    link.href = URL.createObjectURL(blob);
    link.download = 'controle-financeiro-dados.json';
    link.click();
    URL.revokeObjectURL(link.href);
    toast('Exportação iniciada.');
}

// Apaga as metas e o perfil salvos no navegador. Não apaga dados do MySQL.
function clearData() {
    if (confirm('Isso apagará as metas e o perfil salvos neste navegador. Deseja continuar?')) {
        localStorage.removeItem('cf_goals');
        localStorage.removeItem('cf_user');
        goals = [];
        user = { name: 'Usuário', email: '' };
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
// INICIALIZAÇÃO
// ==========================================

updateMini();
render();

loadUserFromSession();
loadCategoriesFromDatabase();
loadExpensesFromDatabase();
loadGoalsFromDatabase();
