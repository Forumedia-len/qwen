<?php

defined('MC_ARENA') || define('MC_ARENA', false);

// некоторые константы, думаю потом их перенести в базу данных и добавить в админке возожность их менять
/**
 * Использовать дверные коды.
 * Форматы: true/false — для всех контекстов; all|false|10_7|true — отдельное значение для type_id=10, sport_id=7.
 * @const DOOR_CODES bool|string
 */
defined('DOOR_CODES') || define('DOOR_CODES', true);
defined('GUEST') || define('GUEST', true); // использовать функционал гостя , так же должен быть включен paypal, так как гости платят только по пайпал
defined('USE_GUEST_OPEN') || define('USE_GUEST_OPEN', true); // использовать функционал гостя на открытых кортах
defined('CODE_CARD') || define('CODE_CARD', false); // использовать вход по коду с карты
defined('TOUCHSCREEN') || define('TOUCHSCREEN', true); // нужен тач или нет
/** Доступность интерфейса /widget/: false возвращает HTTP 404 до обработки запроса. */
defined('USE_WIDGET') || define('USE_WIDGET', false);
/** Запрет индексации HTML-страниц всех устройств с разрешением индексации при встраивании. Виджет выводит тег всегда. */
defined('NOINDEX_SITE') || define('NOINDEX_SITE', false);
/** Базовые цвета виджета: RGB HEX, необязательный # и три либо шесть знаков. Атрибуты iframe имеют приоритет. */
defined('WIDGET_PRIMARY_COLOR') || define('WIDGET_PRIMARY_COLOR', '00471f');
defined('WIDGET_ACCENT_COLOR') || define('WIDGET_ACCENT_COLOR', 'ffb900');
/**
 * type_sport
 * false-1_1,2_1
 */
defined('DISPLAY_TYPE') || define('DISPLAY_TYPE', false);
/**
 * Глобальное: использования оплату по paypal, на русских кортах не используется вообще
 */
defined('USE_PAYMENT_PAYPAL') || define('USE_PAYMENT_PAYPAL', true);
/**
 * Глобальное: используем оплату по счету
 */
defined('USE_PAYMENT_INVOICE') || define('USE_PAYMENT_INVOICE', true);
/**
 * Сколько часов(количество бронирований) бронирует один игрок ! Для открытых кортов !
 * Если false, то не используется
 */
defined('OPEN_MANY_HOURS') || define('OPEN_MANY_HOURS', false);
defined('CLOSE_MANY_HOURS') || define('CLOSE_MANY_HOURS', false);
defined('MC_ARENA_MANY_HOURS') || define('MC_ARENA_MANY_HOURS', false);

/**
 * Промежуток времени в минутах от текущего времени в котором можно забронировать игру ! Для открытых кортов !
 */
defined('OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION') || define('OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION', false);
/**
 * Промежуток времени в часах только после которого возможно забронировать игру ! Для открытых кортов !
 */
defined('OPEN_TIME_INTERVAL_IN_MINUTE_AFTER_WHICH_RESERVATION_IS_POSSIBLE')
|| define('OPEN_TIME_INTERVAL_IN_MINUTE_AFTER_WHICH_RESERVATION_IS_POSSIBLE', false);
/**
 * Промежуток времени в часах только после которого возможно забронировать игру ! Для остальных типов кортов !
 */
defined('TIME_INTERVAL_IN_HOUR_AFTER_WHICH_RESERVATION_IS_POSSIBLE')
|| define('TIME_INTERVAL_IN_HOUR_AFTER_WHICH_RESERVATION_IS_POSSIBLE', false);

defined('OPEN_RESERVATION_ON_DEVICE') || define(
  'OPEN_RESERVATION_ON_DEVICE',
  'all'
); // где игрок может бронировать: all - везде , touch - только на тачскрине
defined('OPEN_DEFAULT_SPORT_ID') || define('OPEN_DEFAULT_SPORT_ID', 1);// Id спорта выбираемого по умолчанию в touchscreen версии


defined('PER_PAGE') || define('PER_PAGE', 4); // количество столбцов в табличном расписании
defined('OPEN_STREET_PER_PAGE') || define('OPEN_STREET_PER_PAGE', 4);//количество столбцов в тач версии расписании для открытых кортов
defined('TOUCH_PER_PAGE') || define('TOUCH_PER_PAGE', 6);//количество столбцов в тач версии расписании для закрытых кортов
/**
 *  Количество столбцов в табличном расписании в админке
 */
