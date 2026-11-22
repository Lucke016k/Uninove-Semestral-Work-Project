# Auditoria do sistema UniFinance

Revisão estática de todos os arquivos do projeto (sem `vendor/`). Não foi possível executar PHP nem o banco
neste ambiente, então os itens abaixo vêm da leitura do código, de buscas por referências e de testes do front-end
no navegador. **Nada foi apagado nem alterado**: este documento só lista o que encontrei.

Legenda: 🔴 corrigir já · 🟠 inconsistência / risco · 🟡 limpeza (código ou arquivo sem uso) · 🔵 observação

---


## Status da limpeza (aplicada depois desta auditoria)

**Feito**
* `gitignore.txt` renomeado para `.gitignore` (agora vale para `vendor/`, `.env` e logs). A linha dos arquivos de segredos ficou **comentada**
  de propósito, a pedido do time: eles continuam sendo versionados. Para ignorá-los, tire o `#` da linha em `.gitignore`.
* Apagados: `editar.php` (raiz), `teste_conexao.php`, `api/metas/progresso.php`, `config/configuracoes.php`, `img/unifinance_logo.png`,
  `PaginaLogin/inicio.php`, `PaginaLogin/teste_login.html`, `PaginaLogin/teste_validacao.php`, `PaginaLogin/conexao.php`.
* `api/receitas/resumo.php`, `api/receitas/salvar.php` e `api/usuarios/desativar.php` agora usam `exigirLoginApi()` (inatividade de 30 min e "Lembrar de mim").
* `api/receitas/resumo.php`, `api/receitas/salvar.php`, `api/metas/gasto_obter.php`, `api/metas/gasto_salvar.php` e `api/perfil/editar.php`
  agora confirmam se a conta está ativa (`includes/verificar_usuario_ativo.php`).
* `script.js`: removidos `defaultGoals`, `clearData()`, `monthlyIncome()`, `MONTHLY_INCOME`, `save()` e a gravação de `cf_user`; textos e comentários atualizados
  (cabeçalho e Configurações → Sessão, que agora mostra "Conectado como <e-mail>").
* `index.php`: removida a tela de login de protótipo e os ids `datePill`, `confirmIcon` e `incomeValue`.
* `style.css` / `login.css` / `theme.css`: removidas as classes e variáveis sem uso (`.login-*`, `.notice`, `.goal-card(s)`, `.three-col`, `.brand-icon`, `.auth-brand-icon`, `.tag-nowrap`, `.with-extra`, `--chart-dot`).
* `dropdown.js`: removidas as entradas de dropdowns que não existem mais.
* Novo `SQL/migracao_aporte_meta.sql` (faltava para bancos antigos) e `README.md` com instruções de instalação.

**Mantido de propósito / pendente de decisão**
* Arquivos `config/email_segredos.php` e `config/google_segredos.php`: mantidos como estão.
* Linhas que removem as chaves antigas `cf_spend_goal` e `cf_period` do navegador (início da inicialização do `script.js`): mantidas até todos abrirem o sistema uma vez.
* Modo **Substituir** da edição de meta (altera o valor guardado sem mexer no saldo): sem mudança, falta decidir a regra.
* Colunas sem uso (`lancamento.DATA_VENCIMENTO`, `lancamento.ID_LANCAMENTO_ORIGINAL`, `meta.DATA_CRIACAO`) e a forma de pagamento "Transferência bancária": mantidas, pois remover exige alterar o banco.
* Lista de formas de pagamento escrita em vários lugares e função `responder()` repetida nos PHP: não foram centralizadas, porque seria uma refatoração grande
  em muitos arquivos PHP que não consigo executar aqui para testar.

---

## 1. Segurança e repositório

