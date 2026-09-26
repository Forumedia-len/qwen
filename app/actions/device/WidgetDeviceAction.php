<?php

namespace AC\app\actions\device;

use AC\core\system\helpers\ColorsHelper;
use AC\core\system\helpers\TemplateThemeHelper;
use Service;

/** Подготавливает встраиваемый интерфейс с функциональностью публичного сайта. */
class WidgetDeviceAction extends SiteDeviceAction
{
  /** Встраиваемый интерфейс не выводит форму подтверждения cookies. */
  protected bool $showCookieConsent = false;

  /** Проверяет запрос и разрешает загрузку интерфейса внутри iframe. */
  public function before()
  {
    header_remove('X-Frame-Options');

    // Формы внутри iframe отправляются с origin виджета; родитель не должен отправлять их за пользователя.
    $request = Service::request();
    if (!in_array(strtoupper($request->getMethod()), ['GET', 'HEAD', 'OPTIONS'], true)) {
      $origin = $request->getHeaderLine('Origin');
      $expectedOrigin = Service::url()->getScheme() . '://' . Service::url()->getAuthority();
      if (($origin !== '' && strcasecmp($origin, $expectedOrigin) !== 0)
        || $request->getHeaderLine('Sec-Fetch-Site') === 'cross-site') {
        return Service::response()->setStatusCode(403)->setContentType('text/plain', 'UTF-8')
          ->setBody(lang('invalid_request_input', 'http_error'));
      }
    }

    return parent::before();
  }

  /** Принимает только RGB HEX: необязательный # и три либо шесть шестнадцатеричных знаков. */
  public static function normalizeColor(mixed $color): ?string
  {
    return ColorsHelper::normalizeHex($color);
  }

  /**
   * Возвращает проверенные цвета темы и выбранный цвет текста для каждого фона.
   * @return array<string, string> HEX-значения с # для CSS-переменных --ac-color-*.
   */
  public static function getThemeColors(mixed $color, mixed $accent): array
  {
    return TemplateThemeHelper::getColors($color, $accent);
  }

  /** Вычисляет относительную яркость нормализованного шестизначного sRGB HEX. */
  private static function relativeLuminance(string $hex): float
  {
    return ColorsHelper::relativeLuminance($hex);
  }
}
