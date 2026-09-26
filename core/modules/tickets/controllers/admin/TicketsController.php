<?php

namespace AC\core\modules\tickets\controllers\admin;

use AC\app\controllers\AdminController;
use AC\app\helpers\ButtonLinkLayoutHelper;
use AC\app\helpers\LayoutHelper;
use AC\core\engines\ClientsEngine;
use AC\core\modules\tickets\actions\admin\form_content\ShowAsTableTickets;
use AC\core\modules\tickets\models\TicketsModel;
use AC\core\system\actions\admin\contentBlock\ShowAsTable;
use AC\core\system\helpers\HtmlHelper;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TimeHelper;
use Service;

class TicketsController extends AdminController
{
  /**
   * @var TicketsModel
   */
  public    $model;
  protected $base_model = 'TicketsModel';

  public function show()
  {
    return [$this->formContent(), $this->list()];
  }

  public function list()
  {
    $outData = '';

    if ($this->model->getEngine()->getTicketsAreas($areas)) {
      $outData .= $this->topMenuList($areas, $this->commonProperties()->mode, $this->commonProperties()->areaId);

//      if ($action = Service::request()->checkPregMatchPost('submit_')) {
//        $outData[] = $this->markExecution()->markExecution(str_replace('submit_', '', $action));
//      }
      $outData .= $this->showListTable();
    }

    return $outData;
  }

  public function edit()
  {
    if ($ticketId = Service::request()->_('ticket_id')) {
      if ($this->model->getEngine()->getTicketDataWithPeriodsById($ticketId, $ticket_data, $periods_data)) {

        return [
          $this->formContent(false, $ticket_data),
          $this->listPeriods($ticketId, $periods_data),
          $this->formPeriods($ticketId, $ticket_data),
          $this->backButton()
        ];
      }
    }

    return $this->redirect($this->getDefaultUrl());
  }

  public function update($new = false)
  {
    $post = Service::request()->_post();

    if (Service::request()->checkPost([
      'area_id',
      'client_id',
      'time_start',
      'time_finish',
      'title',
      'space',
      'weekdays',
      's_year',
      's_month',
      's_day',
      'f_year',
      'f_month',
      'f_day'
    ])) {

      if (is_array($post['weekdays']) && !empty ($post['weekdays'])) {
        $post['weekdays'] = implode(',', $post['weekdays']);
      }
      $start  = $post['s_year'] . '-' . $post['s_month'] . '-' . $post['s_day'];
      $finish = $post['f_year'] . '-' . $post['f_month'] . '-' . $post['f_day'];
      if ($this->model->getEngine()->insertTicket(
        (int)$post['area_id'],
        (int)$post['client_id'],
        TimeHelper::convertTime24($post['time_start'], false),
        TimeHelper::convertTime24($post['time_finish'], false),
        $post['weekdays'],
        (int)$post['space'],
        $post['title'],
        (int)$post['discount'],
        $start,
        $finish,
        $error,
        $new_ticket_id
      )) {
        //билет добавлен, добавляем промежуток
        //проверка на ошибку "пересечение с имеющимися заказами"
        if (!$this->model->getEngine()->addPeriodToTicket($new_ticket_id, $start, $finish, $error, $crosses)) {
          $this->view->addMessage($this->interpretatePeriodInsertError($error, $crosses), 'error');
          $this->model->getEngine()->removeTicket($new_ticket_id);
        } else {
          $engines = Service::engines();
          $engines->webIo->sendFTPCurrentIcal($engines);
          //промежуток добавлен успешно
          setcookie("start_year", $_POST['s_year'], time() + 3600);
          setcookie("start_month", $_POST['s_month'], time() + 3600);
          setcookie("start_day", $_POST['s_day'], time() + 3600);

          setcookie("finish_year", $_POST['f_year'], time() + 3600);
          setcookie("finish_month", $_POST['f_month'], time() + 3600);
          setcookie("finish_day", $_POST['f_day'], time() + 3600);
        }
      } else {
        if ($error == 3) //начальное/конечное время некорректно
        {
          $this->error = lang('Incorrect data entry. Please check the data!', 'message_error');
        } elseif ($error == 4) //не выделен ли один день недели
        {
          $this->error = lang('No day of the week marked yet!', 'message_error');
        } else {
          $this->error = lang('Ticket inserting error', 'message_error', ['error' => $error]);
        }

        $this->error .= "<br>" . lang('Please enter the data again!', 'message_error');
      }
    }
  }