defined('ADMIN_COUNT_COURT_ON_PAGE') || define('ADMIN_COUNT_COURT_ON_PAGE', 5);


/**
 * Использование распределение по страницам из базы с площадками(если не используется то берется стандартная система по PER_PAGE столбцов)
 */
defined('USE_PAGE_BREAK_FROM_BASE_AREA') || define('USE_PAGE_BREAK_FROM_BASE_AREA', false);
defined('GUTHABEN_COUPONS') || define('GUTHABEN_COUPONS', true); // Функционал купонов для пополнения гутхабена от кортов MCArena

defined('DEFAULT_CLIENT_AREA_TYPE') || define('DEFAULT_CLIENT_AREA_TYPE', 0); // какие типы площадок задать клиенту по умолчанию 0 - все
/**
 * Авторизация клиентов, если не используется, то показывается надпись и номер телефона по которому нужно звонить не отображаюся кнопки забронировать
 */
defined('USE_AUTHORIZATION') || define('USE_AUTHORIZATION', true);
/**
 * Использовать возможность изменять бронирование в админке (цену, способ оплаты и статус) используется на закрытых кортах и макаренах
 */
defined('USE_CHANGE_PRICE_AND_ENCASH_IN_ADMIN') || define('USE_CHANGE_PRICE_AND_ENCASH_IN_ADMIN', true);

/**
 * методы оплаты при регистрации, в зависимости от выбранного тот и показывается
 *  возможные варианты:
 *  DE - по умолчанию 2 способа (Rechnung/Lastschriftverfahren и Paypal / Guthaben )
 *  RE - Rechnung/Lastschriftverfahren
 *  RE_NBD - Rechnung/Lastschriftverfahren - без обязательных банковских данных
 *  GU - Paypal/Guthaben
 */
defined('REGISTRATION_PAYMENT_METHOD') || define('REGISTRATION_PAYMENT_METHOD', 'DE');

/**
 * В админке позволяет делать бронь без ограничения по времени и это отображается на календаре - действует на все время(прошедшее и будущее)
 */
defined('USE_ADMIN_ALL_TIME') || define('USE_ADMIN_ALL_TIME', true);
/**
 *  Использовать возможность бронирования админом прошедшее время
 *  по умолчанию 1 месяц назад константа ORDER_PAST_TIME
 *  измеряется в месяцах
 */
defined('USE_ADMIN_POSSIBILITY_ORDER_PAST_TIME') || define('USE_ADMIN_POSSIBILITY_ORDER_PAST_TIME', false);
defined('ORDER_PAST_TIME') || define('ORDER_PAST_TIME', 1);

/**
 * Использовать двойные корты или нет (при них не работает система присоеденения 2 игрока)
 * игра бронируется сразу со вторым игроком или гостем
 */
defined('DOUBLE_FRIENDS_OPEN_COURT') || define('DOUBLE_FRIENDS_OPEN_COURT', true);
/**
 * Сколько игроков играет 2, 4 или четное количество (если используете больше 4 доработать вывод)
 */
defined('DOUBLE_FRIENDS_OPEN_COURT_NUMBER_PLAYERS') || define('DOUBLE_FRIENDS_OPEN_COURT_NUMBER_PLAYERS', 2);
defined('USE_DOUBLE_OPEN_COURT') || define('USE_DOUBLE_OPEN_COURT', false); //использовать двойную игру
defined('SHOW_OPENING_HOURS') || define('SHOW_OPENING_HOURS', true); // показать часы работы
defined('USE_REGISTRATION') || define('USE_REGISTRATION', true); // использовать регистрацию на клиенте
defined('USE_REGISTRATION_TOUCH') || define('USE_REGISTRATION_TOUCH', true); // использовать регистрацию на тачскрине
/**
 * Использовать на закрытых кортах возможность бронировать весь день сразу(пока закрытые корты)
 */
defined('USE_RESERVATION_ALL_DAY') || define('USE_RESERVATION_ALL_DAY', false);
/**
 *  Показывать вместо имени и фамилии - логин на стрит версии
 */
defined('SHOW_LOGIN_INSTEAD_NAME_ON_STREET') || define('SHOW_LOGIN_INSTEAD_NAME_ON_STREET', false);

/** DEFAULT_REGISTRATION_USER_CLUB_STATE
 *  При регистрации клубный статус клиента по умолчанию
 *  0 - ничлен (Nichtmitglied)
 *  1 - член версии 1 (Mitglied V1)
 *  2 - член версии 2 (Mitglied V2)
 */
