<?php

namespace AC\core\modules\reservations\config;

use AC\core\modules\areas\entities\dto\AreaTypeDto;
use AC\core\system\config\BaseConfig;
use AC\core\system\controller\Auth;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use Service;

class ReservationsConfig extends BaseConfig
{
  public function getConsecutiveBookings($type, $sport)
  {
    return false;
  }

  /**
   * Использовать опции и спец цены для конкретного клиента и на конкретный тип корта
   * глобальная проверка для всех ценовых опций
   * есть две константы, не понятно на каких площадках используются и работают.
   * Будем провеять существование всех констант и если какая, то используется и включена
   * считаем что все работает
   *
   * @param bool $openType
   *
   * @return bool
   */
  public function useOptionsPrice(bool $openType = false): bool
  {
    return $this->useOptionsPriceForLeftClient() && (!$openType || $this->useOptionsPriceForOpenCourt());
  }

  public function useOptionsPriceForLeftClient(): bool
  {
    /** @var Auth $auth */
    $auth = Service::auth();

    return $auth->checkAuthorization() && (!$auth->isBarClient()
        || (defined('USE_OPTION_AND_SPEC_PRICE_FOR_LEFT_PLAYER') && USE_OPTION_AND_SPEC_PRICE_FOR_LEFT_PLAYER));
  }

  public function useOptionsPriceForOpenCourt(): bool
  {
    //TODO: отключил принудительно работу опций на открытых кортах - нужно разобраться где они используются и должны ли вообще,
    //  разобраться формированием цены за опции на открытых кортах
    // отображается более менее - посмотреть как считает цену!!!!!!!!!!
    return false && (defined('USE_OPTION_AND_SPEC_PRICE_ON_OPEN_COURT') && USE_OPTION_AND_SPEC_PRICE_ON_OPEN_COURT)
      || (defined('SHOW_OPTION_AND_SPEC_PRICE_ON_OPEN') && SHOW_OPTION_AND_SPEC_PRICE_ON_OPEN);
  }

  /**
   * Проверяет, можно ли использовать опции для конкретного типа корта и клиента
   * так же проверяет, возможно ли использовать ценовые опции глобально
   *  Пока заглушки на глобальные настройки
   *
   * @param bool $openType
   *
   * @return bool
   */
  public function useStock(bool $openType = false): bool
  {
    return $this->useOptionsPrice($openType);
  }

  /**
   *  Проверяет, можно ли использовать спец цены для конкретного типа корта и клиента
   *  так же проверяет, возможно ли использовать ценовые опции глобально.
   *  Пока заглушки на глобальные настройки
   *
   * @param bool $openType
   *
   * @return bool
   */
  public function useSpecPrice(bool $openType = false): bool
  {
    return $this->useOptionsPrice($openType);
  }

  /**
   * Тип площадки с таким id есть в справочнике (модуль areas).
   */
  public function areaTypeExists(int $typeId): bool
  {
    return $this->tryGetAreaTypeDto($typeId) !== null;
  }

  /**
   * @return AreaTypeDto|null null если type_id невалиден или тип не найден
   */
  public function getAreaTypeDto(int $typeId): ?AreaTypeDto
  {
    return $this->tryGetAreaTypeDto($typeId);
  }

  private function tryGetAreaTypeDto(int $typeId): ?AreaTypeDto
  {
    if ($typeId <= 0) {
      return null;
    }
    static $types;
    if(empty($types[$typeId])) {
      /** @var AreaTypeDto|null $type */
      $type = ModCommHelper::get('areas', 'areasType/getAreaType', ['type_id' => $typeId, 'asDto' => true], 'data');
      $types[$typeId] = $typeId;
    } else {
      $type = $types[$typeId];
    }

    return $type instanceof AreaTypeDto ? $type : null;
  }

  public function isOpenType(int $typeId, string $openTypeAlias = 'open'): bool
  {
    $type = $this->tryGetAreaTypeDto($typeId);

    return $type !== null && $type->alias === $openTypeAlias;
  }

  public function isCloseType(int $typeId, string $closeTypeAlias = 'close'): bool
  {
    $type = $this->tryGetAreaTypeDto($typeId);

    return $type !== null && $type->alias === $closeTypeAlias;
  }

  public function isMcArenaType(int $typeId, string $mcArenaTypeAlias = 'mc_arena'): bool
  {
    $type = $this->tryGetAreaTypeDto($typeId);
    if ($type === null) {
      return false;
    }
    if ($type->alias === $mcArenaTypeAlias) {
      return true;
    }

    return str_ends_with($type->alias, '_' . $mcArenaTypeAlias);
  }

  public function isOpenCourtForPrices($aliasType, ?int $typeId = null, ?int $sportId = null, ?int $areaId = null): bool
  {
    return $aliasType === 'open'
      && !config('OpenType')->checkOpenTypeAsClose($typeId, $sportId, $areaId);
  }

  public function isCloseCourtForPrices($aliasType, ?int $typeId = null, ?int $sportId = null, ?int $areaId = null): bool
  {
    return $aliasType === 'close'
      || ($aliasType === 'open' && config('OpenType')->checkOpenTypeAsClose($typeId, $sportId, $areaId));
  }
}