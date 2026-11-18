-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 18/11/2026 às 02:27
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
-- Estrutura para tabela `aporte_meta`
--

CREATE TABLE `aporte_meta` (
  `ID_APORTE` bigint(20) UNSIGNED NOT NULL,
  `ID_USUARIO` bigint(20) UNSIGNED NOT NULL,
  `ID_META` bigint(20) UNSIGNED NOT NULL,
  `VALOR` decimal(13,2) NOT NULL,
  `ORIGEM` enum('FIXA','VARIAVEL') NOT NULL,
  `DATA_APORTE` date NOT NULL,
  `CRIADO_EM` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `categoria` (
  `ID_CATEGORIA` bigint(20) UNSIGNED NOT NULL,
  `ID_USUARIO` bigint(20) UNSIGNED DEFAULT NULL,
  `NOME` varchar(80) NOT NULL,
  `TIPO` enum('RECEITA','DESPESA','AMBOS') NOT NULL DEFAULT 'DESPESA',
  `ATIVO` tinyint(1) NOT NULL DEFAULT 1,
  `CRIADO_EM` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
  `OBSERVACAO` text DEFAULT NULL,
  `STATUS` enum('PENDENTE','PAGO','CANCELADO') NOT NULL DEFAULT 'PENDENTE',
  `ID_LANCAMENTO_ORIGINAL` bigint(20) UNSIGNED DEFAULT NULL,
  `CRIADO_EM` timestamp NOT NULL DEFAULT current_timestamp(),
  `ATUALIZADO_EM` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `meta`
--

CREATE TABLE `meta` (
  `ID_META` bigint(20) UNSIGNED NOT NULL,
  `ID_USUARIO` bigint(20) UNSIGNED NOT NULL,
  `TITULO` varchar(120) NOT NULL,
  `DESCRICAO` text DEFAULT NULL,
  `ICONE` varchar(16) DEFAULT NULL,
  `VALOR_OBJETIVO` decimal(13,2) NOT NULL,
  `VALOR_ACUMULADO` decimal(13,2) NOT NULL DEFAULT 0.00,
  `DATA_CRIACAO` date NOT NULL DEFAULT curdate(),
  `PRAZO` date DEFAULT NULL,
  `STATUS` enum('EM_ANDAMENTO','CONCLUIDA','PAUSADA','CANCELADA') NOT NULL DEFAULT 'EM_ANDAMENTO',
  `CRIADO_EM` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Estrutura para tabela `recuperacao_senha`
--

CREATE TABLE `recuperacao_senha` (
  `ID_RECUPERACAO` bigint(20) UNSIGNED NOT NULL,
  `ID_USUARIO` bigint(20) UNSIGNED NOT NULL,
  `TOKEN_HASH` varchar(255) NOT NULL,
  `EXPIRA_EM` datetime NOT NULL,
  `UTILIZADO` tinyint(1) NOT NULL DEFAULT 0,
  `CRIADO_EM` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
-- Índices de tabela `aporte_meta`
--
ALTER TABLE `aporte_meta`
  ADD PRIMARY KEY (`ID_APORTE`),
  ADD KEY `IDX_APORTE_USUARIO_DATA` (`ID_USUARIO`,`DATA_APORTE`),
  ADD KEY `IDX_APORTE_META` (`ID_META`);

--
-- Índices de tabela `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`ID_CATEGORIA`),
  ADD KEY `IDX_CATEGORIA_USUARIO_TIPO` (`ID_USUARIO`,`TIPO`);

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
-- Índices de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD PRIMARY KEY (`ID_RECUPERACAO`),
  ADD KEY `FK_RECUPERACAO_USUARIO` (`ID_USUARIO`);

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
-- AUTO_INCREMENT de tabela `aporte_meta`
--
ALTER TABLE `aporte_meta`
  MODIFY `ID_APORTE` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `categoria`
--
ALTER TABLE `categoria`
  MODIFY `ID_CATEGORIA` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `forma_pagamento`
--
ALTER TABLE `forma_pagamento`
  MODIFY `ID_FORMA_PAGAMENTO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `lancamento`
--
ALTER TABLE `lancamento`
  MODIFY `ID_LANCAMENTO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `meta`
--
ALTER TABLE `meta`
  MODIFY `ID_META` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  MODIFY `ID_RECUPERACAO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `ID_USUARIO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `aporte_meta`
--
ALTER TABLE `aporte_meta`
  ADD CONSTRAINT `APORTE_META_FK` FOREIGN KEY (`ID_META`) REFERENCES `meta` (`ID_META`) ON DELETE CASCADE,
  ADD CONSTRAINT `APORTE_USUARIO_FK` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`) ON DELETE CASCADE;

--
-- Restrições para tabelas `categoria`
--
ALTER TABLE `categoria`
  ADD CONSTRAINT `CATEGORIA_ID_USUARIO_FK` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`) ON DELETE CASCADE;

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
-- Restrições para tabelas `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD CONSTRAINT `FK_RECUPERACAO_USUARIO` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
