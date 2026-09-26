<?php

namespace AC\app\config;

use AC\core\modules\areas\entities\dto\AreaTypeDto;
use AC\core\modules\config\contexts\RestrictionContext;
use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\modules\config\scoped\ConfigTypeKeyBuilder;
use AC\core\modules\config\scoped\ScopedConfigResult;
use AC\core\system\config\BaseConfig;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use Service;

/**
 * Конфигурация ограничений клиентов через ScopedConfigGateway (prefix restriction).
 *
 * @todo фаза 2 — таблица client_restriction
 */
class ClientRestrictionConfig extends BaseConfig
{
  public const RESERVATION_LIMIT   = 'reservation_limit';
  public const POSSIBILITY_BOOKING = 'possibility_booking';

  public const BOOKING_ALLOWED = 1;
  public const BOOKING_STOPPED = 0;

  /** @var list<AreaTypeDto>|null */
  private static ?array $activeAreaTypesForTest = null;

  /**
   * @var array<string, array{
   *   courtAliases: list<string>,
   *   clubMarks: list<string>,
   *   dbAlias: string,
   *   typeValue?: string,
   *   adminRequiresDbRow?: bool,
   *   adminInput: 'limit_select'|'booking_checkbox',
   *   adminLangKey: string,
   *   defaultValue?: int,
   * }>
   */
  private array $rules = [
    self::RESERVATION_LIMIT => [
      'courtAliases'       => ['close'],
      'clubMarks'          => ['club_rate2'],
      'dbAlias'            => 'reservation_limit',
      'typeValue'          => 'int',
      'adminRequiresDbRow' => true,
      'adminInput'         => 'limit_select',
      'adminLangKey'       => 'Restriction Hall V2',
    ],
    self::POSSIBILITY_BOOKING => [
      'courtAliases'       => ['close'],
      'clubMarks'          => ['no_club_rate'],
      'dbAlias'            => 'possibility_booking',
      'typeValue'          => 'int',
      'adminRequiresDbRow' => false,
      'adminInput'         => 'booking_checkbox',
      'adminLangKey'       => 'Restriction Hall Noclub',
      'defaultValue'       => self::BOOKING_ALLOWED,
    ],
  ];


  /**
   * Контекст из модели бронирования: type_id, club_state → clubMark, touch → device.
   *
   * @param object      $model OrdersModelReservation или совместимый объект бронирования.
   * @param string|null $device pc, touch; если null — из $model->touch.
   */
  public function fromReservationModel(object $model, ?string $device = null): RestrictionContext
  {
    $clubStateId = null;
    $clubMark      = null;

    if (isset($model->current_client)) {
      $clubStateId = isset($model->current_client->club_state)
        ? (int)$model->current_client->club_state
        : null;
      if ($clubStateId > 0) {
        $clubMark = (string)(ConfigClubStateModel::getItemById($clubStateId, 'mark') ?? '');
      }
    }

    $courtTypeAlias = null;
    if (isset($model->areas_types->alias)) {
      $courtTypeAlias = (string)$model->areas_types->alias;
    }

    if ($device === null && isset($model->touch)) {
      $device = $model->touch ? 'touch' : 'pc';
    }

    return new RestrictionContext(
      typeId: isset($model->type_id) ? (int)$model->type_id : null,
      sportId: isset($model->sport_id) ? (int)$model->sport_id : null,
      areaId: isset($model->area_id) ? (int)$model->area_id : null,
      clubStateId: $clubStateId,
      clubMark: $clubMark !== '' ? $clubMark : null,
      device: $device !== '' ? $device : null,
      courtTypeAlias: $courtTypeAlias !== '' ? $courtTypeAlias : null,
    );
  }

  /** Есть ли правило restriction для данного типа и контекста (корт + clubMark). */
  public function hasRule(string $restrictionType, RestrictionContext $ctx): bool
  {
    return isset($this->rules[$restrictionType]) && $this->matchesRuleScope($restrictionType, $ctx);
  }

