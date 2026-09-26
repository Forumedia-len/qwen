<?php

declare(strict_types=1);

namespace AC\core\system\helpers;

use AC\core\system\entities\enums\RedisKeyType;
use Redis;
use RedisException;
use JsonException;
use RuntimeException;

class RedisHelper
{
  private static ?Redis $redis     = null;
  private static bool   $connected = false;

  // ==========================================
  // Site Prefix Generation
  // ==========================================

  /**
   * Генерирует уникальный префикс для текущего сайта на основе BASE_HREF.
   * Поддерживает поддомены и подкаталоги (например, https://site.com/   или https://test.com/ck/  ).
   *
   * @return string Префикс вида "host__path" (например: "example.com", "test.com__ck")
   * @throws RuntimeException Если BASE_HREF не определён или некорректен
   */
  private static function getSitePrefix(): string
  {
    $rawUrl = defined('BASE_HREF') ? BASE_HREF : 'https://' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $host   = parse_url($rawUrl, PHP_URL_HOST);
    $path   = parse_url($rawUrl, PHP_URL_PATH);

    $host = strtolower($host ?: ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    $path = strtolower(trim($path ?? '', '/'));

    $path = preg_replace('/[^a-z0-9_]+/', '_', $path);
    $path = preg_replace('/_{2,}/', '_', $path);
    $path = trim($path, '_');

    return $host . ($path ? '__' . $path : '');
  }

  /**
   * Преобразует локальный ключ в полный ключ с префиксом текущего сайта.
   */
  public static function resolveKey(string $localKey): string
  {
    static $prefix = null;
    if ($prefix === null) {
      $prefix = self::getSitePrefix();
    }

    return rtrim($prefix . ':' . ltrim($localKey, ':'), ':');
  }

  // ==========================================
  // Connection Management
  // ==========================================

  /**
   * Redis::pconnect при true (значение по умолчанию). Иначе обычный connect.
   * Константу можно задать в config: defined('REDIS_PERSISTENT') || define('REDIS_PERSISTENT', true);
   * Отключить постоянное соединение: define('REDIS_PERSISTENT', false);
   */
  private static function usePersistentConnection(): bool
  {
    if (!defined('REDIS_PERSISTENT')) {
      define('REDIS_PERSISTENT', true);
    }

    return (bool)REDIS_PERSISTENT;
  }

  /**
   * Уникальный id пула phpredis: раздельные сокеты для разных хостов/портов/БД/пароля.
   */
  private static function persistentConnectionId(string $host, int $port): string
  {
    $db = defined('REDIS_DATABASE') ? (int)REDIS_DATABASE : 0;
    $pw = defined('REDIS_PASSWORD') ? (string)REDIS_PASSWORD : '';

    return 'ac_' . md5($host . "\0" . $port . "\0" . $db . "\0" . $pw);
  }

  private static function resetConnectionState(): void
  {
    if (self::$redis !== null) {
      try {
        self::$redis->close();
      } catch (RedisException|\Throwable) {
        // соединение уже мёртвое или сокет в невалидном состоянии
      }
      self::$redis     = null;
      self::$connected = false;
    }
  }

  private static function getConnection(): Redis
  {
    if (self::$redis === null || !self::$connected) {
      self::$redis = new Redis();

      try {
        $host    = defined('REDIS_HOST') ? REDIS_HOST : '127.0.0.1';
        $port    = defined('REDIS_PORT') ? (int)REDIS_PORT : 6379;
        $timeout = 2.5;

        $connected = self::usePersistentConnection()
          ? self::$redis->pconnect($host, $port, $timeout, self::persistentConnectionId($host, $port))
          : self::$redis->connect($host, $port, $timeout);

        if (!$connected) {
          throw new RedisException("Failed to connect to Redis at {$host}:{$port}");
        }

        self::$connected = true;

        if (defined('REDIS_PASSWORD') && REDIS_PASSWORD) {
          if (!self::$redis->auth(REDIS_PASSWORD)) {
            throw new RedisException('Redis authentication error');
          }
        }

        if (defined('REDIS_DATABASE') && REDIS_DATABASE) {
          if (!self::$redis->select((int)REDIS_DATABASE)) {
            throw new RedisException('Failed to select Redis database');
          }
        }
      } catch (RedisException $e) {
        self::resetConnectionState();
        throw $e;
      }
    }

    return self::$redis;
  }

  public static function redis(): Redis
  {
    return self::getConnection();
  }

  // ==========================================
  // Private Helpers (JSON & Redis Operations)
  // ==========================================

  /**
   * Кодирует значение в JSON для записи в Redis
   *
   * @throws JsonException
   */
  private static function encode(mixed $value): string
  {
    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
      throw new JsonException('Failed to encode value to JSON');
    }

    return $encoded;
  }

