<?php

namespace AC\core\system\modules\modComm\http;

use AC\core\system\data\DataTrait;
use InvalidArgumentException;

/**
 * Класс для представления ответов между модулями.
 *
 * Этот класс инкапсулирует структуру ответа, возвращаемого модулем
 * в результате обработки входящего запроса. Предоставляет:
 * - Стандартизированную структуру ответа с полями success, statusCode, message
 * - Методы для создания типовых ответов (success, error, notFound и т.д.)
 * - Поддержку метаданных и временных меток
 * - Сериализацию в JSON и восстановление из различных форматов
 * - Fluent интерфейс для цепочки вызовов
 *
 * @package AC\core\system\modules\modComm\http
 * @since   1.0.0
 */
class ModCommResponse
{
  use DataTrait;
  
  /**
   * Флаг успешности выполнения операции
   *
   * @var bool true если операция выполнена успешно, false при ошибке
   */
  private bool $success;
  
  /**
   * HTTP-код статуса ответа
   *
   * Стандартные коды: 200 (OK), 400 (Bad Request), 401 (Unauthorized),
   * 403 (Forbidden), 404 (Not Found), 500 (Internal Server Error)
   *
   * @var int HTTP-код статуса ответа
   */
  private int $statusCode;
  
  /**
   * Сообщение, связанное с результатом выполнения операции
   *
   * @var string Текстовое описание результата или ошибки
   */
  private string $message;
  
  /**
   * Дополнительные метаданные ответа
   *
   * Может содержать информацию о времени выполнения, версии API,
   * дополнительных параметрах и служебных данных
   *
   * @var array<string, mixed> Массив метаданных ответа
   */
  private array $metadata;
  
  /**
   * Временная метка создания ответа
   *
   * @var float Время создания ответа в микросекундах
   */
  private float $timestamp;
  
  /**
   * Конструктор класса ModuleResponse.
   *
   * Создает новый объект ответа с указанными параметрами.
   * Автоматически устанавливает временную метку создания.
   *
   * @param bool   $success    Флаг успешности операции
   * @param array  $data       Данные, передаваемые в ответе (по умолчанию пустой массив)
   * @param int    $statusCode HTTP-код статуса (по умолчанию 200)
   * @param string $message    Сообщение (по умолчанию пустая строка)
   * @param array  $metadata   Метаданные (по умолчанию пустой массив)
   */
  public function __construct(bool $success, array $data = [], int $statusCode = 200, string $message = '', array $metadata = [])
  {
    $this->success = $success;
    $this->setData($data);
    $this->statusCode = $statusCode;
    $this->message    = $message;
    $this->metadata   = $metadata;
    $this->timestamp  = microtime(true);
  }
  
  /**
   * Создать успешный ответ.
   *
   * Статический метод для быстрого создания ответа об успешном
   * выполнении операции с кодом 200.
   *
   * @param array  $data     Данные, которые будут возвращены (по умолчанию пустой массив)
   * @param string $message  Сообщение об успехе (по умолчанию 'Success')
   * @param array  $metadata Метаданные (по умолчанию пустой массив)
   * @return self Новый объект успешного ответа
   */
  public static function success(array $data = [], string $message = 'Success', array $metadata = []): self
  {
    return new self(true, $data, 200, $message, $metadata);
  }
  
  /**
   * Создать ответ с ошибкой.
   *
   * Статический метод для быстрого создания ответа об ошибке
   * с указанным HTTP-кодом статуса.
   *
   * @param string $message    Сообщение об ошибке (по умолчанию 'Error')
   * @param int    $statusCode HTTP-код ошибки (по умолчанию 500)
   * @param array  $data       Дополнительные данные (по умолчанию пустой массив)
   * @param array  $metadata   Метаданные (по умолчанию пустой массив)
   * @return self Новый объект ответа с ошибкой
   */
  public static function error(string $message = 'Error', int $statusCode = 500, array $data = [], array $metadata = []): self
  {
    return new self(false, $data, $statusCode, $message, $metadata);
  }
  
  /**
   * Создать ответ "не найдено".
   *
   * Статический метод для создания ответа с кодом 404,
   * когда запрашиваемый ресурс не найден.
   *
   * @param string $message Сообщение об ошибке (по умолчанию 'Not Found')
   * @param array  $data    Дополнительные данные (по умолчанию пустой массив)
   * @return self Новый объект ответа "не найдено"
   */
  public static function notFound(string $message = 'Not Found', array $data = []): self
  {
    return new self(false, $data, 404, $message);
  }
  
  /**
   * Создать ответ "не авторизован".
   *
   * Статический метод для создания ответа с кодом 401,
   * когда пользователь не авторизован для выполнения операции.
   *
   * @param string $message Сообщение об ошибке (по умолчанию 'Unauthorized')
   * @param array  $data    Дополнительные данные (по умолчанию пустой массив)
   * @return self Новый объект ответа "не авторизован"
   */
  public static function unauthorized(string $message = 'Unauthorized', array $data = []): self
  {
    return new self(false, $data, 401, $message);
  }
  
  /**
   * Создать ответ "запрещено".
   *
   * Статический метод для создания ответа с кодом 403,
   * когда доступ к ресурсу запрещен.
   *
   * @param string $message Сообщение об ошибке (по умолчанию 'Forbidden')
   * @param array  $data    Дополнительные данные (по умолчанию пустой массив)
   * @return self Новый объект ответа "запрещено"
   */
  public static function forbidden(string $message = 'Forbidden', array $data = []): self
  {
    return new self(false, $data, 403, $message);
  }
  
