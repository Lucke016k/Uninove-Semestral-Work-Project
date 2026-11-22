<?php

// Inicia a sessão.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Importa a conexão.
require_once __DIR__ . '/config/conexao.php';

// Importa as funções de autenticação.
require_once __DIR__ . '/config/auth.php';

// Verifica se existe um usuário autenticado.
exigirLoginPagina("PaginaLogin/login.php");

// Recupera o ID do usuário.
$idUsuario = $_SESSION['usuario']['id'] ?? null;

if (!$idUsuario) {
    header('Location: PaginaLogin/login.php');
    exit;
}

// Consulta se a conta continua ativa.
$sql = "
    SELECT ATIVO
    FROM usuario
    WHERE ID_USUARIO = :usuario
    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':usuario' => $idUsuario
]);

$ativo = $stmt->fetchColumn();

// Bloqueia contas desativadas.
if ($ativo === false || (int) $ativo !== 1) {

    // Limpa os dados da sessão.
    $_SESSION = [];

    // Remove o cookie da sessão.
    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);
    }

    session_destroy();

    header('Location: PaginaLogin/login.php?status=conta_desativada');
    exit;
}

// Gera o token CSRF.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>UniFinance</title>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES,'UTF-8') ?>">
    <script src="theme.js"></script>
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="img/favicon.png">
</head>
<body>
    <!-- Estrutura principal do aplicativo: menu lateral e área de conteúdo. -->
    <div class="app">
        <!-- Menu lateral: navegação, filtros rápidos de pagamento e resumo mensal. -->
       <aside class="sidebar">
    <div class="brand">
        <img src="img/unifinance_icone_192.png" alt="UniFinance" class="brand-logo">
        <div>
            <strong>UniFinance</strong>
            <small>Seus gastos, seus objetivos</small>
        </div>
    </div>
    <div>
        <div class="nav-label">Menu principal</div>
                <!-- data-page identifica qual tela o JavaScript deve renderizar. -->
                <nav class="nav" id="nav">
                    <button data-page="home" class="active">
                        <span class="ico">⌂</span>Início
                    </button>
                    <button data-page="expenses">
                        <span class="ico">☷</span>
                        Lançamentos
                    </button>
                    <button data-page="reports">
                        <span class="ico">▥</span>
                        Relatórios
                    </button>
                    <button data-page="categories">
                        <span class="ico">◆</span>
                        Categorias
                    </button>
                    <button data-page="goals">
                        <span class="ico">◎</span>
                        Metas
                    </button>
                    <button data-page="settings">
                        <span class="ico">⚙</span>
                        Configurações
                    </button>
                </nav>
            </div>
            <div>
                <div class="nav-label">Formas de pagamento</div>
                <!-- data-filter-pay aplica um filtro por forma de pagamento. -->
                <nav class="nav">
                    <button data-filter-pay="Crédito">
                        <span class="ico">▣</span>
                        Crédito
                    </button>
                    <button data-filter-pay="Débito">
                        <span class="ico">▤</span>
                        Débito
                    </button>
                    <button data-filter-pay="Pix">
                        <span class="ico">▦</span>
                        Pix
                    </button>
                    <button data-filter-pay="VR">
                        <span class="ico">▧</span>
                        VR (Vale Refeição)
                    </button>
                    <button data-filter-pay="VA">
                        <span class="ico">▧</span>
                        VA (Vale Alimentação)
                    </button>
                    <button data-filter-pay="Ticket">
                        <span class="ico">▤</span>
                        Ticket
                    </button>
                    <button data-filter-pay="Dinheiro">
                        <span class="ico">♙</span>
                        Dinheiro
                    </button>
                </nav>
            </div>
            <div class="goal-mini" id="goalMini" role="button" tabindex="0" title="Editar meta de gasto" onclick="openSpendGoal()">
                <span class="goal-mini-head">◎ Meta de gasto <em>Editar</em></span>
                <strong id="mini-goal">R$ 0,00</strong>
                <div class="progress">
                    <span id="mini-progress" style="width:0%"></span>
                </div>
                <small id="mini-note"></small>
                <small id="mini-period"></small>
            </div>
        </aside>

        <!-- Conteúdo principal; o JavaScript injeta aqui a tela selecionada. -->
        <main class="main">
            <div class="topbar">
                <button class="pill">▦ 
                    <span id="today"></span>
                </button>
                <button class="pill" id="userPill">
    <span class="avatar">♙</span>
    <span id="userLabel">
        <?= htmlspecialchars($_SESSION["usuario"]["nome"] ?? "Usuário", ENT_QUOTES, "UTF-8") ?>
    </span>
