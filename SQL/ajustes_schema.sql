-- Ajustes recomendados para armazenar todos os dados que a interface atual coleta.
-- Execute depois de importar sistema_financeiro.sql no banco sistema_financeiro.

-- Campo Local (Casa/Filial) do formulário de lançamentos.
ALTER TABLE lancamento
    ADD COLUMN LOCAL_GASTO VARCHAR(80) NULL AFTER RESPONSAVEL;

-- Ícone selecionado pelo usuário em cada meta.
ALTER TABLE meta
    ADD COLUMN ICONE VARCHAR(16) NULL AFTER DESCRICAO;

-- Limite mensal de gastos usado nos cartões e na barra lateral.
ALTER TABLE configuracao
    ADD COLUMN LIMITE_GASTOS_MENSAL DECIMAL(13,2) NOT NULL DEFAULT 3500.00 AFTER NOTIFICACOES;

-- Recomendação: confira se todas as tabelas que usam chaves estrangeiras estão em InnoDB.
-- O mecanismo InnoDB é necessário para garantir integridade referencial via FOREIGN KEY.
