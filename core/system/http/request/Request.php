<?php

namespace AC\core\system\http\request;

use AC\core\system\helpers\StringHelper;
use AC\core\system\exceptions\http\RequestValidationException;
use AC\core\system\http\message\Message;
use AC\core\system\http\url\URI;
use AC\core\system\http\url\Url;
use AC\core\system\validators\FormatRules;
use StdClass;

/**
 * Representation of an HTTP request.
 */
class Request extends Message implements RequestInterface
{
  use RequestTrait;
  
  /**
   * Request method.
   *
   * @var string
   */
  protected $method;
  
  /**
   * A URI instance.
   *
   * @var Url
   */
  protected $url;
  
  protected $segments = [];
  
  static private ?array $post    = null;
  static private ?array $get     = null;
  static private ?array $request = null;
  
  /**
   * Constructor.
   *
   */
  public function __construct()
  {
    if (empty($this->method)) {
      $this->method = $this->getServer('REQUEST_METHOD') ?: 'GET';
    }
    
    if (empty($this->url)) {
      $this->url = \Service::uri();
    }
    $this->instance();
  }
  
  /**
   * Validate an IP address
   *
   * @param string $ip    IP Address
   * @param string $which IP protocol: 'ipv4' or 'ipv6'
   */
  public function isValidIP($ip = null, $which = null)
  {
    return (new FormatRules())->valid_ip($ip, $which);
  }
  
  /**
   * Get the request method.
   *
   * @param bool $upper Whether to return in upper or lower case.
   */
  public function getMethod($upper = false)
  {
    return ($upper) ? strtoupper($this->method) : strtolower($this->method);
  }
  
  /**
   * Sets the request method. Used when spoofing the request.
   *
   * @return Request
   *
   */
  public function setMethod($method)
  {
    $this->method = $method;
    
    return $this;
  }
  
  /**
   * Returns an instance with the specified method.
   *
   * @param string $method
   *
   * @return static
   */
  public function withMethod($method)
  {
    $request = clone $this;
    
    $request->method = $method;
    
    return $request;
  }
  
  /**
   * Retrieves the URI instance.
   *
   * @return URI
   */
  public function getUrl()
  {
    return $this->url;
  }
  
  /** При подаче объекта или массива обновляет их данными из $_REQUEST
   *
   * @param array|object $data
   *
   * @param array        $necessarily - обязательные
   *
   * @param string       $typeReturn  - тип возврата
   *
   * @return array|StdClass
   */
  public function loadAndChangeValue($data, $necessarily = [], $typeReturn = 'object')
  {
    $_data = new StdClass();
    foreach ($data as $key => $value) {
      $_value = $this->_($key);
      if ($_value !== null) {
        $_value = is_array($_value) ? (object)$_value : $_value;
      } elseif (!empty($necessarily) && isset($necessarily[$key])) {
        $_value = $necessarily[$key];
      } else {
        $_value = $value;
      }
      $_data->{$key} = $_value;
    }
    
    return match ($typeReturn) {
      'array' => (array)$_data,
      default => $_data,
    };
  }
  
  /** При подаче объекта или массива обновляет их данными из $_REQUEST
   *
   * @param $data
   *
   * @param $necessarily - обязательные
   *
   * @return StdClass
   */
  public function load($data, $necessarily = [])
  {
    $_data = new StdClass();
    foreach ($data as $key) {
      $value = $this->_($key);
      if ($value !== null) {
        $value         = is_array($value) ? (object)$value : $value;
        $_data->{$key} = $value;
      } elseif (!empty($necessarily) && isset($necessarily[$key])) {
        $_data->{$key} = $necessarily[$key];
      }
    }
    
    return $_data;
  }
  
  /** Получить значение из запросов $_REQUEST
   *
   * @param      $alias
   * @param null $default
   *
   * @return mixed|null
   */
  public function _($alias, $default = null): mixed
  {
    
    return $this->_get($alias) ?? ($this->_post($alias) ?? $default);
  }

  /**
   * Получает параметр и проверяет его существующими модельными валидаторами.
   *
   * request сохраняет приоритет GET (включая маршруты) над POST, без cookies.
   * Значение по умолчанию применяется только к отсутствующему необязательному параметру.
   * Метод не изменяет исходный запрос и не выполняет автоматическое HTML-экранирование.
   *
   * @param string $alias Имя параметра или его стандартный camelCase/snake_case alias
   * @param string $source get, post или request
   * @param string|array $rules Тип валидатора, строка required|integer или правила одного поля
   * @param mixed $default Значение для отсутствующего необязательного параметра
   * @return mixed Проверенное значение после преобразований валидаторов
   * @throws RequestValidationException При неверном пользовательском вводе
   * @throws \InvalidArgumentException При неверном источнике или правилах
   */
  public function validated(string $alias, string $source, string|array $rules, mixed $default = null): mixed
  {
    $source = strtolower($source);
    $value = match ($source) {
      'get' => $this->_get($alias),
      'post' => $this->_post($alias),
      'request' => $this->_($alias),
      default => throw new \InvalidArgumentException('Unsupported request source: ' . $source),
    };

    // Внутреннее имя исключает пересечение alias со свойствами самого Validation.
    $validation = \Service::validation(false)->setRule('inputValue', $alias, $rules);
    if ($value === null && !in_array('inputValue', $validation->getValidateRulesByType('required'), true)) {
      $value = $default;
    }
    if (!$validation->run(['inputValue' => $value], true)) {
      throw new RequestValidationException($alias, $source, $validation->getErrors()['inputValue'] ?? []);
    }

    return $validation->getValidated()['inputValue'];
  }
  
