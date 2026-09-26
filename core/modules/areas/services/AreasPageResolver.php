<?php

namespace AC\core\modules\areas\services;

final class AreasPageResolver
{
  /**
   * Выбирает запрошенную страницу площадок или первый доступный таб.
   *
   * @return array{page: int, limit: ?array{0: int, 1: int}, pages: array}
   */
  public static function resolve(array $pages, int $requestedPage): array
  {
    $page = $requestedPage;
    if ($pages && !isset($pages[$page])) {
      $page = (int)array_key_first($pages);
    }

    $selectedPage = $pages[$page] ?? [];

    return [
      'page'  => $page,
      'limit' => $selectedPage ? [$selectedPage[0]['start_limit'], count($selectedPage)] : null,
      'pages' => $pages,
    ];
  }
}
