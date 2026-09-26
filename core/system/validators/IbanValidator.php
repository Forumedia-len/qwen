<?php

namespace AC\core\system\validators;

class IbanValidator extends Validator
{
  /**
   *  {@inheritDoc}
   */
  public function initMessageToParams($model)
  {
    parent::initMessageToParams($model);
  }

  /**
   * {@inheritDoc}
   */
  public function validateType($model)
  {
    parent::validateType($model);
    $attribute = $this->getAttribute();

    if ($this->checkIban($model->$attribute)) {
      return true;
    }

    return false;
  }

  protected function checkIban($iban)
  {
    $iban = str_replace(array(' ', '_'), '', $iban);
    if (!empty($iban)) {
      $country_code           = substr($iban, 0, 2);
      $result_of_country_code = preg_match('/^[A-Z][A-Z]/', $country_code);

      if ($result_of_country_code == 0) {
        return false;
      }

      $iban1 = substr($iban, 4)
        . strval(ord($iban[0]) - 55)
        . strval(ord($iban[1]) - 55)
        . substr($iban, 2, 2);

      $rest = 0;
      for ($pos = 0; $pos < strlen($iban1); $pos += 7) {
        $part = strval($rest) . substr($iban1, $pos, 7);
        $rest = intval($part) % 97;
      }
      $pz = sprintf("%02d", 98 - $rest);

      if (substr($iban, 2, 2) == '00') {
        return substr_replace($iban, $pz, 2, 2);
      } else {
        return $rest == 1;
      }
    }

    return false;
  }
}