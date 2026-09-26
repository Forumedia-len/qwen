<?php

namespace AC\core\system\language;

use AC\core\system\helpers\FileHelper;
use Service;

defined('_BR_') || define('_BR_', chr(13) . chr(10));

class IniFiles
{
  public const _BR_ = _BR_;

  private array   $dataIniFiles = [];
  private ?string $baseFileName = null;
  private array   $arr          = [];
  private array   $changedRows  = [];

  public function __construct($file = false)
  {
    if ($file) {
      $this->loadFromFile(pathAs($file));
    }
  }

  public static function parseFile($filename)
  {
    return (new IniFiles($filename))->getAll();
  }

  private function parse($filename, $section = true)
  {
    $parse        = [];
    $handle       = @fopen($filename, 'rb');
    $section_name = 'common';

    if ($handle) {
      while (($buffer = fgets($handle, 4096)) !== false) {
        $buffer = trim($buffer);

        if (!empty($buffer)) {
          // Обработка секции
          if (preg_match('/^\[(.*?)\]$/', $buffer, $matches)) {
            $section_name = $matches[1];
          } // Обработка комментариев
          elseif (preg_match('/^;(.*?)$/', $buffer)) {
            // Комментарии пропускаем
          } // Обработка пар "ключ = значение"
          elseif (preg_match('/^"(.*?)"[ ]*?=[ ]*?"(.*?)"$/u', $buffer, $matches)
            || preg_match('/^(.*?)[ ]*?=[ ]*?"(.*?)"$/u', $buffer, $matches)) {
            $key   = $matches[1];
            $value = $matches[2];

            if ($section) {
              $parse[$section_name][$key] = $value;
            } else {
              $parse[$key] = $value;
            }
          }
        }
      }

      if (!feof($handle)) {
        Service::logger('IniFile')->logError("Ошибка: парсер fgets() неожиданно потерпел неудачу\n", []);
      }

      fclose($handle);
    }

    return $parse;
  }


  public function getAll()
  {
    return $this->arr;
  }

  public function getDataIniFiles($fileName = null)
  {
    if ($fileName) {
      return $this->dataIniFiles[$fileName] ?? [];
    }

    return $this->dataIniFiles ?? [];
  }

  private function initArray($file = false)
  {
    $parseData                 = $this->parse($file);
    $this->dataIniFiles[$file] = $parseData;
    $this->arr                 = array_replace_recursive($this->arr, $parseData);
  }

  private function loadFromFile($file, $ext = 'ini')
  {
    $this->baseFileName = str_ireplace('.' . $ext, '', $file);

    if (file_exists($file)) {
      $this->setFile($file);
    } else {
      foreach (Service::locator()->search($file, $ext) as $filePath) {
        $this->setFile($filePath);
      }
    }
  }

  public function setFile($file, $namespace = SHARED_NAMESPACE)
  {
    if ($file && is_readable($file)) {
      $this->initArray($file);
    }
  }

  public function read($section, $key, $def = '')
  {
    return $this->arr[$section][$key] ?? $def;
  }

  /**
   * Проверяет наличие значения в указанной секции и по ключу
   * 
   * @param string $section Название секции
   * @param string $key Ключ для поиска
   * @return bool Возвращает true если значение существует, false в противном случае
   */
  public function has($section, $key): bool
  {
    return isset($this->arr[$section][$key]);
  }

  public function write($section, $key, $value)
  {
    if (is_bool($value)) {
      $value = $value ? 1 : 0;
    }

    $previousValue = $this->arr[$section][$key] ?? null;
    if ($previousValue === $value) {
      return;
    }

    $this->arr[$section][$key]      = $value;
    $this->changedRows[$section][$key] = $value;
  }

  public function eraseSection($section)
  {
    if (isset($this->arr[$section])) {
      unset($this->arr[$section]);
    }
  }

  public function deleteKey($section, $key)
  {
    if (isset($this->arr[$section][$key])) {
      unset($this->arr[$section][$key]);
    }
  }

  public function readSections(&$array)
  {
    $array = array_keys($this->arr);

    return $array;
  }

  public function readKeys($section, &$array)
  {
    if (isset($this->arr[$section]) && ($array = array_keys($this->arr[$section]))) {

      return $array;
    }

    return [];
  }

  public function updateFile($local = true)
  {
    if (!$this->baseFileName) {
      return false;
    }

    return $local ? $this->writeLocalDiff() : $this->writeFullDump();
  }

  private function writeLocalDiff(): bool
  {
    if (empty($this->changedRows)) {
      return true;
    }

    $fileName = $this->resolveFilePath(true);
    if (!$fileName) {
      return false;
    }

    $localData = is_file($fileName) ? $this->parse($fileName) : [];

    foreach ($this->changedRows as $section => $items) {
      foreach ($items as $key => $value) {
        $localData[$section][$key] = $value;
      }
    }

    if (empty($localData)) {
      if (is_file($fileName)) {
        @unlink($fileName);
      }
      $this->changedRows = [];

      return true;
    }

    $result = $this->buildIniContent($localData);

    $this->changedRows = [];

    return FileHelper::write($fileName, $result);
  }

  private function writeFullDump(): bool
  {
    $fileName = $this->resolveFilePath(true);
    if (!$fileName) {
      return false;
    }

    $result = $this->buildIniContent($this->arr);

    return FileHelper::write($fileName, $result);
  }

  private function resolveFilePath(bool $local): ?string
  {
    if (!$this->baseFileName) {
      return null;
    }

    $basePath = $local
      ? Service::autoloader()->getPathRootNamespace()
      : Service::autoloader()->getPathSharedNamespace();

    return Service::autoloader()->getPathFile($this->baseFileName, 'ini', $basePath, false);
  }

  private function buildIniContent(array $data): string
  {
    $result = '';

    foreach ($data as $sname => $section) {
      $result .= '[' . $sname . ']' . self::_BR_;
      foreach ($section as $key => $value) {
        $result .= $this->formatRow($key, $value) . self::_BR_;
      }
      $result .= self::_BR_;
    }

    return $result;
  }

  private function formatRow(string $key, $value): string
  {
    return $key . ' = "' . $value . '"';
  }
}