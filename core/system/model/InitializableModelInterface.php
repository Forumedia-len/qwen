<?php

namespace AC\core\system\model;

/** Модель, загружающая данные после установки контекста устройства. */
interface InitializableModelInterface
{
  /** Инициализировать модель после установки контекста, повторный вызов не меняет данные. */
  public function initialize(): void;
}
