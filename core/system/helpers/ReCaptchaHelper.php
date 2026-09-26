<?php

namespace AC\core\system\helpers;



use AC\app\locators\Service;
use const RECAPTCHA_CLIENT_KEY;
use const RECAPTCHA_SCORE;
use const USE_RECAPTCHA_V4;

class ReCaptchaHelper
{
  public static function checkReCaptcha($secretKey = 'g-recaptcha-response', $scope = RECAPTCHA_SCORE): bool
  {
    if (static::useReCaptcha()) {
      $response = json_decode(
        file_get_contents(
          "https://www.google.com/recaptcha/api/siteverify?secret=" . RECAPTCHA_SERVER_KEY . "&response=" . Service::request()->_post($secretKey)
        )
      );
      return $response->success == true && $response->score >= $scope;
    }

    return true;
  }

  public static function getReCaptchaScript($action = 'homepage', $delay = 60000)
  {
    return static::useReCaptcha()
      ? useLayout()::render('recaptcha', ['action' => $action, 'delay' => $delay], 'common')
      : '';
  }

  public static function useReCaptcha()
  {
    return config('cookieApply')->checkUseOtherCookies() && defined('USE_RECAPTCHA_V4') && USE_RECAPTCHA_V4;
  }
}