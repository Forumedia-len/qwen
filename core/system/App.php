<?php

declare(strict_types=1);

namespace AC\core\system;

use AC\core\system\actions\DeviceActionInterface;
use AC\core\system\exceptions\PageNotFoundException;
use AC\core\system\exceptions\RedirectException;
use AC\core\system\exceptions\http\RequestValidationException;
use AC\core\system\files\MimeType;
use AC\core\system\helpers\FileHelper;
use AC\core\system\http\response\ResponseInterface;
use AC\core\system\http\url\Url;
use AC\core\system\module\BaseModule;
use AC\core\modules\config\models\ConfigModel;
use AC\core\system\http\request\IncomingRequest;
use AC\app\locators\Service;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use PHPMailer\PHPMailerException;

/**
 * Главный класс приложения
 *
 * Управляет жизненным циклом приложения, обработкой запросов,
 * определением типа устройства и выполнением соответствующих действий.
 */
class App
{
  /**
   * @var App|null Синглтон-экземпляр приложения
   */
  static public ?App $app = null;
  
  /**
   * @var array Параметры приложения
   */
  public array $params = [];
  
  /**
   * @var IncomingRequest|null Экземпляр входящего HTTP-запроса
   */
  protected ?IncomingRequest $request = null;
  
  /**
   * @var ConfigModel|null Модель конфигурации
   */
  private ?ConfigModel $config = null;
  
  /**
   * @var BaseModule|null Текущий модуль приложения
   */
  public ?BaseModule $module = null;
  
  /**
   * @var DeviceActionInterface|null Действие для текущего устройства
   */
  private ?DeviceActionInterface $deviceAction = null;
  
  /**
   * @var array
   */
  private array $usedModules = [];
  
  /**
   * Приватный конструктор для реализации паттерна Singleton
   */
  private function __construct() { }
  
  /**
   * Инициализация приложения
   *
   * @param bool $newInstance Флаг создания нового экземпляра
   * @return App Экземпляр приложения
   */
  public static function initialize(bool $newInstance = false): App
  {
    if (self::$app === null || $newInstance) {
      $appClassName = useClass(paths()->systemDir . 'App');
      self::$app    = new $appClassName();
    }
    
    return self::$app;
  }
  
  /**
   * Основной метод запуска приложения
   *
   * @return ResponseInterface|void Ответ HTTP-запроса
   * @throws RedirectException При необходимости перенаправления
   * @throws PageNotFoundException При отсутствии страницы
   * @throws Exception Общие ошибки приложения
   */
  public function run()
  {
    try {
      $this->initializeRequest();
      $this->checkDeviceAvailability();
      $this->checkOldSsl();
      $this->handleStaticFiles();
      $this->initializeSession();
      $this->initializeTranslation();
      $this->initializeDeviceAction();
      
      $response = $this->processRequest();
      if ($response instanceof ResponseInterface) {
        return $response->send();
      }
      if (!$response) {
        $this->send404('Not found page "' . $this->request->getUrl() . '"');
      }
      
      $this->finalizeRequest(['_page' => $response]);
    } catch (RequestValidationException $e) {
      return $this->handleRequestValidationException($e);
    } catch (RedirectException|PageNotFoundException|Exception $e) {
      $this->handleException($e);
      $this->send404($e->getMessage());
    }
  }
  
  /**
   * Отправляет ответ 404 Not Found.
   *
   * @param string $message Внутренняя причина; не передаётся в пользовательский шаблон.
   * @return void
   */
  #[NoReturn] protected function send404(string $message = ''): void
  {
    $request = $this->request ?? Service::request();
    $html = !$request->isAJAX() && str_contains(strtolower($request->getHeaderLine('Accept')), 'text/html');
    Service::response()->setStatusCode(404)->setContentType($html ? 'text/html' : 'text/plain', 'UTF-8')
      ->setBody($html
        ? Service::view()->renderer(paths()->getTplDir('html/error_404.php', 'errors'))
        : lang('page_not_found', 'http_error'))->send();
    exit;
  }
  
  
  /**
   * Инициализация HTTP-запроса
   */
  protected function initializeRequest(): void
  {
    $this->request = Service::request();
  }

