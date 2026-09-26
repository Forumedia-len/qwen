<?php

use AC\app\helpers\LayoutHelper;
use AC\core\engines\AreasEngine;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\helpers\TranslateHelper;
use AC\core\engines\Engines;
use AC\core\modules\areas\models\AreasLightsModel;
use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\modules\config\models\ConfigOpenTypeModel;
use AC\core\modules\webIo\models\WebIoModel;
use AC\core\modules\webIo\models\WebIoTypeSatesModel;

$_page['key'] = 'areas';

class areas_admin
{
  /**
   * @var Engines
   */
  public $r;

  function start()
  {
    $this->r = Service::engines();

    if (isset ($_GET['action'])) {
      $action = $_GET['action'];
    } else {
      return $this->getAreasList();
    }

    switch ($action) {
      case 'changeAreaData':
        return $this->changeAreaData();
        break;
      case 'editAreaData':
        return $this->getAreaDataEditForm();
        break;
      //цены на тип площадок
      case 'editAreasPrice':
        return $this->editAreasPrice();
        break;
      case 'editAreaPrice':
        return $this->editAreaPrice();
        break;
      case 'changeAreasPrice':
        return $this->changeAreasPrice();
        break;
      case 'changeAreaPrice':
        return $this->changeAreaPrice();
        break;
      case 'changeAreasPricesForPlayers':
        return $this->changeAreasPricesForPlayers();
        break;
      //комментарии на тип
      case 'editTypeComments':
        return $this->editTypeComments();
        break;
      case 'changeTypeComments':
        return $this->changeTypeComments();
        break;
      //Установка света
      case 'editLightData':
        return $this->editLightDataForm();
        break;
      case 'changeLightData':
        return $this->changeLightData();
        break;
      //дверные коды
      case 'editCodeData':
        return $this->editCodeDataForm();
        break;
      case 'changeCodeData':
        return $this->changeCodeData();
        break;
      case 'viewCodeData':
        return $this->viewCodeData();
        break;
      case 'importCodeForm':
        return $this->importCodeForm();
        break;
      case 'importCode':
        return $this->importDoorCodeRequest();
        break;
      case 'exportCode':
        return $this->exportDoorCodeRequest();
        break;
      case 'generateCode':
        return $this->generateDoorCode();
        break;
      //сезоны
      case 'editSeasons':
        return $this->editSeasons();
        break;
      case 'changeSeasonsData':
        return $this->changeSeasonsData();
        break;
      case 'changeSeasons':
        return $this->changeSeasons();
        break;
      case 'active':
        return $this->active();
        break;
      default:
        return $this->getAreasList();
    }
  }

  //изменение значения
  function changeAreaData()
  {
    if (isset ($_POST['area_id']) && isset ($_POST['title']) && isset ($_POST['comment']) && isset ($_POST['sort']) && isset ($_POST['timetable']) && !empty ($_POST['timetable'])) {
      for ($i = 0; $i < 7; $i++) {
        if (isset ($_POST['timetable'][$i]) && $_POST['timetable'][$i][0] == 1 && $_POST['timetable'][$i][1] >= 0 && $_POST['timetable'][$i][1] <= 1440 && $_POST['timetable'][$i][2] >= 0 && $_POST['timetable'][$i][2] <= 1440) {
          $timetable[$i][0] = true;
          $timetable[$i][1] = $_POST['timetable'][$i][1];
          $timetable[$i][2] = $_POST['timetable'][$i][2];
        }
      }
      if (!isset ($timetable)) {
        $timetable = [];
      }
      $webIo = [
        'light_on'   => (int)Service::request()->_('light_on', 0),
        'heating_on' => (int)Service::request()->_('heating_on', 0),
        'net_on'     => (int)Service::request()->_('net_on', 0),
      ];
      $this->r->areas->setWebIoStatus($_POST['area_id'], $webIo);
      WebIoTypeSatesModel::setActive('alias', $this->r->areas->getAreasWebIoTypeStateOn());


      if ($this->r->areas->changeAreaData(
        $_POST['area_id'],
        $_POST['title'],
        $_POST['comment'],
        (int)$_POST['sort'],
        $timetable,
        $error_code
      )) {
        $this->message = lang('message_element_base_update', 'message_success');

        return $this->getAreasList();
      } else {
        if ($error_code == 1) {
          $this->error = lang('Area not found', 'message_error');

          return $this->getAreasList();
        } else {
          $codes[2]    = lang('No time range specified!', 'message_error');
          $codes[3]    = lang('Time intervals check false', 'message_error');
          $this->error = $codes[$error_code];

          return $this->getAreaDataEditForm();
        }
      }
    } else {
      //не все данные пришли
      return $this->getAreasList();
    }
  }

