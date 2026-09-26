<?php

namespace AC\core\system\modules\modComm\services;

use AC\core\system\module\BaseModule;
use AC\core\system\modules\modComm\exceptions\ModCommException;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;
use AC\core\system\modules\modComm\ModCommModuleInterface;
use AC\core\system\service\history\HistoryTrait;
use Service;

/**
 * Сервис для межмодульного общения через JSON запросы.
 * 
 * Этот сервис реализует паттерн Singleton и обеспечивает безопасную
 * коммуникацию между модулями системы. Основные возможности:
 * - Регистрация модулей для межмодульного взаимодействия
 * - Отправка запросов к зарегистрированным модулям
 * - Автоматическая загрузка языковых файлов
 * - Логирование всех операций и ошибок
 * - Управление жизненным циклом модулей
 * 
 * Сервис является центральной точкой для организации межмодульного
 * взаимодействия в системе.
 * 
 * @package AC\core\system\modules\modComm\services
 * @since 1.0.0
 */
class ModCommService
{
  use HistoryTrait;
  
  /**
   * Единственный экземпляр сервиса (Singleton)
   * 
   * @var self|null Экземпляр сервиса или null если не создан
   */
  private static ?self $instance = null;
  
  /**
   * Список зарегистрированных модулей с метаданными
   * 
   * Каждый модуль описывается массивом с информацией:
   * - module: экземпляр модуля
   * - registered_at: время регистрации модуля
   * 
   * @var array<string, array{
   *   module: ModCommModuleInterface,
   *   registered_at: int
   * }> Карта зарегистрированных модулей
   */
  private array $registeredModules = [];
  
  
  /**
   * Конструктор — приватный для реализации паттерна Singleton.
   * 
   * Инициализирует историю событий сервиса и загружает
   * необходимые языковые файлы для модуля modComm.
   */
  private function __construct()
  {
    $this->initHistory('modComm')->addHistoryEvent('startService', 'ModCommService started');
    foreach (['', 'message', 'exception'] as $message) {
      Service::lang()->addFile($message, pathAs(paths()->systemDir . 'module\\' . ModCommHelper::getModCommNamePrefix() . '\\' . paths()->getLangDir()));
    }
  }
  
  /**
   * Получить единственный экземпляр сервиса (Singleton).
   * 
   * Устанавливает текущий используемый модуль как modComm
   * и создает экземпляр сервиса если он еще не существует.
   *
   * @return self Единственный экземпляр сервиса
   */
  public static function getInstance(): self
  {
    Service::app()->setUsedModule(ModCommHelper::getModCommNamePrefix());
    
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }
  
  /**
   * Зарегистрировать модуль для межмодульного общения.
   * 
   * Выполняет поиск модуля в системе и регистрирует его
   * для участия в межмодульном взаимодействии. Модуль должен
   * реализовывать интерфейс ModCommModuleInterface.
   *
   * @param string $moduleName Имя модуля для регистрации
   * @return bool Возвращает true, если модуль был найден и зарегистрирован
   */
  public function registerModule(string $moduleName): bool
  {
    //todo сейчас вызов только для модуля modComm(если нужно вызвать внешний модуль нужно поменять варианты поиска)
    // todo сейчас ищет core\modules\clientsModComm\ClientsModCommModule вместо core\modules\clients\ClientsModCommModule
    foreach ([$moduleName, ModCommHelper::getModCommNamePrefix()] as $moduleNameSearch) {
      if (($module = module($moduleNameSearch)) && $module instanceof ModCommModuleInterface) {
        $this->addHistoryEvent('modComm_register', "Module '{$moduleName}' is registered", []);
        
        $this->registeredModules[$moduleName] = [
          'module'        => $module,
          'registered_at' => time()
        ];
        
        return true;
      }
    }
    
    return false;
  }
  
  /**
   * Проверяет, зарегистрирован ли модуль, и при необходимости регистрирует его.
   * 
   * Если модуль не зарегистрирован, автоматически пытается
   * зарегистрировать его в системе.
   *
   * @param string $moduleName Имя модуля для проверки
   * @return bool Возвращает true, если модуль уже зарегистрирован или успешно зарегистрирован
   */
  public function isModuleRegistered(string $moduleName): bool
  {
    return isset($this->registeredModules[$moduleName]) || $this->registerModule($moduleName);
  }
  
  /**
   * Отправить запрос к указанному модулю.
   * 
   * Создает объект запроса и отправляет его к зарегистрированному модулю.
   * Выполняет автоматическую регистрацию модуля если он не зарегистрирован.
   * Логирует все этапы выполнения запроса и обработки ответа.
   *
   * @param string $targetModule Имя целевого модуля для отправки запроса
   * @param string $action Название действия для выполнения в модуле
   * @param array $data Данные для передачи в модуль
   * @param array $options Дополнительные параметры запроса (метаданные, заголовки и т.д.)
   * @return ModCommResponse Ответ от модуля с результатом выполнения
   * @throws ModCommException Если модуль не зарегистрирован или произошла ошибка
   */
  public function sendRequest(string $targetModule, string $action, array $data = [], array $options = []): ModCommResponse
  {
    Service::app()->setUsedModule($targetModule);
    
    $targetModule = !str_ends_with($targetModule, ModCommHelper::getModCommNamePrefix(true)) ? $targetModule . ModCommHelper::getModCommNamePrefix(true) : $targetModule;
    $metaData     = [
      'module'  => $targetModule,
      'action'  => $action,
      'data'    => $data,
      'options' => $options
    ];
    $this->startOperationInHistory($metaData);
    
    if (!$this->isModuleRegistered($targetModule, $options)) {
      $this->addHistoryEvent('modComm_error', "Module '{$targetModule}' is not registered", $metaData);
      
      return ModCommHelper::badRequest();
    }
    
    $request = ModCommHelper::createRequest($targetModule, $action, $data, $options);
    $this->logRequest($request);
    
    try {
      /** @var ModCommModuleInterface $module */
      $module = $this->registeredModules[$targetModule]['module'];
      
      $response = $module->handleModuleRequest($request);
      
      $this->addHistoryEvent('historyModule', 'Module History', $module->history()?->getHistory() ?? []);
      $this->logResponse($response);
      $this->endOperationInHistory($response->toArray());
      
      return $response;
    } catch (\Exception $e) {
      $this->addErrorInHistory("Error communicating with module '{$targetModule}': " . $e->getMessage(), $metaData);
      
      return ModCommHelper::badRequest();
    }
  }
  
  /**
   * Логирует информацию о запросе.
   * 
   * Создает запись в истории о входящем запросе к модулю,
   * включая данные запроса и дополнительные опции.
   *
   * @param ModCommRequest $request Объект запроса для логирования
   * @return void
   */
  private function logRequest(ModCommRequest $request): void
  {
    $this->addHistoryEvent('modComm_request', "ModComm request to module {$request->getTargetModule()}:{$request->getAction()}",
      [
        'request_data' => $request->getData(),
        'options'      => $request->getOptions()
      ]);
  }
  
  /**
   * Логирует информацию об ответе.
   * 
   * Создает запись в истории о полученном ответе от модуля,
   * включая статус выполнения, сообщения и данные ответа.
   *
   * @param ModCommResponse $response Объект ответа для логирования
   * @return void
   */
  private function logResponse(ModCommResponse $response): void
  {
    $this->addHistoryEvent('modComm_response', "ModComm response from module",
      [
        'success'     => $response->isSuccess(),
        'status_code' => $response->getStatusCode(),
        'message'     => $response->getMessage(),
        'data'        => $response->getData(),
        'meta_data'   => $response->getMetadata(),
      ]);
  }
}
