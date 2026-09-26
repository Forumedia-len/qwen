<?php

namespace AC\mapi\act\repositories\workWith;

use AC\mapi\act\repositories\BaseRepository;
use PDO;

/**
 * Проверяем на наших админов и заполняем верные данные
 * ADVSMaster! : sA644gfjH!&5D5h3?
 * adminFM : zpc69gD4T88?IiU6$
 * MasterAdminFM : $wfUa1dA1CfG@z||6
 */
class DefaultUsers extends BaseRepository
{
  protected function process(): void
  {
    foreach ($this->getDefaultUsers() as $user) {
      if (!$this->db->fulfillRequestToDataBase(
        'select count(u.user_id) from '
        . $this->db->generateTableName('users') . ' u where u.login=\'' . $user[0] . '\'',
        [],
        true,
        ['style' => PDO::FETCH_NUM, 'onlyOne' => true]
      )[0]) {
        $this->query[] = $this->gQDBS->generateInsertData(
          'users',
          [
            'fields' => ['rights', 'login', 'password', 'email', 'name', 'code', 'default', 'language', 'created', 'preferences'],
            'values' => [[$user[4], $user[0], $user[1], $user[2], $user[5], 'fm', '1', 'de', date('Y-m-d H:i:s'), $user[3]]]
          ]
        );
      } else {
        $this->query[] = $this->gQDBS->generateUpdateData('users', [
          'where' => ['login' => $user[0]],
          'set'   => ['rights' => $user[4], 'password' => $user[1], 'email' => $user[2], 'default' => '1', 'preferences' => $user[3]]
        ], 'u');
      }
    }
  }
  
  private function getDefaultUsers(): array
  {
    return [
      [
        'ADVSMaster!',
        password_hash('sA644gfjH!&5D5h3?', PASSWORD_BCRYPT),
        'info@forumedia.com',
        '{"useSaltPasswordHash":1}',
        '-1',
        'AdminVS'
      ],
      [
        'adminFM',
        password_hash('zpc69gD4T88?IiU6$', PASSWORD_BCRYPT),
        'developer@forumedia.com',
        '{"useSaltPasswordHash":1}',
        '-1',
        'AdminFM'
      ],
      [
        'MasterAdminFM',
        password_hash('$wfUa1dA1CfG@z||6', PASSWORD_BCRYPT),
        'developer@forumedia.com',
        '{"useSaltPasswordHash":1}',
        '-100',
        'MAdminFM'
      ]
    
    ];
  }
}