  //форма редактирования значения
  function getAreaDataEditForm()
  {
    if (isset ($_POST['area_id'])) {
      $area_id = (int)$_POST['area_id'];
    } elseif (isset ($_GET['area_id'])) {
      $area_id = (int)$_GET['area_id'];
    } else {
      return $this->getAreasList();
    }
    if ($this->r->areas->getAreaData($area_id, $area_data) == true) {
      $output = [];

      $out = "<h1>" . lang('Edit playground', 'areas') . "</h1>\n";

      if (isset ($this->error)) {
        $out .= '<span class="error"><b>' . $this->error . '</b></span>';
      }

      $out .= '<form method="post" action="areas.php?action=changeAreaData">' . "\n";
      $out .= '<input type="hidden" name="area_id" value="' . $area_id . '">' . "\n";
      $out .= '<center>' . "\n";

      $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
      $out .= '<tr>';
      $out .= '<th colspan="4">' . lang('Edit playground', 'areas') . '</th>';
      $out .= '</tr>' . "\n";

      $out .= '<tr>';
      $out .= '<td class="light">' . lang('Type') . '</td>';
      $out .= '<td class="light" colspan="3">' . $this->r->areas->getTitleByAreaId($area_data['area_id']) . '</td>';
      $out .= '</tr>' . "\n";

      $out .= '<tr>';
      $out .= '<td class="dark">' . lang('Time step', 'areas') . ':</td>';
      $out .= '<td class="dark" colspan="3">' . $area_data['period'] . '</td>';
      $out .= '</tr>' . "\n";

      $out .= '<tr>';
      $out .= '<td class="light">' . lang('Title') . '</td>';
      $out .= '<td class="light" colspan="3"><input type="text" name="title" class="input wide" value="' . $area_data['title'] . '"></td>';
      $out .= '</tr>' . "\n";

      $out .= '<tr>';
      $out .= '<td class="dark">' . lang('Number', 'areas') . '</td>';
      $out .= '<td class="dark" colspan="3"><input type="text" name="sort" class="input wide" value="' . $area_data['sort'] . '"></td>';
      $out .= '</tr>' . "\n";


      $out .= '<tr>';
      $out .= '<td class="light">' . lang('Description', 'areas') . ':</td>';
      $out .= '<td class="light" colspan="3"><textarea name="comment" class="input wide">' . $area_data['comment'] . '</textarea></td>';
      $out .= '</tr>' . "\n";
      if (module('webIo')->useModel()->checkUse()) {
        $out .= '<tr>';
        $out .= '<td class="light">' . lang('Control', 'areas') . ':</td>';
        $out .= '<td class="light" colspan="3">';
        foreach (WebIoTypeSatesModel::getTypeSates('alias', false, false, true) as $alias => $typeSate) {
          $out .= '<label for="' . $alias . '_on">' . $typeSate->title . '</label>
              <input type="checkbox" name="' . $alias . '_on" id="' . $alias . '_on" value="1" ' . ($area_data[$alias . '_on'] ? 'checked'
              : '') . ' style="margin: 4px 30px 4px 5px">';
        }
        $out .= '</tr>' . "\n";
      }

      $out .= '<tr>';
      $out .= '<td colspan="4">&nbsp;</td>';
      $out .= '</tr>' . "\n";

      $out .= '<tr>';
      $out .= '<td class="light" colspan="4" style="line-height: 20px">' . lang(
          'explanatory_text_about_changes_in_the_opening_hours_of_the_area',
          'areas'
        ) . '</td>';
      $out .= '</tr>' . "\n";

      $out .= '<tr>';
      $out .= '<td class="dark">' . lang('Opening hours', 'areas') . ':</td>';
      $out .= '<td class="dark" align="center">' . lang('Working day', 'areas') . '</td>';
      $out .= '<td class="dark" align="center">' . lang('From') . '</td>';
      $out .= '<td class="dark" align="center">' . lang('Until') . '</td>';
      $out .= '</tr>' . "\n";

      //получаем раписание площадки
      $this->r->areas->getAreaFullTimeTable($area_id, $timetable);
      //подготавливаем массив для select'ов
      for (
        $i = 0 + $area_data['offset_start']; $i <= 1440; $i = $i + ($area_data['period'] % 45 == 0 && $area_data['offset_start'] == 0
          ? 15 : $area_data['period'])
      ) {
        $time_values[$i] = TimeHelper::convertMinutes2MySQLTime($i);
      }

      //рапиновка активности и времени работы по дням недели
      for ($i = 0; $i < 7; $i++) {
        if ($i % 2 == 0) {
          $class = 'light';
        } else {
          $class = 'dark';
        }

        $options_from = '';
        foreach ($time_values as $minutes => $time_value) {
          if ($timetable[$i][0] == $time_value) {
            $options_from .= '<option value="' . $minutes . '" selected>' . $time_value . '</option>' . "\n";
          } else {
            $options_from .= '<option value="' . $minutes . '">' . $time_value . '</option>' . "\n";
          }
        }

        $options_to = '';
        foreach ($time_values as $minutes => $time_value) {
          if ($timetable[$i][1] == $time_value) {
            $options_to .= '<option value="' . $minutes . '" selected>' . $time_value . '</option>' . "\n";
          } else {
            $options_to .= '<option value="' . $minutes . '">' . $time_value . '</option>' . "\n";
          }
        }

        $out .= '<tr>';
        $out .= '<td class="' . $class . '">' . TranslateHelper::translateWeekday($i) . '</td>';
        $out .= '<td class="' . $class . '" align="center"><input type="checkbox" name="timetable[' . $i . '][0]"' . (isset ($timetable[$i])
            ? ' checked' : '') . ' value="1"></td>';
        $out .= '<td class="' . $class . '" align="center"><select class="input" name="timetable[' . $i . '][1]">' . $options_from . '</select></td>';
        $out .= '<td class="' . $class . '" align="center"><select class="input" name="timetable[' . $i . '][2]">' . $options_to . '</select></td>';
        $out .= '</tr>' . "\n";
      }

      $out .= '<tr><th align="center" colspan="4"><input type="submit" class="button" value="' . lang('button_update') . '">&nbsp;<input type="reset" class="button" value="' . lang('button_reset') . '"></th></tr>' . "\n";
      $out .= "</table>\n";

      $out .= '</center>' . "\n";
      $out .= '</form>' . "\n";

      $output[] = $out;
      $output[] = "<span class=\"back\"><a href=\"areas.php\">" . lang('Back') . "</a></span>";

      return $output;
    } else {
      return $this->getAreasList();
    }
  }

  //список площадок
  function getAreasList()
  {
    /** @var AreasEngine $areasEngine */
    $areasEngine = getEngine('areas', false);
    //сообщения и ошибки
    $output = [];
    $out    = '';
    if (isset ($this->message)) {
      $out .= '<span class="message">' . $this->message . '</span>';
    } elseif (isset ($this->error)) {
      $out .= '<span class="error"><b>' . $this->error . '</b></span>';
    }
    if ($out != '') {
      $output[] = $out;
    }

    //сезоны
    $out           = "<h1>" . lang('Seasons', 'areas') . "</h1>\n";
    $periodByMonth = $areasEngine->getPeriodByMonths();
    if ($seasons = $areasEngine->getSeasons()) {
      $keySeasons = array_keys($seasons);
      $out        .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
      $out        .= '<tr><th colspan="2">' . lang('Designation', 'areas') . '</th><th>' . lang('Start', 'areas') . '</th><th>' . lang(
          'Action'
        ) . '</th></tr>' . "\n";
      $value      = [];
      foreach ($seasons as $s) {
        $value[$s['period_id']] = $s['colors'];

        $out .= '<tr>';
        $out .= '<td class="dark" style="width: 10px">' . LayoutHelper::renderAreaTypeSquare($s['colors']) . '</td>';
        $out .= '<td class="light">' . $s['title'] . '</td>';
        $out .= '<td class="dark">' . date(
            'd',
            strtotime($s['start'])
          ) . ' ' . TranslateHelper::translateMonth(date('n', strtotime($s['start']))) . '</td>';
        $out .= '<td class="light"><a href="areas.php?action=editSeasons&period_id=' . $s['period_id'] . '" class="btnEdit">' . lang(
            'button_save'
          ) . '</a></td>';
        $out .= "</tr>\n";
      }
      $out .= "</table><br />\n";
      foreach ($value as $v => $c) {
        $out .= '<table border="0" cellspacing="1" cellpadding="0" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
      }
      $out .= '<tr>';
      for ($m = 1; $m <= 12; $m++) {
        $out .= '<td><div class="month-button winter-color" style="background-color:#' . $value[$periodByMonth[$m]] . '">' . TranslateHelper::translateMonth(
            $m
          );
        if ($m == date('n', strtotime($seasons[$keySeasons[0]]['start']))) {
          $out .= '<div style="font-size:22px; padding-top:8px;">' . date(
              'd',
              strtotime($seasons[$keySeasons[0]]['start'])
            ) . '</div>';
        } else {
          if ($m == date('n', strtotime($seasons[$keySeasons[1]]['start']))) {
            $out .= '<div style="font-size:22px; padding-top:8px;">' . date(
                'd',
                strtotime($seasons[$keySeasons[1]]['start'])
              ) . '</div>';
          }
        }
        $out .= '</div></td>';
      }
      $out .= "</tr>\n";
      $out .= '<tr><td align="center" colspan="12"><br /><input type="submit" class="button" value="' . lang('button_save') . '"</td></tr>' . "\n";
      $out .= "</table>\n";
    }
    $output[] = $out;

    //площадки
    $out = "<h1>" . lang('Playgrounds', 'areas') . "</h1>\n";
    $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
    $out .= '<tr><th colspan="2">' . lang('Type') . '</th><th>' . lang('Playgrounds', 'areas') . '</th><th>' . lang(
        'Time step',
        'areas'
      ) . '</th><th>' . lang('Number', 'areas') . '</th><th>' . lang('Door code', 'areas')
      . '</th><th>' . lang('Active') . '</th>' . '</th><th>' . lang('Action') . '</th>'
      . (module('webIo')->useModel()->checkUse() ? '<th>' . lang('Light') . ' /' . lang('Heating') . '</th>' : '') . '</tr>' . "\n";
    if ($areasEngine->getAllAreasData($areas, false)) {
      foreach ($areas as $area) {
        $out .= '<tr>';
        $out .= '<td class="light">' . LayoutHelper::renderSquareByTypeSport($area['type_id'], $area['sport_id']) . '</td>';
        $out .= '<td class="dark">' . $areasEngine->getTitleByAreaId($area['area_id']) . '</td>';
        $out .= '<td class="dark">' . $area['title'] . '</td>' .
          '<td class="light" align="center">' . $area['period'] . '</td>' .
          '<td class="dark" align="center">' . $area['type_sort'] . '</td>' .
          '<td class="light" align="center">' . $area['sort'] . '</td>'
          . '<td class="dark" align="center">
                <a href="areas.php?action=active&active=' . (int)(!$area['active']) . '&area_id=' . $area['area_id'] . '">' . ($area['active']
            ? '<i class="fas fa-check" style="color: green"></i>'
            : '<i class="fa fa-ban fa-rotate-90" aria-hidden="true"  style="color: red"></i>') . '</a></td>';
        $out .= '<td class="light"><a href="areas.php?action=editAreaData&area_id=' . $area['area_id'] . '" class="btnEdit">' . lang(
            'button_update'
          ) . '</a></td>';
        if (module('webIo')->useModel()->checkUse()) {
          $out .= '<td class="dark">' . ($area['light_on'] == 1 || $area['heating_on'] == 1 || $area['net_on'] == 1
              ? '<a href="areas.php?action=editLightData&area_id=' . $area['area_id'] . '" class="btnEdit">' . lang('button_update') . '</a>'
              : '&nbsp;') . '</td>';
        }
        $out .= "</tr>\n";
      }
    }
    $out      .= "</table>\n";
    $output[] = $out;

    //Цены
    if ($sportsByType = $areasEngine->getSportsByType()) {
      $out = "<h1>" . lang('Prices of the playgrounds and access codes', 'areas') . "</h1>\n";
      $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
      $out .= '<tr><th colspan="2">' . lang('Type') . '</th><th colspan="3">' . lang('Action') . '</th></tr>' . "\n";
      foreach ($sportsByType as $key => $sportByType) {
        foreach ($sportByType->periods as $period) {
          $periodSuffixUrl = count($sportByType->periods) > 1 ? '&period=' . $period : '';
          $out             .= '<tr>';
          $out             .= '<td class="light">' . $sportByType->title_site_url . (count($sportByType->periods) > 1 ? ' -  ' . lang('Time step',
                'areas') . ' ' . $period : '') . '</td>';
          $out             .= '<td class="light">' . LayoutHelper::renderSquareByTypeSport($sportByType->type_id, $sportByType->sport_id) . '</td>';
          $out             .= '<td class="light"><a href="areas.php?action=viewCodeData&type_id=' . $sportByType->type_id . '&sport_id=' . $sportByType->sport_id . $periodSuffixUrl . '" class="btnEdit">'
            . lang('Access codes', 'areas') . '</a></td>';
          $out             .= '<td class="light"><a href="areas.php?action=editAreasPrice&type_id=' . $sportByType->type_id . '&sport_id=' . $sportByType->sport_id . $periodSuffixUrl . '" class="btnEdit">'
            . lang('Change prices', 'areas') . '</a></td>';
          $out             .= '<td class="light"><a href="areas.php?action=editTypeComments&type_id=' . $sportByType->type_id . '&sport_id=' . $sportByType->sport_id . $periodSuffixUrl . '" class="btnEdit">'
            . lang('Change remarks', 'areas') . '</a></td>';
          $out             .= "</tr>\n";
        }
      }
      $out      .= "</table>\n";
      $output[] = $out;
    }

    return $output;
  }


  /* РАСЦЕНКИ НА ТИП ПЛОЩАДОК */

  public function editAreaPrice()
  {
    $area_id = Service::request()->_('area_id', null);
    if ($area_id === null) {
      return $this->getAreasList();
    }
    $out = '';
    if ($this->r->areas->getAreaData($area_id, $area_data)) {
      $out .= "<h1>" . lang(
          'Prices for the',
          'areas',
          ['name_court' => $area_data['type_title'] . " - " . $area_data['sport_title'] . " - " . $area_data['title']]
        ) . "</h1>";
      if ($this->r->areas->getAreaPriceOrderBySeasonTimeWeekday($area_id, $area_price, $error_code)) {
        if ($seasons = $this->r->areas->getSeasons()) {
          $out .= '<form method="post" action="areas.php?action=changeAreaPrice">' . "\n";
          $out .= '<input type="hidden" name="area_id" value="' . $area_id . '">' . "\n";
          foreach ($seasons as $s) {
            $out     .= "<h1 style=\"text-align:center\">" . $s['title'] . "</h1>";
            $out     .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
            $out     .= "<tr><th rowspan=\"2\">" . lang('Time in hours') . "</th><th colspan=\"7\">" . lang('Weekdays') . " / " . lang(
                'Prices'
              ) . "  " . CURR_VALUTE . "</th></tr><tr><th>" . TranslateHelper::translateWeekday(
                0,
                true
              ) . "</th><th>" . TranslateHelper::translateWeekday(1, true) . "</th><th>" . TranslateHelper::translateWeekday(
                2,
                true
              ) . "</th><th>" . TranslateHelper::translateWeekday(3, true) . "</th><th>" . TranslateHelper::translateWeekday(
                4,
                true
              ) . "</th><th>" . TranslateHelper::translateWeekday(5, true) . "</th><th>" . TranslateHelper::translateWeekday(
                6,
                true
              ) . "</th></tr>\n";
            $current = $area_price['min_start'];
            $j       = 0;
            while ($current != $area_price['max_finish']) {
              $next  = TimeHelper::addMinutes2MySQLTime($current, $area_data['period']);
              $class = $j % 2 == 0 ? 'light' : 'dark';
              $out   .= "<tr><td class=\"" . $class . "\"><b>" . $current . " - " . $next . "</b></td>";
              foreach ($area_price['time_by_weekday'] as $weekday => $times) {
                $price = null;
                if ($current >= $times[0] && $current < $times[1]) {
                  $price = 0;
                }
                if (isset($area_price['prices'][$s['period_id']][$current][$weekday])) {
                  $price = $area_price['prices'][$s['period_id']][$current][$weekday];
                }
                $out .= '<td class="' . $class . '">' . ($price !== null
                    ?
                    '<input type="text" class="input smallest price" name="price[' . $weekday . '][' . $s['period_id'] . '][' . $current . ']" value="' .
                    number_format(
                      $price,
                      2,
                      ',',
                      "'"
                    ) . '">'
                    :
                    '&nbsp;') .
                  '</td>' . "\n";
              }
              $current = $next;
              $j++;
            }

            $out .= "</table><br />\n";
          }
          $out .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
          $out .= '<tr><th align="center" colspan="8"><input type="submit" class="button" value="' . lang(
              'button_update'
            ) . '">&nbsp;<input type="reset" class="button" value="' . lang('button_reset') . '"></th></tr>' . "\n";
          $out .= "</table><br />\n";
          $out .= "</form>\n";
        } else {
          $out = "<p class=\"error\">" . lang('Season not found', 'message_error') . "</p>\n";
        }
      } else {
        if ($error_code == 1) {
          $out .= "<p class=\"error\">" . lang(
              'Periods not equa - you can_t edit prices of areas of this type commonly!',
              'message_error'
            ) . "</p>\n";
        } elseif ($error_code == 2) {
          $out .= "<p class=\"error\">" . lang(
              'Attention! So far no opening time has been set for this playground type!',
              'message_error'
            ) . "</p>\n";
        }
      }
    } else {
      $out .= "Areas not found (invalid area_id)";
    }

    return [$out . "<span class=\"back\"><a href=\"areas.php\">" . lang('Back') . "</a></span>"];
  }

  //форма редактирования
  function editAreasPrice()
  {
    $output  = [];
    $type_id = Service::request()->_('type_id', null);
    if ($type_id === null) {
      return $this->getAreasList();
    }
    $type_id  = (int)$type_id;
    $sport_id = (int)Service::request()->_('sport_id', false);
    $period   = Service::request()->_('period');
    $period   = $period !== null && $period !== '' ? (int)$period : null;
    if ($this->r->areas->getTypeData($type_id, $type_row)) {
      if (config('DoubleGame')->doubleFriendsEnabled($type_id, $sport_id)
        && config('OpenType')->isPricingSystem('price_for_player', $type_id, $sport_id)
      ) {
        return $this->getAreasPricesForPlayers($type_id, $sport_id);
      }

      //тип найден
      $out = "<h1>" . lang('Prices for the', 'areas', ['name_court' => $this->r->areas->getTitleByTypeAndSport($type_id, $sport_id)]) . "</h1>";

      $priceGroups = $this->r->areas->getAreasPriceGroups($type_id, $sport_id, $period);
      if (!empty($priceGroups)) {
        $output = [];
        if (isset ($this->error)) {
          $output[] = $this->error;
        }
        $icon_c = useLayout()->svg('table_price_fill_column');
        $icon_l = useLayout()->svg('table_price_fill_row');
        $icon_d = useLayout()->svg('table_price_fill_all');
        $columnCell = '<td class="check light check-c" data-title="' .
          lang('insert_column', 'table_price') . '">' . $icon_c . '</td>';

        foreach ($priceGroups as &$priceGroup) {
          $priceGroup['time_rows'] = [];
          if (!$priceGroup['interval'] || empty($priceGroup['price'])) {
            continue;
          }

          foreach ($priceGroup['price'] as &$weekdayPrice) {
            $weekdayPrice['time'] = [];
            if (!isset($weekdayPrice[0], $weekdayPrice[1])) {
              continue;
            }
            $startMinutes         = TimeHelper::convertMySQLTimeToMinutes($weekdayPrice[0]);
            $finishMinutes        = TimeHelper::convertMySQLTimeToMinutes($weekdayPrice[1]);
            $duration             = $finishMinutes - $startMinutes;
            if ($duration <= 0 || $duration % $priceGroup['interval'] !== 0) {
              $priceGroup['error_code'] = 3;
              continue;
            }

            for ($minutes = $startMinutes; $minutes < $finishMinutes; $minutes += $priceGroup['interval']) {
              $time = TimeHelper::convertMinutes2MySQLTime($minutes);
              $weekdayPrice['time'][] = $time;
              if (!in_array($time, $priceGroup['time_rows'], true)) {
                $priceGroup['time_rows'][] = $time;
              }
            }
          }
          unset($weekdayPrice);
          sort($priceGroup['time_rows']);

          // Праздник может использовать воскресную цену в часы работы любого дня недели.
          // Поэтому воскресная колонка должна содержать все слоты текущей группы, даже если
          // обычное воскресное расписание начинается позже или заканчивается раньше.
          if (!isset($priceGroup['price'][6])) {
            $priceGroup['price'][6] = [null, null, []];
          }
          $priceGroup['price'][6]['time'] = $priceGroup['time_rows'];
        }
        unset($priceGroup);

        if ($seasons = $this->r->areas->getSeasons()) {
          $out .= '<style>
            .area-price-group-tabs { display: flex; justify-content: center; margin: 0; }
            .area-price-group-tabs .list-type { padding-left: 0; }
            .area-price-group-tabs .item-type a { font-size: 13px; white-space: normal; }
            .area-price-group-content { display: none; }
            .area-price-group-content.active { display: block; }
          </style>';
          $out .= '<script>
            if (typeof window.switchAreaPriceGroup === "undefined") {
              window.switchAreaPriceGroup = function (groupKey, containerId) {
                var container = document.getElementById(containerId);
                if (!container) {
                  return;
                }
                var season = container.closest(".area-price-season");
                season.querySelectorAll(".area-price-group-content").forEach(function (content) {
                  content.classList.toggle("active", content.getAttribute("data-price-group") === groupKey);
                });
                container.querySelectorAll(".item-type").forEach(function (item) {
                  var link = item.querySelector(".area-price-group-tab");
                  item.classList.toggle("active", link && link.getAttribute("data-price-group") === groupKey);
                });
              };
            }
          </script>';
          $out .= '<form method="post" action="areas.php?action=changeAreasPrice">' . "\n";
          $out .= '<input type="hidden" name="type_id" value="' . $type_id . '">' . "\n";
          $out .= '<input type="hidden" name="period" value="' . ($period ?? '') . '">' . "\n";
          $out .= '<input type="hidden" name="sport_id" value="' . $sport_id . '">' . "\n";
          foreach ($seasons as $s) {
            $seasonId       = (int)$s['period_id'];
            $tabsContainer  = 'area-price-tabs-' . $seasonId;
            $firstGroupKey  = array_key_first($priceGroups);
            $out .= '<div class="area-price-season">';
            $out .= "<h1 style=\"text-align:center\">" . htmlspecialchars($s['title'], ENT_QUOTES, 'UTF-8') . "</h1>";
            $out .= '<div style="text-align:center">' . lang('title_table_price', 'table_price') . '</div><br/>';
            if (count($priceGroups) > 1) {
              $out .= '<div class="area-price-group-tabs" id="' . $tabsContainer . '"><div class="list-type list-type-content">';
              foreach ($priceGroups as $groupKey => $priceGroup) {
                $groupTitle = htmlspecialchars(implode(', ', $priceGroup['titles']), ENT_QUOTES, 'UTF-8');
                $active     = $groupKey === $firstGroupKey ? ' active' : '';
                $out .= '<div class="item-type' . $active . '"><a href="#" class="area-price-group-tab" data-price-group="' . $groupKey . '" onclick="switchAreaPriceGroup(\'' . $groupKey . '\', \'' . $tabsContainer . '\'); return false;">' . $groupTitle . '</a></div>';
              }
              $out .= '</div></div>';
            }

            foreach ($priceGroups as $groupKey => $priceGroup) {
              $active = $groupKey === $firstGroupKey ? ' active' : '';
              $out .= '<div class="area-price-group-content' . $active . '" data-price-group="' . $groupKey . '">';
              $groupError = match ($priceGroup['error_code']) {
                2       => lang('Attention! So far no opening time has been set for this playground type!', 'message_error'),
                3       => lang('Working hours of this group are not evenly divisible by the booking period.', 'message_error'),
                default => null,
              };
              if ($groupError !== null) {
                $out .= '<p class="error">' . $groupError . '</p></div>';
                continue;
              }

              $out .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide area-price-group-table">' . "\n";
              $out .= "<tr><th rowspan=\"2\">" . lang('Time in hours') . "</th> <th class=\"check\"></th>
                <th colspan=\"7\">" . lang('Weekdays') . " / " . lang('Prices') . "  " . CURR_VALUTE . "</th></tr>
                <tr><th class=\"check\"></th>";
              for ($weekday = 0; $weekday < 7; $weekday++) {
                $out .= "<th>" . TranslateHelper::translateWeekday($weekday, true) . "</th>";
              }
              $out .= "</tr>\n";
              $out .= '<tr>
                      <td class="light check-c"></td>
                      <td class="check first-check diag-check light check-c" data-title="' . lang('insert_all_table', 'table_price') . '">' . $icon_d . '</td>' .
                str_repeat($columnCell, 7) . '</tr>';

              $rowNumber = 0;
              foreach ($priceGroup['time_rows'] as $timeRow) {
                $class = $rowNumber % 2 === 0 ? 'light' : 'dark';
                $out .= '<tr><td class="' . $class . '"><b>' . $timeRow . ' - ' .
                  TimeHelper::addMinutes2MySQLTime($timeRow, $priceGroup['interval']) . '</b></td>';
                $out .= '<td class="check check-l ' . $class . '" data-title="' . lang('insert_line', 'table_price') . '">' . $icon_l . '</td>';
                for ($weekday = 0; $weekday < 7; $weekday++) {
                  $weekdayPrice = $priceGroup['price'][$weekday] ?? null;
                  $hasTime      = $weekdayPrice && in_array($timeRow, $weekdayPrice['time'], true);
                  $value        = $hasTime && isset($weekdayPrice[2][$seasonId][$timeRow])
                    ? number_format($weekdayPrice[2][$seasonId][$timeRow], 2, ',', '')
                    : '0,00';
                  $out .= '<td class="' . $class . '">' . ($hasTime
                      ? '<input type="text" class="input smallest price" name="price[' . $groupKey . '][' . $weekday . '][' . $seasonId . '][' . $timeRow . ']" value="' . $value . '">'
                      : '&nbsp;') . '</td>' . "\n";
                }
                $out .= "</tr>\n";
                $rowNumber++;
              }
              $out .= "</table><br />\n</div>";
            }
            $out .= '</div>';
          }
          $out .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
          $out .= '<tr><th align="center" colspan="8"><input type="submit" class="button" value="' . lang(
              'button_update'
            ) . '">&nbsp;<input type="reset" class="button" value="' . lang('button_reset') . '"></th></tr>' . "\n";
          $out .= "</table><br />\n";
          $out .= "</form>\n";
        } else {
          $out = "<p class=\"error\">" . lang('Season not found', 'message_error') . "</p>\n";
        }

        $output[] = $out;

        //назад
        $output[] = "<span class=\"back\"><a href=\"areas.php\">" . lang('Back') . "</a></span>";

        return $output;
      } else {
        $out .= "<p class=\"error\">" . lang('Attention! So far no opening time has been set for this playground type!', 'message_error') . "</p>\n";
        return [$out . "<span class=\"back\"><a href=\"areas.php\">" . lang('Back') . "</a></span>"];
      }
    } else {
      $out = lang('Areas not found (invalid type_id)', 'message_error');

      return [$out . "<span class=\"back\"><a href=\"areas.php\">" . lang('Back') . "</a></span>"];
    }
  }

  //изменение
  function changeAreaPrice()
  {
    $prices  = Service::request()->_('price', null);
    $area_id = Service::request()->_('area_id', null);

    if ($prices && $area_id) {
      //проверяем и преобразуем массив price из post
      //проверить нужно только время на валидность - hh:mm, и чтобы никаких 24:59!
      foreach ($prices as $weekday => $seasons) {
        foreach ($seasons as $season => $times) {
          foreach ($times as $start => $price) {
            if (!TimeHelper::checkMySQLTime($start)) {
              unset ($prices[$weekday][$season][$start]);
            } else {
              $prices[$weekday][$season][$start] = (float)str_replace(
                ',',
                '.',
                $prices[$weekday][$season][$start]
              );
            }
          }
        }
      }
      $this->r->areas->setAreaPrice($area_id, $prices);
      $this->message = lang('message_element_base_update', 'message_success');
    }

    return $this->getAreasList();
  }

  function changeAreasPrice()
  {
    if (isset ($_POST['type_id']) && !empty ($_POST['price'])) {
      $type_id  = (int)Service::request()->_post('type_id');
      $sport_id = (int)Service::request()->_post('sport_id');
      $period   = Service::request()->_post('period');
      $period   = $period !== null && $period !== '' ? (int)$period : null;
      $groups   = $this->r->areas->getAreasPriceGroups($type_id, $sport_id, $period);
      $updated  = false;

      foreach ($groups as $groupKey => $group) {
        if (empty($_POST['price'][$groupKey])) {
          continue;
        }

        $prices = [];
        foreach ($_POST['price'][$groupKey] as $weekday => $seasonPrice) {
          if ((int)$weekday < 0 || (int)$weekday > 6 || !is_array($seasonPrice)) {
            continue;
          }
          foreach ($seasonPrice as $periodId => $weekdayPrice) {
            if (!is_array($weekdayPrice)) {
              continue;
            }
            foreach ($weekdayPrice as $start => $value) {
              if (TimeHelper::checkMySQLTime($start)) {
                $prices[(int)$weekday][(int)$periodId][$start] = NumberHelper::float($value);
              }
            }
          }
        }

        if (!empty($prices)) {
          $updated = $this->r->areas->setAreasPrice(
              $type_id,
              $prices,
              $sport_id,
              $group['period'],
              $group['area_ids']
            ) || $updated;
        }
      }

      if ($updated) {
        $this->message = lang('message_element_base_update', 'message_success');
      }
    }

    return $this->getAreasList();
  }


  /* КОММЕНТАРИИ К ТИПУ ПЛОЩАДОК */

  //форма редактирования
  function editTypeComments()
  {
    if (isset ($_GET['type_id']) && $this->r->areas->getTypeData((int)$_GET['type_id'], $type_data)) {
      $sport_id = Service::request()->_('sport_id');
      $out      = "<h1>" . lang('Remarks for the', 'areas', [
          'name_court' => $this->r->areas->getTitleByTypeAndSport(
            $type_data['type_id'],
            $sport_id
          ),
        ]) . "</h1>";
      $out      .= '<form method="post" action="areas.php?action=changeTypeComments">' . "\n";
      $out      .= '<input type="hidden" name="type_id" value="' . $type_data['type_id'] . '">' . "\n";
      $out      .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
      $out      .= '<tr><th>' . lang('Comments', 'areas') . '</th></tr>' . "\n";
      $out      .= '<tr><td class="dark"><textarea name="comments" class="input wide">' . htmlspecialchars(
          $type_data['comments']
        ) . '</textarea></td></tr>' . "\n";
      $out      .= '<tr><th><input type="submit" class="button" value="' . lang(
          'button_update'
        ) . '">&nbsp;<input type="reset" class="button" value="' . lang('button_reset') . '"></th></tr>' . "\n";
      $out      .= '</table>' . "\n";
      $out      .= '</form>' . "\n";

      return [$out, "<span class=\"back\"><a href=\"areas.php\">" . lang('Back') . "</a></span>"];
    } else {
      $this->error = lang('Type not found', 'message_error');

      return $this->getAreasList();
    }
  }

  //изменить примечание для типа площадок
  function changeTypeComments()
  {
    if (isset ($_POST['type_id']) && isset ($_POST['comments'])) {
      $this->r->areas->changeTypeComments((int)$_POST['type_id'], $_POST['comments']);
      $this->message = lang('message_element_base_update', 'message_success');
    }

    return $this->getAreasList();
  }

  /* СВЕТ */
  function changeLightData()
  {
    if (isset($_POST['area_id'])) {
      $area_id = (int)$_POST['area_id'];
      if (isset($_POST['light_price']) || isset($_POST['heating_price']) || isset($_POST['net_price'])) {
        $this->r->areas->changeLightPrice(
          $area_id,
          (isset($_POST['light_price'])) ? NumberHelper::float($_POST['light_price']) : '',
          (isset($_POST['heating_price'])) ? NumberHelper::float($_POST['heating_price']) : '',
          (isset($_POST['net_price'])) ? NumberHelper::float($_POST['net_price']) : ''
        );
      }
      foreach (WebIoModel::getWebIoTypes() as $alias => $webIoState) {
        $stateData = [];
        if (Service::request()->checkPost($alias)) {
          foreach (Service::request()->_post($alias) as $weekday => $state) {
            foreach ($state as $time => $st) {
              $stateData[] = [$weekday, TimeHelper::convertMinutes2MySQLTime($time)];
            }
          }
        }
        AreasLightsModel::changeDefaultState($area_id, $stateData, $webIoState['id']);
      }


      return $this->editLightDataForm();
    }
    $this->error = lang('not work', 'message_error');

    return $this->editLightDataForm();
  }

  function editLightDataForm()
  {
    if (isset ($_POST['area_id'])) {
      $area_id = (int)$_POST['area_id'];
    } elseif (isset ($_GET['area_id'])) {
      $area_id = (int)$_GET['area_id'];
    } else {
      return $this->getAreasList();
    }

    if ($this->r->areas->getAreaData($area_id, $area_data) == true) {
      $output = [];

      $out = "<h1>" . lang('Edit playground', 'areas') . "</h1>\n";

      if (isset ($this->error)) {
        $out .= '<span class="error"><b>' . $this->error . '</b></span>';
      }

      //получаем раписание площадки
      $this->r->areas->getAreaFullTimeTable($area_id, $timetable);
      //получаем расписание света включенного всегда

      $out      .= '<form method="post" action="areas.php?action=changeLightData">' . "\n";
      $out      .= '<input type="hidden" name="area_id" value="' . $area_id . '">' . "\n";
      $out      .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
      $out      .= '<tr><th colspan="8">' . $this->r->areas->getTitleByAreaId(
          $area_id
        ) . ' - ' . $area_data['title'] . '</th></tr>' . "\n";
      $on_title = [];
      if ($area_data['light_on']) {
        $light_data = AreasLightsModel::getDefaultStateData($area_id, 1);
        $out        .= '<tr><th>' . lang('Price of', 'areas', ['state' => lang('Light')]
          ) . ', ' . CURR_VALUTE . ' :</th><td class="dark" colspan="7"><input type="text" name="light_price" class="input wide" value="' . number_format(
            $area_data['light_price'],
            2,
            ',',
            ''
          ) . '"></td></tr>' . "\n";
        $on_title[] = 'L';
      }
      if ($area_data['heating_on']) {
        $heating_data = AreasLightsModel::getDefaultStateData($area_id, 2);
        $out          .= '<tr><th>' . lang('Price of', 'areas', ['state' => lang('Heating')]
          ) . ', ' . CURR_VALUTE . ' :</th><td class="dark" colspan="7"><input type="text" name="heating_price" class="input wide" value="' . number_format(
            $area_data['heating_price'],
            2,
            ',',
            ''
          ) . '"></td></tr>' . "\n";
        $on_title[]   = 'H';
      }
      if ($area_data['net_on']) {
        $net_data   = AreasLightsModel::getDefaultStateData($area_id, 3);
        $out        .= '<tr><th>' . lang('Price of', 'areas', ['state' => lang('Net')]
          ) . ', ' . CURR_VALUTE . ' :</th><td class="dark" colspan="7"><input type="text" name="net_price" class="input wide" value="' . number_format(
            $area_data['net_price'],
            2,
            ',',
            ''
          ) . '"></td></tr>' . "\n";
        $on_title[] = 'N';
      }
      $out .= '<tr><th>&nbsp;</th>' . "\n";

      $min_start_time  = 1000000;
      $max_finish_time = 0;

      foreach ($timetable as $weekday => $time) {
        $weekdays[] = $weekday;
        $out        .= '<th>' . TranslateHelper::translateWeekday($weekday, true) . '<br /> ' . implode(' &nbsp;| &nbsp;', $on_title) . '
		<br />'
          . ($area_data['light_on'] ? '<input type="checkbox" onclick="setLightCheckboxValues(this, \'light_check_' . $weekday . '\')" />' : '')
          . ($area_data['heating_on'] ? '<input type="checkbox" onclick="setLightCheckboxValues(this, \'heat_check_' . $weekday . '\')" />' : '')
          . ($area_data['net_on'] ? '<input type="checkbox" onclick="setLightCheckboxValues(this, \'net_check_' . $weekday . '\')" />' : '')
          . '</th>' . "\n";
        if ($min_start_time > TimeHelper::convertMySQLTimeToMinutes($time[0])) {
          $min_start_time = TimeHelper::convertMySQLTimeToMinutes($time[0]);
        }
        if ($max_finish_time < TimeHelper::convertMySQLTimeToMinutes($time[1])) {
          $max_finish_time = TimeHelper::convertMySQLTimeToMinutes($time[1]);
        }
      }

      $j = 0;
      for ($i = $min_start_time + $area_data['period']; $i <= $max_finish_time; $i = $i + $area_data['period']) {
        $out .= '<tr>' . "\n";

        $class = (($j % 2 == 0) ? 'light' : 'dark');

        $out .= '<th>' . TimeHelper::convertMinutes2MySQLTime($i - $area_data['period']) . '-' . TimeHelper::convertMinutes2MySQLTime(
            $i
          ) . '</th>' . "\n";
        foreach ($weekdays as $weekday) {
          $out .= '<td class="' . $class . '" align="center">'

            . ($area_data['light_on']
              ? '<input class="light_check_' . $weekday . '" type="checkbox" name="light[' . $weekday . '][' . ($i - $area_data['period']) . ']"' . ((isset ($light_data[$area_id][$weekday]) && in_array(
                  TimeHelper::convertMinutes2MySQLTime($i - $area_data['period']),
                  $light_data[$area_id][$weekday]
                )) ? ' checked' : '') . ' value="1">' : '')
            . ($area_data['heating_on']
              ? '<input class="heat_check_' . $weekday . '" type="checkbox" name="heating[' . $weekday . '][' . ($i - $area_data['period']) . ']"' . ((isset ($heating_data[$area_id][$weekday]) && in_array(
                  TimeHelper::convertMinutes2MySQLTime($i - $area_data['period']),
                  $heating_data[$area_id][$weekday]
                )) ? ' checked' : '') . ' value="1">' : '')
            . ($area_data['net_on']
              ? '<input class="net_check_' . $weekday . '" type="checkbox" name="net[' . $weekday . '][' . ($i - $area_data['period']) . ']"' . ((isset ($net_data[$area_id][$weekday]) && in_array(
                  TimeHelper::convertMinutes2MySQLTime($i - $area_data['period']),
                  $net_data[$area_id][$weekday]
                )) ? ' checked' : '') . ' value="1">' : '')
            . '</td>' . "\n";
        }

        $out .= '</tr>' . "\n";
        $j++;
      }

      $out .= '<tr><th align="center" colspan="8"><input type="submit" class="button" value="' . lang(
          'button_update'
        ) . '">&nbsp;<input type="reset" class="button" value="' . lang('button_reset') . '"></th></tr>' . "\n";

      $out .= '</table>' . "\n";
      $out .= '</form>' . "\n";

      $output[] = $out;
      $output[] = "<span class=\"back\"><a href=\"areas.php\">" . lang('Back') . "</a></span>";

      return $output;
    }

    return $this->getAreasList();
  }


  /* ДВЕРНЫЕ КОДЫ */
  function changeCodeData()
  {
    $type_id  = Service::request()->_('type_id', null);
    $sport_id = Service::request()->_('sport_id', null);
    if ($type_id !== null && $sport_id !== null) {
      $type_id  = (int)$type_id;
      $sport_id = (int)$sport_id;
    } else {
      return $this->editCodeDataForm();
    }
    $weekday = Service::request()->_('weekday', null);
    $code    = Service::request()->_('code', null);
    if ($weekday != null && $code != null) {
      $this->r->door_code->changeCodeData($type_id, $weekday, $code, $error_code, $sport_id);
    }

    return $this->editCodeDataForm();
  }


