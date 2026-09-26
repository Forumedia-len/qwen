<?php

namespace AC\core\system\service\history;

use Exception;
use Service;

/**
 * Трейт для автоматической интеграции истории операций
 *
 * Предоставляет методы для автоматического логирования событий
 * в различных компонентах системы. Позволяет легко интегрировать
 * функциональность отслеживания истории в любой класс.
 *
 * @package AC\core\system\service\history
 * @since   1.0.0
 */
trait HistoryTrait
{
  
  /**
   * Экземпляр сервиса истории операций
   *
   * @var HistoryService|null
   */
  private ?HistoryService $history = null;
  
  /**
   * Инициализирует историю операций
   *
   * Создает новый экземпляр HistoryService и инициализирует его
   * с указанным ID операции.
   *
   * @param string|null $operationId ID операции для инициализации
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  protected function initHistory(?string $operationId = null): self
  {
    $this->history = Service::history($operationId);
    return $this;
  }
  
  /**
   * Получает экземпляр сервиса истории
   *
   * @return HistoryService|null Экземпляр сервиса истории или null если не инициализирован
   */
  public function history(): ?HistoryService
  {
    return $this->history;
  }
  
  /**
   * Начинает операцию в истории
   *
   * Инициализирует историю если необходимо и начинает новую операцию
   * с указанными метаданными.
   *
   * @param array<string, mixed> $metadata Метаданные операции (время начала, пользователь и т.д.)
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  protected function startOperationInHistory(array $metadata = []): self
  {
    if (!$this->history) {
      $this->initHistory();
    }
    
    $this->history->startOperation($metadata);
    return $this;
  }
  
  /**
   * Завершает операцию в истории
   *
   * Завершает текущую активную операцию и добавляет данные результата.
   *
   * @param array<string, mixed> $resultData Данные результата операции
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  protected function endOperationInHistory(array $resultData = []): self
  {
    if ($this->history) {
      $this->history->endOperation($resultData);
    }
    return $this;
  }
  
  /**
   * Добавляет событие в историю
   *
   * Создает новое событие с указанным типом, описанием и данными.
   *
   * @param string               $type        Тип события (например: 'user_action', 'system_event')
   * @param string               $description Описание события на русском языке
   * @param array<string, mixed> $data        Дополнительные данные события
   * @param string               $level       Уровень важности ('info', 'warning', 'error', 'debug')
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  protected function addHistoryEvent(string $type, string $description, array $data = [], string $level = 'info'): self
  {
    $this->history?->addEvent($type, $description, $data, $level);
    
    return $this;
  }
  
  /**
   * Логирует HTTP запрос в историю
   *
   * @param string                $method  HTTP метод (GET, POST, PUT, DELETE и т.д.)
   * @param string                $url     URL запроса
   * @param array<string, string> $headers Заголовки HTTP запроса
   * @param array<string, mixed>  $data    Данные запроса (POST данные, параметры)
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  protected function logHttpRequest(string $method, string $url, array $headers = [], array $data = []): self
  {
    if ($this->history) {
      $this->history->addHttpRequest($method, $url, $headers, $data);
    }
    return $this;
  }
  
  /**
   * Логирует HTTP ответ в историю
   *
   * @param int                   $statusCode   HTTP код статуса ответа
   * @param array<string, string> $headers      Заголовки HTTP ответа
   * @param array<string, mixed>  $data         Данные ответа
   * @param float                 $responseTime Время ответа в секундах
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  protected function logHttpResponse(int $statusCode, array $headers = [], array $data = [], float $responseTime = 0): self
  {
    $this->history?->addHttpResponse($statusCode, $headers, $data, $responseTime);
    return $this;
  }
  
  /**
   * Логирует запрос к базе данных в историю
   *
   * @param string               $query         SQL запрос
   * @param array<string, mixed> $params        Параметры SQL запроса
   * @param float                $executionTime Время выполнения запроса в секундах
   * @param array<string, mixed> $result        Результат выполнения запроса
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  protected function logDatabaseQuery(string $query, array $params = [], float $executionTime = 0, array $result = []): self
  {
    $this->history?->addDatabaseQuery($query, $params, $executionTime, $result);
    return $this;
  }
  
  /**
   * Логирует ошибку в историю
   *
   * @param string               $errorMessage Сообщение об ошибке
   * @param array<string, mixed> $errorData    Дополнительные данные об ошибке
   * @param Exception|null       $exception    Объект исключения если доступен
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  protected function addErrorInHistory(string $errorMessage, array $errorData = [], ?Exception $exception = null): self
  {
    $this->history?->addError($errorMessage, $errorData, $exception);
    return $this;
  }
  
  /**
   * Логирует критическую ошибку с записью в файл
   *
   * Критические ошибки дополнительно записываются в лог-файл
   * для последующего анализа.
   *
   * @param string               $errorMessage Сообщение об ошибке
   * @param array<string, mixed> $errorData    Дополнительные данные об ошибке
   * @param Exception|null       $exception    Объект исключения если доступен
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  protected function logCriticalError(string $errorMessage, array $errorData = [], ?Exception $exception = null): self
  {
    $this->history?->addCriticalError($errorMessage, $errorData, $exception);
    return $this;
  }
  
  /**
   * Логирует пользовательское событие в историю
   *
   * @param string               $type        Тип пользовательского события
   * @param string               $description Описание события
   * @param array<string, mixed> $data        Данные события
   * @param string               $level       Уровень важности ('info', 'warning', 'error', 'debug')
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  protected function logCustomEvent(string $type, string $description, array $data = [], string $level = 'info'): self
  {
    $this->history?->addCustomEvent($type, $description, $data, $level);
    return $this;
  }
  
  /**
   * Получает полную историю операций
   *
   * @return array<string, mixed>|null Массив с историей операций или null если история не инициализирована
   */
  protected function getHistory(): ?array
  {
    return $this->history?->getHistory();
  }
  
  /**
   * Сохраняет историю операции в файл
   *
   * @param string $format Формат сохранения ('json', 'txt', 'html')
   */
  protected function saveOperationHistory(string $format = 'json'): void
  {
    $this->history?->saveToFile($format);
  }
  
  /**
   * Экспортирует историю операции в выбранном формате
   *
   * @param string $format Формат экспорта ('json', 'txt', 'html')
   * @return string|null Путь к созданному файлу или null в случае ошибки
   */
  protected function exportOperationHistory(string $format = 'json'): ?string
  {
    return $this->history?->export($format);
  }
  
  /**
   * Получает ID текущей операции
   *
   * @return string|null ID операции или null если операция не активна
   */
  protected function getOperationId(): ?string
  {
    return $this->history?->getOperationId();
  }
  
  /**
   * Проверяет, активна ли текущая операция
   *
   * @return bool true если операция активна, false в противном случае
   */
  protected function isOperationActive(): bool
  {
    return $this->history && $this->history->isActiveOperation();
  }
  
  /**
   * Получает статистику по операциям
   *
   * @return array<string, mixed>|null Статистика операций или null если история не инициализирована
   */
  protected function getOperationStatistics(): ?array
  {
    return $this->history?->getStatistics();
  }
  
  /**
   * Логирует всю историю операции в файл
   *
   * @param string $level Уровень логирования ('info', 'warning', 'error', 'debug')
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  protected function logOperationHistory(string $level = 'info'): self
  {
    $this->history?->logHistory($level);
    return $this;
  }
} 