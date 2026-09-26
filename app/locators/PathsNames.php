<?php

namespace AC\app\locators;

/**
 * PathsNames предоставляет набор констант, представляющих пути к директориям в приложении.
 * Эти константы используются для поддержания согласованности и избегания использования жёстко заданных строк путей.
 */
class PathsNames
{
  /**
   * Директория ядра.
   */
  const CORE_DIR = 'core';
  
  /**
   * Директория приложения.
   */
  const APP_DIR = 'app';
  
  /**
   * Директория модулей.
   */
  const MODULES_DIR = 'modules';
  
  /**
   * Системная директория.
   */
  const SYSTEM_DIR = 'system';
  
  /**
   * Директория шаблонов.
   */
  const TPL_DIR = 'tpl';
  
  /**
   * Директория активов (например, CSS, JS, изображения).
   */
  const ASSETS_DIR = 'assets';
  
  /**
   * Директория сущностей (например, для ORM-сущностей).
   */
  const ENTITY_DIR = 'entity';
  
  /**
   * Директория загрузок.
   */
  const UPLOAD_DIR = 'uploads';
  
  /**
   * Директория логов.
   */
  const LOGS_DIR = 'logs';
  
  /**
   * Директория временных файлов.
   */
  const TMP_FILES_DIR = 'tmp_files';
  
  /**
   * Директория локаторов.
   */
  const LOCATORS_DIR = 'locators';
  
  /**
   * Директория языковых файлов.
   */
  const LANG_DIR = 'lang';
  
  /**
   * Директория констант или общих утилит.
   */
  const CONSTANT_DIR = 'uses';
  
  /**
   * Директория движков (например, для бизнес-логики).
   */
  const ENIGINES_DIR = 'engines';
  
  /**
   * Директория действий (например, для действий контроллера).
   */
  const ACTIONS_DIR = 'actions';
  
  /**
   * Директория структуры (например, для определений структуры приложения).
   */
  const STRUCTURE_DIR = 'structure';
  
  /**
   * Директория конфигурации.
   */
  const CONFIG_DIR = 'config';
  
  /**
   * Директория шаблонов (альтернативное имя, рассмотрите удаление, если не используется).
   */
  const TEMPLATES_DIR = 'templates';
  
  /**
   * Директория кэша.
   */
  const CACHE_DIR = 'cache';
  
  /**
   * Директория временных файлов (альтернативное имя, рассмотрите удаление, если не используется).
   */
  const TMP_DIR = 'tmp';
  
  /**
   * Директория коммуникации между модулями.
   */
  const MOD_COMM_DIR = 'modComm';
  
  /**
   * Директория тем (например, для фронтенд-тем).
   */
  const THEMES_DIR = 'themes';
  
  /**
   * Директория представлений (views).
   */
  const VIEWS_DIR = 'views';
  
  /**
   * Директория контроллеров.
   */
  const CONTROLLERS_DIR = 'controllers';
  
  /**
   * Директория моделей.
   */
  const MODELS_DIR = 'models';
  
  /**
   * Директория сервисов.
   */
  const SERVICES_DIR = 'services';
  
  /**
   * Директория макетов (layouts).
   */
  const LAYOUTS_DIR = 'layouts';
  
  /**
   * Директория таблиц (например, для SQL-структур).
   */
  const TABLES_DIR = 'tables';
}