  /**
   * Создать ответ "неверный запрос".
   *
   * Статический метод для создания ответа с кодом 400,
   * когда входящий запрос некорректен или содержит ошибки.
   *
   * @param string $message Сообщение об ошибке (по умолчанию 'Bad Request')
   * @param array  $data    Дополнительные данные (по умолчанию пустой массив)
   * @return self Новый объект ответа "неверный запрос"
   */
  public static function badRequest(string $message = 'Bad Request', array $data = []): self
  {
    return new self(false, $data, 400, $message);
  }
  
  /**
   * Проверить, успешен ли ответ.
   *
   * Возвращает true если операция выполнена успешно,
   * false если произошла ошибка.
   *
   * @return bool Флаг успешности операции
   */
  public function isSuccess(): bool
  {
    return $this->success;
  }
  
  /**
   * Получить HTTP-код статуса.
   *
   * Возвращает числовой код HTTP-статуса, который
   * указывает на результат выполнения операции.
   *
   * @return int HTTP-код статуса ответа
   */
  public function getStatusCode(): int
  {
    return $this->statusCode;
  }
  
  /**
   * Получить сообщение, связанное с ответом.
   *
   * Возвращает текстовое описание результата выполнения
   * операции или сообщение об ошибке.
   *
   * @return string Текстовое сообщение ответа
   */
  public function getMessage(): string
  {
    return $this->message;
  }
  
  /**
   * Получить метаданные.
   *
   * Возвращает массив дополнительных данных, связанных
   * с ответом (время выполнения, версия API и т.д.).
   *
   * @return array<string, mixed> Массив метаданных
   */
  public function getMetadata(): array
  {
    return $this->metadata;
  }
  
  /**
   * Получить временную метку создания ответа.
   *
   * Возвращает время создания ответа в микросекундах
   * с момента запуска системы.
   *
   * @return float Временная метка создания ответа
   */
  public function getTimestamp(): float
  {
    return $this->timestamp;
  }
  
  /**
   * Получить значение из метаданных по ключу.
   *
   * Извлекает конкретное значение из метаданных по указанному ключу.
   * Если ключ не найден, возвращает значение по умолчанию.
   *
   * @param string $key     Ключ для поиска значения в метаданных
   * @param mixed  $default Значение по умолчанию если ключ не найден
   * @return mixed Найденное значение или значение по умолчанию
   */
  public function getMetadataValue(string $key, mixed $default = null): mixed
  {
    return $this->metadata[$key] ?? $default;
  }
  
  /**
   * Проверить, содержит ли метаданные указанный ключ.
   *
   * Проверяет наличие указанного ключа в массиве метаданных.
   * Использует array_key_exists для корректной проверки null значений.
   *
   * @param string $key Ключ для проверки наличия в метаданных
   * @return bool Возвращает true если ключ существует
   */
  public function hasMetadataKey(string $key): bool
  {
    return array_key_exists($key, $this->metadata);
  }
  
  /**
   * Получить ответ в виде ассоциативного массива.
   *
   * Преобразует объект ответа в массив, содержащий все
   * основные поля и данные для сериализации.
   *
   * @return array<string, mixed> Массив с данными ответа
   */
  public function toArray(): array
  {
    return [
      'success'     => $this->success,
      'data'        => $this->getData(),
      'status_code' => $this->statusCode,
      'message'     => $this->message,
      'metadata'    => $this->metadata,
      'timestamp'   => $this->timestamp
    ];
  }
  
  /**
   * Получить ответ в виде JSON-строки.
   *
   * Сериализует объект ответа в JSON формат с красивым
   * форматированием для удобства чтения.
   *
   * @return string JSON-представление ответа
   */
  public function toJson(): string
  {
    return json_encode($this->toArray(), JSON_PRETTY_PRINT);
  }
  
  /**
   * Создать объект ModuleResponse из массива данных.
   *
   * Статический метод для восстановления объекта ответа из
   * ассоциативного массива. Позволяет десериализовать ответ
   * из ранее сохраненных данных.
   *
   * @param array<string, mixed> $data Массив данных для восстановления ответа
   * @return self Новый объект ответа
   */
  public static function fromArray(array $data): self
  {
    $response = new self(
      $data['success'] ?? false,
      $data['data'] ?? [],
      $data['status_code'] ?? 200,
      $data['message'] ?? '',
      $data['metadata'] ?? []
    );
    
    if (isset($data['timestamp'])) {
      $response->timestamp = $data['timestamp'];
    }
    
    return $response;
  }
  
  /**
   * Создать объект ModuleResponse из JSON-строки.
   *
   * Статический метод для восстановления объекта ответа из
   * JSON строки. Декодирует JSON и создает объект через fromArray.
   * Выбрасывает исключение при некорректном JSON.
   *
   * @param string $json JSON-строка для восстановления ответа
   * @return self Новый объект ответа
   * @throws InvalidArgumentException Если JSON некорректный
   */
  public static function fromJson(string $json): self
  {
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new InvalidArgumentException('Invalid JSON format: ' . json_last_error_msg());
    }
    
    return self::fromArray($data);
  }
  
  /**
   * Добавить метаданные к текущему ответу.
   *
   * Объединяет существующие метаданные с новыми и возвращает
   * текущий объект для поддержки цепочки вызовов (fluent interface).
   *
   * @param array<string, mixed> $metadata Метаданные для добавления
   * @return self Возвращает текущий объект для цепочки вызовов
   */
  public function withMetadata(array $metadata): self
  {
    $this->metadata = array_merge($this->metadata, $metadata);
    return $this;
  }
}