  /**
   * Декодирует JSON-строку из Redis в PHP-значение
   */
  private static function decode(string|false $value): mixed
  {
    if ($value === false) {
      return null;
    }
    try {
      return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
      return null;
    }
  }

  /**
   * Декодирует массив полей хеша (field => json_string)
   */
  private static function decodeHashFields(array $data): array
  {
    if (empty($data)) {
      return [];
    }

    return array_map(function ($value) {
      return self::decode($value);
    }, $data);
  }

  /**
   * Выполняет Redis-операцию с обработкой исключений
   * @template T
   *
   * @param callable(Redis): T $operation
   * @param T                  $default Значение при ошибке
   *
   * @return T
   */
  private static function withRedis(callable $operation, mixed $default): mixed
  {
    try {
      return $operation(self::getConnection());
    } catch (RedisException) {
      self::resetConnectionState();

      return $default;
    }
  }

  // ==========================================
  // String Operations
  // ==========================================

  public static function get(string $localKey, ?string $keyData = null, mixed $default = null): mixed
  {
    $fullKey = self::resolveKey($localKey);
    $value   = self::withRedis(
      fn(Redis $redis) => self::decode($redis->get($fullKey)),
      null
    );

    if ($keyData === null) {
      return is_array($value) ? $value : ($value ?? $default);
    }

    if (!is_array($value) || !array_key_exists($keyData, $value)) {
      return $default;
    }

    return $value[$keyData];
  }

  /**
   * Добавляет или обновляет поле в JSON-массиве, хранящемся по ключу.
   * Если значение отсутствует или не массив — инициализируется пустым массивом.
   */
  public static function setKey(string $localKey, string $field, mixed $value, ?int $ttl = null): bool
  {
    $data = self::get($localKey);
    if (!is_array($data)) {
      $data = [];
    }
    $data[$field] = $value;

    return self::set($localKey, $data, $ttl);
  }

  /**
   * Добавляет или обновляет несколько полей в JSON-массиве, хранящемся по ключу.
   */
  public static function setKeys(string $localKey, array $fields, ?int $ttl = null): bool
  {
    $data = self::get($localKey);
    if (!is_array($data)) {
      $data = [];
    }
    $data = array_merge($data, $fields);

    return self::set($localKey, $data, $ttl);
  }