  /** Проверяет доступность устройства до выдачи файлов и выполнения действий пользователя. */
  protected function checkDeviceAvailability(): void
  {
    if ($this->request->getUrl()->getTemplatePath() === 'widget' && !USE_WIDGET) {
      // Ответ 404 должен отображаться и при встраивании на другом сайте.
      header_remove('X-Frame-Options');
      $this->initializeStructureTranslations();
      $this->send404();
    }
  }
  
  /**
   * Обработка статических файлов
   */
  protected function handleStaticFiles(): void
  {
    if ($this->request) {
      $url = $this->request->getUrl();
      $this->uploadStaticFileNotPhp($url);
    }
  }
  
  /**
   * Инициализация сессии
   */
  protected function initializeSession(): void
  {
    if ($this->request) {
      $url = $this->request->getUrl();
      if ($url instanceof Url) {
        if ($url->getTemplatePath() === 'widget') {
          Service::session()->setCookieOptions(['samesite' => 'None']);
        }
        Service::session()->start(SESSION_COURT . '_' . $url->getTemplatePath());
      }
    }
  }
  
  /**
   * Инициализация системы локализации
   */
  protected function initializeTranslation(): void
  {
    config('lang')->setLang(Service::request()->_get('lang'));
    $this->initializeStructureTranslations();
  }

  /** Загружает подписи структуры, в том числе для ошибки до запуска сессии и обработки параметров запроса. */
  protected function initializeStructureTranslations(): void
  {
    $langDir        = paths()->getLangDir();
    foreach (Service::templates()->getMergeOrder() as $device) {
      $structureFiles = Service::locator()->search(
        Service::lang()->generateNameToAddFile('structure', $langDir, $device),
        'ini'
      );
      foreach ($structureFiles as $path) {
        Service::lang()->addTranslationsFromFile('structure', $path, $device);
      }
    }
  }
  
  /**
   * Инициализация действия для текущего устройства
   */
  protected function initializeDeviceAction(): void
  {
    $this->deviceAction = $this->getDeviceAction();
  }
  
  /**
   * Обработка HTTP-запроса
   *
   * @return ResponseInterface|array|null Результат обработки
   */
  protected function processRequest()
  {
    $possibleResponse = $this->deviceAction->before();
    
    // Если возвращен ResponseInterface, отправляем его и останавливаемся
    if ($possibleResponse instanceof ResponseInterface) {
      return $possibleResponse;
    }
    
    // Обработка статических PHP файлов
    $this->uploadStaticFilePhp($this->request->getUrl(), $this->deviceAction, $possibleResponse);
    
    // Извлечение переменных из ответа
    if ($possibleResponse && is_array($possibleResponse)) {
      extract($possibleResponse, EXTR_OVERWRITE);
    }
    
    // Загрузка и выполнение модуля
    return $this->executeModule();
  }
  
  /**
   * Выполнение модуля
   *
   * @return  ResponseInterface|array|null выполнения модуля
   */
  protected function executeModule(): ResponseInterface|array|null
  {
    $url = $this->request->getUrl();
    if ($url->getTotalSegments() && $module = module($url->getSegment(1), ['useRouting' => true])) {
      $this->module = $module;
      Service::autoloader()->raisePriorityNamespaceModuleUp($url->getSegment(1));
      
      $_page = $module->exec();
      // Структура уже загружена в singleton; повторный loadStructure() дублирует
      // чтение всех structure.*.php и заметно увеличивает TTFB на каждом запросе.
      return $_page;
    }
    
    return null;
  }
  
  /**
   * Завершение обработки запроса
   *
   * @param array $vars Переменные после выполнения
   */
  protected function finalizeRequest(array $vars): void
  {
    $this->deviceAction->after($vars);
  }

