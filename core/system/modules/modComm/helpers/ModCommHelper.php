<?php

namespace AC\core\system\modules\modComm\helpers;

use AC\core\system\modules\modComm\exceptions\ModCommException;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;
use AC\core\system\modules\modComm\services\ModCommService;
use AC\core\system\service\history\HistoryService;

/**
 * Хелпер для упрощения работы с системой межмодульного общения
 *
 * Этот класс предоставляет набор статических методов для упрощения
 * работы с системой межмодульного взаимодействия. Включает:
 * - Быстрые методы для CRUD операций (get, create, update, delete)
 * - Автоматическую обработку ошибок и исключений
 * - Создание типовых ответов (success, error, notFound и т.д.)
 * - Утилитарные методы для работы с запросами и ответами
 * - Доступ к истории операций и сервису modComm
 *
 * Все методы являются статическими и не требуют создания экземпляра класса.
 *
 * @package AC\core\system\modules\modComm\helpers
 * @since   1.0.0
 */
class ModCommHelper
{
  /**
   * Кэшированный экземпляр сервиса modComm
   *
   * @var ModCommService|null Экземпляр сервиса или null если не инициализирован
   */
  private static ?ModCommService $service = null;
  
  /**
   * Получить экземпляр сервиса общения
   *
   * Ленивая инициализация сервиса modComm при первом обращении.
   * Использует паттерн Singleton для обеспечения единственного экземпляра.
   *
   * @return ModCommService Экземпляр сервиса modComm
   */
  private static function getService(): ModCommService
  {
    if (!self::$service) {
      self::$service = ModCommService::getInstance();
    }
    
    return self::$service;
  }
  
  /**
   * Быстрый запрос к модулю
   *
   * Внутренний метод для выполнения запросов к модулям
   * через сервис modComm. Используется другими публичными методами.
   *
   * @param string $module  Имя целевого модуля
   * @param string $action  Название действия для выполнения
   * @param array  $data    Данные для передачи в модуль
   * @param array  $options Дополнительные опции запроса
   * @return ModCommResponse Ответ от модуля
   * @throws ModCommException При ошибках в процессе выполнения запроса
   */
  private static function call(string $module, string $action, array $data = [], array $options = []): ModCommResponse
  {
    return self::getService()->sendRequest($module, $action, $data, $options);
  }
  
  /**
   * Быстрый запрос с автоматической обработкой ошибок
   *
   * Выполняет запрос к модулю и автоматически обрабатывает
   * возможные исключения, возвращая стандартный ответ об ошибке.
   * Логирует все ошибки в историю сервиса.
   *
   * @param string $module  Имя целевого модуля
   * @param string $action  Название действия для выполнения
   * @param array  $data    Данные для передачи в модуль
   * @param array  $options Дополнительные опции запроса
   * @return ModCommResponse Ответ от модуля или ответ об ошибке
   */
  public static function callSafe(string $module, string $action, array $data = [], array $options = []): ModCommResponse
  {
    try {
      return self::call($module, $action, $data, $options);
    } catch (ModCommException $e) {
      self::getService()->history()?->addError(lang('module_communication_error', 'modComm'), [
        'request' => 'ModCommHelper::callSafe',
        'module'  => $module,
        'action'  => $action,
        'data'    => $data,
        'option'  => $options,
      ], $e);
      
      return ModCommResponse::badRequest();
    }
  }
  
  /**
   * Получение данных с автоматическим извлечением значения
   *
   * Выполняет безопасный запрос к модулю и возвращает:
   * - Все данные ответа если ключ не указан
   * - Конкретное значение по ключу если указан
   * - Значение по умолчанию при ошибке или отсутствии ключа
   *
   * @param string      $module  Имя целевого модуля
   * @param string      $action  Название действия для выполнения
   * @param array       $data    Данные для передачи в модуль
   * @param string|null $key     Ключ для извлечения конкретного значения
   * @param mixed|null  $default Значение по умолчанию при ошибке
   * @return mixed Данные ответа, конкретное значение или значение по умолчанию
   */
  public static function get(string $module, string $action, array $data = [], ?string $key = null, mixed $default = null): mixed
  {
    $result = self::callSafe($module, $action, $data, [], null);
    
    if (!$result->isSuccess()) {
      return $default;
    }
    
    if ($key === null) {
      return $result->getData();
    }
    
    return $result->getData()[$key] ?? $default;
  }
  
