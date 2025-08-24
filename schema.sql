SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `artygs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(250) NOT NULL,
  `texto` mediumblob NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `publico` tinyint(4) NOT NULL DEFAULT 0,
  `id_pap` bigint(20) UNSIGNED NOT NULL,
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `id_idioma` bigint(20) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `artyg_dest` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_artyg` bigint(20) UNSIGNED NOT NULL,
  `tipo_dest` varchar(15) DEFAULT NULL,
  `id_dest` bigint(20) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `asons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `data_acao` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo_destino` varchar(30) NOT NULL,
  `id_destino` bigint(20) UNSIGNED NOT NULL,
  `tipo` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `autosubstituicoes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_glifo` bigint(20) UNSIGNED NOT NULL,
  `tecla` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ipa` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `id_escrita` bigint(20) UNSIGNED NOT NULL,
  `glifos` varchar(150) NOT NULL,
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `blocos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `tipo_nucleo` varchar(15) NOT NULL,
  `id_nucleo` bigint(20) UNSIGNED NOT NULL,
  `tipo_dependente` varchar(15) NOT NULL,
  `id_dependente` bigint(20) UNSIGNED NOT NULL,
  `id_separador` int(11) NOT NULL,
  `descricao` varchar(250) NOT NULL,
  `lado` tinyint(4) NOT NULL DEFAULT 1,
  `nome` varchar(150) NOT NULL,
  `id_gloss` bigint(20) UNSIGNED NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `id_gloss` bigint(20) UNSIGNED NOT NULL,
  `proto_tipo` tinyint(4) NOT NULL DEFAULT 0,
  `superior` bigint(20) UNSIGNED NOT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `paradigma` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `classesGeneros` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_classe` bigint(20) UNSIGNED NOT NULL,
  `id_palavra` bigint(20) UNSIGNED NOT NULL,
  `id_genero` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `classesSom` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `simbolo` varchar(3) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `id_tipoClasse` bigint(20) UNSIGNED NOT NULL,
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `collabs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `collabs_realidades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_realidade` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `concordancias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_gloss` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(250) NOT NULL,
  `descricao` text NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `id_classe` bigint(20) UNSIGNED NOT NULL,
  `depende` bigint(20) UNSIGNED NOT NULL,
  `obrigatorio` tinyint(4) NOT NULL DEFAULT 0,
  `genero` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `ordem` int(11) NOT NULL DEFAULT 1,
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `drawChars` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_escrita` bigint(20) UNSIGNED NOT NULL,
  `glifo` varchar(15) NOT NULL DEFAULT '',
  `descricao` text NOT NULL,
  `ordem` int(11) NOT NULL,
  `input` varchar(8) NOT NULL DEFAULT '',
  `id_principal` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `vetor` mediumtext NOT NULL,
  `data_modificado` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `entidades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome_legivel` varchar(150) NOT NULL DEFAULT '',
  `descricao` mediumtext DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `id_criador` bigint(20) UNSIGNED NOT NULL,
  `publico` tinyint(4) NOT NULL DEFAULT 0,
  `id_pai` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `id_realidade` bigint(20) UNSIGNED NOT NULL,
  `id_tipo` bigint(20) UNSIGNED NOT NULL,
  `descricao_curta` varchar(250) NOT NULL,
  `privado` text DEFAULT NULL,
  `rule` enum('character','place','item','other') NOT NULL DEFAULT 'other'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `entidades_nomes` (
  `id` bigint(20) NOT NULL,
  `id_entidade` bigint(20) NOT NULL,
  `id_idioma` bigint(20) NOT NULL,
  `nome` varchar(250) NOT NULL,
  `info` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `entidades_relacoes` (
  `id` bigint(20) NOT NULL,
  `id_entidade1` bigint(20) NOT NULL,
  `id_entidade2` bigint(20) NOT NULL,
  `id_momento_inicio` bigint(20) DEFAULT NULL,
  `id_momento_fim` bigint(20) DEFAULT NULL,
  `tipo_relacao` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `entidades_tipos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_realidade` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `id_superior` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `rule` enum('character','place','item','other') NOT NULL DEFAULT 'other'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `entidades_tipos_stats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_entidade_tipo` bigint(20) UNSIGNED NOT NULL,
  `id_stat` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `escritas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(50) NOT NULL,
  `id_tipo` int(11) NOT NULL,
  `publico` tinyint(4) NOT NULL,
  `descricao` text NOT NULL,
  `id_fonte` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `tamanho` varchar(15) NOT NULL DEFAULT 'unset',
  `id_nativo` bigint(20) UNSIGNED NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `padrao` tinyint(4) NOT NULL,
  `substituicao` tinyint(4) NOT NULL DEFAULT 0,
  `checar_glifos` tinyint(4) NOT NULL DEFAULT 0,
  `separadores` varchar(250) NOT NULL DEFAULT ' ',
  `iniciadores` varchar(250) NOT NULL,
  `sep_sentencas` varchar(250) DEFAULT ' ',
  `inic_sentencas` varchar(250) DEFAULT '',
  `binario` tinyint(4) NOT NULL DEFAULT 0,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `flexoes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(150) NOT NULL,
  `ordem` int(11) NOT NULL,
  `regra_pronuncia` text NOT NULL,
  `regra_romanizacao` text NOT NULL,
  `motor` varchar(15) NOT NULL DEFAULT 'sca2',
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `fontes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(150) NOT NULL,
  `arquivo` varchar(150) NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `publica` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `formaSilabaComponente` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_formaSilaba` bigint(20) UNSIGNED NOT NULL,
  `id_classeSom` bigint(20) UNSIGNED NOT NULL,
  `ordem` int(11) NOT NULL,
  `obrigatorio` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `formasSilaba` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(50) DEFAULT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `tipo` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=geral/medial, 1=inicial, 2=final, 3=monosilabas',
  `peso` int(11) NOT NULL DEFAULT 1,
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `generos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_gloss` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(250) NOT NULL,
  `descricao` text NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `id_classe` bigint(20) UNSIGNED NOT NULL,
  `depende` bigint(20) UNSIGNED NOT NULL,
  `obrigatorio` tinyint(4) NOT NULL DEFAULT 0,
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `glifos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_escrita` bigint(20) UNSIGNED NOT NULL,
  `glifo` varchar(15) NOT NULL,
  `descricao` text NOT NULL,
  `ordem` int(11) NOT NULL,
  `input` varchar(8) NOT NULL COMMENT 'Simbolo inserido no teclado para converter para este glifo',
  `id_principal` bigint(20) UNSIGNED NOT NULL,
  `data_modificado` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `glosses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `gloss` varchar(15) NOT NULL,
  `descricao` varchar(250) NOT NULL,
  `tipo` char(1) NOT NULL DEFAULT 'i',
  `descricaoPt` varchar(250) NOT NULL,
  `descricaoEo` varchar(250) NOT NULL,
  `descricaoJp` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `gloss_itens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_item` bigint(20) UNSIGNED NOT NULL,
  `id_gloss` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `gloss_referentes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_gloss` bigint(20) UNSIGNED NOT NULL,
  `id_referente` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `grupos_idiomas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(150) NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `descricao` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `historias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_realidade` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(350) NOT NULL,
  `descricao` text NOT NULL,
  `status` varchar(15) NOT NULL,
  `id_superior` bigint(20) UNSIGNED NOT NULL,
  `id_tipo` bigint(20) UNSIGNED NOT NULL,
  `texto` mediumtext NOT NULL DEFAULT '',
  `id_momento` bigint(20) UNSIGNED NOT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `historias_entidades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_realidade` bigint(20) UNSIGNED NOT NULL,
  `id_entidade` bigint(20) UNSIGNED NOT NULL,
  `id_historia` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `historias_tipos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_superior` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descricao` text NOT NULL,
  `id_realidade` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `idiomas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `id_tipo` int(11) NOT NULL,
  `publico` tinyint(4) NOT NULL,
  `copyright` text DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `id_nome_nativo` bigint(20) UNSIGNED NOT NULL,
  `id_ascendente` bigint(20) UNSIGNED NOT NULL,
  `descricao` mediumtext DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `nome_legivel` varchar(250) NOT NULL,
  `ordem` int(11) DEFAULT NULL,
  `marcacao` int(11) DEFAULT NULL,
  `direcao` int(11) DEFAULT NULL,
  `sintese` int(11) DEFAULT NULL,
  `alinhamento` int(11) DEFAULT NULL,
  `buscavel` tinyint(4) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `romanizacao` tinyint(4) NOT NULL DEFAULT 0,
  `sigla` varchar(10) NOT NULL,
  `id_idioma_descricao` bigint(20) UNSIGNED NOT NULL,
  `checar_sons` tinyint(4) NOT NULL DEFAULT 0,
  `id_familia` bigint(20) UNSIGNED NOT NULL,
  `data_modificacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `motor` varchar(15) NOT NULL DEFAULT '',
  `silabas` tinyint(4) NOT NULL DEFAULT 0,
  `id_realidade` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `id_momento` bigint(20) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `inventarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `id_som` bigint(20) UNSIGNED NOT NULL,
  `id_tipoSom` bigint(20) UNSIGNED NOT NULL,
  `data_modificado` datetime NOT NULL DEFAULT current_timestamp(),
  `peso` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ipaTitulos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `dimensao` int(11) NOT NULL COMMENT 'row, col, voice',
  `pos` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ipaTudo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pos` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `dimensao` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `itensConcordancias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_concordancia` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(250) NOT NULL,
  `descricao` text DEFAULT NULL,
  `padrao` tinyint(4) NOT NULL DEFAULT 0,
  `ordem` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `itens_flexoes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_flexao` bigint(20) UNSIGNED NOT NULL,
  `id_concordancia` bigint(20) UNSIGNED NOT NULL,
  `id_item` bigint(20) UNSIGNED NOT NULL,
  `id_genero` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `itens_palavras` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_palavra` bigint(20) UNSIGNED NOT NULL,
  `id_concordancia` bigint(20) UNSIGNED NOT NULL,
  `id_item` bigint(20) UNSIGNED NOT NULL,
  `usar` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `listas_referentes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_referente` bigint(20) UNSIGNED NOT NULL,
  `id_lista` bigint(20) UNSIGNED NOT NULL,
  `ordem` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `momentos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `id_superior` bigint(20) UNSIGNED NOT NULL,
  `id_realidade` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `data_calendario` varchar(250) NOT NULL DEFAULT '',
  `id_time_system` bigint(20) UNSIGNED NOT NULL,
  `time_value` double DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `nivelUsoPalavra` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descricao` varchar(250) NOT NULL,
  `ordem` int(11) NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `opcoes_sistema` (
  `id` int(11) NOT NULL,
  `opcao` varchar(50) NOT NULL,
  `valor` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `palavras` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `irregular` tinyint(4) DEFAULT 0,
  `id_classe` bigint(20) DEFAULT 0,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `detalhes` mediumtext DEFAULT NULL,
  `pronuncia` varchar(255) DEFAULT NULL,
  `romanizacao` varchar(255) DEFAULT NULL,
  `significado` text DEFAULT NULL,
  `id_forma_dicionario` bigint(20) UNSIGNED DEFAULT 0,
  `id_uso` bigint(20) UNSIGNED NOT NULL,
  `privado` text DEFAULT NULL,
  `publico` tinyint(4) NOT NULL DEFAULT 1,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `id_derivadora` bigint(20) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `palavrasNativas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_palavra` bigint(20) UNSIGNED NOT NULL,
  `id_escrita` bigint(20) UNSIGNED NOT NULL,
  `palavra` varchar(150) NOT NULL,
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `palavras_origens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_palavra` bigint(20) UNSIGNED NOT NULL,
  `id_origem` bigint(20) UNSIGNED NOT NULL,
  `detalhes` text NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `palavras_referentes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_palavra` bigint(20) UNSIGNED NOT NULL,
  `id_referente` bigint(20) UNSIGNED NOT NULL,
  `obs` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `palavras_usos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_palavra` bigint(20) UNSIGNED NOT NULL,
  `id_nivel` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pal_sig_comunidade` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `palavra` varchar(250) NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `significado` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `realidades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(250) NOT NULL,
  `descricao` text NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `publico` tinyint(4) NOT NULL DEFAULT 0,
  `id_idioma_descricao` bigint(20) UNSIGNED NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `referentes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `modificado` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `referentes_descricoes` ( 
  `id` BIGINT UNSIGNED NOT NULL , 
  `id_referente` BIGINT UNSIGNED NOT NULL , 
  `id_idioma` BIGINT UNSIGNED NOT NULL , 
  `descricao` VARCHAR(150) NOT NULL , 
  `detalhes` TEXT NULL 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 

ALTER TABLE `referentes_descricoes` 
  ADD PRIMARY KEY (`id`); 

CREATE TABLE `regrasOrdens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `id_classe1` bigint(20) UNSIGNED NOT NULL,
  `id_classe2` bigint(20) UNSIGNED NOT NULL,
  `separador` varchar(8) NOT NULL DEFAULT ' ',
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `significados_idiomas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_palavra` bigint(20) UNSIGNED NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `significado` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(50) NOT NULL,
  `ipa` varchar(8) NOT NULL,
  `id_referente` bigint(20) UNSIGNED NOT NULL,
  `posx` int(11) NOT NULL,
  `posy` int(11) NOT NULL,
  `posz` int(11) NOT NULL,
  `id_tipoSom` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sonsPersonalizados` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(50) NOT NULL,
  `ipa` varchar(8) NOT NULL,
  `id_referente` bigint(20) UNSIGNED NOT NULL,
  `posx` int(11) NOT NULL,
  `posy` int(11) NOT NULL,
  `posz` int(11) NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `id_tipoSom` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sons_classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=sons, 2=sonsPersonalizados',
  `id_som` bigint(20) UNSIGNED NOT NULL,
  `id_classeSom` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sosail_joes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `tipo_destino` varchar(50) NOT NULL,
  `id_destino` bigint(20) UNSIGNED NOT NULL,
  `data_interacao` datetime NOT NULL DEFAULT current_timestamp(),
  `valor` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `sosail_komentares` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `tipo_destino` varchar(50) NOT NULL,
  `id_destino` bigint(20) UNSIGNED NOT NULL,
  `id_respondido` int(11) NOT NULL DEFAULT 0,
  `data_interacao` datetime NOT NULL DEFAULT current_timestamp(),
  `comentario` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `sosail_sgisons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `id_seguido` bigint(20) UNSIGNED NOT NULL,
  `data_interacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `soundChanges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `changes` text NOT NULL,
  `descricao` text NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(50) NOT NULL,
  `instrucoes` text NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `motor` varchar(15) NOT NULL DEFAULT 'sca2',
  `publico` tinyint(4) NOT NULL DEFAULT 0,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `substituicoes` TEXT NOT NULL DEFAULT '',
  `classes` TEXT NOT NULL DEFAULT '',
  `id_momento_inicial` BIGINT UNSIGNED NOT NULL DEFAULT '0',
  `id_momento_final` BIGINT UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `stats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descricao` text NOT NULL,
  `tipo` varchar(50) NOT NULL COMMENT 'numero, porcentagem, fracao etc',
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `id_realidade` bigint(20) UNSIGNED NOT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo_entidade` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `stats_entidades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_entidade` bigint(20) UNSIGNED NOT NULL,
  `id_stat` bigint(20) UNSIGNED NOT NULL,
  `id_momento` bigint(20) UNSIGNED NOT NULL,
  `valor` varchar(255) NOT NULL,
  `id_entidade_relacionada` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `studason_palavrs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pids` text NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `status_aprendido` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `studason_tests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `link_origem` varchar(250) NOT NULL,
  `link_audio` varchar(250) NOT NULL,
  `texto` text NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `num_palavras` int(11) NOT NULL DEFAULT 0,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo_dest` varchar(10) NOT NULL,
  `id_dest` bigint(20) UNSIGNED NOT NULL,
  `tag` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `teclas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_inventario` bigint(20) UNSIGNED NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `ordem` int(11) NOT NULL,
  `tecla` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tests_importasons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_texto` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tests_palavrs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `texto` varchar(150) NOT NULL,
  `id_palavra_kond` bigint(20) UNSIGNED NOT NULL,
  `exemplos` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `time_adjustment_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_time_system` bigint(20) UNSIGNED NOT NULL,
  `affected_unit_id` bigint(20) UNSIGNED NOT NULL,
  `condition` varchar(255) DEFAULT NULL,
  `adjustment_value` int(11) DEFAULT NULL,
  `target_unit_id` bigint(20) UNSIGNED NOT NULL,
  `target_subunit_id` bigint(20) UNSIGNED NOT NULL,
  `target_unit_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_unit_properties`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `time_cycles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_time_system` bigint(20) UNSIGNED NOT NULL,
  `id_unidade` bigint(20) UNSIGNED NOT NULL,
  `id_unidade_ref` bigint(20) UNSIGNED NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `time_names` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_time_system` bigint(20) UNSIGNED NOT NULL,
  `id_unidade` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `quantidade_subunidade` decimal(10,2) DEFAULT NULL,
  `posicao` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `time_systems` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_realidade` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_padrao` date DEFAULT NULL,
  `padrao` tinyint(4) NOT NULL DEFAULT 0,
  `publico` tinyint(4) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `time_units` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_time_system` bigint(20) UNSIGNED NOT NULL,
  `id_realidade` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(50) NOT NULL,
  `duracao` decimal(20,2) NOT NULL,
  `equivalente` enum('minuto','hora','dia','mes','ano','semana','decada','seculo','milenio') DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tiposSom` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `titulo` varchar(50) NOT NULL,
  `dimx` int(11) NOT NULL,
  `dimy` int(11) NOT NULL,
  `dimz` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `usuarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `nome_completo` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `id_idioma_nativo` bigint(20) UNSIGNED DEFAULT NULL,
  `data_cadastro` datetime DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `confirmacao` varchar(250) DEFAULT NULL,
  `acesso` tinyint(4) DEFAULT NULL DEFAULT 0,
  `publico` tinyint(4) DEFAULT NULL DEFAULT 0,
  `token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `wordbanks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(50) NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `data_criacao` datetime NOT NULL,
  `data_modificacao` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `frases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_idioma` bigint(20) UNSIGNED NOT NULL,
  `id_criador` bigint(20) UNSIGNED NOT NULL,
  `id_original` bigint(20) UNSIGNED NOT NULL,
  `frase` text NOT NULL,
  `info` text NULL,
  `privado` text NULL,
  `descricao` text NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `frases`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `artygs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `artyg_dest`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `asons`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `autosubstituicoes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `blocos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_k` (`id_idioma`,`id_gloss`,`superior`);

ALTER TABLE `classesGeneros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idc_cg` (`id_palavra`,`id_classe`,`id_genero`);

ALTER TABLE `classesSom`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `collabs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `collabs_realidades`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `concordancias`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `drawChars`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `entidades`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `entidades_nomes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `entidades_relacoes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `entidades_tipos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `entidades_tipos_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_entidade_tipo` (`id_entidade_tipo`),
  ADD KEY `id_stat` (`id_stat`);

ALTER TABLE `escritas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_writing` (`id_idioma`,`padrao`);

ALTER TABLE `flexoes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `fontes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `formaSilabaComponente`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `formasSilaba`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `generos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `glifos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `glosses`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `gloss_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gi` (`id_gloss`,`id_item`);

ALTER TABLE `gloss_referentes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `grupos_idiomas`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `historias`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `historias_entidades`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `historias_tipos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `idiomas`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `inventarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ists` (`id_som`,`id_tipoSom`),
  ADD KEY `idx_iiid` (`id_idioma`);

ALTER TABLE `ipaTitulos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `ipaTudo`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `itensConcordancias`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `itens_flexoes`
  ADD PRIMARY KEY (`id`),
  ADD INDEX `idx_if` (`id_genero`, `id_flexao`, `id_concordancia`, `id_item`); 

ALTER TABLE `itens_palavras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_cun` (`id_concordancia`,`id_palavra`),
  ADD KEY `ipx_ipc` (`usar`,`id_palavra`,`id_concordancia`,`id_item`),
  ADD KEY `idx_ipip` (`id_palavra`),
  ADD KEY `idx_ipic` (`id_concordancia`);

ALTER TABLE `listas_referentes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `momentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_momentos_time_systems` (`id_time_system`);

ALTER TABLE `nivelUsoPalavra`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `opcoes_sistema`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `palavras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idc_iid` (`id_idioma`),
  ADD INDEX `idx_idp` (`id`),
  ADD KEY `idx_pd` (`id_forma_dicionario`);

ALTER TABLE `palavrasNativas`
  ADD PRIMARY KEY (`id`),
  ADD INDEX `idx_idpn` (`id_palavra`),
  ADD KEY `idx_pe` (`id_escrita`,`id_palavra`);

ALTER TABLE `palavras_origens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pop` (`id_palavra`);

ALTER TABLE `palavras_referentes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ref` (`id_referente`,`id_palavra`),
  ADD KEY `idx_prp` (`id_palavra`);

ALTER TABLE `palavras_usos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `pal_sig_comunidade`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `realidades`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `referentes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `regrasOrdens`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `significados_idiomas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sip` (`id_palavra`),
  ADD KEY `idx_sii` (`id_idioma`);

ALTER TABLE `sons`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sonsPersonalizados`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sons_classes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sosail_joes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sosail_komentares`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sosail_sgisons`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `soundChanges`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `stats`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `stats_entidades`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `studason_palavrs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `studason_tests`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `teclas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tci` (`id_inventario`);

ALTER TABLE `tests_importasons`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tests_palavrs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `time_adjustment_rules`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `time_cycles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_time_system` (`id_time_system`),
  ADD KEY `id_unidade` (`id_unidade`),
  ADD KEY `id_unidade_ref` (`id_unidade_ref`);

ALTER TABLE `time_names`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_time_system` (`id_time_system`),
  ADD KEY `id_unidade` (`id_unidade`);

ALTER TABLE `time_systems`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_realidade` (`id_realidade`);

ALTER TABLE `time_units`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_time_system` (`id_time_system`),
  ADD KEY `id_realidade` (`id_realidade`);

ALTER TABLE `tiposSom`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `wordbanks`
  ADD PRIMARY KEY (`id`);

ALTER TABLE collabs ADD CONSTRAINT unique_usuario_idioma UNIQUE (id_usuario, id_idioma);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
