<?php

namespace AC\core\system\service;

use AC\app\config\AppConfig;
use AC\app\config\ExceptionsConfig;
use AC\app\config\FormatConfig;
use AC\app\config\LangConfig;
use AC\core\engines\Engines;
use AC\core\engines\PreferencesEngine;
use AC\core\modules\config\models\ConfigDbValueSelector;
use AC\core\modules\config\scoped\ScopedConfigGateway;
use AC\core\modules\config\scoped\ScopedConfigGatewayInterface;
use AC\core\modules\mailing\services\Mailer;
use AC\core\system\controller\AuthInterface;
use AC\core\system\db\Query;
use AC\core\system\debug\Exceptions;
use AC\core\system\debug\Logger;
use AC\core\system\format\Format;
use AC\core\system\http\Negotiate;
use AC\core\system\http\request\CURLRequest;
use AC\core\system\http\request\IncomingRequest;
use AC\core\system\http\request\RequestInterface;
use AC\core\system\http\response\RedirectResponse;
use AC\core\system\http\response\Response;
use AC\core\system\http\response\ResponseInterface;
use AC\core\system\http\url\URI;
use AC\core\system\http\url\Url;
use AC\core\system\language\Translations;
use AC\core\system\object\entity\cast\BaseCast;
use AC\core\system\object\entity\format\BaseFormat;
use AC\core\system\router\RouteCollection;
use AC\core\system\service\history\HistoryService;
use AC\core\system\session\Session;
use AC\core\system\structure\Structure;
use AC\core\system\structure\TranslationStructure;
use AC\core\system\validators\Validation;
use AC\core\system\view\TemplateResolver;
use AC\core\system\view\View;
use Service;

/** Фабрики сервисов приложения с поддержкой общих экземпляров и локальных переопределений. */
class Services extends BaseService
{
  /** Возвращает сервис иерархии интерфейсов и поиска шаблонов. */
  public static function templates(bool $getShared = true): TemplateResolver
  {
    if ($getShared) {
      return static::getSharedInstance('templates');
    }
    return useClass(paths()->systemDir . 'view\TemplateResolver', true);
  }

  /**
   * Возвращает экземпляр класса Url для работы с URL.
   *
   * @param string|null $uri       URI для инициализации
   * @param bool        $getShared Использовать shared-экземпляр
   *
   * @return Url
   */
  public static function url(?string $uri = null, bool $getShared = true): Url
  {
    if ($getShared) {
      return static::getSharedInstance('url', $uri);
    }

    return useClass(paths()->systemDir . 'http\url\Url', true, $uri);
  }

  /**
   * Возвращает экземпляр класса URI для работы с URI.
   *
   * @param string|null $uri       URI для инициализации
   * @param bool        $getShared Использовать shared-экземпляр
   *
   * @return URI
   */
  public static function uri(?string $uri = null, bool $getShared = true): URI
  {
    if ($getShared) {
      return static::getSharedInstance('uri', $uri);
    }

    return useClass(paths()->systemDir . 'http\url\URI', true, $uri);
  }

  /**
   * Возвращает экземпляр класса Session для работы с сессиями.
   *
   * @param bool $getShared Использовать shared-экземпляр
   *
   * @return Session
   */
  public static function session(bool $getShared = true): Session
  {
    if ($getShared) {
      return static::getSharedInstance('session');
    }

    return useClass(paths()->systemDir . 'session\Session', true);
  }


  /**
   * Возвращает экземпляр класса Response для HTTP-ответов.
   *
   * @param AppConfig|null $config    Конфигурация приложения
   * @param bool           $getShared Использовать shared-экземпляр
   *
   * @return Response
   */
  public static function response(?AppConfig $config = null, bool $getShared = true): Response
  {
    if ($getShared) {
      return static::getSharedInstance('response', $config);
    }

    return useClass(paths()->systemDir . 'http\response\Response', true, $config ?: config('App'));
  }

  /**
   * Возвращает экземпляр класса IncomingRequest для HTTP-запросов.
   *
   * @param AppConfig|null $config    Конфигурация приложения
   * @param bool           $getShared Использовать shared-экземпляр
   *
   * @return IncomingRequest
   */
  public static function request(?AppConfig $config = null, bool $getShared = true): IncomingRequest
  {
    if ($getShared) {
      return static::getSharedInstance('request', $config);
    }

    return useClass(
      paths()->systemDir . 'http\request\IncomingRequest',
      true,
      $config ?: config('app')
    );
  }

