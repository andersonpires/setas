-- =====================================================
-- SETAS-WEB - Migrations
-- Banco: mwtech63_setas-web
-- Charset: utf8mb4
-- =====================================================
-- 2025-02-01 - Criação inicial das tabelas base
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------
-- Tabela: permissoes
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `permissoes` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permissoes_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------
-- Tabela: funcionalidades (páginas do sistema)
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `funcionalidades` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `rota` varchar(255) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_funcionalidades_rota` (`rota`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------
-- Tabela: permissao_funcionalidade
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `permissao_funcionalidade` (
  `permissao_id` int(11) UNSIGNED NOT NULL,
  `funcionalidade_id` int(11) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`permissao_id`, `funcionalidade_id`),
  KEY `fk_pf_funcionalidade` (`funcionalidade_id`),
  CONSTRAINT `fk_pf_permissao` FOREIGN KEY (`permissao_id`) REFERENCES `permissoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pf_funcionalidade` FOREIGN KEY (`funcionalidade_id`) REFERENCES `funcionalidades` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------
-- Tabela: colaboradores
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `colaboradores` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto` varchar(500) DEFAULT NULL,
  `dt_nascimento` date NOT NULL,
  `permissao_id` int(11) UNSIGNED NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_colaboradores_cpf` (`cpf`),
  UNIQUE KEY `uk_colaboradores_email` (`email`),
  KEY `fk_colaboradores_permissao` (`permissao_id`),
  CONSTRAINT `fk_colaboradores_permissao` FOREIGN KEY (`permissao_id`) REFERENCES `permissoes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------
-- Tabela: colaborador_sessao (manter logado)
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `colaborador_sessao` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `colaborador_id` int(11) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sessao_token` (`token`),
  KEY `fk_sessao_colaborador` (`colaborador_id`),
  CONSTRAINT `fk_sessao_colaborador` FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------
-- Tabela: recuperacao_senha
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `recuperacao_senha` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `colaborador_id` int(11) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_recup_colaborador` (`colaborador_id`),
  CONSTRAINT `fk_recup_colaborador` FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------
