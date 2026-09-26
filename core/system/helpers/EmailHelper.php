<?php

namespace AC\core\system\helpers;

use AC\core\engines\Engines;

class EmailHelper
{
  private static $pattern = '/^[a-zA-Z0-9\-\_\.]+\@[a-zA-Z0-9\-\_\.]+\.[a-zA-Z]{2,4}$/';

  public static function getBasePattern()
  {
    return self::$pattern;
  }

  public static function checkEmail($email, $pattern = false)
  {
    if (!$pattern) {
      $pattern = self::getBasePattern();
    }

    if ($email == null || !(preg_match($pattern, $email))) {
      return false;
    }

    return true;
  }

  //разобрать шаблон письма и выдать содержание
  public static function parseEmailTemplate($template, $template_data)
  {
    $c = join('', file(useFile('letters_templates/' . $template . '.txt')));
    foreach ($template_data as $key => $value) {
      $c = str_replace('%' . $key . '%', $value, $c);
    }

    return $c;
  }
//
//  public static function composeHeaders()
//  {
//    $a = new Engines();
//
//    return "Content-Type: text/plain; charset=iso-8859-1\r\n" .
//      "From: " . $a->config['admin_email'] . "\r\n" .
//      "Reply-To: " . $a->config['admin_email'] . "\r\n" .
//      "X-Mailer: PHP/" . phpversion();
//  }

  public static function maskEmail($email, $first = 1, $last = 1, $filter = '*')
  {
    $mail_parts   = explode("@", $email);
    $domain_parts = explode('.', $mail_parts[1]);

    $mail_parts[0]   = StringHelper::mask($mail_parts[0], $first, $last, $filter);
    $domain_parts[0] = StringHelper::mask($domain_parts[0], $first, $last, $filter);
    $mail_parts[1]   = implode('.', $domain_parts);

    return implode("@", $mail_parts);
  }
}