<?php

namespace AC\app\config\db_court;


use AC\core\system\db\DB;
use AC\core\system\helpers\ArrayHelper;
use AC\core\system\helpers\ConfigHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\helpers\TimeHelper;

class NewCortDbConfig extends CortDbConfig
{
  /**
   * @var DB
   */
  private static $stDB;
  public static  $tableName;

  static public function configCourt($params)
  {
    $config     = array();
    self::$stDB = DB::instance('base');
    foreach (self::$stDB->getNameTables() as $tableName) {
      $action          = 'config' . ucfirst(StringHelper::underscoreToCamelCase($tableName));
      self::$tableName = $tableName;
      if (method_exists(self::class, $action)) {
        $config[$tableName] = self::$action($params);
      }
    }

    return $config;
  }

  static public function configAreas($params)
  {
    $types  = self::$stDB->getFullDataInTable('areas_types', 'alias');
    $sports = self::$stDB->getFullDataInTable('areas_sports', 'alias');

    return array(
      'insert' => ConfigHelper::generateValues(
        array(
          'type_id'     => $types[$params['areas_types']]->type_id,
          'sport_id'    => count($sports) === 0 ? 1 : $sports[$params['areas_sports']]->sport_id,
          'period'      => $params['period'],
          'title'       => 'Platz %index%',
          'short_title' => '%index%',
          'page'        => 1,
          'sort'        => '%index%',
        ),
        $params['areas']
      )
    );
  }

  static public function configAreasTypes($params)
  {
    $confT       = array();
    $areas_types = ArrayHelper::makeArray($params['areas_types']);
    $types       = self::$stDB->getFullDataInTable('areas_types', 'alias');
    foreach ($types as $alias => $type) {
      $active = (int)in_array($alias, $areas_types);
      if ($type->active !== $active) {
        $confT[] = array(
          'where' => 'alias=\'' . $alias . '\'',
          'set'   => 'active=\'' . (int)in_array($alias, $areas_types) . '\''
        );
      }
    }

    return array(
      'update' => $confT
    );
  }

  static public function configAreasSports($params)
  {
    $config       = array();
    $areas_sports = ArrayHelper::makeArray($params['areas_sports']);
    $sports       = self::$stDB->getFullDataInTable('areas_sports', 'alias');
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
    return array(
      'insert' => ConfigHelper::generateValues(
        array(
          'area_id' => '%index%',
          'weekday' => array(
            'type'  => 'array',
            'value' => $params['weekday'],
          ),
          'start'   => date('H:i:00', strtotime($params['start'])),
          'finish'  => date('H:i:00', strtotime($params['finish'])),
        ),
        $params['areas'],
        array('weekday')
      ),
    );
  }

  static public function configAreasPrices($params)
  {
    return array(
      'insert' => ConfigHelper::generateValues(
        array(
          'area_id'   => '%index%',
          'period_id' => array(
            'type'  => 'array',
            'value' => array(1, 2),
          ),
          'weekday'   => array(
            'type'  => 'array',
            'value' => $params['weekday'],
          ),
          'start'     => array(
            'type'  => 'array',
            'value' => TimeHelper::generateArrayTimeInIncrements(
              $params['start'],
              $params['finish'],
              $params['period']
            ),
          ),
          'price'     => $params['price'],
        ),
        $params['areas'],
        array('period_id', 'weekday', 'start')
      ),
    );
  }

  static public function configConfig($params)
  {
    $insert = $update = array();
    $_base  = self::$stDB->getFullDataInTable(self::$tableName, 'alias');

    foreach (isset($params['config']) ? $params['config'] : array() as $alias => $value) {
      $value = (preg_match('/^%(.*?)%$/', $value, $key) ? $params[$key[1]] : $value);
      if (isset($_base[$alias])) {
        $update[] = array(
          'where' => 'alias=\'' . $alias . '\'',
          'set'   => 'value=\'' . $value . '\''
        );
      } else {
        $insert[] = array(
          $alias,
          $value
        );
      }
    }

    return self::generateConfig(self::$stDB->getFields(self::$tableName, false), $insert, $update);
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
    $_base   = self::$stDB->getFullDataInTable(self::$tableName, 'alias');

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

    return self::generateConfig(self::$stDB->getFields(self::$tableName, false), $insert, $update);
  }

  static public function configLettersTemplates($params)
  {
    $insert = $update = array();
    $txt    = $params['name_court'] . "\n" . $params['address'] . "\n" . $params['post_code'] . ' ' . $params['city'] . "\n" . "\n"
      . (!empty($params['phone']) ? 'Tel.:  ' . $params['phone'] : '')
      . "\n" . 'E-Mail: ' . $params['admin_email'];
    $_base  = self::$stDB->getFullDataInTable(self::$tableName, 'alias');
    if (isset($params[self::$tableName])) {
      foreach ($params[self::$tableName] as $alias => $value) {
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
    } else {
      foreach ($_base as $alias => $value) {
        $content = $value->content;
        if (preg_match('/%DATA_COURT%/', $content)) {
          $update[] = array(
            'where' => 'alias=\'' . $alias . '\'',
            'set'   => 'content=\'' . str_replace('%DATA_COURT%', $txt, $content) . '\''
          );
        }
      }
    }

    return self::generateConfig(self::$stDB->getFields(self::$tableName, false), $insert, $update);
  }

  static public function configConfigExtra($params)
  {
    $insert = $update = array();
    $_base  = self::$stDB->getFullDataInTable(self::$tableName);
    foreach ($params[self::$tableName] as $param) {
      $insert[] = $param;
    }

    return self::generateConfig(self::$stDB->getFields(self::$tableName, false), $insert, $update);
  }
}