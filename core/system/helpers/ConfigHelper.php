<?php

namespace AC\core\system\helpers;

class ConfigHelper
{
  static public function generateValues($fields, $quantity, $params = null, $back_fields = true)
  {
    $values = array();
    for ($i = 1; $i <= $quantity; $i++) {
      $_value = array();
      foreach ($fields as $field) {
        if (is_array($field)) {
          $val = str_replace('%index%', $i, $field['value']);
          switch ($field['type']) {
            case 'int':
              $val = (int)$val;
              switch ($field['action']) {
                case '+':
                  $val += $field['min'];
                  break;
                default:
                  break;
              }
              break;
            default:
              break;
          }
          $_value[] = $val;
        } else {
          $_value[] = str_replace('%index%', $i, $field);
        }
      }
      $values[] = $_value;
    }
    if ($params !== null) {
      foreach ($params as $param) {
        self::parseParams($fields, $param, $values);
      }
    }
    $result['values'] = $values;
    if ($back_fields) {
      $result['fields'] = array_keys($fields);
    }

    return $result;
  }

  static public function parseParams($fields, $param, &$values)
  {
    $_values   = array();
    $keyFields = array_flip(array_keys($fields));
    foreach ($values as $value) {
      foreach ($fields[$param]['value'] as $val) {
        $value[$keyFields[$param]] = $val;
        $_values[]                 = $value;
      }
    }
    $values = $_values;
  }
  
//кэширование результата + по ключу можно парсить разные переменные;
static protected $parse_variable;

static public function parseStringToVariables($input,$var_key): array
{
    $result = [];

    if (empty($input)) {
        return $result;
    }
    
    if (!empty(self::$parse_variable[$var_key])) {
        return self::$parse_variable[$var_key];
    }

    $pairs = explode('|', $input);

    foreach ($pairs as $pair) {
        $pair = trim($pair);
        if (empty($pair)) {
            continue;
        }

        // Разделяем по последнему двоеточию, чтобы корректно обработать возможные минусы в type_id
        $lastColonPos = strrpos($pair, ':');
        if ($lastColonPos === false) {
            continue; // некорректная пара
        }

        $keyPart = substr($pair, 0, $lastColonPos);
        $valuePart = substr($pair, $lastColonPos + 1);
        $matches=[];
        // Проверяем, что ключ состоит из двух чисел через подчёркивание
        if (!preg_match('/^(-?\d+)_(-?\d+)$/', $keyPart, $matches)) {
            continue; // некорректный формат ключа
        }

        $typeId = $matches[1];
        $sportId = $matches[2];

        // Формируем ключ как "type_id_sport_id"
        $key = "{$typeId}_{$sportId}";

        // Преобразуем значение в число (int или float)
        if (is_numeric($valuePart)) {
            $value = strpos($valuePart, '.') !== false ? (float)$valuePart : (int)$valuePart;
        } else {
            $value = $valuePart; // или пропустить, если нужен только числовой тип
        }

        $result[$key] = $value;
    }
    self::$parse_variable[$var_key]=$result;
    return $result;
}

static public function checkDateInInterval(string $date_start, $count, string $check_date): bool
{    if(empty($count)) return false;
    // Приводим все даты к объектам DateTime
    $start = new \DateTime($date_start);
    $check = new \DateTime($check_date);

    // Копируем начальную дату, чтобы не менять оригинал
    $end = clone $start;
    $end->add(new \DateInterval('P' . $count . 'D')); // добавляем $count дней

    // Проверяем: $start <= $check < $end  (интервал [start, start + count))
    // Если нужно включать последний день — замени на <= $end
    return ($check >= $start) && ($check < $end);
}

}