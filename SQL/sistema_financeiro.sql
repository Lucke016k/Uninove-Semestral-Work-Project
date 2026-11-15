-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 28/10/2026 às 01:48
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sistema_financeiro`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categoria`
--

CREATE TABLE `categoria` (
  `ID_CATEGORIA` bigint(20) UNSIGNED NOT NULL,
  `ID_USUARIO` bigint(20) UNSIGNED DEFAULT NULL,
  `NOME` varchar(80) NOT NULL,
  `TIPO` enum('RECEITA','DESPESA','AMBOS') NOT NULL DEFAULT 'DESPESA',
  `COR` char(7) DEFAULT NULL,
  `ATIVO` tinyint(1) NOT NULL DEFAULT 1,
  `CRIADO_EM` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracao`
--

CREATE TABLE `configuracao` (
  `ID_CONFIGURACAO` bigint(20) UNSIGNED NOT NULL,
  `ID_USUARIO` bigint(20) UNSIGNED NOT NULL,
  `TEMA` enum('CLARO','ESCURO','SISTEMA') NOT NULL DEFAULT 'CLARO',
  `MOEDA` char(3) NOT NULL DEFAULT 'BRL',
  `FORMATO_DATA` varchar(20) NOT NULL DEFAULT 'DD/MM/YYYY',
  `NOTIFICACOES` tinyint(1) NOT NULL DEFAULT 1,
  `ATUALIZADO_EM` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `forma_pagamento`
--

CREATE TABLE `forma_pagamento` (
  `ID_FORMA_PAGAMENTO` bigint(20) UNSIGNED NOT NULL,
  `NOME` varchar(60) NOT NULL,
  `TIPO` enum('CREDITO','DEBITO','BENEFICIO','DINHEIRO','PIX','TRANSFERENCIA','OUTRO') NOT NULL,
  `ATIVO` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `forma_pagamento`
--

INSERT INTO `forma_pagamento` (`ID_FORMA_PAGAMENTO`, `NOME`, `TIPO`, `ATIVO`) VALUES
(1, 'CARTÃO DE CRÉDITO', 'CREDITO', 1),
(2, 'CARTÃO DE DÉBITO', 'DEBITO', 1),
(3, 'VR', 'BENEFICIO', 1),
(4, 'VA', 'BENEFICIO', 1),
(5, 'TICKET', 'BENEFICIO', 1),
(6, 'DINHEIRO', 'DINHEIRO', 1),
(7, 'PIX', 'PIX', 1),
(8, 'TRANSFERÊNCIA BANCÁRIA', 'TRANSFERENCIA', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `lancamento`
--

CREATE TABLE `lancamento` (
  `ID_LANCAMENTO` bigint(20) UNSIGNED NOT NULL,
  `ID_USUARIO` bigint(20) UNSIGNED NOT NULL,
  `ID_CATEGORIA` bigint(20) UNSIGNED DEFAULT NULL,
  `ID_FORMA_PAGAMENTO` bigint(20) UNSIGNED DEFAULT NULL,
  `DESCRICAO` varchar(180) NOT NULL,
  `VALOR` decimal(13,2) NOT NULL,
  `DATA_LANCAMENTO` date NOT NULL,
  `DATA_VENCIMENTO` date DEFAULT NULL,
  `TIPO` enum('RECEITA','DESPESA') NOT NULL,
  `NATUREZA_RECEITA` enum('FIXA','VARIAVEL') DEFAULT NULL,
  `RESPONSAVEL` varchar(120) DEFAULT NULL,
  `OBSERVACAO` text DEFAULT NULL,
  `STATUS` enum('PENDENTE','PAGO','CANCELADO') NOT NULL DEFAULT 'PENDENTE',
  `ID_LANCAMENTO_ORIGINAL` bigint(20) UNSIGNED DEFAULT NULL,
  `CRIADO_EM` timestamp NOT NULL DEFAULT current_timestamp(),
  `ATUALIZADO_EM` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `meta`
--

CREATE TABLE `meta` (
  `ID_META` bigint(20) UNSIGNED NOT NULL,
  `ID_USUARIO` bigint(20) UNSIGNED NOT NULL,
  `TITULO` varchar(120) NOT NULL,
  `DESCRICAO` text DEFAULT NULL,
  `VALOR_OBJETIVO` decimal(13,2) NOT NULL,
  `VALOR_ACUMULADO` decimal(13,2) NOT NULL DEFAULT 0.00,
  `DATA_CRIACAO` date NOT NULL DEFAULT curdate(),
  `PRAZO` date DEFAULT NULL,
  `STATUS` enum('EM_ANDAMENTO','CONCLUIDA','PAUSADA','CANCELADA') NOT NULL DEFAULT 'EM_ANDAMENTO',
  `CRIADO_EM` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `parcela`
--

CREATE TABLE `parcela` (
  `ID_PARCELA` bigint(20) UNSIGNED NOT NULL,
  `ID_PARCELAMENTO` bigint(20) UNSIGNED NOT NULL,
  `NUMERO_PARCELA` smallint(5) UNSIGNED NOT NULL,
  `VALOR` decimal(13,2) NOT NULL,
  `DATA_VENCIMENTO` date NOT NULL,
  `STATUS` enum('PENDENTE','PAGO','CANCELADO') NOT NULL DEFAULT 'PENDENTE',
  `DATA_PAGAMENTO` date DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `parcelamento`
--

CREATE TABLE `parcelamento` (
  `ID_PARCELAMENTO` bigint(20) UNSIGNED NOT NULL,
  `ID_LANCAMENTO` bigint(20) UNSIGNED NOT NULL,
  `QUANTIDADE_PARCELAS` smallint(5) UNSIGNED NOT NULL,
  `VALOR_TOTAL` decimal(13,2) NOT NULL,
  `DATA_PRIMEIRA_PARCELA` date NOT NULL,
  `CRIADO_EM` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `ID_USUARIO` bigint(20) UNSIGNED NOT NULL,
  `NOME` varchar(120) NOT NULL,
  `EMAIL` varchar(254) NOT NULL,
  `SENHA_HASH` varchar(255) NOT NULL,
  `ATIVO` tinyint(1) NOT NULL DEFAULT 1,
  `CRIADO_EM` timestamp NOT NULL DEFAULT current_timestamp(),
  `ATUALIZADO_EM` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`ID_CATEGORIA`),
  ADD KEY `IDX_CATEGORIA_USUARIO_TIPO` (`ID_USUARIO`,`TIPO`);

--
-- Índices de tabela `configuracao`
--
ALTER TABLE `configuracao`
  ADD PRIMARY KEY (`ID_CONFIGURACAO`),
  ADD UNIQUE KEY `UQ_CONFIGURACAO_USUARIO` (`ID_USUARIO`);

--
-- Índices de tabela `forma_pagamento`
--
ALTER TABLE `forma_pagamento`
  ADD PRIMARY KEY (`ID_FORMA_PAGAMENTO`),
  ADD UNIQUE KEY `UQ_FORMA_PAGAMENTO_NOME` (`NOME`);

--
-- Índices de tabela `lancamento`
--
ALTER TABLE `lancamento`
  ADD PRIMARY KEY (`ID_LANCAMENTO`),
  ADD KEY `LANCAMENTO_ORIGINAL_FK` (`ID_LANCAMENTO_ORIGINAL`),
  ADD KEY `IDX_LANCAMENTO_USUARIO_DATA` (`ID_USUARIO`,`DATA_LANCAMENTO`),
  ADD KEY `IDX_LANCAMENTO_USUARIO_TIPO_STATUS` (`ID_USUARIO`,`TIPO`,`STATUS`),
  ADD KEY `IDX_LANCAMENTO_CATEGORIA` (`ID_CATEGORIA`),
  ADD KEY `IDX_LANCAMENTO_FORMA_PAGAMENTO` (`ID_FORMA_PAGAMENTO`);

--
-- Índices de tabela `meta`
--
ALTER TABLE `meta`
  ADD PRIMARY KEY (`ID_META`),
  ADD KEY `IDX_META_USUARIO_STATUS` (`ID_USUARIO`,`STATUS`);

--
-- Índices de tabela `parcela`
--
ALTER TABLE `parcela`
  ADD PRIMARY KEY (`ID_PARCELA`),
  ADD UNIQUE KEY `UQ_PARCELA_NUMERO` (`ID_PARCELAMENTO`,`NUMERO_PARCELA`),
  ADD KEY `IDC_PARCELA_VENCIMENTO_STATUS` (`DATA_VENCIMENTO`,`STATUS`);

--
-- Índices de tabela `parcelamento`
--
ALTER TABLE `parcelamento`
  ADD PRIMARY KEY (`ID_PARCELAMENTO`),
  ADD UNIQUE KEY `UQ_PARCELMANETO_LANCAMENTO` (`ID_LANCAMENTO`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`ID_USUARIO`),
  ADD UNIQUE KEY `UQ_USUARIO_EMAIL` (`EMAIL`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categoria`
--
ALTER TABLE `categoria`
  MODIFY `ID_CATEGORIA` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configuracao`
--
ALTER TABLE `configuracao`
  MODIFY `ID_CONFIGURACAO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `forma_pagamento`
--
ALTER TABLE `forma_pagamento`
  MODIFY `ID_FORMA_PAGAMENTO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `lancamento`
--
ALTER TABLE `lancamento`
  MODIFY `ID_LANCAMENTO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `meta`
--
ALTER TABLE `meta`
  MODIFY `ID_META` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `parcela`
--
ALTER TABLE `parcela`
  MODIFY `ID_PARCELA` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `parcelamento`
--
ALTER TABLE `parcelamento`
  MODIFY `ID_PARCELAMENTO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `ID_USUARIO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `categoria`
--
ALTER TABLE `categoria`
  ADD CONSTRAINT `CATEGORIA_ID_USUARIO_FK` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`) ON DELETE CASCADE;

--
-- Restrições para tabelas `configuracao`
--
ALTER TABLE `configuracao`
  ADD CONSTRAINT `CONFIGURACAO_USUARIO_FK` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`) ON DELETE CASCADE;

