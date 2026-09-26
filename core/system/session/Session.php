<?php

namespace AC\core\system\session;

/**
 * Class Session
 *
 * Управление сессиями, включая работу с flash-сообщениями и базовыми операциями.
 */
class Session
{
  public string $sessionFlashName = '__FlashBack';

  /** Переопределения cookie для текущего интерфейса; задаются до старта сессии. */
  protected array $cookieOptions = [];

  /** Устанавливает параметры cookie, сохраняя существующую сигнатуру start(). */
  public function setCookieOptions(array $options): void
  {
    $this->cookieOptions = $options;
  }

  /**
   * Конструктор класса.
   */
  public function __construct()
  {
  }

  /**
   * Инициализирует сессию с заданными параметрами.
   *
   * @param string|null $sessionName Имя сессии (по умолчанию null)
   */
  public function start(?string $sessionName = null): void
  {
    session_name($sessionName);

    if (isset($_COOKIE[session_name()]) && !preg_match('/^[a-zA-Z0-9,\-]{22,52}$/', $_COOKIE[session_name()])) {
      exit('Error: Invalid session ID!');
    }

    session_set_cookie_params(array_replace([
      'lifetime' => 0,
      'path'     => '/',
      'domain'   => '',
      'secure'   => true,
      'httponly' => true,
      'samesite' => 'Lax',
    ], $this->cookieOptions));

    // Начало сессии
    session_start();
  }


  /**
   * Возвращает хэшированный идентификатор текущей сессии.
   *
   * @return string Хэшированный идентификатор сессии
   */
  public function id(): string
  {
    return sha1(session_id());
  }

  /**
   * Регенерирует идентификатор сессии для предотвращения подделки.
   */
  public function regenerate(): void
  {
    session_regenerate_id(true);
  }

  /**
   * Проверяет, существует ли указанная переменная сессии.
   *
   * @param mixed $name Название переменной
   *
   * @return bool true, если переменная существует, иначе false
   */
  public function exists(string $name): bool
  {
    if (!empty($name)) {
      if (isset($_SESSION[$name])) {
        return true;
      }
    }

    return false;
  }

  /**
   * Устанавливает значение переменной сессии.
   *
   * @param string $name  Название переменной
   * @param mixed  $value Значение переменной
   */
  public function set(string $name, mixed $value = null): void
  {
    if (!empty($name)) {
      $_SESSION[$name] = $value;
    }
  }

  /**
   * Получает значение переменной сессии.
   *
   * @param string $name Название переменной
   *
   * @return mixed Значение переменной или null, если не существует
   */
  public function get(string $name): mixed
  {
    return $_SESSION[$name] ?? null;
  }

  /**
   * Получает значение переменной сессии и удаляет её.
   *
   * @param string $name Название переменной
   *
   * @return mixed Значение переменной или null, если не существует
   */
  public function getWithDelete(string $name): mixed
  {
    if ($return = self::get($name)) {
      self::delete($name);

      return $return;
    }

    return null;
  }

  /**
   * Удаляет переменную сессии.
   *
   * @param string $name Название переменной
   *
   * @return bool false
   */
  public function delete(string $name): bool
  {
    if (self::exists($name)) {
      unset($_SESSION[$name]);

      return true;
    }

    return false;
  }

  /**
   * Устанавливает flash-сообщение в сессии.
   *
   * @param ?string $value Текст сообщения
   */
  public function setFlash(?string $value): void
  {
    if (!empty($value)) {
      self::set($this->sessionFlashName, $value);
    }
  }

  /**
   * Получает и удаляет flash-сообщение из сессии.
   *
   * @return ?string Flash-сообщение
   */
  public function getFlash(): ?string
  {
    if (self::exists($this->sessionFlashName)) {
      ob_start();
      echo self::get($this->sessionFlashName);
      $content = ob_get_contents();
      ob_end_clean();

      self::delete($this->sessionFlashName);

      return $content;
    }

    return null;
  }

  /**
   * Проверяет, существует ли flash-сообщение.
   *
   * @return bool true, если сообщение существует, иначе false
   */
  public function flashExists(): bool
  {
    return self::exists($this->sessionFlashName);
  }

  /**
   * Уничтожает всю сессию.
   */
  public function destroy(): bool
  {
    foreach (array_keys($_SESSION) as $sessionName) {
      self::delete($sessionName);
    }

    return session_destroy();
  }

  /**
   * Возвращает все данные сессии.
   *
   * @return array Массив всех данных сессии
   */
  public function all(): array
  {
    return $_SESSION;
  }
}
