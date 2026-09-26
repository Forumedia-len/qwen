CREATE TABLE `{DB_DATABASE_NAME}`.`{DB_TABLE_PREFIX}private_account_transactions`
# CREATE TABLE `at_base_active_court`.`at_base_active_court_private_account_transactions`
(
  `id`             INT(10) UNSIGNED                                              NOT NULL AUTO_INCREMENT,
  `client_id`      INT(10) UNSIGNED                                              NOT NULL COMMENT 'ID клиента, которому принадлежит счёт',
  `type_direction` ENUM ('in','out')                                             NOT NULL DEFAULT 'in' COMMENT 'Направление операции: "in" — зачисление средств, "out" — списание',
  `type_code`      VARCHAR(255)                                                  NOT NULL DEFAULT 'unknown' COMMENT 'Код типа операции (например: coupon, ticket, reservation и т.д.)',
  `amount`         DECIMAL(12, 2)                                                NOT NULL DEFAULT 0.00 COMMENT 'Сумма транзакции в валюте счёта (с точностью до 2 знаков)',
  `created_at`     DATETIME                                                      NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время создания транзакции',
  `created_user`   VARCHAR(32)                                                   NOT NULL DEFAULT 'system' COMMENT 'Имя пользователя или система, инициировавшая операцию (например: admin, api, system). Формат {user_type}|{user_id}|{system|api|other}',
  `related_data`   TEXT                                                                   DEFAULT NULL COMMENT 'Связанные данные в формате JSON',
  `related_id`     INT(10) UNSIGNED                                                       DEFAULT NULL COMMENT 'Ссылка на связанную сущность (например: ID заказа, платежа, бонуса)',
  `status`         ENUM ('succeeded', 'cancelled', 'pending', 'failed', 'reversed') NOT NULL DEFAULT 'pending' COMMENT 'Статус транзакции',
  `external_id`    VARCHAR(64)                                                            DEFAULT NULL COMMENT 'Уникальный идентификатор транзакции во внешней системе или для идемпотентности',
  PRIMARY KEY (`id`),
  KEY `idx_client_id` (`client_id`) COMMENT 'Индекс для поиска по клиенту',
  KEY `idx_type_direction` (`type_direction`) COMMENT 'Индекс по направлению операции',
  KEY `idx_type_code` (`type_code`) COMMENT 'Индекс по типу операции',
  KEY `idx_related_id` (`related_id`) COMMENT 'Индекс по связанной сущности',
  KEY `idx_status` (`status`) COMMENT 'Индекс по статусу транзакции',
  UNIQUE KEY `uniq_external_id` (`external_id`) COMMENT 'Уникальность внешнего ID для предотвращения дублей'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT ='Таблица транзакций персональных счетов клиентов';

/**
    Статусы транзакций:
      succeeded - Успешно завершена
        * Транзакция успешно обработана и учтена в балансе
        * Основной статус для завершённых операций
        * Примеры:
          - Успешное зачисление платежа
          - Корректное списание средств за заказ

      pending - В обработке/ожидании
        * Транзакция ожидает подтверждения или обработки
        * Средства могут быть временно зарезервированы
        * Примеры:
          - Ожидание подтверждения платежа от банка
          - Резервирование средств при создании заказа

      cancelled - Отменена до завершения
        * Транзакция отменена до её фактического выполнения
        * Не влияет на итоговый баланс
        * Примеры:
          - Пользователь отменил операцию до её завершения
          - Истекло время ожидания подтверждения

      failed - Ошибка выполнения
        * Транзакция не выполнена из-за ошибки
        * Причины могут быть технические или бизнес-логики
        * Примеры:
          - Недостаточно средств на счету
          - Ошибка подключения к платёжному шлюзу
          - Не прошла валидация данных

      reversed - Отменена после выполнения (возврат)
        * Транзакция была успешной, но затем отменена
        * Создаётся компенсирующая транзакция
        * Примеры:
          - Возврат средств за отменённый заказ
          - Отмена ошибочного зачисления
        * Важно: исходная транзакция остаётся в истории с этим статусом
*/