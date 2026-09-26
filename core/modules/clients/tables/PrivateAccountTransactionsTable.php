<?php

/**
 * CREATE TABLE `{DB_DATABASE_NAME}`.`{DB_TABLE_PREFIX}private_account_transactions` (
 * CREATE TABLE `{DB_DATABASE_NAME}`.`{DB_TABLE_PREFIX}private_account_transactions`
 * (
 * `id`             INT(10) UNSIGNED                                              NOT NULL AUTO_INCREMENT,
 * `client_id`      INT(10) UNSIGNED                                              NOT NULL COMMENT 'ID клиента, которому принадлежит счёт',
 * `type_direction` ENUM ('in','out')                                             NOT NULL DEFAULT 'in' COMMENT 'Направление операции: "in" — зачисление средств, "out" — списание',
 * `type_code`      VARCHAR(255)                                                  NOT NULL DEFAULT 'unknown' COMMENT 'Код типа операции (например: coupon, ticket, reservation и т.д.)',
 * `amount`         DECIMAL(12, 2)                                                NOT NULL DEFAULT 0.00 COMMENT 'Сумма транзакции в валюте счёта (с точностью до 2 знаков)',
 * `created_at`     DATETIME                                                      NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время создания транзакции',
 * `created_user`   VARCHAR(32)                                                   NOT NULL DEFAULT 'system' COMMENT 'Имя пользователя или система, инициировавшая операцию (например: admin, api, system). Формат {user_type}|{user_id}|{system|api}',
 * `related_data`   TEXT                                                                   DEFAULT NULL COMMENT 'Связанные данные в формате JSON',
 * `related_id`     INT(10) UNSIGNED                                                       DEFAULT NULL COMMENT 'Ссылка на связанную сущность (например: ID заказа, платежа, бонуса)',
 * `status`         ENUM ('succeeded', 'cancelled', 'pending', 'failed', 'reversed') NOT NULL DEFAULT 'pending' COMMENT 'Статус транзакции',
 * `external_id`    VARCHAR(64)                                                            DEFAULT NULL COMMENT 'Уникальный идентификатор транзакции во внешней системе или для идемпотентности',
 * PRIMARY KEY (`id`),
 * KEY `idx_client_id` (`client_id`) COMMENT 'Индекс для поиска по клиенту',
 * KEY `idx_type_direction` (`type_direction`) COMMENT 'Индекс по направлению операции',
 * KEY `idx_type_code` (`type_code`) COMMENT 'Индекс по типу операции',
 * KEY `idx_related_id` (`related_id`) COMMENT 'Индекс по связанной сущности',
 * KEY `idx_status` (`status`) COMMENT 'Индекс по статусу транзакции',
 * UNIQUE KEY `uniq_external_id` (`external_id`) COMMENT 'Уникальность внешнего ID для предотвращения дублей'
 * ) ENGINE = InnoDB
 * DEFAULT CHARSET = utf8mb4
 * COLLATE = utf8mb4_unicode_ci COMMENT ='Таблица транзакций персональных счетов клиентов';
 *
 * клиентов';
 *
 */

namespace AC\core\modules\clients\tables;

use AC\core\system\model\BaseModel;

class PrivateAccountTransactionsTable extends BaseModel
{
  public ?int     $id;
  public int     $client_id;
  public string  $type_direction; // in | out
  public string  $type_code;      // строковый код типа операции
  public float   $amount;
  public string  $created_at;
  public string  $created_user;   // {admin|client}|{id}
  public ?string $related_data;                   // json
  public ?int    $related_id;                // универсальная связь
  public ?string  $status;           // succeeded | cancelled | pending | failed | reversed
  public ?string $external_id;
  
  protected $tableName = 'private_account_transactions';
  
}