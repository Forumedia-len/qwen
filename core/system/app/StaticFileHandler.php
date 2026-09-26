<?php

declare(strict_types=1);

namespace AC\core\system\app;

use AC\app\locators\Service;
use AC\core\system\actions\DeviceActionInterface;
use AC\core\system\files\MimeType;
use AC\core\system\helpers\FileHelper;

/**
 * Класс для обработки статических файлов
 */
class StaticFileHandler
{
  /**
   * @var array
   */
  private array $allowedExtensions = [
    'css',
    'js',
    'png',
    'jpg',
    'jpeg',
    'gif',
    'svg',
    'ico',
    'woff',
    'woff2',
    'ttf',
    'eot',
    'pdf',
    'txt',
    'xml',
    'json',
    'zip',
    'rar',
    'doc',
    'docx',
    'xls',
    'xlsx'
  ];

  /**
   * @var array
   */
  private array $phpExtensions = ['php'];

  /**
   * Обработка статических файлов (не PHP)
   * 
   * @param mixed $url
   * @return bool
   */
  public function handleNonPhpFile($url): bool
  {
    if (!$url || !method_exists($url, 'getPath')) {
      return false;
    }

    $file = $url->getPath();
    if (!$file) {
      return false;
    }

    $extension = FileHelper::checkExtension($file, 'php');
    if (!$extension || !in_array($extension, $this->allowedExtensions)) {
      return false;
    }

    $filePath = Service::autoloader()->getPathFile($file, $extension);
    if (!file_exists($filePath)) {
      return false;
    }

    $this->serveFile($filePath, $extension);
    return true;
  }

  /**
   * Обработка статических PHP файлов
   * 
   * @param mixed $url
   * @param DeviceActionInterface|null $deviceAction
   * @param mixed $possibleResponse
   * @return bool
   */
  public function handlePhpFile($url, ?DeviceActionInterface $deviceAction = null, $possibleResponse = null): bool
  {
    if (!$url || !method_exists($url, 'getPath')) {
      return false;
    }

    $path = $this->buildStaticFilePath($url);

    if (!FileHelper::checkExtension($path)) {
      return false;
    }

    $this->executePhpFile($path, $deviceAction, $possibleResponse);
    return true;
  }

  /**
   * Отдача файла клиенту
   * 
   * @param string $filePath
   * @param string $extension
   */
  private function serveFile(string $filePath, string $extension): void
  {
    ob_get_level() || ob_end_clean();

    $mimeType = MimeType::getExtensionMimes($extension);
    header('Content-Type: ' . $mimeType);

    // Добавляем заголовки кэширования для статических файлов
    $this->addCacheHeaders($extension);

    readfile($filePath);
    exit;
  }

  /**
   * Выполнение PHP файла
   * 
   * @param string $path
   * @param DeviceActionInterface|null $deviceAction
   * @param mixed $possibleResponse
   */
  private function executePhpFile(string $path, ?DeviceActionInterface $deviceAction = null, $possibleResponse = null): void
  {
    // Извлечение переменных из ответа
    if ($possibleResponse && is_array($possibleResponse)) {
      extract($possibleResponse, EXTR_OVERWRITE);
    }

    require_once Service::autoloader()->getPathFile($path);

    if ($deviceAction) {
      $deviceAction->after(get_defined_vars());
    }

    exit;
  }

  /**
   * Построение пути к статическому файлу
   * 
   * @param mixed $url
   * @return string
   */
  private function buildStaticFilePath($url): string
  {
    $path = empty($url->getPath(false))
      ? $url->getPath() . ($url->getDevicePath() === '' ? 'aktuelles.php' : 'index.php')
      : $url->getPath();

    if (
      !FileHelper::checkExtension($path) &&
      ($_path = Service::autoloader()->actualPath($path . '/index.php'))
    ) {
      $path = $path . '/index.php';
    }

    return $path;
  }

  /**
   * Добавление заголовков кэширования
   * 
   * @param string $extension
   */
  private function addCacheHeaders(string $extension): void
  {
    $cacheableExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];

    if (in_array($extension, $cacheableExtensions)) {
      $maxAge = 86400; // 24 часа
      header('Cache-Control: public, max-age=' . $maxAge);
      header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $maxAge));
    } else {
      header('Cache-Control: no-cache, no-store, must-revalidate');
      header('Pragma: no-cache');
      header('Expires: 0');
    }
  }

  /**
   * Установить разрешенные расширения
   * 
   * @param array $extensions
   */
  public function setAllowedExtensions(array $extensions): void
  {
    $this->allowedExtensions = $extensions;
  }

  /**
   * Добавить разрешенное расширение
   * 
   * @param string $extension
   */
  public function addAllowedExtension(string $extension): void
  {
    if (!in_array($extension, $this->allowedExtensions)) {
      $this->allowedExtensions[] = $extension;
    }
  }

  /**
   * Удалить разрешенное расширение
   * 
   * @param string $extension
   */
  public function removeAllowedExtension(string $extension): void
  {
    $key = array_search($extension, $this->allowedExtensions);
    if ($key !== false) {
      unset($this->allowedExtensions[$key]);
    }
  }

  /**
   * Получить разрешенные расширения
   * 
   * @return array
   */
  public function getAllowedExtensions(): array
  {
    return $this->allowedExtensions;
  }

  /**
   * Проверить, является ли расширение разрешенным
   * 
   * @param string $extension
   * @return bool
   */
  public function isExtensionAllowed(string $extension): bool
  {
    return in_array($extension, $this->allowedExtensions);
  }

  /**
   * Получить MIME-тип для расширения
   * 
   * @param string $extension
   * @return string
   */
  public function getMimeType(string $extension): string
  {
    return MimeType::getExtensionMimes($extension);
  }

  /**
   * Проверить существование файла
   * 
   * @param string $filePath
   * @return bool
   */
  public function fileExists(string $filePath): bool
  {
    return file_exists($filePath);
  }

  /**
   * Получить размер файла
   * 
   * @param string $filePath
   * @return int
   */
  public function getFileSize(string $filePath): int
  {
    return file_exists($filePath) ? filesize($filePath) : 0;
  }

  /**
   * Получить время последней модификации файла
   * 
   * @param string $filePath
   * @return int
   */
  public function getFileModificationTime(string $filePath): int
  {
    return file_exists($filePath) ? filemtime($filePath) : 0;
  }
}
