-- ============================================================
-- Dados de TESTE para popular o banco e validar telas/relatórios.
--
-- Pré-requisitos (nesta ordem):
--   1) importar sistema_financeiro.sql
--   2) executar ajustes_schema.sql (cria a coluna LOCAL_GASTO)
--
-- Este script:
--   - cria o usuário teste@local.dev (senha Teste@123), se não existir;
--   - cria as categorias do usuário, se ainda não existirem;
--   - insere 32 lançamentos de ago/2026 a nov/2026.
--
-- ATENÇÃO: execute UMA vez. Rodar de novo duplica os lançamentos.
-- Para remover só os lançamentos de teste:
--   DELETE FROM lancamento WHERE OBSERVACAO = 'DADOS_DE_TESTE';
-- ============================================================

USE sistema_financeiro;

-- 1) Usuário de teste (o mesmo do ativar_usuario_teste.php).
INSERT INTO usuario (NOME, EMAIL, SENHA_HASH)
SELECT 'Usuário Teste', 'teste@local.dev', '$2y$10$Gy/58uJI8QDAUfNTHY8PeemUOwBOFYtNF2Coo8HNh8.AAHB6UZn3K'
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE EMAIL = 'teste@local.dev');

SET @u = (SELECT ID_USUARIO FROM usuario WHERE EMAIL = 'teste@local.dev' LIMIT 1);

-- 2) Categorias do usuário (só as que ainda não existem).
INSERT INTO categoria (ID_USUARIO, NOME, TIPO)
SELECT @u, n.nome, 'DESPESA'
FROM (
  SELECT 'Alimentação' AS nome
    UNION ALL
  SELECT 'Supermercado' AS nome
    UNION ALL
  SELECT 'Vestuário' AS nome
    UNION ALL
  SELECT 'Lazer' AS nome
    UNION ALL
  SELECT 'Transporte' AS nome
    UNION ALL
  SELECT 'Moradia' AS nome
    UNION ALL
  SELECT 'Saúde' AS nome
    UNION ALL
  SELECT 'Outros' AS nome
) n
WHERE NOT EXISTS (
    SELECT 1 FROM categoria c
    WHERE c.ID_USUARIO = @u
      AND c.NOME = n.nome COLLATE utf8mb4_unicode_ci
      AND c.ATIVO = 1
);

-- 3) Lançamentos (despesas). Categoria e forma de pagamento são buscadas pelo nome.
INSERT INTO lancamento
    (ID_USUARIO, ID_CATEGORIA, ID_FORMA_PAGAMENTO, DESCRICAO, VALOR,
     DATA_LANCAMENTO, TIPO, RESPONSAVEL, LOCAL_GASTO, OBSERVACAO, STATUS)
SELECT
    @u, c.ID_CATEGORIA, fp.ID_FORMA_PAGAMENTO, v.descricao, v.valor,
    v.data, 'DESPESA', v.quem, v.local, 'DADOS_DE_TESTE', 'PAGO'
