<?php

namespace AC\core\system\handlers;

interface BaseHandlerInterface
{
  /**
   * Читает данные из файла
   *
   * @param string $filePath Путь к файлу
   *
   * @return string Содержимое файла
   */
  public function read(string $filePath): string;

  /**
   * Записывает данные в файл
   *
   * @param string $filePath Путь к файлу
   * @param string $data     Данные для записи
   *
   * @return bool Успешность операции
   */
  public function write(string $filePath, string $data): bool;

  /**
   * Удаляет файл
   *
   * @param string $filePath Путь к файлу
   *
   * @return bool Успешность операции
   */
  public function delete(string $filePath): bool;
}