| | Item | Onde | O que fazer |
|---|---|---|---|
| 🔴 | **Senha e e-mail reais em texto puro** num arquivo de teste | `PaginaLogin/teste_validacao.php` | Apagar o arquivo, **trocar a senha dessa conta** e considerar o dado já exposto se o repositório for público. |
| 🔴 | O `.gitignore` está com o nome **`gitignore.txt`**, então o Git não o usa | raiz | Renomear para `.gitignore`. Hoje `vendor/` (218 MB) e `config/*segredos.php` (e-mail e Google) seriam enviados ao GitHub. |
| 🔴 | Arquivos com segredos presentes no projeto | `config/email_segredos.php`, `config/google_segredos.php` | Garantir que estão no `.gitignore` (item acima) e criar versões `*.exemplo.php` sem valores. Se algum já foi publicado, gerar novas chaves/senhas. |
| 🟠 | Arquivo de teste de conexão acessível pelo navegador | `teste_conexao.php` | Apagar (o próprio comentário do arquivo diz para remover em produção). |
| 🟠 | Conexão duplicada com usuário `root` e senha vazia fixos | `PaginaLogin/conexao.php` | Ninguém inclui esse arquivo (todos usam `config/conexao.php`). Apagar. |
| 🟠 | Três endpoints não usam o controle de sessão padrão (`exigirLoginApi()`): usam só `session_start()`, então **não aplicam o limite de 30 min de inatividade nem o "Lembrar de mim"** | `api/receitas/resumo.php`, `api/receitas/salvar.php`, `api/usuarios/desativar.php` | Trocar `session_start()` por `exigirLoginApi()`, como nos demais. |
| 🟠 | Seis endpoints não confirmam se a conta ainda está **ativa** (`includes/verificar_usuario_ativo.php`): uma conta desativada continua acessando por eles | `api/metas/gasto_obter.php`, `api/metas/gasto_salvar.php`, `api/perfil/editar.php`, `api/receitas/resumo.php`, `api/receitas/salvar.php` (`ping.php` e `desativar.php` podem ficar sem) | Incluir o `require_once` da verificação, como nos outros 17 endpoints. |
| 🟠 | `api/metas/progresso.php` permite gravar `VALOR_ACUMULADO` direto, **sem passar pela regra de saldo/aporte** | `api/metas/progresso.php` | O front não chama mais esse endpoint. Apagar. |
| 🔵 | Regra de negócio: o modo **Substituir** na edição de meta altera o valor guardado sem tocar no saldo | `api/metas/editar.php` + `script.js` | É intencional (correção de valor), mas permite "guardar" dinheiro sem sair do saldo. Decidir se Substituir deve poder **aumentar** o valor. |

## 2. Arquivos que ninguém usa (candidatos a apagar)

| Arquivo | Motivo |
|---|---|
| `editar.php` (raiz) | Cópia solta do `api/metas/editar.php`, com caminho `../../config/conexao.php` **errado** (não funcionaria) e sem verificação de login. Nada chama. |
| `api/metas/progresso.php` | Sem chamadas no front (ver 1). |
| `PaginaLogin/inicio.php` | Página antiga de "Login realizado com sucesso"; o sistema redireciona para `index.php`. |
| `PaginaLogin/teste_login.html` | Formulário de teste. |
| `PaginaLogin/teste_validacao.php` | Teste com credenciais (ver 1). |
| `PaginaLogin/conexao.php` | Duplicata de `config/conexao.php`. |
| `teste_conexao.php` | Teste de conexão. |
| `config/configuracoes.php` | Página "Minha conta" antiga (HTML solto); a tela de Configurações hoje é montada pelo `script.js`. Nenhuma referência. |
| `img/unifinance_logo.png` (126 KB) | Nenhuma referência no código. (`unifinance_icone.png` é usado nas telas de login; `unifinance_icone_192.png` e `favicon.*` no sistema.) |

## 3. Código morto no front-end (`script.js`, `index.php`, `dropdown.js`, CSS)

| | Item | Detalhe |
|---|---|---|
| 🟡 | `defaultGoals` | Metas de exemplo de quando as metas eram locais. Não é mais lida. |
| 🟡 | `clearData()` | Nunca é chamada. Apagava `cf_goals`/`cf_user` do navegador. |
| 🟡 | `monthlyIncome()` e `MONTHLY_INCOME` | Sem uso (renda de demonstração de R$ 4.500). O saldo vem dos lucros cadastrados. |
| 🟡 | `save()` e a chave `cf_user` | `save()` grava `cf_user` no navegador depois de editar o nome, mas **nada lê essa chave** (o usuário vem da sessão PHP). Pode remover `save()` e sua chamada. |
| 🟡 | Bloco `#loginPage` / `#loginForm` em `index.php` e o CSS `.login-page`, `.login-box`, `.login-logo`, `.notice` | Tela de login de protótipo ("qualquer e-mail e senha permitem entrar"), nunca exibida; o login real está em `PaginaLogin/`. |
| 🟡 | Entradas do mapa de ícones em `dropdown.js`: `Mensal`, `Semanal`, `Diário`, `Anual`, `Todos os períodos` | Pertenciam a dropdowns que foram removidos (período dos relatórios e filtro de mês). |
| 🟡 | CSS sem uso: `.goal-card`, `.goal-cards`, `.three-col`, `.brand-icon`, `.auth-brand-icon`, `.tag-nowrap`, `.income-row.with-extra` | `.tag-nowrap` e `.with-extra` são de recursos que o CSS recebeu mas o JS atual não usa (cards "12x de R$…" e card de saldo em Metas). |
| 🟡 | Variável `--chart-dot` em `theme.css` | Não é mais usada (as barras usam `--accent2`). |
| 🟡 | IDs sem uso: `datePill`, `incomeValue`, `confirmIcon` | Inofensivos. |
| 🟡 | Textos desatualizados: cabeçalho do `script.js` ("Metas e perfil ainda ficam no localStorage"), comentário "valores de demonstração", e na tela Configurações → Sessão: "Você está usando o protótipo local do Controle Financeiro." | Hoje metas, categorias, lançamentos, lucros, aportes e meta de gasto vêm do MySQL. |

## 4. O que era local e hoje está no banco (e o que ainda é local)

