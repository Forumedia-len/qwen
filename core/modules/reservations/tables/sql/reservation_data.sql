# CREATE TABLE `at_base_active_court`.`at_base_active_court_reservation_data`
CREATE TABLE `{DB_DATABASE_NAME}`.`{DB_TABLE_PREFIX}reservation_data`
(
  `id`                 INT(11)      NOT NULL AUTO_INCREMENT COMMENT 'Уникальный идентификатор записи',
  `reservation_id`     INT(11) DEFAULT NULL COMMENT 'ID основной брони (связь с {prefix}reservations)',
  `tmp_reservation_id` INT(11) DEFAULT NULL COMMENT 'ID временной брони PayPal (связь с {prefix}reservations_tmp_paypal)',
  `type_block`          VARCHAR(100) NOT NULL COMMENT 'Источник или тип данных (например stock и т.д.)',
  `entry_id`           INT(11) DEFAULT NULL COMMENT 'Группировка stock/опций для одной брони',
  `name`               VARCHAR(100) NOT NULL COMMENT 'Ключ параметра (например stock_id, rate и т.д.)',
  `value`              TEXT    DEFAULT NULL COMMENT 'Значение параметра',
  PRIMARY KEY (`id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `tmp_reservation_id` (`tmp_reservation_id`),
  KEY `idx_type_entry` (`type_block`, `entry_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  COMMENT ='Таблица для хранения дополнительных параметров бронирования также для временных броней PayPal';

