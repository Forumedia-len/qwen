<?php

namespace AC\core\system\entities\dto;

/**
 * Интерфейс DTO (Data Transfer Object), определяющий общие методы для преобразования данных.
 */
interface DtoInterface
{
  /**
   * Создаёт объект DTO из массива данных.
   *
   * @param array $data Массив данных для инициализации свойств
   * @return static
   */
  public static function fromArray(array $data): self;
  
  /**
   * Преобразует объект DTO в ассоциативный массив.
   *
   * @return array
   */
  public function toArray(): array;
}