  protected function listPeriods($ticketId, $periods_data): string
  {
    $out   = HtmlHelper::tag('h1', null, null,
      ['content' => lang('Periods of validity of the subscription', 'tickets')])->asString();
    $table = HtmlHelper::table()
      ->addField('start', ['title' => lang('From')])
      ->addField('finish', ['title' => lang('Until')])
      ->addField('actions', ['title' => lang('Action')]);

    $table->style = 'border:none; margin: 0 auto; cell-spacing:0; cell-content:3; ackground-color:#fff';
    $table->class = 'main wide';
    foreach ($periods_data as $key => $period) {
      $table->addRow(HtmlHelper::tagTr()
        ->addItem(HtmlHelper::tagTdTh('td', null, null,
          ['class' => $key % 2 == 0 ? 'dark' : 'light', 'content' => date('d.m.Y', strtotime($period['start']))]))
        ->addItem(HtmlHelper::tagTdTh('td', null, null,
          ['class' => $key % 2 == 0 ? 'dark' : 'light', 'content' => date('d.m.Y', strtotime($period['finish']))]))
        ->addItem(HtmlHelper::tagTdTh('td', null, null,
          [
            'class'   => $key % 2 == 0 ? 'dark' : 'light',
            'content' => ButtonLinkLayoutHelper::getRemoveButton($this->getDefaultUrl() . '/removePeriod/periodId/'
              . $period['period_id'] . '/ticketId/' . $ticketId
              . ($this->commonProperties()->mode > 0 ? '/mode/' . $this->commonProperties()->mode : '')
              . '#periods')
          ]))
      );
    }

    return $out . $table->asString();
  }

  protected function formPeriods($ticketId, $ticketData): string
  {
    $block = new ShowAsTable();
    $block->title->addContent(lang('New period', 'tickets'));
    $block->form()->setName('insertTicketPeriod')
      ->addOtherAttribute('form-period-start', $ticketData['period_start'])
      ->addOtherAttribute('form-period-finish', $ticketData['period_finish'])
      ->addField('ticket_id', ['type' => 'hidden', 'value' => $ticketId])
      ->addProperties([
        'method' => 'post',
        'action' => $this->getDefaultUrl() . '/insertPeriod' . ($this->commonProperties()->mode > 0 ? '/mode/' . $this->commonProperties()->mode : ''),
        'id'     => 'insertTicketPeriod'
      ])
      ->addButton('submit', ['type' => 'submit', 'class' => 'button', 'value' => lang('button_create')])
      ->addButton('reset', ['type' => 'reset', 'class' => 'button', 'value' => lang('button_reset')]);;
    $block->table()->style = 'margin: 0 auto;border:none;cell-spacing:0;cell-content:3;';
    $block->table()->class = 'main wide';

    $block->table()
      ->addField('title')
      ->addField('value')
      ->addRow(
        HtmlHelper::tagTr()
          ->addItem(HtmlHelper::tagTdTh('td', null, null, ['class' => 'dark', 'content' => lang('Action') . ':']))
          ->addItem(HtmlHelper::tagTdTh('td', null, null, [
            'class'   => 'dark',
            'content' =>
              $block->form()->generateField('action_insert', ['type' => 'radio', 'value' => 0, 'id' => 'action_insert_0'])
                ->addOtherAttribute('checked', 1)->asString()
              . HtmlHelper::tag('label', null, null, ['content' => lang('insert', 'tickets')])
                ->addOtherAttribute('for', 'action_insert_0')->asString()
              . '<br>'
              . $block->form()->generateField('action_insert', ['type' => 'radio', 'value' => 1, 'id' => 'action_insert_1'])
                ->asString()
              . HtmlHelper::tag('label', null, null, ['content' => lang('eviscerate', 'tickets')])
                ->addOtherAttribute('for', 'action_insert_1')->asString()
              . '<br>'
              . $block->form()->generateField('action_insert', ['type' => 'radio', 'value' => 2, 'id' => 'action_insert_2'])
                ->asString()
              . HtmlHelper::tag('label', null, null, ['content' => lang('Transfer this subscription to new season', 'tickets') . '*'])
                ->addOtherAttribute('for', 'action_insert_2')->asString()
          ]))
      )
      ->addRow(HtmlHelper::tagTr('date')->setIdForm('insertTicketPeriod')->generateItems([], true, 'light'))
      ->addRow(HtmlHelper::tagTr()
        ->addItem(HtmlHelper::tagTdTh('th', null, null,
          ['colspan' => 2, 'style' => 'text-align:center', 'content' => $block->form()->getButtonsAsString()])));
    $block->footer = HtmlHelper::tag('p')->addContent(lang('text link to the description of the transfer to the new season', 'tickets'))->asString();
    return $block->asString();
  }

