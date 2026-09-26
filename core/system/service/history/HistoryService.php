<?php

namespace AC\core\system\service\history;

use AC\core\system\debug\Logger;
use AC\core\system\helpers\FileHelper;
use AC\core\system\helpers\JsonHelper;
use AC\core\system\helpers\UniqHelper;
use Exception;
use Service;

/**
 * Сервис для отслеживания истории операций в системе
 *
 * Позволяет фиксировать все взаимодействия между модулями, запросы, ответы
 * и другие полезные сообщения в рамках одной операции. Предоставляет
 * детальное логирование для отладки и аудита системы.
 *
 * @package AC\core\system\service\history
 * @since   1.0.0
 */
class HistoryService
{
  /**
   * Уникальный идентификатор текущей операции
   *
   * @var string|null
   */
  private ?string $operationId;
  
  /**
   * История всех событий в рамках сессии
   *
   * @var array<int, array<string, mixed>>
   */
  private array $history = [];
  
  /**
   * История событий текущей активной операции
   *
   * @var array<int, array<string, mixed>>
   */
  private array $historyOperation = [];
  
  /**
   * Метаданные текущей операции
   *
   * @var array<string, mixed>
   */
  private array $metadata = [];
  
  /**
   * Метаданные истории сессии
   *
   * @var array<string, mixed>
   */
  private array $metadataHistory;
  
  /**
   * Флаг активности операции
   *
   * @var bool
   */
  private bool $isActiveOperation = false;
  private bool $useFullHistory    = false;
  
  /**
   * Логгер для записи критических ошибок
   *
   * @var Logger
   */
  private Logger $logger;
  
  /**
   * Конструктор сервиса
   *
   * Инициализирует сервис истории с указанным ID и создает
   * уникальный идентификатор для истории.
   *
   * @param string $historyId Уникальный идентификатор операции
   * @param array  $params
   */
  public function __construct(string $historyId = 'common', array $params = [])
  {
    $this->logger                           = Service::logger(pathAs('history/' . $historyId));
    $this->metadataHistory['historyId']     = $historyId;
    $this->metadataHistory['uniqHistoryId'] = UniqHelper::generateUniqId($historyId);
    $this->metadataHistory['metadata']      = $this->getMetadata($params['metadata'] ?? []);
    $this->useFullHistory                   = $params['useFullHistory'] ?? false;
  }
  
  /**
   * Начинает новую операцию
   *
   * Устанавливает флаг активности операции и инициализирует
   * метаданные с временными метками.
   *
   * @param array<string, mixed> $metadata Метаданные операции
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  public function startOperation(array $metadata = []): self
  {
    $this->operationId             = $metadata['operationId'] ?? UniqHelper::generateUniqId('op');
    $this->isActiveOperation       = true;
    $this->metadata                = $this->getMetadata($metadata);
    $this->metadata['operationId'] = $this->operationId;
    
    $this->addEvent('operation_started', 'Start operation', $metadata);
    
    return $this;
  }
  
  /**
   * Получает метаданные с базовой информацией
   *
   * Формирует базовые метаданные включающие время начала,
   * информацию о пользователе, сессии и системе.
   *
   * @param array<string, mixed> $metadata Дополнительные метаданные
   * @return array<string, mixed> Объединенные метаданные
   */
  private function getMetadata(array $metadata = []): array
  {
    return array_merge([
      'start_time'     => microtime(true),
      'start_datetime' => date('Y-m-d H:i:s'),
      'user_id'        => Service::auth()->getUserId(),
      'is_admin'       => Service::auth()->isAdmin(),
      'session_id'     => session_id(),
      'request_uri'    => $_SERVER['REQUEST_URI'] ?? null,
      'user_agent'     => $_SERVER['HTTP_USER_AGENT'] ?? null,
      'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
      'memory_usage'   => memory_get_usage(true),
      'peak_memory'    => memory_get_peak_usage(true),
    ], $metadata);
  }
  
