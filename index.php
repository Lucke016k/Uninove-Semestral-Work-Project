<?php
/**
 * Página principal do Controle Financeiro.
 * A extensão .php permite acrescentar processamento no servidor.
 * Neste estágio, o arquivo ainda entrega a interface; a conexão PDO
 * ficará em config/conexao.php e será usada por endpoints PHP separados.
 */
?>
<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: PaginaLogin/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Controle Financeiro</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Estrutura principal do aplicativo: menu lateral e área de conteúdo. -->
    <div class="app">
        <!-- Menu lateral: navegação, filtros rápidos de pagamento e resumo mensal. -->
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-icon">▣</div>
                <div>
                    <strong>Controle Financeiro</strong>
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
            <div class="goal-mini">
                <span>◎ Meta do mês</span>
                <strong id="mini-goal">R$ 3.500,00</strong>
                <div class="progress">
                    <span id="mini-progress" style="width:60%"></span>
                </div>
                <small id="mini-note">R$ 1.120,00 restantes</small>
            </div>
        </aside>

        <!-- Conteúdo principal; o JavaScript injeta aqui a tela selecionada. -->
        <main class="main">
            <div class="topbar">
                <button class="pill" id="datePill">▦ 
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
                        <select name="category" id="expenseCategory"></select>
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
                    <div class="form-group">
                        <label>Quem gastou?</label>
                        <input name="who" value="Eu" placeholder="Nome">
                    </div>
                    <div class="form-group">
                        <label>Local</label>
                        <select name="place">
                            <option>Casa</option>
                            <option>Filial</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Parcelado?</label>
                        <select name="installments">
                            <option value="Não">Não</option>
                            <option value="Sim">Sim</option>
                        </select>
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
                            <option>✈</option>
                            <option>⌂</option>
                            <option>🚗</option>
                            <option>▤</option>
                            <option>◎</option>
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
    <!-- Tela visual de login. O login atual é apenas demonstrativo e será substituído por autenticação PHP. -->
    <div class="login-page" id="loginPage">
        <div class="login-box">
            <div class="login-logo">▣</div>
            <h2>Controle Financeiro</h2>
            <p>Seus gastos, seus objetivos.</p>
            <form id="loginForm">
                <div class="form-group">
                    <label>E-mail</label>
                    <input name="email" type="email" placeholder="Digite seu e-mail" required>
                </div>
                <div class="form-group">
                    <label>Senha</label>
                    <input name="password" type="password" placeholder="Digite sua senha" required>
                </div>
                <button class="btn primary">Entrar</button>
            </form>
            <div class="notice">Protótipo local: qualquer e-mail e senha permitem entrar.</div>
        </div>
    </div>
    <!-- Mensagens temporárias de sucesso/erro exibidas pelo JavaScript. -->
    <div class="toast" id="toast"></div>

<script>
    window.USUARIO = <?= json_encode([
        "nome"  => $_SESSION["usuario"]["nome"]  ?? "Usuário",
        "email" => $_SESSION["usuario"]["email"] ?? ""
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

    <script src="script.js"></script>
    
</body>
</html>
