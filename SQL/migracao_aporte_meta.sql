-- Aportes em metas: valores que saem do saldo (lucro fixo ou variável) e vão para uma meta.
-- Rode este script uma vez nos bancos que já existem. Instalações novas já recebem a tabela
-- pelo arquivo sistema_financeiro.sql.

CREATE TABLE IF NOT EXISTS `aporte_meta` (
  `ID_APORTE` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ID_USUARIO` bigint(20) UNSIGNED NOT NULL,
  `ID_META` bigint(20) UNSIGNED NOT NULL,
  `VALOR` decimal(13,2) NOT NULL,
  `ORIGEM` enum('FIXA','VARIAVEL') NOT NULL,
  `DATA_APORTE` date NOT NULL,
  `CRIADO_EM` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID_APORTE`),
  KEY `IDX_APORTE_USUARIO_DATA` (`ID_USUARIO`,`DATA_APORTE`),
  KEY `IDX_APORTE_META` (`ID_META`),
  CONSTRAINT `APORTE_USUARIO_FK` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`) ON DELETE CASCADE,
  CONSTRAINT `APORTE_META_FK` FOREIGN KEY (`ID_META`) REFERENCES `meta` (`ID_META`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
