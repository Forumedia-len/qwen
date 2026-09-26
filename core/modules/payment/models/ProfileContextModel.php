<?php

namespace AC\core\modules\payment\models;

use AC\app\locators\Service;
use AC\core\modules\config\helpers\BindingConfigTypeHelper;
use AC\core\modules\config\models\ConfigDbValueSelector;
use AC\core\system\db\Query;
use AC\core\system\helpers\StringHelper;

/**
 * Доп. реквизиты по контексту площадки: строки в {prefix}config с type вида
 * {prefix}|{alias}[_{type_id}[_{sport_id}[_{area_id}]]]; чтение значений через {@see ConfigDbValueSelector}.
 */
abstract class ProfileContextModel
{
  public const CORRELATION_PREFIX = '';

  abstract public static function profileFields(): array;

  /**
   * Строки для админ-списка по type: только то, что реально есть в `config`.
   *
   * @return list<array>
   */
  public static function listProfileRows(): array
  {
    static $profileRows;
    if (empty($profileRows[static::CORRELATION_PREFIX])) {
      if (($fieldsProfile = static::profileFields()) !== []) {
        foreach (Service::configDBModel()->getByPrefixType(static::CORRELATION_PREFIX) as $typeKey => $rowConfig) {
          $row = clone $rowConfig;
          $row->typeKey = rawurlencode($typeKey);
          foreach ($fieldsProfile as $field => $params) {
            $value       = Service::cast($params['type'])::get($row->$field ?? null);
            $row->$field = [
              'value'  => $value,
              'masked' => isset($params['masked']) ? StringHelper::mask($value, ...$params['masked']) : $value,
              'type'   => $params['type'],
            ];
            if(isset($params['useType'])) {
              $fieldValue = Service::cast($params['useType'])::get($value, $params['useCastParams'] ?? []);
              if(isset($params['availableMethods']) && $params['availableMethods'] && method_exists(static::class, 'availableMethods')) {
                foreach ($fieldValue as $methodKey => $methodValue) {
                  if($paramsMethod = static::availableMethods()[$methodKey]['extra_params'] ?? null) {
                    foreach ($paramsMethod as $paramMethod => $paramValue) {
                      if(isset($methodValue[$paramMethod])) {
                        $row->$field['maskedValues'][$methodKey][$paramMethod] = isset($paramValue['masked']) ? StringHelper::mask($methodValue[$paramMethod], ...$paramValue['masked']) : $methodValue[$paramMethod];
                      }
                    }                    
                  }
                }
              }
              $row->$field['useValue'] = $fieldValue;
            }
          }
          $row->allow_remove     = $typeKey !== static::CORRELATION_PREFIX;
          $row->bindingLabel     = BindingConfigTypeHelper::bindingLabelForConfigType($typeKey);
          $profileRows[static::CORRELATION_PREFIX][$typeKey] = (array)$row;
        }
      }
    }

    return $profileRows[static::CORRELATION_PREFIX] ?? [];
  }

  public static function profileRow(string $typeKey): ?array
  {
    return static::listProfileRows()[$typeKey] ?? null;
  }

  public static function isValidContextType(string $type): bool
  {
    return BindingConfigTypeHelper::isValidConfigType($type, static::CORRELATION_PREFIX);
  }

  /**
   * Все `type` в таблице `config`, относящиеся к PayPal-профилям:
   * - `paypal`
   * - `paypal|...`
   *
   * @return list<string>
   */
  public static function listAllConfigTypes(): array
  {
    return array_keys(self::listProfileRows());
  }

  public static function deleteContextType(string $type): void
  {
    if (!self::isValidContextType($type)) {
      return;
    }
    Query::sqlQuery(
      'DELETE FROM ' . Query::tableName('config') . ' WHERE type = ?',
      [$type],
      false
    );
  }
}