  /**
   * Возвращает сервис выбора formatter-а HTTP-ответа по MIME-типу.
   *
   * @param FormatConfig|null $config    Конфигурация доступных форматов
   * @param bool              $getShared Использовать shared-экземпляр
   *
   * @return Format
   */
  public static function format(?FormatConfig $config = null, bool $getShared = true): Format
  {
    if ($getShared) {
      return static::getSharedInstance('format', $config);
    }

    return useClass(
      paths()->systemDir . 'format\Format',
      true,
      $config ?: config('Format')
    );
  }

  /**
   * Возвращает сервис согласования HTTP-заголовков запроса.
   *
   * @param RequestInterface|null $request   HTTP-запрос для согласования
   * @param bool                  $getShared Использовать shared-экземпляр
   *
   * @return Negotiate
   */
  public static function negotiator(?RequestInterface $request = null, bool $getShared = true): Negotiate
  {
    if ($getShared) {
      return static::getSharedInstance('negotiator', $request);
    }

    return useClass(
      paths()->systemDir . 'http\Negotiate',
      true,
      $request ?: Service::request()
    );
  }

  /**
   * Возвращает адаптер модельных валидаторов для произвольных данных.
   *
   * @param bool $getShared Использовать shared-экземпляр
   *
   * @return Validation
   */
  public static function validation(bool $getShared = true): Validation
  {
    if ($getShared) {
      return static::getSharedInstance('validation');
    }

    return useClass(paths()->systemDir . 'validators\Validation', true);
  }

  /**
   * Возвращает экземпляр класса Exceptions для обработки исключений.
   *
   * @param ExceptionsConfig|null $config    Конфигурация исключений
   * @param IncomingRequest|null  $request   Объект запроса
   * @param Response|null         $response  Объект ответа
   * @param bool                  $getShared Использовать shared-экземпляр
   *
   * @return Exceptions
   */
  public static function exceptions(
    ?ExceptionsConfig $config = null,
    ?IncomingRequest $request = null,
    ?Response $response = null,
    bool $getShared = true
  ): Exceptions {
    if ($getShared) {
      return static::getSharedInstance('exceptions', $config, $request, $response);
    }

    $config   = $config ?: config('Exceptions');
    $request  = $request ?: Service::request();
    $response = $response ?: Service::response();

    return useClass(
      paths()->systemDir . 'debug\Exceptions',
      true,
      $config,
      $request,
      $response
    );
  }

  /**
   * Возвращает экземпляр класса CURLRequest для HTTP-клиентских запросов.
   *
   * @param array                  $options   Параметры запроса
   * @param ResponseInterface|null $response  Объект ответа
   * @param AppConfig|null         $config    Конфигурация приложения
   * @param bool                   $getShared Использовать shared-экземпляр
   *
   * @return CURLRequest
   */
  public static function curl(
    array $options = [],
    ?ResponseInterface $response = null,
    ?AppConfig $config = null,
    bool $getShared = true
  ): CURLRequest {
    if ($getShared === true) {
      return static::getSharedInstance('curl', $options, $response, $config);
    }

    $config   = $config ?: config('App');
    $response = $response ?: new Response($config);

    return new CURLRequest(
      new URI(isset($options['base_uri']) ? $options['base_uri'] : null),
      $response,
      $options
    );
  }

  /**
   * Возвращает экземпляр класса Structure или TranslationStructure.
   *
   * @param string|null $alias     Алиас структуры
   * @param string      $prefix    Префикс для названия класса
   * @param bool        $getShared Использовать shared-экземпляр
   *
   * @return Structure|TranslationStructure
   */
  public static function structure(?string $alias = null, string $prefix = '', bool $getShared = true): Structure|TranslationStructure
  {
    if ($getShared && !$alias) {
      return static::getSharedInstance('structure', $alias, $prefix);
    }
    $className = useClass(paths()->systemDir . 'structure\\' . ucfirst($prefix) . 'Structure')
      ?: useClass(paths()->systemDir . 'structure\Structure');

    return useClass(
      $className,
      true,
      $alias
    );
  }