  /**
   * @return object{areaId: int, mode: int, 'actions': array}
   */
  protected function commonProperties(): object
  {
    $params                       = new \stdClass();
    $params->areaId               = Service::request()->_get(['area_id', 'areaId']);
    $params->mode                 = Service::request()->_get('mode', ($params->areaId !== null ? 0 : 1));
    $params->actions['removeAll'] = $this->getDefaultUrl() . '/remove/' . ($params->mode > 0 ? 'mode/' . $params->mode : '');

    return $params;
  }

  protected function getDataForTableList(): array
  {
    if ($this->model->getEngine()->getTicketsData($this->commonProperties()->mode, $this->commonProperties()->areaId, $tickets)) {
      foreach ($tickets as $key => $ticket) {
        $tickets[$key]['place']  = getEngine('areas', false)->getTitleByAreaId($ticket['area_id'], 'title_site_url') . ' - ' . $ticket['area'];
        $tickets[$key]['client'] = LayoutHelper::renderClientInfoHref($ticket['client_name'], $ticket['client_surname'], $ticket['client_id']);
        $discount                = array();
        if (isset($ticket['client_discount']) || isset($ticket['discount_id'])) {
          getEngine('discounts', false)->getFullAboDiscount(
            (int)$ticket['client_discount'],
            (int)$ticket['discount_id'],
            $discount
          );
        }
        $tickets[$key]['discount'] = (empty($discount)
          ? lang('no')
          : (isset($discount['client_discount']['ticket']) ? NumberHelper::format($discount['client_discount']['ticket']) : '0') . ' '
          . (isset($discount['client_discount']['dimension']) && $discount['client_discount']['dimension'] == 1 ? '%'
            : CURR_VALUTE) . ' | ' . (isset($discount['ticket_discount']['ticket'])
            ? NumberHelper::format($discount['ticket_discount']['ticket']) : '0') . ' '
          . (isset($discount['ticket_discount']['dimension']) && $discount['ticket_discount']['dimension'] == 1 ? '%'
            : CURR_VALUTE));
        $tickets[$key]['actions']  = [
          'remove'   => $this->getDefaultUrl() . '/remove/ticketId/' . $ticket['ticket_id']
            . ($this->commonProperties()->mode > 0 ? '/mode/' . $this->commonProperties()->mode : ''),
          'edit'     => $this->getDefaultUrl() . '/edit/ticketId/' . $ticket['ticket_id']
            . ($this->commonProperties()->mode > 0 ? '/mode/' . $this->commonProperties()->mode : ''),
          'viewInfo' => $this->getDefaultUrl() . '/viewInfo/ticketId/' . $ticket['ticket_id']
            . ($this->commonProperties()->mode > 0 ? '/mode/' . $this->commonProperties()->mode : ''),
        ];
      }

      return $tickets;
    }
    return [];
  }

