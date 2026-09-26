<?php
//
//declare(strict_types=1);
//
//namespace AC\core\system\app;
//
//use AC\app\locators\Service;
//use AC\core\system\actions\DeviceActionInterface;
//use AC\core\system\App;
//use AC\core\system\helpers\FileHelper;
//use AC\core\system\http\request\IncomingRequest;
//use AC\core\system\http\response\ResponseInterface;
//use AC\core\system\module\BaseModule;
//use Exception;
//
///**
// * Класс для управления жизненным циклом приложения
// */
//class AppLifecycle
//{
//  /**
//   * @var App
//   */
//  private App $app;
//
//  /**
//   * @var IncomingRequest|null
//   */
//  private ?IncomingRequest $request = null;
//
//  /**
//   * @var DeviceActionInterface|null
//   */
//  private ?DeviceActionInterface $deviceAction = null;
//
//  /**
//   * @var BaseModule|null
//   */
//  private ?BaseModule $module = null;
//
//  /**
//   * @var array
//   */
//  private array $context = [];
//
//  public function __construct(App $app)
//  {
//    $this->app = $app;
//  }
//
//  /**
//   * Выполнить полный жизненный цикл приложения
//   *
//   * @return ResponseInterface|void
//   * @throws Exception
//   */
//  public function execute()
//  {
//    try {
//      $this->initialize();
//      $this->process();
//      $this->finalize();
//    } catch (Exception $e) {
//      $this->handleException($e);
//      throw $e;
//    }
//  }
//
//  /**
//   * Инициализация приложения
//   */
//  public function initialize(): void
//  {
//    $this->initializeRequest();
//    $this->initializeSession();
//    $this->initializeTranslation();
//    $this->initializeDeviceAction();
//  }
//
//  /**
//   * Обработка запроса
//   *
//   * @return ResponseInterface|null
//   */
//  public function process(): ?ResponseInterface
//  {
//    $this->handleStaticFiles();
//
//    $response = $this->processRequest();
//    if ($response instanceof ResponseInterface) {
//      return $response;
//    }
//
//    return $this->executeModule();
//  }
//
//  /**
//   * Завершение обработки
//   */
//  public function finalize(): void
//  {
//    if ($this->deviceAction) {
//      $this->deviceAction->after($this->context);
//    }
//  }
//
//  /**
//   * Инициализация запроса
//   */
//  private function initializeRequest(): void
//  {
//    $this->request = Service::request();
//    $this->app->checkOldSsl();
//  }
//
//  /**
//   * Инициализация сессии
//   */
//  private function initializeSession(): void
//  {
//    if ($this->request) {
//      $url = $this->request->getUrl();
//      if ($url instanceof \AC\core\system\http\url\Url) {
//        Service::session()->start(SESSION_COURT . '_' . $url->getTemplatePath());
//      }
//    }
//  }
//
//  /**
//   * Инициализация перевода
//   */
//  private function initializeTranslation(): void
//  {
//    Service::lang()->setLang();
//
//    $langDir = paths()->getLangDir();
//    $structureFiles = Service::locator()->search(
//      Service::lang()->generateNameToAddFile('structure', $langDir),
//      'ini'
//    );
//
//    foreach ($structureFiles as $path) {
//      Service::lang()->addTranslationsFromFile('structure', $path);
//    }
//  }
//
//  /**
//   * Инициализация действий устройства
//   */
//  private function initializeDeviceAction(): void
//  {
//    $this->deviceAction = $this->getDeviceAction();
//  }
//
//  /**
//   * Обработка статических файлов
//   */
//  private function handleStaticFiles(): void
//  {
//    if ($this->request) {
//      $url = $this->request->getUrl();
//      $this->uploadStaticFileNotPhp($url);
//    }
//  }
//
//  /**
//   * Обработка запроса
//   *
//   * @return ResponseInterface|array|null
//   */
//  private function processRequest()
//  {
//    if (!$this->deviceAction) {
//      return null;
//    }
//
//    $possibleResponse = $this->deviceAction->before();
//
//    if ($possibleResponse instanceof ResponseInterface) {
//      return $possibleResponse;
//    }
//
//    if ($this->request) {
//      $this->uploadStaticFilePhp($this->request->getUrl(), $this->deviceAction, $possibleResponse);
//    }
//
//    if ($possibleResponse && is_array($possibleResponse)) {
//      $this->context = array_merge($this->context, $possibleResponse);
//    }
//
//    return null;
//  }
//
//  /**
//   * Выполнение модуля
//   *
//   * @return ResponseInterface|null
//   */
//  private function executeModule(): ?ResponseInterface
//  {
//    if (!$this->request) {
//      return null;
//    }
//
//    $url = $this->request->getUrl();
//
//    if (Service::url()->getTotalSegments() && $module = module($url->getSegment(1), ['useRouting' => true])) {
//      $this->module = $module;
//      Service::autoloader()->raisePriorityNamespaceModuleUp($url->getSegment(1));
//
//      $_page = $module->exec();
//      Service::structure()->reloadTree();
//
//      if ($_page instanceof ResponseInterface) {
//        return $_page;
//      }
//
//      if (is_array($_page)) {
//        $this->context = array_merge($this->context, $_page);
//      }
//    }
//
//    return null;
//  }
//
//  /**
//   * Обработка исключений
//   *
//   * @param Exception $e
//   */
//  private function handleException(Exception $e): void
//  {
//    // Здесь можно добавить логирование исключений
//    // Service::logger()->error('Application lifecycle error: ' . $e->getMessage(), ['exception' => $e]);
//  }
//
//  /**
//   * Обработка статических файлов (не PHP)
//   *
//   * @param mixed $url
//   */
//  private function uploadStaticFileNotPhp($url): void
//  {
//    if (!$url || !method_exists($url, 'getPath')) {
//      return;
//    }
//
//    $file = $url->getPath();
//    if (!$file) {
//      return;
//    }
//
//    $extension = FileHelper::getExtension($file, 'php');
//    if (!$extension) {
//      return;
//    }
//
//    $filePath = Service::autoloader()->getPathFile($file, $extension);
//    if (!file_exists($filePath)) {
//      return;
//    }
//
//    ob_get_level() || ob_end_clean();
//    header('Content-Type: ' . \AC\core\system\files\MimeType::getExtensionMimes(FileHelper::getExtension($filePath)));
//    readfile($filePath);
//    exit;
//  }
//
//  /**
//   * Обработка статических PHP файлов
//   *
//   * @param mixed $url
//   * @param DeviceActionInterface|null $deviceAction
//   * @param mixed $possibleResponse
//   */
//  private function uploadStaticFilePhp($url, ?DeviceActionInterface $deviceAction = null, $possibleResponse = null): void
//  {
//    if (!$url || !method_exists($url, 'getPath')) {
//      return;
//    }
//
//    $path = $this->buildStaticFilePath($url);
//
//    if (!FileHelper::getExtension($path)) {
//      return;
//    }
//
//    if ($possibleResponse && is_array($possibleResponse)) {
//      extract($possibleResponse, EXTR_OVERWRITE);
//    }
//
//    require_once Service::autoloader()->getPathFile($path);
//
//    if ($deviceAction) {
//      $deviceAction->after(get_defined_vars());
//    }
//
//    exit;
//  }
//
//  /**
//   * Построение пути к статическому файлу
//   *
//   * @param mixed $url
//   * @return string
//   */
//  private function buildStaticFilePath($url): string
//  {
//    $path = empty($url->getPath(false))
//      ? $url->getPath() . ($url->getDevicePath() === '' ? 'aktuelles.php' : 'index.php')
//      : $url->getPath();
//
//    if (
//      !FileHelper::getExtension($path) &&
//      ($_path = Service::autoloader()->actualPath($path . '/index.php'))
//    ) {
//      $path = $path . '/index.php';
//    }
//
//    return $path;
//  }
//
//  /**
//   * Получение действия устройства
//   *
//   * @return DeviceActionInterface
//   */
//  private function getDeviceAction(): DeviceActionInterface
//  {
//    $priorities = [
//      paths()->appDir . paths()->actionsDir . 'device\\' . ucfirst(Service::url()->getTemplatePath()) . 'DeviceAction',
//      paths()->systemDir . paths()->actionsDir . 'DeviceAction'
//    ];
//
//    foreach ($priorities as $priority) {
//      $deviceAction = useClass($priority, true);
//      if ($deviceAction) {
//        return $deviceAction;
//      }
//    }
//
//    return useClass(paths()->systemDir . paths()->actionsDir . 'DeviceAction', true);
//  }
//
//  /**
//   * Получить контекст выполнения
//   *
//   * @return array
//   */
//  public function getContext(): array
//  {
//    return $this->context;
//  }
//
//  /**
//   * Установить контекст выполнения
//   *
//   * @param array $context
//   */
//  public function setContext(array $context): void
//  {
//    $this->context = $context;
//  }
//
//  /**
//   * Получить текущий модуль
//   *
//   * @return BaseModule|null
//   */
//  public function getModule(): ?BaseModule
//  {
//    return $this->module;
//  }
//
//  /**
//   * Получить действие устройства
//   *
//   * @return DeviceActionInterface|null
//   */
//  public function getDeviceActionInstance(): ?DeviceActionInterface
//  {
//    return $this->deviceAction;
//  }
//}