defined('DEFAULT_REGISTRATION_USER_CLUB_STATE') || define('DEFAULT_REGISTRATION_USER_CLUB_STATE', 0);
/**
 *  Задать цену для гостя ребенка если false то не используется эта функция
 */
defined('PRICE_FOR_GUEST_CHILD') || define('PRICE_FOR_GUEST_CHILD', false);


/**
 *  Бронирование возможно только с полного часа или с получаса
 *  значения:
 *  1. false - не используется
 *  2. '00' - возможно бронирование с часа
 *  3. '30' - с получаса
 */
defined('RESERVATION_ONLY_POSSIBLE_FROM_FULL_HOUR') || define('RESERVATION_ONLY_POSSIBLE_FROM_FULL_HOUR', false);

/**
 * @todo Устаревшее значение при переносе на ядро использовать таблицу в базе
 *      at/config.php?mode=registration - здесь есть таблица с выбором доступных значений по умолчанию
 *
 *  Задать по умолчанию при регистрации значение какие типы и спорт клиент не имеет право играть и только эти
 *  может принимать значение false - все типы и спорты открыты
 *  1_1;1_2 - тип_спорт - значение типа и спорта на которых нельзя играть после регистрации перечисление через ;
 */
defined('UNAVAILABLE_SPORTS') || define('UNAVAILABLE_SPORTS', false);

/**
 *  Показывать в расписании имя фамилию пользователей если авторизовался
 */
defined('SHOW_NAMES_FOR_AUTHORIZED_USERS') || define('SHOW_NAMES_FOR_AUTHORIZED_USERS', false);
/**
 *  Показывать в расписании название для блокировок
 */
defined('SHOW_BLOCK_NAMES_FOR_USERS') || define('SHOW_BLOCK_NAMES_FOR_USERS', true);
defined('SHOW_BLOCK_NAMES_FOR_AUTHORIZED_USERS') || define('SHOW_BLOCK_NAMES_FOR_AUTHORIZED_USERS', true);

/**
 *  Использовать на отрытых кортах опции и спец цены
 *  todo уже есть вверху , посмотреть как реализовано и удалить лишнее
 */
defined('USE_OPTION_AND_SPEC_PRICE_ON_OPEN_COURT') || define('USE_OPTION_AND_SPEC_PRICE_ON_OPEN_COURT', false);
defined('SHOW_OPTION_AND_SPEC_PRICE_ON_OPEN') || define('SHOW_OPTION_AND_SPEC_PRICE_ON_OPEN', false);

/**
 *  Использовать новую стратегию применения опций и спеццен
 *  (можно выбрать или опцию или спец цену)
 */
defined('USE_NEW_STRATEGY_OPTION') || define('USE_NEW_STRATEGY_OPTION', false);

/**
 * Использовать опции и спеццены для незарегистрированного пользователя
 */
defined('USE_OPTION_AND_SPEC_PRICE_FOR_LEFT_PLAYER') || define('USE_OPTION_AND_SPEC_PRICE_FOR_LEFT_PLAYER', false);

/**
 *  При добавлении опции или спец цены в админке можно назначить для всех типов спорта админка
 * todo если включать нужно доработать(продумать) как выводить чекбоксы по периодам
 */
defined('USE_OPTION_AND_SPEC_PRICE_FOR_ALL_TYPES') || define('USE_OPTION_AND_SPEC_PRICE_FOR_ALL_TYPES', ['stocks' => true, 'spec_prices' => false]);

/**
 * Использование капчи при регистрации
 *  ключи от active-court.com
 */
defined('USE_RECAPTCHA_V4') || define('USE_RECAPTCHA_V4', false);
defined('RECAPTCHA_CLIENT_KEY') || define('RECAPTCHA_CLIENT_KEY', '6Lc-6cAhAAAAAE6mxWzfRviEf6xxfskaxPi0QUhG');
defined('RECAPTCHA_SERVER_KEY') || define('RECAPTCHA_SERVER_KEY', '6Lc-6cAhAAAAAPNF1yzYjJEa9c9V83RFsQpQt-J7');
defined('RECAPTCHA_SCORE') || define('RECAPTCHA_SCORE', 0.3); // порог сработки рекапчи
/**
 * На странице пополнения лицевого счета показывает/скрывает метод пополнения через письмо админу
 */
