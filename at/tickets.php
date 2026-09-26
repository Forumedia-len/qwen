<?php

use AC\app\helpers\LayoutHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\helpers\TranslateHelper;
use AC\core\engines\Engines;

class tickets_admin
{
  /**
   * @var Engines
   */
  public       $r;
  public       $page_key;
  public       $error;
  public       $error_insert;
  public array $select_years   = [];
  public array $select_monthes = [];
  public array $select_days    = [];

  function start()
  {
    $this->r = Service::engines();

    $this->page_key = 'tickets_show';

    switch (Service::request()->_get('action')) {
      //билеты
      case 'insertTicket':
      {
        $mutationSucceeded = false;
        $res               = $this->insertTicket($mutationSucceeded);
        if ($mutationSucceeded) {
          $this->r->webIo->sendFTPCurrentIcal($this->r);
        }
        return $res;
      }
      case 'removeTicket':
      {
        $res = $this->removeTicket();
        $this->r->webIo->sendFTPCurrentIcal($this->r);
        return $res;
      }
      case 'editTicket':
      {
        $res = $this->editTicket();
        $this->r->webIo->sendFTPCurrentIcal($this->r);
        return $res;
      }
      case 'changeTicket':
      {
        $res = $this->changeTicket();
        $this->r->webIo->sendFTPCurrentIcal($this->r);
        return $res;
      }
      case 'viewTicketInfo':
        return $this->viewTicketInfo();
      //периоды
      case 'removeTicketPeriod':
      {
        $res = $this->removeTicketPeriod();
        $this->r->webIo->sendFTPCurrentIcal($this->r);
        return $res;
      }
      case 'insertTicketPeriod':
      {
        $res = $this->insertTicketPeriod();
        $this->r->webIo->sendFTPCurrentIcal($this->r);
        return $res;
      }
      //экспорт
      case 'viewExportFormTickets':
        return $this->viewExportFormTickets();
      case 'exportRequest':
        return $this->exportRequest();
      //билеты
      default:
        return $this->viewTicketsList();
    }
  }

