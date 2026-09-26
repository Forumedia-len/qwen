<?php

namespace AC\core\modules\reservations\controllers;

use AC\core\modules\reservations\helpers\TmpBlockingHelper;
use AC\core\system\helpers\JsonHelper;
use AC\core\modules\reservations\models\LightModelOrder;
use AC\core\modules\reservations\models\OrdersModelReservation;
use AC\core\modules\reservations\models\TicketsModelOrder;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use Service;


/** Действия бронирования закрытого корта, света и абонементов. */
class CloseControllerReservation extends ReservationsController
{
  public $default_template = 'close';
  public $base_model = 'OrdersModelReservation';

  /**
   * @var OrdersModelReservation
   */
  public $model;

  /** Показать заказ бронирования
   *
   */
  public function showOrder()
  {
    $this->setOrdersTemplate();
    $check = true;
    if (!TmpBlockingHelper::checkOrderBlock($this->model->getAreaId(), $this->model->getDate(), $this->model->getTimes(),
      $this->model->getClientId())) {
      $check      = false;
      $error_code = lang('Reserved block order', 'message_error');
    } else {
      TmpBlockingHelper::setOrderBlock($this->model->getAreaId(), $this->model->getDate(), $this->model->getTimes(),
        ['client_id' => $this->model->getClientId()]);
    }
    if ($check && $content = $this->getContentShowOrder($error_code)) {
      return $this->view->render('show_order', $content);
    } else {
      return $this->showOrderSetErrors($error_code);
    }
  }

  public function getContentShowOrder(&$error_code)
  {
    return $this->model->getOrder($error_code);
  }

  /** Задать основные параметры страницы
   */
  public function setOrdersTemplate()
  {
    $this->view->title      = $this->getTitle();
    $this->view->h1         = $this->getTitle();
    $this->view->_back_href = 'reservations.php?action=showReservations&type_id=' . $this->model->type_id . '&sport_id=' . $this->model->sport_id . '&date=' . $this->model->date . '&page=' . $this->model->page . '&area_id=' . $this->model->area_id . ((defined(
          'DEFAULT_TAGESANSICHT_WOCHENANSICHT'
        ) && (DEFAULT_TAGESANSICHT_WOCHENANSICHT == 'WOCHENANSICHT')) ? '&week=1' : '');
    $this->view->_class     = 'order-success';
  }

  public function showOrderSetErrors($error_code = null)
  {
    $this->view->setErrorCode($error_code);
    $this->view->useH1 = false;

    return 'date/time unavailable/ordered [' . $this->view->getErrorMessage() . ']';
  }

  protected function prefixTitle()
  {
    return '';
  }

  /** Процесс обработки заказа
   *
   * @return false|string
   */
  public function proceedOrder()
  {
    $this->model->prepayment = $this->admin && $this->model->prepayment === null ? 1 : $this->model->prepayment;
    $this->setOrdersTemplate();
    if (!$this->admin && $this->model->current_client->encash == 2 && !in_array($this->model->prepayment, [2, 3])) {
      // если у клиента стоит что он платит через лицевой счет, может платить только с него или с paypal
      $error_text = lang(
        'you_are_not_activated_for_invoice_direct_debit_payments',
        'message_error' . ($this->model->areas_types->alias == 'mc_arena' ? '_mc_arena' : ''),
        ['email' => Service::configDB('email', 'admin_email')]
      );

      $this->view->setErrorMessage('<p>' . $error_text . '</p>');
      $times = '';
      foreach ($this->model->times as $time) {
        $times .= '&time[' . htmlspecialchars($time) . ']=1';
      }
      $this->view->_back_href = 'reservations.php?action=showOrder&area_id=' . $this->model->area_id . '&type_id=' . $this->model->type_id . '&sport_id=' . $this->model->sport_id . '&date=' . $this->model->date . '&page=' . $this->model->page . $times;
    } else {
      /** проверка на допустимый способ оплаты  0-BAR, 1-RE счет, 2 - GH, 3-PP, 4-EC */
      if (in_array($this->model->prepayment, [0, 1, 2, 3, 4])) {
        // если наличкой то статус заказа по умолчанию незавершен
        $this->model->setPayStatus((int)($this->model->prepayment != 0));
        $this->model->encash     = $this->model->prepayment;
        $this->model->online_pay = false;
        $render_file             = 'proceed_order';
        if ($this->model->prepayment == 3) {
          // если платят через онлайн-оплату (PayPal / Payone)
          if (!config('payment')->useOnlinePayment()) {
            $this->view->setErrorCode(lang('not all input data presented', 'message_error'));
            return $this->view->getErrorMessage();
          }
          $this->model->encash     = 3;
          $this->model->online_pay = true;
          $this->view->_class      = "pp_loading";
          $render_file             = config('payment')->usePayonePayment() ? 'po_form' : 'pp_form';
        }
        if ($content = $this->model->proceed($error_code)) {
          $content['prepayment'] = $this->model->prepayment;
          if (in_array($render_file, ['po_form', 'pp_form'])) {
            echo $this->view->render($render_file, $content);
            exit();
          }

          return !$this->admin
            ? $this->view->render($render_file, $content)
            : lang('Reservation entered!', 'order');
        }
        $this->view->setErrorCode($error_code);
      } else {
        $this->view->setErrorCode(lang('not all input data presented', 'message_error'));
      }
    }

    return $this->view->getErrorMessage();
  }

