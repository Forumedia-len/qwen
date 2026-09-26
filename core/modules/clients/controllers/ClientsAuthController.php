<?php

namespace AC\core\modules\clients\controllers;

use AC\core\engines\ClientsEngine;
use AC\core\system\controller\Auth;

use Service;

class ClientsAuthController extends Auth
{
  /**
   * @var ClientsEngine
   */
  protected $engine;
  
  protected $useBaseModel = false;
  
  protected $baseEngine = 'ClientsEngine';
  
  public function logIn()
  {
    // TODO: Implement logIn() method.
  }
  
  public function logOut()
  {
    $this->engine->logOut();
    
    Service::session()->delete($this->sessionParameter);
    
    Service::redirect()->redirect(site_url(Service::structure()->getPageHrefByKey('login')))->send();
  }
  
  public function setSessionParameter()
  {
    $this->sessionParameter = config('auth')->sessionClientParams;
  }
  
  public function checkAuthorization(): bool
  {
    return $this->engine->checkAuthorization();
  }
  
  public function isBarClient(): bool
  {
    // @todo: переделать чтобы подтягивался клиент и из него была проверка
    return $this->engine->isBar();
  }
  
  public function getUserId(): ?int
  {
    // TODO: пока если bar(гость) - то 0
    $id = Service::session()->get($this->sessionParameter);
    
    return is_string($id) ? 0 : $id;
  }
  
  public function isAdmin(): bool
  {
    return false;
  }
  
  public function getTypeUserAndId(): string
  {
    return sprintf("client|%d|%s", ($this->getUserId() ? (string)$this->getUserId() : 'guest'), parent::getTypeUserAndId());
  }
}