  /**
   * Применимо ли правило: scope совпадает и (для limit) есть строка в БД.
   *
   * @param string                    $restrictionType
   * @param RestrictionContext|string $ctxOrCourt
   * @param string|null               $clubState
   *
   * @return bool
   */
  public function useRestriction(string $restrictionType, RestrictionContext|string $ctxOrCourt, ?string $clubState = null): bool
  {
    $ctx = $this->toContext($ctxOrCourt, $clubState);
    if (!$this->hasRule($restrictionType, $ctx)) {
      return false;
    }

    $rule = $this->rules[$restrictionType];
    if (!($rule['adminRequiresDbRow'] ?? false)) {
      return true;
    }

    return $this->resolveFromGateway($restrictionType, $ctx)->found;
  }

  /**
   * Значение restriction: из БД или defaultValue правила (если adminRequiresDbRow=false).
   * null — правило не применимо, нет строки в БД и нет defaultValue.
   *
   * @param string                    $restrictionType
   * @param RestrictionContext|string $ctxOrCourt
   * @param string|null               $clubState
   *
   * @return mixed
   */
  public function getRestrictionValue(
    string $restrictionType,
    RestrictionContext|string $ctxOrCourt,
    ?string $clubState = null,
  ): mixed {
    $ctx = $this->toContext($ctxOrCourt, $clubState);
    if (!$this->hasRule($restrictionType, $ctx)) {
      return null;
    }

    $rule   = $this->rules[$restrictionType];
    $result = $this->resolveFromGateway($restrictionType, $ctx);
    if ($result->found) {
      return $this->castValue($result->value, $rule['typeValue'] ?? null);
    }

    return $this->resolveDefaultValue($restrictionType);
  }

  /**
   * Лимит бронирований из scoped config; null — правило не задано (L5 подставит TableRules/default).
   *
   * @param RestrictionContext|string $ctxOrCourt
   * @param string|int|null           $clubOrDefault clubMark (legacy) или defaultLimit (новый API)
   * @param int|null                  $defaultLimit
   *
   * @return int|null
   */
  public function resolveReservationLimit(
    RestrictionContext|string $ctxOrCourt,
    string|int|null $clubOrDefault = null,
    ?int $defaultLimit = null,
  ): ?int {
    if ($ctxOrCourt instanceof RestrictionContext) {
      return $this->resolveReservationLimitForContext($ctxOrCourt, is_int($clubOrDefault) ? $clubOrDefault : $defaultLimit);
    }

    return $this->resolveReservationLimitForContext(
      RestrictionContext::fromCourtAliasAndClub($ctxOrCourt, (string)($clubOrDefault ?? '')),
      $defaultLimit,
    );
  }

  /**
   * Разрешено ли бронирование (possibility_booking); без значения — true.
   *
   * @param RestrictionContext|string $ctxOrCourt
   * @param string|null               $clubState
   *
   * @return bool
   */
  public function isBookingAllowed(RestrictionContext|string $ctxOrCourt, ?string $clubState = null): bool
  {
    $ctx = $this->toContext($ctxOrCourt, $clubState, );
    if (!$this->hasRule(self::POSSIBILITY_BOOKING, $ctx)) {
      return true;
    }

    $value = $this->getRestrictionValue(self::POSSIBILITY_BOOKING, $ctx);
    if ($value === null) {
      return true;
    }

    return (int)$value === self::BOOKING_ALLOWED;
  }

  /** Обратная isBookingAllowed — для проверок в L5 (OrdersModelReservation). */
  public function isBookingDenied(RestrictionContext $ctx): bool
  {
    return !$this->isBookingAllowed($ctx);
  }

  /** Собранный config.type для записи/чтения (restriction|close, restriction|close_10, …). */
  public function getDbTypeKey(RestrictionContext $ctx, string $restrictionType): string
  {
    $dbAlias = $this->rules[$restrictionType]['dbAlias'] ?? '';

    return (new ConfigTypeKeyBuilder())->build($ctx->toQuery($dbAlias));
  }

