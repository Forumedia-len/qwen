<?php

namespace AC\core\modules\mailing\services;

use AC\core\modules\mailing\engines\LettersTemplatesEngine;
use PHPMailer\PHPMailer;
use PHPMailer\PHPMailerException;
use Service;

uses('smtp');

/**
 *  если константа определена и задан в ней путь то сохраняет письма по этому пути
 * (для unix систем должен быть доступ для записи по этому пути)
 *  используется для записи всех писем в файлы
 *  По умолчанию путь  - dirname(__FILE__) . '../../mails'
 */
defined('SAVE_EMAIL_TO_FILE') || (define('SAVE_EMAIL_TO_FILE', false));
defined('TYPE_MAIL_TEMPLATE') || (define('TYPE_MAIL_TEMPLATE', 'html'));

class Mailer extends PHPMailer
{
  private LettersTemplatesEngine $templatesEngine;
  
  public function __construct($exceptions = null)
  {
    parent::__construct($exceptions);
    $this->setTemplatesEngine();
    $this->isHTML(TYPE_MAIL_TEMPLATE === 'html');
    $this->setFrom('no-reply@' . HOST_NAME, config('app')->getProjectTitle() ?? '');
    $this->CharSet = 'UTF-8';
    $this->setLanguage(config('lang')->defaultLanguage);
  }
  
  public function setTemplatesEngine(?LettersTemplatesEngine $engine = null): void
  {
    $this->templatesEngine = $engine ?? getEngine('LettersTemplates', false);
  }
  
  public function getTemplatesEngine(): LettersTemplatesEngine
  {
    return $this->templatesEngine;
  }
  
  //собственно рассылка писем
  //$to может быть или одним адресом (строка) или несколькими (массив)
  public function dispatch($to, int $template_mode, string $template_alias, array $template_data = [], ?string $language = null): bool
  {
    if (!$this->templatesEngine->getTemplate($template_mode, $template_alias, $title, $subject, $content, $language)) {
      return false;
    }
    
    //заголовки письма
    $this->addReplyTo(Service::configDB('mail', 'admin_email'), config('app')->getProjectTitle());
    
    //подставляем значения в шаблон
    $content = $this->templatesEngine->replaceTemplateData($content, $template_data);
    if (empty($content)) {
      $content = '<p></p>';
    }
    if ($this->ContentType == self::CONTENT_TYPE_PLAINTEXT) {
      $this->Body = $content;
    } else {
      $this->msgHTML($content);
    }
    $subjectPrefix = trim((Service::configDB('mail', 'email_subject_prefix') ?? '') . ' ' . ($subject ?? ''));
    $this->Subject = $subjectPrefix;
    
    $this->clearAddresses();
    if (defined('TEST_ACTIVE_COURT') && TEST_ACTIVE_COURT) {
      $this->addAddress('developer@forumedia.com');
    } elseif (is_array($to)) {
      foreach ($to as $to1) {
        $this->addAddress($to1);
      }
    } else {
      $this->addAddress($to);
    }
    $this->_mail();
    
    return true;
  }
  
  /**
   * @throws PHPMailerException
   */
  public function sendMail($mail, $subject, $content): bool
  {
    $this->isHTML();
    $this->Subject = $subject;
    $this->msgHTML($content);
    $this->clearAddresses();
    $this->addAddress($mail);
    
    return $this->_mail();
  }
  
  public function _mail($to = null)
  {
    $mailTo = implode(';',
      (!empty($to) ? (is_array($to) ? $to : [$to]) : array_map(static fn($item) => $item[0], $this->getToAddresses())));
    if (LOCAL_SERVER && defined('SAVE_EMAIL_TO_FILE') && SAVE_EMAIL_TO_FILE !== false) {
      if ($this->preSend()) {
        Debug()::saveMail(
          $mailTo,
          $this->Subject,
          $this->MIMEBody,
          $this->MIMEHeader
        );
      }
      return true;
    }
    
    if (defined('SMTP') && SMTP && $this->Mailer !== 'smtp') {
      $this->setSmtp();
      // $this->SMTPDebug = 4;
    }
    
    if ($this->send()) {
      return true;
    }
    
    return false;
  }
  
  public function setSmtp()
  {
    $this->isSMTP();
    $this->Host       = SMTP_HOST;
    $this->Port       = SMTP_PORT;
    $this->SMTPSecure = SMTP_SECURE;
    
    if (defined('SMTP_USER') && SMTP_USER != '') {
      $this->SMTPAuth = SMTP_AUTH;
      $this->Username = SMTP_USER;
      $this->Password = SMTP_PASSWORD;
    }
    // $this->SMTPDebug = 4;
    $this->SMTPDebug = (int)SMTP_DEBUG;
  }
}