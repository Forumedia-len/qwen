<?php

namespace AC\core\modules\reservations\exceptions;

use AC\core\system\exceptions\http\RequestValidationException;

/** Недоступная дата бронирования, для которой контроллер показывает обычную ошибку модуля. */
class ReservationDateUnavailableException extends RequestValidationException
{
  /** Создать ошибку доступности без сохранения исходного значения запроса. */
  public function __construct()
  {
    parent::__construct('date', 'request', ['Reservation date is outside the available calendar interval.']);
  }
}