--
-- Restrições para tabelas `lancamento`
--
ALTER TABLE `lancamento`
  ADD CONSTRAINT `LANCAMENTO_CATEGORIA_FK` FOREIGN KEY (`ID_CATEGORIA`) REFERENCES `categoria` (`ID_CATEGORIA`) ON DELETE SET NULL,
  ADD CONSTRAINT `LANCAMENTO_FORMA_PAGAMENTO_FK` FOREIGN KEY (`ID_FORMA_PAGAMENTO`) REFERENCES `forma_pagamento` (`ID_FORMA_PAGAMENTO`) ON DELETE SET NULL,
  ADD CONSTRAINT `LANCAMENTO_ORIGINAL_FK` FOREIGN KEY (`ID_LANCAMENTO_ORIGINAL`) REFERENCES `lancamento` (`ID_LANCAMENTO`) ON DELETE SET NULL,
  ADD CONSTRAINT `LANCAMENTO_USUARIO_FK` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`) ON DELETE CASCADE;

--
-- Restrições para tabelas `meta`
--
ALTER TABLE `meta`
  ADD CONSTRAINT `META_USUARIO_FK` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`) ON DELETE CASCADE;

--
-- Restrições para tabelas `parcela`
--
ALTER TABLE `parcela`
  ADD CONSTRAINT `PARCELA_PARCELAMENTO_FK` FOREIGN KEY (`ID_PARCELAMENTO`) REFERENCES `parcelamento` (`ID_PARCELAMENTO`) ON DELETE CASCADE;

--
-- Restrições para tabelas `parcelamento`
--
ALTER TABLE `parcelamento`
  ADD CONSTRAINT `PARCELAMENTO_LANCAMENTO_FK` FOREIGN KEY (`ID_LANCAMENTO`) REFERENCES `lancamento` (`ID_LANCAMENTO`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
