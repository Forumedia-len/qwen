<?php

namespace AC\core\system\modules\modComm;

use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\exceptions\ModCommException;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;
use AC\core\system\service\history\HistoryTrait;
use Exception;
use Service;

/**
 * Модуль ModCommModule реализует логику обработки запросов и взаимодействия с другими модулями.
 *
 * Этот модуль является центральным компонентом системы межмодульного взаимодействия,
 * обеспечивающим:
 * - Обработку входящих запросов от других модулей
 * - Маршрутизацию запросов к соответствующим контроллерам
 * - Логирование всех операций и ошибок
 * - Управление жизненным циклом запросов
 *
 * Модуль поддерживает работу с контроллерами для организации бизнес-логики
 * и автоматически загружает необходимые компоненты системы.
 *
 * @package AC\core\system\modules\modComm
 * @since   1.0.0
 * @implements ModCommModuleInterface
 */
class ModCommModule implements ModCommModuleInterface
{
  use HistoryTrait;
  
  /**
   * Текущий входящий запрос для обработки
   *
   * @var ModCommRequest|null Объект текущего запроса или null если запрос не установлен
   */
  private ?ModCommRequest $request = null;
  
  /**
   * Конструктор модуля.
   *
   * Инициализирует историю событий модуля и создает начальную запись
   * о запуске модуля в системе.
   */
  public function __construct()
  {
    $this->initHistory('modCommModule')->addHistoryEvent('initModule', 'ModCommModule init');
  }
  
  /**
   * Обрабатывает входящий модульный запрос.
   *
   * Основной метод для обработки запросов от других модулей системы.
   * Выполняет следующие операции:
   * - Валидацию входящего запроса
   * - Логирование начала обработки
   * - Делегирование обработки соответствующему контроллеру
   * - Измерение времени выполнения
   * - Логирование результата или ошибки
   *
   * @param ModCommRequest $request Входящий запрос от другого модуля
   * @return ModCommResponse Структурированный ответ с результатом обработки
   * @throws Exception При критических ошибках в процессе обработки
   */
  public function handleModuleRequest(ModCommRequest $request): ModCommResponse
  {
    $startTime = microtime(true);
    $this->setRequest($request);
    
    $this->startOperationInHistory([
      'message'          => 'Incoming modComm request',
      'request_id'       => $this->getRequest()?->getRequestId(),
      'module_name'      => $this->getRequest()?->getModuleName(),
      'action'           => $this->getRequest()?->getAction(),
      'request_data'     => $this->getRequest()?->getData(),
      'request_metadata' => $this->getRequest()?->getMetadata(),
      'user_id'          => Service::auth()->getUserId(),
      'isAdmin'          => Service::auth()->isAdmin(),
      'timestamp'        => $this->getRequest()?->getTimestamp()
    ]);
    
    try {
      // Обработка запроса через соответствующий контроллер
      $response = $this->processRequest();
      
      // Добавление метаданных о времени выполнения
      $processingTime = microtime(true) - $startTime;
      $response->withMetadata(['processing_time' => $processingTime]);
      
      $this->addHistoryEvent('modCommModule', "Module response", $response->toArray());
      
      // Завершаем операцию
      $this->endOperationInHistory([
        'status'          => 'success',
        'processing_time' => $processingTime,
        'response_status' => $response->getStatusCode()
      ]);
      $this->clearRequest();
      
      return $response;
    } catch (Exception $e) {
      $processingTime = microtime(true) - $startTime;
      
      // Логируем ошибку
      $this->addErrorInHistory('Internal error in modCommModule request', [
        'action'          => $this->getRequest()?->getAction(),
        'processing_time' => $processingTime,
        'error_message'   => $e->getMessage()
      ], $e);
      
      // Завершаем операцию с ошибкой
      $this->endOperationInHistory([
        'status'          => 'error',
        'error'           => 'internal_error',
        'processing_time' => $processingTime,
        'error_message'   => $e->getMessage()
      ]);
      $this->clearRequest();
      
      return ModCommHelper::error(
        'Internal error: ' . $e->getMessage(),
        500,
        ['processing_time' => $processingTime]
      );
    }
  }
  
  /**
   * Парсинг действия для определения контроллера и метода.
   *
   * Разбирает строку действия в формате "controller/method" или "method"
   * и возвращает массив с названием контроллера и метода для обработки.
   *
   * @param string $action Действие в формате "controller/method" или "method"
   * @return array{controller: string, method: string} Массив с ключами 'controller' и 'method'
   */
  private function parseAction(string $action): array
  {
    $parts = explode('/', $action, 2);
    
    if (count($parts) === 2) {
      return [
        'controller' => ucfirst($parts[0]),
        'method'     => $parts[1]
      ];
    }
    
    return [
      'controller' => ucfirst(str_replace(ModCommHelper::getModCommNamePrefix(true), '', $this->getRequest()?->getModuleName())),
      'method'     => $parts[0]
    ];
  }
  