  /**
   * Добавляет абонемент и возвращает результат мутации через output-параметр.
   *
   * @param bool $mutationSucceeded
   *
   * @return mixed
   */
  function insertTicket(bool &$mutationSucceeded)
  {
    $mutationSucceeded = false;
    if (isset ($_POST['area_id']) && isset ($_POST['client_id']) && isset ($_POST['time_start']) && isset ($_POST['time_finish']) &&
      isset ($_POST['title']) && isset ($_POST['space']) && isset ($_POST['s_year']) && isset ($_POST['s_month']) && isset ($_POST['s_day']) &&
      isset ($_POST['f_year']) && isset ($_POST['f_year']) && isset ($_POST['f_year'])
    ) {
      $weekdays = '';
      if (isset ($_POST['weekdays']) && is_array($_POST['weekdays']) && !empty ($_POST['weekdays'])) {
        foreach ($_POST['weekdays'] as $wd => $value) {
          $weekdays .= $wd . ',';
        }
      }
      $start  = $_POST['s_year'] . '-' . $_POST['s_month'] . '-' . $_POST['s_day'];
      $finish = $_POST['f_year'] . '-' . $_POST['f_month'] . '-' . $_POST['f_day'];
      if ($this->r->tickets->insertTicket(
        (int)$_POST['area_id'],
        (int)$_POST['client_id'],
        $_POST['time_start'],
        TimeHelper::convertTime24($_POST['time_finish'], false),
        substr($weekdays, 0, -1),
        (int)$_POST['space'],
        $_POST['title'],
        (int)$_POST['discount'],
        $start,
        $finish,
        $error,
        $new_ticket_id
      )
      ) {
        //билет добавлен, добавляем промежуток

        //проверка на ошибку "пересечение с имеющимися заказами"
        if (!$this->r->tickets->addPeriodToTicket($new_ticket_id, $start, $finish, $error, $crosses)) {
          $this->error = $this->interpretatePeriodInsertError($error, $crosses);
          $this->r->tickets->removeTicket($new_ticket_id);
        } else {
          $mutationSucceeded = true;
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

    return $this->viewTicketsList(!$error);
  }

  //удалить билет
  function removeTicket()
  {
    $tickets = Service::request()->_('tickets', null);
    if ($tickets == null) {
      $tickets[] = Service::request()->_('ticket_id');
    }

    foreach ($tickets as $ticket_id) {
      if ($this->r->tickets->getTicketDataWithPeriodsById($ticket_id, $ticket_data, $periods_data)) {
        $this->r->tickets->addArchive($ticket_data, $periods_data);
        $this->r->tickets->removeTicket($ticket_id);
      }
    }

    return $this->viewTicketsList();
  }

  //редактировать билет
  function editTicket()
  {
    //определяем полученный ticket_id
    if (isset ($_GET['ticket_id'])) {
      $ticket_id = (int)$_GET['ticket_id'];
    } elseif (isset ($_POST['ticket_id'])) {
      $ticket_id = (int)$_POST['ticket_id'];
    } else {
      return $this->viewTicketsList();
    }

    if ($this->r->tickets->getTicketDataWithPeriodsById($ticket_id, $ticket_data, $periods_data)) {
      //билет найден
      //определяем постфикс для ссылок со значением режима
      $mode_postfix = isset ($_GET['mode']) && ($_GET['mode'] == 1 || $_GET['mode'] == 2) ? '&mode=' . (int)$_GET['mode'] : '';

      $tb = getThemeBuilder();

      $list = '<a name="periods"></a><h1>' . lang('Periods of validity of the subscription', 'tickets') . '</h1>';

      if (isset ($this->msg0)) {
        $list .= $tb->message($this->msg0);
      }
      if (isset ($this->error0)) {
        $list .= $tb->error($this->error0);
      }

      $list .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
      $list .= '<tr><th>' . lang('From') . '</th><th>' . lang('Until') . '</th><th>' . lang('Action') . '</th></tr>' . "\n";
      foreach ($periods_data as $period) {
        $list .= '<tr>' .
          '<td class="dark">' . date('d.m.Y', strtotime($period['start'])) . '</td><td class="light">' . date(
            'd.m.Y',
            strtotime($period['finish'])
          ) . '</td>' .
          '<td class="dark"><a href="tickets.php?action=removeTicketPeriod&period_id=' . $period['period_id'] . '&ticket_id=' . $ticket_id . $mode_postfix . '#periods" onclick="return ifConfirm ()" class="btnRemove">' . lang('button_remove') . '</a></td>' .
          '</tr>' . "\n";
      }
      $list .= "</table>";

      //форма добавления периода
      $form = '<h1>' . lang('New period', 'tickets') . '</h1>';

      if (isset ($this->msg_insert)) {
        $form .= $tb->message($this->msg_insert);
      }
      if (isset ($this->error_insert)) {
        $form .= $tb->error($this->error_insert);
      }

      $form .= '<form method="post" action="tickets.php?action=insertTicketPeriod' . $mode_postfix . '#periods" name="insertTicketPeriod" id="insertTicketPeriod" form-period-start="' . $ticket_data['period_start'] . '" form-period-finish="' . $ticket_data['period_finish'] . '">' . "\n";
      $form .= '<input type="hidden" name="ticket_id" value="' . $ticket_id . '">' . "\n";

      $this->prepareDateArrays();

      ob_start();
      ?>
      <tr>
        <td class="dark"><?= lang('Action') ?>:</td>
        <td class="dark">
          <input type="radio" checked name="action_insert" value="0"><?= lang('insert', 'tickets') ?>
          <br>
          <input type="radio" name="action_insert" value="1"><?= lang('eviscerate', 'tickets') ?>
          <br>
          <input type="radio" name="action_insert" value="2"><?= lang('Transfer this subscription to new season', 'tickets') ?>*
        </td>
      </tr>
      <tr>
        <td colspan="2" class="light">
          <table border="0" cellspacing="3" cellpadding="0">
            <tr>
              <td><?= lang('From') ?>:</td>
              <td><?= $tb->select('s_year', $this->select_years, date('Y')); ?></td>
              <td>.</td>
              <td><?= $tb->select('s_month', $this->select_monthes, date('m')); ?></td>
              <td>.</td>
              <td><?= $tb->select('s_day', $this->select_days, date('d')); ?></td>
              <td rowspan="2">
                <a href="javascript:void null"
                   onclick="blocks_copyPeriodDate (document.forms['insertTicketPeriod']);">
                  <img
                    src="<?= base_url(paths()->getAssetsDir('images/copy.gif')) ?>" alt="Kopieren" border="0">
                </a>
              </td>
            </tr>
            <tr>
              <td><?= lang('Until') ?>:</td>
              <td><?= $tb->select('f_year', $this->select_years, date('Y')); ?></td>
              <td>.</td>
              <td><?= $tb->select('f_month', $this->select_monthes, date('m')); ?></td>
              <td>.</td>
              <td><?= $tb->select('f_day', $this->select_days, date('d')); ?></td>
            </tr>

          </table>
        </td>
      </tr>
      <tr>
        <th align="center" colspan="2"><input type=submit class="button" value="<?= lang('button_create') ?>">&nbsp;<input
            type="reset" class="button" value="<?= lang('button_reset') ?>"></th>
      </tr>
      <?
      $tbl = ob_get_contents();
      ob_end_clean();

      $form .= $tb->table($tbl, false);
      $form .= "</form>\n";
      $form .= "<p>" . lang('text link to the description of the transfer to the new season', 'tickets') . "</p>\n";

      return [
        $this->getTicketFormContent(1, $ticket_data),
        $list,
        $form,
        $tb->back(
          'tickets.php?area_id=' . $ticket_data['area_id'] . (isset ($_GET['mode']) && ($_GET['mode'] == 1 || $_GET['mode'] == 2)
            ? '&mode=' . $_GET['mode'] : '')
        ),
      ];
    } else {
      return $this->viewTicketsList();
    }
  }

  //изменить билет
  function changeTicket()
  {
    if (isset ($_POST['ticket_id']) && isset ($_POST['client_id']) && isset ($_POST['title'])) {
      $this->r->tickets->changeTicket(
        (int)$_POST['ticket_id'],
        (int)$_POST['client_id'],
        $_POST['title'],
        (int)$_POST['discount']
      );
    }

    return $this->viewTicketsList();
  }

  //список билетов
  function viewTicketsList($new = true)
  {


    $tb = getThemeBuilder();

    //сообщения и ошибки
    $out = "";
    if (isset ($this->message)) {
      $out .= $tb->message($this->message);
    }
    if (isset ($this->error)) {
      $out .= $tb->error($this->error);
    }
    if (!empty ($out)) {
      $output[] = $out;
    }

    $output[] = $this->getTicketFormContent(0, ($new ? [] : $_POST));

    //список билетов
    //area_id
    $area_id = Service::request()->_get('area_id', null);
    $mode    = Service::request()->_get('mode', ($area_id !== null ? 0 : 1));

    if ($this->r->tickets->getTicketsAreas($areas)) {
      $out = '';
      //последние 20 билетов и билеты с истЁкшим сроком действия
      $out .= "<big class=\"blue\"><b>" . lang('All seats', 'tickets') . ":</b></big> ";
      $out .= $mode == 1 ? "<b>" . lang('Last 20 entries', 'tickets') . "</b> | " : "<a href=\"tickets.php\">" . lang('Last 20 entries',
          'tickets') . "</a> | ";
      $out .= $mode == 2 ? "<b>" . lang('Expired subscriptions',
          'tickets') . "</b>" : "<a href=\"tickets.php?mode=2\">" . lang('Expired subscriptions', 'tickets') . "</a>";

      //навигация по площадкам
      $last_type_id  = null;
      $last_sport_id = null;

      foreach ($areas as $area) {
        if ($last_type_id != $area['type_id']) {
          if ($last_type_id !== null) {
            $out = substr($out, 0, -3);
          }
          $last_sport_id = null;
          $out           .= "<br>\n<span class=\"blue\"><b>" . $area['type_title'] . ":</b></span> ";
        }
        if ($last_sport_id != $area['sport_id']) {
          if ($last_sport_id !== null) {
            $out = substr($out, 0, -3);
          }
          $out .= "<br>\n&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class=\"blue\"><b> - " . $area['sport_title'] . " : </b></span> ";
        }

        if ($mode == 0 && $area['area_id'] == $area_id) {
          $out .= "<b>" . $area['area_title'] . " (" . $area['cnt'] . ")</b> | ";
        } else {
          $out .= "<a href=\"tickets.php?area_id=" . $area['area_id'] . "\">" . $area['area_title'] . " (" . $area['cnt'] . ")</a> | ";
        }
        $last_type_id  = $area['type_id'];
        $last_sport_id = $area['sport_id'];
      }
      $out = substr($out, 0, -3);

      $today = strtotime(date('Y-m-d'));

      if ($this->r->tickets->getTicketsData($mode, $area_id, $tickets)) {
        $out .= "<br><br>\n";
        if ($mode == 2) {
          $out .= '<form action="tickets.php?mode=2" method="POST" id="tickets_list">' . "\n";
        }
        $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
        $out .= '<tr>';
        if ($mode == 2) {
          $out .= '<th><input type="checkbox" name="all_select" value="0" onchange="selectAll(this, \'tickets_list\')"/>' . lang('To mark',
              'tickets') . '</th>';
        }
        if ($area_id === null) {
          $out .= '<th>' . lang('Place') . '</th>';
        }
        $out .= '<th>' . lang('Client') . '</th><th>' . lang('Time period')
          . '</th><th>' . lang('Time in hours') . '</th>';
        $out .= '<th>' . TranslateHelper::translateWeekday(0, true) . '</th>';
        $out .= '<th>' . TranslateHelper::translateWeekday(1, true) . '</th>';
        $out .= '<th>' . TranslateHelper::translateWeekday(2, true) . '</th>';
        $out .= '<th>' . TranslateHelper::translateWeekday(3, true) . '</th>';
        $out .= '<th>' . TranslateHelper::translateWeekday(4, true) . '</th>';
        $out .= '<th>' . TranslateHelper::translateWeekday(5, true) . '</th>';
        $out .= '<th>' . TranslateHelper::translateWeekday(6, true) . '</th>';
        $out .= '<th>' . lang('Regularity', 'tickets') . '</th>';
        $out .= '<th>' . lang('Discount(Customers | Subscription)', 'tickets') . '</th>';
        $out .= '<th>' . lang('Comment') . '</th><th colspan="4">' . lang('Action') . '</th></tr>' . "\n";

        $mode_postfix = $mode != 0 ? '&mode=' . $mode : '';
        foreach ($tickets as $ticket) {
          //строка таблицы
          $out .= '<tr>';
          if ($mode == 2) {
            $out .= '<td class="light" align="center" valign="top"><input type="checkbox" name="tickets[]" value="' . $ticket['ticket_id'] . '"/></td>';
          }
          if ($area_id === null) {
            $out .= '<td class="light" nowrap ' . (empty($ticket['date_finish']) || $today > strtotime(
                $ticket['date_finish']
              ) ? 'style="color:gray"' : '') . '>' . $this->r->areas->getTitleByAreaId(
                $ticket['area_id']
              ) . ' - ' . $ticket['area'] . '</td>';
          }
          $out .= '<td class="dark" nowrap>' . LayoutHelper::renderClientInfoHref(
              $ticket['client_name'],
              $ticket['client_surname'],
              $ticket['client_id']
            ) . '</td>';
          if ($ticket['date_start'] === null) {
            $out .= '<td class="dark" align="center">' . lang('The time periods are not entered!', 'message_error') . '</td>';
          } else {
            $out .= '<td class="dark" nowrap ' . ($today > strtotime(
                $ticket['date_finish']
              ) ? 'style="color:gray"' : '') . '>' . date(
                'd.m.Y',
                strtotime($ticket['date_start'])
              ) . ' - ' . date('d.m.Y', strtotime($ticket['date_finish'])) . '</td>';
          }

          $out .= '<td class="light" nowrap ' . (empty($ticket['date_finish']) || $today > strtotime(
              $ticket['date_finish']
            ) ? 'style="color:gray"' : '') . '>' . date(
              'H:i',
              strtotime($ticket['time_start'])
            ) . ' - ' . TimeHelper::convertTime24($ticket['time_finish'], false) . '</td>';

          //дни недели
          for ($j = 0; $j < 7; $j++) {
            $out .= '<td class="' . ($j % 2 == 0 ? 'dark' : 'light') . '"' . (strpos(
                $ticket['weekdays'],
                (string)$j
              ) !== false ? ' align="center">x' : '>') . '</td>';
          }

          $out .= '<td class="light">' . ($ticket['space'] == 1 ? lang('Every week', 'tickets') : lang('Every n weeks', 'tickets',
              ['space' => $ticket['space']])) . '</td>';

          //Вывести скидки
          $discount = [];
          if (isset($ticket['client_discount']) || isset($ticket['discount_id'])) {
            $this->r->discount->getFullAboDiscount(
              (int)$ticket['client_discount'],
              (int)$ticket['discount_id'],
              $discount
            );
          }
          $out .= '<td class="light">' . (empty($discount)
              ? lang('no')
              : (isset($discount['client_discount']['ticket']) ? number_format(
                $discount['client_discount']['ticket'],
                2,
                ',',
                ''
              ) : '0') . ' ' . (isset($discount['client_discount']['dimension']) && $discount['client_discount']['dimension'] == 1 ? '%'
                : CURR_VALUTE) . ' | ' . (isset($discount['ticket_discount']['ticket'])
                ? number_format(
                  $discount['ticket_discount']['ticket'],
                  2,
                  ',',
                  ''
                ) : '0') . ' ' . (isset($discount['ticket_discount']['dimension']) && $discount['ticket_discount']['dimension'] == 1 ? '%'
                : CURR_VALUTE)) . '</td>';

          $out .= '<td class="dark">' . $ticket['title'] . '</td>';
          $out .= '<td class="light"><a href="tickets.php?action=viewTicketInfo&ticket_id=' . $ticket['ticket_id'] . $mode_postfix . '" class="btnCalc">' . lang('Calculation',
              'tickets') . '</a></td>';
          $out .= '<td class="dark"><a href="tickets.php?action=editTicket&ticket_id=' . $ticket['ticket_id'] . $mode_postfix . '" class="btnEdit">' . lang('button_update') . '</a></td>';
          $out .= '<td class="light"><a href="tickets.php?action=removeTicket&ticket_id=' . $ticket['ticket_id'] . $mode_postfix . '" onclick="return ifConfirm ()" class="btnRemove">' . lang('button_remove') . '</a></td>';
          $out .= '</tr>' . "\n";
        }
        if ($mode == 2) {
          $out .= '<tr><td>  </td></tr>';
          $out .= '<tr><td colspan="18" align="right">
							<input type="button" name="archives" value="' . lang('button_remove') . '" onClick="return buttonAction(\'tickets_list\', \'tickets.php?action=removeTicket' . $mode_postfix . '\')" class="button"/>
					</td></tr>' . "\n";
        }
        $out .= "</table>\n<br>\n";
        if ($mode == 2) {
          $out .= '</form>';
        }
      }
      $output[] = $out;
    }


    return $output;
  }

  function viewTicketInfo()
  {
    if (isset($_GET['ticket_id'])) {
      $ticket_id = (int)$_GET['ticket_id'];
      if ($this->r->tickets->getTicketFullDataWithPeriodsAndPriceById($ticket_id, $ticket_data, $periods_data)) {
        $extra           = number_format(
            (float)$ticket_data['extra'],
            2,
            ',',
            ''
          ) . ' ' . CURR_VALUTE . ' ';
        $client_discount = number_format(
            (float)$ticket_data['discount_client'],
            2,
            ',',
            ''
          ) . ' ' . ($ticket_data['discount_client_dimension'] == 1 ? '%' : CURR_VALUTE);
        $ticket_discount = number_format(
            (float)$ticket_data['discount_ticket'],
            2,
            ',',
            ''
          ) . ' ' . ($ticket_data['discount_ticket_dimension'] == 1 ? '%' : CURR_VALUTE);
        $out             = '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">';
        $out             .= '<tr>' . "\n";
        $out             .= '<th style="text-align:center" colspan="6">' . $this->r->areas->getTitleByAreaId(
            $ticket_data['area_id']
          ) . ' - ' . $ticket_data['area'] . '</th>' . "\n";
        $out             .= '</tr>' . "\n";
        $out             .= '<tr>' . "\n";
        $out             .= '<td  colspan="6" class="dark"><strong>' . $ticket_data['client_name'] . ' ' . $ticket_data['client_surname'] . '</strong><br />' . $ticket_data['title'] . '</td>' . "\n";
        $out             .= '</tr>' . "\n";

        //Выводи скидки и наценки
        $out .= '<tr>' . "\n";
        $out .= '<th colspan="6">' . lang('Additions and deductions', 'tickets') . '</th>' . "\n";
        $out .= '</tr>' . "\n";
        $out .= '<tr>' . "\n";
        $out .= '<tr>' . "\n";
        $out .= '<td class="dark">' . lang('Surcharge', 'tickets') . ':</td><td class="light" colspan="5">' . $extra . '</td>' . "\n";
        $out .= '</tr>' . "\n";
        $out .= '<tr>' . "\n";
        $out .= '<td class="dark">' . lang('Customer discount',
            'tickets') . ':</td><td class="light" colspan="5">' . $client_discount . '</td>' . "\n";
        $out .= '</tr>' . "\n";
        $out .= '<tr>' . "\n";
        $out .= '<td class="dark">' . lang('Subscription discount',
            'tickets') . ':</td><td class="light" colspan="5">' . $ticket_discount . '</td>' . "\n";
        $out .= '</tr>' . "\n";

        $out .= '<tr>' . "\n";
        $out .= '<th>' . lang('Period', 'tickets') . '</th>' . "\n";
        $out .= '<th>' . lang('Weekdays') . '</th>' . "\n";
        $out .= '<th>' . lang('Date') . '</th>' . "\n";
        $out .= '<th>' . lang('Time') . '</th>' . "\n";
//        $out         .= '<th>Preis</th>' . "\n";
        $out         .= '<th>' . lang('Sum') . '</th>' . "\n";
        $out         .= '</tr>' . "\n";
        $count_game  = 0;
        $total_price = 0;

        foreach ($periods_data as $pd) {
          $f1 = true;
          if (is_array($pd['count_game'])) {
            foreach ($pd['count_game'] as $weekday => $games) {
              $f2 = true;
              if (is_array($games)) {
                foreach ($games as $g_date => $t_periods) {
                  if (is_array($t_periods)) {
                    $f3 = true;
                    foreach ($t_periods as $start => $count) {
                      $unixstart = strtotime($start);
                      if ($count) {
                        $out .= '<tr>' . "\n";
                        $out .= '<td class="' . ($f1 ? 'light' : 'dark') . '">' . ($f1 ? date(
                              'd.m.Y',
                              strtotime($pd['start'])
                            ) . ' - ' . date(
                              'd.m.Y',
                              strtotime($pd['finish'])
                            ) : '') . '</td>';
                        $out .= '<td class="' . ($f2 ? 'light' : 'dark') . '">' . ($f2 ? TranslateHelper::translateWeekday(
                            $weekday
                          ) : '') . '</td>';
                        $out .= '<td class="' . ($f3 ? 'light' : 'dark') . '">' . ($f3 ? date(
                            'd.m.Y',
                            strtotime($g_date)
                          ) : '') . '</td>' . "\n";
                        $out .= '<td class="light">' . date('H:i', $unixstart) . '-' . TimeHelper::convertTime24(
                            date(
                              'H:i',
                              mktime(
                                date('H', $unixstart),
                                (date('i', $unixstart) + $ticket_data['period'])
                              )
                            ),
                            false
                          ) . '</td>' . "\n";
                        if ($p_id = $this->r->areas->getPeriodByDate($g_date)) {
//                          $ren_p = ' + ' . $extra . ' - ' . $client_discount . ' - ' . $ticket_discount;
//                          $out         .= '<td class="light" style="text-align: center;">' . number_format(
//                              $ticket_data['price_info'][$weekday][$start][$p_id],
//                              2,
//                              ',',
//                              ''
//                            ) . ' ' . CURR_VALUTE . ' ' . $ren_p . '</td>' . "\n";
                          $out         .= '<td class="light" style="text-align: center">' . number_format(
                              $pd['price'][$weekday][$start][$p_id],
                              2,
                              ',',
                              ''
                            ) . ' ' . CURR_VALUTE . '</td>' . "\n";
                          $total_price += $pd['price'][$weekday][$start][$p_id];
                          $count_game  += $count;
                        }
                        $out .= '</tr>';
                        $f1  = false;
                        $f2  = false;
                        $f3  = false;
                      }
                    }
                  }
                }
              }
            }
          }
        }
        //Выводим итог
        $out .= '<tr>' . "\n";
        $out .= '<th colspan="3"><strong>' . lang('Total') . '</strong></th>' . "\n";
        $out .= '<td class="light" style="text-align:center"><strong>' . $count_game . '</strong></td>' . "\n";
        $out .= '<td class="dark" style="text-align:right" colspan="2"><strong>' . number_format(
            $total_price,
            2,
            ',',
            ''
          ) . ' ' . CURR_VALUTE . '</strong></td>' . "\n";
        $out .= '</tr>' . "\n";
        $out .= '</table>';
      }

      $output[] = $out;
      $output[] = '<p style="text-align:center;"><a href="tickets.php">' . lang('Back') . '</a></p>';
    }

    return $output;
  }


  //форма с полями для билета
  function getTicketFormContent($mode, $row = [])
  {
    $tb  = getThemeBuilder();
    $out = '<h1>' . ($mode == 0 ? lang('Insert subscription', 'tickets') : lang('Change subscription data', 'tickets')) . '</h1>';

    //значения
    if ($mode == 0) {
      //добавить
      $time_start = $time_finish = '00:00';

      $action = 'insertTicket&mode=1';
      $submit = lang('button_create');
    } else {
      //изменить
      $time_start  = substr($row['time_start'], 0, 5);
      $time_finish = substr($row['time_finish'], 0, 5);

      $action = 'changeTicket' . (isset ($_GET['mode']) && ($_GET['mode'] == 1 || $_GET['mode'] == 2) ? '&mode=' . $_GET['mode'] : '');
      $submit = lang('button_update');
    }

    //список зарегестрированных клиентов (если они есть)
    if ($mode == 1) {
      $out .= "<p>" . lang('the text of the subscription note', 'tickets') . "</p>\n";
    }

    $out .= '<form method="post" action="tickets.php?action=' . $action . '" name="insertTicket">' . "\n";
    if ($mode == 1) {
      $out .= '<input type="hidden" name="ticket_id" value="' . $row['ticket_id'] . '">' . "\n";
    }
    $tbl = '<tr><th colspan="2">' . lang('Abo data', 'tickets') . '</th></tr>' . "\n";
    ob_start();
    ?>
    <tr>
      <td class="dark"><label for="ticket-client-id"><?= lang('Client') ?>:</label></td>
      <td class="dark">
        <select id="ticket-client-id" name="client_id" class="clientsList select2-find"
          <?= ($mode == 1 ? 'data-selected-id="' . (int)$row['client_id'] . '"' : '') ?>>
          <?php if ($mode == 1) { ?>
            <option value="<?= (int)$row['client_id'] ?>" selected><?= htmlspecialchars(
                trim($row['client_surname'] . ' ' . $row['client_name']),
                ENT_QUOTES
              ) ?></option>
          <?php } ?>
        </select>
      </td>
    </tr>
    <?
    if ($mode == 0) {
      //новый билет

      //площадки
      $this->r->areas->getAllAreasData($areas_data);
      $select_areas = [];
      foreach ($areas_data as $area_data) {
        if (!isset($area_id)) {
          $area_id = $area_data['area_id'];
        }
        $select_areas[$area_data['area_id']] = $this->r->areas->getTitleByTypeAndSport(
            $area_data['type_id'],
            $area_data['sport_id']
          ) . ' - ' . $area_data['title'];
      }
      $path_ajax_params = 'area_id=' . $area_id
        . (isset($row['time_start']) ? '&time_start="' . $row['time_start'] . '"' : '')
        . (isset($row['time_finish']) ? '&time_finish="' . $row['time_finish'] . '"' : '');
      ?>
      <tr>
        <td class="light"><?= lang('Playground') ?>:</td>
        <td class="light">
          <script>
            var load_time = new ajaxLoader('load_time', 'ajax_time_tickets.php?action=getAreaTimes', 'loadTimeBlock', 'loaderTimeBlock')
            todo.onload(function () {
              load_time.loadModule('<?=$path_ajax_params?>')
            })
          </script>
          <?php
          unset($area_id);
          echo '<div id="loaderTimeBlock" style="float:right"><img src="' . base_url(
              paths()->getAssetsDir('images/ajax_loader.gif')
            ) . '" alt="load"/></div>' . "\n";
          echo '<select name="area_id" class="input" onchange="return getAreaTimes(this, load_time)">' . "\n";
          foreach ($select_areas as $key => $value) {
            echo '<option value="' . $key . '"' . (isset($row['area_id']) && $key == $row['area_id'] ? ' selected'
                : '') . '>' . $value . '</option>' . "\n";
          }
          echo '</select>' . "\n";
          ?>
      </tr>
      <tr>
        <td class="dark"><?= lang('Time') ?>:</td>
        <td class="dark" id="loadTimeBlock">
          <!-- Сюда вставляется время-->
        </td>
      </tr>
      <?
      //массивы для select'ов с датами
      $this->prepareDateArrays();

      $start_year  = isset ($_COOKIE['start_year'])
        ? $_COOKIE['start_year']
        : (isset($row['s_year'])
          ? $row['s_year']
          : date(
            'Y'
          ));
      $start_month = isset ($_COOKIE['start_month'])
        ? $_COOKIE['start_month']
        : (isset($row['s_month'])
          ? $row['s_month']
          : date(
            'm'
          ));
      $start_day   = isset ($_COOKIE['start_day'])
        ? $_COOKIE['start_day']
        : (isset($row['s_day'])
          ? $row['s_day']
          : date(
            'd'
          ));

      $finish_year  = isset ($_COOKIE['finish_year'])
        ? $_COOKIE['finish_year']
        : (isset($row['f_year'])
          ? $row['f_year']
          : date(
            'Y'
          ));
      $finish_month = isset ($_COOKIE['finish_month'])
        ? $_COOKIE['finish_month']
        : (isset($row['f_month'])
          ? $row['f_month']
          : date(
            'm'
          ));
      $finish_day   = isset ($_COOKIE['finish_day'])
        ? $_COOKIE['finish_day']
        : (isset($row['f_day'])
          ? $row['f_day']
          : date(
            'd'
          ));
      ?>
      <tr>
        <td class="light"><?= lang('Date') ?>:</td>
        <td class="light">
          <table border="0" cellspacing="3" cellpadding="0">
            <tr>
              <td><?= lang('From') ?>:</td>
              <td><?= $tb->select('s_year', $this->select_years, $start_year); ?></td>
              <td>.</td>
              <td><?= $tb->select('s_month', $this->select_monthes, $start_month); ?></td>
              <td>.</td>
              <td><?= $tb->select('s_day', $this->select_days, $start_day); ?></td>
              <td rowspan="2">
                <a href="javascript:void null"
                   onclick="blocks_copyPeriodDate (document.forms['insertTicket']);">
                  <img
                    src="<?= base_url(paths()->getAssetsDir('images/copy.gif')) ?>" alt="Kopieren" border="0">
                </a>
              </td>
            </tr>
            <tr>
              <td><?= lang('Until') ?>:</td>
              <td><?= $tb->select('f_year', $this->select_years, $finish_year); ?></td>
              <td>.</td>
              <td><?= $tb->select('f_month', $this->select_monthes, $finish_month); ?></td>
              <td>.</td>
              <td><?= $tb->select('f_day', $this->select_days, $finish_day); ?></td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Regularity', 'tickets') ?>:</td>
        <td class="dark">
          <select name="space">
            <option value="1" <?= (!isset($row['space']) || (isset($row['space']) && $row['space'] == 1) ? 'selected' : '') ?>>
              <?= lang('Every week', 'tickets') ?>
            </option>
            <option value="2" <?= ((isset($row['space']) && $row['space'] == 2) ? 'selected' : '') ?>><?= lang('Every n weeks', 'tickets',
                ['space' => 2]) ?>
            </option>
            <!--
                  <option value="3">Alle 3 Wochen</a>
                  <option value="4">Alle 4 Wochen</a>
            -->
          </select>
        </td>
      </tr>
      <tr>
        <td class="light"><?= lang('Weekdays') ?>:</td>
        <td class="light">
          <table>
            <?
            //дни недели
            $weekdays = [];
            for ($i = 0; $i <= 6; $i++) {
              echo '				<tr><td>' . TranslateHelper::translateWeekday(
                  $i
                ) . '</td><td><input type="checkbox" name="weekdays[' . $i . ']" 
                  value="1" ' . (isset($row['weekdays']) && in_array(
                  $i,
                  array_keys($row['weekdays'])
                ) ? 'checked' : '') . '></td></tr>' . "\n";
            }
            ?>
          </table>
        </td>
      </tr>
      <?
    } elseif ($mode == 1) {
      //редактирование основных данных билета
      ?>
      <tr>
        <td class="light"><?= lang('Playground') ?>:</td>
        <td class="light"><?= $this->r->areas->getTitleByAreaId($row['area_id']) . ' - ' . $row['area'] ?></td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Time') ?>:</td>
        <td class="dark"><?= $time_start; ?> - <?= TimeHelper::convertTime24($time_finish) ?></td>
      </tr>
      <tr>
        <td class="light"><?= lang('Regularity', 'tickets') ?>:</td>
        <?
        if ($row['space'] == 1) {
          echo '<td class="light">' . lang('Every week', 'tickets') . '</td>' . "\n";
        } else {
          echo '<td class="light">' . lang('Every n weeks', 'tickets', ['space' => $row['space']]) . '</td>' . "\n";
        }
        ?>
      </tr>
      <tr>
        <td class="light"><?= lang('Weekdays') ?>:</td>
        <td class="light">
          <table>
            <tr>
              <td><?
                //дни недели
                $a        = [];
                $weekdays = [];
                for ($i = 0; $i < 7; $i++) {
                  if (strpos($row['weekdays'], (string)$i) !== false) {
                    echo TranslateHelper::translateWeekday($i) . "<br>";
                  }
                }
                ?></td>
            </tr>
          </table>
        </td>
      </tr>
      <?
    }
    /*-------------------- Скидки для билетов ------------------------------------*/
    echo '<tr><td class="dark">' . lang('Abo discount', 'tickets') . ':</td>' . "\n";
    echo '<td class="dark"><select name="discount" class="input small">' . "\n";
    if ($this->r->discount->getDiscounts(1, $discount)) {
      echo '<option value="null">' . lang('None') . '</option>' . "\n";
      foreach ($discount as $d) {
        $d_tmp = htmlspecialchars(
            $d['title'],
            ENT_QUOTES
          ) . ' (Abo: ' . number_format($d['ticket'], 2, ',', '') . ' ' . ($d['dimension'] == 1 ? '%' : CURR_VALUTE) . ')';
        echo '<option value="' . $d['discount_id'] . '" ' . (isset($row['discount_id']) && $d['discount_id'] == $row['discount_id'] ? 'selected'
            : '') . ' title="' . $d_tmp . '">' . $d_tmp . '</option>' . "\n";
      }
    }
    echo '</select></td></tr>' . "\n";
    ?>
    <tr>
      <td class="light"><?= lang('Comment') ?>:</td>
      <td class="light"><input type="text" name="title" maxlength="255" class="input small"
                               value="<?= (isset($row['title']) ? $row['title'] : '') ?>"></td>
    </tr>
    <tr>
      <th align="center" colspan="2"><input type=submit class="button" value="<?= $submit ?>">&nbsp;<input
          type="reset" class="button" value="<?= lang('button_reset') ?>"></th>
    </tr>
    <?
    $tbl .= ob_get_contents();
    ob_end_clean();

    $out .= $tb->table($tbl, false);
    $out .= "</form>\n";

    return $out;
  }


  /* ПЕРИОДЫ */

  //удалить период по ID
  function removeTicketPeriod()
  {
    $this->r->tickets->removePeriodById((int)$_GET['period_id']);

    return $this->editTicket();
  }

  //добавить период
  function insertTicketPeriod()
  {
    if (isset ($_POST['ticket_id']) && isset ($_POST['action_insert']) &&
      isset ($_POST['s_year']) && isset ($_POST['s_month']) && isset ($_POST['s_day']) &&
      isset ($_POST['f_year']) && isset ($_POST['f_month']) && isset ($_POST['f_day'])
    ) {
      $start  = "$_POST[s_year]-$_POST[s_month]-$_POST[s_day]";
      $finish = "$_POST[f_year]-$_POST[f_month]-$_POST[f_day]";

      if ($_POST['action_insert'] == '0') {
        //добавить период
        if (!$this->r->tickets->addPeriodToTicket((int)$_POST['ticket_id'], $start, $finish, $error, $crosses)) {
          $this->error_insert = $this->interpretatePeriodInsertError($error, $crosses);
        }
      } elseif ($_POST['action_insert'] == '1') {
        //исключить период
        $this->r->tickets->removePeriodFromTicket((int)$_POST['ticket_id'], $start, $finish, $error);
      } elseif ($_POST['action_insert'] == '2') {
        //создать новый абонемент с такими же данными но с другим периодом
        return $this->viewCloneTicket((int)$_POST['ticket_id'], (int)$_GET['mode'], $start, $finish);
      }
    }

    return $this->editTicket();
  }

  //Создать клон абонимента
  function viewCloneTicket($ticket_id, $mode, $start, $finish)
  {

    $tb = getThemeBuilder();

    $out = '<h1>' . lang('Insert subscription', 'tickets') . '</h1>';

    if ($this->r->tickets->getTicketDataWithPeriodsById($ticket_id, $ticket_data, $period_data)) {
      $out .= '<form method="post" action="tickets.php?action=insertTicket" name="insertTicket">' . "\n";

      $out .= '<input type="hidden" name="client_id" value="' . $ticket_data['client_id'] . '">' . "\n";
      $out .= '<input type="hidden" name="area_id" value="' . $ticket_data['area_id'] . '">' . "\n";
      $out .= '<input type="hidden" name="space" value="' . $ticket_data['space'] . '">' . "\n";

      $out .= '<input type="hidden" name="time_start" value="' . date(
          'H:i',
          strtotime($ticket_data['time_start'])
        ) . '">' . "\n";
      $out .= '<input type="hidden" name="time_finish" value="' . date(
          'H:i',
          strtotime($ticket_data['time_finish'])
        ) . '">' . "\n";

      $st  = explode('-', $start);
      $fn  = explode('-', $finish);
      $out .= '<input type="hidden" name="s_year" value="' . $st[0] . '">' . "\n";
      $out .= '<input type="hidden" name="s_month" value="' . $st[1] . '">' . "\n";
      $out .= '<input type="hidden" name="s_day" value="' . $st[2] . '">' . "\n";
      $out .= '<input type="hidden" name="f_year" value="' . $fn[0] . '">' . "\n";
      $out .= '<input type="hidden" name="f_month" value="' . $fn[1] . '">' . "\n";
      $out .= '<input type="hidden" name="f_day" value="' . $fn[2] . '">' . "\n";

      $out_tbl = '<tr><th colspan="2">' . lang('Abo data', 'tickets') . '</th></tr>' . "\n";
      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<td class="dark">' . lang('Client') . ':</td>' . "\n";
      $out_tbl .= '<td class="dark">' . $ticket_data['client_name'] . ' ' . $ticket_data['client_surname'] . '</td>' . "\n";
      $out_tbl .= '</tr>' . "\n";

      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<td class="light">' . lang('Playground') . ':</td>' . "\n";
      $out_tbl .= '<td class="light">' . $ticket_data['area_type'] . ' ' . $ticket_data['area'] . '</td>' . "\n";
      $out_tbl .= '</tr>' . "\n";

      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<td class="dark">' . lang('Date') . ':</td>' . "\n";
      $out_tbl .= '<td class="dark"><strong>' . date('d.m.Y', strtotime($start)) . ' - ' . date(
          'd.m.Y',
          strtotime($finish)
        ) . '</strong></td>' . "\n";
      $out_tbl .= '</tr>' . "\n";

      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<td class="light">' . lang('Time') . ':</td>' . "\n";
      $out_tbl .= '<td class="light">' . $ticket_data['time_start'] . '-' . $ticket_data['time_finish'] . '</td>' . "\n";
      $out_tbl .= '</tr>' . "\n";

      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<td class="dark">' . lang('Regularity', 'tickets') . ':</td>' . "\n";
      $out_tbl .= '<td class="dark">' . "\n";
      if ($ticket_data['space'] == 1) {
        $out_tbl .= lang('Every week', 'tickets') . "\n";
      } else {
        $out_tbl .= lang('Every n weeks', 'tickets', ['space' => $ticket_data['space']]) . "\n";
      }
      $out_tbl .= '</td>' . "\n";
      $out_tbl .= '</tr>' . "\n";

      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<td class="dark">' . lang('Weekdays') . ':</td>' . "\n";
      $out_tbl .= '<td class="dark">' . "\n";
      //дни недели
      $a        = [];
      $weekdays = [];
      for ($i = 0; $i < 7; $i++) {
        if (strpos($ticket_data['weekdays'], (string)$i) !== false) {
          $out     .= '<input type="hidden" name="weekdays[' . $i . ']" value="' . $i . '">' . "\n";
          $out_tbl .= TranslateHelper::translateWeekday($i) . "<br>";
        }
      }

      $out_tbl .= '</td>' . "\n";
      $out_tbl .= '</tr>' . "\n";

      /*-------------------- Скидки для билетов ------------------------------------*/
      $out_tbl .= '<tr><td class="light">' . lang('Abo discount', 'tickets') . ':</td>' . "\n";
      $out_tbl .= '<td class="light"><select name="discount" class="input small">' . "\n";
      if ($this->r->discount->getDiscounts(1, $discount)) {
        $out_tbl .= '<option value="null">' . lang('None') . '</option>' . "\n";
        foreach ($discount as $d) {
          $d_tmp   = htmlspecialchars(
              $d['title'],
              ENT_QUOTES
            ) . ' (Abo:' . $d['ticket'] . ' ' . ($d['dimension'] == 1 ? '%' : CURR_VALUTE) . ')';
          $out_tbl .= '<option value="' . $d['discount_id'] . '" ' . ($d['discount_id'] == $ticket_data['discount_id'] ? 'selected'
              : '') . ' title="' . $d_tmp . '">' . $d_tmp . '</option>' . "\n";
        }
      }
      $out_tbl .= '</select></td></tr>' . "\n";

      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<td class="dark">' . lang('Comment') . ':</td>' . "\n";
      $out_tbl .= '<td class="dark"><input type="text" name="title" maxlength="255" class="input small" value="' . $ticket_data['title'] . '"></td>' . "\n";
      $out_tbl .= '</tr>' . "\n";

      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<th align="center" colspan="2"><input type="submit" class="button" value="' . lang('button_create') . '">&nbsp;<input type="reset" class="button" value="' . lang('button_reset') . '"></th>' . "\n";
      $out_tbl .= '</tr>' . "\n";

      $out .= $tb->table($out_tbl, false);

      $out .= "</form>\n";
    }

    $output = [
      $out,
      $tb->back(
        'tickets.php?action=editTicket&ticket_id=' . $ticket_id . (isset ($mode) && ($mode == 1 || $mode == 2) ? '&mode=' . $mode : '')
      ),
    ];

    return $output;
  }


  /* СЕРВИС */

  //подготовить массивы для select'ов с выбором дат
  function prepareDateArrays()
  {
    //годы
    $this->select_years = [];
    for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++) {
      $this->select_years[sprintf("%04d", $i)] = sprintf("%04d", $i);
    }

    //месяца
    $this->select_monthes = [];
    for ($i = 1; $i <= 12; $i++) {
      $this->select_monthes[sprintf("%02d", $i)] = TranslateHelper::translateMonth($i);
    }

    //дни
    $this->select_days = [];
    for ($i = 1; $i <= 31; $i++) {
      $this->select_days[sprintf("%02d", $i)] = sprintf("%02d", $i);
    }
  }

