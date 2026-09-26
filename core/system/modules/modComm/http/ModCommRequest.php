<?php

namespace AC\core\system\modules\modComm\http;

use AC\core\system\data\DataTrait;
use AC\core\system\helpers\JsonHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\helpers\UniqHelper;

/**
 * Класс для представления запросов между модулями.
 *
 * Этот класс инкапсулирует всю информацию о запросе, отправляемом
 * от одного модуля к другому в системе межмодульного взаимодействия.
 * Включает в себя:
 * - Целевой модуль для обработки запроса
 * - Действие для выполнения
 * - Данные запроса
 * - Опции и метаданные
 * - Уникальный идентификатор и временные метки
 *
 * Поддерживает сериализацию в JSON и восстановление из различных форматов.
 *
 * @package AC\core\system\modules\modComm\http
 * @since   1.0.0
 */
class ModCommRequest
{
  use DataTrait;
  
  /**
   * Идентификатор целевого модуля для обработки запроса
   *
   * @var string Имя модуля, к которому направлен запрос
   */
  private string $targetModule;
  
  /**
   * Название действия для выполнения в целевом модуле
   *
   * @var string Действие в формате camelCase
   */
  private string $action;
  
  /**
   * Дополнительные опции и параметры запроса
   *
   * @var array<string, mixed> Массив опций запроса
   */
  private array $options;
  
  /**
   * Уникальный идентификатор запроса для отслеживания
   *
   * @var string Уникальный ID в формате 'req_xxxxxxxx'
   */
  private string $requestId;
  
  /**
   * Временная метка создания запроса в микросекундах
   *
   * @var float Время создания запроса
   */
  private float $timestamp;
  
  /**
   * Конструктор класса.
   *
   * Создает новый объект запроса с указанными параметрами.
   * Автоматически генерирует уникальный ID и временную метку.
   * Преобразует название действия в camelCase формат.
   *
   * @param string $targetModule Имя целевого модуля для обработки
   * @param string $action       Название действия для выполнения
   * @param array  $data         Данные запроса (по умолчанию пустой массив)
   * @param array  $options      Опции запроса (по умолчанию пустой массив)
   */
  public function __construct(string $targetModule, string $action, array $data = [], array $options = [])
  {
    $this->targetModule = $targetModule;
    $this->action       = StringHelper::underscoreToCamelCase($action);
    $this->setData($data);
    $this->options   = $options;
    $this->requestId = $this->generateRequestId();
    $this->timestamp = microtime(true);
  }
  
  /**
   * Получить имя целевого модуля.
   *
   * Возвращает название модуля, который должен обработать
   * данный запрос.
   *
   * @return string Имя целевого модуля
   */
  public function getTargetModule(): string
  {
    return $this->targetModule;
  }
  
  /**
   * Получить название действия.
   *
   * Возвращает название действия в формате camelCase,
   * которое должно быть выполнено в целевом модуле.
   *
   * @return string Название действия в camelCase
   */
  public function getAction(): string
  {
    return $this->action;
  }
  
  /**
   * Получить опции запроса.
   *
   * Возвращает массив дополнительных параметров и опций,
   * переданных вместе с запросом.
   *
   * @return array<string, mixed> Массив опций запроса
   */
  public function getOptions(): array
  {
    return $this->options;
  }
  
  /**
   * Получить уникальный идентификатор запроса.
   *
   * Возвращает уникальный ID, который был автоматически
   * сгенерирован при создании запроса.
   *
   * @return string Уникальный идентификатор запроса
   */
  public function getRequestId(): string
  {
    return $this->requestId;
  }
  
  /**
   * Получить временную метку запроса.
   *
   * Возвращает время создания запроса в микросекундах
   * с момента запуска системы.
   *
   * @return float Временная метка создания запроса
   */
  public function getTimestamp(): float
  {
    return $this->timestamp;
  }
  
  /**
   * Получить конкретное значение из опций запроса.
   *
   * Извлекает значение по указанному ключу из опций запроса.
   * Если ключ не найден, возвращает значение по умолчанию.
   *
   * @param string $key     Ключ для поиска значения в опциях
   * @param mixed  $default Значение по умолчанию если ключ не найден
   * @return mixed Найденное значение или значение по умолчанию
   */
  public function getOptionValue(string $key, mixed $default = null): mixed
  {
    return $this->options[$key] ?? $default;
  }
  
