<?php

namespace AC\core\system\modules\modComm\controllers;

use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;
use AC\core\system\service\history\HistoryTrait;
use Service;

/**
 * Базовый контроллер для модульного общения.
 *
 * Этот контроллер предоставляет базовую инфраструктуру для обработки
 * межмодульных запросов, включая:
 * - Автоматическую валидацию входящих параметров
 * - Систему авторизации и контроля доступа
 * - Логирование всех операций и ошибок
 * - Управление жизненным циклом запросов
 * - Базовые методы для получения информации о контроллере
 *
 * От этого класса должны наследоваться все контроллеры, которые
 * поддерживают межмодульное взаимодействие через систему modComm.
 *
 * @package AC\core\system\modules\modComm\controllers
 * @since   1.0.0
 */
class ModCommController
{
  use HistoryTrait;
  
  /**
   * Карта доступных методов с их конфигурацией
   *
   * Каждый метод описывается массивом с параметрами:
   * - description: описание назначения метода
   * - requires_auth: требуется ли авторизация
   * - required_params: обязательные параметры
   * - optional_params: опциональные параметры
   *
   * @var array<string, array{
   *   description?: string,
   *   requires_auth?: bool,
   *   required_params?: array<string>,
   *   optional_params?: array<string>
   * }> Карта доступных методов
   */
  protected array $availableMethods = [];
  
  /**
   * Правила авторизации для методов контроллера
   *
   * Определяет, какие роли или уровни доступа требуются
   * для выполнения конкретных методов. Поддерживаемые значения:
   * - 'public': публичный доступ без ограничений
   * - 'authenticated': требуется аутентификация
   * - 'admin': требуется права администратора
   *
   * @var array<string, array<string>> Правила авторизации по методам
   */
  protected array $authorizationRules = [];
  
  /**
   * Правила валидации параметров для методов
   *
   * Описывает требования к входящим параметрам каждого метода:
   * - type: ожидаемый тип данных
   * - max_length: максимальная длина для строк
   * - min_length: минимальная длина для строк
   * - optional: является ли параметр опциональным
   *
   * @var array<string, array<string, array{
   *   type?: string,
   *   max_length?: int,
   *   min_length?: int,
   *   optional?: bool
   * }>> Правила валидации по методам
   */
  protected array $validationRules = [];
  
  /**
   * Конструктор базового контроллера
   *
   * Инициализирует историю событий контроллера и настраивает
   * базовые методы, доступные во всех контроллерах modComm.
   */
  public function __construct()
  {
    $this->initHistory('modCommController')->addHistoryEvent('initController', "Init controller '" . get_class($this) . "'");
    $this->initializeBaseMethods();
    $this->initializeCustomMethods();
  }
  
  /**
   * Инициализация базовых методов контроллера
   *
   * Настраивает стандартные методы, доступные во всех контроллерах:
   * - getInfo: получение информации о контроллере
   * - getAvailableMethodsList: список доступных методов
   * - ping: проверка доступности контроллера
   *
   * @return void
   */
  protected function initializeBaseMethods(): void
  {
//    $this->availableMethods = [
//      'getInfo'                 => [
//        'description'     => 'Get controller information',
//        'requires_auth'   => false,
//        'required_params' => [],
//        'optional_params' => []
//      ],
//      'getAvailableMethodsList' => [
//        'description'     => 'Get list of available methods',
//        'requires_auth'   => false,
//        'required_params' => [],
//        'optional_params' => []
//      ],
//      'ping'                    => [
//        'description'     => 'Check controller availability',
//        'requires_auth'   => false,
//        'required_params' => [],
//        'optional_params' => ['message']
//      ]
//    ];
    
    $this->authorizationRules = [
      'getInfo'                 => ['public'],
      'getAvailableMethodsList' => ['public'],
      'ping'                    => ['public']
    ];
    
    $this->validationRules = [
      'ping' => [
        'message' => ['type' => 'string', 'max_length' => 255, 'optional' => true]
      ]
    ];
  }
  
  /**
   * Инициализация пользовательских методов в потомках
   *
   * Этот метод предназначен для переопределения в дочерних классах
   * для настройки специфичных методов конкретного контроллера.
   * Здесь можно добавить методы, правила авторизации и валидации.
   *
   * @return void
   */
  protected function initializeCustomMethods(): void
  {
    // Переопределяется в потомках
  }
  
