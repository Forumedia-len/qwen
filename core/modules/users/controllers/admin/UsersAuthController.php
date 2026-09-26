<?php

namespace AC\core\modules\users\controllers\admin;

use AC\core\engines\UsersEngine;
use AC\core\system\controller\Auth;
use AC\core\system\helpers\EmailHelper;
use Service;

/**
 * Контроллер аутентификации пользователей в административной панели
 *
 * todo вынести весь js в отдельный файл (формы 2FA, _form)
 */
class UsersAuthController extends Auth
{
  /**
   * @var string Имя параметра сессии для хранения данных пользователя
   */
  protected $sessionParameter;
  
  /**
   * @var UsersEngine Экземпляр движка работы с пользователями
   */
  protected $engine;
  
  /**
   * @var bool Флаг использования базовой модели
   */
  protected $useBaseModel = false;
  
  /**
   * @var string Класс базового движка
   */
  protected $baseEngine = 'UsersEngine';
  
  /**
   * Обработка входа пользователя
   *
   * Проверяет данные формы входа, управляет блокировкой после нескольких неудачных попыток,
   * отправляет уведомления об атаках и выполняет двухфакторную аутентификацию при необходимости.
   *
   * @return mixed Результат рендеринга шаблона или перенаправление
   */
  public function logIn()
  {
    $error = null;
    Service::session()->delete($this->sessionParameter);
    $this->unsetBlockingAuth();
    $noa = Service::session()->get('noa') ?: 0;
    if ($noa > 0) {
      $sleep = 2;
    }
    
    if (!$this->checkBlockingAuth() && Service::request()->isPost()) {
      $username = Service::request()->_post('username');
      $password = Service::request()->_post('password');
      if ($username && $password && $accepted = $this->engine->logIn($username, $password)) {
        Service::session()->set($this->sessionParameter, [
          'rights'   => $accepted['rights'],
          'code'     => $accepted['code'],
          'name'     => $accepted['name'],
          'email'    => $accepted['email'] ?: Service::configDB('email', 'admin_email'),
          'language' => $accepted['language'] ?? config('lang')->getDefault(),
          'id'       => $accepted['user_id'],
          'ct'       => strtotime('now')
        ]);
        $this->unsetBlockingAuth(true);
        return $this->checkUse2FA()
          ? Service::redirect()->redirect(site_url(Service::structure()->getPageHrefByKey('login_2FA')))->send()
          : $this->redirectDefaultHref();
      }
      $erULog   = Service::session()->get('erULog') ?? [];
      $erULog[] = [$username, $password, date('Y-m-d H:i:s')];
      Service::session()->set('erULog', $erULog);
      $error = lang('Error! Username or Password incorrect!', 'message_error');
      $noa++;
      Service::session()->set('noa', $noa);
    }
    
    if ($noa > config('auth')->numberOfAttempts()) {
      $sleep = 0;
      if (!Service::session()->exists('blockingAuth')) {
        Service::session()->set('blockingAuth', strtotime('+' . config('auth')->lockTimeAuth() . ' second'));
        $this->mailSend('developer@forumedia.com', '_errorCheckUser', 'Попытка перебора пароля с сайта ' . base_url(),
          ['username' => $username, 'host' => base_url(), 'userLog' => Service::session()->get('erULog')]);
        $this->mailSend('info@forumedia.com', '_errorCheckUser.de', 'Auf der Webseite ' . base_url() . ' wurde das Passwort mehrmals eingegeben.',
          ['username' => $username, 'host' => base_url(), 'userLog' => Service::session()->getWithDelete('erULog')]);
      }
      $error = lang('You have made more than 5 unsuccessful', 'message_error',
        [
          'number' => config('auth')->numberOfAttempts(),
          'time'   => '<strong><span id="time-box-login" data-second="' . (Service::session()->get('blockingAuth') - time()) . '"></span></strong>'
        ]);
    }
    
    if (isset($sleep) && $sleep) {
      sleep($sleep);
    }
    
    return $this->render('auth/default',
      ['action' => Service::structure()->getPageHrefByKey('login'), 'error' => $error, 'blockingAuth' => Service::session()->get('blockingAuth')]);
  }
  
  /**
   * Проверяет, заблокирован ли доступ к аутентификации
   *
   * @return bool true, если блокировка активна
   */
  protected function checkBlockingAuth()
  {
    if (($blocking = Service::session()->get('blockingAuth')) && $blocking >= time()) {
      return true;
    }
    
    return false;
  }
  
