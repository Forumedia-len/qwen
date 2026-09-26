<?php

/**
 * Перечисление дней недели.
 *
 * @package AC\app\entities\enums
 */

namespace AC\app\entities\enums;

use AC\core\system\helpers\TranslateHelper;

/**
 * Дни недели с соответствующими числовыми значениями.
 * Понедельник = 0, Воскресенье = 6
 */
enum Weekday: int
{
  /** Понедельник */
  case Mo = 0;
  /** Вторник */
  case Tu = 1;
  /** Среда */
  case We = 2;
  /** Четверг */
  case Th = 3;
  /** Пятница */
  case Fr = 4;
  /** Суббота */
  case Sa = 5;
  /** Воскресенье */
  case Su = 6;
  
  /**
   * Возвращает текстовое представление дня недели.
   *
   * @param bool $short Флаг короткого формата
   * @return string Текстовое название дня недели
   */
  public function getLabel(bool $short = false): string
  {
    return TranslateHelper::translateWeekday($this->value, $short);
  }
  
  /**
   * Возвращает короткое текстовое представление дня недели.
   *
   * @return string Короткое название дня недели
   */
  public function getShortLabel(): string
  {
    return $this->getLabel(true);
  }
}
