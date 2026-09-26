<?php

namespace AC\core\system\controller;

interface AuthInterface
{
  
  public function logIn();
  
  public function setSessionParameter();
  
  public function logOut();
  
  public function checkAuth(&$redirectUrl = null): bool;
  
  /**
   * Возвращает ID пользователя
   *
   * @return ?int ID пользователя(user(admin)/client) или null
   */
  public function getUserId(): ?int;

  /**
   * Возвращает проверка, что это админ
   *
   * @return bool
   */
  public function isAdmin(): bool;
  
  public function getTypeUserAndId(): string;
  
}