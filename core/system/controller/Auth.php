<?php

namespace AC\core\system\controller;

use Service;

abstract class Auth extends BaseController implements AuthInterface
{
  /**
   * @var string Session Parameter
   */
  protected $sessionParameter = SESSION_COURT . '_session';
  
  public function __construct($action = false, $runController = true)
  {
    $this->setSessionParameter();
    parent::__construct($action, $runController);
  }
  
  abstract public function logIn();
  
  abstract public function setSessionParameter();
  
  abstract public function getUserId(): ?int;
  
  public function logOut()
  {
    $defaultUrl = Service::structure()->getDefaultPageData('href') ?: '';
    
    return Service::redirect()->redirect(site_url($defaultUrl))->send();
  }
  
  public function checkAuth(&$redirectUrl = null): bool
  {
    return Service::session()->exists($this->sessionParameter);
  }
  
  /**
   * Возвращает данные авторизованного пользователя
   *
   * @return ?array Данные пользователя, null если не авторизован
   */
  public function getUserData(): ?array
  {
    if ($this->checkAuth()) {
      return Service::session()->get($this->sessionParameter);
    }
    
    return null;
  }
  
  public function checkAuthorization(): bool
  {
    return $this->checkAuth();
  }
  
  public function getTypeUserAndId(): string
  {
    return 'system';
  }
  
  public function isBarClient(): bool
  {
    return false;
  }
}