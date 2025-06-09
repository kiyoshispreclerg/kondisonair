SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `artygs` (
  `id` int(11) NOT NULL,
  `nome` varchar(250) NOT NULL,
  `texto` mediumblob NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `publico` tinyint(4) NOT NULL DEFAULT 0,
  `id_pap` int(11) NOT NULL DEFAULT 0,
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `id_idioma` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `artyg_dest` (
  `id` int(11) NOT NULL,
  `id_artyg` int(11) NOT NULL,
  `tipo_dest` varchar(15) DEFAULT NULL,
  `id_dest` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `asons` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_acao` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo_destino` varchar(30) NOT NULL,
  `id_destino` int(11) NOT NULL,
  `tipo` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `autosubstituicoes` (
  `id` int(11) NOT NULL,
  `id_glifo` int(11) NOT NULL,
  `tecla` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ipa` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `id_escrita` int(11) NOT NULL,
  `glifos` varchar(15) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `blocos` (
  `id` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `tipo_nucleo` varchar(15) NOT NULL,
  `id_nucleo` int(11) NOT NULL,
  `tipo_dependente` varchar(15) NOT NULL,
  `id_dependente` int(11) NOT NULL,
  `id_separador` int(11) NOT NULL,
  `descricao` varchar(250) NOT NULL,
  `lado` tinyint(4) NOT NULL DEFAULT 1,
  `nome` varchar(150) NOT NULL,
  `id_gloss` int(11) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `id_gloss` int(11) NOT NULL,
  `proto_tipo` tinyint(4) NOT NULL DEFAULT 0,
  `superior` int(11) NOT NULL DEFAULT 0,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `classesGeneros` (
  `id` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL DEFAULT 0,
  `id_palavra` int(11) NOT NULL,
  `id_genero` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `classesSom` (
  `id` int(11) NOT NULL,
  `simbolo` varchar(3) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `id_tipoClasse` int(11) DEFAULT NULL COMMENT 'Onset, nucleo, coda...'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `collabs` (
  `id` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `collabs_realidades` (
  `id` int(11) NOT NULL,
  `id_realidade` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `concordancias` (
  `id` int(11) NOT NULL,
  `id_gloss` int(11) NOT NULL,
  `nome` varchar(250) NOT NULL,
  `descricao` text NOT NULL,
  `id_idioma` int(11) NOT NULL DEFAULT 0,
  `id_classe` int(11) NOT NULL DEFAULT 0,
  `depende` int(11) NOT NULL DEFAULT 0,
  `obrigatorio` tinyint(4) NOT NULL DEFAULT 0,
  `genero` int(11) NOT NULL DEFAULT 0,
  `ordem` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `drawChars` (
  `id` int(11) NOT NULL,
  `id_escrita` int(11) NOT NULL,
  `glifo` varchar(15) NOT NULL DEFAULT '',
  `descricao` text NOT NULL,
  `ordem` int(11) NOT NULL,
  `input` varchar(8) NOT NULL DEFAULT '',
  `id_principal` int(11) NOT NULL DEFAULT 0,
  `vetor` mediumtext NOT NULL,
  `data_modificado` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `entidades` (
  `id` int(11) NOT NULL,
  `nome_legivel` varchar(150) NOT NULL DEFAULT '',
  `descricao` mediumtext DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `id_criador` int(11) NOT NULL,
  `publico` tinyint(4) NOT NULL DEFAULT 0,
  `id_pai` int(11) NOT NULL DEFAULT 0,
  `id_realidade` int(11) NOT NULL,
  `id_tipo` int(11) NOT NULL,
  `descricao_curta` varchar(250) NOT NULL,
  `privado` text DEFAULT NULL,
  `rule` enum('character','place','item','other') NOT NULL DEFAULT 'other'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `entidades_nomes` (
  `id` int(11) NOT NULL,
  `id_entidade` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `nome` varchar(250) NOT NULL,
  `info` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `entidades_relacoes` (
  `id` int(11) NOT NULL,
  `id_entidade1` int(11) NOT NULL,
  `id_entidade2` int(11) NOT NULL,
  `id_momento_inicio` int(11) DEFAULT NULL,
  `id_momento_fim` int(11) DEFAULT NULL,
  `tipo_relacao` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `entidades_tipos` (
  `id` int(11) NOT NULL,
  `id_realidade` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `id_superior` int(11) NOT NULL DEFAULT 0,
  `id_usuario` int(11) NOT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `rule` enum('character','place','item','other') NOT NULL DEFAULT 'other'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `entidades_tipos_stats` (
  `id` int(11) NOT NULL,
  `id_entidade_tipo` int(11) NOT NULL,
  `id_stat` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `escritas` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `id_tipo` int(11) NOT NULL,
  `publico` tinyint(4) NOT NULL,
  `descricao` text NOT NULL,
  `id_fonte` int(11) NOT NULL DEFAULT 0,
  `tamanho` varchar(15) NOT NULL DEFAULT 'unset',
  `id_nativo` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL,
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

CREATE TABLE `etimologias` (
  `id` int(11) NOT NULL,
  `id_palavra` int(11) NOT NULL,
  `id_origem` int(11) NOT NULL,
  `descricao` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `flexoes` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `ordem` int(11) NOT NULL,
  `regra_pronuncia` text NOT NULL,
  `regra_romanizacao` text NOT NULL,
  `motor` varchar(15) NOT NULL DEFAULT 'sca2'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `fontes` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `arquivo` varchar(150) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `publica` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `formaSilabaComponente` (
  `id` int(11) NOT NULL,
  `id_formaSilaba` int(11) NOT NULL,
  `id_classeSom` int(11) NOT NULL,
  `ordem` int(11) NOT NULL,
  `obrigatorio` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `formasSilaba` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) DEFAULT NULL,
  `id_idioma` int(11) NOT NULL,
  `tipo` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=geral/medial, 1=inicial, 2=final, 3=monosilabas',
  `peso` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `generos` (
  `id` int(11) NOT NULL,
  `id_gloss` int(11) NOT NULL,
  `nome` varchar(250) NOT NULL,
  `descricao` text NOT NULL,
  `id_idioma` int(11) NOT NULL DEFAULT 0,
  `id_classe` int(11) NOT NULL DEFAULT 0,
  `depende` int(11) NOT NULL DEFAULT 0,
  `obrigatorio` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `glifos` (
  `id` int(11) NOT NULL,
  `id_escrita` int(11) NOT NULL,
  `glifo` varchar(15) NOT NULL,
  `descricao` text NOT NULL,
  `ordem` int(11) NOT NULL,
  `input` varchar(8) NOT NULL COMMENT 'Simbolo inserido no teclado para converter para este glifo',
  `id_principal` int(11) NOT NULL DEFAULT 0,
  `data_modificado` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `glosses` (
  `id` int(11) NOT NULL,
  `gloss` varchar(15) NOT NULL,
  `descricao` varchar(250) NOT NULL,
  `tipo` char(1) NOT NULL,
  `id_referenteX` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `glosses` (`id`, `gloss`, `descricao`, `tipo`, `id_referenteX`) VALUES
(1, 'N', 'Noun', 'k', 0),
(2, 'V', 'Verb', 'k', 0),
(3, 'NP', 'Noun Phrase', 'b', 0),
(4, 'GND', 'Gender', 'c', 0),
(5, 'NUM', 'Number', 'c', 0),
(6, 'M', 'masculine gender', 'i', 0),
(7, 'F', 'feminine gender', 'i', 0),
(8, 'SG', 'singular', 'i', 0),
(9, 'PL', 'Plural', 'i', 0),
(10, '1', 'first person', 'i', 0),
(11, 'Prep', 'Preposition', 'k', 0),
(12, 'VP', 'Verbal Phrase', 'b', 0),
(13, '2', 'second person', 'i', 0),
(14, '3', 'third person', 'i', 0),
(15, '1SG', 'first person singular', 'i', 0),
(16, '2SG', 'second person singular', 'i', 0),
(17, '3SG', 'third person singular', 'i', 0),
(18, '1PL', 'first person plural', 'i', 0),
(19, 'PP', 'Prepositional Phrase', 'b', 0),
(20, 'Part', 'Particle', 'k', 0),
(21, 'Ppos', 'Postposition', 'k', 0),
(22, 'Adj', 'Adjective', 'k', 0),
(23, 'Adv', 'Adverb', 'k', 0),
(24, 'Conj', 'Conjunction', 'k', 0),
(25, 'Art', 'Article', 'k', 0),
(26, 'Det', 'Determiner', 'k', 0),
(27, 'Int', 'Interjection', 'k', 0),
(28, 'NP1', 'Noun Phrase', 'b', 0),
(29, 'NP2', 'Noun Phrase', 'b', 0),
(30, 'NP3', 'Noun Phrase', 'b', 0),
(31, 'S', 'Sentence', 'b', 0),
(32, 'S0', 'Sentence', 'b', 0),
(33, 'AdjP', 'Adjective Phrase', 'b', 0),
(34, 'AdvP', 'Adverbial Phrase', 'b', 0),
(35, 'NC', 'Noun Complement', 'b', 0),
(36, 'Num', 'Numeral', 'k', 0),
(37, 'Pron', 'Pronoum', 'k', 0),
(38, 'INF', 'Infinitive', 'i', 0),
(39, 'IND', 'Indicative', 'i', 0),
(40, 'SUBJ', 'Subjuntive', 'i', 0),
(41, 'IMP', 'imperative mood', 'i', 0),
(42, 'GER', 'Gerund', 'i', 0),
(43, 'PTC', 'Participle', 'i', 0),
(44, 'GEN', 'genitive case', 'i', NULL),
(45, 'ACC', 'accusative case', 'i', NULL),
(46, 'ABS', 'absolutive case', 'i', NULL),
(47, 'ACT', 'active voice', 'i', NULL),
(48, 'ART', 'article', 'i', NULL),
(49, 'AUX', 'auxiliary verb', 'i', NULL),
(50, 'DAT', 'dative case', 'i', NULL),
(51, 'DEF', 'definite', 'i', NULL),
(52, 'DEM', 'demonstrative', 'i', NULL),
(53, 'DO', 'direct object', 'i', NULL),
(54, 'ERG', 'ergative case', 'i', NULL),
(55, 'COP', 'copula', 'i', NULL),
(56, 'FUT', 'future tense', 'i', NULL),
(57, 'IMPF', 'imperfect', 'i', NULL),
(58, 'IND', 'indicative mood', 'i', NULL),
(59, 'INF', 'infinitive', 'i', NULL),
(60, 'INS', 'instrumental case', 'i', NULL),
(61, 'IRR', 'irrealis mood', 'i', NULL),
(62, 'LOC', 'locative case', 'i', NULL),
(63, 'N', 'neuter gender', 'i', NULL),
(64, 'INDEF', 'indefinite', 'i', NULL),
(65, 'NEUT', 'neutral aspect', 'i', NULL),
(66, 'NOM', 'nominative case', 'i', NULL),
(67, 'ONOM', 'onomatopoeia', 'i', NULL),
(68, 'OBL', 'oblique case', 'i', NULL),
(69, 'PASS', 'passive voice', 'i', NULL),
(70, 'PFV', 'perfective aspect', 'i', NULL),
(71, 'POL', 'polite register', 'i', NULL),
(72, 'POT', 'potential mood', 'i', NULL),
(73, 'PRF', 'perfect', 'i', NULL),
(74, 'PRS', 'present tense', 'i', NULL),
(75, 'PST', 'past tense', 'i', NULL),
(76, 'Q', 'question word', 'i', NULL),
(77, 'REL', 'relative clause marker, relative pronoun affix', 'i', NULL),
(78, 'TOP', 'topic marker', 'i', NULL),
(79, '2PL', 'second person plural', 'i', NULL),
(80, '3PL', 'third person plural', 'i', NULL),
(81, 'INCL', 'inclusive', 'i', NULL);

CREATE TABLE `gloss_itens` (
  `id` int(11) NOT NULL,
  `id_item` int(11) NOT NULL,
  `id_gloss` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `gloss_referentes` (
  `id` int(11) NOT NULL,
  `id_gloss` int(11) NOT NULL,
  `id_referente` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `grupos_idiomas` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `descricao` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `historias` (
  `id` int(11) NOT NULL,
  `id_realidade` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(350) NOT NULL,
  `descricao` text NOT NULL,
  `status` varchar(15) NOT NULL,
  `id_superior` int(11) NOT NULL,
  `id_tipo` int(11) NOT NULL COMMENT 'novel, manga, resumo, beats, rascunho etc',
  `texto` mediumtext NOT NULL DEFAULT '',
  `id_momento` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `historias_entidades` (
  `id` int(11) NOT NULL,
  `id_realidade` int(11) NOT NULL,
  `id_entidade` int(11) NOT NULL,
  `id_historia` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `historias_tipos` (
  `id` int(11) NOT NULL,
  `id_superior` int(11) NOT NULL DEFAULT 0,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descricao` text NOT NULL,
  `id_realidade` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `idiomas` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_tipo` int(11) NOT NULL,
  `publico` tinyint(4) NOT NULL,
  `copyright` text DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `id_nome_nativo` int(11) NOT NULL,
  `id_ascendente` int(11) NOT NULL,
  `descricao` text DEFAULT NULL,
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
  `id_idioma_descricao` int(11) NOT NULL,
  `checar_sons` tinyint(4) NOT NULL DEFAULT 0,
  `id_familia` int(11) NOT NULL DEFAULT 0,
  `data_modificacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `motor` varchar(15) NOT NULL DEFAULT '',
  `silabas` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `inventarios` (
  `id` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `id_som` int(11) NOT NULL,
  `id_tipoSom` int(11) NOT NULL COMMENT '0=sonsPersonalizados',
  `x_id_classeSom` int(11) NOT NULL,
  `data_modificado` datetime NOT NULL DEFAULT current_timestamp(),
  `peso` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ipaTitulos` (
  `id` int(11) NOT NULL,
  `dimensao` int(11) NOT NULL COMMENT 'row, col, voice',
  `pos` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `id_idioma` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ipaTudo` (
  `id` int(11) NOT NULL,
  `pos` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `dimensao` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `ipaTudo` (`id`, `pos`, `nome`, `dimensao`) VALUES
(1, 10, 'Bilabial', 1),
(2, 80, 'Glottal', 1),
(3, 15, 'Labiodental', 1),
(4, 45, 'Retroflex', 1),
(5, 10, 'Nasal', 2),
(6, 15, 'Plosive', 2),
(7, 70, 'Lateral tap', 2),
(8, 40, 'Aproximant', 2),
(9, 10, 'Close', 5),
(10, 15, 'Near-close', 5),
(11, 20, 'Close-mid', 5),
(12, 25, 'Mid', 5),
(13, 30, 'Open-mid', 5),
(14, 35, 'Near-open', 5),
(15, 40, 'Open', 5),
(16, 10, 'Unounded', 7),
(17, 15, 'Rounded', 7),
(18, 10, 'Front', 6),
(19, 15, 'Near-front', 6),
(20, 20, 'Central', 6),
(21, 25, 'Near-back', 6),
(22, 30, 'Back', 6),
(23, 5, 'Labial', 1),
(24, 20, 'Linguolabial', 1),
(25, 25, 'Coronal', 1),
(26, 30, 'Dental', 1),
(27, 40, 'Postalveolar', 1),
(28, 35, 'Alveolar', 1),
(29, 50, 'Palatal', 1),
(30, 55, 'Dorsal', 1),
(31, 60, 'Velar', 1),
(32, 65, 'Uvular', 1),
(33, 70, 'Laryngeal', 1),
(34, 75, 'Pharyngeal/Epiglottal', 1),
(35, 20, 'Sibilant Affricate', 2),
(36, 25, 'Non Sibilant Affricate', 2),
(37, 30, 'Sibilant Fricative', 2),
(38, 35, 'Non Sibilant Fricative', 2),
(39, 45, 'Tap/Flap', 2),
(40, 50, 'Trill', 2),
(41, 55, 'Lateral Affricate', 2),
(42, 60, 'Lateral Fricative', 2),
(43, 65, 'Lateral Approximant', 2),
(44, 70, 'Lateral Tap/Flap', 2),
(45, 75, 'Ejective Stop', 2),
(46, 80, 'Ejective Affricate', 2),
(47, 85, 'Ejective Fricative', 2),
(48, 90, 'Ejective Lateral', 2),
(49, 95, 'Implosive', 2),
(50, 100, 'Click', 2),
(51, 10, 'Unvoiced', 3),
(52, 15, 'Voiced', 3),
(53, 10, 'A', 9),
(54, 10, '1', 10),
(55, 12, 'B', 9),
(56, 14, 'C', 9),
(57, 16, 'D', 9),
(58, 18, 'E', 9),
(59, 20, 'F', 9),
(60, 22, 'G', 9),
(61, 24, 'H', 9),
(62, 12, '2', 10),
(63, 14, '3', 10),
(64, 16, '4', 10),
(65, 18, '5', 10),
(66, 20, '6', 10),
(67, 10, 'X', 11);

CREATE TABLE `itensConcordancias` (
  `id` int(11) NOT NULL,
  `id_concordancia` int(11) NOT NULL,
  `id_glossX` int(11) NOT NULL DEFAULT 1,
  `nome` varchar(250) NOT NULL,
  `descricao` text DEFAULT NULL,
  `padrao` tinyint(4) NOT NULL DEFAULT 0,
  `ordem` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `itens_flexoes` (
  `id` int(11) NOT NULL,
  `id_flexao` int(11) NOT NULL,
  `id_concordancia` int(11) NOT NULL,
  `id_item` int(11) NOT NULL,
  `id_genero` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `itens_palavras` (
  `id` int(11) NOT NULL,
  `id_palavra` int(11) NOT NULL,
  `id_concordancia` int(11) NOT NULL,
  `id_item` int(11) NOT NULL,
  `usar` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `listasReferentes` (
  `id` int(11) NOT NULL,
  `nome` varchar(25) NOT NULL,
  `descricao` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `listas_referentes` (
  `id` int(11) NOT NULL,
  `id_referente` int(11) NOT NULL,
  `id_lista` int(11) NOT NULL,
  `ordem` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lugares` (
  `id` int(11) NOT NULL,
  `id_superior` int(11) NOT NULL,
  `titulo` varchar(350) NOT NULL,
  `descricao` text NOT NULL,
  `id_realidade` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_inicio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lugares_tipos` (
  `id` int(11) NOT NULL,
  `id_superior` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descricao` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `momentos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_superior` int(11) NOT NULL DEFAULT 0,
  `id_realidade` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `data_calendario` varchar(250) NOT NULL DEFAULT '',
  `id_time_system` int(10) UNSIGNED DEFAULT NULL,
  `time_value` double DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `nivelUsoPalavra` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descricao` varchar(250) NOT NULL,
  `ordem` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `opcoes_sistema` (
  `id` int(11) NOT NULL,
  `opcao` varchar(50) NOT NULL,
  `valor` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `opcoes_sistema` (`id`, `opcao`, `valor`) VALUES
(1, 'limite_langs', '3'),
(2, 'inscr_aberta', '1'),
(3, 'palavras_lang', '5000'),
(4, 'fonts_usuario', '3'),
(5, 'limite_scs_lang', '10'),
(6, 'limite_scs_user', '20'),
(7, 'palavras_base_lang', '1000'),
(8, 'lim_lang_parts', '12'),
(9, 'limite_escritas_l', '3');

CREATE TABLE `palavras` (
  `id` int(11) NOT NULL,
  `irregular` tinyint(4) DEFAULT 0,
  `id_classe` int(11) DEFAULT NULL,
  `id_idioma` int(11) NOT NULL,
  `detalhes` text DEFAULT NULL,
  `pronuncia` varchar(255) DEFAULT NULL,
  `romanizacao` varchar(255) DEFAULT NULL,
  `significado` text DEFAULT NULL,
  `id_forma_dicionario` int(11) DEFAULT 0,
  `id_uso` int(11) NOT NULL DEFAULT 0,
  `privado` text DEFAULT NULL,
  `publico` tinyint(4) NOT NULL DEFAULT 1,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  `id_derivadora` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `palavrasNativas` (
  `id` int(11) NOT NULL,
  `id_palavra` int(11) NOT NULL,
  `id_escrita` int(11) NOT NULL,
  `palavra` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `palavras_origens` (
  `id` int(11) NOT NULL,
  `id_palavra` int(11) NOT NULL,
  `id_origem` int(11) NOT NULL,
  `detalhes` text NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `palavras_referentes` (
  `id` int(11) NOT NULL,
  `id_palavra` int(11) NOT NULL,
  `id_referente` int(11) NOT NULL,
  `obs` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `palavras_usos` (
  `id` int(11) NOT NULL,
  `id_palavra` int(11) NOT NULL,
  `id_nivel` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pal_sig_comunidade` (
  `id` int(11) NOT NULL,
  `palavra` varchar(250) NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `significado` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `personagens` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_realidade` int(11) NOT NULL,
  `titulo` varchar(350) NOT NULL,
  `descricao` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `realidades` (
  `id` int(11) NOT NULL,
  `titulo` varchar(250) NOT NULL,
  `descricao` text NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `publico` tinyint(4) NOT NULL DEFAULT 0,
  `id_idioma_descricao` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `referentes` (
  `id` int(11) NOT NULL,
  `descricao` varchar(512) NOT NULL,
  `detalhes` text DEFAULT NULL,
  `descricaoPort` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `referentes` (`id`, `descricao`, `detalhes`, `descricaoPort`) VALUES
(1, 'I (me)', 'Primeira pessoa, o falante', 'eu'),
(2, 'head', 'lit. cabeça, parte frontal ou superior do corpo', 'cabeça'),
(3, 'meat', 'carne', 'carne'),
(4, 'to walk', '', 'andar'),
(5, 'you (singular)', 'Segunda pessoa, o ouvinte', 'tu, você'),
(6, 'man (human being)', '', 'homem (ser humano)'),
(7, 'woman', '', 'mulher'),
(8, 'to eat', '', 'comer'),
(19, 'they (singular), he, she', NULL, 'ele, ela'),
(20, 'we', '', 'nós'),
(21, 'you (plural)', '', 'vós, vocês'),
(22, 'they (plural)', NULL, 'eles, elas'),
(23, 'this', NULL, 'este, isto'),
(24, 'that', NULL, 'aquele, aquilo'),
(25, 'here', NULL, 'aqui'),
(26, 'there', NULL, 'ali, lá'),
(27, 'who', NULL, 'quem'),
(28, 'what', NULL, 'que'),
(29, 'where', NULL, 'onde'),
(30, 'when', NULL, 'quando'),
(31, 'how', NULL, 'como'),
(32, 'not', NULL, 'não'),
(33, 'all', '', 'todo, tudo'),
(34, 'many', NULL, 'muito'),
(35, 'some', NULL, 'algum'),
(36, 'few', NULL, 'pouco'),
(37, 'other', NULL, 'outro'),
(38, 'one', NULL, 'um'),
(39, 'two', NULL, 'dois'),
(40, 'three', NULL, 'três'),
(41, 'four', NULL, 'quatro'),
(42, 'five', NULL, 'cinco'),
(43, 'big', NULL, 'grande'),
(44, 'long', NULL, 'longo'),
(45, 'wide', NULL, 'largo'),
(46, 'thick', NULL, 'grosso'),
(47, 'heavy', NULL, 'pesado'),
(48, 'small', NULL, 'pequeno'),
(49, 'short', NULL, 'curto'),
(50, 'narrow', NULL, 'estreito'),
(51, 'thin', NULL, 'fino'),
(53, 'man (adult male)', NULL, 'homem (humano adulto masculino)'),
(55, 'child', NULL, 'criança'),
(56, 'wife', NULL, 'esposa'),
(57, 'husband', NULL, 'marido'),
(58, 'mother', NULL, 'mãe'),
(59, 'father', NULL, 'pai'),
(60, 'animal', NULL, 'animal'),
(61, 'fish', NULL, 'peixe'),
(62, 'bird', NULL, 'ave'),
(63, 'dog', NULL, 'cachorro'),
(64, 'louse', NULL, 'piolho'),
(65, 'snake', NULL, 'serpente'),
(66, 'worm', NULL, 'verme'),
(67, 'tree', NULL, 'árvore'),
(68, 'forest', NULL, 'floresta'),
(69, 'stick', NULL, 'bastão'),
(70, 'fruit', NULL, 'fruta'),
(71, 'seed', NULL, 'semente'),
(72, 'leaf', NULL, 'folha'),
(73, 'root', NULL, 'raiz'),
(74, 'bark (of a tree)', NULL, 'casca'),
(75, 'flower', NULL, 'flor'),
(76, 'grass', NULL, 'relva, grama'),
(77, 'rope', NULL, 'corda'),
(78, 'skin', NULL, 'pele'),
(80, 'blood', NULL, 'sangue'),
(81, 'bone', NULL, 'osso'),
(82, 'fat (noun)', NULL, 'gordura'),
(83, 'egg', NULL, 'ovo'),
(84, 'horn', NULL, 'chifre'),
(85, 'tail', NULL, 'rabo'),
(86, 'feather', NULL, 'pena, pluma'),
(87, 'hair', NULL, 'cabelo, pêlo'),
(89, 'ear', NULL, 'orelha'),
(90, 'eye', NULL, 'olho'),
(91, 'nose', NULL, 'nariz'),
(92, 'mouth', NULL, 'boca'),
(93, 'tooth', NULL, 'dente'),
(94, 'tongue (organ)', NULL, 'língua (órgão)'),
(95, 'fingernail', NULL, 'unha'),
(96, 'foot', NULL, 'pé'),
(97, 'leg', NULL, 'perna'),
(98, 'knee', NULL, 'joelho'),
(99, 'hand', NULL, 'mão'),
(100, 'wing', NULL, 'asa'),
(101, 'belly', NULL, 'barriga'),
(102, 'guts', NULL, 'entranhas'),
(103, 'neck', NULL, 'pescoço'),
(104, 'back', NULL, 'costas'),
(105, 'breast', NULL, 'peito'),
(106, 'heart', NULL, 'coração'),
(107, 'liver', NULL, 'fígado'),
(108, 'to drink', NULL, 'to drink'),
(110, 'to bite', NULL, 'to bite'),
(111, 'to suck', NULL, 'to suck'),
(112, 'to spit', NULL, 'to spit'),
(113, 'to vomit', NULL, 'to vomit'),
(114, 'to blow', NULL, 'to blow'),
(115, 'to breathe', NULL, 'to breathe'),
(116, 'to laugh', NULL, 'to laugh'),
(117, 'to see', NULL, 'to see'),
(118, 'to hear', NULL, 'to hear'),
(119, 'to know', NULL, 'to know'),
(120, 'to think', NULL, 'to think'),
(121, 'to smell', NULL, 'to smell'),
(122, 'to fear', NULL, 'to fear'),
(123, 'to sleep', NULL, 'to sleep'),
(124, 'to live', NULL, 'to live'),
(125, 'to die', NULL, 'to die'),
(126, 'to kill', NULL, 'to kill'),
(127, 'to fight', NULL, 'to fight'),
(128, 'to hunt', NULL, 'to hunt'),
(129, 'to hit', NULL, 'to hit'),
(130, 'to cut', NULL, 'to cut'),
(131, 'to split', NULL, 'to split'),
(132, 'to stab', NULL, 'to stab'),
(133, 'to scratch', NULL, 'to scratch'),
(134, 'to dig', NULL, 'to dig'),
(135, 'to swim', NULL, 'to swim'),
(136, 'to fly', NULL, 'to fly'),
(137, 'he', NULL, 'ele'),
(138, 'to come', NULL, 'to come'),
(139, 'to lie (as in a bed)', NULL, 'to lie (as in a bed)'),
(140, 'to sit', NULL, 'to sit'),
(141, 'to stand', NULL, 'to stand'),
(142, 'to turn (intransitive)', NULL, 'to turn (intransitive)'),
(143, 'to fall', NULL, 'to fall'),
(144, 'to give', NULL, 'to give'),
(145, 'to hold', NULL, 'to hold'),
(146, 'to squeeze', NULL, 'to squeeze'),
(147, 'to rub', NULL, 'to rub'),
(148, 'to wash', NULL, 'to wash'),
(149, 'to wipe', NULL, 'to wipe'),
(150, 'to pull', NULL, 'to pull'),
(151, 'to push', NULL, 'to push'),
(152, 'to throw', NULL, 'to throw'),
(153, 'to tie', NULL, 'to tie'),
(154, 'to sew', NULL, 'to sew'),
(155, 'to count', NULL, 'to count'),
(156, 'to say', NULL, 'to say'),
(157, 'to sing', NULL, 'to sing'),
(158, 'to play', NULL, 'to play'),
(159, 'to float', NULL, 'to float'),
(160, 'to flow', NULL, 'to flow'),
(161, 'to freeze', NULL, 'to freeze'),
(162, 'to swell', NULL, 'to swell'),
(163, 'sun', NULL, 'sun'),
(164, 'moon', '', 'lua'),
(165, 'star', NULL, 'star'),
(166, 'water', NULL, 'water'),
(167, 'rain', NULL, 'rain'),
(168, 'river', NULL, 'river'),
(169, 'lake', NULL, 'lake'),
(170, 'sea', NULL, 'sea'),
(171, 'salt', NULL, 'salt'),
(172, 'stone', NULL, 'stone'),
(173, 'sand', NULL, 'sand'),
(174, 'dust', '', 'areia'),
(175, 'earth', '', 'terra'),
(176, 'cloud', '', 'nuvem'),
(177, 'fog', NULL, 'fog'),
(178, 'sky', NULL, 'sky'),
(179, 'wind', NULL, 'wind'),
(180, 'snow', NULL, 'snow'),
(181, 'ice', NULL, 'ice'),
(182, 'smoke', NULL, 'smoke'),
(183, 'fire', NULL, 'fire'),
(184, 'ash', NULL, 'ash'),
(185, 'to burn', NULL, 'to burn'),
(186, 'road', NULL, 'road'),
(187, 'mountain', NULL, 'mountain'),
(188, 'red', NULL, 'red'),
(189, 'green', NULL, 'green'),
(190, 'yellow', NULL, 'yellow'),
(191, 'white', NULL, 'white'),
(192, 'black', '', 'preto'),
(193, 'night', NULL, 'night'),
(194, 'day', '', 'dia'),
(195, 'year', NULL, 'year'),
(196, 'warm', NULL, 'warm'),
(197, 'cold', '', 'frio'),
(198, 'full', NULL, 'full'),
(199, 'new', NULL, 'new'),
(200, 'old', NULL, 'old'),
(201, 'good', NULL, 'good'),
(202, 'bad', NULL, 'bad'),
(203, 'rotten', NULL, 'rotten'),
(204, 'dirty', NULL, 'dirty'),
(205, 'straight', NULL, 'straight'),
(206, 'round', NULL, 'round'),
(207, 'sharp (as a knife)', NULL, 'sharp (as a knife)'),
(208, 'dull (as a knife)', '', 'cego (como uma faca)'),
(209, 'smooth', NULL, 'smooth'),
(210, 'wet', NULL, 'wet'),
(211, 'dry', NULL, 'dry'),
(212, 'correct', NULL, 'correct'),
(213, 'near', NULL, 'near'),
(214, 'far', '', 'longe'),
(215, 'right', NULL, 'right'),
(216, 'left', NULL, 'left'),
(217, 'at', NULL, 'at'),
(218, 'in', NULL, 'in'),
(219, 'with', NULL, 'with'),
(220, 'and', '', 'e'),
(221, 'if', NULL, 'if'),
(222, 'because', '', 'por que'),
(223, 'name', NULL, 'name'),
(224, 'GENITIVE', '', 'GENITIVO'),
(225, 'POSSESSIVE', '', 'POSSESSIVE');

CREATE TABLE `regrasOrdens` (
  `id` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `id_classe1` int(11) NOT NULL,
  `id_classe2` int(11) NOT NULL,
  `separador` varchar(8) NOT NULL DEFAULT ' ',
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `romanizacoes` (
  `id` int(11) NOT NULL,
  `id_inventario` int(11) NOT NULL,
  `caractere` varchar(12) NOT NULL,
  `ordem` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `significados_idiomas` (
  `id` int(11) NOT NULL,
  `id_palavra` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `significado` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sons` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `ipa` varchar(8) NOT NULL,
  `id_referente` int(11) NOT NULL COMMENT 'Para nomes traduzidos',
  `posx` int(11) NOT NULL,
  `posy` int(11) NOT NULL,
  `posz` int(11) NOT NULL,
  `id_tipoSom` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sons` (`id`, `nome`, `ipa`, `id_referente`, `posx`, `posy`, `posz`, `id_tipoSom`) VALUES
(1, 'Voiceless bilabial nasal', 'm̥', 0, 10, 10, 10, 1),
(2, 'Voiceless bilabial stop', 'p', 0, 15, 10, 10, 1),
(4, 'Close front unrounded vowel', 'i', 0, 10, 10, 10, 2),
(7, 'Close back rounded vowel', 'u', 0, 30, 10, 15, 2),
(11, 'Voiced bilabial stop', 'b', 0, 15, 10, 15, 1),
(14, 'Voiced bilabial nasal', 'm', 0, 10, 10, 15, 1),
(15, 'Voiceless alveolar fricative', 's', 0, 30, 35, 10, 1),
(16, 'Voiced alveolar fricative', 'z', 0, 30, 35, 15, 1),
(17, 'Voiceless alveolar nasal', 'n̥', 0, 10, 35, 10, 1),
(18, 'Voiced alveolar nasal', 'n', 0, 10, 35, 15, 1),
(19, 'Voiceless alveolar stop', 't', 0, 15, 35, 10, 1),
(20, 'Voiced alveolar stop', 'd', 0, 15, 35, 15, 1),
(21, 'Voiceless postalveolar fricative', 'ʃ', 0, 30, 40, 10, 1),
(22, 'Voiced postalveolar fricative', 'ʒ', 0, 30, 40, 15, 1),
(23, 'Close front rounded vowel', 'y', 0, 10, 10, 15, 2),
(24, 'Open-mid front unrounded vowel', 'ɛ', 0, 10, 30, 10, 2),
(25, 'Open-mid front rounded vowel', 'œ', 0, 10, 30, 15, 2),
(26, 'Open front unrounded vowel', 'a', 0, 10, 40, 10, 2),
(27, 'Close back unrounded vowel', 'ɯ', 0, 30, 10, 10, 2),
(28, 'Open front rounded vowel', 'ɶ', 0, 10, 40, 15, 2),
(29, 'Near-open front unrounded vowel', 'æ', 0, 10, 35, 10, 2),
(30, 'Mid front unrounded vowel', 'e̞', 0, 10, 25, 10, 2),
(31, 'Mid front rounded vowel', 'ø̞', 0, 10, 25, 15, 2),
(32, 'Close-mid front unrounded vowel', 'e', 0, 10, 20, 10, 2),
(33, 'Close-mid front rounded vowel', 'ø', 0, 10, 20, 15, 2),
(34, 'Near-close near-front unrounded vowel', 'ɪ', 0, 15, 15, 10, 2),
(35, 'Near-close near-front rounded vowel', 'ʏ', 0, 15, 15, 15, 2),
(36, 'Close central rounded vowel', 'ʉ', 0, 20, 10, 15, 2),
(37, 'Close central unrounded vowel', 'ɨ', 0, 20, 10, 10, 2),
(38, 'Near-close near-back rounded vowel', 'ʊ', 0, 25, 15, 15, 2),
(39, 'Close-mid central unrounded vowel', 'ɘ', 0, 20, 20, 10, 2),
(40, 'Close-mid central rounded vowel', 'ɵ', 0, 20, 20, 15, 2),
(41, 'Mid central vowel', 'ə', 0, 20, 25, 10, 2),
(42, 'Open-mid central unrounded vowel', 'ɜ', 0, 20, 30, 10, 2),
(43, 'Open-mid central rounded vowel', 'ɞ', 0, 20, 30, 15, 2),
(44, 'Near-open central vowel', 'ɐ', 0, 20, 35, 10, 2),
(45, 'Open central unrounded vowel', 'ä', 0, 20, 40, 10, 2),
(46, 'Close-mid back unrounded vowel', 'ɤ', 0, 30, 20, 10, 2),
(47, 'Close-mid back rounded vowel', 'o', 0, 30, 20, 15, 2),
(48, 'Mid back unrounded vowel', 'ɤ̞', 0, 30, 25, 10, 2),
(49, 'Mid back rounded vowel', 'o̞', 0, 30, 25, 15, 2),
(50, 'Open-mid back unrounded vowel', 'ʌ', 0, 30, 30, 10, 2),
(51, 'Open-mid back rounded vowel', 'ɔ', 0, 30, 30, 15, 2),
(52, 'Open back unrounded vowel', 'ɑ', 0, 30, 40, 10, 2),
(53, 'Open back rounded vowel', 'ɒ', 0, 30, 40, 15, 2),
(54, 'Voiceless bilabial fricative', 'ɸ', 0, 35, 10, 10, 1),
(55, 'Voiced bilabial fricative', 'β', 0, 35, 10, 15, 1),
(56, 'Voiceless labiodental fricative', 'f', 0, 35, 15, 10, 1),
(57, 'Voiced labiodental fricative', 'v', 0, 35, 15, 15, 1),
(58, 'Voiceless dental fricative', 'θ', 0, 35, 30, 10, 1),
(59, 'Voiced dental fricative', 'ð', 0, 35, 30, 15, 1),
(60, 'Voiced alveolar lateral approximant', 'l', 0, 65, 35, 15, 1),
(61, 'Voiced dental lateral approximant', 'l̪', 0, 65, 30, 15, 1),
(62, 'Voiced postalveolar lateral approximant', 'l̠', 0, 65, 40, 15, 1),
(63, 'Voiced alveolar trill', 'r', 0, 50, 35, 15, 1),
(64, 'Voiceless alveolar trill', 'r̥', 0, 50, 35, 10, 1),
(65, 'Voiced alveolar tap', 'ɾ', 0, 45, 35, 15, 1),
(66, 'Voiceless alveolar tap', 'ɾ̥', 0, 45, 35, 10, 1),
(67, 'Voiced alveolar approximant', 'ɹ', 0, 40, 35, 15, 1),
(68, 'Voiced dental approximant', 'ð̞', 0, 40, 30, 15, 1),
(69, 'Voiced postalveolar approximant', 'ɹ̠', 0, 40, 40, 15, 1),
(70, 'Voiced postalveolar trill', 'r̠', 0, 50, 40, 15, 1),
(71, 'Voiced bilabial trill', 'ʙ', 0, 50, 10, 15, 1),
(72, 'Voiceless bilabial trill', 'ʙ̥', 0, 50, 10, 10, 1),
(73, 'Voiced labiodental flap', 'ⱱ', 0, 45, 15, 15, 1),
(74, 'Voiced linguolabial tap', 'ɾ̼', 0, 45, 20, 15, 1),
(75, 'Voiced bilabial flap', 'ⱱ̟', 0, 45, 10, 15, 1),
(76, 'Voiced bilabial approximant', 'β̞', 0, 40, 10, 15, 1),
(77, 'Voiced labiodental approximant', 'ʋ', 0, 40, 15, 15, 1),
(78, 'Voiceless linguolabial fricative', 'θ̼', 0, 35, 20, 10, 1),
(79, 'Voiced linguolabial fricative', 'ð̼', 0, 35, 20, 15, 1),
(80, 'Voiceless velar plosive', 'k', 0, 15, 60, 10, 1),
(81, 'Voiced velar plosive', 'g', 0, 15, 60, 15, 1),
(82, 'Voiceless palatal plosive', 'c', 0, 15, 50, 10, 1),
(83, 'Voiced palatal plosive', 'ɟ', 0, 15, 50, 15, 1),
(86, 'Voiced uvular nasal', 'ɴ', 0, 10, 65, 15, 1),
(87, 'Voiceless velar fricative', 'x', 0, 35, 60, 10, 1),
(88, 'Voiced velar fricative', 'ɣ', 0, 35, 60, 15, 1),
(89, 'Voiced velar lateral approximant', 'ʟ', 0, 65, 60, 15, 1),
(90, 'Voiced palatal lateral approximant', 'ʎ', 0, 65, 50, 15, 1),
(91, 'Voiced retroflex lateral approximant', 'ɭ', 0, 65, 45, 15, 1),
(92, 'Voiceless retroflex fricative', 'ʂ', 0, 30, 45, 10, 1),
(93, 'Voiced retroflex fricative', 'ʐ', 0, 30, 45, 15, 1),
(94, 'Voiceless alveolo-palatal fricative', 'ɕ', 0, 30, 50, 10, 1),
(95, 'Voiced alveolo-palatal fricative', 'ʑ', 0, 30, 50, 15, 1),
(96, 'Voiced palatal fricative', 'ʝ', 0, 35, 50, 15, 1),
(97, 'Voiceless palatal fricative', 'ç', 0, 35, 50, 10, 1),
(98, 'Voiced palatal approximant', 'j', 0, 40, 50, 15, 1),
(99, 'Voiced retroflex approximant', 'ɻ', 0, 40, 45, 15, 1),
(100, 'Voiced velar approximant', 'ɰ', 0, 40, 60, 15, 1),
(101, 'Voiced uvular approximant', 'ʁ̞', 0, 40, 65, 15, 1),
(102, 'Voiced uvular plosive', 'ɢ', 0, 15, 65, 15, 1),
(103, 'Voiceless uvular plosive', 'q', 0, 15, 65, 10, 1),
(104, 'Glottal stop', 'ʔ', 0, 15, 80, 10, 1),
(105, 'Voiceless glottal fricative', 'h', 0, 35, 80, 10, 1),
(106, 'Voiced glottal fricative', 'ɦ', 0, 35, 80, 15, 1),
(107, 'Creaky-voiced glottal approximant', 'ʔ̞', 0, 40, 80, 15, 1),
(108, 'Epiglottal plosive', 'ʡ', 0, 15, 75, 10, 1),
(109, 'Voiced pharyngeal fricative', 'ʕ', 0, 35, 75, 15, 1),
(110, 'Voiceless pharyngeal fricative', 'ħ', 0, 35, 75, 10, 1),
(111, 'Voiced uvular fricative', 'ʁ', 0, 35, 65, 15, 1),
(112, 'Voiceless uvular fricative', 'χ', 0, 35, 65, 10, 1),
(113, 'Voiced velar nasal', 'ŋ', 0, 10, 60, 15, 1),
(114, 'Voiceless velar nasal', 'ŋ̊', 0, 10, 60, 10, 1),
(115, 'Voiced palatal nasal', 'ɲ', 0, 10, 50, 15, 1),
(116, 'Voiceless palatal nasal', 'ɲ̊', 0, 10, 50, 10, 1),
(117, 'Primary stress', 'ˈ', 0, 10, 10, 10, 3),
(118, 'Voiced retroflex flap', 'ɽ', 0, 45, 45, 15, 1),
(119, 'Secondary stress', 'ˌ', 0, 10, 12, 10, 3),
(120, 'Tenuis bilabial click', 'ʘ', 0, 100, 10, 10, 1),
(121, 'Tenuis dental click', 'ǀ', 0, 100, 30, 10, 1),
(122, 'Tenuis alveolar click', 'ǃ', 0, 100, 35, 10, 1),
(123, 'Tenuis palatal click', 'ǂ', 0, 100, 50, 10, 1),
(124, 'Tenuis lateral velar click', 'ǁ', 0, 100, 60, 10, 1),
(125, 'Tenuis lateral uvular click', 'ʖ', 0, 100, 65, 10, 1),
(126, 'Voiced bilabial implosive', 'ɓ', 0, 95, 10, 10, 1),
(127, 'Voiced alveolar implosive', 'ɗ', 0, 95, 35, 10, 1),
(128, 'Voiced palatal implosive', 'ʄ', 0, 95, 50, 15, 1),
(129, 'Voiced velar implosive', 'ɠ', 0, 95, 60, 15, 1),
(130, 'Voiced uvular implosive', 'ʛ', 0, 95, 65, 15, 1),
(131, 'Voiced labial–velar approximant', 'w', 0, 40, 5, 15, 1),
(132, 'Downstep', 'ꜜ', 0, 12, 10, 10, 3),
(133, 'Upstep', 'ꜛ', 0, 12, 12, 10, 3),
(134, 'Aspirated', 'ʰ', 0, 10, 16, 10, 3),
(135, 'Labialized', 'ʷ', 0, 12, 16, 10, 3),
(136, 'Palatalized', 'ʲ', 0, 14, 16, 10, 3),
(137, 'Rhoticity', '˞', 0, 10, 14, 10, 3),
(138, 'Ejective', 'ʼ', 0, 12, 14, 10, 3),
(139, 'Long', 'ː', 0, 14, 10, 10, 3),
(140, 'Voiceless labial–velar approximant', 'ʍ', 0, 40, 5, 10, 1),
(141, 'Space', ' ', 0, 14, 12, 10, 3);

CREATE TABLE `sonsPersonalizados` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `ipa` varchar(8) NOT NULL,
  `id_referente` int(11) NOT NULL COMMENT 'Para nomes traduzidos',
  `posx` int(11) NOT NULL,
  `posy` int(11) NOT NULL,
  `posz` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `x_id_classeSom` int(11) NOT NULL,
  `id_tipoSom` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sons_classes` (
  `id` int(11) NOT NULL,
  `tipo` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=sons, 2=sonsPersonalizados',
  `id_som` int(11) NOT NULL,
  `id_classeSom` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sosail_joes` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_destino` varchar(50) NOT NULL,
  `id_destino` int(11) NOT NULL,
  `data_interacao` datetime NOT NULL DEFAULT current_timestamp(),
  `valor` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `sosail_komentares` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_destino` varchar(50) NOT NULL,
  `id_destino` int(11) NOT NULL,
  `id_respondido` int(11) NOT NULL DEFAULT 0,
  `data_interacao` datetime NOT NULL DEFAULT current_timestamp(),
  `comentario` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `sosail_sgisons` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_seguido` int(11) NOT NULL,
  `data_interacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `soundChanges` (
  `id` int(11) NOT NULL,
  `changes` text NOT NULL,
  `descricao` text NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(50) NOT NULL,
  `instrucoes` text NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `motor` varchar(15) NOT NULL DEFAULT 'sca2',
  `publico` tinyint(4) NOT NULL DEFAULT 0,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `stats` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descricao` text NOT NULL,
  `tipo` varchar(50) NOT NULL COMMENT 'numero, porcentagem, fracao etc',
  `id_usuario` int(11) NOT NULL,
  `id_realidade` int(11) NOT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo_entidade` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `stats_entidades` (
  `id` int(11) NOT NULL,
  `id_entidade` int(11) NOT NULL,
  `id_stat` int(11) NOT NULL,
  `id_momento` int(11) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `id_entidade_relacionada` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `studason_palavrs` (
  `id` int(11) NOT NULL,
  `pids` varchar(150) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `status_aprendido` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `studason_tests` (
  `id` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `link_origem` varchar(250) NOT NULL,
  `link_audio` varchar(250) NOT NULL,
  `texto` text NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `num_palavras` int(11) NOT NULL DEFAULT 0,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `tipo_dest` varchar(10) NOT NULL,
  `id_dest` int(11) NOT NULL,
  `tag` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `teclas` (
  `id` int(11) NOT NULL,
  `id_inventario` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `ordem` int(11) NOT NULL,
  `tecla` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tests_importasons` (
  `id` int(11) NOT NULL,
  `id_texto` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tests_palavrs` (
  `id` int(11) NOT NULL,
  `texto` varchar(150) NOT NULL,
  `id_palavra_kond` int(11) NOT NULL,
  `exemplos` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `time_adjustment_rules` (
  `id` int(11) NOT NULL,
  `id_time_system` int(11) DEFAULT NULL,
  `affected_unit_id` int(11) DEFAULT NULL,
  `condition` varchar(255) DEFAULT NULL,
  `adjustment_value` int(11) DEFAULT NULL,
  `target_unit_id` int(11) DEFAULT NULL,
  `target_subunit_id` int(11) DEFAULT NULL,
  `target_unit_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_unit_properties`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `time_cycles` (
  `id` int(11) NOT NULL,
  `id_time_system` int(11) NOT NULL,
  `id_unidade` int(11) NOT NULL,
  `id_unidade_ref` int(11) NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `time_names` (
  `id` int(11) NOT NULL,
  `id_time_system` int(11) DEFAULT NULL,
  `id_unidade` int(11) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `quantidade_subunidade` decimal(10,2) DEFAULT NULL,
  `posicao` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `time_systems` (
  `id` int(11) NOT NULL,
  `id_realidade` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_padrao` date DEFAULT NULL,
  `padrao` tinyint(4) NOT NULL DEFAULT 0,
  `publico` tinyint(4) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `time_units` (
  `id` int(11) NOT NULL,
  `id_time_system` int(11) NOT NULL,
  `id_realidade` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `duracao` decimal(20,2) NOT NULL,
  `equivalente` enum('minuto','hora','dia','mes','ano','semana','decada','seculo','milenio') DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tiposSom` (
  `id` int(11) NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `titulo` varchar(50) NOT NULL,
  `dimx` int(11) NOT NULL,
  `dimy` int(11) NOT NULL,
  `dimz` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tiposSom` (`id`, `codigo`, `titulo`, `dimx`, `dimy`, `dimz`) VALUES
(1, 'C', 'Consonants', 1, 2, 3),
(2, 'V', 'Vowels', 5, 6, 7),
(3, 'O', 'Suprasegmentals', 9, 10, 11);

CREATE TABLE `tradson_tests` (
  `id_texto` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `traducao` text NOT NULL,
  `explicacao` text NOT NULL,
  `link_audio` varchar(250) NOT NULL,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `traducoes` (
  `id` int(11) NOT NULL,
  `id_idioma` int(11) NOT NULL,
  `base` varchar(50) NOT NULL,
  `traducao` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `id_idioma_nativo` int(11) NOT NULL,
  `data_cadastro` datetime NOT NULL,
  `email` varchar(250) NOT NULL,
  `confirmacao` varchar(250) NOT NULL,
  `publico` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `wordbanks` (
  `id` int(11) NOT NULL,
  `titulo` varchar(50) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_criacao` datetime NOT NULL,
  `data_modificacao` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


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
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_realidade` (`id_realidade`);

ALTER TABLE `entidades_tipos_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_entidade_tipo` (`id_entidade_tipo`),
  ADD KEY `id_stat` (`id_stat`);

ALTER TABLE `escritas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_writing` (`id_idioma`,`padrao`);

ALTER TABLE `etimologias`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`id`);

ALTER TABLE `itens_palavras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_cun` (`id_concordancia`,`id_palavra`),
  ADD KEY `ipx_ipc` (`usar`,`id_palavra`,`id_concordancia`,`id_item`),
  ADD KEY `idx_ipip` (`id_palavra`),
  ADD KEY `idx_ipic` (`id_concordancia`);

ALTER TABLE `listasReferentes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `listas_referentes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `lugares`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `lugares_tipos`
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
  ADD KEY `idx_pd` (`id_forma_dicionario`);

ALTER TABLE `palavrasNativas`
  ADD PRIMARY KEY (`id`),
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

ALTER TABLE `personagens`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `realidades`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `referentes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `descricao` (`descricao`);

ALTER TABLE `regrasOrdens`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `romanizacoes`
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

ALTER TABLE `tradson_tests`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `traducoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trad` (`id_idioma`,`base`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `wordbanks`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `artygs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `artyg_dest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `asons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `autosubstituicoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `blocos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `classesGeneros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `classesSom`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `collabs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `collabs_realidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `concordancias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `drawChars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `entidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `entidades_nomes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `entidades_relacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `entidades_tipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `entidades_tipos_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `escritas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `etimologias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `flexoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `fontes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `formaSilabaComponente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `formasSilaba`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `generos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `glifos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `glosses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

ALTER TABLE `gloss_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `gloss_referentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `grupos_idiomas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `historias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `historias_entidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `historias_tipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `idiomas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `inventarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ipaTitulos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ipaTudo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

ALTER TABLE `itensConcordancias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `itens_flexoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `itens_palavras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `listasReferentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `listas_referentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `lugares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `lugares_tipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `momentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `nivelUsoPalavra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `opcoes_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

ALTER TABLE `palavras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `palavrasNativas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `palavras_origens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `palavras_referentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `palavras_usos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pal_sig_comunidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `personagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `realidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `referentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=226;

ALTER TABLE `regrasOrdens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `romanizacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `significados_idiomas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

ALTER TABLE `sonsPersonalizados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sons_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sosail_joes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sosail_komentares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sosail_sgisons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `soundChanges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `stats_entidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `studason_palavrs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `studason_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `teclas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `tests_importasons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `tests_palavrs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `time_adjustment_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `time_cycles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `time_names`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `time_systems`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `time_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `tiposSom`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `tradson_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `traducoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `wordbanks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `entidades_tipos`
  ADD CONSTRAINT `entidades_tipos_ibfk_1` FOREIGN KEY (`id_realidade`) REFERENCES `realidades` (`id`) ON DELETE CASCADE;

ALTER TABLE `entidades_tipos_stats`
  ADD CONSTRAINT `entidades_tipos_stats_ibfk_1` FOREIGN KEY (`id_entidade_tipo`) REFERENCES `entidades_tipos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entidades_tipos_stats_ibfk_2` FOREIGN KEY (`id_stat`) REFERENCES `stats` (`id`) ON DELETE CASCADE;

ALTER TABLE `time_cycles`
  ADD CONSTRAINT `time_cycles_ibfk_1` FOREIGN KEY (`id_time_system`) REFERENCES `time_systems` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `time_cycles_ibfk_2` FOREIGN KEY (`id_unidade`) REFERENCES `time_units` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `time_cycles_ibfk_3` FOREIGN KEY (`id_unidade_ref`) REFERENCES `time_units` (`id`) ON DELETE CASCADE;

ALTER TABLE `time_names`
  ADD CONSTRAINT `time_names_ibfk_1` FOREIGN KEY (`id_time_system`) REFERENCES `time_systems` (`id`),
  ADD CONSTRAINT `time_names_ibfk_2` FOREIGN KEY (`id_unidade`) REFERENCES `time_units` (`id`);

ALTER TABLE `time_systems`
  ADD CONSTRAINT `time_systems_ibfk_1` FOREIGN KEY (`id_realidade`) REFERENCES `realidades` (`id`) ON DELETE CASCADE;

ALTER TABLE `time_units`
  ADD CONSTRAINT `time_units_ibfk_1` FOREIGN KEY (`id_time_system`) REFERENCES `time_systems` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `time_units_ibfk_2` FOREIGN KEY (`id_realidade`) REFERENCES `realidades` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