  /**
   * Обрабатывает запрос в зависимости от его типа.
   *
   * Определяет способ обработки запроса:
   * - Проверяет наличие специальных обработчиков в текущем модуле
   * - Загружает соответствующий контроллер
   * - Валидирует существование метода в контроллере
   * - Делегирует обработку контроллеру
   *
   * @return ModCommResponse Ответ на запрос
   * @throws ModCommException|Exception При ошибках загрузки контроллера или метода
   */
  protected function processRequest(): ModCommResponse
  {
    ['controller' => $controllerName, 'method' => $methodName] = $this->parseAction($this->getRequest()?->getAction());

    // Проверяем, есть ли специальная обработка для действия
    if (method_exists($this, 'handle' . ucfirst($methodName))) {
      $this->addHistoryEvent('modCommModule', "Special action handler found",
        ['action' => 'handle' . ucfirst($methodName), 'request' => $this->getRequest()]);
      
      return $this->{'handle' . ucfirst($methodName)}($this->getRequest());
    }
    // Ищем контроллер
    if (!$controller = $this->loadController($controllerName)) {
      $this->addErrorInHistory("Controller not found", [
        'controller_name' => $controllerName,
        'request'         => $this->getRequest()
      ]);
      
      return ModCommHelper::error(lang('controller_not_found', 'modComm') . ': ' . $controllerName, 404);
    }
    
    // Проверяем, есть ли метод в контроллере
    if (!$controller->hasMethod($methodName)) {
      $this->addErrorInHistory("Method not found", [
        'controller_name' => $controllerName,
        'method_name'     => $methodName,
        'request'         => $this->getRequest()
      ]);
      
      return ModCommHelper::error("Method '{$methodName}' not found in controller '{$controllerName}'", 404);
    }
    
    // Создаем новый запрос с правильным действием
    $newRequest = new ModCommRequest(
      $this->getRequest()?->getTargetModule(),
      $methodName,
      $this->getRequest()?->getData(),
      $this->getRequest()?->getOptions()
    );
    $response   = $controller->handleRequest($newRequest);
    
    $this->addHistoryEvent('modCommModule', "Request processed", ['historyController' => $controller->history()?->getHistory()]);
    
    return $response;
  }
  
  /**
   * Получение контроллера в соответствии с текущим запросом.
   *
   * Выполняет поиск и загрузку контроллера по приоритетным путям:
   * 1. Специфичный контроллер модуля
   * 2. Системный контроллер modComm
   *
   * Автоматически создает экземпляр контроллера если загрузка не удалась.
   *
   * @param string $controllerName Название контроллера для загрузки
   * @return ModCommController Экземпляр загруженного или созданного контроллера
   */
  private function loadController(string $controllerName): ModCommController
  {
    $priorityPaths = [
      paths()::MODULES_DIR . '\\' . lcfirst(str_replace(ModCommHelper::getModCommNamePrefix(true), '',
        $this->getRequest()?->getModuleName())) . '\\' . paths()::CONTROLLERS_DIR . '\\' . ModCommHelper::getModCommNamePrefix() . '\\',
      paths()->systemDir . paths()::MODULES_DIR . '\\' . ModCommHelper::getModCommNamePrefix() . '\\' . paths()::CONTROLLERS_DIR . '\\',
    ];
    foreach ($priorityPaths as $path) {
      foreach ([$controllerName, ModCommHelper::getModCommNamePrefix(true)] as $name) {
        foreach (Service::locator()->search($path . $name . 'Controller.php', 'php', false) as $file) {
          if (file_exists($file) && ($class = useClass(Service::locator()->getClassname($file), true)) && $class instanceof ModCommController) {
            $this->addHistoryEvent('modCommModule', "Controller loaded", [
              'controller_name' => $name,
              'file'            => $file,
              'class'           => $class,
              'request'         => $this->getRequest()
            ]);
            
            return $class;
          }
        }
      }
    }
    
    return new ModCommController();
  }
  
  /**
   * Возвращает текущий объект запроса.
   *
   * Предоставляет доступ к объекту запроса, который в данный момент
   * обрабатывается модулем.
   *
   * @return ModCommRequest|null Объект запроса, если установлен, иначе null
   */
  protected function getRequest(): ?ModCommRequest
  {
    return $this->request;
  }
  
  /**
   * Устанавливает новый объект запроса.
   *
   * Позволяет установить объект запроса для обработки.
   * Предыдущий запрос (если был) будет заменен новым.
   *
   * @param ModCommRequest $request Объект запроса для установки
   * @return void
   */
  protected function setRequest(ModCommRequest $request): void
  {
    $this->request = $request;
  }
  
  /**
   * Проверяет, установлен ли корректный объект запроса.
   *
   * Валидирует, что текущий объект запроса является экземпляром
   * ModCommRequest и готов к обработке.
   *
   * @return bool Возвращает true, если запрос корректен, иначе false
   */
  protected function checkRequest(): bool
  {
    return $this->getRequest() instanceof ModCommRequest;
  }
  
  /**
   * Сбрасывает (удаляет) текущий объект запроса.
   *
   * Очищает ссылку на текущий запрос, освобождая память
   * и подготавливая модуль к обработке следующего запроса.
   *
   * @return void
   */
  protected function clearRequest(): void
  {
    $this->request = null;
  }
}
