<?php
class ajax_test
{
  function __construct($action, $engine_name = false)
  {
    if ($engine_name) {
      $this->e = getEngine($engine_name);
    }
    $this->action = $action;
  }

  function start()
  {
    switch ($this->action) {
      case 'testiban':
        return $this->testIBAN();
        break;
      case 'testbic' :
        return $this->testBIC();
        break;
      default:
        break;
    }
  }

  function testBIC()
  {
    if (isset($_POST['field'])) {
      $bic = $_POST['field'];

      if (strlen($bic) != 11) {
        return false;
      }

      $result = preg_match('/^\w{11}$/', $bic);
      if ($result == 1) {
        return true;
      }
    }

    return false;
  }

  function testIBAN()
  {
    if (isset($_POST['field']) && !empty($_POST['field'])) {
      $iban = $_POST['field'];
      return  $this->is_iban_valid($iban);
//      $iban = str_replace(array(' ', '_'), '', $iban);
//      if(!empty($iban)) {
//        $country_code           = substr($iban, 0, 2);
//        $result_of_country_code = preg_match('/^[A-Z][A-Z]/', $country_code);
//
//        if ($result_of_country_code == 0) {
//          return false;
//        }
//
//        $iban1 = substr($iban, 4)
//          . strval(ord($iban[0]) - 55)
//          . strval(ord($iban[1]) - 55)
//          . substr($iban, 2, 2);
//
//        $rest = 0;
//        for ($pos = 0; $pos < strlen($iban1); $pos += 7) {
//          $part = strval($rest) . substr($iban1, $pos, 7);
//          $rest = intval($part) % 97;
//        }
//        $pz = sprintf("%02d", 98 - $rest);
//
//        if (substr($iban, 2, 2) == '00') {
//          return substr_replace($iban, $pz, 2, 2);
//        } else {
//          return ($rest == 1) ? true : false;
//        }
//      }
    }

    return false;
  }

  function is_iban_valid($iban)
  {
    $iban = strtolower(preg_replace('/\s+/', '', $iban));
    $countries = ['al' => 28, 'ad' => 24, 'at' => 20, 'az' => 28, 'bh' => 22, 'be' => 16, 'ba' => 20, 'br' => 29, 'bg' => 22, 'cr' => 21, 'hr' => 21, 'cy' => 28, 'cz' => 24, 'dk' => 18, 'do' => 28, 'ee' => 20, 'fo' => 18, 'fi' => 18, 'fr' => 27, 'ge' => 22, 'de' => 22, 'gi' => 23, 'gr' => 27, 'gl' => 18, 'gt' => 28, 'hu' => 28, 'is' => 26, 'ie' => 22, 'il' => 23, 'it' => 27, 'jo' => 30, 'kz' => 20, 'kw' => 30, 'lv' => 21, 'lb' => 28, 'li' => 21, 'lt' => 20, 'lu' => 20, 'mk' => 19, 'mt' => 31, 'mr' => 27, 'mu' => 30, 'mc' => 27, 'md' => 24, 'me' => 22, 'nl' => 18, 'no' => 15, 'pk' => 24, 'ps' => 29, 'pl' => 28, 'pt' => 25, 'qa' => 29, 'ro' => 24, 'sm' => 27, 'sa' => 24, 'rs' => 22, 'sk' => 24, 'si' => 19, 'es' => 24, 'se' => 24, 'ch' => 21, 'tn' => 24, 'tr' => 26, 'ae' => 23, 'gb' => 22, 'vg' => 24];
    if (strlen($iban) < 2) {
      return false;
    }
    $country = substr($iban, 0, 2);
    if (isset($countries[$country]) && strlen($iban) == $countries[$country]) {
      $reordered = substr($iban, 4) . substr($iban, 0, 4);
      $chars = str_split($reordered);
      $converted = '';

      foreach ($chars as $key => $value) {
        if (!is_numeric($value)) {
          $chars[$key] = mb_ord($value, 'utf8') - 87;
        }
        $converted .= $chars[$key];
      }

      if (bcmod($converted, '97') == 1) {
        return true;
      }
    }
    return false;
  }
}

if (isset($_GET['action'])) {
  $action = $_GET['action'];
} else {
  if (isset($_POST['action'])) {
    $action = $_POST['action'];
  }
}

if (isset($_GET['engine'])) {
  $engine = $_GET['engine'];
} else {
  if (isset($_POST['engine'])) {
    $engine = $_POST['engine'];
  }
}

if (isset($action)) {
  $ad = new ajax_test($action, (isset($engine) ? $engine : false));
  echo $ad->start();
}