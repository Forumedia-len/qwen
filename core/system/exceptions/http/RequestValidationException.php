<?php

namespace AC\core\system\exceptions\http;

/**
 * Ошибка проверки пользовательского параметра без сохранения исходного значения.
 */
class RequestValidationException extends HTTPException
{
  /**
   * @param string $alias Имя параметра, заданное вызывающим кодом
   * @param string $source Источник параметра
   * @param array $errors Сообщения валидаторов
   */
  public function __construct(
    private string $alias,
    private string $source,
    private array $errors = []
  ) {
    parent::__construct('Invalid request parameter.', 400);
  }

  /** Возвращает имя параметра. */
  public function getAlias(): string
  {
    return $this->alias;
  }

  /** Возвращает источник параметра. */
  public function getSource(): string
  {
    return $this->source;
  }

  /** Возвращает ошибки проверки для обработки вызывающим кодом. */
  public function getErrors(): array
  {
    return $this->errors;
  }
}