  /**
   * Проверка существования данных
   *
   * Выполняет безопасный запрос к модулю и проверяет:
   * - Наличие любых данных в ответе если ключ не указан
   * - Существование конкретного ключа в данных ответа
   * - Возвращает false при ошибке выполнения запроса
   *
   * @param string      $module Имя целевого модуля
   * @param string      $action Название действия для выполнения
   * @param array       $data   Данные для передачи в модуль
   * @param string|null $key    Ключ для проверки существования
   * @return bool Возвращает true если данные существуют
   */
  public static function exists(string $module, string $action, array $data = [], ?string $key = null): bool
  {
    $result = self::callSafe($module, $action, $data, [], null);
    
    if (!$result->isSuccess()) {
      return false;
    }
    
    if ($key === null) {
      return !empty($result->getData());
    }
    
    return isset($result->getData()[$key]);
  }
  
  /**
   * Создание данных с автоматической обработкой результата
   *
   * Выполняет безопасный запрос на создание данных и возвращает:
   * - ID созданного элемента если операция успешна
   * - null при ошибке или отсутствии ключа ID
   *
   * По умолчанию ищет ключ 'id', но можно указать другой ключ.
   *
   * @param string $module Имя целевого модуля
   * @param string $action Название действия для создания
   * @param array  $data   Данные для создания элемента
   * @param string $idKey  Ключ для извлечения ID созданного элемента
   * @return int|string|null ID созданного элемента или null
   */
  public static function create(string $module, string $action, array $data, string $idKey = 'id'): int|string|null
  {
    $result = self::callSafe($module, $action, $data, [], null);
    
    if ($result->isSuccess() && isset($result->getData()[$idKey])) {
      return $result->getData()[$idKey];
    }
    
    return null;
  }
  
  /**
   * Обновление данных
   *
   * Выполняет безопасный запрос на обновление данных и проверяет:
   * - Успешность выполнения операции
   * - Наличие данных в ответе (подтверждение обновления)
   *
   * Возвращает true только если обновление выполнено успешно.
   *
   * @param string $module Имя целевого модуля
   * @param string $action Название действия для обновления
   * @param array  $data   Данные для обновления элемента
   * @return bool Возвращает true если обновление выполнено успешно
   */
  public static function update(string $module, string $action, array $data): bool
  {
    $result = self::callSafe($module, $action, $data, [], null);
    return $result->isSuccess() && !empty($result->getData());
  }
  
  /**
   * Удаление данных
   *
   * Выполняет безопасный запрос на удаление данных и проверяет:
   * - Успешность выполнения операции
   * - Наличие данных в ответе (подтверждение удаления)
   *
   * Возвращает true только если удаление выполнено успешно.
   *
   * @param string $module Имя целевого модуля
   * @param string $action Название действия для удаления
   * @param array  $data   Данные для идентификации удаляемого элемента
   * @return bool Возвращает true если удаление выполнено успешно
   */
  public static function delete(string $module, string $action, array $data): bool
  {
    $result = self::callSafe($module, $action, $data, [], null);
    return $result->isSuccess() && !empty($result->getData());
  }
  
  /**
   * Создание запроса с предустановленными параметрами
   *
   * Создает объект ModCommRequest с указанными параметрами.
   * Позволяет подготовить запрос для последующей отправки
   * или модификации перед отправкой.
   *
   * @param string $module  Имя целевого модуля
   * @param string $action  Название действия для выполнения
   * @param array  $data    Данные для передачи в модуль
   * @param array  $options Дополнительные опции запроса
   * @return ModCommRequest Объект запроса с указанными параметрами
   */
  public static function createRequest(string $module, string $action, array $data = [], array $options = []): ModCommRequest
  {
    return new ModCommRequest($module, $action, $data, $options);
  }
  
  /**
   * Отправка запроса с дополнительными опциями
   *
   * Отправляет готовый объект запроса через сервис modComm.
   * Извлекает параметры из объекта запроса и передает их сервису.
   *
   * @param ModCommRequest $request Объект запроса для отправки
   * @return ModCommResponse Ответ от целевого модуля
   * @throws ModCommException При ошибках в процессе выполнения запроса
   */
  public static function sendRequest(ModCommRequest $request): ModCommResponse
  {
    return self::getService()->sendRequest(
      $request->getTargetModule(),
      $request->getAction(),
      $request->getData(),
      $request->getOptions()
    );
  }
  
