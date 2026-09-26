<?php

use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\TranslateHelper;
use AC\core\engines\Engines;


class holidays_admin
{

  function start()
  {
    $this->r = new Engines();

    switch (\Service::request()->_get('action')) {
      /* календарь праздников */
      //показать календарь
      case 'showCalendar':
        $this->page_key = 'holidays_calendar';

        return $this->showCalendar();
        break;
      //обновить календарь
      case 'updateCalendar':
        $this->page_key = 'holidays_calendar';

        return $this->updateCalendar();
        break;
      /* таблица праздников */
      case 'insertHoliday':
        $this->page_key = 'holidays_table';

        return $this->insertHoliday();
        break;
      case 'removeHoliday':
        $this->page_key = 'holidays_table';

        return $this->removeHoliday();
        break;
      default:
        $this->page_key = 'holidays_table';

        return $this->getHolidaysList();
    }
  }

  function getPageKey()
  {
    return $this->page_key;
  }

  //добавить праздник
  function insertHoliday()
  {
    $tmp  = explode('.', $_POST['date']);
    $date = $tmp[2] . '-' . $tmp[1] . '-' . $tmp[0];
    if (checkdate($tmp[1], $tmp[0], $tmp[2])) {
      if ($this->r->holidays->insertHoliday($date, (int)isset($_POST['sunday']), $holiday_id)) {
        $this->message = lang('Holiday registered!', 'message_success');
      } else {
        $this->error = lang('This holiday is already registered!', 'message_error');
      }
    } else {
      $this->error = lang('Wrong date input!', 'message_error');
    }

    return $this->getHolidaysList();
  }

  //удалить праздник
  function removeHoliday()
  {
    if (isset ($_GET['holiday_id'])) {
      $this->r->holidays->removeHolidayById($_GET['holiday_id']);
      $this->message = lang('Holiday deleted!', 'message_success');
    }

    return $this->getHolidaysList();
  }

  //список настроек
  function getHolidaysList()
  {
    $out_array = [];

    //таблички с сообщениями
    $tb = getThemeBuilder();
    if (isset ($this->message)) {
      $out_array[] = $tb->message($this->message);
    } elseif (isset ($this->error)) {
      $out_array[] = $tb->error($this->error);
    }


    //основной блок
    $list = '';
    //постраничный вывод begin
    $perpage = 60;
    $page    = \Service::request()->_('page');

    if (!isset ($page) || $page < 1) {
      $page = 1;
    }

    $count       = $this->r->holidays->getHolidaysCount();
    $pages_count = ceil($count / $perpage);
    if ($page > $pages_count) {
      $page = $pages_count;
    }

    //вывод финальный. ункомпрессед РГБ!
    if ($pages_count > 1) {
      $list .= "<table align=\"center\"><tr>\n";
      if ($page > 1) {
        $list .= "<td><a href=\"holidays.php?page=" . ($page - 1) . "\"><img src=\"" . base_url(paths()->getAssetsDir('images/page_previous.gif')) . "\" hspace=2 border=0></a></td>\n";
      }
      $list .= "<td>\n";
      for ($i = 1; $i <= $pages_count; $i++) {
        if ($i == $page) {
          $list .= " <b>$i</b> ";
        } else {
          $list .= " <a href=\"holidays.php?page=$i\">$i</a> ";
        }
      }
      $list .= "</td>\n";
      if (($page + 1) <= $pages_count) {
        $list .= "<td><a href=\"holidays.php?page=" . ($page + 1) . "\"><img src=\"" . base_url(paths()->getAssetsDir('images/page_next.gif')) . "\" hspace=2 border=0></a></td>\n";
      }
      $list .= "</tr></table><br>\n";
    }
    $limit_begin = $perpage * ($page - 1);
    //постраничный вывод end

    //таблицы дат праздников
    if ($this->r->holidays->getHolidaysList($limit_begin, $perpage, $holidays)) {
      $tables_columns = [];
      $i              = 0;
      foreach ($holidays as $holiday) {
        if (!isset($tables_columns[(int)($i / 15)])) {
          $tables_columns[(int)($i / 15)] = '';
        }
        $tables_columns[(int)($i / 15)] .= '<tr><td class="dark">' . date(
            'd.m.Y',
            strtotime($holiday['date'])
          ) . '</td>' . (config('holidays')->useHolidayAsSanday() ? '<td class="light" style="text-align:center">' . ($holiday['sunday'] == 1 ? '+' : '-') .
            '</td>'
            : '') . '<td class="light"><a href="holidays.php?action=removeHoliday&holiday_id=' . $holiday['holiday_id'] . '" onclick="return ifConfirm ()" class="btnRemove">' . lang('button_remove') . '</a></td></tr>' . "\n";
        $i++;
      }

      $list .= '<table align="center" cellspacing="10">' . "\n<tr>\n";
      foreach ($tables_columns as $column) {
        $list .= '<td width="' . (100 / count($tables_columns)) . '%" valign="top">' . "\n";
        $list .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main">';
        $list .= "\n<tr><th>" . lang('Date') . '</th>' . (config('holidays')->useHolidayAsSanday() ? '<th>' . lang('Use Sunday Prices',
              'holiday') . '</th>' : '') . '<th>' . lang('Action') . '</th></tr>';
        $list .= $column . "</table>\n</td>\n";
      }
      $list .= "</tr>\n</table>\n";
    }


    //форма добавления
    //проставляем уже введенные данные
    $row = isset ($this->error) ? ['date' => $_POST['date']] : [];
    $add = $this->getHolidayInputFields(0, $row);

    $out_array[] = $add;
    $out_array[] = $list;

    return $out_array;
  }