  /**
   * Записывает причину ошибки валидации в лог и отправляет ответ для текущего типа запроса.
   *
   * @param RequestValidationException $e Ошибка проверки входного параметра
   * @return ResponseInterface Отправленный HTTP-ответ
   */
  protected function handleRequestValidationException(RequestValidationException $e): ResponseInterface
  {
    // Не передаём в лог значения запроса и аргументы стека, которые могут содержать секреты.
    Service::logger('app')->warning('Request validation failed.', [
      'parameter' => $e->getAlias(),
      'source' => $e->getSource(),
      'errors' => $e->getErrors(),
      'method' => $this->request->getMethod(true),
      'path' => $this->request->getUrl()->getPath(),
      'file' => $e->getFile(),
      'line' => $e->getLine(),
      'trace' => array_map(
        static fn(array $frame): array => array_intersect_key($frame, array_flip(['file', 'line', 'class', 'type', 'function'])),
        $e->getTrace()
      ),
    ]);

    // Для открытия страницы показываем 404; AJAX и программные клиенты сохраняют прежний ответ валидации.
    if (!$this->request->isAJAX() && str_contains(strtolower($this->request->getHeaderLine('Accept')), 'text/html')) {
      return Service::response()->setStatusCode(404)->setContentType('text/html', 'UTF-8')
        ->setBody(Service::view()->renderer(paths()->getTplDir('html/error_404.php', 'errors'), [
          'publicMessage' => lang('invalid_request_input', 'http_error'),
        ]))->send();
    }
    return Service::response()->setStatusCode(400)->setContentType('text/plain', 'UTF-8')
      ->setBody(lang('invalid_request_input', 'http_error'))->send();
  }
  
  /**
   * Обработка исключений
   *
   * @param Exception $e Исключение для обработки
   * @throws PHPMailerException
   */
  protected function handleException(Exception $e): void
  {
    // TODO: доработать логирование - через Exception передавать тип ошибки и остальные данные(например необходимость отправки письма)
    Service::logger('app')->logException($e, 'Application error: ',
      ['app' => $this, 'auth' => Service::auth(), 'session' => $_SESSION, 'request' => $_REQUEST, 'url' => $_SERVER['REQUEST_URI']], 'critical');
  }
  
  /**
   * Обработка статических файлов (не PHP)
   *
   * @param Url $url URL-запрос
   */
  protected function uploadStaticFileNotPhp(Url $url): void
  {
    $file = $url->getPath();
    // Старое содержимое страниц хранит ресурсы относительно корня сайта.
    if ($url->getTemplatePath() === 'widget' && preg_match('~^(assets|uploads)/~', $url->getPath(false))) {
      $file = $url->getPath(false);
    }
    if (!$file) {
      return;
    }
    
    $extension = FileHelper::checkExtension($file, 'php');
    if (!$extension) {
      return;
    }
    
    $filePath = Service::autoloader()->getPathFile($file, $extension);
    if (!file_exists($filePath)) {
      return;
    }
    
    ob_get_level() || ob_end_clean();
    header('Content-Type: ' . MimeType::getExtensionMimes(FileHelper::checkExtension($filePath)));
    readfile($filePath);
    exit;
  }
  
  /**
   * Обработка статических PHP файлов
   *
   * @param Url                        $url              URL-запрос
   * @param DeviceActionInterface|null $deviceAction     Действие устройства
   * @param mixed|null                 $possibleResponse Возможный результат
   */
  protected function uploadStaticFilePhp(Url $url, ?DeviceActionInterface $deviceAction = null, mixed $possibleResponse = null): void
  {
    $path = $this->buildStaticFilePath($url);
    
    if (!FileHelper::checkExtension($path)) {
      return;
    }
    
    // Извлечение переменных из ответа
    if ($possibleResponse && is_array($possibleResponse)) {
      extract($possibleResponse, EXTR_OVERWRITE);
    }
    
    $scriptPath = Service::autoloader()->getPathFile($path);
    if (!$scriptPath || !is_file($scriptPath)) {
      $this->send404();
    }
    require_once $scriptPath;
    
    $this->finalizeRequest(get_defined_vars());
    
    exit;
  }
  
  /**
   * Построение пути к статическому файлу
   *
   * @param Url $url URL-запрос
   * @return string Путь к файлу
   */
  protected function buildStaticFilePath(Url $url): string
  {
    $path = empty($url->getPath(false))
      ? $url->getPath() . ($url->getDevicePath() === '' ? 'aktuelles.php' : 'index.php')
      : $url->getPath();
    if (
      !FileHelper::checkExtension($path) &&
      (Service::autoloader()->actualPath($path . '/index.php'))
    ) {
      $path .= '/index.php';
    }
    
    return $path;
  }
  