defined('SHOW_PREPAYMENT_ADMIN_MAIL') || define('SHOW_PREPAYMENT_ADMIN_MAIL', true);
/**
 * Текущая валюта сайта и текущая валюта платежей PP
 */
defined('CURR_VALUTE') || define('CURR_VALUTE', '€');
defined('CURR_VALUTE_PP') || define('CURR_VALUTE_PP', 'EUR');
/**
 * Выставляются 14 дневные абонементы 1 - строго по первой недели,2 - если день не выпадает на первую неделю, то он переносится на следующую
 */
defined('ABO14_TYPE') || define('ABO14_TYPE', 2);
/**
 * Количество интервалов для парной игры 2 или 3 или 4
 */
defined('DOUBLE_PLAYERS_TIME_COUNT') || define('DOUBLE_PLAYERS_TIME_COUNT', 2);
/**
 * Максимальное значение количества интервалов для парной игры
 */
defined('DOUBLE_PLAYERS_TIME_COUNT_MAX') || define('DOUBLE_PLAYERS_TIME_COUNT_MAX', false);
/**
 * При двойной игре со скольких людей брать оплату
 */
defined('DOUBLE_PRICE_COUNT_PLAYERS') || define('DOUBLE_PRICE_COUNT_PLAYERS', 2);
/**
 *  Берем цену за всех гостей, или за первого
 */
defined('PRICE_BY_ALL_GUEST') || define('PRICE_BY_ALL_GUEST', false);

/**
 *  Какой заголовок для спорта не отображать
 *  варианты :
 *    false  - отображать все
 *    2_7;1_* - перебор вариантов через ; type_sport
 *    если за место спорта или типа * то все возможные значения
 *
 */
defined('NOT_SHOW_TITLE_SPORT_SITE_URL') || define('NOT_SHOW_TITLE_SPORT_SITE_URL', false);

defined('NOT_SHOW_TITLE_TYPE_SPORT_SITE_URL') || define('NOT_SHOW_TITLE_TYPE_SPORT_SITE_URL', false);

/**
 *  Всегда платить хотябы за одного гостя если он не попадает в
 *  периоды оплаты
 */
defined('ALWAYS_PAY_AT_LEAST_FOR_ONE_GUEST') || define('ALWAYS_PAY_AT_LEAST_FOR_ONE_GUEST', true);

/**
 *  На таче на открытых кортах показывать только 1 период :
 *  перечесление area_id;
 *
 *    todo переделать на таблицу club_reservation_rules
 */
defined('USE_SHOW_ONE_AVAILABLE_PERIOD_ON_TOUCH_IN_OPEN_COURT') || define('USE_SHOW_ONE_AVAILABLE_PERIOD_ON_TOUCH_IN_OPEN_COURT', false);
/**
 * прятать нулевые счета на открытых кортах
 */
defined('ADMIN_HIDE_NULL_ACCOUNT') || define('ADMIN_HIDE_NULL_ACCOUNT', true);

/**
 * Новая функция для открытых кортов.
 *  Подтверждение бронирования на тачскрине
 *  значенеи имеет диапозон времени в минутах от начала игры
 *  если игра не была подтверждена то она будет удалена.
 *  Для правильной работы этой функции нужно настроить cron для удаления бронирований
 *  пример крона :
 *  26,56   7-21    *       *       *       /opt/php-5.6/bin/php -q -f /var/www/tcesslingen-tennisbuchung/html/tcesslingen-tennisbuchung.de/at/cron.php removeUnconfirmed
 *  варинаты:
 *    1. false - неиспользовать данную функцию
 *    2. min_max - промежуток по времени до начала игры
 *    3. 0_max или min_60 - крайние значения
 */
defined('CONFIRMATION_OF_RESERVATIONS_VIA_TOUCH_FOR_OPEN') || define('CONFIRMATION_OF_RESERVATIONS_VIA_TOUCH_FOR_OPEN', false);

defined('CORONA_MC_ARENA') || define('CORONA_MC_ARENA', false);
/**
 * прятать в меню открытые/закрытые корты если игрок заходит под гостем open/close
 */
defined('HIDE_GUEST_MENU') || define('HIDE_GUEST_MENU', false);
/**
 * прятать в меню открытые/закрытые корты если игрок заходит под гостем open/close на тачскрине
 */
