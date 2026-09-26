<?php
/**
 * @todo нужно поправить загрузку фотографий (сейчас возможно загружать только jpg)
 */

namespace AC\core\engines;

use AC\core\system\db\Query;
use AC\core\system\helpers\PasswordHelper;
use ImageResize;

useClass('classes/image_resize.php');


class UsersEngine extends ImageResize
{

  function initialize()
  {
    parent::__construct(40, 40, false);
  }

  //добавить
  function insertUser($rights, $login, $password, $email, $code, $name, $image)
  {
    $q      = 'insert into ' . Query::tableName('users') . ' 
                            set 
                                rights = :rights,
                                login = :login,
                                password = :password,
                                email = :email,
                                code = :code,
                                name = :name,
                                preferences = :preferences,
                                created = "' . date('Y-m-d H:i:s') . '"';
    $params = [
      'rights'      => $rights,
      'login'       => $login,
      'password'    => $this->hashPassword($password),
      'email'       => $email,
      'code'        => $code,
      'preferences' => $this->mandatoryPreferences(),
      'name'        => $name
    ];

    Query::sqlQuery($q, $params, false);
    $new_user_id = Query::getLastId();
    $this->saveImage($image, $new_user_id);
  }

  //изменить
  function changeUser($rights, $login, $password, $email, $code, $name, $image, $user_id)
  {
    $this->saveImage($image, $user_id);

    $q = 'update ' . Query::tableName('users') . ' set 
                                rights = :rights,
                                login = :login,
                                ' . (!empty($password) ? 'password = "' . $this->hashPassword($password) . '", ' : '') . '
                                email = :email,
                                code = :code,
                                ' . (!empty($password) ? 'preferences = \'' . $this->mandatoryPreferences($user_id) . '\', ' : '') . '
                                name = :name where user_id = :user_id';

    $params = [
      'rights'   => $rights,
      'login'    => $login,
      'email'    => $email,
      'code'     => $code,
      'name'     => $name,
      'user_id'  => $user_id
    ];
    Query::sqlQuery($q, $params, false);
  }

  protected function mandatoryPreferences($user_id = null)
  {
    $data = ['useSaltPasswordHash' => 1];
    if ($user_id && $this->getUser($user_id, $userData)) {
      $data = array_merge($data, json_decode($userData['preferences'], true) ?? []);
    }

    return json_encode($data);
  }

  //удалить
  function removeUser($user_id)
  {
    if ($this->getUser($user_id, $row)) {
      if (isset($row['image'])) {
        unlink($this->getImagePath($row['user_id']));
      }
      Query::sqlQuery('delete from ' . Query::tableName('users') . ' where  user_id = ' . $user_id, [], false);
    }
  }

  function getUsers(&$result)
  {
    $temp = Query::sqlQuery('select * from ' . Query::tableName('users') . ' order by rights, code');
    if (!empty($temp)) {
      $result = array();
      foreach ($temp as $row) {
        if (file_exists($img = $this->getImagePath($row['user_id']))) {
          $row['image'] = $img;
        }
        $result[] = $row;
      }

      return true;
    } else {
      $result = null;

      return false;
    }
  }

  function logIn($login, $password)
  {
    if ($userData = $this->getDataUser($login)) {
      $preferences = json_decode($userData['preferences'], true);
      if ((!isset($preferences['useSaltPasswordHash']) && md5($password) == $userData['password'])
        || (isset($preferences['useSaltPasswordHash']) && $preferences['useSaltPasswordHash']) && $this->checkHashPassword($password,
          $userData['password'])) {
        return $userData;
      }
    }

    return false;
  }

  protected function getDataUser($login)
  {
    return Query::sqlQuery('select * from ' . Query::tableName('users') . ' where login = ?', [$login], true, ['onlyOne' => true]);
  }

  public function getUser($user_id, &$result = [])
  {
    $temp = $user_id ? (Query::sqlQuery('select * from ' . Query::tableName('users') . ' where user_id = ?', [(int)$user_id])) : null;
    if (!empty($temp)) {
      if ($result = $temp[0]) {
        if (file_exists($img = $this->getImagePath($result['user_id']))) {
          $result['image'] = $img;
        }
      }

      return true;
    } else {
      $result = [];

      return false;
    }
  }

  function getUserByCode($code)
  {
    $temp = Query::sqlQuery('select * from ' . Query::tableName('users') . ' where code = "' . $code . '" ');
    if (!empty($temp)) {
      if ($result = $temp[0]) {
        if (file_exists($img = $this->getImagePath($result['user_id']))) {
          $result['image'] = $img;
        }
      }

      return $result;
    }

    return false;
  }

  protected function saveImage($image, $user_id)
  {
    if ($image !== null) {
      if (!file_exists(trim($this->getImagePath(), DIRECTORY_SEPARATOR))) {
        mkdir($this->getImagePath(), 0700, true);
      }
      copy($image, $this->getImagePath($user_id));
      $this->createImage($this->getImagePath($user_id));
    }
  }

  function getImagePath($user_id = null)
  {
    return pathAs(paths()->getAssetsDir('images/users/' . ($user_id ? $user_id . '.jpg' : '')));
  }

  protected function checkHashPassword($password, $hash)
  {
    return password_verify($password, $hash);
  }

  protected function hashPassword($password)
  {
    return password_hash($password, PASSWORD_BCRYPT);
  }

  public function checkPasswordValidation($password, &$errors)
  {
    $errors = [];

    if (strlen($password) < 16) {
      $errors[] = lang('error_attribute_min_value', 'message_error', ['attribute' => lang('Password', 'users'), 'value' => 16]);
    }
    $wildcardChar = $lowerChar = $upperChar = $numericChar = $otherChar = 0;
    for ($i = 0; $i <= strlen($password); $i++) {
      if (in_array($password[$i], PasswordHelper::getSpecialCharacters())) {
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

    if ($wildcardChar < 3) {
      $errors[] = lang('The minimum number of special characters', 'message_error', ['attribute' => lang('Password', 'users'), 'value' => 3]);
    }

    if (!$numericChar || !$lowerChar || !$upperChar) {
      $errors[] = lang('The password must contain uppercase, lowercase letters and numbers', 'message_error');
    }
    return !count($errors);
  }

  public function checkEmailUnique($email)
  {
    return !count(Query::sqlQuery('select email from ' . Query::tableName('users') . ' where email=:email', ['email' => $email]));
  }

  public function checkLoginUnique($login)
  {
    return !count(Query::sqlQuery('select email from ' . Query::tableName('users') . ' where login=:login', ['login' => $login]));
  }


  public function lastLogin($userId)
  {
    Query::sqlQuery('update ' . Query::tableName('users') . ' set last_login="' . date('Y-m-d H:i:s') . '" where user_id=' . $userId);
  }

  public function setPreference($user_id)
  {
//    $pre
  }

}