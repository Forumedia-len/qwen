# CREATE TABLE `at_base_active_court_reservations_tmp_paypal`
CREATE TABLE `{DB_DATABASE_NAME}`.`{DB_TABLE_PREFIX}reservations_tmp_paypal`
(
  `reservation_id`      int(11) unsigned           NOT NULL AUTO_INCREMENT,
  `area_id`             int(11) unsigned           NOT NULL  DEFAULT 0,
  `client_id`           int(11) unsigned                     DEFAULT NULL,
  `type_reservation`    int(3) unsigned            NOT NULL  DEFAULT 1 COMMENT '1 - simple, 2 - double',
  `payment_state`       enum ('1','0')             NOT NULL  DEFAULT '1',
  `status`              enum ('0','1')             NOT NULL  DEFAULT '1' COMMENT 'The order status is 1-complete or 0-incomplete for outdoor courts',
  `main_reservation_id` int(10) unsigned                     DEFAULT NULL,
  `main_client_id`      int(10) unsigned                     DEFAULT NULL,
  `light_state`         enum ('0','1')             NOT NULL  DEFAULT '0',
  `heating_state`       enum ('0','1')             NOT NULL  DEFAULT '0',
  `net_state`           enum ('0','1')             NOT NULL  DEFAULT '0',
  `stock_id`            int(10) unsigned                     DEFAULT NULL,
  `sprice_id`           int(10)                              DEFAULT NULL,
  `encash`              enum ('0','1','2','3','4') NOT NULL  DEFAULT '0' COMMENT '0-BAR, 1-RE счет, 2 - GH, 3-PP, 4-EC',
  `start`               datetime                   NOT NULL  DEFAULT '0000-00-00 00:00:00',
  `finish`              datetime                   NOT NULL  DEFAULT '0000-00-00 00:00:00',
  `ordered`             datetime                   NOT NULL  DEFAULT '0000-00-00 00:00:00',
  `price`               double                     NOT NULL  DEFAULT 0,
  `light_price`         double(10, 2)                        DEFAULT NULL,
  `heating_price`       double(10, 2)                        DEFAULT NULL,
  `net_price`           double(10, 2)                        DEFAULT 0.00,
  `customer`            int(1)                               DEFAULT NULL,
  `client_name`         varchar(255)                         DEFAULT NULL,
  `client_surname`      varchar(255)                         DEFAULT NULL,
  `memo`                varchar(255)               NOT NULL  DEFAULT '',
  `paypal_status`       varchar(20)                          DEFAULT NULL,
  `door_code`           varchar(20)                          DEFAULT NULL,
  `street_friends`      text                                 DEFAULT NULL,
  `pay_state`           enum ('OK','FALSE','CANCEL','ERROR') DEFAULT NULL,
  `real_reservation_id` int(11)                              DEFAULT NULL,
  `pay_one_type`        varchar(10)                          DEFAULT NULL,
  `reference`           varchar(128)                         DEFAULT NULL,
  PRIMARY KEY (`reservation_id`),
  KEY `idx_reservations_client_main_start_finish` (`client_id`, `main_reservation_id`, `start`, `finish`)
) ENGINE = MyISAM
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