defined('HIDE_GUEST_MENU_TOUCH') || define('HIDE_GUEST_MENU_TOUCH', false);
/**
 * Открытые вкладки по умолчанию TAGESANSICHT/WOCHENANSICHT при отсутствии TAGESANSICHT
 */
defined('DEFAULT_TAGESANSICHT_WOCHENANSICHT') || define('DEFAULT_TAGESANSICHT_WOCHENANSICHT', false);
/**
 * Гость через наличку на тачскрине
 */
defined('GUEST_BAR_MENU_TOUCH_CLOSE') || define('GUEST_BAR_MENU_TOUCH_CLOSE', false);

/**
 * Гость может платить наличкой
 *  варианты :
 *         touch - только с тачскрина
 *         site  - только с сайта
 *         all   - может платить и с сайта и с тачскрина
 *         false - не может платить
 */
defined('THE_GUEST_CAN_PLAY_IN_CASH') || define('THE_GUEST_CAN_PLAY_IN_CASH', false);

/**
 * Убираем в заголовке списка счетов тип площадки
 */
defined('HIDE_ACCOUNT_TITLE_AREA') || define('HIDE_ACCOUNT_TITLE_AREA', true);

/** Как выводить список игр в счете в админке
 * 0,false - группировать. Не выводить точные даты
 * 1,true - выводить точные даты игр
 */
defined('ACCOUNTS_TYPE_VIEW_ABO') || define('ACCOUNTS_TYPE_VIEW_ABO', false);

/** Прячет в форме авторизованного пользователя ссылки счет, данные пользователя, резервирования
 * Через запятую my_guthaben,my_data,my_booking
 */
defined('HIDE_LINK_AUTH_USER') || define('HIDE_LINK_AUTH_USER', false);

/**
 * Скрыть методы оплаты.
 * Форматы: GH;RE;BR — для всех контекстов;
 * all|GH;RE;BR|10|PP|10_7|PO — отдельные значения для all, type_id и type_id_sport_id.
 * Для type_id_sport_id_area_id значение ищется по каскаду: type_sport_area -> type_sport -> type -> all.
 * PP paypal, GH лицевой счёт, RE по счету, BR наличные, PO payone. Пусто/false — не скрывать.
 * @const HIDE_PAYMENT_METHOD string
 */

defined('HIDE_PAYMENT_METHOD') || define('HIDE_PAYMENT_METHOD', '');
/**
 * Процент стоимости каждой следующей игры нечлена. Игры идут подряд.
 */
defined('PRICE_NEXT_GAME') || define('PRICE_NEXT_GAME', false);
/**
 * Для тачскрина открытых кортов какой таб открывать по умолчанию
 */
defined('TYPE_LOGIN_VIEW_TAB') || define('TYPE_LOGIN_VIEW_TAB', 'codecard');

/**
 * При регистрации устанавливаем один stock_id, id которого REGISTRATION_STOCK_ID
 * false - ничего не устанавливаем
 */
defined('REGISTRATION_STOCK_ID') || define('REGISTRATION_STOCK_ID', false);
/**
 * Четыре кнопки на тачскрине при входе.
 * false - ничего не выводим
 */
defined('TOUCH_SHOW_4BUTTON') || define('TOUCH_SHOW_4BUTTON', false);
/**
 * В счете выводить только спорт в соответствующей колонке
 */
defined('SHOW_ONLY_SPORT') || define('SHOW_ONLY_SPORT', true);
/**
 * В счете не показывать столбец со светом
 */
defined('DONOT_SHOW_LIGHT') || define('DONOT_SHOW_LIGHT', false);

/**
 * При бронировании отправлять пользователю два варианта писем в зависимости от текущего времени
 * LETTER_CORRECT_ALIAS_INTERVAL - шаблон отправляемый в указанном интервале
 * LETTER_CORRECT_ALIAS_REPLACE - шаблон отправляемый по умолчанию
 */
defined('LETTER_CORRECT_ALIAS_INTERVAL') || define('LETTER_CORRECT_ALIAS_INTERVAL', false);
defined('LETTER_CORRECT_ALIAS_REPLACE') || define('LETTER_CORRECT_ALIAS_REPLACE', false);
defined('LETTER_CORRECT_TIME_START') || define('LETTER_CORRECT_TIME_START', '13:00');
defined('LETTER_CORRECT_TIME_STOP') || define('LETTER_CORRECT_TIME_STOP', '14:00');

/**
 * в админке в статистике показывать paypal блок в месячном и дневном отчетах
 */
