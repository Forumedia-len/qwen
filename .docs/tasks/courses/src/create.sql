-- ================================================================
-- 🧩 Расширение базы данных "at_base_active_court" — модуль Kurse/Events
-- Версия: 1.20
-- Цель: вариант B из ADR-003 — занятия как самостоятельный источник занятости, без обычных броней площадки.
-- Примечание: 14 новых таблиц, право в существующей users и одна настройка config; рабочая БД не изменяется автоматически.
-- FK к базовым areas, users, config_club_state и clients рассчитаны на InnoDB эталонной БД; движки проверяются до применения.
-- course_event_reservations исключена из проекта; технические периоды хранятся в course_event_service_slots.
-- Это схема создания, не миграция установленного варианта A; перенос существующих данных требует отдельной миграции.
-- Оплату клиентов в обоих сценариях принимает площадка; различаются только взаиморасчёты с тренером (ADR-004).
-- Версия 1.19: условия тренеров, возврат всего курса/аренды, поздняя запись, бесплатные новые даты, согласование и связь со счётом.
-- Версия 1.20: фото и отдельное право персональных цен тренера, скрытие курса, Guthaben в этапе 1, месячные счета в этапе 2.
-- Коды, оборудование и вся почтовая обработка — этап 2; структуры и контракты предусмотрены заранее.
-- ================================================================

-- ---------------------------------------------------------------
-- 0. Отдельное право управления курсами в существующей users
-- Флаг не заменяет users.rights и не выдаётся автоматически по уровню администратора.
-- 1 — управление всеми курсами и назначение тренеров; 0 — такого административного доступа нет.
-- Выдача права требует существующих полномочий управления пользователями; само право не позволяет выдать его себе или другим.
-- Проект расширения таблицы: при внедрении нужна миграция с проверкой существования поля/ограничения.
-- ---------------------------------------------------------------
ALTER TABLE `at_base_active_court_users`
  ADD COLUMN `can_manage_courses` tinyint(1) UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Отдельное право полного управления курсами и назначения тренеров',
  ADD CONSTRAINT `chk_users_can_manage_courses` CHECK (`can_manage_courses` IN (0, 1));

