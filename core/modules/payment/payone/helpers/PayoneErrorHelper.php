<?php

namespace AC\core\modules\payment\payone\helpers;

class PayoneErrorHelper
{
  public static function getError($code): string
  {
    return match (true) {
      self::checkCode($code) => self::errors()[$code],
      default                => lang('The unknown error, please contact the administrator', 'message_error'),
    };
  }

  private static function errors(): array
  {
    return [
      '1'                      => lang('Incomplete data has been submitted', 'message_error', ['error' => 'Error:#1']),
      '2'                      => lang('Incomplete data has been submitted', 'message_error', ['error' => 'Error:#2']),
      '3'                      => lang('Bookings are not found', 'message_error', ['error' => 'Error:#3']),
      '4 '                     => lang('Incomplete data has been submitted', 'message_error', ['error' => 'Error:#4']),
      '5'                      => lang('The answer is wrong', 'message_error', ['error' => 'Error:#5']),
      '6'                      => lang('Data error', 'message_error', ['error' => 'Error:#6']),
      '7'                      => lang('The payment was not submitted successfully', 'message_error', ['error' => 'Error:#7']),
      '8'                      => lang('An internal error has occurred. If the amount is not credited, please contact your administrator',
        'message_error', ['error' => 'Error:#8']),
      'error_two_transactions' => lang('error_two_transactions', 'message_error'),
      'time_booked'            => lang('time_booked', 'message_error'),
      'You canceled the order' => lang('You canceled the order', 'message_error'),
    ];
  }

  public static function checkCode($code): bool
  {
    return isset(self::errors()[$code]);
  }
}