  /**
   * Завершает операцию
   *
   * Вычисляет длительность операции, добавляет финальное событие
   * и сбрасывает состояние операции.
   *
   * @param array<string, mixed> $resultData Данные результата операции
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  public function endOperation(array $resultData = []): self
  {
    if (!$this->isActiveOperation || !$this->useFullHistory) {
      return $this;
    }
    
    $endData = array_merge([
      'end_time'     => microtime(true),
      'end_datetime' => date('Y-m-d H:i:s'),
      'duration'     => round(microtime(true) - $this->metadata['start_time'], 5) . ' sec',
    ], $resultData);
    
    $this->addEvent('operation_ended', 'Finish operation', $endData);
    $this->metadataHistory['operations'][$this->operationId] = [
      'end_time'     => microtime(true),
      'end_datetime' => date('Y-m-d H:i:s'),
      'duration'     => round(microtime(true) - $this->metadata['start_time'], 5) . ' sec',
      'memory_usage' => FileHelper::humanFileSize(memory_get_usage(true) - $this->isActiveOperation ? $this->metadata['memory_usage'] : $this->metadataHistory['metadata']['memory_usage']),
      'peak_memory'  => FileHelper::humanFileSize(memory_get_peak_usage(true) - $this->metadata['peak_memory']),
      'total_events' => count($this->historyOperation),
      'events'       => $this->historyOperation,
    ];
    
    $this->isActiveOperation = false;
    $this->operationId       = null;
    $this->historyOperation  = [];
    $this->metadata          = [];
    
    return $this;
  }
  
  /**
   * Добавляет событие в историю операции
   *
   * Создает новое событие с временными метками, информацией о памяти
   * и статусе активности операции.
   *
   * @param string               $type        Тип события (например: 'user_action', 'system_event')
   * @param string               $description Описание события на русском языке
   * @param array<string, mixed> $data        Дополнительные данные события
   * @param string               $level       Уровень важности ('info', 'warning', 'error', 'debug')
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  public function addEvent(string $type, string $description, array $data = [], string $level = 'info'): self
  {
    if (!$this->useFullHistory) {
      return $this;
    }
    $event = [
      'operationId'  => $this->operationId ?? null,
      'timestamp'    => microtime(true),
      'datetime'     => date('Y-m-d H:i:s'),
      'type'         => $type,
      'description'  => $description,
      'data'         => $this->useFullHistory ? $data : [],
      'level'        => $level,
      'memory_usage' => isset($this->metadata['memory_usage'])
        ? FileHelper::humanFileSize(memory_get_usage(true) - $this->metadata['memory_usage'])
        : memory_get_usage(true),
      'peak_memory'  => isset($this->metadata['peak_memory'])
        ? FileHelper::humanFileSize(memory_get_peak_usage(true) - $this->metadata['peak_memory'])
        : memory_get_peak_usage(true),
      'is_active'    => (int)$this->isActiveOperation
    ];
    
    if ($this->isActiveOperation) {
      $this->historyOperation[] = $event;
    }
    $this->history[] = $event;
    
    return $this;
  }
  
  /**
   * Получает полную историю
   *
   * Возвращает структурированную историю включающую метаданные,
   * статистику и все события.
   *
   * @return array<string, mixed> Структурированная история операций
   */
  public function getHistory(): array
  {
    return [
      'historyId'        => $this->metadataHistory['historyId'],
      'uniqHistoryId'    => $this->metadataHistory['uniqHistoryId'],
      'duration'         => round((microtime(true) - $this->metadataHistory['metadata']['start_time']), 5) . ' sec',
      'memory_usage'     => FileHelper::humanFileSize(memory_get_usage(true) - $this->metadataHistory['metadata']['memory_usage']),
      'peak_memory'      => FileHelper::humanFileSize(memory_get_peak_usage(true) - $this->metadataHistory['metadata']['peak_memory']),
      'metadata'         => $this->metadataHistory['metadata'] ?? [],
      'operations'       => $this->metadataHistory['operations'] ?? [],
      'total_events'     => count($this->history ?? []),
      'total_operations' => count($this->metadataHistory['operations'] ?? []),
      'events'           => $this->history ?? [],
    ];
  }
  
  /**
   * Получает историю операций
   *
   * @return array<string, mixed> Массив завершенных операций
   */
  public function getHistoryOperations(): array
  {
    return $this->metadataHistory['operations'] ?? [];
  }
  
  /**
   * Получает ID всех операций
   *
   * @return array<int, string> Массив ID операций
   */
  public function getHistoryOperationIds(): array
  {
    return array_keys($this->metadataHistory['operations'] ?? []);
  }
  
