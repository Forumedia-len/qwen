<?php

namespace AC\app\config\db_court;

use AC\core\system\db\DB;
use AC\core\system\helpers\ArrayHelper;
use AC\core\system\helpers\ConfigHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\helpers\TimeHelper;

class CopyCortDbConfig extends CortDbConfig
{
  static public function configCourt($params)
  {
    $config            = array();
    self::$courtData   = $params['court_data'];
    self::$changeTable = $params['change'];
    self::$baseDB      = DB::instance('base');
    self::$siteDB      = DB::instance('site', true);

    $max_id                         = self::$siteDB->getDbo()->query(
      'select max(area_id) as max_id from ' . self::$siteDB->getDbo()->generateTableName(
        'areas'
      ) . ' where area_id is not null'
    );
    self::$courtData['area_max_id'] = $max_id[0]['max_id'];
    $tables                         = array_keys(self::$changeTable);
    foreach (self::$baseDB->getNameTables() as $tableName) {
      $action          = 'config' . ucfirst(StringHelper::underscoreToCamelCase($tableName));
      self::$tableName = $tableName;
      if (in_array($tableName, $tables)) {
        if (method_exists(self::class, $action)) {
          $config[$tableName] = self::$action(self::$changeTable[$tableName]);
        } else {
          $config[$tableName] = self::$changeTable[$tableName];
        }
      }
    }

    return $config;
  }

  static public function configAreas($params)
  {
    $config = array();
    if (isset($params['new'])) {
      $new              = ArrayHelper::makeArray($params['new'], 'quantity');
      $config['insert'] = array();
      $count            = 0;
      $min_area_id      = self::$courtData['area_max_id'];
      foreach ($new as $area_param) {
        $config['insert'] = array_merge_recursive(
          $config['insert'],
          ConfigHelper::generateValues(
            array(
              'area_id'     => array('min' => $min_area_id, 'value' => '%index%', 'type' => 'int', 'action' => '+'),
              'type_id'     => $area_param['type_id'],
              'sport_id'    => $area_param['sport_id'],
              'period'      => isset($area_param['period']) ? $area_param['period'] : self::$courtData['period'],
              'title'       => isset($area_param['title']) ? $area_param['title'] : 'Platz %index%',
              'short_title' => isset($area_param['short_title']) ? $area_param['short_title'] : '%index%',
              'page'        => 1,
              'sort'        => isset($area_param['sort']) ? $area_param['sort'] : '%index%',
            ),
            $area_param['quantity'],
            null,
            $count < 1
          )
        );
        $min_area_id      += $area_param['quantity'];
        $count++;
      }
    }

    return $config;
  }

  static public function configAreasTypes($params)
  {
    $config = array();
    if (isset($params['replace']) && $params['replace'] === true) {
      $config['replace'] = array(
        'source' => 'base',
      );
    }

    if (isset($params['active'])) {
      $areas_types = ArrayHelper::makeArray($params['active']);
      $types       = self::$baseDB->getFullDataInTable('areas_types', 'alias');
      foreach ($types as $alias => $type) {
        $active = (int)in_array($alias, $areas_types);
        if ($type->active !== $active) {
          $config['update'][] = array(
            'where' => 'alias=\'' . $alias . '\'',
            'set'   => 'active=\'' . (int)in_array($alias, $areas_types) . '\''
          );
        }
      }
    }

    return $config;
  }

  static public function configAreasSports($params)
  {
    $config       = array();
    $areas_sports = ArrayHelper::makeArray($params);
    $sports       = self::$baseDB->getFullDataInTable('areas_sports', 'alias');
    if (count($sports) == 0) {
      $i = 1;
      foreach ($areas_sports as $sport) {
        if (is_array($sport)) {
          // todo нет возможности обновления и сравнения
        } else {
          $config[] = array(ucfirst($sport), $i, '', $sport);
        }
        $i++;
      }

      $config = array(
        'insert' => array(
          'fields' => array('title', 'sort', 'comment', 'alias'),
          'values' => $config
        )
      );
    }

    return $config;
  }

