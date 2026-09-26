<?php

namespace AC\core\system\helpers;


class LogHelper
{
  public static function mailSend($email, $message, $subject): bool
  {
    $mailer = \Service::mailer();
    $mailer->isHTML();
    $mailer->Subject = $subject;
    $mailer->msgHTML($message);
    $mailer->addAddress($email);
    
    return $mailer->_mail($email);
  }
  
  public static function getMessageByPath($path, $data = []): string
  {
    return view()->render($path, $data) ?? '';
  }
}