-- ---------------------------------------------------------------
-- 1. at_base_active_court_trainers
-- Профиль тренера: один профиль на клиента
-- Администратор выдаёт клиенту статус тренера; can_manage_courses отдельно разрешает управление всеми курсами.
-- Связь client_id с clients проверяется приложением: на площадках возможен MyISAM.
-- При назначении на курс сервер должен проверить существование клиента и is_active = 1.
-- Доступ тренера проверяется по course_groups.trainer_id; административный доступ — по users.can_manage_courses.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_trainers` (
  `trainer_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID профиля тренера',
  `client_id` int(10) UNSIGNED NOT NULL COMMENT 'Клиент, которому принадлежит профиль тренера',
  `is_active` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 — тренер доступен для назначения на курс',
  `can_manage_personal_prices` tinyint(1) UNSIGNED NOT NULL DEFAULT 1
    COMMENT 'Право тренера на персональные тарифы; не ограничивает групповые цены и не отменяет существующие тарифы',
  `photo_path` varchar(512) DEFAULT NULL COMMENT 'Одно фото публичного профиля; NULL — нейтральный заполнитель',
  `description` mediumtext DEFAULT NULL COMMENT 'Описание тренера',
  `qualification` text DEFAULT NULL COMMENT 'Квалификация тренера',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания профиля',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата изменения профиля',
  PRIMARY KEY (`trainer_id`),
  UNIQUE KEY `uk_trainers_client_id` (`client_id`),
  CONSTRAINT `chk_trainers_active` CHECK (`is_active` IN (0, 1)),
  CONSTRAINT `chk_trainers_personal_prices` CHECK (`can_manage_personal_prices` IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Профили тренеров';

-- ---------------------------------------------------------------
-- 2. at_base_active_court_course_groups
-- Основная сущность: курс с собственными занятиями
-- rental: внешний тренер арендует площадку за фиксированную сумму; площадка принимает оплату и рассчитывается с тренером.
-- hired: наёмный тренер без аренды; площадка принимает оплату и рассчитывается с ним по условиям найма.
-- Курс создаёт и ведёт основной тренер либо пользователь users с can_manage_courses = 1.
-- Пользователь с правом выбирает основной профиль тренера; авторство сохраняет фактического инициатора.
-- Для rental согласование суммы аренды определяется config: courses / rental_approval_required (1 или 0).
-- Для hired аренды нет; публикация, расписание и цены участия согласования с администратором не требуют.
-- До публикации rental сервис требует trainer_price и выбранный администратором rental_refund_mode.
-- NULL trainer_price допустим в черновике, в том числе пока предложенная начальная сумма ожидает согласования.
-- Изменение предложения увеличивает rental_terms_revision; утверждение проверяет прочитанную ревизию под блокировкой.
-- При обязательном согласовании новая сумма хранится отдельно, не заменяя действующую trainer_price до подтверждения.
-- rental_approved_* подтверждают действующую сумму; более новая ожидающая заявка не отменяет старое подтверждение.
-- Смену действующей суммы/условий и историю предложений фиксирует сервис; CHECK не проверяет право пользователя или config.
-- Возврат аренды: none — без уменьшения, proportional — trainer_price * отменяемая длительность / длительность по договору.
-- Длительности, состав договора, использованный срок тренера и результат фиксируются в cancellation_parameters при отмене.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_course_groups` (
  `course_group_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Уникальный ID группы курса',
  `title` varchar(255) NOT NULL COMMENT 'Название курса (Titel)',
  `description` mediumtext DEFAULT NULL COMMENT 'Описание курса (Beschreibung)',
  `image_path` varchar(512) DEFAULT NULL COMMENT 'Путь к изображению курса (одно изображение)',
  `trainer_id` int(10) UNSIGNED NOT NULL COMMENT 'ID профиля тренера (ссылка на trainers.trainer_id)',
  `area_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Площадка по умолчанию; NULL — внешнее место, адрес хранится у занятий',
  `min_participants` int(4) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Минимальное кол-во участников',
  `max_participants` int(4) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'Максимальное кол-во участников',
  `is_sessions_bookable_separately` enum('0','1') NOT NULL DEFAULT '0' COMMENT '1 — занятия можно бронировать по отдельности',
  `trainer_settlement_mode` enum('rental','hired') NOT NULL COMMENT 'Модель взаиморасчётов: аренда или найм; оплату клиентов всегда принимает площадка',
  `trainer_price` decimal(10,2) DEFAULT NULL COMMENT 'Действующая аренда всего курса; NULL до определения суммы или для hired',
  `rental_refund_mode` enum('none','proportional') DEFAULT NULL
    COMMENT 'Выбор администратора: без возврата или по длительности; NULL — не выбран/нет аренды',
  `rental_terms_revision` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Версия условий и предложения аренды для защиты от устаревшего подтверждения',
  `rental_proposed_price` decimal(10,2) DEFAULT NULL COMMENT 'Предложенная сумма; действующая аренда сохраняется до подтверждения',
  `rental_proposed_by_trainer_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Автор предложения из trainers; XOR с пользователем',
  `rental_proposed_by_user_id` int(11) DEFAULT NULL COMMENT 'Автор предложения из users; XOR с тренером',
  `rental_proposed_at` datetime DEFAULT NULL,
  `rental_approved_price` decimal(10,2) DEFAULT NULL COMMENT 'Подтверждённая сумма; при наличии должна совпадать с trainer_price',
  `rental_approved_revision` int(10) UNSIGNED DEFAULT NULL COMMENT 'Ревизия подтверждения действующей суммы, не выше текущей',
  `rental_approved_by_user_id` int(11) DEFAULT NULL COMMENT 'Подтвердивший пользователь; can_manage_courses проверяет сервис',
  `rental_approved_at` datetime DEFAULT NULL,
  `cancel_days_limit` int(3) UNSIGNED NOT NULL DEFAULT 3 COMMENT 'Сколько дней до начала можно отменить участие (участником)',
  `reminder_minutes_before` int(10) UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'За сколько минут до занятия отправлять напоминание; 0 — выключено; настраивает управляющий курсом',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания курса',
  `created_by_trainer_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Тренер-создатель; NULL при создании пользователем админки',
  `created_by_user_id` int(11) DEFAULT NULL COMMENT 'Пользователь-создатель с can_manage_courses; NULL при создании тренером',
  `status` enum('active','cancelled','archived') NOT NULL DEFAULT 'active' COMMENT 'Статус курса',
  `is_publicly_listed` tinyint(1) UNSIGNED NOT NULL DEFAULT 1
    COMMENT 'Видимость в публичных списках; скрытие не отменяет курс, участие или занятость',
  `cancelled_at` datetime DEFAULT NULL COMMENT 'Время отмены курса',
  `cancellation_parameters` json DEFAULT NULL COMMENT 'Снимок отмены: инициатор, причина, исходные условия; не журнал платежей',
  PRIMARY KEY (`course_group_id`),
  KEY `idx_trainer_id` (`trainer_id`),
  KEY `idx_cg_public_listing` (`status`, `is_publicly_listed`),
  KEY `idx_area_id` (`area_id`),
  KEY `idx_created_by_trainer_id` (`created_by_trainer_id`),
  KEY `idx_created_by_user_id` (`created_by_user_id`),
  KEY `idx_cg_rental_proposed_trainer` (`rental_proposed_by_trainer_id`),
  KEY `idx_cg_rental_proposed_user` (`rental_proposed_by_user_id`),
  KEY `idx_cg_rental_approved_user` (`rental_approved_by_user_id`),
  CONSTRAINT `chk_cg_public_listing` CHECK (`is_publicly_listed` IN (0, 1)),
  CONSTRAINT `fk_cg_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `at_base_active_court_trainers` (`trainer_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cg_area` FOREIGN KEY (`area_id`) REFERENCES `at_base_active_court_areas` (`area_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cg_created_by_trainer` FOREIGN KEY (`created_by_trainer_id`)
    REFERENCES `at_base_active_court_trainers` (`trainer_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cg_created_by_user` FOREIGN KEY (`created_by_user_id`)
    REFERENCES `at_base_active_court_users` (`user_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cg_rental_proposed_trainer` FOREIGN KEY (`rental_proposed_by_trainer_id`)
    REFERENCES `at_base_active_court_trainers` (`trainer_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cg_rental_proposed_user` FOREIGN KEY (`rental_proposed_by_user_id`)
    REFERENCES `at_base_active_court_users` (`user_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cg_rental_approved_user` FOREIGN KEY (`rental_approved_by_user_id`)
    REFERENCES `at_base_active_court_users` (`user_id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_cg_author` CHECK (
    (`created_by_trainer_id` IS NOT NULL AND `created_by_user_id` IS NULL)
    OR (`created_by_trainer_id` IS NULL AND `created_by_user_id` IS NOT NULL)
  ),
  CONSTRAINT `chk_cg_capacity` CHECK (`min_participants` > 0 AND `max_participants` >= `min_participants`),
  CONSTRAINT `chk_cg_separate_booking` CHECK (`is_sessions_bookable_separately` IN ('0', '1')),
  CONSTRAINT `chk_cg_settlement_terms` CHECK (
    (`trainer_settlement_mode` = 'rental' AND (`trainer_price` IS NULL OR `trainer_price` >= 0))
    OR (`trainer_settlement_mode` = 'hired' AND `trainer_price` IS NULL AND `rental_refund_mode` IS NULL
      AND `rental_proposed_price` IS NULL AND `rental_approved_price` IS NULL)
  ),
  CONSTRAINT `chk_cg_rental_revision` CHECK (`rental_terms_revision` > 0),
  CONSTRAINT `chk_cg_rental_proposal` CHECK (
    (`rental_proposed_price` IS NULL AND `rental_proposed_by_trainer_id` IS NULL
      AND `rental_proposed_by_user_id` IS NULL AND `rental_proposed_at` IS NULL)
    OR (`rental_proposed_price` IS NOT NULL AND `rental_proposed_price` >= 0 AND `rental_proposed_at` IS NOT NULL
      AND ((`rental_proposed_by_trainer_id` IS NOT NULL AND `rental_proposed_by_user_id` IS NULL)
        OR (`rental_proposed_by_trainer_id` IS NULL AND `rental_proposed_by_user_id` IS NOT NULL)))
  ),
  CONSTRAINT `chk_cg_rental_approval` CHECK (
    (`rental_approved_price` IS NULL AND `rental_approved_revision` IS NULL
      AND `rental_approved_by_user_id` IS NULL AND `rental_approved_at` IS NULL)
    OR (`rental_approved_price` IS NOT NULL AND `trainer_price` IS NOT NULL AND `rental_approved_price` = `trainer_price`
      AND `rental_approved_revision` IS NOT NULL AND `rental_approved_revision` > 0
      AND `rental_approved_revision` <= `rental_terms_revision`
      AND `rental_approved_by_user_id` IS NOT NULL AND `rental_approved_at` IS NOT NULL)
  ),
  CONSTRAINT `chk_cg_cancellation_object` CHECK (
    `cancellation_parameters` IS NULL OR JSON_TYPE(`cancellation_parameters`) = 'OBJECT'
  ),
  CONSTRAINT `chk_cg_cancelled` CHECK (
    `status` <> 'cancelled' OR (`cancelled_at` IS NOT NULL AND `cancellation_parameters` IS NOT NULL)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Группы курсов (Kurse) — основная сущность';

-- ---------------------------------------------------------------
-- 3. at_base_active_court_course_prices
-- Цены: одна строка на курс и тип клиента из config_club_state
-- Тип клиента определяется по clients.club_state = config_club_state.id.
-- В форме настройки используются активные типы; ID и названия типов не фиксируются в коде.
-- Цены участия независимы от стоимости обычных резервирований и модели взаиморасчётов с тренером.
-- Тарифы меняет основной тренер курса либо пользователь users с can_manage_courses = 1.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_course_prices` (
  `course_group_id` int(11) NOT NULL COMMENT 'Ссылка на курс',
  `club_state_id` int(11) NOT NULL COMMENT 'Тип клиента: ссылка на config_club_state.id',
  `price_full` decimal(10,2) DEFAULT NULL COMMENT 'Цена полного курса; NULL — недоступен, 0.00 — бесплатно',
  `price_single` decimal(10,2) DEFAULT NULL COMMENT 'Цена одного занятия; NULL — недоступно, 0.00 — бесплатно',
  `refund_single_from_full` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Возврат за одно занятие из полного курса: 0 — нет, больше 0 — фиксированная сумма',
  `refund_full` decimal(10,2) NOT NULL DEFAULT 0.00
    COMMENT 'База возврата всего курса: умножается на долю неиспользованных занятий; 0 — нет возврата',
  PRIMARY KEY (`course_group_id`, `club_state_id`),
  KEY `idx_cp_club_state_id` (`club_state_id`),
  CONSTRAINT `fk_cp_course_group` FOREIGN KEY (`course_group_id`) REFERENCES `at_base_active_court_course_groups` (`course_group_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_club_state` FOREIGN KEY (`club_state_id`) REFERENCES `at_base_active_court_config_club_state` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_cp_prices` CHECK (
    (`price_full` IS NULL OR `price_full` >= 0) AND (`price_single` IS NULL OR `price_single` >= 0)
    AND `refund_single_from_full` >= 0 AND `refund_full` >= 0
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Цены на курсы и отдельные занятия';

-- ---------------------------------------------------------------
-- 4. at_base_active_court_course_events
-- Занятие хранит общий интервал, тренера, место, вместимость и состояние.
-- Тренер, площадка и вместимость копируются из курса; внешний адрес задаётся у занятия.
-- Вид места определяется area_id: значение — площадка, NULL — обязательный внешний адрес; отдельный флаг не хранится.
-- Опубликованное занятие само задаёт занятость площадки; строки reservations/reservations_tmp_paypal не создаются.
-- Технические периоды course_event_service_slots наследуют место и время занятия; перенос сохраняет ID занятия.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_course_events` (
  `course_event_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID занятия',
  `course_group_id` int(11) NOT NULL COMMENT 'Ссылка на курс',
  `start_at` datetime NOT NULL COMMENT 'Начало занятия',
  `finish_at` datetime NOT NULL COMMENT 'Окончание занятия',
  `trainer_id` int(10) UNSIGNED NOT NULL COMMENT 'Фактический тренер занятия',
  `area_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Площадка; NULL — внешнее место с обязательным адресом',
  `external_location_text` varchar(512) DEFAULT NULL COMMENT 'Адрес занятия вне площадок',
  `max_participants` int(4) UNSIGNED NOT NULL COMMENT 'Фактическая вместимость занятия',
  `status` enum('draft','scheduled','cancelled','completed') NOT NULL DEFAULT 'draft' COMMENT 'Состояние занятия',
  `revision` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Версия расписания для проверки конкурентных изменений',
  `cancelled_at` datetime DEFAULT NULL COMMENT 'Дата отмены',
  `cancellation_reason` varchar(512) DEFAULT NULL COMMENT 'Причина отмены',
  `cancellation_parameters` json DEFAULT NULL
    COMMENT 'Инициатор, условия тренера и их источник, расчёт аренды по длительности, снимок занятия с техническими периодами',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`course_event_id`),
  UNIQUE KEY `uk_ce_event_course` (`course_event_id`, `course_group_id`),
  KEY `idx_ce_course_start` (`course_group_id`, `start_at`),
  KEY `idx_ce_status_start` (`status`, `start_at`),
  KEY `idx_ce_area_status_start` (`area_id`, `status`, `start_at`),
  KEY `idx_ce_trainer_status_start` (`trainer_id`, `status`, `start_at`),
  CONSTRAINT `fk_ce_course` FOREIGN KEY (`course_group_id`) REFERENCES `at_base_active_court_course_groups` (`course_group_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ce_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `at_base_active_court_trainers` (`trainer_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ce_area` FOREIGN KEY (`area_id`) REFERENCES `at_base_active_court_areas` (`area_id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_ce_interval` CHECK (`finish_at` > `start_at`),
  CONSTRAINT `chk_ce_capacity` CHECK (`max_participants` > 0),
  CONSTRAINT `chk_ce_cancellation_object` CHECK (
    `cancellation_parameters` IS NULL OR JSON_TYPE(`cancellation_parameters`) = 'OBJECT'
  ),
  CONSTRAINT `chk_ce_cancelled` CHECK (
    `status` <> 'cancelled' OR (`cancelled_at` IS NOT NULL AND `cancellation_parameters` IS NOT NULL)
  ),
  CONSTRAINT `chk_ce_location` CHECK (
    (`area_id` IS NOT NULL AND `external_location_text` IS NULL)
    OR (`area_id` IS NULL
      AND `external_location_text` IS NOT NULL AND CHAR_LENGTH(TRIM(`external_location_text`)) > 0)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Занятия курсов';

-- ---------------------------------------------------------------
-- 5. at_base_active_court_course_event_service_slots
-- Коды доступа и настройки оборудования по периодам; источник занятости — родительское course_events.
-- Площадка, тренер и абсолютное время не дублируются; начало периода = start_at занятия + offset_minutes.
-- Перед публикацией сервис проверяет полное покрытие занятия без пропусков/наложений, границы и сетку площадки.
-- Межстрочные проверки и запрет технических периодов для внешнего адреса выполняет сервис в общей транзакции.
-- Перенос согласует периоды, сохранённые коды и revision занятия; прежние значения сохраняются в аудите.
-- Отмена сохраняет технические строки для истории; cancelled/draft не передаются оборудованию и не занимают площадку.
-- door_code совместим с reservations.door_code; повторный показ использует сохранённый код, не генерирует новый.
-- Стоимость оборудования и платёжные данные не добавляются: их применение требует отдельной финансовой модели.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_course_event_service_slots` (
  `course_event_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Занятие-владелец технического периода',
  `offset_minutes` int(10) UNSIGNED NOT NULL COMMENT 'Смещение начала периода от start_at занятия в минутах',
  `duration_minutes` int(10) UNSIGNED NOT NULL COMMENT 'Длительность технического периода в минутах',
  `door_code` varchar(20) DEFAULT NULL COMMENT 'Сохранённый код доступа; NULL — код не используется',
  `light_state` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Свет: 0 — выключен, 1 — включён',
  `heating_state` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Отопление: 0 — выключено, 1 — включено',
  `net_state` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Канал net: 0 — выключен, 1 — включён',
  PRIMARY KEY (`course_event_id`, `offset_minutes`),
  CONSTRAINT `fk_cess_event` FOREIGN KEY (`course_event_id`)
    REFERENCES `at_base_active_court_course_events` (`course_event_id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_cess_offset` CHECK (`offset_minutes` >= 0),
  CONSTRAINT `chk_cess_duration` CHECK (`duration_minutes` > 0),
  CONSTRAINT `chk_cess_light` CHECK (`light_state` IN (0, 1)),
  CONSTRAINT `chk_cess_heating` CHECK (`heating_state` IN (0, 1)),
  CONSTRAINT `chk_cess_net` CHECK (`net_state` IN (0, 1)),
  CONSTRAINT `chk_cess_door_code` CHECK (`door_code` IS NULL OR CHAR_LENGTH(TRIM(`door_code`)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Технические периоды занятий: коды и оборудование';

-- ---------------------------------------------------------------
-- 6. at_base_active_court_course_enrollments
-- Одна строка на оформление записи; повторная покупка других дат создаёт новую запись.
-- Участник и плательщик — client_id; отдельный сценарий оплаты третьим лицом не предусмотрен.
-- Оплату всегда принимает площадка. Сохраняются сумма, модель взаиморасчётов и основной тренер на момент оформления.
-- Замена тренера не переписывает снимок взаиморасчётов; это не журнал распределения средств или выплаты тренеру.
-- full_course: полная цена до старта; remaining_sessions: полная запись после старта по сумме разовых цен остатка.
-- selected_sessions: частичная покупка; обе цены возврата полного курса для неё NULL.
-- Возврат всего курса: refund_full * число отменяемых неиспользованных дат / полное число дат курса.
-- Числитель, знаменатель, состав дат, предыдущие возвраты и итог сохраняются в cancellation_parameters, не пересчитываются при повторе.
-- invoice_account_id — явная логическая ссылка на accounts.account_id либо accounts_archive.old_account_id после архивации.
-- FK к accounts намеренно нет: существующий архивный процесс может физически переносить документ из accounts.
-- Этап 1: Guthaben списывается идемпотентно через существующий личный счёт; invoice сохраняет долг для месячного расчёта.
-- Этап 2: один месячный счёт клиента может включать несколько оформлений, поэтому invoice_account_id не UNIQUE.
-- Защита от повторного включения проверяет оформление и его связь с позицией счёта, а не уникальность заголовка.
-- Пока документы не формируются: invoice_status = not_created, invoice_account_id = NULL; это не ошибка записи.
-- building: заголовок и ссылка уже закреплены, позиции/снимок ещё формируются; issued: документ сформирован, не означает оплату.
-- Сервис фиксирует заголовок и ссылку атомарно до записи позиций; после сбоя восстанавливает тот же документ без дубля.
-- accounts_reservations_others в эталоне MyISAM: незавершённые позиции требуют восстановления, одного ROLLBACK недостаточно.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_course_enrollments` (
  `course_enrollment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID оформления записи',
  `course_group_id` int(11) NOT NULL COMMENT 'Курс',
  `client_id` int(10) UNSIGNED NOT NULL COMMENT 'Участник; существование проверяет приложение',
  `booking_type` enum('full','partial') NOT NULL COMMENT 'Весь курс или выбранные занятия',
  `pricing_basis` enum('full_course','remaining_sessions','selected_sessions') NOT NULL
    COMMENT 'Основание расчёта: полный тариф, разовые цены остатка или выбранных дат',
  `club_state_id` int(11) NOT NULL COMMENT 'Тип клиента на момент оформления',
  `trainer_settlement_mode` enum('rental','hired') NOT NULL COMMENT 'Снимок модели взаиморасчётов курса на момент оформления',
  `settlement_trainer_id` int(10) UNSIGNED NOT NULL COMMENT 'Основной тренер на момент оформления для истории взаиморасчётов в обоих режимах',
  `total_price` decimal(10,2) NOT NULL COMMENT 'Зафиксированная сумма участия, независимая от цены обычной брони',
  `refund_single_from_full` decimal(10,2) DEFAULT NULL COMMENT 'Условие возврата при покупке полного курса: 0 — нет, больше 0 — сумма; NULL для partial',
  `refund_full` decimal(10,2) DEFAULT NULL COMMENT 'Снимок базы возврата всего курса; 0 — нет, NULL для partial',
  `cancel_days_limit` int(3) UNSIGNED NOT NULL COMMENT 'Срок отмены участником на момент покупки, копия условия курса',
  `currency` char(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'Валюта суммы',
  `payment_method` varchar(32) DEFAULT NULL COMMENT 'Этап 1: invoice или алиас Encash::PrivateAccount; доступность проверяет сервер',
  `invoice_account_id` int(11) DEFAULT NULL COMMENT 'ID первичного счёта площадки в accounts; связь сохраняется после переноса в архив',
  `invoice_status` enum('not_created','building','issued') NOT NULL DEFAULT 'not_created'
    COMMENT 'Состояние формирования документа, независимо от оплаты',
  `payment_status` enum('unpaid','pending','paid','refund_required','partially_refunded','refunded') NOT NULL DEFAULT 'unpaid',
  `status` enum('pending_payment','confirmed','partially_cancelled','cancelled','payment_failed','expired') NOT NULL,
  `hold_expires_at` datetime DEFAULT NULL COMMENT 'Срок временного удержания мест при онлайн-оплате',
  `idempotency_key` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'Ключ защиты от повторного оформления',
  `created_by_type` enum('client','admin','trainer','system') NOT NULL,
  `created_by_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID инициатора в соответствующей таблице',
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_parameters` json DEFAULT NULL COMMENT 'Параметры отмены оформления целиком; отмены отдельных дат сохраняются у связей участия',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`course_enrollment_id`),
  UNIQUE KEY `uk_cen_idempotency` (`idempotency_key`),
  KEY `idx_cen_invoice_account` (`invoice_account_id`),
  UNIQUE KEY `uk_cen_identity` (`course_enrollment_id`, `course_group_id`, `client_id`),
  KEY `idx_cen_client_created` (`client_id`, `created_at`, `course_enrollment_id`),
  KEY `idx_cen_course_status` (`course_group_id`, `status`, `course_enrollment_id`),
  KEY `idx_cen_status_expires` (`status`, `hold_expires_at`),
  KEY `idx_cen_settlement_trainer` (`settlement_trainer_id`),
  CONSTRAINT `fk_cen_course` FOREIGN KEY (`course_group_id`) REFERENCES `at_base_active_court_course_groups` (`course_group_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cen_settlement_trainer` FOREIGN KEY (`settlement_trainer_id`)
    REFERENCES `at_base_active_court_trainers` (`trainer_id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_cen_price` CHECK (`total_price` >= 0),
  CONSTRAINT `chk_cen_pricing_basis` CHECK (
    (`booking_type` = 'full' AND `pricing_basis` IN ('full_course', 'remaining_sessions'))
    OR (`booking_type` = 'partial' AND `pricing_basis` = 'selected_sessions')
  ),
  CONSTRAINT `chk_cen_refund_terms` CHECK (
    (`booking_type` = 'full' AND `refund_single_from_full` IS NOT NULL AND `refund_single_from_full` >= 0
      AND `refund_full` IS NOT NULL AND `refund_full` >= 0)
    OR (`booking_type` = 'partial' AND `refund_single_from_full` IS NULL AND `refund_full` IS NULL)
  ),
  CONSTRAINT `chk_cen_invoice` CHECK (
    (`invoice_status` = 'not_created' AND `invoice_account_id` IS NULL)
    OR (`invoice_status` IN ('building', 'issued') AND `invoice_account_id` IS NOT NULL AND `invoice_account_id` > 0)
  ),
  CONSTRAINT `chk_cen_cancellation_object` CHECK (
    `cancellation_parameters` IS NULL OR JSON_TYPE(`cancellation_parameters`) = 'OBJECT'
  ),
  CONSTRAINT `chk_cen_cancelled` CHECK (
    `status` <> 'cancelled' OR (`cancelled_at` IS NOT NULL AND `cancellation_parameters` IS NOT NULL)
  ),
  CONSTRAINT `chk_cen_hold` CHECK (`status` <> 'pending_payment' OR `hold_expires_at` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Записи клиентов на курсы и снимки оплаты';

-- ---------------------------------------------------------------
-- 7. at_base_active_court_course_enrollment_events
-- Все выбранные даты сохраняются явно, включая запись на полный курс.
-- course_group_id и client_id дублируются для составных FK и запрета повторного участия.
-- Время и цена полного курса сюда не копируются.
-- Для исходной покупки full_course price_amount = NULL; для remaining_sessions/selected_sessions — разовая цена даты.
-- added_free создаётся сервисом только для действующей полной покупки при публикации новой даты, всегда с price_amount = 0.
-- Согласование inclusion_type/price_amount с pricing_basis родителя проверяет сервис под блокировкой; CHECK не читает другую таблицу.
-- Добавленные даты не меняют total_price, refund_full или уже выставленный счёт; отменённые полные покупки не возобновляются.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_course_enrollment_events` (
  `course_enrollment_id` bigint(20) UNSIGNED NOT NULL,
  `course_event_id` bigint(20) UNSIGNED NOT NULL,
  `course_group_id` int(11) NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','confirmed','cancelled') NOT NULL COMMENT 'Состояние участия в занятии',
  `active_client_id` int(10) UNSIGNED GENERATED ALWAYS AS (
    CASE WHEN `status` IN ('pending','confirmed') THEN `client_id` ELSE NULL END
  ) PERSISTENT COMMENT 'Клиент для уникальности активного участия; после отмены NULL',
  `inclusion_type` enum('purchased','added_free') NOT NULL DEFAULT 'purchased'
    COMMENT 'Дата исходной покупки либо бесплатно добавленная к полному курсу',
  `price_amount` decimal(10,2) DEFAULT NULL COMMENT 'Разовая цена исходной даты; NULL для full_course, 0 для added_free',
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` varchar(512) DEFAULT NULL,
  `cancellation_parameters` json DEFAULT NULL COMMENT 'Инициатор, применённый срок, основание и расчётная сумма возврата; не факт выплаты',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`course_enrollment_id`, `course_event_id`),
  UNIQUE KEY `uk_cee_active_client` (`course_event_id`, `active_client_id`),
  KEY `idx_cee_event_status` (`course_event_id`, `status`, `course_enrollment_id`),
  KEY `idx_cee_event_course` (`course_event_id`, `course_group_id`),
  KEY `idx_cee_enrollment_identity` (`course_enrollment_id`, `course_group_id`, `client_id`),
  CONSTRAINT `fk_cee_enrollment` FOREIGN KEY (`course_enrollment_id`, `course_group_id`, `client_id`)
    REFERENCES `at_base_active_court_course_enrollments` (`course_enrollment_id`, `course_group_id`, `client_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cee_event` FOREIGN KEY (`course_event_id`, `course_group_id`)
    REFERENCES `at_base_active_court_course_events` (`course_event_id`, `course_group_id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_cee_price` CHECK (`price_amount` IS NULL OR `price_amount` >= 0),
  CONSTRAINT `chk_cee_added_free` CHECK (
    `inclusion_type` <> 'added_free' OR (`price_amount` IS NOT NULL AND `price_amount` = 0)
  ),
  CONSTRAINT `chk_cee_cancellation_object` CHECK (
    `cancellation_parameters` IS NULL OR JSON_TYPE(`cancellation_parameters`) = 'OBJECT'
  ),
  CONSTRAINT `chk_cee_cancelled` CHECK (
    `status` <> 'cancelled' OR (`cancelled_at` IS NOT NULL AND `cancellation_parameters` IS NOT NULL)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Участие клиентов в конкретных занятиях';

-- ---------------------------------------------------------------
-- 8. at_base_active_court_course_participant_overrides
-- Персональные тарифы клиента в курсе; список доступных занятий здесь не задаётся.
-- Пара (курс, клиент) — первичный ключ; отдельного id нет, история покупки не ссылается на изменяемую настройку.
-- Хотя бы один тариф задан; сброс всех персональных значений удаляет настройку, сохраняя снимки оформлений.
-- Персональные цены назначает основной тренер либо пользователь с can_manage_courses = 1; прошлые покупки не пересчитываются.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_course_participant_overrides` (
  `course_group_id` int(11) NOT NULL COMMENT 'Ссылка на курс',
  `client_id` int(10) UNSIGNED NOT NULL COMMENT 'Клиент-участник',
  `price_full_override` decimal(10,2) DEFAULT NULL COMMENT 'Специальная цена за полный курс',
  `price_single_override` decimal(10,2) DEFAULT NULL COMMENT 'Специальная цена за одно занятие',
  `refund_single_from_full_override` decimal(10,2) DEFAULT NULL COMMENT 'Персональный возврат: NULL — тариф типа клиента, 0 — нет, больше 0 — сумма',
  `refund_full_override` decimal(10,2) DEFAULT NULL COMMENT 'Персональная база возврата всего курса: NULL — наследование, 0 — нет возврата',
  `created_by_trainer_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Тренер, назначивший тарифы; NULL для автора из users',
  `created_by_user_id` int(11) DEFAULT NULL COMMENT 'Пользователь с правом курсов, назначивший тарифы; NULL для автора-тренера',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Когда создана запись',
  PRIMARY KEY (`course_group_id`, `client_id`),
  KEY `idx_client_id` (`client_id`),
  CONSTRAINT `fk_cpo_course_group` FOREIGN KEY (`course_group_id`) REFERENCES `at_base_active_court_course_groups` (`course_group_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cpo_client` FOREIGN KEY (`client_id`) REFERENCES `at_base_active_court_clients` (`client_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cpo_trainer` FOREIGN KEY (`created_by_trainer_id`) REFERENCES `at_base_active_court_trainers` (`trainer_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cpo_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `at_base_active_court_users` (`user_id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_cpo_author` CHECK (
    (`created_by_trainer_id` IS NOT NULL AND `created_by_user_id` IS NULL)
    OR (`created_by_trainer_id` IS NULL AND `created_by_user_id` IS NOT NULL)
  ),
  CONSTRAINT `chk_cpo_override_present` CHECK (
    `price_full_override` IS NOT NULL OR `price_single_override` IS NOT NULL
    OR `refund_single_from_full_override` IS NOT NULL OR `refund_full_override` IS NOT NULL
  ),
  CONSTRAINT `chk_cpo_prices` CHECK (
    (`price_full_override` IS NULL OR `price_full_override` >= 0)
    AND (`price_single_override` IS NULL OR `price_single_override` >= 0)
    AND (`refund_single_from_full_override` IS NULL OR `refund_single_from_full_override` >= 0)
    AND (`refund_full_override` IS NULL OR `refund_full_override` >= 0)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Персональные тарифы участников курса';

-- ---------------------------------------------------------------
-- 9. at_base_active_court_scheduled_events
-- Общие задания для внешнего процесса, в том числе запускаемого cron. Сам процесс и функции в этой задаче не реализуются.
-- Внешний процесс выбирает pending по available_at <= текущему UTC-времени и определяет будущую функцию по event_type.
-- Для писем курса: course_email_send, одна строка на получателя/макет/запуск; один обработчик для automatic, manual и recurring.
-- source_type/source_id связывают действие с исходной сущностью; полиморфные ссылки проверяет приложение.
-- Для automatic снимок фиксируется перед первой попыткой, для manual — при постановке, для recurring — при создании очередного запуска.
-- Для manual scheduled_at = available_at = выбранное тренером время в UTC; «сейчас» использует время запроса.
-- Отправка сейчас, на дату и периодические расписания доступны для любого макета независимо от напоминаний перед занятиями.
-- payload ручного запуска хранит request_id, recipient_mode, recipient_type, client_id либо NULL, email и готовое письмо.
-- recipient_mode = selected|course связывает письмо с участием: отписка и окончание/отмена курса отменяют такие задания.
-- recipient_mode = direct допускается только для manual: любой клиент общей базы либо введённый email без регистрации.
-- direct-письмо независимо от участия и состояния курса; после постановки используется снимок адреса и письма.
-- Для direct recipient_type = client требует положительный client_id; email требует client_id = NULL. Проверка адреса — в сервисе.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_scheduled_events` (
  `scheduled_event_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID отложенного действия, не ID занятия',
  `event_type` varchar(70) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'Английский ключ обработчика действия',
  `trigger_type` enum('automatic','manual','recurring') NOT NULL DEFAULT 'automatic' COMMENT 'Напоминание перед занятием, разовая ручная постановка или периодическое расписание',
  `source_type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT 'Для писем: course, course_event либо course_email_schedule',
  `source_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID курса, занятия либо периодического расписания согласно source_type',
  `scheduled_at` datetime NOT NULL COMMENT 'Время конкретного запуска в UTC; для recurring — календарный момент повторения',
  `available_at` datetime NOT NULL COMMENT 'Ближайшее время попытки в UTC; сначала scheduled_at либо текущее время для поздней записи',
  `payload` json NOT NULL COMMENT 'Параметры действия; для письма режим и тип получателя, client_id либо NULL, email, язык, тема и тело',
  `idempotency_key` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'Уникальный ключ логического действия',
  `status` enum('pending','processing','completed','cancelled','failed') NOT NULL DEFAULT 'pending',
  `attempts` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Число начатых попыток; не число доставленных писем',
  `lock_token` char(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT 'Случайный токен владельца обработки',
  `locked_until` datetime DEFAULT NULL COMMENT 'Срок владения обработкой в UTC',
  `finished_at` datetime DEFAULT NULL COMMENT 'Время завершения, окончательной ошибки или отмены в UTC',
  `last_error` text DEFAULT NULL COMMENT 'Последняя внутренняя ошибка или причина отмены на английском',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`scheduled_event_id`),
  UNIQUE KEY `uk_se_idempotency` (`idempotency_key`),
  KEY `idx_se_due` (`status`, `available_at`, `scheduled_event_id`),
  KEY `idx_se_expired_lock` (`status`, `locked_until`, `scheduled_event_id`),
  KEY `idx_se_source_status` (`source_type`, `source_id`, `trigger_type`, `status`),
  CONSTRAINT `chk_se_payload` CHECK (JSON_TYPE(`payload`) = 'OBJECT'),
  CONSTRAINT `chk_se_direct_recipient` CHECK (
    `event_type` <> 'course_email_send'
    OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`payload`, '$.recipient_mode')), '') <> 'direct'
    OR COALESCE((
      `trigger_type` = 'manual'
      AND JSON_TYPE(JSON_EXTRACT(`payload`, '$.email')) = 'STRING'
      AND CHAR_LENGTH(TRIM(JSON_UNQUOTE(JSON_EXTRACT(`payload`, '$.email')))) > 0
      AND (
        (JSON_UNQUOTE(JSON_EXTRACT(`payload`, '$.recipient_type')) = 'client'
          AND JSON_TYPE(JSON_EXTRACT(`payload`, '$.client_id')) = 'INTEGER'
          AND JSON_EXTRACT(`payload`, '$.client_id') > 0
          AND JSON_EXTRACT(`payload`, '$.client_id') <= 4294967295)
        OR (JSON_UNQUOTE(JSON_EXTRACT(`payload`, '$.recipient_type')) = 'email'
          AND JSON_TYPE(JSON_EXTRACT(`payload`, '$.client_id')) = 'NULL')
      )
    ), 0)
  ),
  CONSTRAINT `chk_se_keys` CHECK (CHAR_LENGTH(TRIM(`event_type`)) > 0 AND CHAR_LENGTH(TRIM(`idempotency_key`)) > 0),
  CONSTRAINT `chk_se_source` CHECK (
    (`source_type` IS NULL AND `source_id` IS NULL)
    OR (`source_type` IS NOT NULL AND CHAR_LENGTH(TRIM(`source_type`)) > 0 AND `source_id` IS NOT NULL AND `source_id` > 0)
  ),
  CONSTRAINT `chk_se_schedule` CHECK (`available_at` >= `scheduled_at`),
  CONSTRAINT `chk_se_lock` CHECK (
    (`status` = 'processing' AND `lock_token` IS NOT NULL AND CHAR_LENGTH(`lock_token`) = 32
      AND `locked_until` IS NOT NULL AND `attempts` > 0)
    OR (`status` <> 'processing' AND `lock_token` IS NULL AND `locked_until` IS NULL)
  ),
  CONSTRAINT `chk_se_finished` CHECK (
    (`status` IN ('pending','processing') AND `finished_at` IS NULL)
    OR (`status` IN ('completed','cancelled','failed') AND `finished_at` IS NOT NULL)
  ),
  CONSTRAINT `chk_se_completed_attempt` CHECK (`status` <> 'completed' OR `attempts` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Общая очередь автоматических и ручных действий для внешнего процесса';

-- ---------------------------------------------------------------
-- 10. at_base_active_court_course_email_templates
-- Несколько произвольных макетов на курс; название и режим общие для всех языков.
-- Каждый макет допускает отправку сейчас, на конкретную дату и периодически по отдельным расписаниям.
-- lesson_reminder_enabled = 1 дополнительно включает напоминания перед занятиями по интервалу курса; 0 отключает только их.
-- Каждый включённый макет создаёт отдельное напоминание; переключение автоматики не меняет ручные задания.
-- template_alias — стабильный английский ключ, а не имя обработчика; title — название, введённое тренером.
-- Редактировать может основной тренер либо пользователь с can_manage_courses; сохраняется фактический автор правки.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_course_email_templates` (
  `course_group_id` int(11) NOT NULL COMMENT 'Курс-владелец макета',
  `template_alias` varchar(70) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'Стабильный английский ключ макета внутри курса',
  `title` varchar(255) NOT NULL COMMENT 'Произвольное название для списка макетов тренера',
  `lesson_reminder_enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1 — напоминать перед занятиями; отправка сейчас, на дату и периодически доступна при 0 и 1',
  `revision` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Общая версия макета, настроек и всех языковых текстов',
  `updated_by_trainer_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Тренер, сохранивший макет; NULL для автора из users',
  `updated_by_user_id` int(11) DEFAULT NULL COMMENT 'Пользователь с правом курсов, сохранивший макет; NULL для автора-тренера',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`course_group_id`, `template_alias`),
  KEY `idx_cet_automatic` (`course_group_id`, `lesson_reminder_enabled`, `template_alias`),
  KEY `idx_cet_author` (`updated_by_trainer_id`),
  KEY `idx_cet_user_author` (`updated_by_user_id`),
  CONSTRAINT `fk_cet_course` FOREIGN KEY (`course_group_id`)
    REFERENCES `at_base_active_court_course_groups` (`course_group_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cet_trainer` FOREIGN KEY (`updated_by_trainer_id`)
    REFERENCES `at_base_active_court_trainers` (`trainer_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cet_user` FOREIGN KEY (`updated_by_user_id`)
    REFERENCES `at_base_active_court_users` (`user_id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_cet_author` CHECK (
    (`updated_by_trainer_id` IS NOT NULL AND `updated_by_user_id` IS NULL)
    OR (`updated_by_trainer_id` IS NULL AND `updated_by_user_id` IS NOT NULL)
  ),
  CONSTRAINT `chk_cet_values` CHECK (
    `template_alias` REGEXP '^[a-z][a-z0-9-]{0,69}$'
    AND CHAR_LENGTH(TRIM(`title`)) > 0 AND `revision` > 0
  ),
  CONSTRAINT `chk_cet_automatic` CHECK (`lesson_reminder_enabled` IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Макеты писем курса и включение автоматических напоминаний';

-- ---------------------------------------------------------------
-- 11. at_base_active_court_course_email_template_translations
-- Тема и тело каждого макета по языкам; название, режим, ревизия и автор хранятся в родительском макете.
-- Языки берутся из LangConfig; настройки и тексты сохраняются одной транзакцией с обновлением родителя.
-- Параметры формата %KEY% подставляются отдельно в тему и тело; список и доступность проверяет будущий обработчик.
-- Без контекста занятия можно использовать данные участника и курса; для даты/периода/места требуется выбранное занятие.
-- Готовое письмо и значения параметров сохраняются в scheduled_events.payload для повторов и ручной отложенной отправки.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_course_email_template_translations` (
  `course_group_id` int(11) NOT NULL COMMENT 'Курс-владелец макета',
  `template_alias` varchar(70) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'Ключ родительского макета',
  `language` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'Язык текста из настроек приложения',
  `subject` varchar(255) NOT NULL COMMENT 'Макет темы с параметрами %KEY%',
  `content` mediumtext NOT NULL COMMENT 'Макет тела с параметрами %CLIENT_NAME%, %CLIENT_SURNAME% и другими разрешёнными ключами',
  PRIMARY KEY (`course_group_id`, `template_alias`, `language`),
  CONSTRAINT `fk_cett_template` FOREIGN KEY (`course_group_id`, `template_alias`)
    REFERENCES `at_base_active_court_course_email_templates` (`course_group_id`, `template_alias`) ON DELETE CASCADE,
  CONSTRAINT `chk_cett_values` CHECK (
    CHAR_LENGTH(TRIM(`language`)) > 0 AND CHAR_LENGTH(TRIM(`subject`)) > 0 AND CHAR_LENGTH(TRIM(`content`)) > 0
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Языковые тексты макетов писем курса';

-- ---------------------------------------------------------------
-- 12. at_base_active_court_course_email_schedules
-- Одно повторяющееся расписание конкретного макета; у макета может быть несколько расписаний.
-- Каждые interval_days календарных дней от start_date в send_time, по часовому поясу timezone.
-- Например: interval_days = 1, send_time = 12:00:00 — ежедневно в полдень по местному времени.
-- next_run_at и last_occurrence_at — UTC-моменты; время следующего запуска не вычисляется как +86400 секунд.
-- recipient_mode = selected: выбранные клиенты из course_email_schedule_recipients, если участие ещё действует.
-- recipient_mode = course: все действующие участники курса/контекстного занятия заново на каждом запуске; без копии списка.
-- Курс завершён для рассылок, если нет scheduled-занятий с finish_at позже текущего времени; end_date = NULL это не отменяет.
-- Внешний процесс создаёт отдельные recurring-задания в scheduled_events и продвигает курсор одной транзакцией.
-- Последний обработанный момент сохраняется независимо от результата писем, чтобы правка не повторяла старый запуск.
-- Пауза/возобновление/правка повышают revision и отменяют все pending-письма правила, включая ожидающие повторной попытки.
-- Завершение по end_date при продвижении курсора revision не меняет: последняя пачка допустима, пока курс и участие действуют.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_course_email_schedules` (
  `course_email_schedule_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID периодического расписания',
  `course_group_id` int(11) NOT NULL COMMENT 'Курс-владелец макета и получателей',
  `template_alias` varchar(70) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'Макет для периодической рассылки',
  `course_event_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Контекст занятия; NULL — общий контекст курса, независимо от выбора получателей',
  `recipient_mode` enum('selected','course') NOT NULL DEFAULT 'selected' COMMENT 'Выбранные действующие участники либо весь актуальный состав курса/занятия',
  `interval_days` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Период в календарных днях: 1 — ежедневно, 7 — еженедельно',
  `send_time` time NOT NULL COMMENT 'Местное время суток для каждого запуска',
  `timezone` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'Часовой пояс IANA, например Europe/Berlin',
  `start_date` date NOT NULL COMMENT 'Первая календарная дата правила в его часовом поясе',
  `end_date` date DEFAULT NULL COMMENT 'Последняя допустимая местная дата включительно; NULL — без заданной даты окончания',
  `status` enum('paused','active','completed') NOT NULL DEFAULT 'paused' COMMENT 'Приостановлено, включено или завершено',
  `next_run_at` datetime DEFAULT NULL COMMENT 'Ближайший запуск в UTC; обязателен только для active',
  `last_occurrence_at` datetime DEFAULT NULL COMMENT 'Последний обработанный календарный запуск в UTC, не факт доставки писем',
  `revision` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Версия расписания для проверки ожидающих заданий',
  `updated_by_trainer_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Тренер, сохранивший настройки; NULL для автора из users',
  `updated_by_user_id` int(11) DEFAULT NULL COMMENT 'Пользователь с правом курсов, сохранивший настройки; NULL для автора-тренера',
  `last_error` text DEFAULT NULL COMMENT 'Внутренняя причина приостановки или ошибки на английском',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`course_email_schedule_id`),
  KEY `idx_ces_due` (`status`, `next_run_at`, `course_email_schedule_id`),
  KEY `idx_ces_template` (`course_group_id`, `template_alias`),
  KEY `idx_ces_event_course` (`course_event_id`, `course_group_id`),
  KEY `idx_ces_author` (`updated_by_trainer_id`),
  KEY `idx_ces_user_author` (`updated_by_user_id`),
  CONSTRAINT `fk_ces_template` FOREIGN KEY (`course_group_id`, `template_alias`)
    REFERENCES `at_base_active_court_course_email_templates` (`course_group_id`, `template_alias`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ces_event` FOREIGN KEY (`course_event_id`, `course_group_id`)
    REFERENCES `at_base_active_court_course_events` (`course_event_id`, `course_group_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ces_trainer` FOREIGN KEY (`updated_by_trainer_id`)
    REFERENCES `at_base_active_court_trainers` (`trainer_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ces_user` FOREIGN KEY (`updated_by_user_id`)
    REFERENCES `at_base_active_court_users` (`user_id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_ces_author` CHECK (
    (`updated_by_trainer_id` IS NOT NULL AND `updated_by_user_id` IS NULL)
    OR (`updated_by_trainer_id` IS NULL AND `updated_by_user_id` IS NOT NULL)
  ),
  CONSTRAINT `chk_ces_rule` CHECK (
    `interval_days` > 0 AND `send_time` >= '00:00:00' AND `send_time` < '24:00:00'
    AND CHAR_LENGTH(TRIM(`timezone`)) > 0 AND `revision` > 0
  ),
  CONSTRAINT `chk_ces_recipient_mode` CHECK (`recipient_mode` IN ('selected', 'course')),
  CONSTRAINT `chk_ces_dates` CHECK (`end_date` IS NULL OR `end_date` >= `start_date`),
  CONSTRAINT `chk_ces_next_run` CHECK (
    (`status` = 'active' AND `next_run_at` IS NOT NULL)
    OR (`status` <> 'active' AND `next_run_at` IS NULL)
  ),
  CONSTRAINT `chk_ces_cursor` CHECK (
    `next_run_at` IS NULL OR `last_occurrence_at` IS NULL OR `next_run_at` > `last_occurrence_at`
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Периодические расписания рассылок по макетам курсов';

-- ---------------------------------------------------------------
-- 13. at_base_active_court_course_email_schedule_recipients
-- Выбранные тренером клиенты только для recipient_mode = selected; для course строк здесь быть не должно.
-- Новые участники не добавляются в selected автоматически; сохранённый ID не даёт права на письма после отписки.
-- Существование клиента и действующее участие проверяются при выборе, создании запуска и перед каждой отправкой.
-- FK к clients не добавляется для совместимости с установками MyISAM; историю писем сохраняет scheduled_events.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_course_email_schedule_recipients` (
  `course_email_schedule_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Периодическое расписание',
  `client_id` int(10) UNSIGNED NOT NULL COMMENT 'Выбранный клиент-получатель',
  PRIMARY KEY (`course_email_schedule_id`, `client_id`),
  CONSTRAINT `fk_cesr_schedule` FOREIGN KEY (`course_email_schedule_id`)
    REFERENCES `at_base_active_court_course_email_schedules` (`course_email_schedule_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Фиксированные получатели периодических рассылок курса';

-- ---------------------------------------------------------------
-- 14. at_base_active_court_trainers_min_max — общие и индивидуальные условия тренеров
-- trainer_id = NULL — единственная общая строка; иначе условия конкретного основного тренера курса.
-- Персональное NULL наследует общее значение; отсутствие персональной строки также наследует, 0 — явный нулевой срок.
-- Только пользователь с can_manage_courses меняет условия; тренер просматривает эффективное значение и его источник.
-- scope_key с UNIQUE защищает единственность общей строки, чего не обеспечивает UNIQUE по nullable trainer_id.
-- Формула как у обычной брони: start_at > начало дня отмены + N календарных дней, в часовом поясе площадки.
-- Обычные брони продолжают читать areas_min_max.min_rejection_days_count; их настройки не меняются.
-- При миграции установленной ранней схемы старый config/courses/reservation_cancel_days переносится явно; здесь он не создаётся.
-- ---------------------------------------------------------------
CREATE TABLE `at_base_active_court_trainers_min_max` (
  `trainer_min_max_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `trainer_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL — общие условия; иначе конкретный профиль trainers',
  `scope_key` int(10) UNSIGNED GENERATED ALWAYS AS (IFNULL(`trainer_id`, 0)) PERSISTENT,
  `min_rejection_days_count` int(10) UNSIGNED DEFAULT NULL COMMENT 'Календарные дни отмены: NULL наследует только в персональной строке',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`trainer_min_max_id`),
  UNIQUE KEY `uk_tmm_scope` (`scope_key`),
  KEY `idx_tmm_trainer` (`trainer_id`),
  CONSTRAINT `fk_tmm_trainer` FOREIGN KEY (`trainer_id`)
    REFERENCES `at_base_active_court_trainers` (`trainer_id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_tmm_trainer` CHECK (`trainer_id` IS NULL OR `trainer_id` > 0),
  CONSTRAINT `chk_tmm_default` CHECK (`trainer_id` IS NOT NULL OR `min_rejection_days_count` IS NOT NULL),
  CONSTRAINT `chk_tmm_days` CHECK (`min_rejection_days_count` IS NULL OR `min_rejection_days_count` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Условия тренеров: общие значения и персональные переопределения';

-- Общий срок по умолчанию — 1 календарный день. Повтор начальных данных не перезаписывает заданные администратором условия.
INSERT INTO `at_base_active_court_trainers_min_max` (`trainer_id`, `min_rejection_days_count`)
SELECT NULL, 1
WHERE NOT EXISTS (
  SELECT 1 FROM `at_base_active_court_trainers_min_max` WHERE `scope_key` = 0
);

-- ---------------------------------------------------------------
-- 15. Согласование только суммы аренды — существующая таблица config
-- 1: сумму trainer_price и её изменение подтверждает любой пользователь с can_manage_courses = 1.
-- 0: автономный тренер, подтверждение суммы администратором не требуется.
-- Применяется только к trainer_settlement_mode = rental; для hired аренды нет и trainer_price = NULL.
-- Не требует согласования публикации курса, расписания, участников, тарифов участия или рассылки.
-- Начальное значение и default — 0 (автономная работа). Существующее явное значение не перезаписывается.
-- Правила проверки и фиксации подтверждения реализуются отдельно; наличие суммы само по себе не означает согласование.
-- ---------------------------------------------------------------
INSERT INTO `at_base_active_court_config` (`type`, `alias`, `value`, `default`, `comment`)
SELECT 'courses', 'rental_approval_required', '0', '0',
  'Согласование суммы аренды внешнего тренера: 1 — требуется, 0 — автономный тренер; для наёмного не применяется'
WHERE NOT EXISTS (
  SELECT 1 FROM `at_base_active_court_config` WHERE `type` = 'courses' AND `alias` = 'rental_approval_required'
);

-- Схема содержит 14 новых таблиц, отдельное право в users и одну настройку config; полного учёта выплат нет.
-- Макеты создаются основным тренером или пользователем с правом курсов; общая letters_templates не изменяется.
-- Разовые, периодические письма и напоминания, их снимки и результаты находятся в scheduled_events.
-- Это проект схемы, не миграция установленной БД; ALTER расширяет только users, DROP отсутствует.