-- Tabela: tema_usuario (preferência light/dark)
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `tema_usuario` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `colaborador_id` int(11) UNSIGNED NOT NULL,
  `tema` enum('light','dark') DEFAULT 'light',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tema_colaborador` (`colaborador_id`),
  CONSTRAINT `fk_tema_colaborador` FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------
-- Tabela: logins (registro de acessos ao sistema)
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `logins` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `colaborador_id` int(11) UNSIGNED NOT NULL,
  `data_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_logins_colaborador` (`colaborador_id`),
  CONSTRAINT `fk_logins_colaborador` FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Dados iniciais
-- =====================================================

-- Permissão superadministrador (hardcode - não cadastrável pelo sistema)
INSERT IGNORE INTO `permissoes` (`id`, `nome`) VALUES (1, 'superadministrador');

-- Permissões padrão adicionais
INSERT IGNORE INTO `permissoes` (`id`, `nome`) VALUES 
(2, 'administrador'),
(3, 'operador'),
(4, 'visualizador');

-- Funcionalidades iniciais
INSERT IGNORE INTO `funcionalidades` (`id`, `nome`, `rota`) VALUES 
(1, 'Dashboard', 'home/index'),
(2, 'Login', 'auth/login'),
(3, 'Logout', 'auth/logout'),
(4, 'Colaboradores', 'colaboradores/index'),
(5, 'Colaboradores - Cadastrar', 'colaboradores/cadastrar'),
(6, 'Permissões', 'permissoes/index'),
(7, 'Funcionalidades', 'funcionalidades/index'),
(8, 'Relatórios', 'relatorios/index'),
(9, 'Beneficiário', 'beneficiario/index'),
(10, 'Perfil', 'perfil/index'),
(11, 'Família', 'familia/index');

-- Vincular todas as funcionalidades ao superadministrador
INSERT IGNORE INTO `permissao_funcionalidade` (`permissao_id`, `funcionalidade_id`)
SELECT 1, id FROM funcionalidades WHERE id NOT IN (SELECT funcionalidade_id FROM permissao_funcionalidade WHERE permissao_id = 1);

-- Primeiro usuário: Anderson Pires (inserido via run_migrations.php com hash PHP)
-- CPF: 30038912864, Senha: 300389, DtNasc: 1982-07-18, Email: andersonpires@msn.com

-- =====================================================
-- 2025-02-01 - Tabela codigo_familia (Bolsa Família)
-- Origem: !Suporte/base_dados/bolsa_familia.csv - coluna CodFamilia
-- =====================================================

CREATE TABLE IF NOT EXISTS `codigo_familia` (
  `id_codigo_familia` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo_familia` varchar(50) NOT NULL,
  PRIMARY KEY (`id_codigo_familia`),
  UNIQUE KEY `uk_codigo_familia` (`codigo_familia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2025-02-01 - Tabela beneficiario (Bolsa Família)
-- Origem: !Suporte/base_dados/bolsa_familia.csv
-- =====================================================

CREATE TABLE IF NOT EXISTS `beneficiario` (
  `id_beneficiario` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cpf` varchar(11) NOT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `dt_nascimento` date DEFAULT NULL,
  `sexo` varchar(20) DEFAULT NULL,
  `tipo_logradouro` varchar(50) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `localidade` varchar(150) DEFAULT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `renda_media` decimal(12,2) DEFAULT NULL,
  `renda_total` decimal(12,2) DEFAULT NULL,
  `ddd` varchar(5) DEFAULT NULL,
  `contato` varchar(20) DEFAULT NULL,
  `data_cadastro` date DEFAULT NULL,
  `data_atualizacao` date DEFAULT NULL,
  `pbf` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_beneficiario`),
  KEY `idx_beneficiario_cpf` (`cpf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2025-02-01 - Tabela beneficiario_cod_familia (Bolsa Família)
-- Origem: bolsa_familia.csv - CodFamilia, cpf
-- codigo_familia (valor do CSV), cpf (11 chars, zeros à direita), id_beneficiario (em branco por enquanto)
-- Relação: 1 codigo_familia : N registros
-- =====================================================

CREATE TABLE IF NOT EXISTS `beneficiario_cod_familia` (
  `id_membro_familia` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo_familia` varchar(50) NOT NULL,
  `cpf` varchar(11) NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `id_beneficiario` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id_membro_familia`),
  KEY `idx_codigo_familia` (`codigo_familia`),
  KEY `idx_cpf` (`cpf`),
  KEY `fk_bcf_beneficiario` (`id_beneficiario`),
  CONSTRAINT `fk_bcf_beneficiario` FOREIGN KEY (`id_beneficiario`) REFERENCES `beneficiario` (`id_beneficiario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2025-02-01 - Tabela vale_gas_federal
-- Origem: !Suporte/base_dados/vale_gas_federal.CSV
-- Colunas: cpf, nis, nome, situacao (SITBENEFICIO), id_beneficiario (em branco)
-- CPF: 11 chars (zeros à direita), NIS: NULL se vazio
-- =====================================================

CREATE TABLE IF NOT EXISTS `vale_gas_federal` (
  `id_vale_gas_federal` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cpf` varchar(11) NOT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `situacao` varchar(50) DEFAULT NULL,
  `id_beneficiario` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id_vale_gas_federal`),
  KEY `idx_cpf` (`cpf`),
  KEY `fk_vgf_beneficiario` (`id_beneficiario`),
  CONSTRAINT `fk_vgf_beneficiario` FOREIGN KEY (`id_beneficiario`) REFERENCES `beneficiario` (`id_beneficiario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2025-02-01 - Tabela vale_gas_ce
-- Origem: !Suporte/base_dados/vale_gas_ce.CSV
-- Colunas: cpf, nis, nome, situacao, id_beneficiario (em branco)
-- CPF: 11 chars (zeros à direita), NIS: NULL se vazio
-- =====================================================

CREATE TABLE IF NOT EXISTS `vale_gas_ce` (
  `id_vale_gas_ce` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cpf` varchar(11) NOT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `situacao` varchar(50) DEFAULT NULL,
  `id_beneficiario` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id_vale_gas_ce`),
  KEY `idx_cpf` (`cpf`),
  KEY `fk_vgce_beneficiario` (`id_beneficiario`),
  CONSTRAINT `fk_vgce_beneficiario` FOREIGN KEY (`id_beneficiario`) REFERENCES `beneficiario` (`id_beneficiario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2025-02-01 - Tabela prog_crianca_feliz (Programa Criança Feliz)
-- Origem: !Suporte/base_dados/prog_crianca_feliz.CSV
-- Colunas: cpf, nis, nome, cras, id_beneficiario (em branco)
-- CPF: remove . e -, apenas números (11 caracteres)
-- =====================================================

CREATE TABLE IF NOT EXISTS `prog_crianca_feliz` (
  `id_prog_crianca_feliz` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cpf` varchar(11) NOT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `cras` varchar(100) DEFAULT NULL,
  `id_beneficiario` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id_prog_crianca_feliz`),
  KEY `idx_cpf` (`cpf`),
  KEY `fk_pcf_beneficiario` (`id_beneficiario`),
  CONSTRAINT `fk_pcf_beneficiario` FOREIGN KEY (`id_beneficiario`) REFERENCES `beneficiario` (`id_beneficiario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2025-02-01 - Tabela cartao_mais_infancia (Cartão Mais Infância)
-- Origem: !Suporte/base_dados/cartao_mais_infancia.CSV
-- Colunas: cpf, nis, nome, cras, nib, situacao, id_beneficiario (em branco)
-- CPF: 11 chars (zeros à direita), NIS: NULL se vazio, situacao: com acentuação
-- =====================================================

CREATE TABLE IF NOT EXISTS `cartao_mais_infancia` (
  `id_cartao_mais_infancia` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cpf` varchar(11) NOT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `cras` varchar(100) DEFAULT NULL,
  `nib` varchar(50) DEFAULT NULL,
  `situacao` varchar(100) DEFAULT NULL,
  `id_beneficiario` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id_cartao_mais_infancia`),
  KEY `idx_cpf` (`cpf`),
  KEY `fk_cmi_beneficiario` (`id_beneficiario`),
  CONSTRAINT `fk_cmi_beneficiario` FOREIGN KEY (`id_beneficiario`) REFERENCES `beneficiario` (`id_beneficiario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2025-02-01 - Tabela cartao_ce_sem_fome (Cartão CE Sem Fome)
-- Origem: !Suporte/base_dados/cartao_ce_sem_fome.CSV
-- Colunas: cpf, nome, situacao, id_beneficiario (em branco)
-- CPF: remove . e -, apenas números (11 caracteres), situacao: com acentuação
-- =====================================================

CREATE TABLE IF NOT EXISTS `cartao_ce_sem_fome` (
  `id_cartao_ce_sem_fome` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cpf` varchar(11) NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `situacao` varchar(100) DEFAULT NULL,
  `id_beneficiario` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id_cartao_ce_sem_fome`),
  KEY `idx_cpf` (`cpf`),
  KEY `fk_ccsf_beneficiario` (`id_beneficiario`),
  CONSTRAINT `fk_ccsf_beneficiario` FOREIGN KEY (`id_beneficiario`) REFERENCES `beneficiario` (`id_beneficiario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2025-02-01 - Tabela aluguel_social (Aluguel Social)
-- Origem: !Suporte/base_dados/aluguel_social.CSV
-- Colunas: cpf, nis, nome, id_beneficiario (em branco)
-- CPF: remove . e -, apenas números (11 caracteres)
-- =====================================================

CREATE TABLE IF NOT EXISTS `aluguel_social` (
  `id_aluguel_social` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cpf` varchar(11) NOT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `id_beneficiario` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id_aluguel_social`),
  KEY `idx_cpf` (`cpf`),
  KEY `fk_as_beneficiario` (`id_beneficiario`),
  CONSTRAINT `fk_as_beneficiario` FOREIGN KEY (`id_beneficiario`) REFERENCES `beneficiario` (`id_beneficiario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- View: total de beneficiários para dashboard
-- CPFs distintos (exceto 00000000000) + quantidade de registros com CPF 00000000000
-- =====================================================
CREATE OR REPLACE VIEW `v_todos_cpfs` AS
SELECT cpf FROM beneficiario
UNION ALL SELECT cpf FROM beneficiario_cod_familia
UNION ALL SELECT cpf FROM vale_gas_federal
UNION ALL SELECT cpf FROM vale_gas_ce
UNION ALL SELECT cpf FROM prog_crianca_feliz
UNION ALL SELECT cpf FROM cartao_mais_infancia
UNION ALL SELECT cpf FROM cartao_ce_sem_fome
UNION ALL SELECT cpf FROM aluguel_social;