  static public function configAreasTimetables($params)
  {
    $config = array();
    if (isset($params['delete_not_use'])) {
      // Удалим лишние перед занисением новых
      self::deleteNotUse('area_id', 'areas', self::$tableName);
    }

    if (isset($params['generate_new']) && self::$courtData['all_new_areas'] > 0) {
      $config['insert'] = ConfigHelper::generateValues(
        array(
          'area_id' => array('min' => self::$courtData['area_max_id'], 'value' => '%index%', 'type' => 'int', 'action' => '+'),
          'weekday' => array(
            'type'  => 'array',
            'value' => self::$courtData['weekday'],
          ),
          'start'   => date('H:i:00', strtotime(self::$courtData['start'])),
          'finish'  => TimeHelper::convertTime24(date('H:i:00', strtotime(self::$courtData['finish']))),
        ),
        self::$courtData['all_new_areas'],
        array('weekday')
      );
    }

    return $config;
  }

  static public function configAreasPrices($params)
  {
    $config = array();
    if (isset($params['delete_not_use'])) {
      // Удалим лишние перед занисением новых
      self::deleteNotUse('area_id', 'areas', self::$tableName);
    }
    if (isset($params['copy'])) {
      $config['copy'] = $params['copy'];
    }
    if (isset($params['generate_new']) && self::$courtData['all_new_areas'] > 0) {
      $config['insert'] = ConfigHelper::generateValues(
        array(
          'area_id'   => array(
            'min'    => isset($params['area_max_id']) ? $params['area_max_id'] : self::$courtData['area_max_id'],
            'value'  => '%index%',
            'type'   => 'int',
            'action' => '+'
          ),
          'period_id' => array(
            'type'  => 'array',
            'value' => array(1, 2),
          ),
          'weekday'   => array(
            'type'  => 'array',
            'value' => self::$courtData['weekday'],
          ),
          'start'     => array(
            'type'  => 'array',
            'value' => TimeHelper::generateArrayTimeInIncrements(
              self::$courtData['start'],
              self::$courtData['finish'],
              self::$courtData['period']
            ),
          ),
          'price'     => self::$courtData['price'],
        ),
        self::$courtData['all_new_areas'],
        array('period_id', 'weekday', 'start')
      );
    }

    return $config;
  }

//  static public function configAreasPricesPeriods($params)
//  {
//  }

  static public function configConfig($params)
  {
    $config = array();
    if (isset($params['change_alias']) && $params['change_alias']) {
      foreach (array('min_rejection_days_count', 'max_forward_reservation_days_count') as $alias) {
        $config['update'][] = array(
          'where' => 'alias=\'' . $alias . '\'',
          'set'   => 'alias=\'' . $alias . '_' . self::$courtData['default_type']['alias'] . '\''
        );
      }
    }
    if (isset($params['change'])) {
      $_base = self::$baseDB->getFullDataInTable(self::$tableName, 'alias');
      $_site = self::$siteDB->getFullDataInTable(self::$tableName, 'alias');
      foreach ($params['change'] as $alias => $value) {
        $value = (preg_match('/^%(.*?)%$/', $value, $key) ? self::$courtData[$key[1]] : $value);
        if (isset($_base[$alias])) {
          $config['update'][] = array(
            'where' => 'alias=\'' . $alias . '\'',
            'set'   => 'value=\'' . $value . '\''
          );
        } else {
          $config['insert']['values'][] = array(
            $alias,
            $value
          );
        }
      }
      if (isset($config['insert'])) {
        $config['insert']['fields'] = self::$baseDB->getFields(self::$tableName, false);
      }
    }

    if (isset($params['add_missing']) && $params['add_missing']) {
      $config['add_missing'] = array(
        'check_fields' => array(
          'alias'
        ),
        'prefix'       => array(
          'alias' => self::$courtData['default_type']['alias']
        ),
      );
      if (isset($params['add_missing']['change'])) {
        foreach ($params['add_missing']['change'] as $alias => $value) {
          if (isset($_base[$alias]) && !isset($_site['alias'])) {
            $value                             = (preg_match('/^%(.*?)%$/', $value, $key) ? self::$courtData[$key[1]] : $value);
            $config['add_missing']['change'][] = array(
              'where' => array('alias' => $alias),
              'set'   => array('value' => $value)
            );
          }
        }
      }
    }

    return $config;
  }

