<?php
// не переработано


mb_internal_encoding("UTF-8");

define('PATH', './');
define('MODE', 0);
useFile('config.php');
useFile('include/useClass.php');
useFile('include/main.php');

error_reporting(E_ALL);

//useClass('core\\base\\Dbo');
useClass('core\\base\\VDebugs');
useClass('core\\base\\baseDB\\SyncTwoDB');
useClass('core\\helpers\\TimeHelper');
useClass('core\\config\\NewCortDbConfig');
useClass('core\\config\\CopyCortDbConfig');
useClass('core\\config\\DataBaseConfig');
define('ALIAS_DEFAULT_TYPE', 'close'); // тип установленный на площадке по умолчанию

//$config = array(
//  'tables' => NewCortDbConfig::configCourt(
//    array(
//      'weekday'      => array(0, 1, 2, 3, 4, 5, 6),
//      'start'        => '07:00',
//      'finish'       => '23:00',
//      'period'       => 60,
//      'price'        => 0,
//      'areas_types'  => 'close',
//      'areas_sports' => 'tennis',
//      'areas'        => 3,
//      'name_court'   => 'Tennisclub Blau-Weiss Spellen 1979 e.V.',
//      'admin_email'  => 'halle@tcbw-spellen.de',
//      'address'      => 'Mühlenberg 5',
//      'city'         => 'Voerde',
//      'post_code'    => '46562',
//      'phone'        => '02855/5939339',
//      'config_extra' => array(),
//      'config'       => array(
//        'sepa_name'            => '%name_court%',
//        'name_bank'            => '%name_court%',
//        'email_subject_prefix' => '%name_court%',
//        'notify_email'         => '%admin_email%',
//        'admin_email'          => '%admin_email%',
//        'account_mail'         => '%admin_email%',
//        'door_time_start'      => '%start%',
//        'door_time_finish'     => '%finish%',
//        'account_view_city'    => '%city%',
//        'account_view_header'  => '%name_court%',
//        'account_view_address' => DATA_ADDRESS_HTML
//      )
//    )
//  )
//);

