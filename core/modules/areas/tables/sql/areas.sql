CREATE TABLE `{DB_DATABASE_NAME}`.`{DB_TABLE_PREFIX}areas`
(
  `area_id`      int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type_id`      int(10) unsigned NOT NULL DEFAULT 1,
  `sport_id`     int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'Type of sports area to play',
  `period`       int(10) unsigned NOT NULL DEFAULT 60,
  `title`        varchar(255) NOT NULL DEFAULT '',
  `short_title`  varchar(250)          DEFAULT NULL,
  `comment`      mediumtext            DEFAULT NULL,
  `page`         int(3) DEFAULT NULL,
  `sort`         int(10) unsigned NOT NULL DEFAULT 0,
  `workdays` set('0','1','2','3','4','5','6') NOT NULL DEFAULT '0,1,2,3,4,5,6',
  `light_price` double(10,2) NOT NULL DEFAULT 0.00,
  `light_on` set('0','1') NOT NULL DEFAULT '0',
  `heating_price` double(10,2) DEFAULT 0.00,
  `heating_on` set('0','1') DEFAULT '0',
  `net_price` double(10,2) DEFAULT 0.00,
  `net_on` set('0','1') DEFAULT '0',
  `offset_start` int(4) NOT NULL DEFAULT 0,
  `active`       tinyint(1) DEFAULT 1,
  PRIMARY KEY (`area_id`),
  KEY            `area_id` (`area_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