  /**
   * Добавляет событие modComm запроса
   *
   * Специализированный метод для логирования межмодульных
   * коммуникаций в системе.
   *
   * @param string               $module          Модуль назначения
   * @param string               $action          Действие в модуле
   * @param array<string, mixed> $requestData     Данные запроса
   * @param array<string, mixed> $requestMetadata Метаданные запроса
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  public function addModCommRequest(string $module, string $action, array $requestData = [], array $requestMetadata = []): self
  {
    $data = array_merge([
      'module'       => $module,
      'action'       => $action,
      'request_data' => $requestData,
    ], $requestMetadata);
    
    return $this->addEvent('modComm_request', "ModComm request to module {$module}:{$action}", $data);
  }
  
  /**
   * Добавляет событие HTTP запроса
   *
   * Логирует детали HTTP запроса включая метод, URL,
   * заголовки и данные.
   *
   * @param string                $method  HTTP метод (GET, POST, PUT, DELETE и т.д.)
   * @param string                $url     URL запроса
   * @param array<string, string> $headers Заголовки HTTP запроса
   * @param array<string, mixed>  $data    Данные запроса (POST данные, параметры)
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  public function addHttpRequest(string $method, string $url, array $headers = [], array $data = []): self
  {
    return $this->addEvent('http_request', "HTTP {$method} request to {$url}", [
      'method'  => $method,
      'url'     => $url,
      'headers' => $headers,
      'data'    => $data
    ]);
  }
  
  /**
   * Добавляет событие HTTP ответа
   *
   * Логирует детали HTTP ответа включая код статуса,
   * заголовки и время ответа.
   *
   * @param int                   $statusCode   HTTP код статуса ответа
   * @param array<string, string> $headers      Заголовки HTTP ответа
   * @param array<string, mixed>  $data         Данные ответа
   * @param float                 $responseTime Время ответа в секундах
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  public function addHttpResponse(int $statusCode, array $headers = [], array $data = [], float $responseTime = 0): self
  {
    return $this->addEvent('http_response', "HTTP response {$statusCode}", [
      'status_code'   => $statusCode,
      'headers'       => $headers,
      'data'          => $data,
      'response_time' => $responseTime
    ]);
  }
  
  /**
   * Добавляет событие работы с базой данных
   *
   * Логирует SQL запросы с параметрами, временем выполнения
   * и предварительным просмотром результата.
   *
   * @param string               $query         SQL запрос
   * @param array<string, mixed> $params        Параметры SQL запроса
   * @param float                $executionTime Время выполнения запроса в секундах
   * @param array<string, mixed> $result        Результат выполнения запроса
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  public function addDatabaseQuery(string $query, array $params = [], float $executionTime = 0, array $result = []): self
  {
    return $this->addEvent('database_query', "SQL request completed in {$executionTime} sec", [
      'query'          => $query,
      'params'         => $params,
      'execution_time' => $executionTime,
      'result_count'   => is_array($result) ? count($result) : null,
      'result_preview' => is_array($result) ? array_slice($result, 0, 5) : $result
    ]);
  }
  
  /**
   * Добавляет событие ошибки
   *
   * Логирует ошибки с детальной информацией включая
   * стек вызовов если доступен объект исключения.
   *
   * @param string               $errorMessage Сообщение об ошибке
   * @param array<string, mixed> $errorData    Дополнительные данные об ошибке
   * @param Exception|null       $exception    Объект исключения если доступен
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  public function addError(string $errorMessage, array $errorData = [], ?Exception $exception = null): self
  {
    $data = [
      'error_message' => $errorMessage,
      'error_data'    => $errorData
    ];
    
    if ($exception) {
      $data['exception'] = [
        'class'   => get_class($exception),
        'message' => $exception->getMessage(),
        'code'    => $exception->getCode(),
        'file'    => $exception->getFile(),
        'line'    => $exception->getLine(),
        'trace'   => $exception->getTraceAsString()
      ];
    }
    
    return $this->addEvent('error', "Error: {$errorMessage}", $data, 'error');
  }
  
  /**
   * Добавляет критическую ошибку с логированием в файл
   *
   * Критические ошибки дополнительно записываются в лог-файл
   * для последующего анализа и мониторинга.
   *
   * @param string               $errorMessage Сообщение об ошибке
   * @param array<string, mixed> $errorData    Дополнительные данные об ошибке
   * @param Exception|null       $exception    Объект исключения если доступен
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  public function addCriticalError(string $errorMessage, array $errorData = [], ?Exception $exception = null): self
  {
    $data = [
      'error_message' => $errorMessage,
      'error_data'    => $errorData
    ];
    
    // Логируем критическую ошибку в файл
    if ($exception) {
      $this->logger->logException($exception, $errorMessage, $errorData);
    } else {
      $this->logger->critical("Critical operation error: {$errorMessage}", [
        'operation_id' => $this->operationId,
        'error_data'   => $data
      ]);
    }
    
    return $this->addEvent('critical_error', "Critical error: {$errorMessage}", $data, 'error');
  }
  
  /**
   * Добавляет пользовательское событие
   *
   * Позволяет логировать произвольные события с
   * пользовательскими типами и описаниями.
   *
   * @param string               $type        Тип пользовательского события
   * @param string               $description Описание события
   * @param array<string, mixed> $data        Данные события
   * @param string               $level       Уровень важности ('info', 'warning', 'error', 'debug')
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  public function addCustomEvent(string $type, string $description, array $data = [], string $level = 'info'): self
  {
    return $this->addEvent("custom_{$type}", $description, $data, $level);
  }
  
  /**
   * Получает события определенного типа
   *
   * Фильтрует события по типу для анализа
   * конкретных категорий событий.
   *
   * @param string $type Тип события для фильтрации
   * @return array<int, array<string, mixed>> Массив отфильтрованных событий
   */
  public function getEventsByType(string $type): array
  {
    return array_filter($this->history, static fn($event) => $event['type'] === $type);
  }
  