</button>
            </div>
            <!-- Área dinâmica: Início, Lançamentos, Relatórios, Categorias, Metas ou Configurações. -->
            <section id="page"></section>
        </main>
    </div>
    <!-- Modal de criação/edição de lançamento financeiro. -->
    <div class="modal-back" id="modalBack">
        <div class="modal">
            <div class="modal-head">
                <h2 id="modalTitle">Novo lançamento</h2>
                <button class="close" id="closeModal">×</button>
            </div>
            <!-- Os names dos campos são lidos pelo JavaScript ao salvar o formulário. -->
            <form id="expenseForm">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Descrição</label>
                        <input name="description" required placeholder="Ex.: Compras no mercado">
                    </div>
                    <div class="form-group">
                        <label>Valor (R$)</label>
                        <input name="value" type="number" min="0.01" step="0.01" required placeholder="0,00">
                    </div>
                    <div class="form-group">
                        <label>Data</label>
                        <input name="date" type="date" required>
                    </div>
                    <div class="form-group">
                        <label>Categoria</label>
                        <select name="category" id="expenseCategory" data-dd-icon="🏷️"></select>
                    </div>
                    <div class="form-group">
                        <label>Forma de pagamento</label>
                        <select name="payment">
                            <option>Pix</option>
                            <option>Crédito</option>
                            <option>Débito</option>
                            <option>Dinheiro</option>
                            <option>VR</option>
                            <option>VA</option>
                            <option>Ticket</option>
                        </select>
                    </div>
                    <div class="form-group" id="installmentsGroup">
                        <label>Parcelado?</label>
                        <select name="installments">
                            <option value="Não">Não</option>
                            <option value="Sim">Sim</option>
                        </select>
                    </div>
                    <div class="form-group" id="installmentCountGroup" hidden>
                        <label>Quantidade de parcelas</label>
                        <input name="installment_count" type="number" min="2" max="48" step="1" placeholder="Ex.: 12" disabled>
                        <small class="field-hint" id="installmentHint"></small>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelModal">Cancelar</button>
                    <button class="btn primary" type="submit">Salvar lançamento</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal para cadastrar uma meta financeira. -->
    <div class="modal-back" id="goalModalBack">
        <div class="modal">
            <div class="modal-head">
                <h2>Nova meta</h2>
                <button class="close" id="closeGoal">×</button>
            </div>
            <form id="goalForm">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Nome da meta</label>
                        <input name="name" required placeholder="Ex.: Viagem dos sonhos">
                    </div>
                    <div class="form-group">
                        <label>Valor desejado (R$)</label>
                        <input name="target" type="number" min="1" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Já guardado (R$)</label>
                        <input name="saved" type="number" min="0" step="0.01" value="0" required>
                    </div>
                    <div class="form-group">
                        <label>Prazo</label>
                        <input name="due" type="date" required>
                    </div>
                    <div class="form-group">
                        <label>Ícone</label>
                        <select name="icon">
                            <option value="✈️" data-icon="✈️">Viagem</option>
                            <option value="🏠" data-icon="🏠">Imóvel</option>
                            <option value="🚗" data-icon="🚗">Automóvel</option>
                            <option value="🎓" data-icon="🎓">Estudos</option>
                            <option value="💍" data-icon="💍">Casamento</option>
                            <option value="📱" data-icon="📱">Eletrônicos</option>
                            <option value="🏖️" data-icon="🏖️">Férias</option>
                            <option value="💰" data-icon="💰">Reserva</option>
                            <option value="🎁" data-icon="🎁">Presente</option>
                            <option value="🎯" data-icon="🎯">Outro objetivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelGoal">Cancelar</button>
                    <button class="btn primary">Criar meta</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal para editar uma meta existente. -->
    <div class="modal-back" id="goalEditModalBack">
        <div class="modal modal-edit" role="dialog" aria-modal="true" aria-labelledby="goalEditTitle">
            <div class="modal-head">
                <div class="modal-title">
                    <span class="modal-icon" id="goalEditIcon">🎯</span>
                    <div>
                        <h2 id="goalEditTitle">Editar meta</h2>
                        <small id="goalEditSubtitle">Atualize o valor guardado</small>
                    </div>
                </div>
                <button type="button" class="close" id="closeGoalEdit" aria-label="Fechar">×</button>
            </div>
            <form id="goalEditForm">
                <input type="hidden" name="id">
                <div class="goal-edit-progress">
                    <div class="progress"><span id="goalEditBar" style="width:0%"></span></div>
                    <small id="goalEditPercent">0% concluído</small>
                </div>
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Nome da meta</label>
                        <input name="name" required maxlength="480" placeholder="Ex.: Viagem dos sonhos">
                    </div>
                    <div class="form-group">
                        <label>Valor desejado (R$)</label>
                        <input name="target" type="number" min="1" step="0.01" required>
                    </div>
                    <div class="form-group full goal-saved-block">
                        <h3 class="income-section"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><circle cx="12" cy="12" r="11" fill="currentColor"/><path d="M12 7v10M7 12h10" style="stroke:var(--panel)" stroke-width="2.2" stroke-linecap="round"/></svg>Atualizar valor guardado</h3>
                        <p class="income-help">Guardado atual: <strong id="goalEditCurrent">R$ 0,00</strong>. Some um valor ao atual ou substitua pelo novo valor.</p>
                        <div class="opt-grid">
                            <label class="opt-card">
                                <input type="radio" name="modo" value="adicionar" checked>
                                <span class="opt-box">
                                    <span class="opt-radio"></span>
                                    <span class="opt-ico"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4"/><path d="M8 3v4"/><path d="M3 11h18"/><path d="M12 14v4"/><path d="M10 16h4"/></svg></span>
                                    <span class="opt-text"><strong>Adicionar</strong><small>Soma ao valor atual</small></span>
                                </span>
                            </label>
                            <label class="opt-card">
                                <input type="radio" name="modo" value="substituir">
                                <span class="opt-box">
                                    <span class="opt-radio"></span>
                                    <span class="opt-ico"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 0 0-15.5-6.2L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 15.5 6.2L21 16"/><path d="M21 21v-5h-5"/></svg></span>
                                    <span class="opt-text"><strong>Substituir</strong><small>Troca o valor atual</small></span>
                                </span>
                            </label>
                        </div>
                        <label>Valor (R$)</label>
                        <input name="valor" type="number" min="0" step="0.01" placeholder="Ex.: 500,00">
                        <p class="income-hint" id="goalEditHint"></p>
                        <input type="hidden" name="saved" value="0">
                    </div>
                    <div class="form-group">
                        <label>Prazo</label>
                        <input name="due" type="date" required>
                    </div>
                    <div class="form-group">
                        <label>Ícone</label>
                        <select name="icon">
                            <option value="✈️" data-icon="✈️">Viagem</option>
                            <option value="🏠" data-icon="🏠">Imóvel</option>
                            <option value="🚗" data-icon="🚗">Automóvel</option>
                            <option value="🎓" data-icon="🎓">Estudos</option>
                            <option value="💍" data-icon="💍">Casamento</option>
                            <option value="📱" data-icon="📱">Eletrônicos</option>
                            <option value="🏖️" data-icon="🏖️">Férias</option>
                            <option value="💰" data-icon="💰">Reserva</option>
                            <option value="🎁" data-icon="🎁">Presente</option>
                            <option value="🎯" data-icon="🎯">Outro objetivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelGoalEdit">Cancelar</button>
                    <button class="btn primary" type="submit">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal do período exibido (vale para todo o sistema). -->
    <div class="modal-back" id="periodModalBack">
        <div class="modal modal-edit modal-profile modal-period" role="dialog" aria-modal="true" aria-labelledby="periodTitle">
            <div class="modal-head">
                <div class="modal-title">
                    <span class="modal-icon" aria-hidden="true">📅</span>
                    <div>
                        <h2 id="periodTitle">Período exibido</h2>
                        <small>Vale para lançamentos, categorias e relatórios</small>
                    </div>
                </div>
                <button type="button" class="close" id="closePeriod" aria-label="Fechar">×</button>
            </div>
            <form id="periodForm">
                <div class="period-presets" id="periodPresets">
                    <button type="button" class="chip" data-preset="month">Este mês</button>
                    <button type="button" class="chip" data-preset="lastmonth">Mês passado</button>
                    <button type="button" class="chip" data-preset="last30">Últimos 30 dias</button>
                    <button type="button" class="chip" data-preset="year">Este ano</button>
                    <button type="button" class="chip" data-preset="all">Todo o período</button>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>De</label>
                        <input name="start" type="date">
                    </div>
                    <div class="form-group">
                        <label>Até</label>
                        <input name="end" type="date">
                    </div>
                    <p class="income-hint full-col" id="periodHint"></p>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelPeriod">Cancelar</button>
                    <button class="btn primary" type="submit">Aplicar período</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de aporte em meta: pergunta de qual lucro sai o valor e confere o saldo. -->
    <div class="modal-back" id="aporteModalBack">
        <div class="modal modal-edit modal-profile" role="dialog" aria-modal="true" aria-labelledby="aporteTitle">
            <div class="modal-head">
                <div class="modal-title">
                    <span class="modal-icon" id="aporteIcon" aria-hidden="true">🎯</span>
                    <div>
                        <h2 id="aporteTitle">De onde sai o valor?</h2>
                        <small id="aporteSub"></small>
                    </div>
                </div>
                <button type="button" class="close" id="closeAporte" aria-label="Fechar">×</button>
            </div>
            <form id="aporteForm">
                <p class="income-help">O valor será descontado do saldo do mês e registrado nos relatórios. Escolha de qual lucro ele sai:</p>
                <div class="opt-grid">
                    <label class="opt-card">
                        <input type="radio" name="origem" value="FIXA">
                        <span class="opt-box">
                            <span class="opt-radio"></span>
                            <span class="opt-text"><strong>Lucro fixo</strong><small id="aporteAvailFIXA"></small></span>
                        </span>
                    </label>
                    <label class="opt-card">
                        <input type="radio" name="origem" value="VARIAVEL">
                        <span class="opt-box">
                            <span class="opt-radio"></span>
                            <span class="opt-text"><strong>Lucro variável</strong><small id="aporteAvailVARIAVEL"></small></span>
                        </span>
                    </label>
                </div>
                <p class="income-hint" id="aporteHint"></p>
                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelAporte">Voltar</button>
                    <button class="btn primary" type="submit" id="confirmAporte">Confirmar aporte</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal da meta de gasto (valor limite + período de até 30 dias). -->
    <div class="modal-back" id="spendGoalModalBack">
        <div class="modal modal-edit modal-profile" role="dialog" aria-modal="true" aria-labelledby="spendGoalTitle">
            <div class="modal-head">
                <div class="modal-title">
                    <span class="modal-icon" aria-hidden="true">◎</span>
                    <div>
                        <h2 id="spendGoalTitle">Meta de gasto</h2>
                        <small>Defina o limite e o período</small>
                    </div>
                </div>
                <button type="button" class="close" id="closeSpendGoal" aria-label="Fechar">×</button>
            </div>
            <form id="spendGoalForm">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Limite de gastos (R$)</label>
                        <input name="limit" type="number" min="1" step="0.01" required placeholder="Ex.: 3500,00">
                    </div>
                    <div class="form-group">
                        <label>Início da meta</label>
                        <input name="start" type="date" required>
                    </div>
                    <div class="form-group">
                        <label>Duração (dias, máx. 30)</label>
                        <input name="days" type="number" min="1" max="30" step="1" required>
                    </div>
                    <p class="income-hint full-col" id="spendGoalHint"></p>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelSpendGoal">Cancelar</button>
                    <button class="btn primary" type="submit">Salvar meta</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal para editar o nome da conta. -->
    <div class="modal-back" id="profileModalBack">
        <div class="modal modal-edit modal-profile" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle">
            <div class="modal-head">
                <div class="modal-title">
                    <span class="modal-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
                    <div>
                        <h2 id="profileModalTitle">Editar nome da conta</h2>
                        <small>Como você aparece no sistema</small>
                    </div>
                </div>
                <button type="button" class="close" id="closeProfile" aria-label="Fechar">×</button>
            </div>
            <form id="profileForm">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Nome</label>
                        <input name="name" required maxlength="120" placeholder="Seu nome" autocomplete="name">
                    </div>
                    <div class="form-group full">
                        <label>E-mail</label>
                        <input name="email" type="email" disabled>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelProfile">Cancelar</button>
                    <button class="btn primary" type="submit" id="saveProfile">Salvar nome</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal de criação/edição de categoria. -->
    <div class="modal-back" id="categoryModalBack">
        <div class="modal" id="categoryModal" role="dialog" aria-modal="true" aria-labelledby="categoryModalTitle">
            <div class="modal-head">
                <div class="modal-title">
                    <span class="modal-icon" id="categoryModalIcon" hidden>◆</span>
                    <div>
                        <h2 id="categoryModalTitle">Nova categoria</h2>
                        <small id="categoryModalSubtitle">Organize seus gastos por categoria</small>
                    </div>
                </div>
                <button type="button" class="close" id="closeCategory" aria-label="Fechar">×</button>
            </div>
            <form id="categoryForm">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Nome da categoria</label>
                        <input name="name" required maxlength="80" placeholder="Ex.: Educação">
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelCategory">Cancelar</button>
                    <button class="btn primary" type="submit" id="categorySubmit">Criar categoria</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Popup de confirmação (usado para excluir metas, lançamentos e categorias). -->
    <div class="modal-back confirm-back" id="confirmModalBack">
        <div class="modal modal-confirm" role="alertdialog" aria-modal="true" aria-labelledby="confirmTitle" aria-describedby="confirmText">
            <div class="confirm-icon"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg></div>
            <h2 id="confirmTitle">Excluir item?</h2>
            <p id="confirmText"></p>
            <input type="text" id="confirmInput" class="field" placeholder="Digite desativar conta" autocomplete="off" style="display: none; width: 100%; margin-top: 12px;">
            <div class="modal-actions">
                <button type="button" class="btn" id="confirmCancel">Cancelar</button>
                <button type="button" class="btn btn-danger-solid" id="confirmOk">Excluir</button>
            </div>
        </div>
    </div>
    <!-- Popup de lucros: visualizar e lançar lucro fixo ou variável. -->
    <div class="modal-back" id="incomeModalBack">
        <div class="modal modal-income" role="dialog" aria-modal="true" aria-labelledby="incomeTitle">
            <div class="modal-head income-head">
                <div class="modal-title">
                    <span class="income-bag"><svg viewBox="0 0 24 24" width="34" height="34" aria-hidden="true"><path d="M9 2.5h6l-1.6 3.2h-2.8L9 2.5z" fill="currentColor"/><path d="M12 6.5c-4.2 0-7.2 3.7-7.2 8.2 0 3.4 2.3 6.3 7.2 6.3s7.2-2.9 7.2-6.3c0-4.5-3-8.2-7.2-8.2z" fill="currentColor"/><text x="12" y="18" text-anchor="middle" font-size="10" font-weight="700" style="fill:var(--panel)" font-family="Arial,sans-serif">$</text></svg></span>
                    <div>
                        <h2 id="incomeTitle">Lucro</h2>
                        <small>Gerencie seu lucro fixo ou variável.</small>
                    </div>
                </div>
                <button type="button" class="close" id="closeIncome" aria-label="Fechar">×</button>
            </div>
            <div class="income-summary">
                <div class="income-total">
                    <span>Lucro Total Atual</span>
                    <strong id="incomeTotal">R$ 0,00</strong>
                </div>
                <div class="income-part">
                    <span class="income-part-label"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 21V11"/><path d="M12 21V4"/><path d="M19 21v-7"/></svg>Fixo</span>
                    <strong id="incomeFixed">R$ 0,00</strong>
                    <small id="incomeFixedPct">(0%)</small>
                </div>
                <div class="income-part">
                    <span class="income-part-label"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/></svg>Variável</span>
                    <strong id="incomeVariable">R$ 0,00</strong>
                    <small id="incomeVariablePct">(0%)</small>
                </div>
            </div>
            <form id="incomeForm">
                <h3 class="income-section"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><circle cx="12" cy="12" r="11" fill="currentColor"/><path d="M12 7v10M7 12h10" style="stroke:var(--panel)" stroke-width="2.2" stroke-linecap="round"/></svg>Lançar Novo Valor</h3>
                <p class="income-help">Adicione um valor ao seu lucro atual ou substitua pelo novo valor.</p>
                <div class="opt-grid">
                    <label class="opt-card">
                        <input type="radio" name="modo" value="adicionar" checked>
                        <span class="opt-box">
                            <span class="opt-radio"></span>
                            <span class="opt-ico"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4"/><path d="M8 3v4"/><path d="M3 11h18"/><path d="M12 14v4"/><path d="M10 16h4"/></svg></span>
                            <span class="opt-text"><strong>Adicionar</strong><small>Soma ao valor atual</small></span>
                        </span>
                    </label>
                    <label class="opt-card">
                        <input type="radio" name="modo" value="substituir">
                        <span class="opt-box">
                            <span class="opt-radio"></span>
                            <span class="opt-ico"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 0 0-15.5-6.2L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 15.5 6.2L21 16"/><path d="M21 21v-5h-5"/></svg></span>
                            <span class="opt-text"><strong>Substituir</strong><small>Troca o valor atual</small></span>
                        </span>
                    </label>
                </div>
                <div class="opt-grid income-fields">
                    <div class="form-group">
                        <label class="income-label-ico"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/></svg>Valor *</label>
                        <div class="money-input">
                            <span>R$</span>
                            <input name="valor" inputmode="decimal" autocomplete="off" placeholder="Ex: 1.000,00" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="income-label-ico"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/></svg>Tipo *</label>
                        <div class="seg">
                            <label>
                                <input type="radio" name="tipo" value="FIXA" checked>
                                <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>Fixo</span>
                            </label>
                            <label>
                                <input type="radio" name="tipo" value="VARIAVEL">
                                <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/></svg>Variável</span>
                            </label>
                        </div>
                    </div>
                </div>
                <p class="income-hint" id="incomeHint"></p>
                <div class="modal-actions income-actions">
                    <button type="button" class="btn" id="cancelIncome">Cancelar</button>
                    <button class="btn primary" type="submit"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>Salvar</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Mensagens temporárias de sucesso/erro exibidas pelo JavaScript. -->
    <div class="toast" id="toast"></div>

<script>
    window.USUARIO = <?= json_encode([
    "nome"    => $_SESSION["usuario"]["nome"]  ?? "Usuário",
    "email"   => $_SESSION["usuario"]["email"] ?? "",
    "lembrar" => !empty($_SESSION["lembrar"])
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
    <script src="sessao.js"></script>
    <script src="dropdown.js"></script>
    <script src="script.js"></script>
    
</body>
</html>