  public static function set(string $localKey, mixed $value, ?int $ttl = null): bool
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(function (Redis $redis) use ($fullKey, $value, $ttl) {
      $jsonValue = self::encode($value);

      return $ttl !== null && $ttl > 0
        ? $redis->setex($fullKey, $ttl, $jsonValue)
        : $redis->set($fullKey, $jsonValue);
    }, false);
  }

  public static function delete(string $localKey): int
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(
      fn(Redis $redis) => $redis->del($fullKey),
      0
    );
  }

  public static function exists(string $localKey): bool
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(
      fn(Redis $redis) => (bool)$redis->exists($fullKey),
      false
    );
  }

  public static function setExpire(string $localKey, int $ttl): bool
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(
      fn(Redis $redis) => $redis->expire($fullKey, $ttl),
      false
    );
  }

  public static function getTTL(string $localKey): int
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(
      fn(Redis $redis) => $redis->ttl($fullKey),
      -2
    );
  }

  public static function increment(string $localKey, int $by = 1): int|false
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(
      fn(Redis $redis) => $redis->incrby($fullKey, $by),
      false
    );
  }

  public static function decrement(string $localKey, int $by = 1): int|false
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(
      fn(Redis $redis) => $redis->decrby($fullKey, $by),
      false
    );
  }

  // ==========================================
  // Hash Operations
  // ==========================================

  public static function hSet(string $localKey, string $field, mixed $value): int
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(function (Redis $redis) use ($fullKey, $field, $value) {
      $jsonValue = self::encode($value);

      return $redis->hSet($fullKey, $field, $jsonValue);
    }, 0);
  }

  public static function hMSet(string $localKey, array $fields): bool
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(function (Redis $redis) use ($fullKey, $fields) {
      $jsonFields = array_map(function ($value) {
        return self::encode($value);
      }, $fields);

      return $redis->hMSet($fullKey, $jsonFields);
    }, false);
  }

  public static function hGet(string $localKey, string $field): mixed
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(
      fn(Redis $redis) => self::decode($redis->hGet($fullKey, $field)),
      null
    );
  }

  public static function hGetAll(string $localKey): array
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(function (Redis $redis) use ($fullKey) {
      $raw = $redis->hGetAll($fullKey);

      return is_array($raw) ? self::decodeHashFields($raw) : [];
    }, []);
  }

  public static function hDel(string $localKey, string $field): int
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(
      fn(Redis $redis) => $redis->hDel($fullKey, $field),
      0
    );
  }

  public static function hExists(string $localKey, string $field): bool
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(
      fn(Redis $redis) => (bool)$redis->hExists($fullKey, $field),
      false
    );
  }

  public static function hKeys(string $localKey): array
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(function (Redis $redis) use ($fullKey) {
      $keys = $redis->hKeys($fullKey);

      return is_array($keys) ? $keys : [];
    }, []);
  }

  public static function hLen(string $localKey): int
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(
      fn(Redis $redis) => $redis->hLen($fullKey),
      0
    );
  }

  // ==========================================
  // Universal Methods
  // ==========================================

  public static function fetch(string $localKey): mixed
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(function (Redis $redis) use ($fullKey, $localKey) {
      $type = self::type($localKey);

      return match ($type) {
        RedisKeyType::STRING => self::decode($redis->get($fullKey)),
        RedisKeyType::HASH   => self::decodeHashFields($redis->hGetAll($fullKey)),
        RedisKeyType::LIST   => $redis->lRange($fullKey, 0, -1),
        RedisKeyType::SET    => $redis->sMembers($fullKey),
        RedisKeyType::ZSET   => $redis->zRange($fullKey, 0, -1, true),
        default              => null,
      };
    }, null);
  }

  public static function store(string $localKey, mixed $value, ?int $ttl = null, bool $forceDelete = false): bool
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(function (Redis $redis) use ($fullKey, $localKey, $value, $ttl, $forceDelete) {
      if ($forceDelete) {
        $type = self::type($localKey);
        if ($type->exists() && !$type->isString()) {
          $redis->del($fullKey);
        }
      }

      $jsonValue = self::encode($value);

      return $ttl !== null && $ttl > 0
        ? $redis->setex($fullKey, $ttl, $jsonValue)
        : $redis->set($fullKey, $jsonValue);
    }, false);
  }

  public static function storeHash(string $localKey, array $fields, bool $forceDelete = false): bool
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(function (Redis $redis) use ($fullKey, $localKey, $fields, $forceDelete) {
      if ($forceDelete) {
        $type = self::type($localKey);
        if ($type->exists() && !$type->isHash()) {
          $redis->del($fullKey);
        }
      }

      $jsonFields = array_map(function ($value) {
        return self::encode($value);
      }, $fields);

      return $redis->hMSet($fullKey, $jsonFields);
    }, false);
  }

  // ==========================================
  // Type Checking Methods
  // ==========================================

  public static function type(string $localKey): RedisKeyType
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(function (Redis $redis) use ($fullKey) {
      $typeInt = $redis->type($fullKey);

      return RedisKeyType::fromInt($typeInt);
    }, RedisKeyType::NONE);
  }

  public static function getTypeName(string $localKey): string
  {
    return self::type($localKey)->getName();
  }

  public static function keyExists(string $localKey): bool
  {
    return self::type($localKey)->exists();
  }

  // ==========================================
  // Scan & Keys Methods (осторожно: паттерны!)
  // ==========================================

  public static function scanKeys(string $pattern = '*', int $count = 100): array
  {
    $fullPattern = self::resolveKey($pattern);

    return self::withRedis(function (Redis $redis) use ($fullPattern, $count) {
      $keys   = [];
      $cursor = null;

      do {
        $foundKeys = $redis->scan($cursor, $fullPattern, $count);
        if ($foundKeys === false) {
          break;
        }

        if (is_array($foundKeys) && !empty($foundKeys)) {
          $prefixLen = strlen(self::getSitePrefix()) + 1;
          foreach ($foundKeys as $key) {
            $keys[] = substr($key, $prefixLen);
          }
        }
      } while ($cursor != 0);

      return array_unique($keys);
    }, []);
  }

  public static function getKeys(string $pattern): array
  {
    $fullPattern = self::resolveKey($pattern);

    return self::withRedis(function (Redis $redis) use ($fullPattern) {
      error_log('WARNING: RedisHelper::getKeys() uses blocking KEYS command.');
      $keys = $redis->keys($fullPattern);
      if (!is_array($keys)) {
        return [];
      }
      $prefixLen = strlen(self::getSitePrefix()) + 1;

      return array_map(fn($k) => substr($k, $prefixLen), $keys);
    }, []);
  }

  public static function scanKeysIterator(string $pattern = '*', int $count = 100): \Generator
  {
    $fullPattern = self::resolveKey($pattern);
    try {
      $redis  = self::getConnection();
      $cursor = 0;

      do {
        $foundKeys = $redis->scan($cursor, $fullPattern, $count);
        if ($foundKeys === false) {
          break;
        }

        if (is_array($foundKeys)) {
          $prefixLen = strlen(self::getSitePrefix()) + 1;
          foreach ($foundKeys as $key) {
            yield substr($key, $prefixLen);
          }
        }
      } while ($cursor != 0);
    } catch (RedisException) {
      self::resetConnectionState();

      return;
    }
  }

  public static function ping(): bool
  {
    return self::withRedis(function (Redis $redis) {
      $result = $redis->ping();

      return $result === true || $result === '+PONG' || $result === 'PONG';
    }, false);
  }

  /**
   * Сбрасывает ссылку в процессе. При pconnect сокет в PHP 8+ обычно возвращается в пул расширения.
   */
  public static function close(): void
  {
    if (self::$redis !== null) {
      self::$redis->close();
      self::$redis     = null;
      self::$connected = false;
    }
  }

  public static function info(): array|false
  {
    return self::withRedis(
      fn(Redis $redis) => $redis->info(),
      false
    );
  }

  public static function memory(string $localKey): int|false
  {
    $fullKey = self::resolveKey($localKey);

    return self::withRedis(function (Redis $redis) use ($fullKey) {
      if (!method_exists($redis, 'memoryUsage')) {
        return false;
      }
      $usage = $redis->memoryUsage($fullKey);

      return $usage === false ? false : (int)$usage;
    }, false);
  }

  public static function memoryInfo(): array|false
  {
    return self::withRedis(function (Redis $redis) {
      $info = $redis->info('memory');

      return is_array($info) ? $info : false;
    }, false);
  }
}