  /** Имя alias в config: dbAlias__clubMark (напр. reservation_limit__club_rate2). */
  public function getDbAlias(string $restrictionType, string $clubMark): string
  {
    $dbAlias = $this->rules[$restrictionType]['dbAlias'] ?? '';

    return $this->composeDbAlias($dbAlias, $clubMark);
  }

  /**
   * @return list<string> config.type для админки (ещё не существующие в БД).
   */
  public function getAdminConfigTypes(): array
  {
    $types = [];
    foreach ($this->buildAdminFields() as $field) {
      $types[$field['typeKey']] = true;
    }

    return array_keys($types);
  }

  /**
   * Поля restriction для админки config (read/write).
   *
   * @return list<array{
   *   restrictionType: string,
   *   ctx: RestrictionContext,
   *   typeKey: string,
   *   dbAlias: string,
   *   value: mixed,
   *   input: 'limit_select'|'booking_checkbox',
   *   label: string,
   * }>
   */
  public function buildAdminFields(): array
  {
    $fields = [];
    foreach ($this->rules as $restrictionType => $rule) {
      foreach ($rule['courtAliases'] as $courtAlias) {
        foreach ($rule['clubMarks'] as $clubMark) {
          $areaTypes = $this->resolveAreaTypesForCourtAlias($courtAlias);

          foreach ($areaTypes as $areaType) {
            $typeId = $areaType instanceof AreaTypeDto
              ? (int)($areaType->type_id ?? $areaType->getId() ?? 0)
              : null;
            if ($typeId !== null && $typeId <= 0) {
              $typeId = null;
            }

            $ctx = RestrictionContext::fromCourtAliasAndClub($courtAlias, $clubMark, $typeId);
            if (!$this->useRestriction($restrictionType, $ctx)) {
              continue;
            }

            $typeKey = $this->getDbTypeKey($ctx, $restrictionType);
            $dbAlias = $this->getDbAlias($restrictionType, $clubMark);
            $typeTitle = $this->resolveAreaTypeTitle($areaType, $courtAlias);

            $fields[] = [
              'restrictionType' => $restrictionType,
              'ctx'             => $ctx,
              'typeKey'         => $typeKey,
              'dbAlias'         => $dbAlias,
              'value'           => $this->getRestrictionValue($restrictionType, $ctx),
              'input'           => $rule['adminInput'],
              'label'           => $this->buildAdminFieldLabel($rule['adminLangKey'], $typeTitle),
            ];
          }
        }
      }
    }

    return $fields;
  }

  private function buildAdminFieldLabel(string $langKey, string $typeTitle): string
  {
    $base = function_exists('lang') ? (string)lang($langKey, 'config') : $langKey;
    $typeTitle = trim($typeTitle);

    return $typeTitle !== '' ? $base . ' (' . $typeTitle . ')' : $base;
  }

  /** @internal Для unit-тестов. */
  public static function seedActiveAreaTypesForTest(array $types): void
  {
    self::$activeAreaTypesForTest = $types;
  }

  /** @internal Для unit-тестов. */
  public static function resetActiveAreaTypesForTest(): void
  {
    self::$activeAreaTypesForTest = null;
  }

  /**
   * Приоритет лимита для L5: scoped → TableRules → default.
   */
  public function resolveEffectiveReservationLimit(
    RestrictionContext $ctx,
    ?int $ruleLimit = null,
    ?int $defaultLimit = null,
  ): int {
    $configLimit = $this->resolveReservationLimit($ctx);

    return $configLimit ?? $ruleLimit ?? $defaultLimit ?? 0;
  }

  private function resolveDefaultValue(string $restrictionType): mixed
  {
    $rule = $this->rules[$restrictionType] ?? [];
    if (($rule['adminRequiresDbRow'] ?? false) || !array_key_exists('defaultValue', $rule)) {
      return null;
    }

    return $this->castValue($rule['defaultValue'], $rule['typeValue'] ?? null);
  }