| Dado | Antes | Agora |
|---|---|---|
| Lançamentos, categorias | MySQL | MySQL |
| Metas | `localStorage` (`cf_goals`) | MySQL (`meta`) |
| Perfil / nome | `localStorage` (`cf_user`) | Sessão PHP + MySQL (`api/perfil/editar.php`) |
| Lucros fixo/variável | MySQL (`lancamento` tipo RECEITA) | igual |
| Aportes em metas | — | MySQL (`aporte_meta`) |
| Meta de gasto | `localStorage` (`cf_spend_goal`, compartilhada entre contas) | MySQL (`meta_gasto`, por usuário) |
| Período do filtro | `localStorage` global | `localStorage` por e-mail (`cf_period:<email>`) |
| Tema (claro/escuro/roxo) | — | `localStorage` do aparelho (`cf_theme`), de propósito (a tela de login precisa dele) |

Sobras: no início do `script.js` há linhas que removem as chaves antigas `cf_spend_goal` e `cf_period` (migração). Podem ser removidas
depois que todo mundo tiver aberto o sistema uma vez.

## 5. Divergências de informação entre camadas

* **Formas de pagamento** estão escritas em **7 lugares**: `PAYMENT_METHODS` (JS), opções do `<select>` em `index.php`, mapa em `criar.php`,
  mapa em `editar.php`, `CASE` em `listar.php`, ícones em `dropdown.js` e itens do menu lateral. O banco tem uma 8ª forma,
  **"TRANSFERÊNCIA BANCÁRIA"**, que a interface não oferece. Se algum nome mudar, os 7 pontos precisam mudar juntos.
* **Regra "sem parcelamento em Débito, VR, VA e Ticket"** existe duas vezes: `NO_INSTALLMENT_PAYMENTS` (JS) e a lista em `criar.php`/`editar.php`.
* **Parcelas** não têm coluna própria: ficam como texto "Parcelado em Nx" em `lancamento.OBSERVACAO`. O MER mostra só `OBSERVACAO`.
  Qualquer uso futuro de `OBSERVACAO` para outra coisa vai conflitar com o formato.
* **Categorias globais** (`ID_USUARIO` nulo): o código lista e trata, mas o `.sql` **não cria nenhuma**. Os nomes padrão em `dropdown.js`
  (Alimentação, Lazer…) só aparecem se o usuário criar a categoria com esse nome.
* **Nome do produto**: "UniFinance" nas telas e no `<title>`, "Controle Financeiro" no cabeçalho do `script.js` e em textos antigos.
* **Moeda de teste**: `MONTHLY_LIMIT = 3500` é usado como padrão da meta de gasto de quem ainda não definiu a sua.
* **Scripts SQL**: `SQL/migracao_meta_gasto.sql` existe, mas não há `migracao_aporte_meta.sql` (a tabela `aporte_meta` só vem no dump). Bancos antigos
  precisam de um script para ela também.

## 6. Colunas e recursos do banco sem uso

| Tabela | Coluna | Situação |
|---|---|---|
| `lancamento` | `DATA_VENCIMENTO` | Nenhum PHP lê ou grava. |
| `lancamento` | `ID_LANCAMENTO_ORIGINAL` | Nenhum PHP usa; é o auto-relacionamento que aparece no MER. Era para parcelas/recorrência. |
| `meta` | `DATA_CRIACAO` | Nenhum PHP usa (existe também `CRIADO_EM`). |
| várias | `CRIADO_EM` / `ATUALIZADO_EM` | Só informativos. |
| `forma_pagamento` | registro `TRANSFERENCIA` | Sem opção na interface (ver 5). |
| `categoria.TIPO` | valor `RECEITA` | A interface só cria `DESPESA`. |

Decisão sugerida: remover `DATA_VENCIMENTO` e `DATA_CRIACAO` do modelo, ou passar a usá-las. Para `ID_LANCAMENTO_ORIGINAL`, ou implementar parcelas de verdade, ou retirar do MER.

## 7. Duplicação de código

* A função `responder()` está copiada em **23 arquivos** PHP (alguns com `ob_start`, outros sem). Vale mover para um include comum (`config/resposta.php`).
* Verificação de login, sessão e usuário ativo é repetida em cada endpoint, com variações (ver 1).
* `README.md` tem só o título; faltam instruções de instalação (XAMPP, importar `SQL/sistema_financeiro.sql`, criar `config/*segredos.php`).

## 8. Ordem sugerida de correção

1. Renomear `gitignore.txt` → `.gitignore`; apagar `teste_validacao.php`; trocar a senha daquela conta; conferir se algum segredo já foi publicado.
2. Padronizar `exigirLoginApi()` + verificação de conta ativa nos endpoints citados em 1.
3. Apagar os arquivos de 2 (começando por `progresso.php`, `editar.php` da raiz e os de teste).
4. Remover o código morto de 3 (inclui o bloco de login do `index.php`) e atualizar os textos.
5. Decidir o destino das colunas de 6 e escrever o `migracao_aporte_meta.sql`.
6. Centralizar a lista de formas de pagamento e a função `responder()`.