  /**
   * Возвращает экземпляр класса Translations для работы с локализацией.
   *
   * @param LangConfig|null $config    Конфигурация языка
   * @param string|null     $lang
   * @param bool            $getShared Использовать shared-экземпляр
   *
   * @return Translations
   */
  public static function lang(?string $lang = null, ?LangConfig $config = null, bool $getShared = true): Translations
  {
    $lang = $lang ?? config('lang')->getCurrentLang();

    if ($getShared) {
      return static::getSharedInstanceItem('lang', $lang, $config);
    }

    return useClass(
      paths()->systemDir . 'language\Translations',
      true,
      $config, $lang
    );
  }

  /**
   * Возвращает экземпляр класса View для отображения шаблонов.
   *
   * @param bool $getShared Использовать shared-экземпляр
   *
   * @return View
   */
  public static function view(bool $getShared = true): View
  {
    if ($getShared) {
      return static::getSharedInstance('view');
    }

    return useClass(paths()->systemDir . 'view\View', true);
  }

  /**
   * Возвращает экземпляр главной страницы (Page).
   *
   * @param string|null $path Путь к файлу страницы
   *
   * @return View
   */
  public static function mainPage(?string $path = null): View
  {
    if (!empty(static::$instances['mainPage'])) {
      return static::$instances['mainPage'];
    }
    static::$instances['mainPage'] = useClass($path ?? paths()->systemDir . 'view\Page', true);

    return static::$instances['mainPage'];
  }

  /** Проверяет, установлена ли главная страница
   *
   * @return bool
   */
  public static function checkInstanceMainPage(): bool
  {
    return !empty(static::$instances['mainPage']);
  }

  /**
   * Возвращает экземпляр класса Query для работы с БД.
   *
   * @param array $options   Параметры запроса
   * @param bool  $getShared Использовать shared-экземпляр
   *
   * @return Query
   */
  public static function query(array $options = [], bool $getShared = true): Query
  {
    if ($getShared) {
      return static::getSharedInstance('query', $options);
    }

    return useClass(paths()->systemDir . 'db\Query', true, $options);
  }

  /**
   * Возвращает экземпляр класса AuthInterface для аутентификации.
   *
   * @param string|null $mode      Режим аутентификации (admin/client)
   * @param bool        $getShared Использовать shared-экземпляр
   *
   * @return AuthInterface
   */
  public static function auth(?string $mode = null, bool $getShared = true): AuthInterface
  {
    if ($getShared) {
      return static::getSharedInstance('auth', $mode);
    }

    $nameModule = match ($mode ?: self::url()->getTemplatePath()) {
      'admin' => config('auth')->nameModuleAdmins,
      default => config('auth')->nameModuleClients,
    };

    return module($nameModule, ['mode' => 'auth'], false)->useController();
  }

  /**
   * Возвращает экземпляр класса RouteCollection для маршрутов.
   *
   * @param bool $getShared Использовать shared-экземпляр
   *
   * @return RouteCollection
   */
  public static function routes(bool $getShared = true): RouteCollection
  {
    if ($getShared) {
      return static::getSharedInstance('routes');
    }

    return useClass(paths()->systemDir . 'router\RouteCollection', true, self::locator(), config('Modules'));
  }


  /**
   * Возвращает экземпляр класса RedirectResponse для перенаправлений.
   *
   * @param string|null    $route     Маршрут для перенаправления
   * @param AppConfig|null $config    Конфигурация приложения
   * @param bool           $getShared Использовать shared-экземпляр
   *
   * @return RedirectResponse
   */
  public static function redirect(?string $route = null, ?AppConfig $config = null, bool $getShared = true): RedirectResponse
  {
    if ($getShared) {
      $response = static::getSharedInstance('redirect', $route, $config);
    } else {
      $config   = $config ?: config('App');
      $response = useClass(paths()->systemDir . 'http\response\RedirectResponse', true, $config);
      $response->setProtocolVersion(self::request()->getProtocolVersion());
    }

    if (!empty($route)) {
      return $response->route($route);
    }

    return $response;
  }

  /**
   * Возвращает экземпляр класса Engines для работы с движками.
   *
   * @param array $options   Параметры инициализации
   * @param bool  $getShared Использовать shared-экземпляр
   *
   * @return Engines
   */
  public static function engines(array $options = [], bool $getShared = true): Engines
  {
    if ($getShared) {
      return static::getSharedInstance('engines', $options);
    }

    return useClass(paths()->enginesDir . 'Engines', true, $options);
  }

