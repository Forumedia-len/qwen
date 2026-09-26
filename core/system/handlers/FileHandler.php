<?php

namespace AC\core\system\handlers;


class FileHandler implements BaseHandlerInterface
{
  /**
   * Читает данные из файла
   *
   * @param string $filePath Путь к файлу
   *
   * @return string Содержимое файла
   */
  public function read(string $filePath): string
  {
    if (!file_exists($filePath)) {
      return '';
    }

    $content = file_get_contents($filePath);

    return $content === false ? '' : $content;
  }

  /**
   * Записывает данные в файл
   *
   * @param string $filePath Путь к файлу
   * @param string $data     Данные для записи
   *
   * @return bool Успешность операции
   */
  public function write(string $filePath, string $data): bool
  {
    $directory = dirname($filePath);
    is_dir($directory) || mkdir($directory, 0755, true);

    return file_put_contents($filePath, $data) !== false;
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
    if (!file_exists($filePath)) {
      return true;
    }

    return unlink($filePath);
  }
}