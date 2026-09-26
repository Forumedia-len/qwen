<?php

namespace AC\core\modules\reservations\models;

use AC\core\modules\clients\models\ClientsModel;
use AC\core\modules\mailing\entities\enums\ModeTemplate;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\JsonHelper;
use AC\core\system\helpers\TranslateHelper;
use Service;


/** Данные заказа для управления абонементом. */
class TicketsModelOrder extends OrdersModelReservation
{
  public $ticket_id;

  /** Загрузить идентификатор абонемента после основных данных заказа. */
  protected function initializeData(): void
  {
    parent::initializeData();
    $this->ticket_id = Service::request()->validated('ticket_id', 'request', [['integer', ['min' => 1]]]);
  }

  public function prefixTitle()
  {
    return ' - ' . lang('Management subscription', 'order');
  }

  public function getOrder(&$error_code)
  {
    if ($this->checkData($error_code)) {
      if ($ticket = $this->getTicketData()) {
        // можно удалять только свои абонименты
        if ($ticket['ticket_data']['client_id'] == $this->current_client->client_id) {
          if ($this->admin || $this->current_client->abo_delete) {
            $unix_time = strtotime($this->getDate() . ' ' . $this->time);
            // проверка что отменяется резервация не раньше разрешенного дня
            if ($this->admin || $unix_time >= strtotime(
                date(
                  'Y-m-d'
                ) . ' + ' . $this->engine->config['min_max'][$this->type_id][$this->sport_id]['min_rejection_days_count_ticket'] . 'day'
              )) {
              $_times   = [];
              $_times[] = (object)[
                'title' => date('H:i', strtotime($this->time_start)) . ' - ' . date(
                    'H:i',
                    strtotime($this->time_finish)
                  ),
              ];
              $h2       = $this->engine->areas->getTitleByAreaId($this->area_id, 'title_site_url') . ', ' . $this->areas->title;
              $content  = [
                'model'        => $this,
                'area_data'    => $this->areas,
                'h2'           => $h2,
                'date'         => (object)[
                  'date'    => date('d.m.Y', $unix_time),
                  'weekday' => TranslateHelper::translateWeekday(CalendarHelper::getWeekdayByUnixtime($unix_time)),
                  'times'   => $_times
                ],
                'return_price' => $this->getSumPriceReturn($unix_time),
              ];

              return $content;
            } else {
              $error_code = lang(
                'error_min_rejection_days_count',
                'message_error',
                ['min_rejection_days_count' => $this->engine->config['min_max'][$this->type_id][$this->sport_id]['min_rejection_days_count_ticket']]
              );
            }
          } else {
            $error_code = lang('You are not entitled to cancel the subscription', 'message_error');
          }
        } else {
          $error_code = lang('You cannot delete other users\' subscriptions.', 'message_error');
        }
      } else {
        $error_code = lang('Ticket not found', 'message_error');
      }
    }

    return false;
  }
  
  public function checkData(&$error_code): bool
  {
    // проверка, что не удаляем прошедшие брони
    if (!$this->admin && strtotime($this->date . ' ' . $this->time) < time()) {
      $error_code = lang('You cannot delete a subscription in the past time.', 'message_error');
      return false;
    }
    
    return parent::checkData($error_code);
  }

  public function proceed(&$error_code = 0)
  {
    if ($this->checkData($error_code)) {
      if ($ticket = $this->getTicketData()) {
        if ($this->checkProceed($error_code)) {
          $this->current_client = new ClientsModel($ticket['ticket_data']['client_id']);
          $unix_time            = strtotime($this->getDate() . ' ' . $this->time);
          $price = $this->getSumPriceReturn($unix_time);
          if ($this->engine->tickets->addDisabledTimeForTicket($this->ticket_id, $unix_time, $error_code, $price)) {
            $this->mailAfterRemoveTicket();
            $this->engine->webIo->sendFTPCurrentIcal($this->engine, $this->area_id, $this->getDate());

            return $this->getSumPriceReturn($unix_time);
          } else {
            $error_code = [lang('ticket period not removed', 'message_error'), $error_code];
          }
        }
      } else {
        $error_code = lang('Ticket not found', 'message_error');
      }
    }

    return false;
  }