  /**
   * Сбрасывает параметры блокировки аутентификации
   *
   * @param bool $unset Принудительное удаление, даже если блокировка истекла
   */
  protected function unsetBlockingAuth($unset = false)
  {
    if ($unset || (($blocking = Service::session()->get('blockingAuth')) && $blocking < time())) {
      Service::session()->delete('blockingAuth');
      Service::session()->delete('noa');
      Service::session()->delete('erULog');
    }
  }
  
  /**
   * Генерирует уникальный секретный ключ для сессии
   *
   * @return string Хэшированный ключ на основе данных сессии
   */
  protected function getSecretKey()
  {
    return md5($this->getSessionValue('name')
      . '_' . $this->getSessionValue('id')
      . '_' . $this->getSessionValue('code')
      . '_' . $this->getSessionValue('email')
      . '_' . $this->getSessionValue('ct')
    );
  }
  
  /**
   * Проверяет необходимость использования 2FA
   *
   * @return bool true, если 2FA включена и не локальный сервер
   */
  protected function checkUse2FA()
  {
    return (bool)(Service::configDB('email', 'use_2FA_admin') && (!LOCAL_SERVER || (defined('USE_2FA_LOC') && USE_2FA_LOC)));
  }
  
  /**
   * Перенаправляет пользователя на главную страницу после успешного входа
   *
   * @return mixed Результат перенаправления
   */
  protected function redirectDefaultHref()
  {
    $this->engine->lastLogin($this->getUserId());
    config('lang')->setLang($this->getSessionValue('language') ?? null);
    
    return Service::redirect()->redirect(site_url(Service::structure()->getDefaultPageData('href')))->send();
  }
  
  /**
   * Отправляет код верификации на email
   *
   * Генерирует случайный 6-значный код и отправляет его пользователю.
   */
  protected function sendVerifyCode()
  {
    $this->delSessionValue($this->getSecretKey());
    $verifyCode = str_pad(mt_rand(0, pow(10, 6) - 1), 6, '0', STR_PAD_LEFT);;
    $this->setSessionValue('ct', strtotime('now'));
    $this->setSessionValue($this->getSecretKey(), false);
    $this->setSessionValue('fa_code_2', $verifyCode);
    // todo письмо только на немецком языке
    $this->mailSend($this->getSessionValue('email'), '_2FA_mail', lang('Verification code', 'users'), ['verifyCode' => $verifyCode]);
  }
  
  /**
   * Отправляет электронное письмо с шаблоном
   *
   * @param string $email    Адрес получателя
   * @param string $template Шаблон письма
   * @param string $subject  Тема письма
   * @param array  $data     Данные для рендера шаблона
   */
  protected function mailSend($email, $template, $subject, $data = [])
  {
    $mailer = Service::mailer();
    $mailer->isHTML();
    $mailer->Subject = $subject;
    $mailer->msgHTML(view()->render('mail/' . $template, $data));
    $mailer->addAddress($email);
    $mailer->_mail($email);
  }
  
  /**
   * Проверяет введенный код верификации
   *
   * @return mixed Результат рендеринга или перенаправление
   */
  public function verifyCode()
  {
    $error = null;
    if (Service::request()->isAJAX() && Service::request()->_post('sendNewCode')) {
      $this->sendVerifyCode();
      echo json_encode([
        'timeRefreshSend' => ($this->getSessionValue('ct') + config('auth')->numberOfSecondsOfCodeValidity2FA()) - time(),
      ]);
      die;
    }
    if (!$this->checkSessionValue($this->getSecretKey())) {
      $this->sendVerifyCode();
    }
    if (Service::request()->_post('check', false)) {
      if (($this->getSessionValue('ct') + config('auth')->numberOfSecondsOfCodeValidity2FA()) > time() && Service::request()->_post('token') == $this->getSessionValue('fa_code_2')) {
        $this->setSessionValue($this->getSecretKey(), true);
        $this->unsetSessionKeys2FA();
        return $this->redirectDefaultHref();
      } else {
        $this->setSessionValue('fa_cnt_f_2', ($this->getSessionValue('fa_cnt_f_2') ?? 0) + 1);
        $error = lang('You have entered an invalid code', 'message_error');
      }
    }
    
    if (($fa_cnt_f_2 = $this->getSessionValue('fa_cnt_f_2')) && $fa_cnt_f_2 >= config('auth')->numberOfAttempts2FA()) {
      return Service::redirect()->redirect(site_url(Service::structure()->getPageHrefByKey('login')))->send();
    }
    
    return $this->render('auth/default',
      [
        'action'          => Service::structure()->getPageHrefByKey('login_2FA'),
        'actionBack'      => Service::structure()->getPageHrefByKey('login'),
        'error'           => $error,
        'typeAuth'        => '2FA',
        'timeRefreshSend' => ($this->getSessionValue('ct') + config('auth')->numberOfSecondsOfCodeValidity2FA()) - time(),
        'email'           => EmailHelper::maskEmail($this->getSessionValue('email'), 3, 3)
      ]);
  }
  
