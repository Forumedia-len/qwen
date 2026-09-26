# CREATE TABLE `at_base_active_court`.`at_base_active_court_trigger_conditions`
CREATE TABLE `{DB_DATABASE_NAME}`.`{DB_TABLE_PREFIX}trigger_conditions`
(
  `id`         INT(11)      NOT NULL AUTO_INCREMENT COMMENT 'Уникальный идентификатор записи',
  `type_block` VARCHAR(100) NOT NULL COMMENT 'Источник или тип данных (например stock и т.д.)',
  `entry_id`   INT(11) DEFAULT NULL COMMENT 'Группировка stock/опций для одной брони',
  `name`       VARCHAR(100) NOT NULL COMMENT 'Ключ параметра (например stock_id, rate и т.д.)',
  `value`      TEXT    DEFAULT NULL COMMENT 'Значение параметра',
  PRIMARY KEY (`id`),
  KEY `idx_type_entry` (`type_block`, `entry_id`),
  KEY `idx_type_entry_name` (`type_block`, `entry_id`, `name`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  COMMENT ='Таблица для хранения условий для сработки разных триггеров';