defined('SHOW_PAYPAL_IN_STAT') || define('SHOW_PAYPAL_IN_STAT', true);

/**
 * Дата выставления счета по резервированиям
 * true - 1
 * false - последнее число месяца
 **/
defined('ADMIN_FIRST_DATE_RESERVATION_ACCOUNT') || define('ADMIN_FIRST_DATE_RESERVATION_ACCOUNT', true);

/**
 * Использовать дату выставления счета по резервированиям как дату создания или старая система (последний или первый день месяца)
 */
defined('USE_DATE_RESERVATION_ACCOUNT_BY_CREATED') || define('USE_DATE_RESERVATION_ACCOUNT_BY_CREATED', true);

/**
 * показывать вкладку Diverse Verkäufe и соответствующий отчет
 */
defined('SHOW_ADMIN_BAR') || define('SHOW_ADMIN_BAR', false);
/**
 *  Какая картинка будет на заднем фоне в админке
 *  4 варианта :
 *  1 - common
 *  2 - tennis
 *  3 - dog
 *  4 - soccer
 *
 */
defined('BACKGROUND_ADMIN') || define('BACKGROUND_ADMIN', 'tennis');
/**
 *  Использовать площадку для теста
 *  пока используем чтобы все письма отправлялись на developer@forumedia.com
 */
defined('TEST_ACTIVE_COURT') || define('TEST_ACTIVE_COURT', false);
/**
 * использовать для каждого счета специальные текстовые поля
 */
defined('USE_ACCOUNT_TEXT_FIELDS') || define('USE_ACCOUNT_TEXT_FIELDS', false);
/**
 * показывать для прошедшего времени что там была игра
 * @todo доработать для всех типов
 */
defined('SHOW_PAST_RESERVATION') || define('SHOW_PAST_RESERVATION', false);
/**
 * задать ндс пользователю по умолчанию при регистрации
 *  например ничлены 19% , члены 7% , по дефолту берем дефолтное значение
 *  1:1|2:2|3:2 - расшифровка club_state_id:nds_id| ....
 */
defined('NDS_DEFAULT_REGISTRATION') || define('NDS_DEFAULT_REGISTRATION', false);

/** Админ может изменять прошедшии бронирования (коментарии к ним) */
defined('ADMIN_EDIT_REQUEST_GONE_TIME') || define('ADMIN_EDIT_REQUEST_GONE_TIME', false);

/** Отправка сообщений о бронировании на разные адреса админам
 *  false - не используется
 */
defined('SEND_ADMIN_TO_DIFFERENT_EMAIL') || define('SEND_ADMIN_TO_DIFFERENT_EMAIL', false);
/**
 *  Новая стратегия при бронировании если цена равна 0, то всегда по счету и не показаны некоторые блоки
 */
defined('USE_NEW_STRATEGY_IF_SUM_NULL') || define('USE_NEW_STRATEGY_IF_SUM_NULL', false);

/** Использовать дополнительно описание при оплате через paypal (название плащадки , дата , время) */
defined('USE_EXTRA_PAYPAL_DESC') || define('USE_EXTRA_PAYPAL_DESC', true);
/**
 * использовать маил в качестве логина
 */
defined('LOGIN_AS_EMAIL') || define('LOGIN_AS_EMAIL', false);

/**
 * На открытых кортах метод оплаты pp.
 */
defined('USE_OPEN_PP') || define('USE_OPEN_PP', false);

/**
 * Использовать дополнительные ссылки в выпадающем блоке главного меню
 *  значения для упрощения
 *  false - не используем
 *  числовое значение - количество ссылок
 */
defined('USE_THIRD_PARTY_LINKS_IN_DROP_DOWN_MENU') || define('USE_THIRD_PARTY_LINKS_IN_DROP_DOWN_MENU', false);

defined('OPEN_MANY_HOURS_IN_DAY') || define('OPEN_MANY_HOURS_IN_DAY', false);
/** Показывать банер актив корт */
defined('VIEW_BANNER_ACTIVE_COURT') || define('VIEW_BANNER_ACTIVE_COURT', true);

/** Показывать данные по sepa */
defined('USE_SEPA') || define('USE_SEPA', true);

/**
 *  Новая глобальная функциональность Членские взносы и все с ней связанное
 *  показывается только если есть открытые корты (пока используется только на них)
 *  сделана константа для включения/ выключения этой функции.
 *  Используется класс конфигурации для возможного дальнейшего переноса данной константы в базу.
 *  По умолчанию эта функция включена для всех открытых кортов.
 */