$config = array(
  'changes_for_compare' => array(
    // заменить название стобца во всех таблицах
    'replaceColumn' => array(
      // в какой таблице или all если изменить во всех
      'all' => array(
        // название столбца в базовой базе (на который будет меяться ) => массив наваний столбцов котрые используются в старой базе
        'address' => array('adres', 'adress')
      )
    )
  ),
  'tables'              => CopyCortDbConfig::configCourt(
    array(
      'court_data' => array(
        'weekday'           => array(0, 1, 2, 3, 4, 5, 6), // использумые дни недели
        'start'             => '07:00', // время начала рабочего дня для генерации расписания
        'finish'            => '22:00', // время окончания рабочего дня
        'period'            => 30, // шаг расписания (для открытых кортов обычно 30)
        'price'             => 0, // цена для открытых кортов для одного промежутка
        'name_court'        => 'TSV Schlechtbach 1919 e.V. / Tennishalle',
        'admin_email'       => 'Oliver.Prey@th-schlechtbach.de',
        'address'           => 'Sportplatzweg 11',
        'city'              => 'Schlechtbach',
        'post_code'         => '73635',
        'phone'             => '015123922000',
        'all_new_areas'     => 5, // количество новых открытых кортов
        'txt_data_address'  => DATA_ADDRESS_TEXT,
        'html_data_address' => DATA_ADDRESS_HTML,
        'default_type'      => array('alias' => 'close', 'id' => 1), // тип площадки который уже есть на ней
        'default_sport'     => array('alias' => 'tennis', 'id' => 1), // тип спорт площадки который уже есть на ней
      ),
      'change'     => array(
        'areas_types'       => array( // по умолчанию копируем с base
          'replace' => true,
          // активируем нужные типы по alias - (close - закрытые, open - открытые, mc_arena - McArena)
          'active'  => array('close', 'open'),
        ),
        'areas_sports'      => array('tennis'),
        'areas'             => array(
//          'change' => array(
//            'where' => array('type_id' => 1),
//            'set' => array('short_title' => 'title')
//          ),
          'new' => array(
            array('quantity' => 5, 'type_id' => 2, 'sport_id' => 1),
//            array(
//              'quantity'    => 5,
//              'type_id'     => 2,
//              'sport_id'    => 1,
//              'title'       => 'L %index%',
//              'short_title' => 'L %index%',
//              'sort'        => '%index%'
//            ),
//            array(
//              'quantity'    => 2,
//              'type_id'     => 2,
//              'sport_id'    => 1,
//              'title'       => 'G %index%',
//              'short_title' => 'G %index%',
//              'sort'        => '%index%'
//            ),
          )
        ),
        'areas_timetables'  => array(
          'delete_not_use' => true,
          // удалить не используемые значения, чтобы не возникало ошибок
          'generate_new'   => true
          // пока если существует это поле генерит новые значения если не нулевое количество новых площадок
        ),
        'areas_prices'      => array(
          'delete_not_use' => true,
//          'copy' => array(
//            'source' => 'site',
//            'change' => array(
//              array(
//                'where' => 'all',
//                'set'   => array('period_id' => 2)
//              )
//            )
//          ),
          'generate_new'   => true
        ),
//        'areas_prices_periods' => array(
//          'update' => array(
//            array(
//              'where' => 'period_id=\'1\'',
//              'set'   => 'start=\'1970-10-01\''
//            ),
//            array(
//              'where' => 'period_id=\'2\'',
//              'set'   => 'start=\'1970-05-01\''
//            ),
//          )
//        ),
        'config_extra'      => array(
          'update_default' => true, // обновляем старую наценку
          'new'            => array(
            'no_club_rate' => 0,
            'club_rate'    => 0,
            'club_rate2'   => 0,
            'area_type_id' => 2,
            'sport_id'     => 1,
          )
        ),
//        'config'              => array(
//          'change_alias' => true,
//          'change'       => array(
////            'sepa_name'            => '%name_court%',
////            'name_bank'            => '%name_court%',
////            'email_subject_prefix' => '%name_court%',
////            'notify_email'         => '%admin_email%',
////            'admin_email'          => '%admin_email%',
////            'account_mail'         => '%admin_email%',
////            'door_time_start'      => '%start%',
////            'door_time_finish'     => '%finish%'
//          ),
//          'add_missing'  => array(
//            'change' => array(
//              'sepa_name'            => '%name_court%',
//              'name_bank'            => '%name_court%',
//              'email_subject_prefix' => '%name_court%',
//              'notify_email'         => '%admin_email%',
//              'admin_email'          => '%admin_email%',
//              'account_mail'         => '%admin_email%',
//              'door_time_start'      => '%start%',
//              'door_time_finish'     => '%finish%'
//            ),
//          )
//        ),
        'letters_templates' => array(
          'type'         => 'html',
          'change_alias' => true,
          'add_missing'  => true
        ),
//        'doorcodes' => array(
//          'replace' => array(
//            'source' => 'base',
//          )
//        ),
//        'users'             => array(
//          'insert' => array(
//            'fields' => array('login', 'password', 'name', 'code'),
//            'values' => array(
//              array('admingemen', '5364f1c5d3bd95a34551287a565f3a8c', 'AdminGemen', 'AG'),
//              array('adgemen', '5364f1c5d3bd95a34551287a565f3a8c', 'AdGemen', 'AD')
//            ),
//          ),
//        )
      )
    )
  )
);
//
//$engine = new SyncTwoDB($config);
//$q      = $engine->generateQuerySyncStructure();
////D()::dvD($q);
//$engine->executingAllQuery($q);

//$q = $engine->postChangesDataTables();
//D()::dvD($q);
//$engine->executingAllQuery($q);