  /**
   * Получает события определенного уровня важности
   *
   * Фильтрует события по уровню важности для
   * анализа критических ситуаций.
   *
   * @param string $level Уровень важности для фильтрации
   * @return array<int, array<string, mixed>> Массив отфильтрованных событий
   */
  public function getEventsByLevel(string $level): array
  {
    return array_filter($this->history, static fn($event) => $event['level'] === $level);
  }
  
  /**
   * Сохраняет историю в файл
   *
   * Сохраняет экспортированную историю в файл
   * в указанном формате.
   *
   * @param string $format Формат сохранения ('json', 'txt', 'html')
   */
  public function saveToFile(string $format = 'json'): void
  {
    Debug()::saveF($this->export($format), ROOT_PATH . 'logs/history/',
      "history_{$this->operationId}_{" . date('d.m.Y H:i:s') . "}.{$format}");
  }
  
  /**
   * Экспортирует историю в различных форматах
   *
   * Поддерживает экспорт в JSON, текстовом и HTML форматах
   * для различных целей анализа и отчетности.
   *
   * @param string $format Формат экспорта ('json', 'txt', 'html')
   * @return string Содержимое в выбранном формате
   */
  public function export(string $format = 'json'): string
  {
    return match ($format) {
      'txt'   => $this->formatAsText(),
      'html'  => $this->formatAsHtml(),
      default => $this->formatAsJson(),
    };
  }
  