defined('USE_MEMBERSHIP_FEES') || define('USE_MEMBERSHIP_FEES', true);

/** Показывать комментарии в расписании */
defined('VIEW_COMMENT_SCHEDULE') || define('VIEW_COMMENT_SCHEDULE', false);
/** Использовать только двойную игру - делает ее по умолчанию и убирает выбор одиночной игры */
defined('USE_ONLY_DOUBLE_GAME') || define('USE_ONLY_DOUBLE_GAME', false);

/**
 * промежуток времени в минутах от текущего времени в котором можно забронировать игру ! Для открытых кортов !
 *  для игры член + гость
 */
defined('OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION_V1_PLUS_GUEST') || define('OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION_V1_PLUS_GUEST',
  false);
/** Использовать маску для регистрации и проверка на iban, по умолчанию всегда включено доработал для нее смену маски для iban, по умолчанию для немцев используем*/
defined('USE_TEST_AND_MASK_IBAN') || define('USE_TEST_AND_MASK_IBAN', 'AA 99 9999 9999 9999 9999 99');
/** Показывать в счете GUTHABEN текст */
defined('SHOW_PREPAYMENT_ACCOUNT_TEXT') || define('SHOW_PREPAYMENT_ACCOUNT_TEXT', true);

/** Ввести проверку на заглавные буквы для имени и фамилии при регистрации*/
defined('USE_CHECK_FOR_NAME_SURNAME') || define('USE_CHECK_FOR_NAME_SURNAME', true);

defined("USE_STEP_FORM_REGISTRATION") || define("USE_STEP_FORM_REGISTRATION", false);

/**
 *  Используем для team-garbsen.de
 *  поле firm при регистрации
 *  из типа string делаем select
 *  false - не используем
 *  перечисление элементов через точку с запятой
 */
defined('USE_FIRM_FIELD_REGISTRATION_AS_SELECT') || define('USE_FIRM_FIELD_REGISTRATION_AS_SELECT', false);
/** Сколько минут давать на задержку бронирования по paypal */
defined('COUNT_MINUTE_FOR_PAYPAL') || define('COUNT_MINUTE_FOR_PAYPAL', 1);

/** Максимальное количество групп для членских взносов, false нет ограничений*/
defined('MAX_COUNT_MEMBERSHIP_FEES_GROUPS') || define('MAX_COUNT_MEMBERSHIP_FEES_GROUPS', 15);

/** Регулирует отступ футера в pdf - отчете */
defined('ORDER_PAGE_FOOTER') || define('ORDER_PAGE_FOOTER', 31);

defined('USE_ADDITION_LETTER_TEMPLATE_FOR_COURT') || define('USE_ADDITION_LETTER_TEMPLATE_FOR_COURT', true);
/** Отправлять письмо админу после того как клиент изменил свои данные */
defined('USE_MAIL_ADMIN_AFTER_CHANGE_DATA_CLIENT') || define('USE_MAIL_ADMIN_AFTER_CHANGE_DATA_CLIENT', true);
/**
 * Используем фитнес абонементы?
 * Сделано для tennishalle-burgwedel.de
 *  Если да, то нужно провести ряд операций:
 *  -обновить базу;
 *  -добавить новый тип Fitness и группу к нему, fitness;
 *  -добавить новый спорт для фитнеса;
 *  -добавить хотя бы одну площадку дя этого типа и спорта;
 *  -настроить наценки и минимум максимум таблицу;
 *  -возможно что-то еще:
 * Первоначально работаю для них только абонементы и счета для них
 */
defined('USE_FITNESS_ABO') || define('USE_FITNESS_ABO', false);

/**
 * Новый функционал для опций бронирования - группы опций.
 * Опции можно будет объединять в группы.
 * Группе можно назначать как использовать опции (как чекбоксы или радиокнопки).
 * Так же можно назначать группе какие-то условия - например:
 * отправлять в письме код от шкафчика.
 * Условия придется прописывать(создавать) вручную.
 * По умолчанию этот функционал отключен.
 *
 * Сделано для tennishalle-villingen.de
 *  4 опции (по количеству бронируемых ракеток):
 * [ ] 1 Leihschläger 1,50
 * [ ] 2 Leihschläger 3,00
 * [ ] 3 Leihschläger 4,50
 * [ ] 4 Leihschläger 6,00
 *  с отправкой в письме кода от шкафчика.
 *  Константа показывает эту функцию и меню для админов.
 * Для нас она видна всегда.
 */
