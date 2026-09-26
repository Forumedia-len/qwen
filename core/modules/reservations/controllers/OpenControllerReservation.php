<?php

namespace AC\core\modules\reservations\controllers;


use AC\core\modules\reservations\models\OrdersModelOpenReservation;
use AC\core\modules\reservations\models\ReservationsModel;
use Service;


/** Действия бронирования открытого корта и присоединения игроков. */
class OpenControllerReservation extends CloseControllerReservation
{
  public $default_template = 'open';
  public $base_model       = 'OrdersModelOpenReservation';

  /**
   * @var OrdersModelOpenReservation
   */
  public $model;

  public function showOrderSetErrors($error_code = null)
  {
    if ($error_code == 1 || $error_code == 5) {
      $error_code  = lang('invalid_time_selection', 'message_error');
    }

    return parent::showOrderSetErrors($error_code);
  }

  /** Вывести форму присоединения
   * @return false|string
   */
  public function joinForm()
  {
    $this->setOrdersTemplate();
    if ($content = $this->model->getDataJoinForm($error_code)) {
      return $this->view->render('join_form', $content);
    } else {
      $this->view->setErrorCode($error_code);

      return $this->view->getErrorMessage();
    }
  }

  /** Присоединить игрока к игре
   * @return string
   */
  public function join()
  {
    $this->setOrdersTemplate();
    if ($this->model->joinProceed($error_code)) {
      return '<span style="font-size: 20px; font-weight: bold;">'.lang('Thank you for your booking.', 'order').'</span><br /><br />';
    } else {
      $this->view->setErrorCode($error_code);

      return $this->view->getErrorMessage();
    }
  }

  /** Вывести форму отсоединения от игры
   *
   */
  public function unJoinForm()
  {
    $this->setOrdersTemplate();
    $reservation = new ReservationsModel($this->model->main_reservation_id, false, true);
    $this->initializeModel($reservation);
    if ($this->model->current_client->client_id == $reservation->client_id) {
      return $this->view->render(
        'un_join_form',
        array(
          'model' => $this->model
        )
      );
    } else {
      $this->view->setErrorMessage('<p>'.lang('This is not your booking', 'message_error').'</p>');

      return $this->view->getErrorMessage();
    }
  }

  /** Отсоеденить игрока от игры
   *
   */
  public function unJoin()
  {
    $this->setOrdersTemplate();
    $reservation = new ReservationsModel($this->model->main_reservation_id, false, true);
    $this->initializeModel($reservation);
    if ($this->model->current_client->client_id == $reservation->client_id) {
      if ($this->model->unJoin($error_code)) {
        return '<span style="font-size: 20px; font-weight: bold;">'.lang('reserv_cancel').'</span><br /><br />';
      } else {
        $this->view->setErrorCode($error_code);

        return $this->view->getErrorMessage();
      }
    } else {
      $this->view->setErrorMessage('<p>'.lang('This is not your booking', 'message_error').'</p>');

      return $this->view->getErrorMessage();
    }
  }

  /** Форма для подтверждения бронирования
   * @return false|string
   */
  public function confirmOrderForm()
  {
    $this->model->reservation_id = Service::request()->_('reservation_id', null);
    $this->setOrdersTemplate();
    if ($this->model->checkConfirmOrder($error_message)) {
      $this->default_template = 'reservations';

      return $this->view->render(
        'confirm_form',
        array(
          'model' => $this->model
        )
      );
    } else {
      $this->view->setErrorMessage($error_message);

      return $this->view->getErrorMessage();
    }
  }

  /**
   * Подтвердить бронирование, на тачскрине и для открытых кортов
   */
  public function confirmOrder()
  {
    $this->model->reservation_id = Service::request()->_('reservation_id', null);
    $this->setOrdersTemplate();
    if ($this->model->confirmOrder($error_message)) {
      return '<p>'.lang('the hour is confirmed', 'order').'</p>';
    } else {
      $this->view->setErrorMessage($error_message);

      return $this->view->getErrorMessage();
    }
  }

}
