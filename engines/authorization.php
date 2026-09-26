<?php


use AC\core\engines\usersEngine;

class authorization
{
  public $sv;
  /**
   * @var usersEngine
   */
  public $e;
  public $admin;
  public $user;

  /* СЕРВИС */
  public function __construct()
  {
    $this->Initialize();
  }

  function Initialize()
  {
    $this->sv = require 'authorization.config.php';
//		session_start ();

    $this->e = getEngine('users');

    $this->admin = 2;
    $this->user  = 1;
  }

  function Finalize()
  {
  }


  /* АВТОРИЗАЦИЯ */

  function Login($username, $password)
  {
    /*$file_passw = file (".htpasswd");
    foreach ($file_passw as $value) {
      $tmp = split (":", $value, 3);
      $accepted_accounts[$tmp[1]]['password'] = rtrim ($tmp[2]);
      $accepted_accounts[$tmp[1]]['status'] = $tmp[0]=='admin'?$this->admin:$this->user;
    }*/
    if ($accepted_accounts = $this->e->logIn($username, $password)) {
      //итак, стартуем сессии
      $_SESSION[$this->sv]['rights'] = $accepted_accounts['rights'];
      $_SESSION[$this->sv]['code']   = $accepted_accounts['code'];
      $_SESSION[$this->sv]['name']   = $accepted_accounts['name'];
      $_SESSION[$this->sv]['id']     = $accepted_accounts['user_id'];

      return true;
    }

    $this->LogOut();

    return false;
  }

  function LogOut()
  {
    unset($_SESSION[$this->sv]);

    return true;
  }

  function CheckSession()
  {
    if (isset($_SESSION[$this->sv]) && intval($_SESSION[$this->sv]['rights']) != 0) {
      return true;
    } else {
      return false;
    }
  }

  function CheckRights($page_access)
  {
    if (isset($_SESSION[$this->sv]) && intval($_SESSION[$this->sv]['rights']) <= $page_access) {
      return true;
    }

    return false;
  }

  function getData()
  {
    if ($this->CheckSession()) {
      $data = $_SESSION[$this->sv];
      if (file_exists($img = $this->e->getImagePath($data['id']))) {
        $data['image'] = $img;
      }

      return $data;
    }

    return false;
  }

  public function getCustomer()
  {
    return isset($_SESSION[$this->sv]) && isset($_SESSION[$this->sv]['id']) ? $_SESSION[$this->sv]['id'] : null;
  }
}