FROM (
    SELECT 'Almoço no Restaurante' AS descricao, 42.50 AS valor, '2026-08-04' AS data, 'Alimentação' AS categoria, 'VR' AS pagamento, 'Eu' AS quem, 'Filial' AS local
    UNION ALL
    SELECT 'Compra no Mercado', 310.80, '2026-08-06', 'Supermercado', 'CARTÃO DE DÉBITO', 'Esposa', 'Casa'
    UNION ALL
    SELECT 'Aluguel', 1300.00, '2026-08-10', 'Moradia', 'PIX', 'Eu', 'Casa'
    UNION ALL
    SELECT 'Posto de Gasolina', 175.00, '2026-08-12', 'Transporte', 'CARTÃO DE DÉBITO', 'Eu', 'Filial'
    UNION ALL
    SELECT 'Cinema', 64.00, '2026-08-16', 'Lazer', 'CARTÃO DE CRÉDITO', 'Amigo', 'Filial'
    UNION ALL
    SELECT 'Consulta Médica', 180.00, '2026-08-21', 'Saúde', 'CARTÃO DE CRÉDITO', 'Eu', 'Casa'
    UNION ALL
    SELECT 'Lanche', 28.90, '2026-08-27', 'Alimentação', 'VA', 'Eu', 'Filial'
    UNION ALL
    SELECT 'Almoço executivo', 39.90, '2026-09-02', 'Alimentação', 'VR', 'Eu', 'Filial'
    UNION ALL
    SELECT 'Compra no Mercado', 287.45, '2026-09-05', 'Supermercado', 'VA', 'Esposa', 'Casa'
    UNION ALL
    SELECT 'Aluguel', 1300.00, '2026-09-10', 'Moradia', 'PIX', 'Eu', 'Casa'
    UNION ALL
    SELECT 'Conta de Luz', 148.30, '2026-09-12', 'Moradia', 'PIX', 'Eu', 'Casa'
    UNION ALL
    SELECT 'Tênis', 259.90, '2026-09-14', 'Vestuário', 'CARTÃO DE CRÉDITO', 'Eu', 'Casa'
    UNION ALL
    SELECT 'Uber', 34.70, '2026-09-18', 'Transporte', 'CARTÃO DE CRÉDITO', 'Esposa', 'Casa'
    UNION ALL
    SELECT 'Show', 180.00, '2026-09-20', 'Lazer', 'PIX', 'Amigo', 'Filial'
    UNION ALL
    SELECT 'Farmácia', 62.40, '2026-09-25', 'Saúde', 'CARTÃO DE DÉBITO', 'Eu', 'Casa'
    UNION ALL
    SELECT 'Presente de aniversário', 95.00, '2026-09-28', 'Outros', 'DINHEIRO', 'Eu', 'Casa'
    UNION ALL
    SELECT 'Almoço no Restaurante', 42.50, '2026-10-02', 'Alimentação', 'VR', 'Eu', 'Filial'
    UNION ALL
    SELECT 'Compra no Mercado', 126.90, '2026-10-03', 'Supermercado', 'CARTÃO DE DÉBITO', 'Esposa', 'Casa'
    UNION ALL
    SELECT 'Camisa', 78.00, '2026-10-05', 'Vestuário', 'CARTÃO DE CRÉDITO', 'Eu', 'Casa'
    UNION ALL
    SELECT 'Cinema', 64.00, '2026-10-06', 'Lazer', 'CARTÃO DE CRÉDITO', 'Amigo', 'Filial'
    UNION ALL
    SELECT 'Posto de Gasolina', 180.00, '2026-10-08', 'Transporte', 'CARTÃO DE DÉBITO', 'Eu', 'Filial'
    UNION ALL
    SELECT 'Aluguel', 1300.00, '2026-10-10', 'Moradia', 'PIX', 'Eu', 'Casa'
    UNION ALL
    SELECT 'Consulta Médica', 180.00, '2026-10-12', 'Saúde', 'CARTÃO DE CRÉDITO', 'Eu', 'Casa'
    UNION ALL
    SELECT 'Farmácia', 45.90, '2026-10-13', 'Saúde', 'CARTÃO DE DÉBITO', 'Eu', 'Casa'
    UNION ALL
    SELECT 'Compra no Mercado', 342.15, '2026-10-17', 'Supermercado', 'TICKET', 'Esposa', 'Casa'
    UNION ALL
    SELECT 'Pizza', 89.00, '2026-10-19', 'Alimentação', 'PIX', 'Eu', 'Casa'
    UNION ALL
    SELECT 'Estacionamento', 22.00, '2026-10-23', 'Transporte', 'DINHEIRO', 'Eu', 'Filial'
    UNION ALL
    SELECT 'Jantar fora', 134.50, '2026-10-26', 'Alimentação', 'CARTÃO DE CRÉDITO', 'Esposa', 'Casa'
    UNION ALL
    SELECT 'Material de escritório', 58.00, '2026-10-29', 'Outros', 'PIX', 'Eu', 'Filial'
    UNION ALL
    SELECT 'Compra no Mercado', 198.60, '2026-11-01', 'Supermercado', 'VA', 'Esposa', 'Casa'
    UNION ALL
    SELECT 'Almoço no Restaurante', 45.00, '2026-11-02', 'Alimentação', 'VR', 'Eu', 'Filial'
    UNION ALL
    SELECT 'Uber', 27.40, '2026-11-03', 'Transporte', 'CARTÃO DE CRÉDITO', 'Eu', 'Casa'
) v
JOIN categoria c
    ON c.ID_USUARIO = @u
   AND c.ATIVO = 1
   AND c.NOME = v.categoria COLLATE utf8mb4_unicode_ci
JOIN forma_pagamento fp
    ON fp.NOME = v.pagamento COLLATE utf8mb4_unicode_ci;

-- Conferência rápida:
-- SELECT COUNT(*) FROM lancamento WHERE OBSERVACAO = 'DADOS_DE_TESTE';  -- deve retornar 32