  /**
   * Удаляет временные ключи сессии 2FA
   */
  protected function unsetSessionKeys2FA()
  {
    foreach (['fa_code_2', 'fa_cnt_f_2'] as $key) {
      $this->delSessionValue($key);
    }
  }
  
  /**
   * Выход пользователя из системы
   */
  public function logOut()
  {
    Service::session()->delete($this->sessionParameter);
    config('lang')->setLang();
    
    return Service::redirect()->redirect(site_url(Service::structure()->getPageHrefByKey('login')))->send();
  }
  
  /**
   * Проверяет статус аутентификации пользователя
   *
   * @param string|null &$redirectUrl URL для перенаправления, если аутентификация не пройдена
   * @return bool true, если пользователь авторизован
   */
  public function checkAuth(&$redirectUrl = null): bool
  {
    $sessionData = Service::session()->get($this->sessionParameter);
    if ($sessionData && isset($sessionData['rights']) && isset($sessionData['id']) && intval($sessionData['rights']) != 0) {
      if (!$this->checkUse2FA() || (isset($sessionData[$this->getSecretKey()]) && $sessionData[$this->getSecretKey()])) {
        return true;
      } else {
        $redirectUrl = Service::structure()->getPageHrefByKey('login_2FA');
        if (Service::url()->getPath(false) == Service::structure()->getPageHrefByKey('login')) {
          $redirectUrl = Service::structure()->getPageHrefByKey('login');
        }
      }
    } else {
      $redirectUrl = Service::structure()->getPageHrefByKey('login');
    }
    
    return false;
  }
  
  /**
   * Проверяет права доступа пользователя
   *
   * @param int $page_access Требуемый уровень прав
   * @return bool true, если права достаточны
   */
  public function checkRights($page_access)
  {
    $sessionData = Service::session()->get($this->sessionParameter);
    if ($sessionData && intval($sessionData['rights']) <= $page_access) {
      return true;
    }
    
    return false;
  }
  
  /**
   * {inheritdoc}
   */
  public function getUserData(): ?array
  {
    if (($data = parent::getUserData()) && file_exists($img = $this->engine->getImagePath($data['id']))) {
      $data['image'] = $img;
    }
    
    return $data;
  }
  
  /**
   * Возвращает ID пользователя
   *
   * @return ?int ID пользователя или null
   */
  public function getUserId(): ?int
  {
    return $this->getSessionValue('id');
  }
  
  /**
   * Устанавливает имя параметра сессии
   */
  public function setSessionParameter()
  {
    $this->sessionParameter = config('auth')->sessionAdminParams;
  }
  
  /**
   * Получает значение из сессии по ключу
   *
   * @param string $key Ключ параметра
   * @return mixed Значение или null
   */
  protected function getSessionValue($key)
  {
    $sessionData = Service::session()->get($this->sessionParameter);
    
    return $sessionData && isset($sessionData[$key]) ? $sessionData[$key] : null;
  }
  
  /**
   * Устанавливает значение в сессию
   *
   * @param string $key   Ключ параметра
   * @param mixed  $value Значение
   */
  protected function setSessionValue($key, $value)
  {
    $sessionData       = Service::session()->get($this->sessionParameter);
    $sessionData[$key] = $value;
    Service::session()->set($this->sessionParameter, $sessionData);
  }
  
  /**
   * Проверяет существование ключа в сессии
   *
   * @param string $key Ключ параметра
   * @return bool true, если ключ существует
   */
  protected function checkSessionValue($key)
  {
    return isset(Service::session()->get($this->sessionParameter)[$key]);
  }
  
  /**
   * Удаляет ключ из сессии
   *
   * @param string $key Ключ параметра
   */
  protected function delSessionValue($key)
  {
    $sessionData = Service::session()->get($this->sessionParameter);
    if (isset($sessionData[$key])) {
      unset($sessionData[$key]);
    }
    Service::session()->set($this->sessionParameter, $sessionData);
  }
  
  /**
   * Устанавливает контекст представления
   */
  protected function setView($key = 'login')
  {
    parent::setView($key);
  }
  
  public function isAdmin(): bool
  {
    return true;
  }
  
  public function getTypeUserAndId(): string
  {
    return 'admin|' . $this->getUserId() . '|' . parent::getTypeUserAndId();
  }
}
