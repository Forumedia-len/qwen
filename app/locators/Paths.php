<?php

namespace AC\app\locators;


/**
 * Paths
 *
 * Хранит пути, используемые системой для определения основных директорий:
 * приложения, системы, модулей и т.д.
 *
 * Изменение этих путей позволяет перестраивать структуру приложения,
 * использовать общую папку системы для нескольких приложений и т.п.
 *
 * Все пути указаны относительно корня проекта.
 */
class Paths extends PathsNames
{
  /**
   * Путь к корневой директории ядра
   *
   * @var string
   */
  public string $coreDir = PathsNames::CORE_DIR . '\\';
  
  /**
   * ---------------------------------------------------------------
   * НАЗВАНИЕ СИСТЕМНОЙ ПАПКИ
   * ---------------------------------------------------------------
   *
   * Это должно содержать имя вашей системной папки "system".
   * Укажите путь, если папка находится не в той же директории, что и этот файл.
   *
   * @var string
   */
  public string $systemDir = PathsNames::CORE_DIR . '\\' . PathsNames::SYSTEM_DIR . '\\';
  
  /**
   * ---------------------------------------------------------------
   * НАЗВАНИЕ ДИРЕКТОРИИ МОДУЛЕЙ
   * ---------------------------------------------------------------
   *
   * Эта переменная содержит путь ко всем
   * вашим модулям (компонентам), загружаемым в систему
   *
   * @var string
   */
  public string $modulesDir = PathsNames::CORE_DIR . '\\' . PathsNames::MODULES_DIR . '\\';
  
  /**
   * Общая папка со всеми настройками и данными
   *
   * @var string
   */
  public string $appDir = PathsNames::APP_DIR . '\\';
  
  /**
   * Папка с конфигурационными файлами
   *
   * @var string
   */
  public string $configDir = PathsNames::APP_DIR . '\\' . PathsNames::CONFIG_DIR . '\\';
  
  /**
   * Папка с файлами действий
   *
   * @var string
   */
  public string $actionsDir = PathsNames::ACTIONS_DIR . '\\';
  
  /**
   * Папка с файлами локализации
   *
   * @var string
   */
  public string $langDir = PathsNames::LANG_DIR . '\\';
  
  /**
   * Папка с файлами структуры
   *
   * @var string
   */
  public string $structureDir = PathsNames::STRUCTURE_DIR . '\\';
  
  /**
   * Папка с файлами тем оформления
   *
   * @var string
   */
  public string $themesDir = PathsNames::APP_DIR . '\\' . PathsNames::THEMES_DIR . '\\';
  
  /**
   * Папка с файлами констант
   *
   * @var string
   */
  public string $constantDir = PathsNames::CONSTANT_DIR . '\\';
  
  /**
   * Папка с файлами локаторов
   *
   * @var string
   */
  public string $locatorsDir = DIR_LOCATORS_PATHS;
  
  /**
   * Папка с файлами шаблонов
   *
   * @var string
   */
  public string $tplDir = 'tpl\\';
  
  /**
   * Папка с файлами ресурсов (ассетов)
   *
   * @var string
   */
  public string $assetsDir = 'assets\\';
  
  /**
   * Папка с классами сущностей
   *
   * @var string
   */
  public string $entityDir = 'entity\\';
  
  /**
   * Папка с файлами движков
   *
   * @var string
   */
  public string $enginesDir = 'core\engines\\';
  
  /**
   * Папка загрузки файлов
   *
   * @var string
   */
  public string $uploadsDir = UPLOAD_DIR;
  
  /**
   * Папка временных файлов
   *
   * @var string
   */
  public string $tmpFilesDir = UPLOAD_DIR . 'tmp_files\\';
  /**
   * Папка временных файлов
   *
   * @var string
   */
  public string $logsDir = PathsNames::LOGS_DIR . '\\';
  
  /**
   * Возвращает путь к папке шаблонов, основываясь на текущей теме или указанной.
   *
   * @param string|null $path     Подкаталог внутри папки шаблона.
   * @param string|null $template Имя шаблона для использования вместо стандартного.
   * @return string Полный путь к папке шаблона.
   */
  public function getTplDir($path = null, $template = null): string
  {
    return $this->tplDir . ($template ?: Service::url()->getTemplatePath()) . '\\' . ($path ?: '');
  }
  
  /**
   * Возвращает путь к папке ассетов, основываясь на текущей теме или указанной.
   *
   * @param string|null $path     Подкаталог внутри папки ассетов.
   * @param string|null $template Имя шаблона для использования вместо стандартного.
   * @return string Полный путь к папке ассетов.
   */
  public function getAssetsDir($path = null, $template = null): string
  {
    return $this->assetsDir . ($template ?: Service::url()->getTemplatePath()) . '\\' . ($path ?: '');
  }
  
  /**
   * Возвращает путь к папке языковых файлов под приложением, используя текущую тему или указанную.
   *
   * @param string|null $path     Подкаталог внутри папки языков.
   * @param string|null $template Имя шаблона для использования вместо стандартного.
   * @return string Полный путь к папке языковых файлов.
   */
  public function getLangAppDir($path = null, $template = null): string
  {
    return $this->appDir . $this->langDir . ($template ?? Service::url()->getTemplatePath()) . '\\' . ($path ?: '');
  }
  
  /**
   * Возвращает путь к папке языковых файлов, возможно с префиксом приложения.
   *
   * @param string|null $path      Подкаталог внутри папки языков.
   * @param bool        $addAppDir Добавить префикс приложения.
   * @return string Полный путь к папке языковых файлов.
   */
  public function getLangDir($path = null, bool $addAppDir = false): string
  {
    return ($addAppDir ? $this->appDir : '') . $this->langDir . ($path ?: '');
  }
  
  /**
   * Возвращает путь к устройству, основываясь на текущем устройстве или указанном.
   *
   * @param string|null $path   Подкаталог внутри папки устройства.
   * @param string|null $device Имя устройства для использования вместо стандартного.
   * @return string Полный путь к папке устройства.
   */
  public function getDeviceDir($path = null, $device = null): string
  {
    return ($device ?: Service::url()->getDevicePath()) . '\\' . ($path ?: '');
  }
  
  /**
   * Возвращает путь к папке временных файлов.
   *
   * @param string|null $path Подкаталог внутри папки временных файлов.
   * @return string Полный путь к папке временных файлов.
   */
  public function getTmpFilesDir($path = null): string
  {
    return $this->tmpFilesDir . ($path ?: '');
  }
  
  /**
   * Возвращает путь к папке сущностей.
   *
   * @param string|null $path Подкаталог внутри папки сущностей.
   * @return string Полный путь к папке сущностей.
   */
  public function getEntityDir($path = null): string
  {
    return $this->entityDir . ($path ?: '');
  }
  
  /**
   * Возвращает путь к папке сущностей.
   *
   * @param string|null $path Подкаталог внутри папки сущностей.
   * @return string Полный путь к папке сущностей.
   */
  public function getModuleDir(?string $path = null): string
  {
    return $this->modulesDir . ($path ?: '');
  }
  
  /**
   * Возвращает путь к папке шаблона.
   *
   * @param string|null $path     Подкаталог внутри папки шаблона.
   * @param string|null $template Имя шаблона для использования вместо стандартного.
   * @return string Полный путь к папке шаблона.
   */
  public function getTemplateDir($path = null, $template = null): string
  {
    return Service::url()->getTemplatePath() . '\\' . ($path ?: '');
  }
}
