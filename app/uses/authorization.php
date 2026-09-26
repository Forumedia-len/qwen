<?php

/** Количество попыток авторизации */
defined('NUMBER_OF_ATTEMPTS') || define('NUMBER_OF_ATTEMPTS', 5);

/** Время блокировки авторизации после неудачных попыток в секундах */
defined('LOCK_TIME_AUTH') || define('LOCK_TIME_AUTH', 60*2);

/** Время жизни кода для двухэтапной авторизации */
defined('NUMBER_SECOND_LIFE_CODE_2FA') || define('NUMBER_SECOND_LIFE_CODE_2FA', 60);

/** Время жизни страницы для ввода кода для двухэтапной авторизации */
defined('NUMBER_SECOND_LIFE_PAGE_SEND_CODE_2FA') || define('NUMBER_SECOND_LIFE_PAGE_SEND_CODE_2FA', 60 * 2);

/** Количество попыток ввода кода из email при авторизации  */
defined('NUMBER_OF_ATTEMPTS_2FA') || define('NUMBER_OF_ATTEMPTS_2FA', 3);
/** Проверять Email админов на уникальность */
defined('CHECK_EMAIL_ADMIN_UNIQUE') || define('CHECK_EMAIL_ADMIN_UNIQUE', true);
