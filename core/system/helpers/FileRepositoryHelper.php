<?php

namespace AC\core\system\helpers;

use AC\core\system\handlers\FileRepository;
use AC\core\system\handlers\JsonFileHandler;

/**
 * Вспомогательный класс для работы с репозиториями файлов
 * Позволяет работать с файлами по имени, автоматически определяя путь
 */
class FileRepositoryHelper
{
  private static array            $repositories = [];
  private static ?JsonFileHandler $jsonHandler  = null;
  private const DEFAULT_LOG_DIR = '/logs/tmp/';

  /**
   * Получает или создает репозиторий для указанного файла
   * Если передано только имя файла, сохраняет его в директории ROOT_PATH . '/logs/tmp/'
   *
   * @param string    $filePath Путь к файлу или только имя файла
   * @param bool      $useCache Использовать кэширование репозитория
   * @param bool|null $compactMode
   *
   * @return FileRepository
   */
  public static function getRepository(string $filePath, bool $useCache = true, ?bool $compactMode = null): FileRepository
  {
    $fullPath = self::resolveFilePath($filePath);
    $key      = self::generateKey($fullPath);

    if ($useCache && isset(self::$repositories[$key])) {
      return self::$repositories[$key];
    }

    $repository = self::createRepository($filePath, $compactMode);

    if ($useCache) {
      self::$repositories[$key] = $repository;
    }

    return $repository;
  }

  /**
   * Создает новый репозиторий без кэширования
   * Если передано только имя файла, сохраняет его в директории ROOT_PATH . '/logs/tmp/'
   *
   * @param string    $filePath Путь к файлу или только имя файла
   * @param bool|null $compactMode
   *
   * @return FileRepository
   */
  public static function createRepository(string $filePath, ?bool $compactMode = null): FileRepository
  {
    $fullPath = self::resolveFilePath($filePath);

    return new FileRepository($fullPath, self::getJsonHandler($compactMode));
  }

  /**
   * Очищает кэш репозиториев
   *
   * @return void
   * @static
   */
  public static function clearCache(): void
  {
    self::$repositories = [];
  }

  /**
   * Удаляет конкретный репозиторий из кэша
   * Если передано только имя файла, формирует полный путь
   *
   * @param string $filePath Путь к файлу или только имя файла
   *
   * @return void
   */
  public static function delete(string $filePath): void
  {
    $fullPath = self::resolveFilePath($filePath);
    $key      = self::generateKey($fullPath);
    unset(self::$repositories[$key]);
    self::getJsonHandler()->delete($fullPath);
  }

  /**
   * Генерирует уникальный ключ для кэширования
   *
   * @param string $filePath Путь к файлу или только имя файла
   *
   * @return string
   */
  private static function generateKey(string $filePath): string
  {
    return md5($filePath);
  }

  /**
   * Преобразует имя файла в полный путь
   * Если путь не содержит директорию, добавляет путь по умолчанию
   *
   * @param string $filePath Путь к файлу или только имя файла
   *
   * @return string Полный путь к файлу
   */
  private static function resolveFilePath(string $filePath): string
  {
    // Если в пути уже есть директория (содержит / или \), возвращаем как есть
    if (strpos($filePath, '/') !== false || strpos($filePath, '\\') !== false) {
      return $filePath;
    }

    // Добавляем расширение .json, если его нет
    if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'json') {
      $filePath .= '.json';
    }

    // Формируем полный путь
    return ROOT_PATH . self::DEFAULT_LOG_DIR . $filePath;
  }

  /**
   * Получает экземпляр JsonFileHandler
   *
   * @param bool|null $compactMode
   *
   * @return JsonFileHandler
   */
  private static function getJsonHandler(?bool $compactMode = null): JsonFileHandler
  {
    if (self::$jsonHandler === null) {
      self::$jsonHandler = new JsonFileHandler($compactMode);
    }

    return self::$jsonHandler;
  }

  /**
   * Быстрое получение значения из JSON-файла
   * Если передано только имя файла, ищет его в директории ROOT_PATH . '/logs/tmp/'
   *
   * @param string $filePath Путь к файлу или только имя файла
   * @param string $key      Ключ в формате 'user.name'
   * @param mixed  $default  Значение по умолчанию
   *
   * @return mixed
   */
  public static function get(string $filePath, string $key, $default = null)
  {
    return self::getRepository($filePath)->get($key, $default);
  }

  /**
   * Быстрая установка значения в JSON-файле
   * Если передано только имя файла, сохраняет его в директории ROOT_PATH . '/logs/tmp/'
   *
   * @param string $filePath Путь к файлу или только имя файла
   * @param string $key      Ключ в формате 'user.name'
   * @param mixed  $value    Значение для установки
   *
   * @return bool Успешность операции
   */
  public static function set(string $filePath, string $key, $value): bool
  {
    return self::getRepository($filePath)->set($key, $value)->commit();
  }

  /**
   * Быстрое удаление элемента из JSON-файла
   * Если передано только имя файла, ищет его в директории ROOT_PATH . '/logs/tmp/'
   *
   * @param string $filePath Путь к файлу или только имя файла
   * @param string $key      Ключ для удаления
   *
   * @return bool Успешность операции
   */
  public static function remove(string $filePath, string $key): bool
  {
    return self::getRepository($filePath)->remove($key)->commit();
  }

  /**
   * Проверка существования ключа в JSON-файле
   * Если передано только имя файла, ищет его в директории ROOT_PATH . '/logs/tmp/'
   *
   * @param string $filePath Путь к файлу или только имя файла
   * @param string $key      Ключ для проверки
   *
   * @return bool
   */
  public static function has(string $filePath, string $key): bool
  {
    return self::getRepository($filePath)->has($key);
  }

  /**
   * Получение всех данных из JSON-файла
   * Если передано только имя файла, ищет его в директории ROOT_PATH . '/logs/tmp/'
   *
   * @param string $filePath Путь к файлу или только имя файла
   *
   * @return array
   */
  public static function all(string $filePath): array
  {
    return self::getRepository($filePath)->all();
  }

  /**
   * Установка всех данных в JSON-файле
   * Если передано только имя файла, сохраняет его в директории ROOT_PATH . '/logs/tmp/'
   *
   * @param string $filePath Путь к файлу или только имя файла
   * @param array  $data     Данные для установки
   *
   * @return bool Успешность операции
   */
  public static function setAll(string $filePath, array $data): bool
  {
    return self::getRepository($filePath)->setAll($data)->commit();
  }

  /**
   * Очистка всех данных в JSON-файле
   * Если передано только имя файла, ищет его в директории ROOT_PATH . '/logs/tmp/'
   *
   * @param string $filePath Путь к файлу или только имя файла
   *
   * @return bool Успешность операции
   */
  public static function clear(string $filePath): bool
  {
    return self::getRepository($filePath)->clear()->commit();
  }
}