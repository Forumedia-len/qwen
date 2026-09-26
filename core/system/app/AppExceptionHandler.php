<?php

declare(strict_types=1);

namespace AC\core\system\app;

use AC\core\system\App;
use AC\core\system\exceptions\PageNotFoundException;
use AC\core\system\exceptions\RedirectException;
use AC\core\system\http\response\ResponseInterface;
use Exception;
use Throwable;

/**
 * Класс для обработки исключений приложения
 */
class AppExceptionHandler
{
  /**
   * @var App
   */
  private App $app;

  /**
   * @var array
   */
  private array $handlers = [];

  public function __construct(App $app)
  {
    $this->app = $app;
    $this->registerDefaultHandlers();
  }

  /**
   * Регистрация обработчика исключений
   * 
   * @param string $exceptionClass
   * @param callable $handler
   */
  public function registerHandler(string $exceptionClass, callable $handler): void
  {
    $this->handlers[$exceptionClass] = $handler;
  }

  /**
   * Обработка исключения
   * 
   * @param Throwable $exception
   * @return ResponseInterface|void
   */
  public function handle(Throwable $exception)
  {
    $exceptionClass = get_class($exception);

    // Поиск точного совпадения
    if (isset($this->handlers[$exceptionClass])) {
      return call_user_func($this->handlers[$exceptionClass], $exception, $this->app);
    }

    // Поиск по иерархии классов
    foreach ($this->handlers as $class => $handler) {
      if ($exception instanceof $class) {
        return call_user_func($handler, $exception, $this->app);
      }
    }

    // Обработка по умолчанию
    return $this->handleDefault($exception);
  }

  /**
   * Регистрация обработчиков по умолчанию
   */
  private function registerDefaultHandlers(): void
  {
    $this->registerHandler(RedirectException::class, [$this, 'handleRedirect']);
    $this->registerHandler(PageNotFoundException::class, [$this, 'handlePageNotFound']);
    $this->registerHandler(Exception::class, [$this, 'handleGeneric']);
  }

  /**
   * Обработка перенаправления
   * 
   * @param RedirectException $exception
   * @param App $app
   */
  public function handleRedirect(RedirectException $exception, App $app): void
  {
    exit($exception->getMessage());
  }

  /**
   * Обработка страницы не найдена
   * 
   * @param PageNotFoundException $exception
   * @param App $app
   */
  public function handlePageNotFound(PageNotFoundException $exception, App $app): void
  {
    exit($exception->getMessage());
  }

  /**
   * Обработка общих исключений
   * 
   * @param Exception $exception
   * @param App $app
   */
  public function handleGeneric(Exception $exception, App $app): void
  {
    $this->logException($exception);
    exit($exception->getMessage());
  }

  /**
   * Обработка по умолчанию
   * 
   * @param Throwable $exception
   */
  private function handleDefault(Throwable $exception): void
  {
    $this->logException($exception);
    exit($exception->getMessage());
  }

  /**
   * Логирование исключения
   * 
   * @param Throwable $exception
   */
  private function logException(Throwable $exception): void
  {
    // Здесь можно добавить логирование исключений
    // Service::logger()->error('Application error: ' . $exception->getMessage(), [
    //   'exception' => $exception,
    //   'file' => $exception->getFile(),
    //   'line' => $exception->getLine(),
    //   'trace' => $exception->getTraceAsString()
    // ]);
  }

  /**
   * Получить все зарегистрированные обработчики
   * 
   * @return array
   */
  public function getHandlers(): array
  {
    return $this->handlers;
  }

  /**
   * Проверить, есть ли обработчик для исключения
   * 
   * @param string $exceptionClass
   * @return bool
   */
  public function hasHandler(string $exceptionClass): bool
  {
    return isset($this->handlers[$exceptionClass]);
  }

  /**
   * Удалить обработчик исключений
   * 
   * @param string $exceptionClass
   */
  public function removeHandler(string $exceptionClass): void
  {
    unset($this->handlers[$exceptionClass]);
  }

  /**
   * Очистить все обработчики
   */
  public function clearHandlers(): void
  {
    $this->handlers = [];
  }
}
