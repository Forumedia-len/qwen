<?php

namespace AC\core\system\helpers;

use Exception;
use InvalidArgumentException;

/** Преобразования цветов и вычисления яркости и контраста. */
class ColorsHelper
{
  /** Нормализует RGB HEX без #; неподдерживаемые значения возвращают null. */
  public static function normalizeHex(mixed $color): ?string
  {
    if (!is_string($color) || !preg_match('/^#?([a-f0-9]{3}|[a-f0-9]{6})$/iD', $color, $matches)) {
      return null;
    }
    $hex = strtolower($matches[1]);
    return strlen($hex) === 3 ? $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2] : $hex;
  }

  /**
   * Вычисляет относительную яркость sRGB HEX.
   * @throws InvalidArgumentException Если значение не является RGB HEX.
   */
  public static function relativeLuminance(string $hex): float
  {
    $hex = self::normalizeHex($hex);
    if ($hex === null) {
      throw new InvalidArgumentException('Invalid RGB HEX color');
    }
    $rgb = array_map(static function (int $channel): float {
      $value = $channel / 255;
      return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
    }, self::hexToRgb($hex));
    return 0.2126 * $rgb['R'] + 0.7152 * $rgb['G'] + 0.0722 * $rgb['B'];
  }

  /**
   * Возвращает отношение контраста двух RGB HEX от 1 до 21.
   * @throws InvalidArgumentException Если значение не является RGB HEX.
   */
  public static function contrastRatio(string $first, string $second): float
  {
    $firstLuminance = self::relativeLuminance($first);
    $secondLuminance = self::relativeLuminance($second);
    return (max($firstLuminance, $secondLuminance) + 0.05) / (min($firstLuminance, $secondLuminance) + 0.05);
  }

  private $_hex;
  private $_hsl;
  private $_rgb;
  
  /**
   * @throws Exception
   */
  public function __construct($color, $type = 'hex')
  {
    switch ($type) {
      case 'hex':
      default:
        $this->renderHex($color);
        break;
    }
  }
  
  /**
   * @throws Exception
   */
  static public function darken($color, $amount, $type = 'hex')
  {
    $_color = new ColorsHelper($color, $type);
    
    foreach ($_color->_rgb as $key => $value) {
      $_color->_rgb[$key] = self::setAmount($value, -$amount);
    }
    
    return self::rgbToHex($_color->_rgb);
  }
  
  /**
   * @throws Exception
   */
  static public function lighten($color, $amount, $type = 'hex')
  {
    $_color = new ColorsHelper($color, $type);
    
    foreach ($_color->_rgb as $key => $value) {
      $_color->_rgb[$key] = self::setAmount($value, $amount);
    }
    
    return self::rgbToHex($_color->_rgb);
  }
  
  /**
   * @throws Exception
   */
  static public function convert($color, $amount, $type = 'hex')
  {
    $amount             = $amount > 255 ? $amount - 255 : $amount;
    $_color             = new ColorsHelper($color, $type);
    $key                = $_color->getMinColor();
    $value              = $_color->_rgb[$key];
    $_color->_rgb[$key] = self::setAmount($value, $amount);
    
    $key                = $_color->getMaxColor();
    $value              = $_color->_rgb[$key];
    $_color->_rgb[$key] = self::setAmount($value, -$amount);
    
    return self::rgbToHex($_color->_rgb);
  }
  
  
  private static function setAmount($color, $amount)
  {
    $color = $color + $amount;
    if ($color > 255) {
      $color = 255;
    } elseif ($color < 0) {
      $color = 0;
    }
    
    return $color;
  }
  
  private function getMinColor()
  {
    $min = $this->_rgb['R'];
    $key = 'R';
    if ($min > $this->_rgb['G']) {
      $min = $this->_rgb['G'];
      $key = 'G';
    }
    if ($min > $this->_rgb['B']) {
      $min = $this->_rgb['B'];
      $key = 'B';
    }
    
    return $key;
  }
  
  private function getMaxColor()
  {
    $min = $this->_rgb['R'];
    $key = 'R';
    if ($min < $this->_rgb['G']) {
      $min = $this->_rgb['G'];
      $key = 'G';
    }
    if ($min < $this->_rgb['B']) {
      $min = $this->_rgb['B'];
      $key = 'B';
    }
    
    return $key;
  }
  
  /**
   * @throws Exception
   */
  private function renderHex($color)
  {
    $color = self::_checkHex($color);
    
    $this->_hex = $color;
    $this->_rgb = self::hexToRgb($color);
  }
  
  /**
   * @throws Exception
   */
  public static function hexToRgb($color)
  {
    // Sanity check
    $color = self::_checkHex($color);
    // Convert HEX to DEC
    $R        = hexdec($color[0] . $color[1]);
    $G        = hexdec($color[2] . $color[3]);
    $B        = hexdec($color[4] . $color[5]);
    $RGB['R'] = $R;
    $RGB['G'] = $G;
    $RGB['B'] = $B;
    
    return $RGB;
  }
  
  /**
   * @throws Exception
   */
  private static function _checkHex($hex)
  {
    // Strip # sign is present
    $color = str_replace("#", "", $hex);
    // Make sure it's 6 digits
    if (strlen($color) == 3) {
      $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
    } else {
      if (strlen($color) != 6) {
        throw new Exception("HEX color needs to be 6 or 3 digits long");
      }
    }
    
    return $color;
  }
  
  /**
   * @throws Exception
   */
  public static function rgbToHex($rgb = [])
  {
    // Make sure it's RGB
    if (empty($rgb) || !isset($rgb["R"]) || !isset($rgb["G"]) || !isset($rgb["B"])) {
      throw new Exception("Param was not an RGB array");
    }
    // https://github.com/mexitek/phpColors/issues/25#issuecomment-88354815
    // Convert RGB to HEX
    $hex[0] = str_pad(dechex($rgb['R']), 2, '0', STR_PAD_LEFT);
    $hex[1] = str_pad(dechex($rgb['G']), 2, '0', STR_PAD_LEFT);
    $hex[2] = str_pad(dechex($rgb['B']), 2, '0', STR_PAD_LEFT);
    // Make sure that 2 digits are allocated to each color.
    $hex[0] = (strlen($hex[0]) == 1) ? '0' . $hex[0] : $hex[0];
    $hex[1] = (strlen($hex[1]) == 1) ? '0' . $hex[1] : $hex[1];
    $hex[2] = (strlen($hex[2]) == 1) ? '0' . $hex[2] : $hex[2];
    
    return implode('', $hex);
  }
  
  public static function hexByTypeSport(int $type, int $sport): string
  {
    return self::hexColors()[($type * $sport) - 1] ?? self::hexColors()[($type * $sport)%100];
  }
  
  private static function hexColors(): array
  {
    return [
      '58bd4b',
      '917501',
      '006cff',
      'c98888',
      '6600ff',
      '999900',
      '003300',
      'ff6600',
      'ff3300',
      'ccccff',
      'cccc00',
      'cc9900',
      'cc6600',
      '99ff99',
      '99ccff',
      '99cc00',
      '999900',
      '999999',
      '996699',
      '996633',
      '993333',
      '66ffff',
      '66ccff',
      '66cc66',
      '669966',
      '6633cc',
      '663333',
      '33ffcc',
      '33ff00',
      '33cc66',
      '33cc00',
      '3399ff',
      '339966',
      '3366ff',
      '339999',
      '3366cc',
      '336666',
      '336600',
      '3333ff',
      '333399',
      '333300',
      '330099',
      '330066',
      '330000',
      '00ff99',
      '00ff00',
      '00ccff',
      '00cc99',
      '00cc33',
      '0099ff',
      '009999',
      '009966',
      '009900',
      '006699',
      '006600',
      '003366',
      '0000cc',
      '000066',
      '666600',
      'CCCC33',
      '999933',
      'ff3333',
      'ff33ff',
      'ff6699',
      'ff6633',
      'ff66ff',
      'ccffff',
      'cc9999',
      'cc3399',
      'cc3333',
      'cccc99',
      'ff3366',
      '6633ff',
      '33cc99',
      '3399cc',
      '336699',
      '336633',
      '3333cc',
      '333366',
      '333333',
      '330033',
      '00ffcc',
      '00cccc',
      '00cc66',
      '0099cc',
      '009933',
      '0066ff',
      '006666',
      '0033cc',
      '003333',
      '000033',
      'ffcccc',
      'ffcc99',
      'ffcc66',
      'ff9966',
      'ff99cc',
      'ff3399',
      'cccc66',
      'cc99ff',
      'cc99cc',
      'cc9966',
    ];
  }
}
