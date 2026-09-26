<?php

namespace AC\core\modules\mailing\config;

use AC\core\system\config\BaseConfig;
use Service;

/**
 * Конфигурационный класс для управления шаблонами писем, связанных с бронированием площадок.
 *
 * Используется для формирования имен шаблонов в зависимости от типа, спорта и других параметров.
 */
class LetterTemplatesConfig extends BaseConfig
{
  /**
   * Возвращает список доступных базовых имен шаблонов.
   *
   * @return array Массив строк с именами базовых шаблонов
   */
  public function getAvailableTemplateBaseNames(): array
  {
    return [];
  }
  
  /**
     * Возвращает базовый алиас для указанного алиаса, исключая указанные исключения.
     *
     * @param string $alias Исходный алиас
     * @param array $exceptions Массив алиасов, которые следует исключить из поиска
     * @return string Базовый алиас
     */
    public function getBaseAlias(string $alias, array $exceptions = []): string
  {
    if ($this->checkCorrectAliasByInterval($alias)) {
      $exceptions[] = $alias;
    }
    foreach ($this->getAvailableTemplateBaseNames() as $baseAlias) {
      if (!in_array($alias, $exceptions) && str_starts_with($alias, $baseAlias)) {
        return $baseAlias;
      }
    }
    
    return $this->additionalAliasProcessing($alias);
  }
  
  /**
     * Дополнительная обработка алиаса, если базовый алиас не найден.
     *
     * @param string $alias Алиас для обработки
     * @return string Обработанный алиас (по умолчанию возвращает исходный)
     */
    protected function additionalAliasProcessing(string $alias): string
  {
    return $alias;
  }
  
  /**
     * Возвращает приоритетные алиасы для указанного алиаса, включая базовый.
     *
     * @param string $alias Исходный алиас
     * @return array Массив приоритетных алиасов, отсортированный по убыванию
     */
    public function getPrioritiesAliases(string $alias): array
  {
    $priorities = [];
    $this->additionalSearchForPriorityAliases($alias, $priorities);

    rsort($priorities);
    array_unshift($priorities, $alias);

    return array_unique($priorities);
  }

  /**
   * Возвращает первый доступный алиас согласно порядку приоритетов.
   *
   * @param string $alias            Исходный алиас
   * @param array  $availableAliases Доступные алиасы
   */
  public function findFirstAvailablePriorityAlias(string $alias, array $availableAliases): ?string
  {
    foreach ($this->getPrioritiesAliases($alias) as $priorityAlias) {
      if (in_array($priorityAlias, $availableAliases, true)) {
        return $priorityAlias;
      }
    }

    return null;
  }
  
  /**
     * Дополнительный поиск приоритетных алиасов, добавляя базовый алиас в список.
     *
     * @param string $alias Исходный алиас
     * @param array &$priorities Ссылка на массив приоритетов для дополнения
     * @return string Базовый алиас
     */
    protected function additionalSearchForPriorityAliases(string $alias, array &$priorities): string
  {
    $baseAlias    = $this->getBaseAlias($alias);
    $priorities[] = $baseAlias;
    
    return $baseAlias;
  }
  
  /**
   * Проверяет, является ли указанный алиас корректным для замены,
   * по временному интервалу.
   * @param string $alias
   * @param bool   $checkAliasByInterval
   * @return bool
   */
  public function checkCorrectAliasByInterval(string $alias, bool $checkAliasByInterval = true): bool
  {
    return defined('LETTER_CORRECT_ALIAS_INTERVAL')
      && defined('LETTER_CORRECT_ALIAS_REPLACE')
      && LETTER_CORRECT_ALIAS_INTERVAL
      && LETTER_CORRECT_ALIAS_REPLACE
      && (!$checkAliasByInterval || $alias === (string)LETTER_CORRECT_ALIAS_INTERVAL);
  }
  
  /**
   * Проверяет, включена ли дополнительная логика для шаблонов писем.
   *
   * @return bool true, если включено, иначе false
   */
  public function useAdditionLetterTemplate(): bool
  {
    return defined('USE_ADDITION_LETTER_TEMPLATE_FOR_COURT') && USE_ADDITION_LETTER_TEMPLATE_FOR_COURT;
  }
  
  /**
   * Проверяет, включена ли дополнительная логика для административных шаблонов писем.
   *
   * В данном случае всегда возвращает false.
   *
   * @return bool false
   */
  public function useAdditionAdminLetterTemplates(): bool
  {
    return false;
  }
  
  /**
   * Возвращает алиас для отключения отправки письма клиенту при бронировании.
   *
   * @param string $type_alias Алиас типа
   * @return string Алиас отключения отправки письма
   */
  public function getAliasDisableSendMailClientReservation(string $type_alias): string
  {
    return 'disable_send_mail_client_reservation_' . $type_alias;
  }
  
  /**
   * Проверяет, отключена ли отправка email клиенту по резервации для указанного псевдонима.
   * disable_send_mail_client_reservation_{$type_alias} - ключ настройки
   * @param string $type_alias Тип псевдонима, для которого проверяется настройка
   * @return ?int 1 или 0, если настройка найдена, иначе null
   */
  public function getValueDisableSendMailClientReservationByAlias(string $type_alias): ?int
  {
    return Service::configDB('email', $this->getAliasDisableSendMailClientReservation($type_alias));
  }
  
  /**
   * Возвращает заголовок шаблона письма по алиасу.
   *
   * @param string $alias    Алиас шаблона
   * @param int    $typeMode Режим типа (не используется в текущей реализации)
   * @param string $default  Значение по умолчанию, если перевод не найден
   * @param bool   $addSuffix
   * @return string Заголовок шаблона письма
   */
    public function getTitleTemplate(string $alias, int $typeMode, string $default = '', bool $addSuffix = true): string
  {
    $baseAlias = $this->getBaseAlias($alias);

    return lang('title_' . $baseAlias, 'mailing.letter_templates', [], $default);
  }
  
  public function normalizeAlias(string $alias):string
  {
    return $alias;
  }
}