  /**
   * Создание успешного ответа
   *
   * Создает объект ModCommResponse с флагом успеха и кодом 200.
   * Удобный способ создания стандартного успешного ответа.
   *
   * @param array  $data     Данные для включения в ответ
   * @param string $message  Сообщение об успехе
   * @param array  $metadata Дополнительные метаданные
   * @return ModCommResponse Объект успешного ответа
   */
  public static function success(array $data = [], string $message = 'Success', array $metadata = []): ModCommResponse
  {
    return ModCommResponse::success($data, $message, $metadata);
  }
  
  /**
   * Создание ответа с ошибкой
   *
   * Создает объект ModCommResponse с флагом ошибки и указанным
   * HTTP-кодом статуса. Удобный способ создания стандартного ответа об ошибке.
   *
   * @param string $message    Сообщение об ошибке
   * @param int    $statusCode HTTP-код статуса ошибки
   * @param array  $data       Дополнительные данные об ошибке
   * @param array  $metadata   Дополнительные метаданные
   * @return ModCommResponse Объект ответа с ошибкой
   */
  public static function error(string $message = 'Error', int $statusCode = 500, array $data = [], array $metadata = []): ModCommResponse
  {
    return ModCommResponse::error($message, $statusCode, $data, $metadata);
  }
  
  /**
   * Создание ответа "не найдено"
   *
   * Создает объект ModCommResponse с кодом 404 для случаев,
   * когда запрашиваемый ресурс не найден.
   *
   * @param string $message Сообщение об ошибке "не найдено"
   * @param array  $data    Дополнительные данные об ошибке
   * @return ModCommResponse Объект ответа "не найдено"
   */
  public static function notFound(string $message = 'Not Found', array $data = []): ModCommResponse
  {
    return ModCommResponse::notFound($message, $data);
  }
  
  /**
   * Создание ответа "не авторизован"
   *
   * Создает объект ModCommResponse с кодом 401 для случаев,
   * когда пользователь не авторизован для выполнения операции.
   *
   * @param string $message Сообщение об ошибке авторизации
   * @param array  $data    Дополнительные данные об ошибке
   * @return ModCommResponse Объект ответа "не авторизован"
   */
  public static function unauthorized(string $message = 'Unauthorized', array $data = []): ModCommResponse
  {
    return ModCommResponse::unauthorized($message, $data);
  }
  
  /**
   * Создание ответа "запрещено"
   *
   * Создает объект ModCommResponse с кодом 403 для случаев,
   * когда доступ к ресурсу запрещен.
   *
   * @param string $message Сообщение об ошибке доступа
   * @param array  $data    Дополнительные данные об ошибке
   * @return ModCommResponse Объект ответа "запрещено"
   */
  public static function forbidden(string $message = 'Forbidden', array $data = []): ModCommResponse
  {
    return ModCommResponse::forbidden($message, $data);
  }
  
  /**
   * Создание ответа "неверный запрос"
   *
   * Создает объект ModCommResponse с кодом 400 для случаев,
   * когда входящий запрос некорректен или содержит ошибки.
   *
   * @param string $message Сообщение об ошибке запроса
   * @param array  $data    Дополнительные данные об ошибке
   * @return ModCommResponse Объект ответа "неверный запрос"
   */
  public static function badRequest(string $message = 'Bad Request', array $data = []): ModCommResponse
  {
    return ModCommResponse::badRequest($message, $data);
  }
  
  /**
   * Возвращает историю запросов.
   *
   * Предоставляет доступ к сервису истории для логирования
   * операций и получения информации о выполненных запросах.
   *
   * @return HistoryService Сервис истории запросов
   */
  public static function history(): HistoryService
  {
    return self::getService()->history();
  }
  
  /**
   * Возвращает префикс имени для директории модульной коммуникации (modComm).
   *
   * Утилитарный метод для получения имени директории modComm
   * в различных форматах в зависимости от контекста использования.
   *
   * @param bool $asNamespace Если true, возвращает имя с заглавной буквы (как пространство имён),
   *                          иначе — как обычное строковое значение.
   * @return string Префикс имени директории modComm
   */
  public static function getModCommNamePrefix(bool $asNamespace = false): string
  {
    return $asNamespace ? ucfirst(paths()::MOD_COMM_DIR) : paths()::MOD_COMM_DIR;
  }
}