//форма редактирования
  function editCodeDataForm()
  {
    //Количество кодов для каждого периода
    $code_count = $this->r->config['count_door_code'];

    $type_id  = Service::request()->_('type_id', null);
    $sport_id = Service::request()->_('sport_id', null);
    if ($type_id !== null && $sport_id !== null) {
      $type_id  = (int)$type_id;
      $sport_id = (int)$sport_id;
    } else {
      return $this->getAreasList();
    }

    if ($this->r->areas->getAreasPrice($type_id, $interval, $range, $price, $ec, $sport_id)) {
      $weekday = Service::request()->_('weekday', 0);

      $code_data = $this->r->door_code->getCodeData($type_id, $weekday, $sport_id);

      $out = $this->getWeekdaysNavigation($type_id, $days_color, $sport_id);

      $out .= '<form method="post" action="areas.php?action=changeCodeData">' . "\n";
      $out .= '<input type="hidden" name="type_id" value="' . $type_id . '" />' . "\n";
      $out .= '<input type="hidden" name="sport_id" value="' . $sport_id . '" />' . "\n";
      $out .= '<input type="hidden" name="weekday" value="' . $weekday . '" />' . "\n";
      $out .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
      $out .= "<tr><th colspan=\"" . ($code_count + 1) . "\" style=\"background-color:#" . $days_color[$weekday] . "; padding:8px; font-weight:bold; font-size:14px;\">" . TranslateHelper::translateWeekday(
          $weekday
        ) . "</th></tr>\n";

      $out_title = '<th>' . lang('Time in hours') . '</th>';

      $unixtime = strtotime(date('Y-m-d'));
      $r        = explode(':', $range['start']);
      $cur      = mktime($r[0], $r[1], '00', date('m', $unixtime), date('d', $unixtime), date('Y', $unixtime));
      $r        = explode(':', $range['finish']);
      $finish   = mktime($r[0], $r[1], '00', date('m', $unixtime), date('d', $unixtime), date('Y', $unixtime));

      $j = 0;

      while ($cur < $finish) {
        $class = $j % 2 == 0 ? 'light' : 'dark';

        $cur_finish = mktime(date('H', $cur), (date('i', $cur) + $interval));
        $out_input  = '<td class="' . $class . '"><strong>' . date('H:i', $cur) . ' - ' . date(
            'H:i',
            $cur_finish
          ) . '</strong></td>';

        for ($c = 0; $c < $code_count; $c++) {
          if ($j == 0) {
            $out_title .= "<th>" . ($c + 1) . "</th>\n";
          }

          $out_input .= '<td class="' . $class . '">' . "\n";
          $out_input .= '<input type="text" class="input smallest price" name="code[' . date(
              'H:i',
              $cur
            ) . '][]" value="' . (isset(
              $code_data['code'][date(
                'H:i',
                $cur
              )][$c]
            ) ? $code_data['code'][date('H:i', $cur)][$c] : '') . '" maxlength="' . config('doorCode')->numberCharactersInDoorCodes() . '"
             style="width:' . (min(config('doorCode')->numberCharactersInDoorCodes() * 10, 70)) . 'px"/>';
          $out_input .= '</td>';
        }

        //Вывод заголовка таблицы
        if ($j == 0) {
          $out .= "<tr>\n";
          $out .= $out_title . "\n";
          $out .= "</tr>\n";
        }

        $out .= "<tr>\n";
        $out .= $out_input . "\n";
        $out .= "</tr>\n";
        $cur = $cur_finish;
        $j++;
      }

      $out .= '<tr><th align="center" colspan="' . ($code_count + 1) . '" ><input type="submit" class="button" value="' . lang(
          'button_update'
        ) . '">&nbsp;<input type="reset" class="button" value="' . lang('button_reset') . '"></th></tr>' . "\n";

      $out .= '</table>' . "\n";
      $out .= '</form>' . "\n";
    }

    return [$out, "<span class=\"back\"><a href=\"areas.php\">" . lang('Back') . "</a></span>"];
  }

  //импорт кодов
  function importCodeForm()
  {
    $type_id  = Service::request()->_('type_id', null);
    $sport_id = Service::request()->_('sport_id', null);
    if ($type_id !== null && $sport_id !== null) {
      $type_id  = (int)$type_id;
      $sport_id = (int)$sport_id;
    } else {
      return $this->getAreasList();
    }

    $out = "<h1>" . lang('Import/playback of the access codes', 'areas') . "</h1>";

    $out .= '<form method="post" action="areas.php?action=importCode" enctype="multipart/form-data">' . "\n";
    $out .= '<input type="hidden" name="type_id" class="input wide" value="' . $type_id . '">' . "\n";
    $out .= '<input type="hidden" name="sport_id" class="input wide" value="' . $sport_id . '">' . "\n";
    $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
    $out .= '<tr>';
    $out .= '<td class="light">' . lang('File (.csv)', 'areas') . '</td>';
    $out .= '<td class="light"><input type="file" name="file_csv" class="input wide" /></td>';
    $out .= '</tr>' . "\n";

    $out .= '<tr><th align="center" colspan="2"><input type="submit" class="button" value="' . lang(
        'button_update'
      ) . '">&nbsp;<input type="reset" class="button" value="' . lang('button_reset') . '"></th></tr>' . "\n";
    $out .= "</table>\n";

    $out .= '</center>' . "\n";
    $out .= '</form>' . "\n";

    return [
      $out,
      "<span class=\"back\"><a href=\"areas.php?action=viewCodeData&type_id=" . $type_id . "&sport_id=" . $sport_id . "\">" . lang(
        'Back'
      ) . "</a></span>",
    ];
  }

  function importDoorCodeRequest()
  {
    $type_id  = Service::request()->_('type_id', null);
    $sport_id = Service::request()->_('sport_id', null);
    if ($type_id !== null && $sport_id !== null) {
      $type_id  = (int)$type_id;
      $sport_id = (int)$sport_id;
    } else {
      return $this->getAreasList();
    }

    $out = "<h1>" . lang('Import/playback of the access codes', 'areas') . "</h1>";

    $code = [];

    if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
      if ($codes = file($_FILES['file_csv']['tmp_name'])) {
        foreach ($codes as $c) {
          $cd = explode(';', $c);
          //готовим массив под нашу систему
          $tmp = [];
          for ($i = 0; $i <= $this->r->config['count_door_code']; $i++) {
            $tmp[$i] = (isset($cd[$i]) ? $cd[$i] : '111');
          }

          //готовим массив для апдейта
          if (!preg_match('/[0-9]:[0-9]{2}/i', $tmp[0]))//день недели
          {
            $weekday = $tmp[0];
          } else {
            foreach ($tmp as $i => $t) {
              if ($i == 0) {
                $key = $t;
              } else {
                $code[$weekday][$key][] = $t;
              }
            }
          }
        }
      }

      if (count($code) > 0) {
        foreach ($code as $weekday => $c) {
          $this->r->door_code->changeCodeData($type_id, TranslateHelper::translateBackWeekday($weekday), $c, $error_code, $sport_id);
        }
      }
      $out .= '<p align="center">' . lang('Successful', 'message_success') . '</p>';

      return [
        $out,
        "<span class=\"back\"><a href=\"areas.php?action=viewCodeData&type_id=" . $type_id . "&sport_id=" . $sport_id . "\">" . lang(
          'Back'
        ) . "</a></span>",
      ];
    }
    $out .= '<p align="center" style="color:red;">' . lang('Successful', 'message_error') . '</p>';

    return [
      $out,
      "<span class=\"back\"><a href=\"areas.php?action=viewCodeData&type_id=" . $type_id . "&sport_id=" . $sport_id . "\">" . lang(
        'Back'
      ) . "</a></span>",
    ];
  }

  //экспорт дверных кодов
  function exportDoorCodeRequest()
  {
    //Количество кодов для каждого периода
    $code_count = $this->r->config['count_door_code'];

    $type_id  = Service::request()->_('type_id', null);
    $sport_id = Service::request()->_('sport_id', null);
    if ($type_id !== null && $sport_id !== null) {
      $type_id  = (int)$type_id;
      $sport_id = (int)$sport_id;
    } else {
      return $this->getAreasList();
    }

    $this->r->areas->getAreasPrice($type_id, $interval, $range, $price, $ec, $sport_id);

    $out_csv = '';

    if ($code_data = $this->r->door_code->getAllCodeData($type_id, $sport_id)) {
      foreach ($code_data as $area_id => $weekdays) {
        foreach ($weekdays as $weekday => $codes) {
          $out_csv .= TranslateHelper::translateWeekday($weekday) . ";";

          foreach ($codes as $time => $code) {
            if (!isset($view_count_line)) {
              for ($n = 1; $n <= $code_count; $n++) {
                $out_csv .= $n . ';';
              }
              $out_csv         .= "\n";
              $view_count_line = true;
            }
            $out_csv .= date('H:i', strtotime($time)) . ';';

            for ($c = 0; $c < $code_count; $c++) {
              $out_csv .= (isset($code[$c]) ? $code[$c] : '') . ';';
            }

            $out_csv .= "\n";
          }
          unset($view_count_line);
        }
      }
    }
    header("Content-Disposition: attachment; filename=zutrittscodes_export.csv");
    header("Content-Type: application/x-force-download; name=\"zutrittscodes_export.csv\"");
    echo $out_csv;
    die;
  }

  //форма редактирования
  function viewCodeData()
  {
    //Количество кодов для каждого периода
    $code_count = $this->r->config['count_door_code'];
    $type_id    = Service::request()->_('type_id', null);
    $sport_id   = Service::request()->_('sport_id', null);
    if ($type_id !== null && $sport_id !== null) {
      $type_id  = (int)$type_id;
      $sport_id = (int)$sport_id;
    } else {
      return $this->getAreasList();
    }

    $this->r->areas->getAreasPrice($type_id, $interval, $range, $price, $ec, $sport_id);
//    list($unix_time_start, $unix_time_finish) = array(strtotime($range[0]), strtotime($range[1]));
    $times = TimeHelper::generateArrayTimeInIncrements($range['start'], $range['finish'], $interval, false);

    $out = $this->getWeekdaysNavigation($type_id, $days_color, $sport_id);

    $out .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
    $out .= '<tr><td><a href="areas.php?action=importCodeForm&type_id=' . $type_id . '&sport_id=' . $sport_id . '">' . lang(
        'Import/playback of the access codes',
        'areas'
      ) . '</a> | <a href="areas.php?action=exportCode&type_id=' . $type_id . '&sport_id=' . $sport_id . '">' . lang(
        'Export / backup of the access codes',
        'areas'
      ) . '</a></td></tr>' . "\n";
    $out .= '</table>' . "\n";

    if ($code_data = $this->r->door_code->getAllCodeData($type_id, $sport_id)) {
      foreach ($code_data as $type_sport => $weekdays) {
        foreach ($weekdays as $weekday => $codes) {
          $out       .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
          $out       .= "<tr><th colspan=\"" . ($code_count + 1) . "\" style=\"background-color:#" . $days_color[$weekday] . "; padding:8px; font-weight:bold; font-size:14px;\">" . TranslateHelper::translateWeekday(
              $weekday
            ) . "</th></tr>\n";
          $out_title = '<th>' . lang('Time in hours') . '</th>';
          $j         = 0;
          foreach ($codes as $time => $code) {
            if (in_array($time, $times)) {
              $class = $j % 2 == 0 ? 'light' : 'dark';

              $out_input = '<td class="' . $class . '"><strong>' . date('H:i', strtotime($time)) . ' - ' . date(
                  'H:i',
                  mktime(date('H', strtotime($time)), (date('i', strtotime($time)) + $interval))
                ) . '</strong></td>';

              for ($c = 0; $c < $code_count; $c++) {
                if ($j == 0) {
                  $out_title .= "<th>" . ($c + 1) . "</th>\n";
                }

                $out_input .= '<td class="' . $class . '">' . "\n";
                $out_input .= (isset($code[$c]) ? $code[$c] : '&nbsp;');
                $out_input .= '</td>';
              }
              //Вывод заголовка таблицы
              if ($j == 0) {
                $out .= "<tr>\n";
                $out .= $out_title . "\n";
                $out .= "</tr>\n";
              }

              $out .= "<tr>\n";
              $out .= $out_input . "\n";
              $out .= "</tr>\n";
              $j++;
            }
          }
          $out .= '</table><br /><br />' . "\n";
        }
      }
    } else {
      $out .= '<p>' . lang('Please enter the codes for each day of the week!', 'message_success') . '</p>';
    }

    return [$out, "<span class=\"back\"><a href=\"areas.php\">" . lang('Back') . "</a></span>"];
  }

  //сезоны
  function changeSeasons()
  {
    if (isset($_POST['periods']) && is_array($_POST['periods'])) {
      $inputs = [];
      foreach ($_POST['periods'] as $m => $period) {
        $inputs[$period] .= $m . ',';
      }
      $this->r->areas->clearMonthsSeazons();
      foreach ($inputs as $period_id => $m) {
        $this->r->areas->changeSeazons($period_id, substr($m, 0, -1));
      }
    }

    return $this->getAreasList();
  }

  //сезоны
  function changeSeasonsData()
  {
    if (isset($_POST['period_id'])) {
      $start = '1970-' . (int)$_POST['month_start'] . '-' . (int)$_POST['day_start'];

      $this->r->areas->changeSeazonsData((int)$_POST['period_id'], $_POST['title'], $start, $_POST['colors']);
    }

    return $this->getAreasList();
  }

  //форма редактирования значения
  function editSeasons()
  {
    if (isset ($_POST['period_id'])) {
      $period_id = (int)$_POST['period_id'];
    } elseif (isset ($_GET['period_id'])) {
      $period_id = (int)$_GET['period_id'];
    } else {
      return $this->getAreasList();
    }

    if ($row = $this->r->areas->getSeasonById($period_id)) {
      $output = [];

      $out = "<h1>" . lang('Edit seasons', 'areas') . "</h1>\n";

      if (isset ($this->error)) {
        $out .= '<span class="error"><b>' . $this->error . '</b></span>';
      }

      $out .= '<form method="post" action="areas.php?action=changeSeasonsData">' . "\n";
      $out .= '<input type="hidden" name="period_id" value="' . $period_id . '">' . "\n";
      $out .= '<center>' . "\n";

      $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
      $out .= '<tr>';
      $out .= '<th colspan="4">' . lang('Edit seasons', 'areas') . '</th>';
      $out .= '</tr>' . "\n";

      $out .= '<tr>';
      $out .= '<td class="light">' . lang('Title') . '</td>';
      $out .= '<td class="light" ><input type="text" name="title" class="input wide" value="' . $row['title'] . '"></td>';
      $out .= '</tr>' . "\n";

      $out .= '<tr>';
      $out .= '<td class="dark">' . lang('Start') . '</td>';
      $out .= '<td class="dark">';

      $out .= ' <select name="day_start">';
      for ($i = 1; $i <= 31; $i++) {
        $out .= '<option value="' . $i . '" ' . ((isset($row['start']) && date(
              'j',
              strtotime($row['start'])
            ) == $i) ? 'selected' : '') . '>' . $i . '</option>';
      }
      $out .= '</select>';
      $out .= '<select name="month_start">';
      for ($i = 1; $i <= 12; $i++) {
        $out .= '<option value="' . $i . '" ' . ((isset($row['start']) && date(
              'n',
              strtotime($row['start'])
            ) == $i) ? 'selected' : '') . '>' . TranslateHelper::translateMonth($i) . '</option>';
      }
      $out .= '</select>
                            </td>';
      $out .= '</tr>' . "\n";

      $out .= '<tr>';
      $out .= '<td class="light">' . lang('Color') . '</td>';
      $out .= '<td class="light"><input type="text" name="colors" class="input wide" value="' . $row['colors'] . '"></td>';
      $out .= '</tr>' . "\n";


      $out .= '<tr><th align="center" colspan="2"><input type="submit" class="button" value="' . lang(
          'button_update'
        ) . '">&nbsp;<input type="reset" class="button" value="' . lang('button_reset') . '"></th></tr>' . "\n";
      $out .= "</table>\n";

      $out .= '</center>' . "\n";
      $out .= '</form>' . "\n";

      $output[] = $out;
      $output[] = "<span class=\"back\"><a href=\"areas.php\">" . lang('Back') . "</a></span>";

      return $output;
    } else {
      return $this->getAreasList();
    }
  }


  //Взять шапку с днями недели
  function getWeekdaysNavigation($type_id, &$days_color, $sport_id)
  {
    $days_color = ['aa0202', 'c98000', 'c1ba03', '0a870f', '158ea0', '000cfc', '851fba'];

    $out = "<h1>" . lang('Access codes', 'areas') . " - " . $this->r->areas->getTitleByTypeAndSport($type_id, $sport_id) . "</h1>";

    //Выводим дни недели
    $out .= '<p style="text-align:center">' . "\n";
    $out .= '<a href="areas.php?action=viewCodeData&type_id=' . $type_id . '&sport_id=' . $sport_id . '"><strong>Alle</strong></a>' . "\n";
    for ($d = 0; $d < 7; $d++) {
      $out .= ' | <a href="areas.php?action=editCodeData&type_id=' . $type_id . '&sport_id=' . $sport_id . '&weekday=' . $d . '" style="color:#' . $days_color[$d] . '">' . TranslateHelper::translateWeekday(
          $d
        ) . '</a>' . "\n";
    }
    $out .= '</p>' . "\n";

    return $out;
  }

  protected function getAreasPricesForPlayers($type_id, $sport_id)
  {
    //сообщения и ошибки
    $outputs = [];
    $out     = '';
    if (isset ($this->message)) {
      $out .= '<span class="message success">' . $this->message . '</span>';
    } elseif (isset ($this->error)) {
      $out .= '<span class="error"><b>' . $this->error . '</b></span>';
    }
    if ($out != '') {
      $outputs[] = $out;
    }

    $outputs[] = "<span class=\"back\"><a href=\"areas.php\">" . lang('Back') . "</a></span>";

    $prices       = $this->r->areas->getPricesForPlayers($type_id, $sport_id);
    $out          = "<h1>" . lang('Prices for the', 'areas', ['name_court' => $this->r->areas->getTitleByTypeAndSport($type_id, $sport_id)]
      ) . "</h1>";
    $out          .= '<form method="post" action="areas.php?action=changeAreasPricesForPlayers">' . "\n";
    $out          .= '<input type="hidden" name="type_id" value="' . $type_id . '">' . "\n";
    $out          .= '<input type="hidden" name="sport_id" value="' . $sport_id . '">' . "\n";
    $combinations = config('OpenType')->getCombinationsOfPlayers($type_id, $sport_id);
    $out          .= "<div style='display: flex;justify-content:space-around;flex-wrap: wrap;'>";
    for ($game = 1; $game < (config('DoubleGame')->doubleGameEnabled($type_id, $sport_id) ? 3 : 2); $game++) {
      $out          .= "<div>";
      $out          .= "<h1 style=\"text-align:center\">" . lang('price_for_' . $game . '_game', 'areas_prices') . "</h1>";
      $countPlayers = ($game == 2 ? config('DoubleGame')->numberPlayers($type_id, $sport_id) : 2);
      foreach ($combinations['players'] as $markId => $mark) {
        if (!$mark->use) {
          continue;
        }
        $out .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide areas-price">' . "\n";
        $out .= "<caption style=\"text-align:center\">" . lang('main_player', 'areas_prices') . "  " . $mark->title . "</caption>";
        $out .= "<tr>";
        $out .= "<th style='width: " . ($game == 1 ? 300 : 150) . "px'>" . lang('other_player', 'areas_prices') . ":</th>";
        for ($i = 1; $i < $countPlayers; $i++) {
          $out .= "<th> + " . $i . ' ' . lang('player', 'areas_prices') . ":</th>";
        }
        $out .= "</tr>";

        foreach ($combinations['players'] as $markId2 => $mark2) {
          $title = $mark2->title;
          if (!isset($combinations['use'][$markId][$markId2]) || $combinations['use'][$markId][$markId2] === 0) {
            continue;
          }
          if (config('OpenType')->isGuestAsNoMember($type_id, $sport_id)) {
            if ($mark2->id == ConfigClubStateModel::getMark('guest')->id) {
              continue;
            }
            if ($mark2->id == ConfigClubStateModel::getMark('no_club_rate')->id && $combinations['use'][$markId][ConfigClubStateModel::getMark(
                'guest'
              )->id]) {
              $title .= ' | ' . $combinations['players'][ConfigClubStateModel::getMark('guest')->id]->title;
            }
          }
          $out .= "<tr>";
          $out .= "<th class='align-right'>";
          $out .= $title;
          $out .= "</th>";
          for ($i = 1; $i < $countPlayers; $i++) {
            $value = number_format((isset($prices[$game][$markId][$markId2][$i]) ? $prices[$game][$markId][$markId2][$i]['price'] : '0'), 2, ',', '');
            $out   .= "<td>";
            $out   .= '<input type="text" class="input smallest price" name="price[' . $game . '][' . $markId . '][' . $markId2 . '][' . $i . ']" value="' . $value . '">';
            $out   .= "</td>";
          }
          $out .= "</tr>";
        }
        $out .= "</table>";
      }
      $out .= "</div>";
    }
    $out       .= "</div>";
    $out       .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
    $out       .= '<tr><th align="center" colspan="8"><input type="submit" class="button" value="' . lang(
        'button_update'
      ) . '">&nbsp;<input type="reset" class="button" value="' . lang('button_reset') . '"></th></tr>' . "\n";
    $out       .= "</table><br />\n";
    $out       .= "</form>\n";
    $outputs[] = $out;
    //назад
    $outputs[] = "<span class=\"back\"><a href=\"areas.php\">" . lang('Back') . "</a></span>";

    return $outputs;
  }

  protected function changeAreasPricesForPlayers()
  {
    $type_id  = Service::request()->_('type_id', null);
    $sport_id = Service::request()->_('sport_id', null);
    $area_id  = Service::request()->_('area_id', null);
    if ($area_id || ($type_id && $sport_id)) {
      $use    = Service::request()->_('use');
      $prices = [];
      foreach (Service::request()->_('price', []) as $game => $mainPlayers) {
        foreach ($mainPlayers as $mainPlayer => $otherPlayers) {
          foreach ($otherPlayers as $otherPlayer => $countsPlayer) {
            foreach ($countsPlayer as $count => $price) {
              $prices[] = [
                'type_id'      => $type_id,
                'sport_id'     => $sport_id,
                'area_id'      => $area_id ?? 0,
                'type_game'    => "$game",
                'main_player'  => $mainPlayer,
                'other_player' => $otherPlayer,
                'count_player' => $count,
                'use'          => (int)isset($use[$game][$mainPlayer][$otherPlayer]),
                'price'        => NumberHelper::float($price),
              ];
            }
          }
        }
      }
      $this->r->areas->savePricesForPlayers($prices);
      $this->message = lang('message_element_base_update', 'message_success');
    }

    return $this->getAreasPricesForPlayers($type_id, $sport_id);
  }

  public function getConfigFormForAreasPrices()
  {
    $type_id  = (int)Service::request()->_('type_id', 0);
    $sport_id = (int)Service::request()->_('sport_id', 0);

    $out = '';
    $out .= '<form method="post" action="areas.php?action=changeAreasPricesForPlayers">' . "\n";
    $out .= '<input type="hidden" name="type_id" value="' . $type_id . '">' . "\n";
    $out .= '<input type="hidden" name="sport_id" value="' . $sport_id . '">' . "\n";
    $out .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
    $out .= '<tr><th align="center" colspan="8"><input type="submit" class="button" value="' . lang(
        'button_update'
      ) . '">&nbsp;<input type="reset" class="button" value="' . lang('button_reset') . '"></th></tr>' . "\n";
    $out .= "</table><br />\n";
    $out .= "</form>\n";

    return $out;
  }

  protected function active()
  {
    $this->r->areas->active(Service::request()->_get('area_id'), Service::request()->_get('active'));
    header('Location: ' . Service::structure()->getPageHrefByKey('areas'));
    return $this->getAreasList();
  }
}

$a                = new areas_admin;
$_page['content'] = $a->start();
