<?php

namespace AC\app\config;

use AC\core\modules\areas\entities\dto\AreaTypeDto;
use AC\core\modules\mailing\config\LetterTemplatesConfig as LetterTemplatesConfigBase;
use AC\core\modules\mailing\entities\enums\ModeTemplate;
use AC\core\system\modules\modComm\helpers\ModCommHelper;

/**
 * Конфигурационный класс для управления шаблонами писем, связанных с бронированием площадок.
 *
 * Используется для формирования имен шаблонов в зависимости от типа, спорта и других параметров.
 */
class LetterTemplatesConfig extends LetterTemplatesConfigBase
{
  /**
   * Возвращает список доступных базовых имен шаблонов.
   *
   * @return array Массив строк с именами базовых шаблонов
   */
  public function getAvailableTemplateBaseNames(): array
  {
    return array_merge(parent::getAvailableTemplateBaseNames(), ['order_new']);
  }

  /**
   * Формирует полное имя шаблона письма на основе базового имени, типа и (опционально) ID спорта.
   *
   * @param string   $baseName Базовое имя шаблона
   * @param mixed    $type     Объект или значение, определяющее тип
   * @param int|null $sport_id ID спорта (если требуется)
   *
   * @return string Полное имя шаблона
   */
  public function getFullAliasForCourtLetterTemplates(string $baseName, $type, ?int $sport_id = null): string
  {
    return $baseName . '_' . $this->getTypeSportAlias($type, $sport_id);
  }

  /**
   * Формирует часть имени шаблона, зависящую от типа и (опционально) ID спорта.
   *
   * @param mixed    $type     Объект или значение, определяющее тип
   * @param int|null $sport_id ID спорта (если требуется)
   *
   * @return string Часть имени шаблона
   */
  public function getTypeSportAlias($type, ?int $sport_id = null): string
  {
    return $this->getTypeAlias($type)
      . ($this->useAdditionLetterTemplate() && $sport_id ? '_' . $sport_id : '');
  }

  /**
   * Получает алиас типа, который используется в формировании имени шаблона.
   *
   * @param mixed $type Объект или значение, определяющее тип
   *
   * @return string Алиас типа
   */
  public function getTypeAlias($type): string
  {
    return ($type->type_count_alias > 1 ? $type->type_alias_type_id : $type->alias);
  }

  /**
   * Получить список возможных алиасов шаблонов писем.
   *
   * @param string|null $baseAlias        Базовый алиас для фильтрации (если null, возвращаются все)
   * @param bool        $useSuffixDefault Использовать суффикс "по умолчанию" для спорта
   *
   * @return array Ассоциативный массив возможных алиасов с соответствующими заголовками
   */
  public function getPossibleAliasesTemplates(?string $baseAlias = null, bool $useSuffixDefault = false): array
  {
    static $sportsByType;
    if (empty($sportsByType)) {
      $sportsByType = ModCommHelper::get('areas', 'areas/relevantSportsByType', [], 'sportsByType', []);
    }
    $orderTemplates = [];
    $orderTemplates = ['all' => []];
    foreach ($this->getAvailableTemplateBaseNames() as $baseName) {
      $aliases = [];
      foreach ($sportsByType as $item) {
        $typeAlias = $item->type_alias . ($item->type_count_alias > 1 ? '_' . $item->type_id : '');
        if (!isset($aliases[$baseName . '_' . $typeAlias])) {
          $aliases[$baseName . '_' . $typeAlias] = ($item->type_count > 1 ? ' ' . $item->type_title : '')
            . ($item->sport_count > 1 && $useSuffixDefault ? ' <b>(' . lang('Default') . ')</b>' : '');
        }
        if ($item->sport_count > 1) {
          $aliases[$baseName . '_' . $typeAlias . '_' . $item->sport]                      = ' ' . $item->title;
          $orderTemplates['onlySports'][$baseName . '_' . $typeAlias . '_' . $item->sport] = [
            'title'    => $item->title,
            'type_id'  => $item->type_id,
            'sport_id' => $item->sport_id,
          ];
        }
      }
      $orderTemplates[$baseName] = $aliases;
      $orderTemplates['all']     = array_merge($orderTemplates['all'], $aliases);
    }

    return $orderTemplates[$baseAlias ?? 'all'] ?? [];
  }

