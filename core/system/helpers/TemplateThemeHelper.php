<?php

namespace AC\core\system\helpers;

/** Правила цветовой темы шаблона, независимые от запроса и сессии. */
class TemplateThemeHelper
{
  public const default_colors = ['color' => '00471f', 'accent' => 'ffb900'];
  private const text_luminance = 0.6;
  private const selected_contrast = 2;

  /** Возвращает настройки браузерной темы с проверенными базовыми цветами конкретного шаблона. */
  public static function getClientConfig(mixed $color = null, mixed $accent = null): array
  {
    return [
      'defaults' => [
        'color' => ColorsHelper::normalizeHex($color) ?? self::default_colors['color'],
        'accent' => ColorsHelper::normalizeHex($accent) ?? self::default_colors['accent'],
      ],
      'textLuminance' => self::text_luminance,
      'selectedContrast' => self::selected_contrast,
    ];
  }

  /**
   * Возвращает цвета фонов, текста и выбранного пункта в формате #rrggbb.
   * @return array<string, string>
   */
  public static function getColors(mixed $color, mixed $accent): array
  {
    $primary = ColorsHelper::normalizeHex($color) ?? self::default_colors['color'];
    $accent = ColorsHelper::normalizeHex($accent) ?? self::default_colors['accent'];
    // Белый текст на цветных фонах; чёрный — на очень светлых.
    $textColor = static fn(string $hex): string => ColorsHelper::relativeLuminance($hex) >= self::text_luminance ? '#000000' : '#ffffff';
    $onPrimary = $textColor($primary);

    return [
      'primary' => '#' . $primary,
      'on-primary' => $onPrimary,
      'accent' => '#' . $accent,
      'on-accent' => $textColor($accent),
      // Почти одинаковые основной и акцентный цвета не должны скрывать выбранный пункт.
      'selected' => ColorsHelper::contrastRatio($primary, $accent) >= self::selected_contrast ? '#' . $accent : $onPrimary,
    ];
  }

  /**
   * Возвращает проверенные значения CSS-переменных темы без HTML-разметки.
   * @return array<string, string>
   */
  public static function getCssVariables(mixed $color, mixed $accent): array
  {
    $variables = [];
    foreach (self::getColors($color, $accent) as $name => $value) {
      $variables['--ac-color-' . $name] = $value;
    }
    return $variables;
  }
}