defined('SHOW_GROUPS_STOCKS') || define('SHOW_GROUPS_STOCKS', false);

/**
 * Задаем количество символов в дверном коде
 *  по умолчанию 6
 */
defined('NUMBER_CHARACTERS_IN_DOOR_CODES') || define('NUMBER_CHARACTERS_IN_DOOR_CODES', 6);
/**
 * Спрятать имя на дисплее
 */
defined('HIDE_NAME_ON_DISPLAY') || define('HIDE_NAME_ON_DISPLAY', false);
/**
 * После регистрации студент получает статус v2
 */
defined('REGISTER_STUDENT_AS_V2') || define('REGISTER_STUDENT_AS_V2', false);

/** tennishalle-schwuelper.de прятать колонки оплаты в счетах абонементов
 * 0,false - не прятать
 * 1,true - прятать
 */
defined('ABO_ORDER_HIDE_PRICE') || define('ABO_ORDER_HIDE_PRICE', false);

/**
 * tennisbuchung-exter.de
 * убираем возможность бронирования на недельном (убираем ссылки)
 */
defined('HIDE_URL_WOCHENANSICHT') || define('HIDE_URL_WOCHENANSICHT', false);

/**
 * tennis-st-mauritz-muenster.de
 * переименовываем названия кортов
 */
defined('DISPLAY_RENAME') || define('DISPLAY_RENAME', false);

/**
 * Отправлять переформированный файл на ftp используем для макарен
 */
defined('SEND_FTP_ICAL_FILE') || define('SEND_FTP_ICAL_FILE', false);

defined('FTP_HOST') || define('FTP_HOST', false);
defined('FTP_USER') || define('FTP_USER', false);
defined('FTP_PWD') || define('FTP_PWD', false);
defined('FTP_DIR') || define('FTP_DIR', false);
/** Показывать список месяцев в списке счетов */
defined('USE_MONTHS_LIST_MENU_IN_ACCOUNTS') || define('USE_MONTHS_LIST_MENU_IN_ACCOUNTS', true);
/** Показывать список месяцев в списке счетов не только для архивов счетов */
defined('USE_LIST_MENU_IN_ACCOUNTS_NOT_ARCHIVE') || define('USE_LIST_MENU_IN_ACCOUNTS_NOT_ARCHIVE', true);


/**
 *  Использовать платежную систему payone
 *  заменяет paypal
 */
defined('USE_PAYONE_PAYMENT') || define('USE_PAYONE_PAYMENT', false);
/**
 * Использовать как способ оплаты по умолчанию
 * значения:
 *    BR - наличкой
 *    RE - по счету
 *    GH - лицевой счет
 *    PP - paypal
 *    PO - payone
 *    false - не использовать
 *  на данный момент смотрим какой способ выбран у клиента первые 3 (они в порядке приоритета)
 *  в дальнейшем хотят чтобы все оплаты были с внутреннего счета, на сколько хватает и доп оплата с других
 * Форматы: PP — для всех контекстов; 1_2|PP — для type_id=1, sport_id=2;
 * all|BR|1|GH|1_2|PP — BR по умолчанию, GH для type_id=1 и PP для контекста 1_2.
 * Для type_id_sport_id_area_id значение ищется по каскаду: type_sport_area -> type_sport -> type -> all.
 * @const DEFAULT_PAYMENT_METHOD string|false
 * делаю для ck у них payone по умолчанию
 */

defined('DEFAULT_PAYMENT_METHOD') || define('DEFAULT_PAYMENT_METHOD', false);

/**
 * Использовать логирование всех этапов онлайн оплаты.
 * Сейчас используется только на payone.
 */
defined('USE_PAY_ONLINE_LOG') || define('USE_PAY_ONLINE_LOG', false);

/**
 * Использование защищенных заголовков
 */
defined('USE_HEADERS') || define('USE_HEADERS', false);

/**
 * false - дефолтно
 * 2_1:300|1_1:10 формат  - type_sport:количество дней
 */
defined('PERIOD_SHOW_CALENDAR') || define('PERIOD_SHOW_CALENDAR', false);

// Швейцарская раскладка счета: блок организации слева, блок клиента справа; текст выровнен в блоках слева.
defined('USE_SWISS_ENVELOPE_LAYOUT') || define('USE_SWISS_ENVELOPE_LAYOUT', false);
