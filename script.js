const defaultExpenses=[
    {
        id:1,
        description:'Almoço no Restaurante',
        value:42.5,
        date:'2026-10-02',
        category:'Alimentação',
        who:'Eu',
        payment:'VR',
        place:'Filial',
        installments:'Não'
    },

    {
        id:2,
        description:'Compra no Mercado',
        value:126.9,
        date:'2026-10-03',
        category:'Supermercado',
        who:'Esposa',
        payment:'Débito',
        place:'Casa',
        installments:'Não'
    },

    {
        id:3,
        description:'Camisa',
        value:78.0,
        date:'2026-10-05',
        category:'Vestuário',
        who:'Eu',
        payment:'Crédito',
        place:'Casa',
        installments:'Sim'
    },

    {
        id:4,
        description:'Cinema',
        value:64.0,date:'2026-10-06',
        category:'Lazer',
        who:'Amigo',
        payment:'Crédito',
        place:'Filial',
        installments:'Não'
    },

    {
        id:5,
        description:'Posto de Gasolina',
        value:180.0,
        date:'2026-10-08',
        category:'Transporte',
        who:'Eu',
        payment:'Débito',
        place:'Filial',
        installments:'Não'
    },

    {
        id:6,
        description:'Aluguel',
        value:1300,
        date:'2026-10-10',
        category:'Moradia',
        who:'Eu',
        payment:'Pix',
        place:'Casa',
        installments:'Não'
    },

    {
        id:7,
        description:'Consulta Médica',
        value:180,
        date:'2026-10-12',
        category:'Saúde',
        who:'Eu',
        payment:'Crédito',
        place:'Casa',
        installments:'Não'
    },

    {
        id:8,
        description:'Farmácia',
        value:45.9,
        date:'2026-10-13',
        category:'Saúde',
        who:'Eu',
        payment:'Débito',
        place:'Casa',
        installments:'Não'
    }
];

const defaultGoals=[
    {
        id:1,
        name:'Viagem dos Sonhos',
        icon:'✈',
        target:5000,
        saved:3000,
        due:'2027-01-10'
    },

    {
        id:2,
        name:'Entrada do Apê',
        icon:'⌂',
        target:35000,
        saved:12250,
        due:'2028-06-30'
    },
    {
        id:3,
        name:'Carro Novo',
        icon:'🚗',
        target:12000,
        saved:2400,
        due:'2027-10-10'
    },
    {
        id:4,
        name:'Pós-Graduação',
        icon:'▤',
        target:20000,
        saved:2000,
        due:'2028-08-01'
    }
];
const categoriesDefault=[
    'Alimentação',
    'Supermercado',
    'Vestuário',
    'Lazer',
    'Transporte',
    'Moradia',
    'Saúde',
    'Outros'
];
let expenses=load('cf_expenses',defaultExpenses),
    goals=load('cf_goals',defaultGoals),
    categories=load('cf_categories',categoriesDefault),
    user=load('cf_user',{name:'Usuário',email:''}),
    page='home',
    editingId=null,
    paymentFilter='',
    searchTerm='';

function load(k,d){try{const v=localStorage.getItem(k);
    return v?JSON.parse(v):JSON.parse(JSON.stringify(d))}
    catch(e){return JSON.parse(JSON.stringify(d))}}
function save(){localStorage.setItem('cf_expenses',JSON.stringify(expenses));
    localStorage.setItem('cf_goals',JSON.stringify(goals));
    localStorage.setItem('cf_categories',JSON.stringify(categories));
    localStorage.setItem('cf_user',JSON.stringify(user))}
