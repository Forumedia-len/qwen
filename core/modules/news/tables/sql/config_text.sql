# CREATE TABLE `at_base_active_court`.`at_base_active_court_config_text`
CREATE TABLE `{DB_DATABASE_NAME}`.`{DB_TABLE_PREFIX}config_text`
(
  `alias` varchar(50) NOT NULL DEFAULT '',
  `content` mediumtext DEFAULT NULL,
  `language` varchar(3) NOT NULL DEFAULT 'de',
  PRIMARY KEY (`alias`, `language`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;