  public function all()
  {
    if (self::$request === null) {
      self::$request = $_REQUEST;
    }
    return $_REQUEST;
  }
  
  private function instance()
  {
    if (self::$request === null) {
      self::$request = $_REQUEST;
    }
    if (self::$post === null) {
      self::$post = $_POST;
    }
    if (self::$get === null) {
      self::$get = $_GET;
    }
  }
  
  /**
   *  Получить данные POST запроса
   *
   * @param bool $alias
   * @param null $default
   *
   * @return mixed
   */
  public function _post($alias = false, $default = null)
  {
    if (self::$post === null) {
      self::$post = $_POST;
    }
    if ($alias !== false) {
      return $this->findByAlias($alias, self::$post, $default);
    }
    
    return self::$post;
  }
  
  public function findByAlias($alias, $data, $default = null, $ifQuery = null)
  {
    if (!is_array($alias)) {
      $alias = [$alias, StringHelper::camelCaseToUnderscore($alias), lcfirst(StringHelper::underscoreToCamelCase($alias))];
    }
    
    return $this->findByAliasArray($alias, $data, $default, $ifQuery);
  }
  
  public function findByAliasArray($alias, $data, $default = null, $ifQuery = null)
  {
    if (in_array($ifQuery, ['post', 'get'])) {
      $data = array_merge($this->{'_' . $ifQuery}(), $data);
    }
    if (is_array($alias)) {
      foreach ($alias as $key) {
        if (isset($data[$key])) {
          return $data[$key];
        }
      }
    }
    
    return $default;
  }
  
  /**
   *  Получить данные GET запроса
   *
   * @param mixed $alias
   * @param null  $default
   *
   * @return mixed
   */
  public function _get($alias = false, $default = null): mixed
  {
    if (self::$get === null) {
      self::$get = $_GET;
    }
    
    $get = array_merge(self::$get, $this->segments);
    if ($alias !== false) {
      return $this->findByAlias($alias, $get, $default);
    }
    
    return $get;
  }
  
  public function isPost()
  {
    return count($_POST) > 0;
  }
  
  public function isGet()
  {
    return count($this->_get()) > 0;
  }
  
  public function checkPost($alias)
  {
    if (is_array($alias)) {
      return $this->checkArrayAliases($alias, $_POST);
    }
    return isset($_POST[$alias]);
  }
  
  public function checkArrayAliases(array $aliases, $data = []): bool
  {
    $count = count($aliases);
    foreach ($aliases as $alias) {
      if (isset($data[$alias])) {
        $count--;
      }
    }
    
    return !$count;
  }
  
  public function checkGet($alias)
  {
    if (is_array($alias)) {
      return $this->checkArrayAliases($alias, $this->_get());
    }
    
    return isset($this->_get()[$alias]);
  }
  
  public function check($alias)
  {
    return self::checkGet($alias) || self::checkPost($alias);
  }
  
  public function checkPregMatch($alias)
  {
    return $this->checkPregMatchGet($alias) || $this->checkPregMatchPost($alias);
  }
  
  public function checkPregMatchGet($alias)
  {
    return $this->pregMatch($alias, $this->_get());
  }
  
  public function checkPregMatchPost($alias)
  {
    return $this->pregMatch($alias, $this->_post());
  }
  
  public function parserSegments($segments = [])
  {
    for ($i = 0, $iMax = count($segments); $i < $iMax; $i += 2) {
      $this->segments[$segments[$i]] = $segments[$i + 1];
    }
    
    return $this->segments;
  }
  
  public function getSegment($key, $default = null)
  {
    return $this->segments[$key] ?? $default;
  }
  
  protected function pregMatch($pattern, $data)
  {
    foreach (array_keys($data) as $datum) {
      if (preg_match('/' . $pattern . '/i', $datum)) {
        return $datum;
      }
    }
    
    return null;
  }
  
  public function set($method, $key, $value): void
  {
    switch (strtolower($method)) {
      case 'post':
        self::$post[$key]    = $value;
        self::$request[$key] = $value;
        $_POST[$key]         = $value;
        $_REQUEST[$key]      = $value;
        break;
      case 'get':
        self::$get[$key]     = $value;
        self::$request[$key] = $value;
        $_GET[$key]          = $value;
        $_REQUEST[$key]      = $value;
        break;
      case 'request':
        self::$request[$key] = $value;
        $_REQUEST[$key]      = $value;
        break;
    }
  }
}