  /**
   * Форматирует историю как JSON
   *
   * @return string JSON строка с историей операций
   */
  public function formatAsJson(): string
  {
    return JsonHelper::encode($this->getHistory(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  }
  
  /**
   * Форматирует историю как текст
   *
   * Создает читаемый текстовый отчет с историей
   * операций и событиями.
   *
   * @return string Текстовое представление истории
   */
  private function formatAsText(): string
  {
    $history = $this->getHistory();
    $content = "ИСТОРИЯ ОПЕРАЦИИ\n";
    $content .= "================\n\n";
    $content .= "ID операции: {$history['operation_id']}\n";
    $content .= "Статус: " . ($history['is_active'] ? 'Активна' : 'Завершена') . "\n";
    $content .= "Начало: {$history['metadata']['start_datetime']}\n";
    
    if (!$history['is_active']) {
      $content .= "Окончание: {$history['metadata']['end_datetime']}\n";
      $content .= "Длительность: " . number_format($history['duration'], 4) . " сек\n";
    }
    
    $content .= "Всего событий: {$history['total_events']}\n\n";
    
    $content .= "СОБЫТИЯ:\n";
    $content .= "=========\n\n";
    
    foreach ($history['events'] as $index => $event) {
      $content .= sprintf(
        "%d. [%s] %s - %s\n",
        $index + 1,
        $event['datetime'],
        strtoupper($event['level']),
        $event['description']
      );
      
      if (!empty($event['data'])) {
        $content .= "   Данные: " . JsonHelper::encode($event['data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
      }
      
      $content .= "\n";
    }
    
    return $content;
  }
  
  /**
   * Форматирует историю как HTML
   *
   * Создает HTML страницу с историей операций
   * включая стили для удобного просмотра.
   *
   * @return string HTML представление истории
   */
  private function formatAsHtml(): string
  {
    $history = $this->getHistory();
    
    $html = '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История операции</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f5f5f5; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .event { border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .event.info { border-left: 4px solid #007bff; }
        .event.warning { border-left: 4px solid #ffc107; }
        .event.error { border-left: 4px solid #dc3545; }
        .event.debug { border-left: 4px solid #6c757d; }
        .event-header { font-weight: bold; margin-bottom: 10px; }
        .event-data { background: #f8f9fa; padding: 10px; border-radius: 3px; margin-top: 10px; }
        .status-active { color: #28a745; }
        .status-completed { color: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <h1>История операции</h1>
        <p><strong>ID операции:</strong> ' . htmlspecialchars($history['operation_id']) . '</p>
        <p><strong>Статус:</strong> <span class="' . ($history['is_active'] ? 'status-active">Активна' : 'status-completed">Завершена') . '</span></p>
        <p><strong>Начало:</strong> ' . htmlspecialchars($history['metadata']['start_datetime']) . '</p>';
    
    if (!$history['is_active']) {
      $html .= '<p><strong>Окончание:</strong> ' . htmlspecialchars($history['metadata']['end_datetime']) . '</p>';
      $html .= '<p><strong>Длительность:</strong> ' . number_format($history['duration'], 4) . ' сек</p>';
    }
    
    $html .= '<p><strong>Всего событий:</strong> ' . $history['total_events'] . '</p>
    </div>
    
    <h2>События</h2>';
    
    foreach ($history['events'] as $index => $event) {
      $html .= '
    <div class="event ' . $event['level'] . '">
        <div class="event-header">
            ' . ($index + 1) . '. [' . htmlspecialchars($event['datetime']) . '] ' .
        strtoupper($event['level']) . ' - ' . htmlspecialchars($event['description']) . '
        </div>';
      
      if (!empty($event['data'])) {
        $html .= '
        <div class="event-data">
            <pre>' . htmlspecialchars(json_encode($event['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>
        </div>';
      }
      
      $html .= '
    </div>';
    }
    
    $html .= '
</body>
</html>';
    
    return $html;
  }
  
  /**
   * Получает ID операции
   *
   * @return string|null ID текущей операции или null если операция не активна
   */
  public function getOperationId(): ?string
  {
    return $this->operationId;
  }
  
  /**
   * Проверяет, активна ли операция
   *
   * @return bool true если операция активна, false в противном случае
   */
  public function isActiveOperation(): bool
  {
    return $this->isActiveOperation;
  }
  
  /**
   * Логирует всю историю операции в файл
   *
   * Записывает полную историю операции в лог-файл
   * для последующего анализа.
   *
   * @param string $level Уровень логирования ('info', 'warning', 'error', 'debug')
   * @return self Возвращает текущий экземпляр для цепочки вызовов
   */
  public function logHistory(string $level = 'info'): self
  {
    $this->logger->$level('Operation history logged', [
      'operation_id' => $this->operationId,
      'total_events' => count($this->history),
      'is_active'    => (int)$this->isActiveOperation,
      'history'      => $this->getHistory()
    ]);
    
    return $this;
  }
  
  /**
   * Получает статистику по событиям
   *
   * Анализирует события и предоставляет статистику
   * по типам и уровням важности.
   *
   * @return array<string, mixed> Статистика событий
   */
  public function getStatistics(): array
  {
    $stats = [
      'total_events' => count($this->history),
      'by_type'      => [],
      'by_level'     => []
    ];
    
    foreach ($this->history as $event) {
      // Статистика по типам
      $stats['by_type'][$event['type']] = ($stats['by_type'][$event['type']] ?? 0) + 1;
      
      // Статистика по уровням
      $stats['by_level'][$event['level']] = ($stats['by_level'][$event['level']] ?? 0) + 1;
    }
    
    return $stats;
  }
  
  public function useFull(bool $full = false): void
  {
    $this->useFullHistory = $full;
  }
}