  private function resolveReservationLimitForContext(RestrictionContext $ctx, ?int $defaultLimit = null): ?int
  {
    if (!$this->hasRule(self::RESERVATION_LIMIT, $ctx)) {
      return $defaultLimit;
    }

    $value = $this->getRestrictionValue(self::RESERVATION_LIMIT, $ctx);
    if ($value === null) {
      return null;
    }

    return (int)$value;
  }

  private function resolveFromGateway(string $restrictionType, RestrictionContext $ctx): ScopedConfigResult
  {
    if (!class_exists('Service', false)) {
      return ScopedConfigResult::notFound();
    }

    $dbAlias = $this->rules[$restrictionType]['dbAlias'] ?? '';

    return Service::scopedConfig()->resolve($ctx->toQuery($dbAlias));
  }

  private function toContext(RestrictionContext|string $ctxOrCourt, ?string $clubState): RestrictionContext
  {
    if ($ctxOrCourt instanceof RestrictionContext) {
      return $ctxOrCourt;
    }

    return RestrictionContext::fromCourtAliasAndClub($ctxOrCourt, (string)($clubState ?? ''));
  }

  private function matchesRuleScope(string $restrictionType, RestrictionContext $ctx): bool
  {
    $rule = $this->rules[$restrictionType];
    $courtAlias = trim((string)($ctx->courtTypeAlias ?? $this->resolveCourtAlias($ctx->typeId) ?? ''));
    if ($courtAlias === '' || !in_array($courtAlias, $rule['courtAliases'], true)) {
      return false;
    }

    $clubMark = trim((string)($ctx->clubMark ?? ''));
    if ($clubMark === '' || !in_array($clubMark, $rule['clubMarks'], true)) {
      return false;
    }

    return true;
  }

  private function resolveCourtAlias(?int $typeId): ?string
  {
    if ($typeId === null || $typeId <= 0) {
      return null;
    }

    $alias = module('areas')->useModel()?->getEngine()?->getAliasType($typeId);

    return !empty($alias) ? (string)$alias : null;
  }

  private function resolveAreaTypesForCourtAlias(string $courtAlias): array
  {
    $courtAlias = trim($courtAlias);
    if ($courtAlias === '') {
      return [];
    }

    $types = [];
    foreach ($this->loadActiveAreaTypes() as $type) {
      if (!$type instanceof AreaTypeDto) {
        continue;
      }

      if ($type->getAlias() !== $courtAlias && $type->getCurrentAlias() !== $courtAlias) {
        continue;
      }

      $typeId = (int)($type->type_id ?? $type->getId() ?? 0);
      if ($typeId > 0) {
        $types[$typeId] = $type;
      }
    }

    ksort($types);

    return array_values($types);
  }

  private function resolveAreaTypeTitle(AreaTypeDto|null $areaType, string $courtAlias): string
  {
    if ($areaType instanceof AreaTypeDto) {
      $title = trim((string)($areaType->title ?? ''));
      if ($title !== '') {
        return $title;
      }

      $currentAlias = trim($areaType->getCurrentAlias());
      if ($currentAlias !== '') {
        return $currentAlias;
      }
    }

    return trim($courtAlias);
  }

  private function loadActiveAreaTypes(): array
  {
    if (self::$activeAreaTypesForTest !== null) {
      return self::$activeAreaTypesForTest;
    }

    if (!function_exists('config') || !class_exists('Service', false)) {
      return [];
    }

    $types = ModCommHelper::get('areas', 'areasType/selectActiveTypes', [], 'data');

    return is_array($types) ? $types : [];
  }

  private function composeDbAlias(string $dbAlias, string $clubMark): string
  {
    $clubMark = trim($clubMark);

    return $clubMark !== '' ? $dbAlias . '__' . $clubMark : $dbAlias;
  }

  private function castValue(mixed $value, ?string $typeValue): mixed
  {
    if ($value === null) {
      return null;
    }

    return match ($typeValue) {
      'int' => $this->castIntValue($value),
      default => $value,
    };
  }

  private function castIntValue(mixed $value): int
  {
    return match ($value) {
      'start' => self::BOOKING_ALLOWED,
      'stop'  => self::BOOKING_STOPPED,
      default => (int)$value,
    };
  }
}
