<?php

namespace AC\core\system\helpers;

class TextHelper
{
  public static function autoParagraph($text)
  {
    if ($text && trim($text) !== '') {
      $text  = preg_replace('|<br[^>]*>\s*<br[^>]*>|i', "\n\n", $text . "\n");
      $text  = preg_replace("/\n\n+/", "\n\n", str_replace(["\r\n", "\r"], "\n", $text));
      $texts = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
      $text  = '';
      foreach ($texts as $txt) {
        $text .= (!preg_match('/<p>(.*?)<\/p>/si', $txt) ? '<p>' . nl2br(trim($txt, "\n")) . "</p>" : $txt) . "\n";
      }
      $text = preg_replace('|<p>\s*</p>|', '<p>&nbsp;</p>', $text);
    }

    return $text ? trim($text) : '';
  }

  public static function fullStripTags($text)
  {
    return preg_replace("/<([\/]?.*?[\/]?>)/", '', $text);
  }

  public static function TextSoftCut($string, $length = 150)
  {
    //если строка чуть длиньше - резать не надо
    if (strlen($string) > floor($length * 1.25)) {
      //стоит резать
      $a = strpos($string, ' ', $length);
      if ($a == true) {
        //нашли
        if ($a > floor($length * 1.25)) {
          $cut = $length;
        } else {
          $cut = $a;
        }
      } else {
        //не нашли
        $cut = $length;
      }

      return trim(substr($string, 0, $cut));
    } else {
      //не режем
      return $string;
    }
  }
}