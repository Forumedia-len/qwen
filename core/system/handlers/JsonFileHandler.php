<?php

namespace AC\core\system\handlers;

/**
 * Обработчик JSON файлов
 * Автоматически определяет режим хранения данных (сжатый/развернутый) в зависимости от окружения
 */
class JsonFileHandler
{
  private FileHandler $fileHandler;

  /**
   * Режим форматирования JSON
   * true - сжатый режим (одна строка, для production)
   * false - развернутый режим (с отступами, для разработки)
   *
   * @var bool
   */
  private static bool $compactMode = true;

  public function __construct(?bool $compactMode = null)
  {
    $this->fileHandler = new FileHandler();
    // Определяем режим хранения данных на основе окружения
    self::$compactMode = $compactMode ?? (!defined('LOCAL_SERVER') || !LOCAL_SERVER);
  }

  /**
   * Читает и декодирует JSON данные из файла
   *
   * @param string $filePath Путь к файлу
   *
   * @return array Данные как массив
   */
  public function read(string $filePath): array
  {
    $content = $this->fileHandler->read($filePath);
    if (empty($content)) {
      return [];
    }

    $data = json_decode($content, true);

    return is_array($data) ? $data : [];
  }

  /**
   * Кодирует данные в JSON и записывает в файл
   *
   * @param string $filePath Путь к файлу
   * @param array  $data     Данные для записи
   *
   * @return bool Успешность операции
   */
  public function write(string $filePath, array $data): bool
  {
    // Определяем флаги JSON в зависимости от режима
    $jsonFlags = JSON_UNESCAPED_UNICODE;
    if (!self::$compactMode) {
      $jsonFlags |= JSON_PRETTY_PRINT;
    }

    return ($content = json_encode($data, $jsonFlags)) !== false &&
      $this->fileHandler->write($filePath, $content);
  }

  /**
   * Удаляет файл
   *
   * @param string $filePath Путь к файлу
   *
   * @return bool Успешность операции
   */
  public function delete(string $filePath): bool
  {
    return $this->fileHandler->delete($filePath);
  }

  /**
   * Сохраняет данные в файл, объединяя с существующими
   *
   * @param string $filePath Путь к файлу
   * @param array  $data     Новые данные
   *
   * @return bool Успешность операции
   */
  public function save(string $filePath, array $data): bool
  {
    $existingData = $this->read($filePath);
    $mergedData   = array_merge($existingData, $data);

    return $this->write($filePath, $mergedData);
  }
}