  /**
   * Обработка запроса к контроллеру
   *
   * Основной метод для обработки входящих запросов. Выполняет:
   * - Валидацию существования запрашиваемого метода
   * - Проверку авторизации пользователя
   * - Валидацию входящих параметров
   * - Вызов соответствующего метода
   * - Логирование результата или ошибки
   * - Измерение времени выполнения
   *
   * @param ModCommRequest $request Объект входящего запроса
   * @return ModCommResponse Структурированный ответ с результатом
   * @throws \Exception При критических ошибках в процессе обработки
   */
  public function handleRequest(ModCommRequest $request): ModCommResponse
  {
    $startTime = microtime(true);
    $method    = $request->getAction();
    $this->startOperationInHistory([
      'message'          => 'Incoming modCommController request',
      'request_id'       => $request->getRequestId(),
      'module_name'      => $request->getModuleName(),
      'action'           => $request->getAction(),
      'request_data'     => $request->getData(),
      'request_metadata' => $request->getMetadata(),
      'user_id'          => Service::auth()->getUserId(),
      'isAdmin'          => Service::auth()->isAdmin(),
      'timestamp'        => $request->getTimestamp()
    ]);
    try {
      // Проверка существования метода
      if (!$this->hasMethod($method)) {
        $this->addErrorInHistory("Method '{$method}' not found");
        
        return ModCommHelper::error("Method '{$method}' not found", 404);
      }
      
      // Проверка авторизации
      if (!$this->checkAuthorization($method, $request)) {
        $this->addErrorInHistory("Unauthorized access to method '{$method}'");
        
        return ModCommHelper::unauthorized("Insufficient permissions for method '{$method}'");
      }
      
      // Валидация параметров
      $validationResult = $this->validateParameters($method, $request);
      if (!$validationResult['valid']) {
        $this->addErrorInHistory("Validation failed for method '{$method}': " . implode(', ', $validationResult['errors']));
        
        return ModCommHelper::badRequest("Validation failed: " . implode(', ', $validationResult['errors']));
      }
      
      $return         = $this->callMethod($method, $request);
      $processingTime = microtime(true) - $startTime;
      
      $this->addHistoryEvent('methodCalled', "Method '{$method}' called");
      
      $this->endOperationInHistory([
        'status'          => 'success',
        'processing_time' => $processingTime,
        'response_status' => $return->getStatusCode()
      ]);
      
      return $return;
    } catch (\Exception $e) {
      $processingTime = microtime(true) - $startTime;
      
      $this->addErrorInHistory("Internal error in modCommController request: " . $e->getMessage());
      
      $this->endOperationInHistory([
        'status'          => 'error',
        'error'           => 'internal_error',
        'processing_time' => $processingTime,
        'error_message'   => $e->getMessage()
      ]);
      
      return ModCommHelper::error("Internal error: " . $e->getMessage(), 500);
    }
  }
  
  /**
   * Проверка существования метода в контроллере
   *
   * Проверяет, что запрашиваемый метод существует и доступен
   * для вызова. Учитывает как наличие метода в классе, так и
   * его доступность согласно настройкам контроллера.
   *
   * @param string $method Название метода для проверки
   * @return bool Возвращает true если метод существует и доступен
   */
  public function hasMethod(string $method): bool
  {
    return method_exists($this, $method) && $this->checkMethodAvailability($method);
  }
  
  /**
   * Проверка доступности метода согласно настройкам
   *
   * Проверяет, доступен ли метод для вызова согласно
   * внутренним настройкам контроллера. В базовой реализации
   * всегда возвращает true, но может быть переопределен
   * в дочерних классах для более сложной логики.
   *
   * @param string $method Название метода для проверки
   * @return bool Возвращает true если метод доступен
   */
  public function checkMethodAvailability(string $method): bool
  {
    // Проверка наличия метода в карте доступных по не используется
    
    return true;
  }
  
  /**
   * Проверка авторизации для метода
   *
   * Проверяет, имеет ли текущий пользователь право на выполнение
   * указанного метода. Учитывает правила авторизации, определенные
   * для конкретного метода в контроллере.
   *
   * @param string         $method  Название метода для проверки авторизации
   * @param ModCommRequest $request Объект запроса с данными пользователя
   * @return bool Возвращает true если доступ разрешен
   */
  public function checkAuthorization(string $method, ModCommRequest $request): bool
  {

//    if (!isset($this->authorizationRules[$method])) {
//      return true; // Если правила не заданы, разрешаем доступ
//    }
//
//    $rules = $this->authorizationRules[$method];
//
//    // Если метод публичный
//    if (in_array('public', $rules)) {
//      return true;
//    }
    
    // Проверка авторизации пользователя
    return $this->isUserAuthorized($request);
  }
  
  /**
   * Проверка авторизации пользователя
   *
   * Базовая реализация проверки авторизации пользователя.
   * В базовом контроллере всегда разрешает доступ, но может
   * быть переопределен в дочерних классах для реализации
   * конкретной логики авторизации.
   *
   * @param ModCommRequest $request Объект запроса с данными пользователя
   * @return bool Возвращает true если пользователь авторизован
   */
  protected function isUserAuthorized(ModCommRequest $request): bool
  {
    // Базовая реализация - всегда разрешает доступ
    // Переопределяется в потомках для конкретной логики авторизации
    return true;
  }
  
