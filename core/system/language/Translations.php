<?php

namespace AC\core\system\language;

use AC\app\config\LangConfig;
use AC\core\system\helpers\StringHelper;
use AC\core\system\object\Entity;
use Service;

/**
 * Class Translations
 *
 * Класс для работы с локализацией (многоязычность сайта).
 * Отвечает за загрузку, кэширование и получение локализованных сообщений из INI-файлов.
 *
 * @package AC\core\system\language
 */
class Translations extends Entity
{
  /**
   * Корневой путь до файлов языковых сообщений.
   * @var string
   */
  protected $rootPath;

  /**
   * Путь до шаблонов (устройство/дизайн).
   * @var string
   */
  protected $devicePath;

  /**
   * Хранилище всех локализованных сообщений.
   * При первом обращении к нему происходит загрузка из файлов и инициализация.
   * @var array<string, array<string>>
   */
  private ?array $langMess = null;

  /**
   * Хранилище информации о файлах перевода.
   * @var array<string, array>|null
   */
  private static array $langMessFile = [];

  private ?string $currentLang;

  private LangConfig $config;

  /**
   * Конструктор класса.
   *
   * Инициализирует путь до файлов локализации, конфигурацию и определяет текущий язык.
   *
   * @param LangConfig|null $config Конфигурация языков.
   */
  public function __construct(?LangConfig $config = null, ?string $lang = null)
  {
    $this->rootPath   = paths()->getLangDir();
    $this->devicePath = Service::url()->getTemplatePath();
    $this->config     = $config ? : config('lang');
    $this->setLang($lang);
  }

  /**
   * Возвращает локализованное сообщение по ключу.
   * Если перевод не найден — возвращает оригинальное сообщение с подстановками.
   *
   * @param string      $message Ключ сообщения.
   * @param string|null $group   Группа сообщений (например: common, exception).
   * @param array       $params  Массив подстановок (ключ => значение).
   * @param string|null $default Значение по умолчанию при отсутствии перевода.
   * @param string|null $locale
   *
   * @return string
   */
  public function _(string $message, ?string $group = null, array $params = [], ?string $default = null, ?string $locale = null): string
  {
    $mess     = $this->_all();
    $group    = $group === null ? 'common' : $group;
    $_message = $group !== null
      ? ($this->has($message, $group) ? $mess[$group][$message] : '')
      : $mess[$message];

    if ($_message === '') {
      $_message = $default ? : ucfirst(str_replace('_', ' ', StringHelper::camelCaseToUnderscore($message)));
    }

    if (!empty($params)) {
      foreach ($params as $key => $param) {
        $_message = str_replace("{{{$key}}}", $param, $_message);
      }
    }

    return $_message;
  }

  /**
   * Возвращает все локализованные сообщения.
   * При необходимости перезагружает их из файлов.
   *
   * @param bool $newInstance Пересобрать сообщения при смене языка.
   *
   * @return array<string, mixed>
   */
  public function _all(bool $newInstance = false): array
  {
    if ($this->langMess === null || $newInstance) {
      $this->langMess = [];
      foreach (['', 'mess', 'dictionary', 'exception', 'message', 'reservations'] as $name) {
        $this->addFile($name);
      }
    }

    return $this->langMess;
  }

  /**
   * Устанавливает новый язык пользователя.
   * Проверяет наличие файлов перевода для указанного языка.
   *
   * @param string|null $lang Новый язык.
   *
   * @return void
   */
  public function setLang(?string $lang = null): void
  {
    $this->currentLang = $lang ?? $this->config->getCurrentLang();

    $this->_all(!empty($lang));
  }

  /**
   * Проверяет, установлен ли язык в сессии и существуют ли файлы перевода.
   *
   * @param $lang
   *
   * @return bool
   */
  public function issetLang($lang = null): bool
  {
    return Service::autoloader()->getPathFile($this->generateNameToAddFile('', null, 'common', $lang), 'ini');
  }

  /**
   * Подключает файл перевода.
   *
   * @param string  $name Имя файла без пути и расширения.
   * @param ?string $path Альтернативный путь до файла.
   *
   * @return void
   */
  public function addFile(string $name, ?string $path = null): void
  {
    $this->addTranslationsFromFile($name, $this->generateNameToAddFile($name, $path, 'common'), 'common');
    foreach (Service::templates()->getMergeOrder($this->devicePath) as $device) {
      $this->addTranslationsFromFile($name, $this->generateNameToAddFile($name, $path, $device), $device);
    }
  }

  /**
   * Генерирует полный путь до файла перевода.
   *
   * @param string      $name   Имя файла - по умолчанию просто язык.
   * @param string|null $path   Альтернативный путь.
   * @param string|null $device Устройство/шаблон.
   * @param string|null $lang   язык
   *
   * @return string
   */
  public function generateNameToAddFile(string $name = '', ?string $path = null, ?string $device = null, ?string $lang = null): string
  {
    $lang = $lang ?? $this->currentLang();

    return pathAs(($path ?? $this->rootPath)
      . ($device ?? $this->devicePath)
      . '/'
      . $lang
      . '/'
      . $name . ($name ? '.' : '')
      . $lang);
  }

  /**
   * Добавляет содержимое INI-файла в хранилище сообщений.
   *
   * @param string      $name     Имя файла.
   * @param string      $pathFile Полный путь до файла.
   * @param string|null $device   Устройство/шаблон.
   *
   * @return void
   */
  public function addTranslationsFromFile(string $name, string $pathFile, ?string $device = null): void
  {
    $device = $device ? : $this->devicePath;
    if (($ini = new IniFiles($pathFile)) && ($translate = $ini->getAll()) && !empty($translate)) {
      // todo: пока не где не используется - возможно понадобится в будущем для администрирования(удаление, добавление и т.д.)
      // todo: Сделать проверку на подгрузку файлов повторно.
//      self::$langMessFile[$this->currentLang()][$name][$device] = array_unique(array_merge(self::$langMessFile[$this->currentLang()][$name][$device] ?? [], array_keys($ini->getDataIniFiles())));

      $this->langMess = array_replace_recursive($this->_all(), $translate);
    }
  }

  /**
   * Проверяет, существует ли сообщение в указанной группе.
   *
   * @param string      $message Ключ сообщения.
   * @param string|null $group   Группа сообщений.
   *
   * @return bool
   */
  public function has(string $message, ?string $group = null): bool
  {
    $group = $group === null ? 'common' : $group;

    return isset($this->_all()[$group][$message]);
  }

  public function getLangMessFile()
  {
    return self::$langMessFile;
  }

  public function currentLang(): string
  {
    $this->currentLang ??= $this->config->getCurrentLang();

    return $this->currentLang;
  }
}