  static public function configConfigText($params)
  {
    $insert  = $update = array();
    $txt     = '<p>' . $params['name_court'] . '<br />' . $params['address'] . '<br />' . $params['post_code'] . ' ' . $params['city'] . '</p>'
      . (!empty($params['phone']) ? '<p>Tel.:  ' . $params['phone'] . '</p>' : '')
      . '<p>E-Mail: <a href="mailto:' . $params['admin_email'] . '">' . $params['admin_email'] . '</a></p>';
    $default = array(
      'address'      => '<p><u><strong>Kontakt / Impressum:</strong></u></p>' . $txt,
      'address_home' => $txt
    );
    $_base   = self::$baseDB->getFullDataInTable(self::$tableName, 'alias');

    foreach (isset($params[self::$tableName]) ? $params[self::$tableName] : $default as $alias => $value) {
      if (isset($_base[$alias])) {
        $update[] = array(
          'where' => 'alias=\'' . $alias . '\'',
          'set'   => 'content=\'' . $value . '\''
        );
      } else {
        $insert[] = array(
          $alias,
          $value
        );
      }
    }

    return self::generateConfig(self::$baseDB->getFields(self::$tableName, false), $insert, $update);
  }

  static public function configLettersTemplates($params)
  {
    $config = array();
    if (isset($params['change_alias']) && $params['change_alias']) {
      $change_alias = array('order_new');
      if (self::$courtData['default_type']['alias'] == 'mc_arena') {
        $change_alias = array_merge(
          $change_alias,
          array(
            'order_remove',
            'registration',
            'activate',
            'recover_password',
            'query_prepayment'
          )
        );
      }
      foreach ($change_alias as $alias) {
        $config['update'][] = array(
          'where' => 'alias=\'' . $alias . '\'',
          'set'   => 'alias=\'' . $alias . '_' . self::$courtData['default_type']['alias'] . '\''
        );
      }
    }
    if (isset($params['add_missing']) && $params['add_missing']) {
      $config['add_missing'] = array(
        'check_fields' => array(
          'alias',
          'mode'
        ),
        'prefix'       => array(
          'alias' => self::$courtData['default_type']['alias']
        ),
        'necessarily'  => array(
          'recover_password',
          'query_prepayment',
          'order_remove',
          'activate',
          'registration',
          'account_file_send'
        ),
        'change'       => array(
          array(
            'where'   => 'all',
            'set'     => array('content'),
            'stencil' => array('%DATA_COURT%' => self::$courtData['txt_data_address'])
          )
        )
      );
    }

    return $config;
  }

  static public function configConfigExtra($params)
  {
    $config = array();
    if (isset($params['update_default'])) {
      $config['update'] = array(
        array(
          'where' => 'extra_id=1',
          'set'   => 'area_type_id=\'' . self::$courtData['default_type']['id'] . '\', area_sport_id=\'' . self::$courtData['default_sport']['id'] . '\''
        )
      );
    }
    if (isset($params['new'])) {
      $new              = ArrayHelper::makeArray($params['new'], 'no_club_rate');
      $config['insert'] = array();
      $count            = 0;
      foreach ($new as $area_param) {
        $config['insert'] = array_merge_recursive(
          $config['insert'],
          ConfigHelper::generateValues(
            $area_param,
            1,
            null,
            $count < 1
          )
        );
        $count++;
      }
    }

    return $config;
  }

  static public function configUsers($params)
  {
    $config = array();
    if (isset($params['insert'])) {
      $config['insert'] = $params['insert'];
    }

    return $config;
  }
}