# UniFinance

Sistema web de controle financeiro pessoal (trabalho semestral – Uninove): lançamentos de gastos, categorias,
lucros fixo e variável, metas com aportes, meta de gasto por período, relatórios com gráficos e três temas
(claro, escuro verde e escuro roxo).

## Tecnologias
PHP 7.4 ou superior (PDO) · MySQL/MariaDB · JavaScript puro · CSS (sem frameworks) · Composer (PHPMailer e Google API Client)

## Como rodar (XAMPP ou similar)
1. Copie o projeto para `htdocs/` (ou a raiz do seu servidor PHP).
2. Crie o banco importando `SQL/sistema_financeiro.sql` (ele já inclui todas as tabelas).
   - Banco já existente? Rode, se ainda não rodou, `SQL/migracao_aporte_meta.sql` e `SQL/migracao_meta_gasto.sql`.
3. Ajuste a conexão em `config/conexao.php` (host, banco, usuário e senha).
4. Confira os arquivos de configuração de e-mail (recuperação de senha) e de login com Google em `config/`.
5. Instale as dependências: `composer install` (cria a pasta `vendor/`, que não vai para o Git).
6. Acesse `http://localhost/<pasta-do-projeto>/PaginaLogin/login.php`, crie uma conta e entre.

## Estrutura
| Pasta / arquivo | Conteúdo |
|---|---|
| `index.php` | Tela principal (após o login); o conteúdo das páginas é montado por `script.js` |
| `script.js`, `dropdown.js`, `sessao.js` | Interface, menus customizados e controle de sessão no navegador |
| `style.css`, `theme.css`, `theme.js` | Estilos e temas (as cores ficam em `theme.css`) |
| `api/` | Endpoints JSON: `lancamentos`, `categorias`, `metas`, `receitas`, `perfil`, `usuarios`, `sessao` |
| `PaginaLogin/` | Login, cadastro, recuperação de senha, login com Google e logout |
| `config/` | Conexão com o banco, autenticação e e-mail |
| `includes/` | Verificação de conta ativa |
| `SQL/` | Banco completo e scripts de migração |
| `docs/` | MER (`MER.png`, `MER.svg`, `MER.md`) e relatório de auditoria |

## Modelo de dados
Veja `docs/MER.md` (diagrama gerado a partir de `SQL/sistema_financeiro.sql`).

## Observações
- Dados do usuário ficam no MySQL. No navegador ficam apenas preferências: tema e período do filtro.
- Parcelas são apenas informativas (guardadas como texto, "Parcelado em Nx", na observação do lançamento).