function money(v){return Number(v||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'})}
function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
function todayISO(){return new Date().toISOString().slice(0,10)}
function prettyDate(s){if(!s)return '—';return new Date(s+'T12:00:00').toLocaleDateString('pt-BR')}
function total(arr){return arr.reduce((a,e)=>a+Number(e.value||0),0)}
function toast(t){let x=document.getElementById('toast');
    x.textContent=t;
    x.classList.add('show');
    setTimeout(()=>x.classList.remove('show'),2400)
}

document.getElementById('today').textContent=new Date().toLocaleDateString('pt-BR', {day:'2-digit',month:'short',year:'numeric'});
document.getElementById('userLabel').textContent=user.name||'Usuário';

function setPage(p){page=p;paymentFilter='';document.querySelectorAll('#nav button').forEach(b=>b.classList.toggle('active',b.dataset.page===p));render()}

document.querySelectorAll('[data-page]').forEach(b=>b.onclick=()=>setPage(b.dataset.page));
document.querySelectorAll('[data-filter-pay]').forEach(b=>b.onclick=()=>{paymentFilter=b.dataset.filterPay;page='expenses';
document.querySelectorAll('#nav button').forEach(x=>x.classList.toggle('active',x.dataset.page==='expenses')); render()});

function head(title,sub,button=''){return `
    <div class="pagehead">
        <div><h1>${title}</h1><p>${sub}</p>
        </div>${button}
    </div>`}

function stat(label,value,note,ico)
    {return `
        <div class="card">
            <div class="stat-label">
                <span>${ico}</span>${label}
            </div>
            <div class="stat-value">${value}</div>
            <div class="stat-note">${note}</div>
        </div>`}

function expenseRows(arr){if(!arr.length)return `<tr><td colspan="8" class="empty">Nenhum lançamento encontrado.</td></tr>`;
    return arr.map(e=>`<tr><td>
        <div class="item-name">
            <span class="cat-icon">${iconFor(e.category)}</span>
            <span>${esc(e.description)}</span>
        </div></td>
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
    </tr>`).join('')}

function iconFor(c){return ({Alimentação:'♨',Supermercado:'▣',Vestuário:'◇',Lazer:'◉',Transporte:'↗',Moradia:'⌂',Saúde:'✚',Outros:'◆'})[c]||'◆'}

function expenseTable(arr){return 
    `<div class="card table-wrap">
        <table><thead><tr>
        th>DESCRIÇÃO</th>
        <th>VALOR</th>
        <th>DATA</th>
        <th>QUEM GASTOU</th>
        <th>PARCELADO</th>
        <th>PAGAMENTO</th>
        <th>CATEGORIA</th>
        <th>AÇÕES</th>
        </tr></thead><tbody>${expenseRows(arr)}</tbody>
        </table>
    </div>`}

function filtered(){
    return expenses.filter(e=>(
        !paymentFilter||
        e.payment===paymentFilter)
        &&
        (!searchTerm||
        (e.description+' '+e.category+' '+e.who+' '+e.payment).toLowerCase().includes(searchTerm.toLowerCase())))}

function homePage(){let sum=total(expenses),
    month=expenses.filter(e=>e.date.slice(0,7)===todayISO().slice(0,7)),
    monthSum=total(month),
    income=4500,
    goal=3500;
    return `${head('Visão geral',
        'Acompanhe suas finanças e mantenha seus objetivos em dia.',
        `<button class="btn primary" onclick="openExpense()">＋ Novo lançamento</button>`)}
        <div class="grid stats">
            ${stat('Gastos registrados',
            money(sum),
            '↗ Atualizado com seus lançamentos',
            '▥')}
            ${stat('Gastos do mês',
                money(monthSum),
                'Este mês',
                '▤')}
            ${stat('Saldo estimado',
                money(income-sum),
                'Considerando renda mensal de '+
                money(income),
                '◉')}
            ${stat('Lançamentos',
                expenses.length,
                'Registros cadastrados',
                '☷')}
        </div>
        <div class="grid two-col">
            <div class="card">
                    <h3 class="section-title">Últimos lançamentos</h3>
                    ${expenseTableMini(expenses.slice().sort((a,b)=>b.date.localeCompare(a.date)).slice(0,5))}
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
                        <strong style="color:var(--green)">${money(goal)}</strong>
                    </div>
                    <div class="progress" style="margin:12px 0">
                        <span style="width:${Math.min(100,monthSum/goal*100)}%"></span>
                    </div>
                    <small class="muted">${Math.max(0,goal-monthSum)>0?money(goal-monthSum)+' disponíveis':money(monthSum-goal)+' acima do limite'}</small>
                </div>
            </div>
        </div>`}

function expenseTableMini(arr){return 
    `<div class="table-wrap">
        <table><thead><tr>
            <th>LANÇAMENTO</th>
            <th>DATA</th>
            <th>VALOR</th>
            </tr></thead>
            <tbody>${arr.length?arr.map(e=>`<tr><td>
            <div class="item-name">
                <span class="cat-icon">${iconFor(e.category)}</span>
                ${esc(e.description)}
            </div></td>
            <td>${prettyDate(e.date)}</td>
            <td><strong>${money(e.value)}</strong></td></tr>`).join(''):
            `<tr><td colspan="3" class="empty">Sem lançamentos ainda.</td></tr>`}</tbody>
        </table>
    </div>`}

function categoryBars(){let sums=categories.map(c=>({name:c,value:total(expenses.filter(e=>e.category===c))})).sort((a,b)=>b.value-a.value).filter(x=>x.value>0);
if(!sums.length)return '<p class="muted">Adicione lançamentos para ver os dados.</p>';
let max=Math.max(...sums.map(x=>x.value));
    return `
    <div class="bar-list">
        ${sums.slice(0,6).map(x=>`
        <div class="bar-row">
            <span>${esc(x.name)}</span>
            <div class="bar">
                <span style="width:${x.value/max*100}%"></span>
            </div>
            <strong>${money(x.value)}</strong>
        </div>`).join('')}
    </div>`}

function expensesPage(){let arr=filtered().slice().sort((a,b)=>b.date.localeCompare(a.date));
    return `${head('Lançamentos','Organize e acompanhe todas as suas despesas.',
        `<button class="btn primary" onclick="openExpense()">＋ Novo lançamento</button>`)}
        <div class="grid stats">
            ${stat('Total filtrado',
            money(total(arr)),
            'Soma dos lançamentos exibidos','▥')}
            ${stat('Quantidade',arr.length,'Lançamentos encontrados','☷')}
            ${stat('Crédito',money(total(arr.filter(e=>e.payment==='Crédito'))),'Compras no crédito','▣')}
            ${stat('Pix',money(total(arr.filter(e=>e.payment==='Pix'))),
            'Pagamentos via Pix','▦')}
        </div>
        <div class="toolbar">
            <input class="field search" id="searchExpense" placeholder="⌕ Pesquisar por nome, gasto, categoria..." value="
            ${esc(searchTerm)}">
            <select class="field" id="categoryFilter">
            <option value="">Todas as categorias</option>
            ${categories.map(c=>`<option ${new URLSearchParams(location.search).get('cat')===c?'selected':''}>
            ${esc(c)}</option>`).join('')}</select>
            <select class="field" id="monthFilter"><option value="">Todos os períodos</option>
            ${[...new Set(expenses.map(e=>e.date.slice(0,7)))].sort().reverse().map(m=>`
                <option value="${m}">${m.split('-').reverse().join('/')}</option>`).join('')}</select>
            ${paymentFilter?`<button class="chip active" onclick="paymentFilter='';render()">
            ${paymentFilter} ×</button>`:''}
            <button class="btn" onclick="resetFilters()">Limpar filtros</button>
        </div>
        <div id="expenseTable">${expenseTable(arr)}</div>`}

function reportsPage(){let sum=total(expenses),
    month=expenses.filter(e=>e.date.slice(0,7)===todayISO().slice(0,7)),
    byPay=[
        'Crédito',
        'Débito',
        'Pix',
        'Dinheiro',
        'VR',
        'VA',
        'Ticket'].map(p=>({p,v:total(expenses.filter(e=>e.payment===p))})).filter(x=>x.v);
    let top=categories.map(c=>({c,v:total(expenses.filter(e=>e.category===c))})).sort((a,b)=>b.v-a.v).slice(0,5);
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
            ${stat('Total de gastos',money(sum),'Em todos os lançamentos','↓')}
            ${stat('Gastos do período',money(total(month)),'Mês atual','▥')}
            ${stat('Média por lançamento',money(expenses.length?sum/expenses.length:0),'Valor médio','◉')}
            ${stat('Categorias utilizadas',new Set(expenses.map(e=>e.category)).size,'Com gastos registrados','◆')}
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
                ${categoryBars()}<hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
                <h3 class="section-title">Forma de pagamento</h3>
                ${byPay.length?byPay.map(x=>`
                    <div class="payrow">
                        <span><span class="cat-icon" style="display:inline-grid;vertical-align:middle;margin-right:8px;width:27px;height:27px">▣</span>${x.p}</span>
                        <strong>${money(x.v)}</strong>
                    </div>`).join(''):'<p class="muted">Sem dados disponíveis.</p>'}
                </div>
        </div>
        <div class="grid two-col" style="margin-top:14px">
            <div class="card">
                <h3 class="section-title">Despesas por período</h3>
                <canvas class="chart" id="barChart"></canvas>
            </div>
            <div class="card">
                <h3 class="section-title">Maiores categorias</h3>${top.map((x,i)=>`
                    <div class="payrow">
                        <span><span class="tag">${i+1}</span> ${esc(x.c)}</span>
                        <strong>${money(x.v)}</strong>
                    </div>`).join('')}
            </div>
        </div>`}

function drawCharts(){['lineChart','barChart'].forEach((id,idx)=>{
    let c=document.getElementById(id);if(!c)return;
    let r=c.getBoundingClientRect(),dpr=window.devicePixelRatio||1;c.width=r.width*dpr;c.height=r.height*dpr;
    let x=c.getContext('2d');x.scale(dpr,dpr);
    let w=r.width,h=r.height;x.clearRect(0,0,w,h);
    let pad=28;for(let i=0;i<4;i++)
        {let y=pad+(h-pad*1.5)*i/3;x.strokeStyle='#e8e9df';x.beginPath();x.moveTo(pad,y);x.lineTo(w-8,y);x.stroke()}
    let labels=idx?['Sem 1','Sem 2','Sem 3','Sem 4','Sem 5']:['01','05','10','15','20','25','30'];
    let vals=labels.map((_,i)=>{let ds=expenses.filter(e=>{let day=Number(e.date.slice(-2));return idx?Math.min(4,Math.floor((day-1)/7))===i:Math.abs(day-(i*5+1))<5});
    return total(ds)});
    let max=Math.max(1,...vals)*1.2;
    let xx=i=>pad+(w-pad-12)*(i/(vals.length-1||1)),yy=v=>h-pad-(h-pad*1.5)*v/max;if(idx)
        {let bw=(w-pad-12)/vals.length*.48;vals.forEach((v,i)=>
            {x.fillStyle='#52765e';
                x.fillRect(xx(i)-bw/2,yy(v),bw,h-pad-yy(v));
                x.fillStyle='#7d887b';
                x.font='10px Arial';
                x.textAlign='center';
                x.fillText(labels[i],xx(i),h-5)})}
            else{ x.beginPath();vals.forEach((v,i)=>i?
                x.lineTo(xx(i),yy(v)):x.moveTo(xx(i),yy(v)));
                x.strokeStyle='#315f47';
                x.lineWidth=2;x.stroke();vals.forEach((v,i)=>{x.beginPath();
                x.arc(xx(i),yy(v),3,0,Math.PI*2);
                x.fillStyle='#315f47';
                x.fill();
                x.fillStyle='#7d887b';
                x.font='10px Arial';
                x.textAlign='center';
                x.fillText(labels[i],xx(i),h-5)})}})}
function categoriesPage(){
    return `${head(
        'Categorias',
        'Gerencie as categorias usadas para organizar seus gastos.',
        `<button class="btn primary" onclick="addCategory()">＋ Nova categoria</button>`)}
        <div class="grid stats">
            ${stat('Categorias',categories.length,'Cadastradas','◆')}
            ${stat('Com movimentação',categories.filter(c=>expenses.some(e=>e.category===c)).length,'Em uso','▥')}
            ${stat('Sem movimentação',categories.filter(c=>!expenses.some(e=>e.category===c)).length,'Disponíveis para uso','◉')}
            ${stat('Total de gastos',money(total(expenses)),'Todos os registros','↓')}
        </div>
        <div class="card">
            <div class="table-wrap">
                <table><thead><tr>
                    <th>CATEGORIA</th>
                    <th>LANÇAMENTOS</th>
                    <th>TOTAL GASTO</th>
                    <th>STATUS</th>
                    <th>AÇÕES</th>
                    </tr></thead>
                    <tbody> ${categories.map(c=>`<tr><td>
                <div class="item-name"><span class="cat-icon">${iconFor(c)}</span>
                    <strong>${esc(c)}</strong>
                </div></td>
                <td>${expenses.filter(e=>e.category===c).length}</td>
                <td>${money(total(expenses.filter(e=>e.category===c)))}</td>
                <td><span class="tag">Ativa</span></td>
                <td class="actions">
                <button onclick="renameCategory('${esc(c).replace(/'/g,"\\'")}')">✎ Renomear</button>
                <button onclick="removeCategory('${esc(c).replace(/'/g,"\\'")}')">⌫</button></td></tr>`).join('')}</tbody></table>
            </div>
        </div>`}
function goalsPage(){
    let fixed=goals.filter(g=>g.name.toLowerCase().includes('salário')||g.name.toLowerCase().includes('fixo')).reduce((a,g)=>a+g.target,0)||2850,variable=1250;
    return `${head('Metas','Defina seus objetivos e acompanhe seu progresso.',`<button class="btn primary" onclick="openGoal()">＋ Criar nova meta</button>`)}
    <div class="grid goal-cards">
        <div class="card goal-card">
            <div class="bigicon">▣</div>
            <div><span class="muted" style="font-size:11px">Renda fixa mensal</span><strong>${money(fixed)}</strong>
                <div class="progress" style="width:160px"><span style="width:72%"></span></div>
            </div>
        </div>
        <div class="card goal-card">
            <div class="bigicon">＄</div>
            <div><span class="muted" style="font-size:11px">Renda variável</span><strong>${money(variable)}</strong>
                <div class="progress" style="width:160px"><span style="width:30%"></span></div>
            </div>
        </div>
    </div>
    <div class="card">
        <h3 class="section-title">Minhas metas</h3>
        <p class="muted" style="font-size:11px;margin-top:-7px">Acompanhe o progresso das suas metas e mantenha o foco nos seus objetivos.</p>
        ${goals.length?goals.map(g=>{
            let p=Math.min(100,g.saved/g.target*100);
            return `<div class="goal-line"><div class="bigicon">${esc(g.icon||'◎')}</div><div><strong>
            ${esc(g.name)}</strong><small>
            ${money(g.saved)} guardados de 
            ${money(g.target)}</small>
                <div class="progress" style="margin-top:9px"><span style="width:${p}%"></span></div></div>
                <div class="goal-amount"><strong>
            ${money(g.target)}</strong><small>
            ${prettyDate(g.due)}</small><span class="tag">
            ${Math.round(p)}% concluído</span></div>
            <div class="actions"><button onclick="editGoal(${g.id})" title="Atualizar">✎</button>
            <button onclick="deleteGoal(${g.id})" title="Excluir">⌫</button>
            </div>
                </div>`}).join(''):'<div class="empty">Você ainda não criou metas.</div>'}
            </div>`}
function settingsPage(){
    return `${head('Configurações','Personalize sua experiência e gerencie suas preferências.')}
    <div class="grid settings-layout">
        <div class="grid">
            <div class="card">
                <h3 class="section-title">Preferências do sistema</h3>
                <div class="setting-row">
                    <div><strong>Aparência</strong><small>Escolha o tema visual do aplicativo</small></div>
                    <span class="tag">Bege + verde</span>
                </div>
                div class="setting-row">
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
                    <div><strong>Backup</strong><small>Os dados deste protótipo ficam neste navegador</small></div>
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
                    <div><strong>Limpar dados locais</strong><small>Apague os dados salvos neste navegador</small></div>
                    <button class="btn danger" onclick="clearData()">Limpar</button>
                </div>
            </div>
        </div>
        <div class="grid">
            <div class="card profile">
                <div class="avatar">♙</div>
                <h3>Minha conta</h3><p class="muted" style="font-size:11px">Gerencie suas informações pessoais e de acesso.</p>
                <div class="setting-row">
                    <div><strong>Nome</strong><small>${esc(user.name||'Usuário')}</small></div>
                    <button class="btn" onclick="editProfile()">Editar</button>
                </div>
                <div class="setting-row">
                    <div><strong>E-mail</strong><small>${esc(user.email||'Não informado')}</small></div>
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
                <button class="btn danger" onclick="document.getElementById('loginPage').classList.add('show')">Sair da conta</button>
            </div>
        </div>
    </div>`}

function render(){
    let el=document.getElementById('page');el.innerHTML=({
        home:homePage,
        expenses:expensesPage,
        reports:reportsPage,
        categories:categoriesPage,
        goals:goalsPage,
        settings:settingsPage}
        [page]||homePage)();
        if(page==='expenses')
            {let s=document.getElementById('searchExpense');
                s.oninput=()=>{searchTerm=s.value;
                document.getElementById('expenseTable').innerHTML=expenseTable(filtered().filter(e=>{let c=document.getElementById('categoryFilter').value,
                m=document.getElementById('monthFilter').value;
                return(!c||e.category===c)&&(!m||e.date.startsWith(m))}).sort((a,b)=>b.date.localeCompare(a.date)))};
                document.getElementById('categoryFilter').onchange=()=>
                    {let c=document.getElementById('categoryFilter').value;
                        document.getElementById('expenseTable').innerHTML=expenseTable(filtered().filter(e=>(!c||e.category===c)&&
                        (!document.getElementById('monthFilter').value||e.date.startsWith(document.getElementById('monthFilter').value))));};
                        document.getElementById('monthFilter').onchange=()=>document.getElementById('categoryFilter').dispatchEvent(new Event('change'));}
                        if(page==='reports'){drawCharts();
                            document.getElementById('reportPeriod').onchange=e=>toast('Visualização selecionada: '+e.target.value)}}

function resetFilters(){searchTerm='';
    paymentFilter='';
    render()}function openExpense(id=null){editingId=id;
        let f=document.getElementById('expenseForm');
        f.reset();
        document.getElementById('expenseCategory').innerHTML=categories.map(c=>`<option>${esc(c)}</option>`).join('');
        f.elements.date.value=todayISO();
        f.elements.who.value=user.name||'Eu';
        document.getElementById('modalTitle').textContent=id?'Editar lançamento':'Novo lançamento';
        if(id){let e=expenses.find(x=>x.id===id);
            if(e){f.elements.description.value=e.description;f.elements.value.value=e.value;f.elements.date.value=e.date;f.elements.category.value=e.category;
                f.elements.payment.value=e.payment;f.elements.who.value=e.who;f.elements.place.value=e.place;f.elements.installments.value=e.installments}}
                document.getElementById('modalBack').classList.add('show')}
        function editExpense(id){openExpense(id)}
        function deleteExpense(id){if(confirm('Deseja excluir este lançamento?')){expenses=expenses.filter(e=>e.id!==id);
            save();
            render();
            updateMini();
            toast('Lançamento excluído.')}}
        document.getElementById('closeModal').onclick=document.getElementById('cancelModal').onclick=()=>
            document.getElementById('modalBack').classList.remove('show');document.getElementById('expenseForm').onsubmit=e=>
                {e.preventDefault();let f=e.target,old=expenses.find(x=>x.id===editingId),
                    entry={id:editingId||Date.now(),description:f.elements.description.value.trim(),value:Number(f.elements.value.value),date:f.elements.date.value,
                        category:f.elements.category.value,who:f.elements.who.value.trim()||'Eu',payment:f.elements.payment.value,place:f.elements.place.value,
                        installments:f.elements.installments.value};
                        if(old)expenses=expenses.map(x=>x.id===editingId?entry:x);
                        else expenses.push(entry);
                        save();
                        document.getElementById('modalBack').classList.remove('show');
                        render();
                        updateMini();
                        toast(old?'Lançamento atualizado!':'Lançamento salvo!')};

function updateMini(){
    let spent=total(expenses.filter(e=>e.date.startsWith(todayISO().slice(0,7)))),
    goal=3500;document.getElementById('mini-progress').style.width=Math.min(100,spent/goal*100)+'%';
    document.getElementById('mini-note').textContent=spent<goal?money(goal-spent)+' restantes':money(spent-goal)+' acima do limite'}
    function addCategory(){let n=prompt('Nome da nova categoria:');
        if(n&&n.trim()&&!categories.includes(n.trim())){categories.push(n.trim());
            save();
            render();
            toast('Categoria adicionada.')}}
        function renameCategory(c){let n=prompt('Novo nome para '+c+':',c);
            if(n&&n.trim()&&n.trim()!==c){categories=categories.map(x=>x===c?n.trim():x);
                expenses=expenses.map(e=>e.category===c?{...e,category:n.trim()}:e);
                save();
                render();
                toast('Categoria renomeada.')}}
            function removeCategory(c){if(expenses.some(e=>e.category===c))
                {alert('Esta categoria possui lançamentos. Reatribua ou exclua esses lançamentos antes de removê-la.');
                    return}if(confirm('Remover a categoria '+c+'?')){categories=categories.filter(x=>x!==c);
                        save();
                        render()}}
function openGoal(){document.getElementById('goalForm').reset();
    document.getElementById('goalForm').elements.due.value=todayISO();
    document.getElementById('goalModalBack').classList.add('show')}document.getElementById('closeGoal').onclick=document.getElementById('cancelGoal').onclick=()=>
        document.getElementById('goalModalBack').classList.remove('show');
    document.getElementById('goalForm').onsubmit=e=>{e.preventDefault();
        let f=e.target;goals.push({id:Date.now(),name:f.elements.name.value.trim(),target:Number(f.elements.target.value),saved:Number(f.elements.saved.value),
            due:f.elements.due.value,icon:f.elements.icon.value});save();
            document.getElementById('goalModalBack').classList.remove('show');render();toast('Meta criada!')};
            function editGoal(id){let g=goals.find(x=>x.id===id),n=prompt('Quanto você já guardou para '+g.name+'? (R$)',g.saved);
                if(n!==null&&Number.isFinite(Number(n))&&Number(n)>=0){g.saved=Number(n);
                    save();
                    render();
                    toast('Progresso atualizado.')}}
                    function deleteGoal(id){if(confirm('Excluir esta meta?')){goals=goals.filter(g=>g.id!==id);
                        save();
                        render()}}
function editProfile(){
    let n=prompt('Seu nome:',user.name||'');
    if(n===null)return;
    let em=prompt('Seu e-mail:',user.email||'');
    if(em===null)return;user={name:n.trim()||'Usuário',email:em.trim()};
    document.getElementById('userLabel').textContent=user.name;
    save();
    render();
    toast('Perfil atualizado.')}
    function exportData(){let blob=new Blob([JSON.stringify({expenses,goals,categories,user},null,2)],{type:'application/json'}),a=document.createElement('a');
    a.href=URL.createObjectURL(blob);
    a.download='controle-financeiro-dados.json';a.click();URL.revokeObjectURL(a.href);toast('Exportação iniciada.')}
    function clearData(){if(confirm('Isso apagará todos os dados locais deste protótipo. Deseja continuar?')){localStorage.removeItem('cf_expenses');
        localStorage.removeItem('cf_goals');
        localStorage.removeItem('cf_categories');
        expenses=[];
        goals=[];
        categories=[];
        render();
        updateMini();
        toast('Dados locais apagados.')}}
document.getElementById('loginForm').onsubmit=e=>{e.preventDefault();
    let f=e.target;user.email=f.elements.email.value;user.name=user.email.split('@')[0]||'Usuário';
    save();
    document.getElementById('userLabel').textContent=user.name;document.getElementById('loginPage').classList.remove('show');
    toast('Bem-vindo!')};
    document.getElementById('userPill').onclick=()=>setPage('settings');
    window.addEventListener('resize',()=>{if(page==='reports')drawCharts()});
    updateMini();
    render();
