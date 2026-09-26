<?php

namespace AC\core\system\helpers;

use InvalidArgumentException;

/** Подготавливает URL ресурсов; HTML-теги остаются в шаблонах. */
class AssetHelper
{
  /** Возвращает URL CSS по имени или дескриптору [имя, шаблон, без css/, origin]. */
  public static function cssUrl(string|array $asset, ?string $origin = null): string
  {
    return self::resourceUrl($asset, 'css', $origin);
  }

  /** Возвращает URL JS по имени или дескриптору [имя, шаблон, без js/, origin]. */
  public static function jsUrl(string|array $asset, ?string $origin = null): string
  {
    return self::resourceUrl($asset, 'js', $origin);
  }

  private static function resourceUrl(string|array $asset, string $type, ?string $origin): string
  {
    [$name, $template, $notUseDefaultPath, $assetOrigin] = is_array($asset)
      ? array_pad($asset, 4, null) : [$asset, null, false, 'base'];
    // У старых CSS-дескрипторов флаг допускает truthy-значения, у JS — только true.
    $withoutPrefix = $type === 'css' ? (bool)$notUseDefaultPath : $notUseDefaultPath === true;
    $path = paths()->getAssetsDir(($withoutPrefix ? '' : $type . '/') . $name . '.' . $type, $template);
    return match ($origin ?? $assetOrigin) {
      'cdn' => cdn_url($path),
      'site' => $type === 'js' ? site_url($path) : base_url($path),
      default => base_url($path),
    };
  }

  /**
   * Возвращает URL существующего локального custom.css/custom.js с версией по времени изменения.
   * @throws InvalidArgumentException Для неподдерживаемого типа ресурса.
   */
  public static function localCustomUrl(string $type, ?string $device = null): ?string
  {
    if (!in_array($type, ['css', 'js'], true)) {
      throw new InvalidArgumentException('Unsupported custom asset type');
    }
    $path = paths()->getAssetsDir($type . '/custom.' . $type, $device);
    $file = pathAs(ROOT_PATH . $path);
    return is_file($file) ? base_url($path) . '?v=' . filemtime($file) : null;
  }
}
