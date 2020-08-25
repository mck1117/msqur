--
-- Table structure for table `msqur_engines`
--

CREATE TABLE IF NOT EXISTS `msqur_engines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` tinytext DEFAULT NULL,
  `make` tinytext DEFAULT NULL,
  `code` tinytext DEFAULT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `displacement` decimal(4,2) NOT NULL,
  `numCylinders` tinyint(2) DEFAULT NULL,
  `compression` decimal(4,2) NOT NULL,
  `induction` int(11) NOT NULL,
  `injectorSize` int(11) DEFAULT NULL,
  `twoStroke` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `injType` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `nInjectors` tinyint(4) DEFAULT NULL,
  `engineType` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `msqur_files`
--

CREATE TABLE IF NOT EXISTS `msqur_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL DEFAULT 0,
  `crc` bigint(20) DEFAULT NULL,
  `data` longblob DEFAULT NULL,
  `html` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `msqur_logs`
--

CREATE TABLE IF NOT EXISTS `msqur_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `tune_id` int(11) DEFAULT 0,
  `info` mediumtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` blob DEFAULT NULL,
  `views` int(10) NOT NULL DEFAULT 0,
  `writeDate` datetime DEFAULT NULL,
  `uploadDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `msqur_metadata`
--

CREATE TABLE IF NOT EXISTS `msqur_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file` int(11) NOT NULL,
  `engine` int(11) DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `fileFormat` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `signature` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `firmware` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `writeDate` datetime DEFAULT NULL,
  `uploadDate` datetime DEFAULT NULL,
  `tuneComment` text DEFAULT NULL,
  `reingest` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `msqur_log_notes`
--

CREATE TABLE IF NOT EXISTS `msqur_log_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log_id` int(11) DEFAULT NULL,
  `time_start` FLOAT DEFAULT NULL,
  `time_end` FLOAT DEFAULT NULL,
  `tune_crc` int(11) DEFAULT NULL,
  `comment` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;
