<?php

declare(strict_types=1);

namespace AC\app\config;

/**
 * Возможности общего каталога тестов, поддерживаемые текущей версией проекта.
 *
 * Здесь указываются только включённые возможности. Все остальные возможности
 * получают false из capabilities.php тестового репозитория.
 */
final class TestCapabilitiesConfig
{
  private const capabilities = [
    'core.full-bootstrap'                   => true,
    'core.request-validation'               => true,
    'core.widget-device'                    => true,
    'core.widget-toggle'                    => true,
    'core.widget-theme'                     => true,
    'core.widget-embed-theme'               => true,
    'core.widget-theme-attributes-only'     => true,
    'core.template-resolver'                => true,
    'core.http-error-layouts'               => true,
    'helpers.html-shielding'                => true,
    'reservations.date-context'             => true,
    'reservations.deferred-initialization'  => true,
    'reservations.date-error-display'       => true,
    'reservations.validation-error-display' => true,
    'reservations.site-error-calendar'      => true,

    'helpers.calendar'                      => true,
    'helpers.colors'                        => true,
    'helpers.template-theme'                => true,
    'helpers.assets'                        => true,
    'helpers.date'                          => true,
    'helpers.scoped-constant'               => true,

    'accounts.online-payment-invoice-csv'   => true,
    'accounts.online-payment-invoice-csv-gateways' => true,

    'reservations.order-validation'         => true,
    'reservations.double-player-pricing'    => true,
    'reservations.double-other-players'     => true,
    'reservations.archive-decoder'          => true,
    'reservations.areas-page-resolver'      => true,
    'reservations.double-game-config'       => true,
    'reservations.open-type-config'         => true,

    'config.scoped'                         => true,
    'config.scoped-db-selector'             => true,
    'config.client-restriction'             => true,

    'areas.archive-filter'                  => true,
    'reports.reservations-table'            => true,
    'area-prices.grouping'                  => true,
    'holidays'                              => true,
    'tickets.block-intersection'            => true,
  ];

  /**
   * Проверить поддержку возможности текущей версией проекта.
   */
  public static function has(string $capability): bool
  {
    return self::capabilities[$capability] ?? false;
  }

  /**
   * Получить все заявленные возможности текущей версии проекта.
   *
   * @return array<string, bool>
   */
  public static function all(): array
  {
    return self::capabilities;
  }
}
