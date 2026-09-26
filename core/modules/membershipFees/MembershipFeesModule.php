<?php

namespace AC\core\modules\membershipFees;

use AC\core\system\module\BaseModule;

/**
 * Модуль членских взносов и административного раздела Vereinsverwaltung.
 */
class MembershipFeesModule extends BaseModule
{
  public const MANAGEMENT_QUERY_KEY = 'vereinsverwaltung';

  private const MANAGEMENT_PAGE_KEYS = [
    'setup'                => 'membership_fees_management_setup',
    'groups'               => 'membership_fees_management_groups',
    'anniversary'          => 'membership_fees_management_anniversary',
    'membersByYearOfBirth' => 'membership_fees_management_members_by_year_of_birth',
    'membership'           => 'membership_fees_management_membership',
    'accounts'             => 'membership_fees_management_accounts',
    'export'               => 'membership_fees_management_export',
  ];

  /**
   * Проверить, открыт ли существующий контроллер из раздела Vereinsverwaltung.
   */
  public static function isManagementRequest(): bool
  {
    return (int)\Service::request()->_(self::MANAGEMENT_QUERY_KEY, 0) === 1;
  }

  /**
   * Получить ключ страницы нового раздела по разрешённому имени подраздела.
   */
  public static function getManagementPageKey(string $section): ?string
  {
    return self::MANAGEMENT_PAGE_KEYS[$section] ?? null;
  }

  /**
   * Добавить признак нового раздела к существующему относительному адресу.
   */
  public static function asManagementHref(string $href): string
  {
    $separator = str_contains($href, '?') ? '&' : '?';

    return $href . $separator . self::MANAGEMENT_QUERY_KEY . '=1';
  }

  /**
   * Добавить сегмент пути перед query-параметрами адреса.
   */
  public static function appendPath(string $href, string $path): string
  {
    [$baseHref, $query] = array_pad(explode('?', $href, 2), 2, null);
    $result = rtrim($baseHref, '/') . '/' . ltrim($path, '/');

    return $query === null ? $result : $result . '?' . $query;
  }
}