  //форма с полями для билета
  protected function formContent($new = true, $data = []): string
  {
    /** @var ClientsEngine $clientEngine */
    if (Service::engines()->clients->getClientsData('1,2', 1, $clients_data, 'surname, name', null, 'client_id, surname, name')) {
      $block                 = new ShowAsTableTickets('formTicket', ['new' => $new]);
      $block->form()->action = $this->getDefaultUrl()
        . ($new ? '/create/mode/1' : '/update/mode/'
          . ($this->commonProperties()->mode > 0 ? '/mode/' . $this->commonProperties()->mode : ''));
      $key                   = 0;
      foreach (['clients', 'place', 'time', 'date', 'space', 'weekdays', 'discount', 'comment'] as $rowName) {
        if (!$new && in_array($rowName, ['date'])) {
          continue;
        }
        $block->table()->addRow(HtmlHelper::tagTr($rowName)->generateItems($data, $new, ($key % 2 == 0 ? 'dark' : 'light')));
        $key++;
      }

      $block->table()->addRow([
        $block->table()->getTdThTag([
          'content' => $block->form()->getButtonsAsString(),
          'colspan' => $block->table()->getCountCols(),
          'style'   => 'text-align:center'
        ], 'th')
      ]);

      return $block->asString();
    }

    return lang('Registered clients not found', 'message_error');
  }

  protected function getMainLinksByTopMenuList($mode = 1): array
  {
    //последние 20 билетов и билеты с истЁкшим сроком действия
    $mainLinks['title']   = lang('All seats', 'tickets') . ': ';
    $mainLinks['items'][] = [
      'value'  => lang('Last 20 entries', 'tickets'),
      'link'   => $this->getDefaultUrl(),
      'active' => $mode == 1
    ];
    $mainLinks['items'][] = [
      'value'  => lang('Expired subscriptions', 'tickets'),
      'link'   => $this->getDefaultUrl() . '/mode/2',
      'active' => $mode == 2
    ];

    return $mainLinks;
  }

  protected function topMenuList($areas, $mode = 1, $area_id = null): string
  {
    return
      useLayout()->render('menu/list_links_by_row', ['row' => $this->getMainLinksByTopMenuList($mode)]) .
      LayoutHelper::areasByRowMenu($areas, $area_id, $mode, $this->getDefaultUrl());
  }

  public function setViewKey($key = 'index')
  {
    parent::setViewKey(Service::structure()->isPageKey($key . '_' . $this->action) ? $key . '_' . $this->action : $key . '_' . $this->default_action);
  }

  public function setViewParams()
  {
    parent::setViewParams();
    $this->view->addJsFile('ajaxloadmodule');
  }

  //текстовое/HTML представление ошибки добавления периода в билет
  protected function interpretatePeriodInsertError($error_code, $crosses)
  {
    $result = '';
    if (is_array($error_code)) {
      foreach ($error_code as $error) {
        $result .= $this->errorTextPeriodInsert($error, $crosses['crosses_' . $error]) . '<br/>';
      }
    } else {
      $result .= $this->errorTextPeriodInsert($error_code, $crosses);
    }

    return $result;
  }

  protected function errorTextPeriodInsert($error, $crosses)
  {
    $result = '';
    if ($error == 1) {
      $result = lang('Period not inserted - target ticket not found', 'message_error');
    } elseif ($error == 2) {
      $result = lang('Period not inserted - start/finish date invalid', 'message_error');
    } elseif ($error == 3) {
      //пересечения с одиночными заказами
      $result = lang('The subscription could not be created.', 'message_error') .'<br>';
      $result .= lang('The following periods are occupied in the specified period', 'message_error') . ':<br>';
      foreach ($crosses as $p) {
        $result .= date('d.m.Y H:i:s', strtotime($p['start'])) . '<br/>';
      }
    } elseif ($error == 4) {
      //пересечения с промужутком(ами) билета(ов)
      $result = lang('The subscription could not be created.', 'message_error') .'<br>';
      $result .= lang('The following subscription hours are created in the specified period', 'message_error') . ':<br>';
      foreach ($crosses as $p) {
        $result .= $p['name'] . ' ' . $p['surname'] . ', ' .
          date('d.m.Y', strtotime($p['start'])) . ' - ' . $p['time'] . '<br/>';
      }
    } elseif ($error == 5) {
      $result = lang('Ticket block exclusions could not be saved', 'message_error');
    } elseif ($error == 6) {
      $result = lang('All subscription game dates are blocked', 'message_error');
    }

    return $result;
  }

}