  function getHolidayInputFields($mode, $row = [])
  {
    if (!isset ($row['date'])) {
      $row['date'] = date('d.m.Y');
    }
    //html
    $tb  = getThemeBuilder();
    $out = '<form action="holidays.php?action=insertHoliday" method="post">' . "\n";
    if ($mode == 1) {
      $out .= "<input type=\"hidden\" name=\"client_id\" value=\"" . $row['client_id'] . "\">\n";
    }
    ob_start();
    ?>
    <tr>
      <th colspan="2"><?= lang('Add public holidays', 'holiday') ?></th>
    </tr>
    <tr>
      <td class="light"><?= lang('Date') ?>:</td>
      <td class="light">
        <input type="text" name="date" class="input small" value="<?= $row['date'] ?>"/>
      </td>
    </tr>
    <?php
    if (config('holidays')->useHolidayAsSanday()) { ?>
      <tr>
        <td class="dark"><?= lang('Use Sunday Prices', 'holiday') ?>:</td>
        <td class="dark">
          <input type="checkbox" name="sunday" value="1" <?= (isset($row['sunday']) && $row['sunday'] == 1 ? 'checked' : '') ?>/>
        </td>
      </tr>
      <?php
    } ?>
    <tr>
      <th align="center" colspan="2">
        <input type="submit" value="<?= lang('button_create') ?>" class="button"> &nbsp;
        <input type="reset" value="<?= lang('button_reset') ?>" class="button">
      </th>
    </tr>
    <?
    $tbl = ob_get_contents();
    ob_end_clean();
    $out .= $tb->table($tbl, false);

    return $out;
  }


