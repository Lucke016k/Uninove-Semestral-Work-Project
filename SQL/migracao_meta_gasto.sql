-- Meta de gasto do usuário (limite, data de início e duração em dias).
-- Antes ficava no navegador e era compartilhada entre contas; agora é uma linha por usuário.
-- Rode este script uma vez nos bancos que já existem. Instalações novas já recebem a tabela
-- pelo arquivo sistema_financeiro.sql.

CREATE TABLE IF NOT EXISTS `meta_gasto` (
  `ID_USUARIO` bigint(20) UNSIGNED NOT NULL,
  `VALOR_LIMITE` decimal(13,2) NOT NULL,
  `DATA_INICIO` date NOT NULL,
  `DIAS` tinyint(3) UNSIGNED NOT NULL,
  `ATUALIZADO_EM` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID_USUARIO`),
  CONSTRAINT `META_GASTO_USUARIO_FK` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