  public function mailAfterRemoveTicket()
  {
    // todo для tennis-squash-rothaargebirge.de - пока только на нем
    $template_alias = 'ticket_remove';
    $mailer   = Service::mailer();
    $client   = (empty($this->current_client)) ? new ClientsModel('current') : $this->current_client;
    $tpl_data = $this->generateTemplateKeysOrder($client);
    if ($mailer->getTemplatesEngine()->checkTemplate(ModeTemplate::ADMIN->value, $template_alias)
      && $this->checkSendMailAdmin()) {
      $templateAlias = $mailer->getTemplatesEngine()->correctAliasByTime($this->time_start, $template_alias, ModeTemplate::ADMIN->value);
      $emails        = explode(',', Service::configDB('email', 'notify_email'));
      if ((defined('SEND_ADMIN_TO_DIFFERENT_EMAIL') && SEND_ADMIN_TO_DIFFERENT_EMAIL)) {
        $emailsTmp = JsonHelper::decode(Service::configDB('email', 'admins_mail_for_different_areas'));
        foreach ($emailsTmp as $emailData) {
          if (in_array($this->type_id . '_' . $this->sport_id, $emailData->courts)) {
            $emails = $emailData->email;
          }
        }
      }
      $mailer->dispatch($emails, ModeTemplate::ADMIN->value, $templateAlias, $tpl_data);
    }

    if ($mailer->getTemplatesEngine()->checkTemplate(ModeTemplate::USER->value, $template_alias)
      && $this->checkSendMailPlayer() && !empty($client->email)) {
      $template_alias = $mailer->getTemplatesEngine()->correctAliasByTime($this->time_start, $template_alias, ModeTemplate::USER->value);
      $mailer->dispatch(
        $client->email,
        ModeTemplate::USER->value,
        $template_alias,
        $tpl_data
      );
    }
  }

  public function generateTemplateKeysOrder(ClientsModel $client)
  {
    $tpl_data                   = [];
    $tpl_data['CLIENT_NAME']    = (!empty($client->name)) ? $client->name : $this->client_name;
    $tpl_data['CLIENT_SURNAME'] = (!empty($client->surname)) ? $client->surname : $this->client_surname;
    $tpl_data['EMAIL']          = $client->email;

    //данные площадки
    $tpl_data['PLACE_TYPE']  = $this->engine->areas->getTitleByAreaId($this->area_id, 'title_site_url');
    $tpl_data['PLACE_TITLE'] = $this->areas->title;


    //дата и промежуток заказа
    $unix_time          = strtotime($this->date);
    $tpl_data['DATE']   = date('d.m.Y',
        $unix_time) . ', ' . TranslateHelper::translateWeekDay(CalendarHelper::getWeekdayByUnixtime($unix_time));
    $tpl_data['PERIOD'] = "\n<br>\t\t" . implode(' ' . lang('clock') . "\n<br>\t\t", array_keys($this->timeTitles));

    return $tpl_data;
  }


  protected function checkProceed(&$error_code)
  {
    $error_code = '';
    if ($this->admin || $this->current_client->abo_delete) { // пользователь может удалять свои обонименты
      return true;
    } else {
      $error_code = lang('You are not entitled to cancel the subscription', 'message_error');
    }

    return false;
  }

  protected function getTicketData()
  {
    static $ticket;
    if (empty($ticket)) {
      if ($this->engine->tickets->getTicketFullDataWithPeriodsAndPriceById(
        $this->ticket_id,
        $ticket_data,
        $periods_data
      )) {
        $ticket['ticket_data']  = $ticket_data;
        $ticket['periods_data'] = $periods_data;
      }
    }

    return $ticket ?? [];
  }

  protected function getSumPriceReturn($unix_time)
  {
    $return_price = 0;
    if ($ticket = $this->getTicketData()) {
      $priceData = $ticket['periods_data'][0]['price'];
      if (config('ticket')->refundMethodFullPrice()) {
        $priceData = $ticket['ticket_data']['price_info'];
      }
      $period       = $this->engine->areas->getPeriodByDate($this->date);
      $return_price = (float)$priceData[CalendarHelper::getWeekdayByUnixtime($unix_time)][$this->time . ':00'][$period];
    }

    return $return_price;
  }

}
