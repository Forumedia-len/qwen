<?php

namespace AC\core\system\helpers;

class PasswordHelper
{
  /**
   *  Генератор паролей
   * @param $number
   * @param bool  $useSpecialCharacters
   * @return string
   */
  public static function generatePassword($number, bool $useSpecialCharacters = true): string
  {
    $arr = array_merge(self::getTextCharacters(),self::getNumericCharacters(),($useSpecialCharacters ? self::getSpecialCharacters() : []));
    // Генерируем пароль
    $pass = "";
    for ($i = 0; $i < $number; $i++) {
      // Вычисляем случайный индекс массива
      $index = mt_rand(0, count($arr) - 1);
      $pass  .= $arr[$index];
    }

    return $pass;
  }

  public static function getSpecialCharacters($as = 'array')
  {
    $characters = [
      ' ', '!', '"', '#', '$', '%',
      '\'', '&', '(', ')', '*', '+',
      ',', '-', '.', '/', ':', ';',
      '<', '=', '>', '?', '@', '[',
      '\\', ']', '^', '_', '`', '{',
      '|', '}', '~'];

    return match ($as) {
      'string' => implode('', $characters),
      default => $characters
    };
  }

  public static function getTextCharacters($as = 'array')
  {
    $characters = [
      'a','b','c','d','e','f',
      'g','h','i','j','k','l',
      'm','n','o','p','q','r',
      's','t','u','v','w','x',
      'y','z',
      'A','B','C','D','E','F',
      'G','H','I','J','K','L',
      'M','N','O','P','Q','R',
      'S','T','U','V','W','X',
      'Y','Z'];

    return match ($as) {
      'string' => implode('', $characters),
      default => $characters
    };
  }

  public static function getNumericCharacters($as = 'array')
  {
    $specialCharacters = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    return match ($as) {
      'string' => implode('', $specialCharacters),
      default => $specialCharacters
    };
  }

  public static function checkPasswordValidation($password, $option = [], &$errors = [])
  {
    $errors = [];

    if (strlen($password) < ($option['length'] ?? 16)) {
      $errors[] = lang('error_attribute_min_value', 'message_error', ['attribute' => lang('Password', 'users'), 'value' => ($option['length'] ?? 16)]);
    }
    $wildcardChar = $lowerChar = $upperChar = $numericChar = $otherChar = 0;
    for ($i = 0, $iMax = strlen($password); $i <= $iMax; $i++) {
      if (in_array($password[$i], self::getSpecialCharacters(), true)) {
        $wildcardChar++;
      } else {
        if (is_numeric($password[$i])) {
          $numericChar++;
        } else {
          if (mb_strtoupper($password[$i]) == $password[$i]) {
            $upperChar++;
          } else {
            if (mb_strtolower($password[$i]) == $password[$i]) {
              $lowerChar++;
            } else {
              $otherChar++;
            }
          }
        }
      }
    }

    if ($wildcardChar < ($option['numberSpecialChar'] ?? 3)) {
      $errors[] = lang('The minimum number of special characters', 'message_error', ['attribute' => lang('Password', 'users'), 'value' => ($option['numberSpecialChar'] ?? 3)]);
    }

    if (!$numericChar || !$lowerChar || !$upperChar) {
      $errors[] = lang('The password must contain uppercase, lowercase letters and numbers', 'message_error');
    }
    return !count($errors);
  }
}