<?php

namespace AC\core\system\helpers;

use DateTime;
use InvalidArgumentException;
use Random\RandomException;

/** Преобразования строк и экранирование текста для HTML. */
class StringHelper
{
  public static function camelCaseToUnderscore($source, $separator = '_')
  {
    return strtolower(preg_replace('/(?<!^)[A-Z]/', $separator . '$0', $source));
  }
  
  public static function underscoreToCamelCase($source, $separator = '_')
  {
    return lcfirst(str_replace($separator, '', ucwords($source, $separator)));
  }
  
  public static function generateLoginByNameSurname($name, $surname, $characterNumberName = 1)
  {
    return str_replace(' ', '', mb_strtolower(mb_substr($name, 0, $characterNumberName) . $surname));
  }
  
  public static function translit($str, $replacement = '-', $colSymbol = null, $startSymbol = 0)
  {
    $str = mb_strtolower($str);
    $str = self::convert($str);
    $str = mb_ereg_replace('[^-0-9a-z]', $replacement, $str);
    $str = mb_ereg_replace('[-]+', $replacement, $str);
    if ($colSymbol) {
      $str = mb_strimwidth($str, $startSymbol, $colSymbol);
    }
    
    return trim($str, $replacement);
  }
  
  public static function convert($str)
  {
    $converter = [
      'а' => 'a',
      'б' => 'b',
      'в' => 'v',
      'г' => 'g',
      'д' => 'd',
      'е' => 'e',
      'ё' => 'e',
      'ж' => 'zh',
      'з' => 'z',
      'и' => 'i',
      'й' => 'y',
      'к' => 'k',
      'л' => 'l',
      'м' => 'm',
      'н' => 'n',
      'о' => 'o',
      'п' => 'p',
      'р' => 'r',
      'с' => 's',
      'т' => 't',
      'у' => 'u',
      'ф' => 'f',
      'х' => 'h',
      'ц' => 'c',
      'ч' => 'ch',
      'ш' => 'sh',
      'щ' => 'sch',
      'ь' => '',
      'ы' => 'y',
      'ъ' => '',
      'э' => 'e',
      'ю' => 'yu',
      'я' => 'ya',
      'ä' => 'ae',
      'ë' => 'e',
      'ď' => 'i',
      'ß' => 'ss',
      'ö' => 'oe',
      'ü' => 'ue',
      'ź' => 'y',
    ];
    
    return strtr($str, $converter);
  }
  
  /**
   * Экранирует текст для HTML, сохраняя прежнюю обрезку пробелов и настройки по умолчанию.
   *
   * @param mixed $string Исходный текст
   * @param string $encoding Кодировка текста
   * @param int $flag Флаги htmlspecialchars; по умолчанию поддерживаются сущности HTML5
   * @param bool $doubleEncode Повторно кодировать готовые сущности; false для вывода старых экранированных имён
   * @return string
   */
  public static function shield($string, $encoding = 'UTF-8', $flag = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 | ENT_HTML5,
    bool $doubleEncode = true)
  {
    return htmlspecialchars(trim($string), $flag, $encoding, $doubleEncode);
  }
  
  public static function upperCount($str_val, $maxUpCase = 1): bool
  {
    $str_val = str_replace(['-', '_', ',', '.', "'", "`"], ' ', $str_val);
    $str_arr = explode(' ', $str_val);
    foreach ($str_arr as $str) {
      $i = 0;
      foreach (str_split($str) as $ind => $char) {
        if (!is_numeric($char) && mb_strtoupper($char) === $char) {
          $i++;
          if ($ind > 0) {
            return true;
          }
        }
      }
      if (($maxUpCase < $i)) {
        return true;
      }
    }
    return false;
  }
  
  public static function mask($str, $first, $last, $filter = '*'): string
  {
    $len    = strlen($str);
    $toShow = $first + $last;
    
    return substr($str, 0, $len <= $toShow ? 0 : $first)
      . str_repeat($filter, $len - ($len <= $toShow ? 0 : $toShow))
      . substr($str, $len - $last, $len <= $toShow ? 0 : $last);
  }
  
  /**
   * Обрезает строку до указанной длины со стартовой позиции и добавляет к концу указанный префикс
   * @param        $str
   * @param ?int   $length
   * @param int    $start
   * @param string $endPrefix
   * @return string
   */
  public static function cropStr($str, ?int $length = 20, int $start = 0, string $endPrefix = '...'): string
  {
    return $length && iconv_strlen($str) > $length ? substr($str, $start, $length) . $endPrefix : $str;
  }
}