  /**
   * Получает конфигурацию из базы данных.
   *
   * @param mixed $type  Тип конфигурации
   * @param mixed $alias Алиас конфигурации
   *
   * @return mixed
   */
  public static function configDB(mixed $type, mixed $alias): mixed
  {
    return self::configDBModel()->_($type, $alias);
  }

  public static function configDBModel(): ConfigDbValueSelector
  {
    static $configDBModel;
    if (!$configDBModel) {
      $configDBModel = module('config', [], false)->useModel('ConfigDbValueSelector');
    }

    return $configDBModel;
  }

  /**
   * Точка входа scoped-конфига (чтение из config / client_restriction).
   */
  public static function scopedConfig(): ScopedConfigGatewayInterface
  {
    static $scopedConfig;
    if (!$scopedConfig) {
      $scopedConfig = new ScopedConfigGateway();
    }

    return $scopedConfig;
  }

  /** Для работы с таблицами с полем preferences
   *  формат хранения json
   *
   * @param string $tableName  Название таблицы
   * @param string $primaryKey Поле первичного ключа
   * @param string $fieldName  Поле с данными
   *
   * @return PreferencesEngine
   */
  public static function tablePreferences(string $tableName, string $primaryKey = 'id', string $fieldName = 'preferences'): PreferencesEngine
  {
    return getEngine('preferences', false, false)
      ?->setTableName($tableName)
      ?->setPrimaryKey($primaryKey)
      ?->setFieldName($fieldName)
      ?->init();
  }

  /** Работа с литьем.
   *  Есть разные форматы и две функции get и set
   *  для кодировки в этот формат и декодировки.
   *
   * @param string $castName  Название формата
   * @param bool   $getShared Использовать shared-экземпляр
   *
   * @return BaseCast
   */
  public static function cast(string $castName = 'base', bool $getShared = true): BaseCast
  {
    if ($getShared) {
      return static::getSharedInstanceItem('cast', $castName);
    }

    return useClass(paths()->systemDir . 'object\entity\cast\\' . ucfirst($castName) . 'Cast', true);
  }

  /** Работа данными определенного формата.
   *
   * @param string $name      Название формата
   * @param bool   $getShared Использовать shared-экземпляр
   *
   * @return BaseFormat
   */
  public static function formatData(string $name = 'base', bool $getShared = true): BaseFormat
  {
    if ($getShared) {
      return static::getSharedInstanceItem('formatData', $name);
    }
    return useClass(paths()->systemDir . 'object\entity\format\\' . ucfirst($name), true);
  }

  /**
   * Возвращает экземпляр класса Logger для логирования.
   *
   * @param string        $name      Имя логгера (ключ для хранения/получения)
   * @param callable|null $provider  Провайдер временных меток
   * @param bool          $getShared Использовать shared-экземпляр
   *
   * @return Logger
   */
  public static function logger(string $name = 'common', ?callable $provider = null, bool $getShared = true): Logger
  {
    if ($getShared) {
      return self::getSharedInstanceItem('logger', $name, $provider);
    }
    return useClass(paths()->systemDir . 'debug\Logger', true, $name, $provider);
  }

  /**
   * Возвращает экземпляр класса HistoryService для отслеживания истории операций.
   *
   * @param string $operationId Уникальный идентификатор операции
   * @param array  $params      Параметры операции
   * @param bool   $getShared   Использовать shared-экземпляр
   *
   * @return HistoryService
   */
  public static function history(string $operationId = 'common', array $params = [], bool $getShared = true): HistoryService
  {
    if ($getShared) {
      return self::getSharedInstanceItem('history', $operationId, $params, false);
    }

    return useClass(paths()->systemDir . 'service\history\HistoryService', true, $operationId, $params);
  }

  public static function mailer(?string $name = null, $getShared = false, $options = []): Mailer
  {
    $name = $name ?? 'mailer';
    if ($getShared) {
      return static::getSharedInstanceItem('mailer', $name, $options);
    }

    return useClass(paths()::MODULES_DIR . '\\mailing\\' . paths()::SERVICES_DIR . '\\' . ucfirst($name), true, $options);
  }
}
