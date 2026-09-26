<?

use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\engines\Engines;
use AC\core\modules\areas\models\AreasModel;
use AC\core\system\modules\modComm\helpers\ModCommHelper;


class spec_price_admin
{
  /**
   * @var $e Engines
   */
  protected $e;

  function start()
  {
    $this->e = new Engines();

    switch (\Service::request()->_get('action')) {
      case 'insert':
        return $this->insert();
        break;
      case 'edit':
        return $this->edit();
        break;
      case 'editTime':
        return $this->editTimeForm();
        break;
      case 'changeTime':
        return $this->changeTime();
        break;
      case 'change':
        return $this->change();
        break;
      case 'remove':
        return $this->remove();
        break;
      default:
        return $this->getList();
    }
  }

  //удалить новость
  function remove()
  {
    $this->e->specprice->removeSprice((int)$_GET['sprice_id']);

    return $this->getList();
  }

  //добавить новость
  function insert()
  {
    $this->e->specprice->insertSprice(
      (int)$_POST['sort'],
      $_POST['code'],
      $_POST['title'],
      NumberHelper::float($_POST['rate']),
      (isset($_POST['for_all']) ? 1 : 0),
      Service::request()->_('type_sport', null)
    );

    return $this->getList();
  }

  //изменить новость
  function change()
  {
    $this->e->specprice->changeSprice(
      (int)$_GET['sprice_id'],
      (int)$_POST['sort'],
      $_POST['code'],
      $_POST['title'],
      NumberHelper::float($_POST['rate']),
      (isset($_POST['for_all']) ? 1 : 0),
      Service::request()->_('type_sport', null)
    );

    return $this->getList();
  }

  //форма редактирования новости
  function edit()
  {
    //если новости нет - показываем список
    if ($this->e->specprice->getSprice((int)$_GET['sprice_id'], $item)) {
      //форма редактирования
      $out = $this->getFields(1, $item);

      return [$out, '<span class="back"><a href="config_specprice.php">' . lang('Back') . '</a></span>'];
    } else {
      return $this->getList();
    };
  }

  function changeTime()
  {
    if (isset($_GET['sprice_id'])) {
      $this->e->specprice->changeSpriceDuration(
        (int)$_GET['sprice_id'],
        (isset($_POST['active']) ? 1 : 0),
        $_POST['start_year'] . '-' . $_POST['start_month'] . '-' . $_POST['start_day'],
        $_POST['finish_year'] . '-' . $_POST['finish_month'] . '-' . $_POST['finish_day'],
        (isset($_POST['time']) ? $_POST['time'] : false)
      );
    }

    return $this->getList();
  }

