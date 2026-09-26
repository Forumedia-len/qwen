<?php

use AC\app\helpers\LayoutHelper;
use AC\core\system\helpers\TranslateHelper;
use AC\core\engines\Engines;

class tickets_fitness
{
  /**
   * @var Engines
   */
  public Engines $r;
  public string  $page_key;

  function start()
  {
    $this->r = new Engines();
    Service::engines()->areas->setGroupType('fitness');
    $this->page_key = 'fitness_tickets_show';

    switch (Service::request()->_get('action')) {
      //билеты
      case 'insertTicket':
        return $this->insertTicket();
      case 'removeTicket':
        return $this->removeTicket();
      case 'editTicket':
        return $this->editTicket();
      case 'changeTicket':
        return $this->changeTicket();
      case 'viewTicketInfo':
        return $this->viewTicketInfo();
      case 'addDateFinishAbo':
        return $this->addDateFinishAbo();
      //периоды
      case 'removeTicketPeriod':
        return $this->removeTicketPeriod();
      case 'insertTicketPeriod':
        return $this->insertTicketPeriod();
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

  //добавить билет
  function insertTicket()
  {

    if (isset ($_POST['area_id']) && isset ($_POST['client_id']) && isset ($_POST['title']) && isset ($_POST['space']) && isset ($_POST['s_year']) && isset ($_POST['s_month'])
    ) {
      $area_id = $_POST['area_id'];
      $this->r->areas->getAreaFullTimeTable($area_id, $timetable);
      $start = $_POST['s_year'] . '-' . $_POST['s_month'] . '-01';

      if ($this->r->tickets->insertTicketFit(
        (int)$area_id, (int)$_POST['client_id'],
        $timetable[0][0], $timetable[0][1], 1, $_POST['title'], (int)$_POST['discount'], $start ,$_POST['price_for_month'],
        0, $error, $new_ticket_id)
      ) {
        //билет добавлен, добавляем промежуток
        if (isset ($_POST['s_year']) && isset ($_POST['s_month'])) {
          $start = $_POST['s_year'] . '-' . $_POST['s_month'] . '-01';

          $this->r->tickets->addPeriodToTicketFit($new_ticket_id, $start);
        } else {
          //даты непереданы или некорректны
        }
      } else {
        if ($error == 3) //начальное/конечное время некорректно
        {
          $this->error = 'Falsche Dateneingabe. Bitte überprüfen Sie die Daten!';
        } elseif ($error == 4) //не выделен ли один день недели
        {
          $this->error = 'Bis jetzt kein Wochentag markiert!';
        } else {
          $this->error = 'Ticket inserting error #' . $error;
        }

        $this->error .= "<br>Bitte geben Sie die Daten neu ein!";
      }
    }

    return $this->viewTicketsList();
  }

  //для определения количества месяцев
  function diff($datetime1, $datetime2 = null)
  {
    //Если вторая дата не задана принимаем ее как текущую
    if (is_null($datetime2)) {
      $datetime2 = new DateTime();
    }
    //Проверяем параметры
    if (!($datetime1 instanceof DateTime)) {
      throw new InvalidArgumentException('Параметр date1 должен быть объектом DateTime');
    }
    if (!($datetime2 instanceof DateTime)) {
      throw new InvalidArgumentException('Параметр date2 должен быть объектом DateTime');
    }
    //Преобразуем даты в массив
    $d1 = array_map('intval', explode('-', $datetime1->format('Y-m-d-H-i-s')));
    $d2 = array_map('intval', explode('-', $datetime2->format('Y-m-d-H-i-s')));
    //Если вторая дата меньше чем первая, меняем их местами
    for ($i = 0; $i < count($d2); $i++) {
      if ($d2[$i] > $d1[$i]) {
        break;
      }
      if ($d2[$i] < $d1[$i]) {
        [$d1, $d2] = array($d2, $d1);
        break;
      }
    }
    //Вычисляем разность между датами (как в столбик)
    $diff  = array(null, null, null, null, null);
    $md1   = array(
      31,
      $d1[0] % 4 || (!($d1[0] % 100) && $d1[0] % 400) ? 28 : 29,
      31,
      30,
      31,
      30,
      31,
      31,
      30,
      31,
      30,
      31
    );
    $minV1 = array(null, 1, 1, 0, 0, 0);
    $maxV1 = array(null, 12, $md1[$d1[1] - 1], 23, 59, 59);
    for ($i = 5; $i > 0; $i--) {
      if ($d1[$i] > $maxV1[$i]) {
        $d1[$i - 1]++;
        $d1[$i] = $minV1[$i];
      }
      if ($d2[$i] < $d1[$i]) {
        $d1[$i - 1]++;
        $diff[$i] = $d2[$i] + ($maxV1[$i] - $minV1[$i] + 1) - $d1[$i];
      } else {
        $diff[$i] = $d2[$i] - $d1[$i];
      }
    }
    $diff[0] = $d2[0] - $d1[0];
    //Возвращаем результат
    return $diff;
  }

  //удалить билет
  function removeTicket()
  {
    $this->r->tickets->removeTicket((int)$_GET['ticket_id']);
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

      $list = '<a name="periods"></a><h1>Gültigkeitsperioden des Abo`s</h1>';

      if (isset ($this->msg0)) {
        $list .= $tb->message($this->msg0);
      }
      if (isset ($this->error0)) {
        $list .= $tb->error($this->error0);
      }

      $list .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
      $list .= '<tr><th>Von</th><th>Bis</th><th>Aktion</th></tr>' . "\n";
      foreach ($periods_data as $period) {
        $list .= '<tr>' .
          '<td class="dark">' . date('m.Y', strtotime($period['start'])) . '</td><td class="light">' . ($period['finish'] != null ? date('d.m.Y',
            strtotime($period['finish'])) : '') .
          '</td>' .
          '<td class="dark"><a href="tickets_fitness.php?action=removeTicketPeriod&period_id=' . $period['period_id'] . '&ticket_id=' . $ticket_id . $mode_postfix . '#periods" onclick="return ifConfirm ()" class="btnRemove">L&ouml;schen</a></td>' .
          '</tr>' . "\n";
      }
      $list .= "</table>";

      //форма добавление даты завершения абонимента
      $form = '<h1>Gekündigt zum</h1>';

      if (isset ($this->msg_insert)) {
        $form .= $tb->message($this->msg_insert);
      }
      if (isset ($this->error_insert)) {
        $form .= $tb->error($this->error_insert);
      }

      $form .= '<form method="post" action="tickets_fitness.php?action=addDateFinishAbo' . $mode_postfix . '#periods" name="insertTicketPeriod">' . "\n";
      $form .= '<input type="hidden" name="ticket_id" value="' . $ticket_id . '">' . "\n";

      $this->prepareDateArrays();
      $finish = $periods_data[0]['finish'];
      ob_start();
      ?>
      <tr>
        <td colspan="2" class="light">
          <table border="0" cellspacing="3" cellpadding="0">
            <tr>
              <td>Bis:</td>
              <td><?= $tb->select('f_year', $this->select_years, ($finish != null ? date('Y', strtotime($finish)) : 'null')) ?></td>
              <td>.</td>
              <td><?= $tb->select('f_month', $this->select_monthes, ($finish != null ? date('m', strtotime($finish)) : 'null')) ?></td>
              <td>.</td>
              <td><?= $tb->select('f_day', $this->select_days, ($finish != null ? date('d', strtotime($finish)) : 'null')) ?></td>
            </tr>

          </table>
        </td>
      </tr>
      <tr>
        <th align="center" colspan="2"><input type=submit class="button" value="Hinzuf&uuml;gen"></th>
      </tr>
      <?
      $tbl = ob_get_contents();
      ob_end_clean();

      $form .= $tb->table($tbl, false);
      $form .= "</form>\n";

      return array(
        $this->getTicketFormContent(1, $ticket_data),
        $list,
//        $form,
        $tb->back('tickets_fitness.php?area_id=' . $ticket_data['area_id'] . (isset ($_GET['mode']) && ($_GET['mode'] == 1 || $_GET['mode'] == 2) ? '&mode=' . $_GET['mode'] : ''))
      );
    } else {
      return $this->viewTicketsList();
    }
  }

  //изменить билет
  function changeTicket()
  {
    if (isset ($_POST['ticket_id']) && isset ($_POST['client_id']) && isset ($_POST['title'])) {
      $this->r->tickets->changeTicket((int)$_POST['ticket_id'], (int)$_POST['client_id'], $_POST['title'],
        (int)$_POST['discount'], $_POST['price_for_month']);
    }

    return $this->viewTicketsList();
  }

  //список билетов
  function viewTicketsList()
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

    $output[] = $this->getTicketFormContent(0);

    //список билетов
    //area_id
    $area_id = Service::request()->_get('area_id', null);
    $mode    = Service::request()->_get('mode', ($area_id !== null ? 0 : 1));

    if ($this->r->tickets->getTicketsAreas($areas)) {
      $out = '';
      //последние 20 билетов и билеты с истЁкшим сроком действия
      $out .= "<big class=\"blue\"><b>Fitness Pl&auml;tze:</b></big> ";
      $out .= $mode == 1 ? "<b>Letzte 20 Eintr&auml;ge</b>" : "<a href=\"tickets_fitness.php\">Letzte 20 Eintr&auml;ge</a>";
      if (count($areas) == 1) {
        $out .= $mode == 0 ? " | <b>Alle Abos (" . $areas[0]['cnt'] . ")</b>" : " | <a href=\"tickets_fitness.php?mode=0&area_id=" . $areas[0]['area_id'] . "\">Alle Abos (" . $areas[0]['cnt'] . ")</a>";
      }
      $out .= $mode == 2 ? " | <b>Abgelaufene Abos</b>" : " | <a href=\"tickets_fitness.php?mode=2\">Abgelaufene Abos</a>";
      if (count($areas) > 1) {
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
      }
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
          $out .= '<th>Platz</th>';
        }
        $out .= '<th>Kunde</th><th>Startdatum</th>';
        $out .= '<th>Rabatt(Kunden | Abo)</th>';
        $out .= '<th>Kommentar</th><th>monatlicher Betrag</th><th>Nächste Rechnung</th>'/*<th>Gekündigt zum</th>*/ . '<th colspan="4">Aktion</th></tr>' . "\n";

        $mode_postfix = $mode != 0 ? '&mode=' . $mode : '';
        foreach ($tickets as $ticket) {
//            mysql_queryD('update '. MYSQL_TABLE_PREFIX . 'tickets_periods set finish = NULL where ticket_id = '. $ticket['ticket_id']); // обнулить дату окончания фитнес абониментов
          $style = '';
          if ($ticket['date_finish'] != null && $today > strtotime($ticket['date_finish'])) {
            $style = 'style="color:gray"';
          }
          //строка таблицы
          $out .= '<tr>';
          if ($mode == 2) {
            $out .= '<td class="light" align="center" valign="top"><input type="checkbox" name="tickets[]" value="' . $ticket['ticket_id'] . '"/></td>';
          }
          if ($area_id === null) {
            $out .= '<td class="light" nowrap ' . $style . '>' . $ticket['area_type'] . ' - ' . $ticket['area'] . '</td>';
          }
          $out .= '<td class="dark" nowrap>' . LayoutHelper::renderClientInfoHref($ticket['client_name'],
              $ticket['client_surname'], $ticket['client_id']) . '</td>';
          if ($ticket['date_start'] === null) {
            $out .= '<td class="dark" align="center">Die Zeitperdioden sind nicht eingegeben!</td>';
          } else {
            $out .= '<td class="dark" nowrap ' . $style . '>' . date('m - Y',
                strtotime($ticket['date_start'])) . '</td>';
          }

          //Вывести скидки
          $discount = array();
          $this->r->discount->getFullAboDiscount((int)$ticket['client_discount'], (int)$ticket['discount_id'],
            $discount);

          $out .= '<td class="light" ' . $style . '>' . (empty($discount) ? 'kein' : ($discount['client_discount']['ticket'] ? number_format($discount['client_discount']['ticket'],
                2, ',',
                '') : '0') . ($discount['client_discount']['dimension'] == 1 ? '%' : CURR_VALUTE) . ' | ' . ($discount['ticket_discount']['ticket'] ? number_format($discount['ticket_discount']['ticket'],
                2, ',', '') : '0') . ($discount['ticket_discount']['dimension'] == 1 ? '%' : CURR_VALUTE)) . '</td>';

          $out .= '<td class="dark" ' . $style . '>' . $ticket['title'] . '</td>';
          $out .= '<td class="dark" align="center" ' . $style . '>' . $ticket['price_for_month'] . '</td>';

          if ($ticket['check_payd']) {
            $plus_months = "+" . $ticket['check_payd'] . " month";
          } else {
            $plus_months = '';
          }
          $out .= '<td  class="dark" align="center" ' . $style . '>' . date('m - Y',
              strtotime($ticket['date_start'] . ' ' . $plus_months)) . '</td>' . "\n";

//            $out .= '<td  class="dark" align="center" ' . $style . '>' . ($ticket['date_finish'] != null ? date('d.m.Y',
//                strtotime($ticket['date_finish'])) : '') . '</td>' . "\n";
          $out .= '<td class="dark"><a href="tickets_fitness.php?action=editTicket&ticket_id=' . $ticket['ticket_id'] . $mode_postfix . '" class="btnEdit">Ver&auml;ndern</a></td>';
          $out .= '<td class="light"><a href="tickets_fitness.php?action=removeTicket&ticket_id=' . $ticket['ticket_id'] . $mode_postfix . '" onclick="return ifConfirm ()" class="btnRemove">L&ouml;schen</a></td>';
          $out .= '</tr>' . "\n";
        }
        if ($mode == 2) {
          $out .= '<tr><td>  </td></tr>';
          $out .= '<tr><td colspan="18" align="right">
							<input type="button" name="archives" value="' . lang('button_remove') . '" onClick="return buttonAction(\'tickets_list\', \'tickets_fitness.php?action=removeTicket' . $mode_postfix . '\')" class="button"/>
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
      $out       = '';
      if ($this->r->tickets->getTicketFullDataWithPeriodsAndPriceById($ticket_id, $ticket_data, $periods_data)) {
        $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">';
        $out .= '<tr>' . "\n";
        $out .= '<th style="text-align:center" colspan="5">' . $ticket_data['area_type'] . ' - ' . $ticket_data['area'] . '</th>' . "\n";
        $out .= '</tr>' . "\n";
        $out .= '<tr>' . "\n";
        $out .= '<td  colspan="5" class="dark"><strong>' . $ticket_data['client_name'] . ' ' . $ticket_data['client_surname'] . '</strong><br />' . $ticket_data['title'] . '</td>' . "\n";
        $out .= '</tr>' . "\n";
        //Выводи скидки и наценки
        $client_discount = 0;
        if (!empty($ticket_data['client_discount'])) {
          $this->r->discount->getFullAboDiscount($ticket_data['client_discount'], $ticket_data['client_discount'],
            $discount);
          $client_discount = $discount['client_discount']['ticket'];
        }


        $out .= '<tr>' . "\n";
        $out .= '<th colspan="5">Zu- und Abschl&auml;ge</th>' . "\n";
        $out .= '</tr>' . "\n";
        $out .= '<tr>' . "\n";
        $out .= '<td class="dark">Zuschlag:</td><td class="light" colspan="4">' . number_format($ticket_data['extra'],
            2, ',', '') . ' &euro;</td>' . "\n";
        $out .= '</tr>' . "\n";
        $out .= '<tr>' . "\n";
        $out .= '<td class="dark">Kundenrabatt:</td><td class="light" colspan="4">' . number_format($discount['client_discount']['ticket'],
            2, ',', '') . ' ' . ($discount['client_discount']['dimension'] == 1 ? '%' : CURR_VALUTE) . '</td>' . "\n";
        $out .= '</tr>' . "\n";
        $out .= '<tr>' . "\n";
        $out .= '<td class="dark">Aboabschlag:</td><td class="light" colspan="4">' . number_format($ticket_data['discount_ticket'],
            2, ',', '') . ' ' . ($ticket_data['discount_ticket_dimension'] == 1 ? '%' : CURR_VALUTE) . '</td>' . "\n";
        $out .= '</tr>' . "\n";

        $out .= '<tr>' . "\n";
        $out .= '<th>Periode</th>' . "\n";

        $out         .= '<th>Preis</th>' . "\n";
        $out         .= '<th>Anzahl der Spiele</th>' . "\n";
        $out         .= '<th>Summe</th>' . "\n";
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
                      $out       .= '<tr>' . "\n";
                      $out       .= '<td class="' . ($f1 ? 'light' : 'dark') . '">' . ($f1 ? date('d.m.Y',
                            strtotime($pd['start'])) . ' - ' . date('d.m.Y', strtotime($pd['finish'])) : '') . '</td>';
                      $out       .= '<td class="' . ($f3 ? 'light' : 'dark') . '">' . ($f3 ? date('d.m.Y',
                          strtotime($g_date)) : '') . '</td>' . "\n";
                      $out       .= '<td class="light">' . date('H:i', $unixstart) . '-' . date('H:i',
                          mktime(date('H', $unixstart),
                            (date('i', $unixstart) + $ticket_data['period']))) . '</td>' . "\n";
                      if ($p_id = $this->r->areas->getPeriodByDate($g_date)) {
                        $out         .= '<td class="light">' . number_format($pd['price'][$weekday][$p_id], 2, ',',
                            '') . ' &euro;</td>' . "\n";
                        $total_price += $pd['price'][$weekday][$p_id];
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
        //Выводим итог
        $out .= '<tr>' . "\n";
        $out .= '<th colspan="3"><strong>Insgesamt</strong></th>' . "\n";
        $out .= '<td class="light" style="text-align:center"><strong>' . $count_game . '</strong></td>' . "\n";
        $out .= '<td class="dark" style="text-align:right"><strong>' . number_format($total_price, 2, ',',
            '') . ' ' . CURR_VALUTE . '</strong></td>' . "\n";
        $out .= '</tr>' . "\n";
        $out .= '</table>';

      }

      $output[] = $out;
      $output[] = '<p style="text-align:center;"><a href="tickets_fitness.php">Zurück</a></p>';
    }
    return $output;
  }


  //форма с полями для билета
  function getTicketFormContent($mode, $row = array())
  {
    $tb  = getThemeBuilder();
    $out = '<h1>' . ($mode == 0 ? 'Abo einf&uuml;gen' : 'Abo-Daten ver&auml;ndern') . '</h1>';

    //значения
    if ($mode == 0) {
      //добавить
      $time_start = $time_finish = '00:00';

      $action = 'insertTicket&mode=1';
      $submit = 'Hinzuf&uuml;gen';
    } else {
      //изменить
      $time_start  = substr($row['time_start'], 0, 5);
      $time_finish = substr($row['time_finish'], 0, 5);

      $action = 'changeTicket' . (isset ($_GET['mode']) && ($_GET['mode'] == 1 || $_GET['mode'] == 2) ? '&mode=' . $_GET['mode'] : '');
      $submit = 'Ver&auml;ndern';
    }

    //список зарегестрированных клиентов (если они есть)
    if ($this->r->clients->getClientsData('1,2', 1, $clients_data) == true) {
      foreach ($clients_data as $client_data) {
        $tmp = $client_data['surname'] . ' ' . $client_data['name'];
        if (strlen($tmp) > 75) {
          $tmp = substr($tmp, 0, 75) . '...';
        }
        $clients2field[$client_data['client_id']] = $tmp;
      }

      if ($mode == 1) {
        $out .= "<p>Um Zeiten eines Abos zu ver&auml;ndern, muss das ABO komplett gel&ouml;scht werden und neu, mit neuen Zeiten, eingerichtet werden.</p>\n";
      }

      $out .= '<form method="post" action="tickets_fitness.php?action=' . $action . '" name="insertTicket">' . "\n";
      if ($mode == 1) {
        $out .= '<input type="hidden" name="ticket_id" value="' . $row['ticket_id'] . '">' . "\n";
      }
      $tbl = '<tr><th colspan="2">Abo-Daten</th></tr>' . "\n";
      ob_start();
      ?>
      <tr>
        <td class="dark">Kunde:</td>
        <td class="dark"><?= $tb->select('client_id', $clients2field, $row['client_id']); ?></td>
      </tr>
      <?
      if ($mode == 0) {
        //новый билет

        //площадки
        $this->r->areas->getAllAreasData($areas_data);
        $select_areas = array();
        foreach ($areas_data as $area_data) {
          if (!isset($area_id)) {
            $area_id = $area_data['area_id'];
          }
          $select_areas[$area_data['area_id']] = $this->r->areas->getTitleByTypeAndSport(
              $area_data['type_id'],
              $area_data['sport_id']
            ) . ' - ' . $area_data['title'];
        }
        ?>
        <tr>
          <td class="light">Spielplatz:</td>
          <td class="light">
            <select name="area_id" class="input" onchange="return getAreaTimes(this, load_time)">
              <option value="6">Fitness ABO</option>
            </select>
        </tr>
        <?
        //массивы для select'ов с датами
        $this->prepareDateArrays();

        $start_year  = isset ($_COOKIE['start_year']) ? $_COOKIE['start_year'] : date('Y');
        $start_month = isset ($_COOKIE['start_month']) ? $_COOKIE['start_month'] : date('m');
        ?>
        <tr>
          <td class="light">Datum:</td>
          <td class="light">
            <table border="0" cellspacing="3" cellpadding="0">
              <tr>
                <td>Ab:</td>
                <td><?= $tb->select('s_year', $this->select_years, $start_year); ?></td>
                <td>.</td>
                <td><?= $tb->select('s_month', $this->select_monthes, $start_month); ?></td>
              </tr>
            </table>
          </td>
        </tr>
        <input name="space" type="hidden" value="1">

        <?
      } elseif ($mode == 1) {
        //редактирование основных данных билета
        ?>
        <tr>
          <td class="light">Spielplatz:</td>
          <td class="light"><?= $row['area_type'] . ' ' . $row['area'] ?></td>
        </tr>
        <?
      }
      /*-------------------- Скидки для билетов ------------------------------------*/
      echo '<tr><td class="dark">Abo-Rabatt:</td>' . "\n";
      echo '<td class="dark"><select name="discount" class="input small">' . "\n";
      if ($this->r->discount->getDiscounts(1, $discount)) {
        echo '<option value="null">Keine</option>' . "\n";
        foreach ($discount as $d) {
          $d_tmp = htmlspecialchars($d['title'],
              ENT_QUOTES) . ' (Abo:' . $d['ticket'] . ' ' . ($d['dimension'] == 1 ? '%' : CURR_VALUTE) . ')';
          echo '<option value="' . $d['discount_id'] . '" ' . ($d['discount_id'] == $row['discount_id'] ? 'selected' : '') . ' title="' . $d_tmp . '">' . $d_tmp . '</option>' . "\n";
        }
      }
      echo '</select></td></tr>' . "\n";
      ?>
      <tr>
        <td class="light">Kommentar:</td>
        <td class="light"><input type="text" name="title" maxlength="255" class="input small"
                                 value="<?= $row['title'] ?>"></td>
      </tr>
      <tr>
        <td class="light">monatlicher Betrag:</td>
        <td class="light"><input type="text" name="price_for_month" maxlength="255" class="input small"
                                 value="<?= $row['price_for_month'] ?>"></td>
      </tr>
      <tr>
        <th align="center" colspan="2"><input type=submit class="button" value="<?= $submit ?>">&nbsp;<input
            type="reset" class="button" value="Zur&uuml;cksetzen"></th>
      </tr>
      <?
      $tbl .= ob_get_contents();
      ob_end_clean();

      $out .= $tb->table($tbl, false);
      $out .= "</form>\n";
    } else {
      //клиентов нет
      $out = 'Registered clients not found.';
    }

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
    if (isset ($_POST['ticket_id']) && isset ($_POST['action']) &&
      isset ($_POST['s_year']) && isset ($_POST['s_month']) && isset ($_POST['s_day']) &&
      isset ($_POST['f_year']) && isset ($_POST['f_month']) && isset ($_POST['f_day'])
    ) {
      $start  = "$_POST[s_year]-$_POST[s_month]-$_POST[s_day]";
      $finish = "$_POST[f_year]-$_POST[f_month]-$_POST[f_day]";

      if ($_POST['action'] == '0') {
        //добавить период
        if (!$this->r->tickets->addPeriodToTicket((int)$_POST['ticket_id'], $start, $finish, $error, $crosses)) {
          $this->error_insert = $this->interpretatePeriodInsertError($error, $crosses);
        }
      } elseif ($_POST['action'] == '1') {
        //исключить период
        $this->r->tickets->removePeriodFromTicket((int)$_POST['ticket_id'], $start, $finish, $error);
      } elseif ($_POST['action'] == '2') {
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

    $out = '<h1>Abo einf&uuml;gen</h1>';

    if ($this->r->tickets->getTicketDataWithPeriodsById($ticket_id, $ticket_data, $period_data)) {

      $out .= '<form method="post" action="tickets_fitness.php?action=insertTicket" name="insertTicket">' . "\n";

      $out .= '<input type="hidden" name="client_id" value="' . $ticket_data['client_id'] . '">' . "\n";
      $out .= '<input type="hidden" name="area_id" value="' . $ticket_data['area_id'] . '">' . "\n";
      $out .= '<input type="hidden" name="space" value="' . $ticket_data['space'] . '">' . "\n";

      $out .= '<input type="hidden" name="time_start" value="' . date('H:i',
          strtotime($ticket_data['time_start'])) . '">' . "\n";
      $out .= '<input type="hidden" name="time_finish" value="' . date('H:i',
          strtotime($ticket_data['time_finish'])) . '">' . "\n";

      $st  = explode('-', $start);
      $fn  = explode('-', $finish);
      $out .= '<input type="hidden" name="s_year" value="' . $st[0] . '">' . "\n";
      $out .= '<input type="hidden" name="s_month" value="' . $st[1] . '">' . "\n";
      $out .= '<input type="hidden" name="s_day" value="' . $st[2] . '">' . "\n";
      $out .= '<input type="hidden" name="f_year" value="' . $fn[0] . '">' . "\n";
      $out .= '<input type="hidden" name="f_month" value="' . $fn[1] . '">' . "\n";
      $out .= '<input type="hidden" name="f_day" value="' . $fn[2] . '">' . "\n";

      $out_tbl = '<tr><th colspan="2">Abo-Daten</th></tr>' . "\n";
      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<td class="dark">Kunde:</td>' . "\n";
      $out_tbl .= '<td class="dark">' . $ticket_data['client_name'] . ' ' . $ticket_data['client_surname'] . '</td>' . "\n";
      $out_tbl .= '</tr>' . "\n";

      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<td class="light">Spielplatz:</td>' . "\n";
      $out_tbl .= '<td class="light">' . $ticket_data['area_type'] . ' ' . $ticket_data['area'] . '</td>' . "\n";
      $out_tbl .= '</tr>' . "\n";

      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<td class="dark">Datum:</td>' . "\n";
      $out_tbl .= '<td class="dark"><strong>' . date('d.m.Y', strtotime($start)) . ' - ' . date('d.m.Y',
          strtotime($finish)) . '</strong></td>' . "\n";
      $out_tbl .= '</tr>' . "\n";


      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<td class="dark">Regelmässigkeit:</td>' . "\n";
      $out_tbl .= '<td class="dark">' . "\n";
      if ($ticket_data['space'] == 1) {
        $out_tbl .= 'Jede Woche' . "\n";
      } else {
        $out_tbl .= 'Alle ' . $ticket_data['space'] . ' Wochen' . "\n";
      }
      $out_tbl .= '</td>' . "\n";
      $out_tbl .= '</tr>' . "\n";


      /*-------------------- Скидки для билетов ------------------------------------*/
      $out_tbl .= '<tr><td class="light">Abo-Rabatt:</td>' . "\n";
      $out_tbl .= '<td class="light"><select name="discount" class="input small">' . "\n";
      if ($this->r->discount->getDiscounts(1, $discount)) {
        $out_tbl .= '<option value="null">Keine</option>' . "\n";
        foreach ($discount as $d) {
          $d_tmp   = htmlspecialchars($d['title'],
              ENT_QUOTES) . ' (Abo:' . $d['ticket'] . ' ' . ($d['dimension'] == 1 ? '%' : CURR_VALUTE) . ')';
          $out_tbl .= '<option value="' . $d['discount_id'] . '" ' . ($d['discount_id'] == $ticket_data['discount_id'] ? 'selected' : '') . ' title="' . $d_tmp . '">' . $d_tmp . '</option>' . "\n";
        }
      }
      $out_tbl .= '</select></td></tr>' . "\n";

      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<td class="dark">monatlicher Betrag:</td>' . "\n";
      $out_tbl .= '<td class="dark"><input type="text" name="price_for_month" maxlength="255" class="input small" value="' . $ticket_data['price_for_month'] . '"></td>' . "\n";
      $out_tbl .= '</tr>' . "\n";

      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<td class="dark">Kommentar:</td>' . "\n";
      $out_tbl .= '<td class="dark"><input type="text" name="title" maxlength="255" class="input small" value="' . $ticket_data['title'] . '"></td>' . "\n";
      $out_tbl .= '</tr>' . "\n";

      $out_tbl .= '<tr>' . "\n";
      $out_tbl .= '<th align="center" colspan="2"><input type=submit class="button" value="Hinzuf&uuml;gen">&nbsp;<input type="reset" class="button" value="Zur&uuml;cksetzen"></th>' . "\n";
      $out_tbl .= '</tr>' . "\n";

      $out .= $tb->table($out_tbl, false);

      $out .= "</form>\n";
    }

    $output = array(
      $out,
      $tb->back('tickets_fitness.php?action=editTicket&ticket_id=' . $ticket_id . (isset ($mode) && ($mode == 1 || $mode == 2) ? '&mode=' . $mode : ''))
    );

    return $output;
  }


  /* СЕРВИС */

  //подготовить массивы для select'ов с выбором дат
  function prepareDateArrays()
  {
    //годы
    $this->select_years = array('null' => '');
    for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++) {
      $this->select_years[(string)sprintf("%04d", $i)] = sprintf("%04d", $i);
    }

    //месяца
    $this->select_monthes = array('null' => '');
    for ($i = 1; $i <= 12; $i++) {
      $this->select_monthes[(string)sprintf("%02d", $i)] = TranslateHelper::translateMonth($i);
    }

    //дни
    $this->select_days = array('null' => '');
    for ($i = 1; $i <= 31; $i++) {
      $this->select_days[(string)sprintf("%02d", $i)] = sprintf("%02d", $i);
    }
  }

  //текстовое/HTML представление ошибки добавления периода в билет
  function interpretatePeriodInsertError($error, $crosses)
  {
    if ($error == 1) {
      $result = 'Period not inserted - target ticket not found';
    } elseif ($error == 2) {
      $result = 'Period not inserted - start/finish date invalid';
    } elseif ($error == 3) {
      //пересечения с одиночными заказами
      $result = 'Das Abo konnte nicht gebucht werden:<br>';
      foreach ($crosses as $p) {
        $result .= date('d.m.Y H:i:s', strtotime($p['start'])) . '<br/>';
      }
      $result .= 'Im angegebenen Zeitraum sind bereits Plätze gebucht!';
    } elseif ($error == 4) {
      //пересечения с промужутком(ами) билета(ов)
      $result = 'Das Abo konnte nicht gebucht werden:<br>';
      foreach ($crosses as $p) {
        $result .= $p['name'] . ' ' . $p['surname'] . ', ' .
          date('d.m.Y', strtotime($p['start'])) . ' - ' . date('d.m.Y', strtotime($p['finish'])) . '<br/>';
      }
      $result .= 'Im angegebenen Zeitraum sind bereits Abos gebucht!';
    }

    return $result;
  }

  private function addDateFinishAbo()
  {
    if (isset ($_POST['ticket_id']) &&
      isset ($_POST['f_year']) && isset ($_POST['f_month']) && isset ($_POST['f_day'])
    ) {
      if ($_POST['f_year'] == 'null' || $_POST['f_month'] == 'null' || $_POST['f_day'] == 'null') {
        $finish = null;
      } else {
        $finish = $_POST['f_year'] . '-' . $_POST['f_month'] . '-' . $_POST['f_day'];
      }

      $this->r->tickets->addFinishDateFitnessAbo($_POST['ticket_id'], $finish);
    }

    return $this->editTicket();
  }

  //Export
  function viewExportFormTickets()
  {
    //выходной массив
    $output = array();

    //ключ страницы
    $this->page_key = 'fitness_tickets_export';

    $out = '';

    $out .= '<form action="tickets_fitness.php?action=exportRequest" method="post" >' . "\n";
    $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
    $out .= '<tr><th colspan="2"><input type="submit" name="import" value = "Abo-Daten exportieren" class="button"/></th></tr>' . "\n";
    $out .= '</table>' . "\n";
    $out .= "</form>\n";

    $output[] = $out;

    return $output;
  }

  function exportRequest()
  {
    $out = "Name;Nachname;Straße;PLZ;Ort;Hallenstunde;Platz;Startdatum;Gekündigt zum;Kommentar;monatlicher Betrag\n";
    if ($this->r->tickets->getTicketsData(3, null, $tickets, true)) {
      foreach ($tickets as $item) {
        $tmp = explode(',', $item['weekdays']);
        unset($item['weekdays']);
        foreach ($tmp as $t) {
          $item['weekdays'] .= ' ' . TranslateHelper::translateWeekday($t, true);
        }

        unset($tmp);

        $a   = array(
          $item['client_name'],
          $item['client_surname'],
          $item['client_address'],
          $item['client_post_code'],
          $item['client_city'],
          $item['area_type'],
          $item['area'],
          date('m.Y', strtotime($item['date_start'])),
          $item['date_finish'] ? date('d.m.Y', strtotime($item['date_finish'])) : '',
          $item['title'],
          $item['price_for_month']
        );
        $out .= join(';', $a) . "\n";
      }
      header("Content-Disposition: attachment; charset=utf-8; filename=abo_fitness_clients_export.csv");
      header("Content-Type: application/x-force-download; charset=utf-8; name=\"abo_fitness_clients_export.csv\"");
      echo "\xEF\xBB\xBF" . $out;
      die;
    }

    return $this->viewExportFormTickets();
  }

  function count_total_price($periods_data, &$total_price)
  {
    $count_game  = 0;
    $total_price = 0;

    foreach ($periods_data as $pd) {
      if (is_array($pd['count_game'])) {
        foreach ($pd['count_game'] as $weekday => $games) {
          if (is_array($games)) {
            foreach ($games as $g_date => $t_periods) {
              if (is_array($t_periods)) {
                foreach ($t_periods as $start => $count) {
                  $unixstart = strtotime($start);
                  if ($count) {
                    if ($p_id = $this->r->areas->getPeriodByDate($g_date)) {
                      $total_price += $pd['price'][$weekday][$start][$p_id];
                      $count_game  += $count;
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

}

$a                = new tickets_fitness;
$_page['content'] = $a->start();
$_page['key']     = $a->page_key;
$_page['js'][]    = 'ajaxloadmodule';
