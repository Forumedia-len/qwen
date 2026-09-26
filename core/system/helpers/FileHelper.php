<?php

namespace AC\core\system\helpers;

/**
 * Вспомогательный класс для работы с файлами
 */
class FileHelper
{
  /**
   * Проверяет временные файлы в указанной директории на предмет их возраста
   * и возвращает файлы, созданные более чем $hoursBack часов назад
   *
   * @param string $directory Путь к директории для проверки
   * @param int    $hoursBack Количество часов для проверки (по умолчанию 1 час)
   *
   * @return array Массив файлов, созданных более $hoursBack часов назад
   */
  public static function getOldTempFiles(string $directory, int $hoursBack = 1): array
  {
    $oldFiles   = [];
    $cutoffTime = time() - ($hoursBack * 3600);

    // Проверяем существование директории
    if (!is_dir($directory)) {
      return $oldFiles;
    }

    // Сканируем директорию
    $files = scandir($directory);

    if ($files === false) {
      return $oldFiles;
    }

    foreach ($files as $file) {
      // Пропускаем текущую и родительскую директории
      if ($file === '.' || $file === '..') {
        continue;
      }

      $filePath = $directory . DIRECTORY_SEPARATOR . $file;

      // Проверяем, что это файл (не директория)
      if (is_file($filePath)) {
        // Получаем время последней модификации файла
        $fileTime = filemtime($filePath);

        // Если файл существует и его время модификации меньше контрольного времени
        if ($fileTime !== false && $fileTime < $cutoffTime) {
          $oldFiles[] = [
            'path'      => $filePath,
            'name'      => $file,
            'size'      => filesize($filePath),
            'modified'  => $fileTime,
            'age_hours' => round((time() - $fileTime) / 3600, 2),
          ];
        }
      }
    }

    return $oldFiles;
  }

  /**
   * Удаляет временные файлы, созданные более чем $hoursBack часов назад
   *
   * @param string $directory Путь к директории для очистки
   * @param int    $hoursBack Количество часов для проверки (по умолчанию 1 час)
   *
   * @return array Результат операции с информацией об удаленных файлах
   */
  public static function cleanupOldTempFiles(string $directory, int $hoursBack = 1): array
  {
    $result = [
      'deleted'          => [],
      'failed'           => [],
      'total_deleted'    => 0,
      'total_size_freed' => 0,
    ];

    $oldFiles = self::getOldTempFiles($directory, $hoursBack);

    foreach ($oldFiles as $file) {
      if (unlink($file['path'])) {
        $result['deleted'][] = $file;
        $result['total_deleted']++;
        $result['total_size_freed'] += $file['size'];
      } else {
        $result['failed'][] = $file;
      }
    }

    return $result;
  }

  /**
   * Проверяет существование файла
   *
   * @param string $filePath Путь к файлу
   *
   * @return bool
   */
  public static function exists(string $filePath): bool
  {
    return file_exists($filePath);
  }

  /**
   * Получает расширение файла
   *
   * @param string $filePath Путь к файлу
   * @param string $default  Расширение по умолчанию
   *
   * @return string
   */
  public static function getExtension(string $filePath, string $default = ''): string
  {
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);

    return $extension ? : $default;
  }

  /**
   * @param             $file
   * @param string|null $checkExtension - проверяем расширение, если оно совпадает то выдаем null
   *
   * @return mixed|null
   */
  public static function checkExtension($file, $checkExtension = null)
  {
    preg_match('/(.+)\.([0-9a-z]*)$/i', $file, $matches);

    return isset($matches[2]) ? (!$checkExtension || $checkExtension !== $matches[2] ? $matches[2] : null) : null;
  }

  /**
   * Переработать путь файла без лишних символов и слешей.
   *
   * @param string $fileName Имя файла
   * @param string $characters
   *
   * @return string
   */
  public static function trimFilePath(
    $fileName,
    $separator = DIRECTORY_SEPARATOR,
    $addSeparatorEnd = true,
    $characters = " \n\r\t\v\0/\\",
    $functionName = 'trim',
  ) {
    $fileName = pathAs($fileName);

    switch ($functionName) {
      case 'rtrim':
        $fileName = rtrim($fileName, $characters);
        break;
      case 'ltrim':
        $fileName = ltrim($fileName, $characters);
        break;
      case 'trim':
      default:
        $fileName = trim($fileName, $characters);
        break;
    }

    return $fileName . ($addSeparatorEnd ? $separator : '');
  }

  public static function getBasename($path, $withExtension = true)
  {
    $path = basename(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path));

    return $withExtension ? $path : explode('.', $path)[0];
  }

  //получать содержимое файла
  public static function getFileContent($filename)
  {
    $fd      = fopen($filename, 'r');
    $content = fread($fd, filesize($filename));
    fclose($fd);

    return $content;
  }

  //записать переменную в файл
  public static function write2File($filename, $content)
  {
    $fd = fopen($filename, 'w');
    flock($fd, LOCK_EX);
    fwrite($fd, $content);
    flock($fd, LOCK_UN);
    fclose($fd);

    return true;
  }

  public static function write($file, $content, $overwrite = true)
  {
    $flags = $overwrite ? 0 : FILE_APPEND;
    if (!file_exists(trim(dirname($file), DIRECTORY_SEPARATOR))) {
      mkdir(dirname($file), 0777, true);
    }

    return file_put_contents($file, $content, $flags);
  }

  /** Человеческое отображение размера файла
   *
   * @param $bytes
   * @param $decimals
   *
   * @return string
   */
  public static function humanFileSize($bytes, $decimals = 2)
  {
    $factor = floor((strlen($bytes) - 1) / 3);
    if ($factor > 0) {
      $sz = 'KMGT';
    }

    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor - 1] . 'B';
  }

}