  /** Процесс обработки включения света
   * todo поправить снятие денег при включении света. если платил с гутхабен и пайпал
   */
  public function lightOrder()
  {
    $checkOpen          = $this->model->open;
    $this->model        = new LightModelOrder();
    $this->initializeModel($this->model);
    $this->model->open = $checkOpen;
    $this->setOrdersTemplate();

    if (($this->model->light_state !== null && (int)$this->model->light_state == 1) || ($this->model->heating_state !== null && (int)$this->model->heating_state == 1) || ($this->model->net_state !== null && (int)$this->model->net_state == 1)) {
      if ($this->model->proceed($error_code)) {
        return '<p>' . lang('The light/heating is switched on for you. Have fun playing!', 'order') . '</p>';
      } else {
        $this->view->setErrorCode($error_code);

        return '<p>' . lang('Failed!', 'order') . '</p><br>' . $this->view->getErrorMessage();
      }
    } else {
      // начальная форма включения света
      if ($content = $this->model->getOrder($error_code)) {
        $this->view->setTemplate('reservations/');

        return $this->view->render('light_order', $content);
      } else {
        $this->view->setErrorCode($error_code);

        return $this->view->getErrorMessage();
      }
    }
  }

  /** Удалить бронирование
   *
   * @return string
   */
  protected function removeOrder()
  {
    $this->setOrdersTemplate();
    if ($this->model->removeOrder($error_message)) {
      return '<p>' . lang('reserv_cancel') . '</p>';
    } else {
      $this->view->setErrorMessage($error_message);

      return $this->view->getErrorMessage();
    }
  }

  /** Форма для удаления бронирования
   *
   * @return false|string
   */
  public function removeOrderForm()
  {
    $this->setOrdersTemplate();
    if ($this->model->checkRemove($error_message)) {
      $this->default_template = 'reservations';

      return $this->view->render(
        'remove_form',
        [
          'model' => $this->model,
        ]
      );
    } else {
      $this->view->setErrorMessage($error_message);

      return $this->view->getErrorMessage();
    }
  }


  /** Показать форму для удаления дня из абонимента
   *  todo сделать модель и таблицу для билетов
   *
   * @return string
   */
  protected function removeTicket()
  {
    $this->model        = new TicketsModelOrder();
    $this->initializeModel($this->model);
    $this->setOrdersTemplate();
    if ($content = $this->model->getOrder($error_code)) {
      $this->view->setTemplate('reservations/');
      $this->view->title       = $this->model->areas->type_title . ' - ' . $this->model->areas->sport_title . ' - ' . lang('Remove ticket', 'order');
      $content['message_view'] = (bool)$this->model->current_client->refund_for_ticket;

      return $this->view->render('ticket_order', $content);
    } else {
      $this->view->setErrorCode($error_code);

      return $this->view->getErrorMessage();
    }
  }

  /** Процесс удаления дня из Билета
   *
   * @return string
   */
  protected function removeTicketProceed()
  {
    $message            = '';
    $this->model        = new TicketsModelOrder();
    $this->initializeModel($this->model);
    $this->setOrdersTemplate();
    if (($price = $this->model->proceed($error_code))) {
      $message .= '<p>' . lang('Reservation (subscription) cancelled!', 'order') . '</p>';
      if ($this->model->current_client->refund_for_ticket) {
        // todo: сделано без возможности откатить удаление билета, если произошла ошибка с транзакцией или с зачислением денег (функционал есть, но нужно продумывать как должны проходить процессы)
        $response = ModCommHelper::callSafe('clients', 'PrivateAccount/deposit', [
          'client_id'    => (int)$this->model->current_client->client_id,
          'type_code'    => 'ticket_removed',
          'amount'       => $price,
          'related_data' => [
            'date'    => $this->model->date,
            'time'    => TimeHelper::generateTitleByTimeAndPeriod($this->model->time, $this->model->areas->period),
            'area_id' => $this->model->area_id,
          ],
          'related_id'   => (int)$this->model->ticket_id,
        ]);

        if (!$response->isSuccess()) {
          // todo: выводит ошибку, но при этом удаляет билет
          $message .= '<span class="message message-error">' . $response->getMessage() . '</span>';
        }
      }

      return $message;
    }

    $this->view->setErrorCode($error_code);

    return $this->view->getErrorMessage();
  }

  public function getAjaxOrderPrice()
  {
    $this->model->price   = $this->model->setOrderPrice(true);
    $out                  = $this->model->renderPricesBlock();
    $out['stockNegative'] = $this->model->checkNegativeValueStocks();

    return JsonHelper::encode($out);
  }
}