  //текстовое/HTML представление ошибки добавления периода в билет
  function interpretatePeriodInsertError($error_code, $crosses)
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

  public function errorTextPeriodInsert($error, $crosses)
  {
    $result = '';
    if ($error == 1) {
      $result = lang('Period not inserted - target ticket not found', 'message_error');
    } elseif ($error == 2) {
      $result = lang('Period not inserted - start/finish date invalid', 'message_error');
    } elseif ($error == 3) {
      //пересечения с одиночными заказами
      $result = lang('The subscription could not be created.', 'message_error') . '<br>';
      $result .= lang('The following periods are occupied in the specified period', 'message_error') . ':<br>';
      foreach ($crosses as $p) {
        $result .= date('d.m.Y H:i:s', strtotime($p['start'])) . '<br/>';
      }
    } elseif ($error == 4) {
      //пересечения с промужутком(ами) билета(ов)
      $result = lang('The subscription could not be created.', 'message_error') . '<br>';
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

  //Export
  function viewExportFormTickets()
  {
    //выходной массив
    $output = [];

    //ключ страницы
    $this->page_key = 'tickets_export';

    $out = '';

    $out .= '<form action="tickets.php?action=exportRequest" method="post" >' . "\n";
    $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
    $out .= '<tr><th colspan="2"><input type="submit" name="import" value = "' . lang('Export subscription data',
        'tickets') . '" class="button"/></th></tr>' . "\n";
    $out .= '</table>' . "\n";
    $out .= "</form>\n";

    $output[] = $out;

    return $output;
  }

  function exportRequest()
  {
    $fields = [
      'Name'      => lang('Name'),
      'Surname'   => lang('Surname'),
      'Street'    => lang('Street'),
      'Zip'       => lang('Zip'),
      'City'      => lang('City'),
      'Hall hour' => lang('Hall hour'),
      'Place'     => lang('Place'),
      'Time'      => lang('Time'),
      'Comment'   => lang('Comment'),
      'E-mail'    => lang('E-mail'),

    ];
    $out    = implode(';', $fields) . "\n";
    if ($this->r->tickets->getTicketsData(3, $area_id, $tickets)) {
      foreach ($tickets as $item) {
        $tmp = explode(',', $item['weekdays']);
        unset($item['weekdays']);
        foreach ($tmp as $t) {
          $item['weekdays'] .= ' ' . TranslateHelper::translateWeekday($t, true);
        }

        unset($tmp);

        $a   = [
          $item['client_name'],
          $item['client_surname'],
          $item['client_address'],
          $item['client_post_code'],
          $item['client_city'],
          $item['area_type'],
          $item['area'],
          date('d.m.Y', strtotime($item['date_start'])) . '-' . date(
            'd.m.Y',
            strtotime($item['date_finish'])
          ) . '  ' . date('H:i', strtotime($item['time_start'])) . '-' . date(
            'H:i',
            strtotime($item['time_finish'])
          ) . '  ' . $item['weekdays'],
          $item['title'],
          $item['client_email'],
        ];
        $out .= join(';', $a) . "\n";
      }
      header("Content-Disposition: attachment; filename=abo_clients_export.csv");
      header("Content-Type: application/x-force-download; name=\"abo_clients_export.csv\"");
      echo "\xEF\xBB\xBF" . $out;
      die;
    }

    return $this->viewExportFormTickets();
  }
}

$a                = new tickets_admin;
$_page['content'] = $a->start();
$_page['key']     = $a->page_key;
$_page['js'][]    = ['ajaxloadmodule', 'admin', false, 'cdn'];
$_page['js'][]    = ['select2/js/select2.min', 'third', true, 'cdn'];
$_page['js'][]    = ['select2/js/i18n/' . config('lang')->getCurrentLang(), 'third', true, 'cdn'];
$_page['css'][]   = ['select2/css/select2.min', 'third', true, 'cdn'];