  /**
   * Возвращает базовый алиас для указанного алиаса, добавляя исключение 'order_new_no_code'.
   *
   * @param string $alias      Исходный алиас
   * @param array  $exceptions Массив алиасов, которые следует исключить из поиска
   *
   * @return string Базовый алиас
   */
  public function getBaseAlias(string $alias, array $exceptions = []): string
  {
    $exceptions[] = 'order_new_no_code';

    return parent::getBaseAlias($alias, $exceptions);
  }

  /**
   * Дополнительная обработка алиаса: удаляет суффикс '_mc_arena' если он присутствует.
   *
   * @param string $alias Алиас для обработки
   *
   * @return string Обработанный алиас
   */
  protected function additionalAliasProcessing(string $alias): string
  {
    if (str_ends_with($alias, '_mc_arena')) {
      $alias = str_replace('_mc_arena', '', $alias);
    }

    return parent::additionalAliasProcessing($alias);
  }

  /**
   * Дополнительный поиск приоритетных алиасов, добавляя алиасы типа и текущего типа.
   *
   * @param string $alias      Исходный алиас
   * @param array &$priorities Ссылка на массив приоритетов для дополнения
   *
   * @return string Базовый алиас
   */
  protected function additionalSearchForPriorityAliases(string $alias, array &$priorities): string
  {
    $baseAlias = parent::additionalSearchForPriorityAliases($alias, $priorities);
    $suffix    = str_replace($baseAlias . '_', '', $alias);
    [$suffixAlias, $suffixType, $suffixSport] = explode("_", $suffix);
    $type = null;
    if (in_array($baseAlias, $this->getAvailableTemplateBaseNames())) {
      /** @var AreaTypeDto $type */
      foreach (ModCommHelper::get('areas', 'areasType/selectActiveTypes', [], 'data') as $type) {
        if (str_starts_with($suffix, $type->alias) && ($type->type_count_alias == 1 || str_starts_with($suffix, $type->current_alias))) {
          break;
        }
      }
    }
    if ($type) {
      $priorities[] = $baseAlias . '_' . $type->current_alias;
      $priorities[] = $baseAlias . '_' . $type->alias;
      $priorities[] = $baseAlias . '_' . $suffixAlias . '_' . $suffixSport ?? $suffixType;
    }

    return $baseAlias;
  }


  /**
   * Возвращает алиас для отключения отправки письма клиенту при бронировании.
   * Если алиас начинается с 'mc_arena', удаляет префикс 'mc_'.
   *
   * @param string $type_alias Алиас типа
   *
   * @return string Алиас отключения отправки письма
   */
  public function getAliasDisableSendMailClientReservation(string $type_alias): string
  {
    if (str_starts_with($type_alias, 'mc_arena')) {
      $type_alias = str_replace('mc_', '', $type_alias);
    }

    return parent::getAliasDisableSendMailClientReservation($type_alias);
  }

  /**
   * Проверяет, должна ли отправляться почта для указанного типа площадки.
   *
   * @param int $typeId ID типа площадки
   *
   * @return bool true, если отправка разрешена, false если запрещена
   */
  public function checkSendMailByType(int $typeId): bool
  {
    $type         = ModCommHelper::get('areas', 'areasType/getAreaType', ['type_id' => $typeId, 'asDto' => true], 'data');
    $currentAlias = $this->getValueDisableSendMailClientReservationByAlias($type->getCurrentAlias());

    return $currentAlias
      || ($currentAlias === null && $this->getValueDisableSendMailClientReservationByAlias($type->getAlias()));
  }

  /**
   * Возвращает заголовок шаблона письма с дополнительным суффиксом при необходимости.
   *
   * @param string $alias    Алиас шаблона
   * @param int    $typeMode Режим типа (пользовательский или административный)
   * @param string $default  Значение по умолчанию, если перевод не найден
   * @param bool   $addSuffix
   *
   * @return string Заголовок шаблона письма с возможным суффиксом
   */
  public function getTitleTemplate(string $alias, int $typeMode, string $default = '', bool $addSuffix = true): string
  {
    $userMode           = ModeTemplate::checkCase($typeMode, ModeTemplate::USER);
    $suffixTitleByAlias = $this->getPossibleAliasesTemplates(null, $userMode);

    return parent::getTitleTemplate($alias, $typeMode, $default) . ($addSuffix ? ($suffixTitleByAlias[$alias] ?? '') : '');
  }

  public function normalizeAlias(string $alias): string
  {
    $alias = parent::normalizeAlias($alias);
    if (MC_ARENA && !str_contains($alias, 'mc_arena')) {
      $alias .= '_mc_arena';
    }

    return $alias;
  }
}