  function editTimeForm()
  {
    if ($this->e->specprice->getSprice((int)$_GET['sprice_id'], $item)) {
      $out      = "";
      $interval = 60;
      $prev     = 0;
      $finish   = 0;
      if ($this->e->areas->getAllAreasData($areas_data)) {
        //todo когда будем переносить в модуль сделать отдельный запрос к модулю areas и получать уже готовые html табличку - доработать также в модуле stocks
        foreach ($areas_data as $area) {
          if (!empty($item['type_sport']) && $item['type_sport'] !== $area['type_id'] . '_' . $area['sport_id']) {
            continue;
          }
          $interval = min($area['period'], $interval);
          $prev     = ($area['start'] < $prev || !$prev ? $area['start'] : $prev);
          $finish   = ($area['finish'] > $finish || !$finish ? $area['finish'] : $finish);
        }
        $prev   = substr($prev, 0, 5);
        $finish = substr($finish, 0, 5);

        $out           = '<form method="post" action="config_specprice.php?action=changeTime&sprice_id=' . $item['sprice_id'] . '">' . "\n";
        $out           .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
        $out           .= "<tr><th colspan=\"2\">" . lang('Validity in time', 'config_spec_price') . " - " . $item['title'] . "</th></tr>\n";
        $out           .= '<tr><td class="dark">' . lang('Active') . ':</td><td class="dark"><input type="checkbox" class="input" name="active" value="1" ' . ((isset($item['duration']) && $item['duration'] == 1)
            ? 'checked' : '') . '/></td></tr>' . "\n";
        $out           .= '<tr><td class="light">' . lang('From') . ':</td><td class="light">' . "\n";
        $out           .= '<table cellspacing="0" cellpadding="0" border="0"><tr>' . "\n";
        $unix_cur_date = (isset($item['duration_start'])
          ? strtotime($item['duration_start'])
          : strtotime(
            date('Y-m-d')
          ));

        //годы
        $select_years = '<select name="start_year">' . "\n";
        for ($i = date('Y') - 1; $i <= date('Y') + 3; $i++) {
          $select_years .= '<option value="' . $i . '"' . ($i == date(
              'Y',
              $unix_cur_date
            ) ? ' selected' : '') . '>' . $i . '</option>' . "\n";
        }
        $select_years .= '</select>' . "\n";
        $out          .= '			<td>' . $select_years . '</td>';

        //месяцы
        $select_monthes = '<select name="start_month">' . "\n";
        for ($i = 1; $i <= 12; $i++) {
          $select_monthes .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date(
              'm',
              $unix_cur_date
            ) ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
        }
        $select_monthes .= '</select>' . "\n";
        $out            .= '			<td>' . $select_monthes . '</td>';

        //дни
        $select_days = '<select name="start_day">' . "\n";
        for ($i = 1; $i <= 31; $i++) {
          $select_days .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date(
              'd',
              $unix_cur_date
            ) ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
        }
        $select_days .= '</select>' . "\n";
        $out         .= '			<td>' . $select_days . '</td>';
        $out         .= "	</tr></table>\n";
        $out         .= '</td></tr>' . "\n";
        $out         .= '<tr><td class="light">' . lang('Until') . ':</td><td class="light">' . "\n";
        $out         .= '<table cellspacing="0" cellpadding="0" border="0"><tr>' . "\n";

        $unix_cur_date = (isset($item['duration_finish'])
          ? strtotime($item['duration_finish'])
          : strtotime(
            date('Y-m-d')
          ));

        //годы
        $select_years = '<select name="finish_year">' . "\n";
        for ($i = date('Y') - 1; $i <= date('Y') + 3; $i++) {
          $select_years .= '<option value="' . $i . '"' . ($i == date(
              'Y',
              $unix_cur_date
            ) ? ' selected' : '') . '>' . $i . '</option>' . "\n";
        }
        $select_years .= '</select>' . "\n";
        $out          .= '			<td>' . $select_years . '</td>';

        //месяцы
        $select_monthes = '<select name="finish_month">' . "\n";
        for ($i = 1; $i <= 12; $i++) {
          $select_monthes .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date(
              'm',
              $unix_cur_date
            ) ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
        }
        $select_monthes .= '</select>' . "\n";
        $out            .= '			<td>' . $select_monthes . '</td>';

        //дни
        $select_days = '<select name="finish_day">' . "\n";
        for ($i = 1; $i <= 31; $i++) {
          $select_days .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date(
              'd',
              $unix_cur_date
            ) ? ' selected' : '') . '>' . sprintf("%02d", $i) . '</option>' . "\n";
        }
        $select_days .= '</select>' . "\n";
        $out         .= '			<td>' . $select_days . '</td>';
        $out         .= "	</tr></table>\n";
        $out         .= '</td></tr>' . "\n";
        $out         .= '<tr><td class="light" colspan="2">' . "\n";
        $out         .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
        $out         .= "<tr><th width='90px'>" . lang('Time in hours') . "</th>
                            				<th>" . lang('weekday_small_0') . "<br /><input type=\"checkbox\" value=\"1\" onchange=\"selectAllCheck(this, 'weekday_c_0')\"/></th>
                            				<th>" . lang('weekday_small_1') . "<br /><input type=\"checkbox\" value=\"1\" onchange=\"selectAllCheck(this, 'weekday_c_1')\"/></th>
                            				<th>" . lang('weekday_small_2') . "<br /><input type=\"checkbox\" value=\"1\" onchange=\"selectAllCheck(this, 'weekday_c_2')\"/></th>
                            				<th>" . lang('weekday_small_3') . "<br /><input type=\"checkbox\" value=\"1\" onchange=\"selectAllCheck(this, 'weekday_c_3')\"/></th>
                            				<th>" . lang('weekday_small_4') . "<br /><input type=\"checkbox\" value=\"1\" onchange=\"selectAllCheck(this, 'weekday_c_4')\"/></th>
                            				<th>" . lang('weekday_small_5') . "<br /><input type=\"checkbox\" value=\"1\" onchange=\"selectAllCheck(this, 'weekday_c_5')\"/></th>
                            				<th>" . lang('weekday_small_6') . "<br /><input type=\"checkbox\" value=\"1\" onchange=\"selectAllCheck(this, 'weekday_c_6')\"/></th></tr>\n";
        $j           = 0;
        do {
          //проходимся по временным интервалам
          $current = TimeHelper::addMinutes2MySQLTime($prev, $interval);

          $class = $j % 2 == 0 ? 'light' : 'dark';
          $out   .= "<tr><td class=\"" . $class . "\"><b>" . $prev . " - " . $current . "</b></td>";
          //промежутки по дням недели
          for ($i = 0; $i < 7; $i++) {
            $out .= '<td class="' . $class . '" align="center"><input class="input weekday_c_' . $i . '" type="checkbox" name="time[' . $i . '][' . $prev . ']" value="' . $current . '" ' . (isset($item['durations'][$i][$prev . ':00'])
                ? 'checked' : '') . '></td>' . "\n";
          }
          $out  .= "</tr>\n";
          $prev = $current;
          $j++;
        } while (strtotime($current) < strtotime(TimeHelper::convertTime24($finish)));

        $out .= "</table><br />\n";
        $out .= '</td></tr>' . "\n";
        $out .= '<tr><th align="center" colspan="8"><input type="submit" class="button" value="' . lang('button_update') . '">&nbsp;<input type="reset" class="button" value="' . lang('button_reset') . '"></th></tr>' . "\n";
        $out .= "</table><br />\n";
        $out .= "</form>\n";
      }

      return [$out, '<span class="back"><a href="config_specprice.php">' . lang('Back') . '</a></span>'];
    } else {
      return $this->getList();
    }
  }

  //список настроек
  function getList()
  {
    $sports = ModCommHelper::get('areas', 'areas/relevantSportsByType', [], 'sportsByType', []);
    $out    = '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
    $out    .= '<tr><th>' . lang('Line', 'config_spec_price') . '</th><th>' . lang('Abbreviation',
        'config_spec_price') . '</th><th>' . lang('Title') . '</th><th>' . lang('Prices') . ', ' . CURR_VALUTE . '</th><th>' . lang(
        'title_choice_type_sport',
        'config_spec_price'
      ) . '</th><th>' . lang('For all users', 'config_spec_price') . '</th><th colspan="3">' . lang('Action') . '</th></tr>' . "\n";

    if ($this->e->specprice->getSprices($stocks)) {
      foreach ($stocks as $item) {
        $out .= '<tr>' .
          '<td class="dark" align="center">' . $item['sort'] . '</td>' .
          '<td class="light">' . $item['code'] . '</td>' .
          '<td class="light">' . $item['title'] . '</td>' .
          '<td class="light">' . number_format($item['rate'], 2, ',', '.') . '</td>';
        $out .= '<td class="dark">' .
          (isset($item['type_sport']) && !empty($item['type_sport']) ?
            $sports[$item['type_sport']]->title
            : lang('all_choice', 'config_spec_price')) . '</td>';
        $out .= '<td class="dark" style="text-align: center">' . ((isset($item['for_all']) && $item['for_all'] == 1) ? '<i class="fas fa-check"></i>'
            : '') . '</td>';
        $out .= '<td class="light"><a href="config_specprice.php?action=editTime&sprice_id=' . $item['sprice_id'] . '" class="btnEdit">' . lang('Validity in time',
            'config_spec_price') . '</a></td>';
        $out .= '<td class="light"><a href="config_specprice.php?action=edit&sprice_id=' . $item['sprice_id'] . '" class="btnEdit">' . lang('button_update') . '</a></td>';
        $out .= '<td class="light"><a href="config_specprice.php?action=remove&sprice_id=' . $item['sprice_id'] . '" onclick="return ifConfirm ()" class="btnRemove">' . lang('button_remove') . '</a></td>';
        $out .= '</tr>' . "\n";
      }
    }

    $out .= "</table>\n";

    return [$out, $this->getFields(0)];
  }

  function getFields($mode, $row = [])
  {
    //html
    $out = '<form action="config_specprice.php?action=' . ($mode == 0 ? 'insert'
        : 'change&sprice_id=' . $row['sprice_id']) . '" method="post">' . "\n";
    ob_start();
    ?>
    <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
      <tr>
        <th colspan="2"><?= ($mode == 0 ? lang('title_create') : lang('title_update')) ?></th>
      </tr>
      <tr>
        <td class="dark"><?= lang('Line', 'config_spec_price') ?>:</td>
        <td class="dark"><input type="text" name="sort" class="input wide" value="<?= (isset($row['sort']) ? $row['sort'] : '') ?>"/></td>
      </tr>
      <tr>
        <td class="light"><?= lang('Abbreviation', 'config_spec_price') ?>:</td>
        <td class="light"><input type="text" name="code" class="input wide" maxlength="2"
                                 value="<?= str_replace('"', '&amp;', (isset($row['code']) ? $row['code'] : '')) ?>"/></td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Title') ?>:</td>
        <td class="dark"><input type="text" name="title" class="input wide"
                                value="<?= (isset($row['title']) ? str_replace('"', '&amp;', $row['title']) : '') ?>"/></td>
      </tr>
      <tr>
        <td class="light"><?= lang('Prices') ?>, <?= CURR_VALUTE ?>:</td>
        <td class="dark"><input type="text" name="rate" class="input wide"
                                value="<?= number_format((isset($row['rate']) ? $row['rate'] : 0), 2, ',', '.') ?>"/></td>
      </tr>
      <tr>
        <td class="light"><?= lang('title_choice_type_sport', 'config_spec_price') ?>:</td>
        <td class="light">
          <?= ModCommHelper::get('areas', 'areas/relevantSportsByTypeAsSelect', [
            'current'    => $row['type_sport'] ?? null,
            'all_sports' => config('specPrices')->useForAllSorts()
          ], 'sports_by_type', '') ?>
        </td>
      </tr>
      <tr>
        <td class="dark"><?= lang('For all users', 'config_spec_price') ?>:</td>
        <td class="dark"><input type="checkbox" name="for_all" class="input"
                                value="1" <?= ((isset($row['for_all']) && $row['for_all'] == 1) ? 'checked' : '') ?> />
        </td>
      </tr>
      <tr>
        <th align="center" colspan="2">
          <input type="submit" value="<?= ($mode == 0 ? lang('button_create') : lang('button_update')) ?>" class="button">
          &nbsp;
          <input type="reset" value="<?= lang('button_reset') ?>" class="button">
        </th>
      </tr>
    </table>
    <?
    $out .= ob_get_contents();
    ob_end_clean();

    return $out;
  }

}

$a                = new spec_price_admin;
$_page['content'] = $a->start();
$_page['key']     = 'config_spec_price';