  /* календарь праздников */
  function showCalendar()
  {
    ob_start();

    //берем внешнее значение
    if (isset ($_GET['year'])) {
      $year = (int)$_GET['year'];
    } elseif (isset ($_POST['year'])) {
      $year = (int)$_POST['year'];
    } else {
      $year = date('Y');
    }

    //проверка корректности
    if ($year < date('Y') - 1 || $year > date('Y') + 2) {
      $year = date('Y');
    }

    //пробиваем праздники за этот год
    $holidays_in_selected_year = $this->r->holidays->getHolidaysByYear($year);

    echo '<form action="holidays.php?action=updateCalendar" onsubmit="holidaysCalendarSubmit (' . $year . ', this)" onreset="holidaysCalendarReset ()" method="post">' . "\n";
    echo '<input type="hidden" name="holidays" value="">' . "\n";
    echo '<input type="hidden" name="year" value="">' . "\n";
    echo '<div align="center">' . "\n";

    //список годов
    $years = [];
    for ($i = date('Y') - 1; $i <= date('Y') + 2; $i++) {
      if ($year == $i) {
        $years[] = '<b>' . $i . '</b>';
      } else {
        $years[] = '<a href="holidays.php?action=showCalendar&year=' . $i . '">' . $i . '</a>';
      }
    }

    echo '<table border="0" class="holidaysCalendar" cellspacing="12" cellpadding="0">' . "\n";
    echo '<tr><th colspan="4">' . join(' | ', $years) . '</th></tr>' . "\n";
    //месяцы
    $weekday = date('w', strtotime($year . '-1-1'));
    if ($weekday == 0) {
      $weekday = 7;
    }
    //получился день недели начиная с 1, т.е. 1 - понедельник

    for ($month = 1; $month <= 12; $month++) {
      //рисуем таблицу месяца
      $tbl = '<table border="0" cellspacing="2" cellpadding="1" class="month">' . "\n" . '<tr><th colspan="7">' . TranslateHelper::translateMonth(
          $month
        ) . ' - ' . $year . '</th></tr>' . "\n";

      //пред-дни
      if ($weekday != 1) {
        $tbl .= '<tr>' . "\n";
        for ($i = 1; $i < $weekday; $i++) {
          $tbl .= '<td class="w' . $i . '">&nbsp;</td>' . "\n";
        }
      }
      for ($day = 1; $day <= CalendarHelper::getDaysInMonth($year, $month); $day++) {
        if ($weekday == 1) {
          $tbl .= '<tr>' . "\n";
        }

        //<br/><input type="checkbox" name="' . $year . '-' . $month . '-' . $day . '">
        $value = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $tbl   .= '<td onclick="s (this)" class="w' . $weekday . '" id="' . $value . '">' . $day . '</td>' . "\n";

        if ($weekday == 7) {
          $tbl     .= '</tr>' . "\n";
          $weekday = 1;
        } else {
          $weekday++;
        }
      }
      //пост-дни
      if ($weekday != 1) {
        for ($i = $weekday; $i <= 7; $i++) {
          $tbl .= '<td class="w' . $i . '">&nbsp;</td>' . "\n";
        }
        $tbl .= '</tr>' . "\n";
      }

      $tbl .= '</table>' . "\n";

      //ячейка месяца
      $item = '<td valign="top" width="25%">' . "\n" . $tbl . '</td>' . "\n";

      if ($month == 1) {
        echo '<tr>' . "\n" . $item;
      } elseif (($month - 1) % 4 == 0) {
        echo '</td>' . "\n" . '<tr>' . $item . "\n";
      } else {
        echo $item;
      }
    }
    echo '<tr><th colspan="4">' . join(' | ', $years) . '</th></tr>' . "\n";
    echo '</table>' . "\n";
    echo '<input type="submit" value="' . lang('button_update') . '" class="button"/>' . "\n";
    echo '<input type="reset" value="' . lang('button_reset') . '" class="button"/>' . "\n";
    echo '</div>' . "\n";
    echo '</form>' . "\n";
    ?>
    <script>
      //изначально выбранные дни - то, что хранится в БД
      <?
      if (count($holidays_in_selected_year) > 0) {
        echo 'selected_days_original = new Array ("' . join('", "', $holidays_in_selected_year) . '");' . "\n";
      } else {
        echo 'selected_days_original = new Array ();' . "\n";
      }
      ?>
      //текущие выбранные дни
      selected_days = new Array()
      restoreOriginalSelectedDays()
      drawSelectedDays()
    </script>
    <?
    $out = ob_get_contents();
    ob_end_clean();

    return [$out];
  }

  function updateCalendar()
  {
    $items = explode(',', $_POST['holidays']);
    if (is_array($items) && count($items) > 0) {
      foreach ($items as $i) {
        $items2[] = addslashes($i);
      }
    }

    $this->r->holidays->setAllHolidaysByYear((int)$_POST['year'], $items2);

    return $this->showCalendar();
  }

}

$a                = new holidays_admin;
$_page['content'] = $a->start();
$_page['key']     = $a->getPageKey();

