<?php

namespace AC\core\system\modules\modComm\exceptions;

use AC\core\system\exceptions\BaseException;

/**
 * Исключение для ошибок межмодульного общения
 *
 * Этот класс расширяет базовое исключение системы и предоставляет
 * специализированные типы исключений для различных ошибок,
 * возникающих в процессе межмодульного взаимодействия:
 * - Модуль не найден или не зарегистрирован
 * - Действие не поддерживается модулем
 * - Ошибки авторизации и доступа
 * - Ошибки валидации параметров
 * - Внутренние ошибки модулей
 *
 * Каждый тип исключения имеет соответствующий HTTP-код статуса
 * для корректной обработки на уровне API.
 *
 * @package AC\core\system\modules\modComm\exceptions
 * @since   1.0.0
 * @extends BaseException
 */
class ModCommException extends BaseException
{
  /**
   * Создать исключение для несуществующего модуля
   *
   * Выбрасывается когда модуль не найден в системе или
   * не зарегистрирован для участия в межмодульном взаимодействии.
   *
   * @param string $moduleName Имя модуля, который не был найден
   * @return self Новый экземпляр исключения с кодом 404
   */
  public static function moduleNotFound(string $moduleName): self
  {
    return new self(
      "Module '{$moduleName}' not found or not registered for communication",
      404,
      null
    );
  }
  
  /**
   * Создать исключение для неподдерживаемого действия
   *
   * Выбрасывается когда модуль не поддерживает запрашиваемое
   * действие или метод не реализован в модуле.
   *
   * @param string $moduleName Имя модуля, который не поддерживает действие
   * @param string $action     Название действия, которое не поддерживается
   * @return self Новый экземпляр исключения с кодом 400
   */
  public static function actionNotSupported(string $moduleName, string $action): self
  {
    return new self(
      "Action '{$action}' is not supported by module '{$moduleName}'",
      400,
      null
    );
  }
  
  /**
   * Создать исключение для ошибки авторизации
   *
   * Выбрасывается когда у пользователя недостаточно прав
   * для выполнения указанного действия в модуле.
   *
   * @param string $moduleName Имя модуля, в котором произошла ошибка авторизации
   * @param string $action     Название действия, к которому нет доступа
   * @return self Новый экземпляр исключения с кодом 401
   */
  public static function unauthorized(string $moduleName, string $action): self
  {
    return new self(
      "Unauthorized access to action '{$action}' in module '{$moduleName}'",
      401,
      null
    );
  }
  
  /**
   * Создать исключение для ошибки валидации
   *
   * Выбрасывается когда входящие параметры не прошли валидацию
   * или не соответствуют требованиям модуля.
   *
   * @param string        $moduleName       Имя модуля, в котором произошла ошибка валидации
   * @param string        $action           Название действия с невалидными параметрами
   * @param array<string> $validationErrors Массив сообщений об ошибках валидации
   * @return self Новый экземпляр исключения с кодом 400
   */
  public static function validationError(string $moduleName, string $action, array $validationErrors): self
  {
    return new self(
      "Validation error for action '{$action}' in module '{$moduleName}': " . json_encode($validationErrors),
      400,
      null
    );
  }
  
  /**
   * Создать исключение для внутренней ошибки модуля
   *
   * Выбрасывается когда в модуле произошла внутренняя ошибка
   * во время выполнения запрошенного действия.
   *
   * @param string $moduleName Имя модуля, в котором произошла внутренняя ошибка
   * @param string $action     Название действия, при выполнении которого произошла ошибка
   * @param string $error      Описание внутренней ошибки модуля
   * @return self Новый экземпляр исключения с кодом 500
   */
  public static function internalError(string $moduleName, string $action, string $error): self
  {
    return new self(
      "Internal error in module '{$moduleName}' for action '{$action}': {$error}",
      500,
      null
    );
  }
}
