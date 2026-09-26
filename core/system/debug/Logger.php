<?php

namespace AC\core\system\debug;

use AC\core\system\helpers\JsonHelper;
use Exception;

/**
 * Сервис для логирования ошибок, сохраняющий их в файл
 */
class Logger
{
  /**
   * @var ?callable|null Провайдер временных меток
   */
  private $currentTimeProvider;
  
  /**
   * @var string Имя логгера / тип файла лога (например, 'auth', 'db')
   */
  private string $typeFile;
  
  /**
   * Конструктор - позволяет установить имя логгера и кастомный провайдер времени
   *
   * @param string        $typeFile Имя логгера / тип файла лога
   * @param callable|null $provider Провайдер временных меток
   */
  public function __construct(string $typeFile = 'common', ?callable $provider = null)
  {
    $this->typeFile            = $typeFile;
    $this->currentTimeProvider = $provider ?: static fn() => (new \DateTime())->format('c');
  }
  
  /**
   * Логирует данные в формате JSON
   *
   * @param string $message Сообщение об ошибке
   * @param array  $data    Стек вызовов и другие данные
   * @return void
   */
  public function logData(string $message, array $data): void
  {
    $timestamp = ($this->currentTimeProvider)();
    
    $logData = [
      'timestamp' => $timestamp,
      'message'   => $message,
      'data'      => $data
    ];
    
    $sanitizedData = $this->sanitizeLogData($logData);
    
    VDebugs::log(JsonHelper::encode($sanitizedData, JSON_UNESCAPED_UNICODE), $this->typeFile, 'data', '');
  }
  
  /**
   * Логирует ошибку в формате JSON
   *
   * @param string $message Сообщение об ошибке
   * @param array  $data    Стек вызовов и другие данные
   * @param string $level   Уровень лога (error/warning/critical)
   * @return void
   */
  public function logError(string $message, array $data, string $level = 'error'): void
  {
    $timestamp = ($this->currentTimeProvider)();
    
    $logData = [
      'timestamp' => $timestamp,
      $level      => $message,
      'data'      => $data
    ];
    
    $sanitizedData = $this->sanitizeLogData($logData);
    
    VDebugs::logJson(JsonHelper::encode($sanitizedData, JSON_UNESCAPED_UNICODE), $this->typeFile, $level);
  }
  
  /**
   * Логирует исключение в формате JSON
   *
   * @param Exception $exception Объект исключения
   * @param string    $message   Сообщение, связанное с исключением
   * @param array     $context   Дополнительные данные контекста
   * @param string    $level     Уровень лога (error/warning/critical)
   * @param bool      $sendMail  Отправлять ли письмо с логом
   * @return void
   */
  public function logException(Exception $exception, string $message, array $context, string $level = 'error', bool $sendMail = false): void
  {
    $timestamp = ($this->currentTimeProvider)();
    
    $logData = [
      'timestamp' => $timestamp,
      'exception' => [
        'classException' => get_class($exception),
        'message'        => ($message ? $message . ': ' : '') . $exception->getMessage(),
        'code'           => $exception->getCode(),
        'file'           => $exception->getFile(),
        'line'           => $exception->getLine(),
        'trace'          => $this->formatStackTrace($exception->getTrace()),
      ],
      'context'   => $context,
      'level'     => $level
    ];
    
    $sanitizedData = $this->sanitizeLogData($logData);
    
    VDebugs::logJson(JsonHelper::encode($sanitizedData, JSON_UNESCAPED_UNICODE), $this->typeFile);
    if ($sendMail) {
      VDebugs::sendDebugEmail(JsonHelper::encode($sanitizedData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
  }
  
  /**
   * Логирует сообщение уровня emergency
   *
   * @param string $message Сообщение
   * @param array  $context Дополнительные данные
   * @return void
   */
  public function emergency(string $message, array $context = []): void
  {
    $this->logError($message, $context, 'emergency');
  }
  
  /**
   * Логирует сообщение уровня alert
   *
   * @param string $message Сообщение
   * @param array  $context Дополнительные данные
   * @return void
   */
  public function alert(string $message, array $context = []): void
  {
    $this->logError($message, $context, 'alert');
  }
  
  /**
   * Логирует сообщение уровня critical
   *
   * @param string $message Сообщение
   * @param array  $context Дополнительные данные
   * @return void
   */
  public function critical(string $message, array $context = []): void
  {
    $this->logError($message, $context, 'critical');
  }
  
  /**
   * Логирует сообщение уровня error
   *
   * @param string $message Сообщение
   * @param array  $context Дополнительные данные
   * @return void
   */
  public function error(string $message, array $context = []): void
  {
    $this->logError($message, $context, 'error');
  }
  
  /**
   * Логирует сообщение уровня warning
   *
   * @param string $message Сообщение
   * @param array  $context Дополнительные данные
   * @return void
   */
  public function warning(string $message, array $context = []): void
  {
    $this->logError($message, $context, 'warning');
  }
  
  /**
   * Логирует сообщение уровня notice
   *
   * @param string $message Сообщение
   * @param array  $context Дополнительные данные
   * @return void
   */
  public function notice(string $message, array $context = []): void
  {
    $this->logError($message, $context, 'notice');
  }
  
  /**
   * Логирует сообщение уровня info
   *
   * @param string $message Сообщение
   * @param array  $context Дополнительные данные
   * @return void
   */
  public function info(string $message, array $context = []): void
  {
    $this->logError($message, $context, 'info');
  }
  
  /**
   * Логирует сообщение уровня debug
   *
   * @param string $message Сообщение
   * @param array  $context Дополнительные данные
   * @return void
   */
  public function debug(string $message, array $context = []): void
  {
    $this->logError($message, $context, 'debug');
  }
  
  /**
   * Логирует сообщение с произвольным уровнем
   *
   * @param string $level   Уровень лога
   * @param string $message Сообщение
   * @param array  $context Дополнительные данные
   * @return void
   */
  public function log(string $level, string $message, array $context = []): void
  {
    $this->logError($message, $context, $level);
  }
  
  /**
   * Экранирует данные для безопасного логирования
   *
   * @param array $data Массив данных
   * @return array Очищенный массив
   */
  private function sanitizeLogData(array $data): array
  {
    foreach ($data as $key => $value) {
      if (is_string($value)) {
        $data[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
      } elseif (is_array($value)) {
        $data[$key] = $this->sanitizeLogData($value);
      }
    }
    return $data;
  }
  
  /**
   * Форматирует стек вызовов в структурированный JSON.
   *
   * @param array $trace Массив стека вызовов
   * @return array Структурированный JSON-массив
   */
  private function formatStackTrace(array $trace): array
  {
    $formattedTrace = [];
    
    foreach ($trace as $i => $frame) {
      $formattedFrame = [
        'level'    => $i,
        'function' => $frame['function'] ?? 'unknown',
        'file'     => $frame['file'] ?? 'unknown',
        'line'     => $frame['line'] ?? 'unknown',
        'args'     => $frame['args'] ?? [],
      ];
      
      $formattedTrace[] = $formattedFrame;
    }
    
    return $formattedTrace;
  }
}