  /**
   * Проверить, содержит ли запрос определенный ключ в опциях.
   *
   * Проверяет наличие указанного ключа в массиве опций запроса.
   * Использует array_key_exists для корректной проверки null значений.
   *
   * @param string $key Ключ для проверки наличия в опциях
   * @return bool Возвращает true если ключ существует
   */
  public function hasOptionKey(string $key): bool
  {
    return array_key_exists($key, $this->options);
  }
  
  /**
   * Получить запрос в виде массива.
   *
   * Преобразует объект запроса в ассоциативный массив,
   * содержащий все основные свойства и данные.
   *
   * @return array<string, mixed> Массив с данными запроса
   */
  public function toArray(): array
  {
    return [
      'request_id'    => $this->requestId,
      'target_module' => $this->targetModule,
      'action'        => $this->action,
      'data'          => $this->getData(),
      'options'       => $this->options,
      'timestamp'     => $this->timestamp
    ];
  }
  
  /**
   * Получить запрос в виде JSON-строки.
   *
   * Сериализует объект запроса в JSON формат с красивым
   * форматированием для удобства чтения.
   *
   * @return string JSON-представление запроса
   */
  public function toJson(): string
  {
    return JsonHelper::encode($this->toArray(), JSON_PRETTY_PRINT);
  }
  
  /**
   * Создать объект запроса из массива.
   *
   * Статический метод для создания объекта запроса из
   * ассоциативного массива. Позволяет восстановить объект
   * из ранее сериализованных данных.
   *
   * @param array<string, mixed> $data Массив с данными запроса
   * @return self Новый объект запроса
   */
  public static function fromArray(array $data): self
  {
    $request = new self(
      $data['target_module'] ?? '',
      $data['action'] ?? '',
      $data['data'] ?? [],
      $data['options'] ?? []
    );
    
    if (isset($data['request_id'])) {
      $request->requestId = $data['request_id'];
    }
    
    if (isset($data['timestamp'])) {
      $request->timestamp = $data['timestamp'];
    }
    
    return $request;
  }
  
  /**
   * Создать объект запроса из JSON-строки.
   *
   * Статический метод для создания объекта запроса из
   * JSON строки. Декодирует JSON и создает объект через fromArray.
   *
   * @param string $json JSON-строка с данными запроса
   * @return self Новый объект запроса
   */
  public static function fromJson(string $json): self
  {
    $data = JsonHelper::decode($json, true);
    
    return self::fromArray($data);
  }
  
  /**
   * Генерация уникального ID запроса.
   *
   * Создает уникальный идентификатор для запроса в формате
   * 'req_xxxxxxxx' используя UniqHelper.
   *
   * @return string Уникальный идентификатор запроса
   */
  private function generateRequestId(): string
  {
    return UniqHelper::generateUniqId('req');
  }
  
  /**
   * Возвращает имя целевого модуля, для которого предназначен запрос.
   *
   * Алиас для getTargetModule() для обратной совместимости
   * и удобства использования.
   *
   * @return string Имя модуля в виде строки
   */
  public function getModuleName(): string
  {
    return $this->targetModule;
  }
  
  /**
   * Возвращает метаданные запроса в виде ассоциативного массива.
   *
   * Метаданные включают всю служебную информацию о запросе:
   * - request_id: уникальный идентификатор запроса
   * - target_module: имя целевого модуля
   * - action: название выполняемого действия
   * - options: опции запроса
   * - timestamp: временная метка создания запроса
   *
   * @return array<string, mixed> Ассоциативный массив с метаданными
   */
  public function getMetadata(): array
  {
    return [
      'request_id'    => $this->requestId,
      'target_module' => $this->targetModule,
      'action'        => $this->action,
      'options'       => $this->options,
      'timestamp'     => $this->timestamp
    ];
  }
  
  /**
   * Получить локаль запроса.
   *
   * Извлекает локаль из данных запроса или возвращает
   * текущую локаль системы по умолчанию.
   *
   * @return string Локаль запроса или системная локаль по умолчанию
   */
  public function getLocale(): string
  {
    return $this->getDataValue('locale') ?? config('lang')->getCurrentLang();
  }
}