  /**
   * Получение действия устройства
   *
   * @return DeviceActionInterface Экземпляр действия устройства
   */
  protected function getDeviceAction(): DeviceActionInterface
  {
    $priorities = [
      paths()->appDir . paths()->actionsDir . 'device\\' . ucfirst(Service::url()->getTemplatePath()) . 'DeviceAction',
      paths()->systemDir . paths()->actionsDir . 'DeviceAction'
    ];
    
    foreach ($priorities as $priority) {
      $deviceAction = useClass($priority, true);
      if ($deviceAction) {
        return $deviceAction;
      }
    }
    
    // Возвращаем базовое действие по умолчанию
    return useClass(paths()->systemDir . paths()->actionsDir . 'DeviceAction', true);
  }
  
  /**
   * Проверка старого SSL (временная мера)
   *
   * @deprecated Временная мера
   */
  protected function checkOldSsl(): void
  {
    if (
      !LOCAL_SERVER
      && strpos(config('App')->baseURL, 'ssl.forumedia.eu')
      && (!isset($_SERVER['HTTP_X_FORWARDED_HOST']) || $_SERVER['HTTP_X_FORWARDED_HOST'] != 'ssl.forumedia.eu')
    ) {
      header("Location: " . site_url());
      exit();
    }
  }
  
  /**
   * Выполнение модуля
   *
   * @param string $moduleName Название модуля
   * @param array  $options    Дополнительные параметры
   * @return array|null Результат выполнения модуля
   */
  public function execModule(string $moduleName, array $options = []): ?array
  {
    $module = module($moduleName, $options);
    if (!$module) {
      return null;
    }
    
    $this->module = $module;
    return $this->module->exec($options['action'] ?? null);
  }
  
  /**
   * Получение текущего модуля
   *
   * @return BaseModule|null Текущий модуль
   */
  public function getModule(): ?BaseModule
  {
    return $this->module;
  }
  
  /**
   * Установка параметра приложения
   *
   * @param string $key   Ключ параметра
   * @param mixed  $value Значение параметра
   */
  public static function setAppParam(string $key, $value): void
  {
    if (self::$app) {
      self::$app->{$key} = $value;
    }
  }
  
  /**
   * Получение базового шаблона
   *
   * @param false|string $template Шаблон (по умолчанию из URL)
   * @return string Путь к шаблону
   */
  public static function getBaseTemplate(false|string $template = false): string
  {
    return match ($template ?: Service::url()->getTemplatePath()) {
      'admin' => 'at/',
      'touch' => 'touchscreen/',
      'site'  => '/',
      'widget' => 'widget/',
      default => $template . '/',
    };
  }
  
  /**
   * Проверка, что запрос с админки
   *
   * @return bool true, если запрос с админской части
   */
  public function isAdmin(): bool
  {
    return Service::url()->getTemplatePath() === 'admin';
  }
  
  /**
   * Проверка, что запрос с тач-скрина
   *
   * @return bool true, если запрос с touch-устройства
   */
  public function isTouch(): bool
  {
    return Service::url()->getTemplatePath() === 'touch';
  }
  
  /**
   * Проверка, что запрос с дисплея
   *
   * @return bool true, если запрос с display-устройства
   */
  public function isDisplay(): bool
  {
    return Service::url()->getTemplatePath() === 'display';
  }
  
  /**
   * Получение HTTP-запроса
   *
   * @return IncomingRequest|null Текущий HTTP-запрос
   */
  public function getRequest(): ?IncomingRequest
  {
    return $this->request;
  }
  
  /**
   * Получение действия устройства
   *
   * @return DeviceActionInterface|null Текущее действие устройства
   */
  public function getDeviceActionInstance(): ?DeviceActionInterface
  {
    return $this->deviceAction;
  }
  
  /**
   * @return array
   */
  public function getUsedModules(): array
  {
    return $this->usedModules;
  }
  
  /**
   * @param string $moduleName
   * @return void
   */
  public function setUsedModule(string $moduleName): void
  {
    // Добавляем имя модуля с изменением порядка приоритета
    
    array_unshift($this->usedModules, $moduleName);
    $this->usedModules = array_unique($this->usedModules);
  }
  
  public function checkModuleUsed(string $moduleName): bool
  {
    return in_array($moduleName, $this->usedModules, true);
  }
}