  /**
   * Валидация параметров для метода
   *
   * Проверяет входящие параметры согласно правилам валидации,
   * определенным для конкретного метода. Поддерживает проверки:
   * - Обязательности параметров
   * - Типов данных
   * - Длины строк
   * - Других ограничений
   *
   * @param string         $method  Название метода для валидации параметров
   * @param ModCommRequest $request Объект запроса с параметрами
   * @return array{valid: bool, errors: array<string>} Результат валидации
   */
  protected function validateParameters(string $method, ModCommRequest $request): array
  {
    if (!isset($this->validationRules[$method])) {
      
      return ['valid' => true, 'errors' => []];
    }
    
    $rules  = $this->validationRules[$method];
    $errors = [];
    $data   = $request->getData();
    
    foreach ($rules as $param => $rule) {
      $value = $data[$param] ?? null;
      
      // Проверка обязательности параметра
      if (!isset($rule['optional']) || !$rule['optional']) {
        if (!isset($data[$param])) {
          $errors[] = "Required parameter '{$param}' is missing";
          continue;
        }
      }
      
      // Если параметр не передан и он опциональный, пропускаем
      if (!isset($data[$param]) && isset($rule['optional']) && $rule['optional']) {
        continue;
      }
      
      // Проверка типа
      if (isset($rule['type'])) {
        if (!$this->validateType($value, $rule['type'])) {
          $errors[] = "Parameter '{$param}' must be of type '{$rule['type']}'";
        }
      }
      
      // Проверка максимальной длины для строк
      if (isset($rule['max_length']) && is_string($value) && strlen($value) > $rule['max_length']) {
        $errors[] = "Parameter '{$param}' exceeds maximum length of {$rule['max_length']}";
      }
      
      // Проверка минимальной длины для строк
      if (isset($rule['min_length']) && is_string($value) && strlen($value) < $rule['min_length']) {
        $errors[] = "Parameter '{$param}' must be at least {$rule['min_length']} characters long";
      }
    }
    
    return [
      'valid'  => empty($errors),
      'errors' => $errors
    ];
  }
  
  /**
   * Валидация типа значения
   *
   * Проверяет, соответствует ли значение ожидаемому типу данных.
   * Поддерживает все основные типы PHP и некоторые специальные случаи.
   *
   * @param mixed  $value Значение для проверки типа
   * @param string $type  Ожидаемый тип данных
   * @return bool Возвращает true если тип соответствует ожидаемому
   */
  protected function validateType(mixed $value, string $type): bool
  {
    return match ($type) {
      'string'  => is_string($value),
      'integer' => is_int($value),
      'float'   => is_float($value) || is_numeric($value),
      'boolean' => is_bool($value),
      'array'   => is_array($value),
      'object'  => is_object($value),
      default   => true,
    };
  }
  
  /**
   * Вызов метода контроллера
   *
   * Безопасно вызывает указанный метод контроллера с передачей
   * объекта запроса. Проверяет существование метода перед вызовом
   * и возвращает ошибку если метод не реализован.
   *
   * @param string         $method  Название метода для вызова
   * @param ModCommRequest $request Объект запроса для передачи в метод
   * @return ModCommResponse Результат выполнения метода
   */
  protected function callMethod(string $method, ModCommRequest $request): ModCommResponse
  {
    if (!method_exists($this, $method)) {
      $this->addErrorInHistory("Method '{$method}' not found");
      
      return ModCommHelper::error("Method '{$method}' not implemented", 501);
    }
    
    return $this->$method($request);
  }
  
  /**
   * Добавить новый метод в список доступных
   *
   * Регистрирует новый метод в карте доступных методов контроллера
   * с указанной конфигурацией. Это позволяет динамически настраивать
   * доступность и параметры методов.
   *
   * @param string $method Название метода для добавления
   * @param array  $config Конфигурация метода (описание, параметры и т.д.)
   * @return void
   */
  protected function addMethod(string $method, array $config): void
  {
    $this->availableMethods[$method] = $config;
  }
  
  /**
   * Добавить правило авторизации для метода
   *
   * Устанавливает правила авторизации для конкретного метода.
   * Правила определяют, какие роли или уровни доступа требуются
   * для выполнения метода.
   *
   * @param string        $method Название метода для установки правил
   * @param array<string> $rules  Массив правил авторизации
   * @return void
   */
  protected function addAuthorizationRule(string $method, array $rules): void
  {
    $this->authorizationRules[$method] = $rules;
  }
  
  /**
   * Добавить правило валидации для метода
   *
   * Устанавливает правила валидации параметров для конкретного метода.
   * Правила определяют требования к типам, длине и обязательности
   * входящих параметров.
   *
   * @param string $method Название метода для установки правил валидации
   * @param array  $rules  Массив правил валидации параметров
   * @return void
   */
  protected function addValidationRule(string $method, array $rules): void
  {
    $this->validationRules[$method] = $rules;
  }
}
