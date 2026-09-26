    <?php
    $base    = DB::instance('base');
    $baseDBO = $base->getDbo();
    $qs      = [];
    $list    = '';
    $list    = explode("\n", $list);
    foreach ($list as $keyList => $row) {
      $list[$keyList] = explode(';', $row);
    }
//    foreach ($baseDBO->showDataBase() as $dbName) {
//      if (str_starts_with($dbName, 'at_')) {
//        foreach ($baseDBO->getFullTableName('users', $dbName, false) as $_tableName) {
//          $tableName = '`' . $dbName . '`.`' . $_tableName . '`';
//          if (!($col = $baseDBO->query('SHOW COLUMNS FROM ' . $tableName . ' LIKE \'preferences\''))) {
//            $qs[] = "ALTER TABLE " . $tableName . " ADD COLUMN `email` VARCHAR (128) NOT NULL AFTER `password`, ADD COLUMN `created` DATETIME NULL AFTER `default`, ADD COLUMN `last_login` DATETIME NULL AFTER `created`, ADD COLUMN `preferences` LONGTEXT NULL AFTER `last_login`;";
//          }
//          $qs[] = 'UPDATE ' . $tableName . ' set email="info@forumedia.com"'./*, password=\''.password_hash('zpc69gD4T88?IiU6$', PASSWORD_BCRYPT).'\', preferences =\'\*/' where login=\'ADVSMaster!\'';
//          $qs[] = 'DELETE FROM' . $tableName . ' where login=\'adminFM\'';
//          $qs[] = 'INSERT INTO ' . $tableName . ' (`rights`, `login`, `password`, `email`, `name`, `code`, `default`, `language`, `created`, `preferences`) VALUES (\'-1\', \'adminFM\', \'' . password_hash('zpc69gD4T88?IiU6$', PASSWORD_BCRYPT) . '\', \'developer@forumedia.com\', \'AdminFM\', \'fm\', \'1\', \'de\', \''. date('Y-m-d H:i:s') .'\', \'{"useSaltPasswordHash":1}\')';
//          $qs[] = 'DELETE FROM' . $tableName . ' where login=\'MasterAdminFM\'';
//          $qs[] = 'INSERT INTO ' . $tableName . ' (`rights`, `login`, `password`, `email`, `name`, `code`, `default`, `language`, `created`, `preferences`) VALUES (\'-100\', \'MasterAdminFM\', \'' . password_hash('$wfUa1dA1CfG@z||6', PASSWORD_BCRYPT) . '\', \'developer@forumedia.com\', \'MAdminFM\', \'fm\', \'1\', \'de\', \''. date('Y-m-d H:i:s') .'\', \'{"useSaltPasswordHash":1}\')';
//        }
//        foreach ($baseDBO->getFullTableName('config', $dbName, false) as $_tableName) {
//          $tableName = '`' . $dbName . '`.`' . $_tableName . '`';
//          if (($col = $baseDBO->query('SHOW COLUMNS FROM ' . $tableName . ' LIKE \'alias\'')) && count($col)) {
//            if (empty($baseDBO->query('select alias from' . $tableName . ' where alias="use_2FA_admin"'))) {
//              $showTypeColumn =  $baseDBO->query('SHOW COLUMNS FROM ' . $tableName . ' LIKE \'type\'');
//              $qs[]= "INSERT INTO" . $tableName . " (".($showTypeColumn ? 'type, ' : '')."alias, value) VALUES (".($showTypeColumn ? '\'email\', ' : '')."'use_2FA_admin', '0') ";
//            }
//          }
//        }
//        foreach ($baseDBO->getFullTableName('clients', $dbName, false) as $_tableName) {
//          $tableName = '`' . $dbName . '`.`' . $_tableName . '`';
//          if ($baseDBO->query('SHOW COLUMNS FROM ' . $tableName . ' LIKE \'mode\'') && !$baseDBO->query('SHOW COLUMNS FROM ' . $tableName . ' LIKE \'preferences\'')) {
//            $qs[] = 'ALTER TABLE ' . $tableName . ' ADD COLUMN `preferences` LONGTEXT NULL;';
//          }
//        }
//
//      }
//    }
    foreach ($list as $item) {
      if (in_array($item[0], $baseDBO->showDataBase())) {
//        $qs[] = 'UPDATE ' . '`' . $item[0] . '`.`' . trim($item[1]) . 'users`' . ' SET password=\'' . password_hash('sA644gfjH!&5D5h3?',
//            PASSWORD_BCRYPT) . '\', preferences =\'{"useSaltPasswordHash":1}\' where login=\'ADVSMaster!\'';
//        $qs[] = 'UPDATE ' . '`' . $item[0] . '`.`' . trim($item[1]) . 'config`' . ' SET value=\'1\' where alias=\'use_2FA_admin\'';
      }
    }
//    Debug($qs);
    foreach ($qs as $q) {
      if (!$baseDBO->query($q, [], false)) {
//        Debug()::dEH('<p style="color: red">' . $q . '</p>');
      }
    }
//    Debug('ok');
//    echo(Debug()::eTv($out));