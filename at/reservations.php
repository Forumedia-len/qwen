<?php

use AC\app\helpers\LayoutHelper;
use AC\core\engines\PricingReservationEngine;
use AC\core\modules\payment\helpers\PaymentHelper;
use AC\core\system\db\Query;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\ColorsHelper;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\helpers\TranslateHelper;


use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\view\View;
use AC\core\engines\Engines;
use AC\core\modules\areas\models\AreasLightsModel;
use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\webIo\models\WebIoModel;
use AC\core\ReservationsVisualizationCommon;

//

class reservations_admin
{
  /**
   * @var Engines
   */
  public $engine;
  public $page_key;
  public $page_key_r;
  public $areas_types;
  public $error;
  public $message;

  function start()
  {
    $this->engine = new Engines();
    $action       = Service::request()->_('action');
    switch ($action) {
      case 'showReservations':
        return $this->showReservations();
        break;
      case 'showBar':
        return $this->showBar();
        break;
      case 'insertBarItem':
        return $this->insertBarItem();
        break;
      case 'removeBarItem':
        return $this->removeBarItem();
        break;
      case 'changeBarItem':
        return $this->changeBarItem();
        break;
      case 'insertBarTitle':
        return $this->insertBarTitle();
        break;
      case 'editBarTitle':
        return $this->editBarTitle();
        break;
      case 'removeBarTitle':
        return $this->removeBarTitle();
        break;
      case 'ajaxGetNds':
        return $this->ajaxGetNds();
        break;
      //оформление и удаление заказа
      case 'requestForm':
        return $this->requestForm();
        break;
      case 'proceedRequestAdmin';
        return $this->proceedRequestAdmin();
        break;
      case 'editRequest':
        return $this->editRequest();
        break;
      case 'changeRequest':
        return $this->changeRequest();
        break;
      case 'removeOrder':
        return $this->setActionByTypeCourt('removeOrder');
        break;
      case 'joinForm':
        return $this->requestForm(true);
        break;
      case 'unJoin':
        return $this->setActionByTypeCourt('unJoin');
        break;
      case 'join':
        return $this->setActionByTypeCourt('join');
        break;
      case 'removeTicketProceed':
        return $this->setActionByTypeCourt('removeTicketProceed');
        break;
      case 'changeOrders';
        return $this->changeOrders();
        break;
      default:
        return $this->mainPage();
    }
  }

  //первая страница
  function mainPage()
  {
    $this->page_key = 'reservations_stats';


    $output = [];
    if (isset ($this->error)) {
      $output[] = '<p align="center" class="error">' . $this->error . "</p>\n";
    }
    $output[] = $this->AreasTypes();
    $output[] = $this->statisticsByLastHour();
    $output[] = $this->statisticsByLastMonth();

    return $output;
  }

  //ключ страницы
  function getPageKey()
  {
    return $this->page_key;
  }

  //доп ключ страницы
  function getPageKeyR()
  {
    return $this->page_key_r;
  }

  //список площадок
  function AreasTypes()
  {
    $out   = '';
    $sportsByType = ModCommHelper::get('areas', 'relevantSportsByType', [], 'sportsByType', []);
    if ($this->engine->areas->getAreasTypesData($this->areas_types)) {
      $out .= "<table cellspacing=\"10\" width=\"95%\" align=\"center\" class=\"areaType\"><tr>\n";
      $width = floor(100 / 4);
      $i = 0;
      foreach ($sportsByType as $sportByType) {
        if($i % 4 == 0) {
          $out .= "</tr><tr>\n";
        }
        $bgcolor = ColorsHelper::hexByTypeSport($sportByType->type_id, $sportByType->sport_id);
        $title   = $sportByType->title_site_url;
        $out     .= "<td width=\"" . $width . "%\" bgcolor=\"" . $bgcolor . "\" align=\"center\">
        <a href=\"reservations.php?action=showReservations&type_id=" . $sportByType->type . "&sport_id=" . $sportByType->sport . "&date=" . date(
            'Y-m-d'
          ) . "\" class=\"title\" style='width:100%;display:block;white-space:normal;'>" . $title . "</a></td>\n";
        $i++;
      }

      $out .= "</tr>\n</table>\n";
    } else {
      $out .= 'There are no areas...';
    }

    return $out;
  }

  //таблица резервирования по типу площадки и дате
  //показывает текущее состояние и календарь навигации
  function showReservations($type_id = null)
  {
    $sportsByType = ModCommHelper::get('areas', 'relevantSportsByType', [], 'sportsByType', []);
    if ($type_id == null) {
      $type_id = Service::request()->_('type_id');
    }
    $sport_id = Service::request()->_('sport_id', false);

    if ($type_id !== null && isset ($_GET['date'])) {
      $output = [];
      $date   = $_GET['date'];

      //используемые переменные
      $get_unixtime = strtotime($date);

      //проверка на корректность даты
      if ($date == @date("Y-m-d", $get_unixtime)) {
        //проверка на корректность $type_id
        if ($this->engine->areas->getTypeData($type_id, $type_data) == true) {
          //тип существуюn

          //ключ страницы
          $this->page_key_r = 'reservations_' . $type_id . ($sport_id ? '_' . $sport_id : '');
          $this->page_key   = 'reservations_areas';

          //ошибки и сообщения
          if (isset ($this->message)) {
            $output[] = "<span class=\"message\">" . $this->message . "</span>\n";
          }
          if (isset ($this->error)) {
            $output[] = "<span class=\"error\"><b>" . $this->error . "</span>\n";
          }

          //навигация по площадкам begin
          [$page, $limit, $areas_navigation] = $this->renderPages($type_id, $sport_id, $date);
          $bgcolor = ColorsHelper::hexByTypeSport($type_id, $sport_id);

          $out    = '';
          $engine = Service::engines();
          //выбранный день недели
          $out .= '<table cellspacing="0" cellpadding="0" border="0">' . "\n";
          $out .= " <tr>";
          $out .= '   <td valign="top" bgcolor="#D8D8D8">' . "\n";
          ReservationsVisualizationCommon::setView([
            'tpl_view'         => 'reservations',
            'default_template' => module('areas')?->useModel()?->getEngine()->getAliasType($type_id)
          ], true);
          //таблица календаря
          $out .= ReservationsVisualizationCommon::renderCalendar(
              $engine,
              $date,
              $type_id,
              $sport_id,
              $page,
              true
            ) . "\n";
          $out .= "   </td>\n";
          $out .= "   <td><img src=\"" . cdn_url(paths()->getAssetsDir('images/spacer.gif', 'admin')) . "\" width=\"20\" height=\"1\"></td>\n";
          $out .= "   <td width=\"100%\" valign=\"top\">\n";
          $out .= '     <table width="100%" bgcolor="#' . $bgcolor . '" cellspacing="5" border="0" class="areaType">' . "\n";
          $out .= '       <tr>' . "\n";
          $out .= '         <td class="title"' . (isset ($areas_navigation) ? ' rowspan="2"'
              : '') . '>' . $this->engine->areas->getTitleByTypeAndSport(
              $type_id,
              $sport_id
            ) . '</td>' . "\n";
          $out .= '         <td width="60%" align="left" class="date">' . date(
              'd',
              $get_unixtime
            ) . '. ' . TranslateHelper::translateMonth(date('n', $get_unixtime)) . ' ' . date(
              'Y',
              $get_unixtime
            ) . ', ' . TranslateHelper::translateWeekday(CalendarHelper::getWeekdayByUnixtime($get_unixtime)) . '</td>' . "\n";
          $out .= '				</tr>' . "\n";
          if (isset ($areas_navigation)) {
            //навигация по площадкам
            $out .= '       <tr>' . "\n";
            $out .= '         <td class="navigation">' . $areas_navigation . "</td>\n";
            $out .= '       </tr>' . "\n";
          }
          $out .= '			</table>' . "\n";
          $out .= Service::view()->renderFile(SHARED_PATH . 'at/reservations.legend.php');
          $out .= "		</td>\n";
          $out .= "	</tr>\n";
          $out .= "</table>\n";
          $out .= "<br>\n";
//          //столбцы площадок
          if ($_out = ReservationsVisualizationCommon::renderAreasTypeColumns(
            $engine,
            $date,
            $type_id,
            $sport_id,
            $page,
            $limit,
            0,
            true
          )) {
            $out .= $_out;
          } else {
            $out .= $engine->holidays->checkHoliday($date)
              ? lang('The chosen day is a holiday', 'message_error')
              : lang('Areas not found', 'message_error');
          }

          $output[] = $out;
        } else {
          $this->page_key = 'reservations';
          $output[]       = lang('Areas not found', 'message_error');
        }
      } else {
        $this->page_key = 'reservations';
        $output[]       = lang('Incorrect date', 'message_error');
      }

      return $output;
    } else {
      $this->error = lang('input data error', 'message_error');

      return $this->mainPage();
    }
  }

  public function getNdsValue($row = [])
  {
    ob_start();
    ?>
    <select name="nds" class="input wide">
      <?php
      foreach ([0, 7, 19] as $nds) { ?>
        <option value="<?= $nds ?>" <?= ((isset($row['nds']) && $row['nds'] == $nds) ? 'selected' : '') ?> >
          <?= $nds ?>%
        </option>
        <?php
      } ?>
    </select>
    <?php
    $out = ob_get_contents();
    ob_end_clean();

    return $out;
  }


  function showBar()
  {
    $output = [];
    $date   = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    //используемые переменные
    $get_unixtime = strtotime($date);
    $this->engine->areas->getTypeData(1, $type_data);
    //проверка на корректность даты
    if ($date == @date("Y-m-d", $get_unixtime)) {
      //проверка на корректность $type_id
      //тип существуюn

      //ключ страницы
      $this->page_key = 'reservations_bar';

      //ошибки и сообщения
      if (isset ($this->message)) {
        $output[] = "<span class=\"message\">" . $this->message . "</span>\n";
      }
      if (isset ($this->error)) {
        $output[] = "<span class=\"error\"><b>" . $this->error . "</span>\n";
      }

      $out = '';

      //выбранный день недели
      $out .= '<table cellspacing="0" cellpadding="0" border="0">' . "\n";
      $out .= " <tr>";
      $out .= '   <td valign="top" bgcolor="#D8D8D8">' . "\n";
      //таблица календаря
      [$page, $limit, $areas_navigation] = $this->renderPages(1, 1, $date);
      $bgcolor = ColorsHelper::hexByTypeSport(1, 1);
      $out     .= ReservationsVisualizationCommon::renderCalendar(
          $this->engine,
          $date,
          1,
          1,
          $page,
          true,
          true
        ) . "\n";
      $out     .= "   </td>\n";
      $out     .= "   <td><img src=\"" . base_url(paths()->getAssetsDir('images/spacer.gif', 'admin')) . "\" width=\"20\" height=\"1\"></td>\n";
      $out     .= "   <td width=\"100%\" valign=\"top\" align='center'>\n";
      $out     .= '     <table width="100%" bgcolor="#' . $bgcolor . '" cellspacing="5" border="0" class="areaType">' . "\n";
      $out     .= '       <tr>' . "\n";
      $out     .= '         <td class="title"' . (isset ($areas_navigation) ? ' rowspan="2"' : '') . '></td>' . "\n";
      $out     .= '         <td width="100%" align="center" class="date">' . date(
          'd',
          $get_unixtime
        ) . '. ' . TranslateHelper::translateMonth(date('n', $get_unixtime)) . ' ' . date('Y',
          $get_unixtime) . ', ' . TranslateHelper::translateWeekday(
          CalendarHelper::getWeekdayByUnixtime($get_unixtime)
        ) . '</td>' . "\n";
      $out     .= '       </tr>' . "\n";
      $out     .= '     </table>' . "\n";
      $out     .= '     <br /><br />' . "\n";
      $out     .= '     <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
      $out     .= '       <tr>' . "\n";
      $out     .= '         <th>' . lang('Title') . '</th>' . "\n";
      $out     .= '         <th>' . lang('VAT rates') . '</th>' . "\n";
      $out     .= '         <th colspan="2">' . lang('Action') . '</th>' . "\n";
      $out     .= '       </tr>' . "\n";
      foreach ($this->engine->bar->getBarTitles() as $barTitle) {
        $out .= '       <tr>' . "\n";
        $out .= '          <td class="dark">' . $barTitle['title'] . '</td>' . "\n";
        $out .= '          <td class="dark" align="center">' . $barTitle['nds'] . '%</td>' . "\n";
        $out .= '<td class="light"><a href="reservations.php?action=editBarTitle&id=' . $barTitle['id'] . '" class="btnEdit">' . lang('button_update') . '</a></td>';
        $out .= '          <td class="light"><a href="reservations.php?action=removeBarTitle&id=' . $barTitle['id'] . '" onclick="return ifConfirm (\'Wollen Sie diesen Eintrag wirklich unwiderruflich löschen?\')" class="btnRemove">' . lang('button_remove') . '/a></td>';
        $out .= '       </tr>' . "\n";
      }
      $out .= '     </table>' . "\n";
      $out .= '     <br /><br />' . "\n";
      $out .= $this->getFormBarTitle();
      $out .= "   </td>\n";
      $out .= " </tr>\n";
      $out .= "</table>\n";
      $out .= "<br>\n";

      $output[] = $out;

      $out = '<br /><br />' . "\n";
      $out .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide" style="width:80%">' . "\n";
      $out .= '<tr><th>' . lang('Date') . '</th><th width="50%">' . lang('Title') . '</th><th>' . lang('Amount') . ', ' . CURR_VALUTE . '</th><th>&nbsp;</th><th>' . lang('VAT rates') . '</th><th>' . lang('Comment') . '</th><th colspan="2">' . lang('Action') . '</th></tr>' . "\n";
      if ($items = $this->engine->bar->getBarItems(date("Y-m-d", $get_unixtime))) {
        foreach ($items as $item) {
          $out .= '<tr>' .
            '<td class="dark" align="center">' . date('d.m.Y H:i', strtotime($item['in_date'])) . '</td>' .
            '<td class="light">' . $item['title'] . '</td>' .
            '<td class="dark" align="center">' . number_format($item['sum'], 2, ',', '') . ' ' . CURR_VALUTE . '</td>' .
            '<td class="dark" align="center">' . ($item['encash'] == 'bar'
              ? lang('Cash payment')
              : ($item['encash'] == 'card' ? lang('Card payment')
                : '')) . '</td>' .
            '<td class="light" align="center">' . $item['nds'] . ' %</td>' .
            '<td class="light" align="center">' . $item['comment'] . '</td>';
          $out .= '<td class="light"><a href="reservations.php?action=showBar&bar_id=' . $item['bar_id'] . '&date=' . $date . '" class="btnEdit">' . lang('button_update') . '</a></td>';
          $out .= '<td class="light"><a href="reservations.php?action=removeBarItem&bar_id=' . $item['bar_id'] . '&date=' . $date . '" onclick="return ifConfirm ()" class="btnRemove">' . lang('button_remove') . '</a></td>';
          $out .= '</tr>' . "\n";
        }
      }
      $out .= "</table>\n";

      if (isset($_GET['bar_id']) && $this->engine->bar->getBarItemById($_GET['bar_id'], $bar_item)) {
        $output[] = $this->getBarInputFields(1, $bar_item);
        $output[] = '<span class="back"><a href="reservations.php?action=showBar&date=' . $_GET['date'] . '">' . lang('Back') . '</a></span>';
      } else {
        $output[] = $out;
        $output[] = $this->getBarInputFields(0, ['in_date' => date("Y-m-d", $get_unixtime)]);
      }
    } else {
      $this->page_key = 'reservations';
      $output[]       = lang('Incorrect date');
    }

    return $output;
  }

  function removeBarItem()
  {
    $this->engine->bar->removeBarItemById($_GET['bar_id']);

    return $this->showBar();
  }

  function changeBarItem()
  {
    $this->engine->bar->changeBarItemById(
      $_POST['bar_id'],
      $_POST['title'],
      NumberHelper::float($_POST['sum']),
      $_POST['encash'],
      $_POST['nds'],
      $_POST['comment']
    );

    return $this->showBar();
  }

  function insertBarItem()
  {
    $this->engine->bar->insertBarItem(
      $_GET['date'] . ' ' . $_POST['cur_time'],
      $_POST['title'],
      NumberHelper::float($_POST['sum']),
      $_POST['encash'],
      $_POST['nds'],
      $_POST['comment']
    );

    return $this->showBar();
  }

  function getBarInputFields($mode, $row = [])
  {
    $out = '<form action="reservations.php?action=' . ($mode == 0 ? 'insertBarItem' : 'changeBarItem') . '&date=' . date(
        'Y-m-d',
        strtotime($row['in_date'])
      ) . '" method="post" id="formBar">' . "\n";
    if ($mode == 1) {
      $out .= "<input type=\"hidden\" name=\"bar_id\" value=\"" . $row['bar_id'] . "\">\n";
    }

    $out .= "<input type=\"hidden\" name=\"cur_time\" value=\"" . date("H:i") . "\">\n";
    ob_start();
    ?>
    <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
      <tr>
        <th colspan="2"><?= ($mode == 0 ? lang('title_create') : lang('title_update')) ?></th>
      </tr>
      <tr>
        <td class="dark"><?= lang('Title') ?>:</td>
        <td class="dark">
          <select name="title"
                  class="input wide" <?//= ($mode == 0 ? ' onchange="if(this.selectedIndex==1){document.getElementById(\'nds-fields\').selectedIndex=1;}else{document.getElementById(\'nds-fields\').selectedIndex=0;}" ' : '')
          ?>>
            <?php
            $barTitles = $this->engine->bar->getBarTitles(true);
            foreach ($barTitles as $bar_title) { ?>
              <option value="<?= $bar_title['title'] ?>" <?= ((isset($row['title']) && $row['title'] == $bar_title['title']) ? 'selected' : '') ?>
                      data-bar-id="<?= $bar_title['id'] ?>">
                <?= $bar_title['title'] ?>
              </option>
              <?php
            } ?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Amount') ?>:</td>
        <td class="dark">
          <input type="text" name="sum" class="input wide" value="<?= str_replace('.', ',', $row['sum']) ?>"/>
        </td>
      </tr>
      <tr>
        <td class="dark">&nbsp;</td>
        <td class="dark">
          <input type="radio" value="card"
                 name="encash" <?= (isset($row['encash']) ? ($row['encash'] == 'card' ? 'checked' : '') : 'checked') ?> /><?= lang('Card payment') ?>
          <input type="radio" value="bar"
                 name="encash" <?= ((isset($row['encash']) && $row['encash'] == 'bar') ? 'checked' : '') ?> /><?= lang('Cash payment') ?>
        </td>
      </tr>
      <tr>
        <td class="dark"><?= lang('VAT rates') ?> :</td>
        <td class="dark">
          <?= $this->getNdsValue($mode == 0 ? $barTitles[0] : $row) ?>
          <!--          <select name="nds" class="input wide">-->
          <!--            <option value="3" --><?//= ((isset($row['nds']) && $row['nds'] == '3') ? 'selected' : '')
          ?><!-- >3%</option>-->
          <!--            <option value="17" --><?//= ((isset($row['nds']) && $row['nds'] == '17') ? 'selected' : '')
          ?><!-- >17%</option>-->
          <!--            <option value="0" --><?//= ((isset($row['nds']) && $row['nds'] == '0') ? 'selected' : '')
          ?><!-- >0%</option>-->
          <!--          </select>-->
        </td>
      </tr>
      <tr>
        <td class="light"><?= lang('Comment') ?>:</td>
        <td class="light">
          <textarea name="comment" class="input wide"><?= str_replace(
              '<',
              '&lt;',
              str_replace('>', '&gt;', $row['comment'])
            ) ?></textarea>
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
    <?php if ($mode == 0) { ?>
    <script>
      $(document).ready(function () {
        $('#formBar select[name="title"]').change(function () {
          var dataObject = []
          dataObject.push({name: 'title', value: this.value})
          dataObject.push({name: 'id', value: $(this.selectedOptions).attr('data-bar-id')})
          dataObject.push({name: 'action', value: 'ajaxGetNds'})
          $.post('reservations.php', dataObject).done(function (res) {
            if (res) {
              $('#formBar select[name="nds"] option[value=' + data.nds + ']').prop('selected', true)
            }
          })

        })
      })
    </script>
  <?php } ?>
    <?
    $out .= ob_get_contents();
    ob_end_clean();

    return $out;
  }

  public function insertBarTitle()
  {
    if (isset($_POST['title']) && trim($_POST['title']) !== '' && isset($_POST['nds'])) {
      if ($this->engine->bar->insertBarTitle($_POST['title'], $_POST['nds'])) {
        $this->message = lang('message_element_base_create', 'message_success');
      } else {
        $this->error = lang('message_element_base_create', 'message_error');
      }
    } else {
      $this->error = lang('message_element_base_create', 'message_error');
    }

    return $this->showBar();
  }

  function removeBarTitle()
  {
    if (isset($_GET['id'])) {
      $this->engine->bar->removeBarTitleById($_GET['id']);
    } else {
      $this->error = lang('message_element_base_create', 'message_error');
    }

    return $this->showBar();
  }

  public function getFormBarTitle($row = [])
  {
    $insert = empty($row);
    ob_start();
    ?>
    <form action="reservations.php?action=<?= ($insert ? 'insert' : 'edit') ?>BarTitle" method="post">
      <?php if (!$insert) { ?>
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
      <?php } ?>
      <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
        <tr>
          <th colspan="2"><?= ($insert ? lang('title_create') : lang('title_update')) ?></th>
        </tr>
        <tr>
          <td class="dark"><?= lang('Title') ?> :</td>
          <td class="dark">
            <input type="text" name="title" class="input wide"
                   value="<?= (isset($row['title']) ? $row['title'] : '') ?>"/>
          </td>
        </tr>
        <tr>
          <td class="dark"><?= lang('VAT rates') ?> :</td>
          <td class="dark">
            <?= $this->getNdsValue($row) ?>
          </td>
        </tr>
        <tr>
          <th align="center" colspan="2">
            <input type="submit" value="<?= ($insert ? lang('button_create') : lang('button_update')) ?>" class="button">
            <input type="reset" value="<?= lang('button_reset') ?>" class="button">
        </tr>
      </table>
    </form>
    <?php
    $out = ob_get_contents();
    ob_end_clean();

    return $out;
  }

  public function editBarTitle()
  {
    if (isset($_POST['title']) && isset($_POST['nds'])) {
      $this->engine->bar->changeBarTitleById($_POST['id'], $_POST['title'], $_POST['nds']);
      $this->message = lang('message_element_base_update', 'message_success');
    } else {
      $this->page_key = 'reservations_bar';
      if (isset($_GET['id'])) {
        $this->engine->bar->getBarTitleById($_GET['id'], $row);
        $output[] = $this->getFormBarTitle($row);
        //ссылка назад только если дата верна и площадка найдена
        $output[] = '<p align="center"><a href="reservations.php?action=showBar">' . lang('Back') . '</a></p>' . "\n";
      }

      return $output;
    }

    return $this->showBar();
  }

  public function ajaxGetNds()
  {
    if (isset($_POST['id']) && isset($_POST['title'])) {
      $this->engine->bar->getBarTitleById($_POST['id'], $barTitle);
      echo json_encode($barTitle);
    }

    return '';
  }

  //форма заказа
  function requestForm($join = false)
  {
    $area_id  = (int)Service::request()->_('area_id');
    $type_id  = (int)Service::request()->_('type_id');
    $sport_id = (int)Service::request()->_('sport_id');
    $date     = Service::request()->_('date');
    $time     = Service::request()->_('time');
    $page     = (int)Service::request()->_('page');

    if ($area_id !== null && $date != null && $time != null && $page != null) {
      $client_id          = Service::request()->_('client_id');
      $client_id_selected = $client_id !== null ? $client_id : (isset ($_COOKIE['client_id_last']) ? $_COOKIE['client_id_last'] : null);
      if ($client_id_selected <= 0) {
        $client_id_selected = null;
      }
      //проверка даты и времени
      $get_unix_time = strtotime($date . ' ' . $time);
      if (@date('Y-m-d H:i', $get_unix_time) == $date . ' ' . $time) {
        //дата верна
        $weekday = CalendarHelper::getWeekdayByUnixtime($get_unix_time);
        $this->engine->setMaxReservationUnixTime($type_id, $sport_id);
        //проверка на наличие И доступность площадки
        if ($this->engine->checkAreaDateTimeAvailable(
            $area_id,
            $client_id_selected,
            $date,
            $time,
            $error_code
          ) || $join) {
          $isOpenType = config('reservations')->isOpenType($type_id);
          //информация о типе площадки
          $this->engine->areas->getTypeData($area_id, $area_type, true);
          $type_id = (int) $area_type['type_id'];
          //информация о площадке
          $this->engine->areas->getAreaData($area_id, $area_data);
          $sport_id = (int) $area_data['sport_id'];
          //ключ страницы
          $this->page_key_r = 'reservations_' . $type_id . '_' . $sport_id;
          $this->page_key   = 'reservations_areas';
          $time_start       = $time;
          for ($i = 0; $i < ($isOpenType ? config('DoubleGame')->getNumberOfPeriods($type_id, $sport_id, $area_id) : 1); $i++) {
            $unix_time_I = strtotime($date . ' ' . $time_start);
            $time_finish = date('H:i', strtotime('+ ' . $area_data['period'] . ' minutes', $unix_time_I));
            $times[]     = ['start' => $time_start, 'finish' => $time_finish];
            $time_start  = $time_finish;
          }

          if ($isOpenType
            && config('DoubleGame')->useMaximumPeriodValue($type_id, $sport_id, $area_id)
            && config('DoubleGame')->getMaxNumberOfPeriods($type_id, $sport_id, $area_id) > config('DoubleGame')->getNumberOfPeriods($type_id, $sport_id, $area_id)
          ) {
            for ($i = config('DoubleGame')->getNumberOfPeriods($type_id, $sport_id, $area_id) + 1; $i <= config('DoubleGame')->getMaxNumberOfPeriods($type_id, $sport_id, $area_id); $i++) {
              $unix_time_I = strtotime($date . ' ' . $time_start);
              $time_finish = date('H:i', strtotime('+ ' . $area_data['period'] . ' minutes', $unix_time_I));
              $times[]     = ['start' => $time_start, 'finish' => $time_finish];
              $time_start  = $time_finish;
            }
            $outNumberOfPeriods = '<tr><td class="light">' . lang('Playtime') . ':</td><td class="light">';
            for ($i = config('DoubleGame')->getNumberOfPeriods($type_id, $sport_id, $area_id); $i <= config('DoubleGame')->getMaxNumberOfPeriods($type_id, $sport_id, $area_id); $i++) {
              $outNumberOfPeriods .= '<div class="type-reservation-item">
                        <input type="radio" name="numberOfPeriods" value="' . $i . '" class="radio" ' . ($i == config('DoubleGame')->getNumberOfPeriods($type_id, $sport_id, $area_id) ? 'checked'
                  : '') . '
                          id="numberOfPeriods_' . $i . '"/>
                            <span class="podlog"></span><label for="numberOfPeriods_' . $i . '">' . TranslateHelper::translatePeriodInMinute($i * $area_data['period'],
                  false) . '</label>
                  </div>';
            }

            $outNumberOfPeriods .= '</td></tr>';
          }
          $output = [];

          //ошибка добавления
          if (isset ($this->error)) {
            $output[] = $this->error;
          }
          $action = 'proceedRequestAdmin';
          if ($join) {
            $action = 'join';
          }
          $bgcolor           = ColorsHelper::hexByTypeSport($type_id, $sport_id);
          $title_time_finish = $isOpenType ? $times[1]['finish'] : $times[0]['finish'];
          $keyIVal           = config('DoubleGame')->useMaximumPeriodValue($type_id, $sport_id, $area_id)
            ? config('DoubleGame')->getNumberOfPeriods($type_id, $sport_id, $area_id)
            : 1;
          $title_time        = '<span class="title-time" data-title-time="' . ($keyIVal) . '">' . $title_time_finish . '</span>';
          if (config('DoubleGame')->useMaximumPeriodValue($type_id, $sport_id, $area_id)) {
            for ($i = config('DoubleGame')->getNumberOfPeriods($type_id, $sport_id, $area_id); $i < config('DoubleGame')->getMaxNumberOfPeriods($type_id, $sport_id, $area_id); $i++) {
              $title_time .= '<span class="title-time hidden"  data-title-time="' . ($i + 1) . '">' . $times[$i]['finish'] . '</span>';
            }
          } else {
            $title_time .= '<span class="title-time hidden"  data-title-time="' . ($keyIVal + 1) . '">' . $times[count($times) - 1]['finish'] . '</span>';
          }
          $out = '<table width="100%" bgcolor="#' . $bgcolor . '" cellspacing="5" class="areaType"><tr><td class="title" width="25%">' . $area_data['title'] . '</td><td width="50%" align="center" class="date">' . date(
              'd',
              $get_unix_time
            ) . '. ' . TranslateHelper::translateMonth(date('n', $get_unix_time)) . ' ' . date(
              'Y',
              $get_unix_time
            ) . ', ' . TranslateHelper::translateWeekday(
              $weekday
            ) . ', ' . $time . ' - ' . $title_time . '</td><td width="25%">&nbsp;</td></tr></table><br>' . "\n";
          $out .= '<form action="reservations.php?action=' . $action . '&type_id=' . $area_type['type_id'] . '&area_id=' . $area_id . '&date=' . $date . '&time=' . $time . '&sport_id=' . $area_data['sport_id'] . '&page=' . $page . '" method="post" name="insertReservation">' . "\n";

          $out .= '<input type="hidden" name="main_reservation_id" value="' . Service::request()->_('main_reservation_id') . '"/>';
          $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main">' . "\n";
          $out .= '<tr><th colspan="2">' . lang('New reservation', 'reservations') . '</th></tr>' . "\n";


          $out .= '<script>
              var stock = new ajaxLoader(\'stock\', \'get_ajax_data.php?action=getStocksList\', \'stockBlockId\', \'stockBlockIdLoading\');
              todo.onload(function(){stock.loadModule(\'client_id=' . $client_id_selected . '&date=' . $date . '&area_id=' . $area_data['area_id'] . '&time=' . $time . '\');});
          </script>' . "\n";

          //выбор клиента
          $out .= '<tr><td class="light">' . lang('Client') . '</td>
                  <td class="light"><select name="client_id" tabindex="1" size="3" class="input wide clientsList select2-find" data-selected-id="' . (int)$client_id_selected . '" 
                      onchange="return stock.loadModule(\'client_id=\'+this.options[this.selectedIndex].value)">' . "\n";
          $out .= "<option value=\"-1\"" . ($client_id_selected <= 0 || $client_id_selected === null ? ' selected' : '') . " >" . lang('New client') . "</option>\n";
          if ($client_id_selected && $this->engine->clients->getClientData($client_id_selected, $client_data)) {
            $client_data = (object)$client_data;
              $tmp = $client_data->surname . ' ' . $client_data->name;
              if (strlen($tmp) > 75) {
                $tmp = substr($tmp, 0, 75) . '...';
              }
              $out .= "<option value=\"" . $client_data->client_id . "\"" . ($client_id_selected == $client_data->client_id ? ' selected'
                  : '') . '>' . $tmp . "</option>\n";
          }

          $out .= '</select></td></tr>' . "\n";
          $out .= $outNumberOfPeriods ?? '';
          $out .= "<script>
          $('body').on('change', '[name=" . (config('DoubleGame')->useMaximumPeriodValue($type_id, $sport_id, $area_id) ? 'numberOfPeriods' : 'type_reservation') . "]', function () {
            let val = $(this).val();"
            . (config('DoubleGame')->useMaximumPeriodValue($type_id, $sport_id, $area_id) ?
              "$('.webio-hidden').each(function (){
                if ($(this).attr('data-hidden') <= val) {
                  $(this).css('display', 'block');
                } else {
                  $(this).css('display', 'none');
                }
              });" : '') .
            "$('.title-time').each(function (){
                if ($(this).attr('data-title-time') == val) {
                  $(this).removeClass('hidden');
                } else {
                  $(this).addClass('hidden');
                }
              })
          })
              </script>";
          if (!$join) {
            foreach (WebIoModel::getWebIoTypes() as $webIoKey => $webIoType) {
              $outWebIo = '';
              $check    = 0;
              if ($area_data[$webIoKey . '_on'] == 1) {
                $i = 1;
                foreach ($times as $key => $timeData) {
                  if (AreasLightsModel::checkAreasDefaultState(
                    $area_id,
                    $weekday,
                    date('H:i:s', strtotime($date . ' ' . $timeData['start'])),
                    $webIoType['id']
                  )) {
                    $outWebIo .= '<input type="hidden" name="' . $webIoKey . '_state[' . $timeData['start'] . ']" value="1" />';
                  } else {
                    $check++;
                    $outWebIo .= '<p class="web-io-state ' . ($key > 1 ? 'webio-hidden' : '') . '" style="' . ($key > 1 ? 'display:none" data-type="2" data-hidden="' . $i . '"'
                        : '') . '"><input type="checkbox" name="' . $webIoKey . '_state[' . $timeData['start'] . ']" value="1" /> ' . number_format(
                        $area_data[$webIoKey . '_price'],
                        2,
                        ',',
                        ''
                      ) . ' ' . CURR_VALUTE . ' ' . lang('for') . ' ' . $timeData['start'] . ' - ' . $timeData['finish'] . ' ' . lang('Clock') . ' </p>';
                  }
                  $i++;
                }
              }
              $out .= $check > 0 ? '<tr><td class="dark">' . lang('Do you need state', 'reservations',
                  ['webIo_type' => $webIoType['title']]) . '</td><td class="dark">' . $outWebIo . '</td></tr>'
                : $outWebIo;
            }
            $out .= '<tr><td class="light">' . lang('Options') . '</td><td class="light">';
            $out .= '<img src="' . base_url(paths()->getAssetsDir('images/ajax_loader.gif',
                'common')) . '" id="stockBlockIdLoading" alt="loading" style="margin:0 auto; "/>' . "\n";
            $out .= '<div id="stockBlockId">';
            $out .= '</div></td></tr>' . "\n";
            //комментарий к заказу
            $out .= '<tr><td class="dark">' . lang('Comment') . '</td><td class="dark"><input type="text" name="memo" class="input wide" value="' . (isset ($_POST['memo'])
                ? htmlspecialchars(
                  $_POST['memo'],
                  ENT_QUOTES
                ) : '') . '"></td></tr>' . "\n";

            if ($isOpenType) {
              if (config('DoubleGame')->doubleFriendsEnabled($type_id, $sport_id, $area_id)) {
                $view = new View(['tpl_view' => 'reservations']);
                $out  .= $view->render(
                  'open/_choice_players',
                  ['clients' => [],
                    'type_id' => $type_id,
                    'sport_id' => $sport_id,
                    'area_id' => $area_id,
                  ]
                );
              } else {
                $out .= '<tr><td class="dark">' . lang('Guest_player') . '</td><td class="dark">' . "\n";
                $out .= '<div class="orderItemBox">' . "\n";
                $out .= '<input name="second_player" value="guest" type="checkbox" onchange="viewHideBlock(\'secondPlayerBox\')" />';
                $out .= '<div id="secondPlayerBox" style="display:none; padding:10px">' . "\n";
                $out .= '<label style="display:block; float:left; width:70px; padding-top:4px;">' . lang('First name') . ':</label> <input name="guest_name" value="" class="input small" title="' . lang('First name') . '" type="text" /><br />';
                $out .= '<label style="display:block; float:left; width:70px; padding-top:4px;">' . lang('Family name') . ':</label> <input name="guest_surname" value="" class="input small" title="' . lang('Family name') . '" type="text" />';
                $out .= '</div>' . "\n";
                $out .= '</td></tr>' . "\n";
              }
            }
          }

          //флажок, который показывает что заказ сделал admin
          $out .= '<input type="hidden" name="customer" value="1">' . "\n";

          $out .= '<tr><th align="center" colspan="4"><input type="submit" tabindex="2" value="' . lang('button_create') . '" class="button"></th></tr>' . "\n";
          $out .= "</table>\n";

          //форма добавления клиента
          prepareClass('client_insert');
          $client_data = [];
          if (isset ($this->error)) //если была ошибка - восстанавливаем введенные данные
          {
            $client_data = client_insert::getClientFormEnteredValues();
          }

          $out .= '<br>' . "\n";
          $out .= '<div id="insertClient">' . "\n";
          $out .= client_insert::getClientForm(null, 2, $client_data);
//          $out .= '<script>initializeInsertClientForm ("insertReservation")</script>' . "\n";
          $out .= '</div>' . "\n";
          $out .= '</form>' . "\n";

          $output[] = $out;

          //ссылка назад только если дата верна и площадка найдена
          $output[] = '<p align="center"><a href="reservations.php?action=showReservations&type_id=' . $area_type['type_id'] . '&sport_id=' . $area_data['sport_id'] . '&date=' . $date . '#requests">' . lang('Back') . '</a></p>' . "\n";

          return $output;
        } else {
          //код ошибки, почему день/время недоступено
          $this->error = lang('Error_message', 'message_error', ['error_message' => $this->explainCheckAreaDateTimeAvailableError($error_code)]);

          return $this->showReservations($type_id);
        }
      } else {
        //дата/время неверно
        $this->error = lang('date_time invalid', 'message_error');

        return $this->showReservations($type_id);
      }
    } else {
      //не все данные переданы
      $this->error = lang('input data error', 'message_error');

      return $this->mainPage();
    }
  }

  //Исправленная функция бронирования для админа из /at/
  function proceedRequestAdmin()
  {
    $type_id   = (int)Service::request()->_('type_id');
    $client_id = Service::request()->_('client_id');
    if ($type_id) {
      $client_id  = $client_id <= 0 ? null : $client_id;
      $mysql_date = Service::request()->_('date');
      $mysql_time = Service::request()->_('time');

      //проверка даты и времени
      $get_unix_time = strtotime($mysql_date . ' ' . $mysql_time);
      if (@date('Y-m-d H:i', $get_unix_time) == $mysql_date . ' ' . $mysql_time) {
        //значения верны

        //сохранение id клиента в cookies
        if ($client_id != null) //записываем в cookies id указанного клиента
        {
          setcookie('client_id_last', $client_id, time() + 3600);
        }/* expire in 1 hour */
        else //иди очищаем куки, если надо добавить нового клиента
        {
          setcookie('client_id_last', '', time() - 3600);
        }/* expire in 1 hour */

        //если надо добавить клиента
        if ($client_id === null) {
          //добавляем клиента
          prepareClass('client_insert');
          $_POST['prepayment']    = Service::request()->_('encash');
          $_REQUEST['prepayment'] = Service::request()->_('encash');
          if (client_insert::proceedInsertClient(1, $this->engine, $error, $client_id) == false) {
            //добавление не прошло - что-то не так...
            $this->error = $error;

            return $this->requestForm();
          }
        }

        // берем данные клиента
        // это нужно только для encash - наличные деньги для Online клиентов
        if ($this->engine->clients->getClientData($client_id, $client_data) == false) {
          return $this->requestForm();
        }
        //сохранение id клиента в cookies
        if ($client_id != null) //записываем в cookies id указанного клиента
        {
          setcookie('client_id_last', $client_id, time() + 3600);
        }/* expire in 1 hour */
        else //иди очищаем куки, если надо добавить нового клиента
        {
          setcookie('client_id_last', '', time() - 3600);
        }/* expire in 1 hour */

        Service::request()->set('post', 'client_id', $client_id);
        Service::request()->set('request', 'client_id', $client_id);

        return $this->setActionByTypeCourt('proceedOrder');
      }
    } else {
      //не все данные переданы
      $this->error = lang('input data error', 'message_error');

      return $this->mainPage();
    }
  }


  //редактирование заказа
  function editRequest()
  {
    if (isset ($_GET['area_id']) && $_GET['date'] && $_GET['time']) {
      //принятые значения
      $area_id        = (int)$_GET['area_id'];
      $date           = $_GET['date'];
      $time           = $_GET['time'];
      $mysql_datetime = $_GET['date'] . ' ' . $_GET['time'];
      $unixtime       = strtotime($mysql_datetime);
      $page           = Service::request()->_('page', 1);
      //Абонемент
      if (isset($_GET['ticket'])) {
        $this->engine->areas->getAreaData($area_id, $area);

        //ключ страницы
        $this->page_key   = 'reservations_areas';
        $this->page_key_r = 'reservations_' . $area['type_id'] . '_' . $area['sport_id'];

        $output = [];

        $out = '<table width="100%" bgcolor="#' . ColorsHelper::hexByTypeSport(
            $area['type_id'],
            $area['sport_id']
          ) . '" cellspacing="5"class="areaType"><tr><td class="title" width="25%">' . $this->engine->areas->getTitleByAreaId(
            $area['area_id']
          ) . ' - ' . $area['title'] . '</td><td width="50%" align="center" class="date">' . date(
            'd',
            $unixtime
          ) . '. ' . TranslateHelper::translateMonth(date('n', $unixtime)) . ' ' . date(
            'Y',
            $unixtime
          ) . ', ' . TranslateHelper::translateWeekday(
            CalendarHelper::getWeekdayByUnixtime($unixtime)
          ) . ', ' . $time . ' - ' . TimeHelper::convertMinutes2MySQLTime(
            TimeHelper::convertMySQLTimeToMinutes($time) + $area['period']
          ) . '</td><td width="25%">&nbsp;</td></tr></table><br>' . "\n";

        $out .= '<form action="reservations.php?action=changeRequest&type_id=' . $area['type_id'] . '&sport_id=' . $area['sport_id'] . '&area_id=' . $area_id . '&ticket=' . (int)$_GET['ticket'] . '&date=' . $date . '&time=' . $time . '" method="post" name="insertReservation">' . "\n";

        $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main">' . "\n";
        $out .= '<tr><th colspan="2">' . lang('Change reservation', 'reservations') . '</th></tr>' . "\n";

        //Свет
        if ($area['light_on'] == 1 &&
          !$this->engine->tickets->checkLightTicket(
            1,
            $area['area_id'],
            date('Y-m-d', $unixtime),
            date('H:i:s', $unixtime),
            CalendarHelper::getWeekdayByUnixtime($unixtime)
          ) &&
          !AreasLightsModel::checkAreasDefaultState(
            $area['area_id'],
            CalendarHelper::getWeekdayByUnixtime($unixtime),
            date('H:i:s', $unixtime),
            1
          )
        ) {
          $out .= '<tr><td class="dark">' . lang('Do you need state', 'reservations', ['webIo_type' => lang('webIo_light', 'webIo')]) . '</td><td class="dark">
              <input type="checkbox" name="light_state" value="1" /> ' . number_format(
              $area['light_price'],
              2,
              ',',
              ''
            ) . ' ' . CURR_VALUTE . ' ' . lang('per hour', 'reservations') . '
					   </td></tr>';
        }

        //Отопление
        if ($area['heating_on'] == 1 &&
          !$this->engine->tickets->checkLightTicket(
            2,
            $area['area_id'],
            date('Y-m-d', $unixtime),
            date('H:i:s', $unixtime),
            CalendarHelper::getWeekdayByUnixtime($unixtime)
          ) &&
          !AreasLightsModel::checkAreasDefaultState(
            $area['area_id'],
            CalendarHelper::getWeekdayByUnixtime($unixtime),
            date('H:i:s', $unixtime),
            2
          )
        ) {
          $out .= '<tr><td class="dark">' . lang('Do you need state', 'reservations', ['webIo_type' => lang('webIo_heating', 'webIo')]) . '</td><td class="dark">
              <input type="checkbox" name="heating_state" value="1" /> ' . number_format(
              $area['heating_price'],
              2,
              ',',
              ''
            ) . ' ' . CURR_VALUTE . ' ' . lang('per hour', 'reservations') . '
					   </td></tr>';
        }

        //Сеть
        if ($area['net_on'] == 1 &&
          !$this->engine->tickets->checkLightTicket(
            3,
            $area['area_id'],
            date('Y-m-d', $unixtime),
            date('H:i:s', $unixtime),
            CalendarHelper::getWeekdayByUnixtime($unixtime)
          ) &&
          !AreasLightsModel::checkAreasDefaultState(
            $area['area_id'],
            CalendarHelper::getWeekdayByUnixtime($unixtime),
            date('H:i:s', $unixtime),
            3
          )
        ) {
          $out .= '<tr><td class="dark">' . lang('Do you need state', 'reservations', ['webIo_type' => lang('webIo_net', 'webIo')]) . '</td><td class="dark">
					    <input type="checkbox" name="net_state" value="1" /> ' . number_format($area['net_price'], 2, ',',
              '') . ' ' . CURR_VALUTE . ' ' . lang('per hour', 'reservations') . '
					   </td></tr>';
        }

        $out .= '<tr><th align="center" colspan="2"><input type="submit" tabindex="2" value="' . lang('button_update') . '" class="button"></th></tr>' . "\n";
        $out .= "</table>\n";

        $output[] = $out;

        //ссылка назад
        $output[] = '<p align="center"><a href="reservations.php?action=showReservations&type_id=' . $area['type_id'] . '&sport_id=' . $area['sport_id'] . '&date=' . $date . '&page=' . $page . '">' . lang('Back') . '</a></p>' . "\n";

        return $output;
        //данные заказа
      } else {
        if ($this->engine->getReservationData($area_id, $mysql_datetime, $reservation_data)) {
          //данные площадки
          $this->engine->areas->getAreaData($reservation_data['area_id'], $area);

          //имя и фамилия клиента
          if ($reservation_data['client_id'] !== null) {
            $this->engine->clients->getClientData($reservation_data['client_id'], $client);
            $client = $client['name'] . ' ' . $client['surname'];
          } else {
            $client = $reservation_data['comment'];
          }

          //ключ страницы
          $this->page_key = 'reservations_areas';

          $this->page_key_r = 'reservations_' . $area['type_id'] . '_' . $area['sport_id'];

          $output = [];

          $out = '<table width="100%" bgcolor="#' . ColorsHelper::hexByTypeSport(
              $area['type_id'],
              $area['sport_id']
            ) . '" cellspacing="5" class="areaType"><tr><td class="title" width="25%">' . $this->engine->areas->getTitleByAreaId(
              $area['area_id']
            ) . ' - ' . $area['title'] . '</td><td width="50%" align="center" class="date">' . date(
              'd',
              $unixtime
            ) . '. ' . TranslateHelper::translateMonth(date('n', $unixtime)) . ' ' . date(
              'Y',
              $unixtime
            ) . ', ' . TranslateHelper::translateWeekday(
              CalendarHelper::getWeekdayByUnixtime($unixtime)
            ) . ', ' . $time . ' - ' . TimeHelper::convertMinutes2MySQLTime(
              TimeHelper::convertMySQLTimeToMinutes($time) + $area['period']
            ) . '</td><td width="25%">&nbsp;</td></tr></table><br>' . "\n";

          $out .= '<form action="reservations.php?action=changeRequest&type_id=' . $area['type_id'] . '&sport_id=' . $area['sport_id'] . '&area_id=' . $area_id . '&date=' . $date . '&time=' . $time . '" method="post" name="insertReservation">' . "\n";

          $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main">' . "\n";
          $out .= '<tr><th colspan="2">' . lang('Change reservation', 'reservations') . '</th></tr>' . "\n";
          $out .= '<tr><td class="light">' . lang('Client') . '</td><td class="light">' . $client . '</td></tr>' . "\n";

          //Свет
          if ($area['light_on'] == 1) {
            if ($reservation_data['light_state'] == 1 || AreasLightsModel::checkAreasDefaultState(
                $area['area_id'],
                CalendarHelper::getWeekdayByUnixtime($unixdate),
                date('H:i:s', $unixtime),
                1
              )) {
              $out .= '<input type="hidden" name="light_state" value="1" />';
            } else {
              $out .= '<tr><td class="dark">' . lang('Do you need state', 'reservations', ['webIo_type' => lang('webIo_light', 'webIo')]) . '</td><td class="dark">
              <input type="checkbox" name="light_state" value="1" /> ' . number_format(
                  $area['light_price'],
                  2,
                  ',',
                  ''
                ) . ' ' . CURR_VALUTE . ' ' . lang('per hour', 'reservations') . '
              </td></tr>';
            }
          }
          //Отоплеине
          if ($area['heating_on'] == 1) {
            if ($reservation_data['heating_state'] == 1 || AreasLightsModel::checkAreasDefaultState(
                $area['area_id'],
                CalendarHelper::getWeekdayByUnixtime($unixdate),
                date('H:i:s', $unixtime),
                2
              )) {
              $out .= '<input type="hidden" name="heating_state" value="1" />';
            } else {
              $out .= '<tr><td class="dark">' . lang('Do you need state', 'reservations', ['webIo_type' => lang('webIo_heating', 'webIo')]) . '</td><td class="dark">
              <input type="checkbox" name="heating_state" value="1" /> ' . number_format(
                  $area['heating_price'],
                  2,
                  ',',
                  ''
                ) . ' ' . CURR_VALUTE . ' ' . lang('per hour', 'reservations') . '
					    </td></tr>';
            }
          }

          //сеть
          if ($area['net_on'] == 1) {
            if ($reservation_data['net_state'] == 1 || AreasLightsModel::checkAreasDefaultState(
                $area['area_id'],
                CalendarHelper::getWeekdayByUnixtime($unixdate),
                date('H:i:s', $unixtime),
                3
              )) {
              $out .= '<input type="hidden" name="net_state" value="1" />';
            } else {
              $out .= '<tr><td class="dark">' . lang('Do you need state', 'reservations', ['webIo_type' => lang('webIo_net', 'webIo')]) . '</td><td class="dark">
					    <input type="checkbox" name="net_state" value="1" /> ' . number_format($area['net_price'], 3, ',',
                  '') . ' ' . CURR_VALUTE . ' ' . lang('per hour', 'reservations') . '
					    </td></tr>';
            }
          }

          $out .= '<tr><td class="light">' . lang('Comment') . '</td><td class="light"><input type="text" name="memo" class="input wide" value="' . htmlspecialchars(
              $reservation_data['memo'],
              ENT_QUOTES
            ) . '"></td></tr>' . "\n";

          $out .= '<tr><th align="center" colspan="2"><input type="submit" tabindex="2" value="' . lang('button_update') . '" class="button"></th></tr>' . "\n";
          $out .= "</table>\n";

          $output[] = $out;

          //ссылка назад
          $output[] = '<p align="center"><a href="reservations.php?action=showReservations&type_id=' . $area['type_id'] . '&sport_id=' . $area['sport_id'] . '&date=' . $date . '&page=' . $page . '#requests">' . lang('Back') . '</a></p>' . "\n";

          return $output;
        } else {
          //заказ не найден
          $this->error = lang('order not found', 'message_error');

          return $this->mainPage();
        }
      }
    } else {
      //не все данные переданы
      $this->error = lang('input data error', 'message_error');

      return $this->mainPage();
    }
  }

  //изменение заказа
  function changeRequest()
  {
    if (isset ($_GET['type_id']) && isset ($_GET['area_id']) && isset ($_GET['date']) && isset ($_GET['time'])) {
      if (@date('Y-m-d H:i', strtotime($_GET['date'] . ' ' . $_GET['time'])) == $_GET['date'] . ' ' . $_GET['time']) {
        $unixtime           = strtotime($_GET['date'] . ' ' . $_GET['time']);
        $heating_flag       = $light_flag = $net_flag = false;
        $electricity_action = [];
        $this->engine->areas->getAreaData((int)$_GET['area_id'], $area_data);
        //включаем свет
        if (isset($_POST['light_state']) && (int)$_POST['light_state'] == 1) {
          //Абонемент
          if (isset($_GET['ticket'])) {
            if ($this->engine->tickets->insertLightHeatingTicket(
              (int)$_GET['ticket'],
              1,
              date('Y-m-d', $unixtime),
              date('H:i', $unixtime),
              CalendarHelper::getWeekdayByUnixtime($unixtime)
            )) {
              $light_flag = true;
            }
            //Обычный заказ
          } else {
            if ($this->engine->changeReservationLightHeatingState(
              (int)$_GET['area_id'],
              1,
              date('Y-m-d H:i', $unixtime),
              1,
              $area_data['light_price']
            )) {
              $light_flag = true;
            }
          }

          //Включаем свет, если сейчас то время
          if ($light_flag && date('Y-m-d') == date('Y-m-d', $unixtime) && date('H:i') >= date('H:i', $unixtime)) {
            $electricity_action[(int)$_GET['area_id']][1] = 1;
          }
        }

        //включаем отопление
        if (isset($_POST['heating_state']) && (int)$_POST['heating_state'] == 1) {
          //Абонемент
          if (isset($_GET['ticket'])) {
            if ($this->engine->tickets->insertLightHeatingTicket(
              (int)$_GET['ticket'],
              2,
              date('Y-m-d', $unixtime),
              date('H:i', $unixtime),
              CalendarHelper::getWeekdayByUnixtime($unixtime)
            )) {
              $heating_flag = true;
            }
            //Обычный заказ
          } else {
            if ($this->engine->changeReservationLightHeatingState(
              (int)$_GET['area_id'],
              2,
              date('Y-m-d H:i', $unixtime),
              1,
              $area_data['heating_price']
            )) {
              $heating_flag = true;
            }
          }

          //Включаем отопление, если сейчас то время
          if ($heating_flag && date('Y-m-d') == date('Y-m-d', $unixtime) && date('H:i') >= date('H:i', $unixtime)) {
            $electricity_action[(int)$_GET['area_id']][2] = 1;
          }
        }

        //включаем сеть
        if (isset($_POST['net_state']) && (int)$_POST['net_state'] == 1) {
          //Абонемент
          if (isset($_GET['ticket'])) {
            if ($this->engine->tickets->insertLightHeatingTicket(
              (int)$_GET['ticket'],
              3,
              date('Y-m-d', $unixtime),
              date('H:i', $unixtime),
              CalendarHelper::getWeekdayByUnixtime($unixtime)
            )) {
              $net_flag = true;
            }
            //Обычный заказ
          } else {
            if ($this->engine->changeReservationLightHeatingState(
              (int)$_GET['area_id'],
              3,
              date('Y-m-d H:i', $unixtime),
              1,
              $area_data['net_price']
            )) {
              $net_flag = true;
            }
          }

          //Включаем отопление, если сейчас то время
          if ($net_flag && date('Y-m-d') == date('Y-m-d', $unixtime) && date('H:i') >= date('H:i', $unixtime)) {
            $electricity_action[(int)$_GET['area_id']][3] = 1;
          }
        }
        $this->engine->light->areasElectricityProcess($electricity_action, $error);

        //изменение данных о заказе
        if (isset($_GET['ticket'])) {
          return $this->showReservations();
        } else {
          if ($this->engine->changeReservation(
            (int)$_GET['area_id'],
            $_GET['date'] . ' ' . $_GET['time'],
            null,
            $_POST['memo']
          )) {
            $this->message = lang('Reservation changed', 'message_success');

            return $this->showReservations();
          } else {
            //изменение не прошло
            $this->error = lang('Error_message', 'message_error', ['error_message' => lang('reservation not changed', 'message_error')]);

            return $this->showReservations();
          }
        }
      } else {
        //invalid date/time
        $this->error = lang('date_time invalid', 'message_error');

        return $this->mainPage();
      }
    } else {
      //не все данные переданы
      $this->error = lang('input data error', 'message_error');

      return $this->mainPage();
    }
  }

  //удалить заказ
  function removeRequest()
  {
    return $this->setActionByTypeCourt('removeOrder');
  }

  /** Запустить действие из контролера по типу корта
   *
   * @param $action
   *
   * @return array
   */
  public function setActionByTypeCourt($action)
  {
    if (Service::request()->check('type_id')) {
      $page = (object)\Service::app()->execModule('reservations', ['action' => $action]);

      if ($page->key == 'error') {
        $this->error = $page->content[1] ?? $page->content[0];
      } else {
        $this->message = $page->content[1] ?? $page->content[0];
      }

      return $this->showReservations();
    } else {
      $this->error = lang('Not all required GET variables presented', 'message_error');

      return $this->mainPage();
    }
  }

  //статистика за последний месяц - html выход
  function statisticsByLastMonth()
  {
    $out = '';

    $out .= '<table width="100%">' . "\n<tr>\n";

    //дни недели по популярности
    $out  .= '<td width="20%" valign="top">' . "\n";
    $temp = Query::sqlQuery(
      'select count(r.reservation_id), WEEKDAY(start)
			from ' . Query::tableName('reservations') . ' r
			group by WEEKDAY(start)', [], true, ['style' => PDO::FETCH_NUM]
    );
    $c    = [];
    foreach ($temp as $row) {
      $c[] = ['<b>' . $row[0] . '</b>', TranslateHelper::translateWeekday($row[1])];
    }
    $out .= $this->getStatTableContent(lang('Weekdays rating', 'reservations'), 2, $c, '100%');
    $out .= '</td>' . "\n";

    //лучшие 10 дней текущего месяца
    $out  .= '<td width="20%" valign="top">' . "\n";
    $temp = Query::sqlQuery(
      'select count(*) as a, start
			from ' . Query::tableName('reservations') . '
			where start between "' . date('Y-m') . '-01" and "' . date('Y-m') . '-1" + INTERVAL 1 MONTH
			group by DAYOFMONTH(start)
			order by a desc
			limit 10', [], true, ['style' => PDO::FETCH_NUM]
    );
    $c    = [];
    if (!empty($temp)) {
      foreach ($temp as $row) {
        $c[] = ['<b>' . $row[0] . '</b>', date('d.m.Y', strtotime($row[1]))];
      }
    }
    $out .= $this->getStatTableContent(lang('The best 10 days this month', 'reservations'), 2, $c, '100%', ['left', 'center']);
    $out .= '</td>' . "\n";

    //10 худших дней
    $out  .= '<td width="20%" valign="top">' . "\n";
    $temp = Query::sqlQuery(
      'select count(*) as a, start
			from ' . Query::tableName('reservations') . '
			where start between "' . date('Y-m') . '-01" and "' . date('Y-m') . '-1" + INTERVAL 1 MONTH
			group by DAYOFMONTH(start)
			order by a
			limit 10', [], true, ['style' => PDO::FETCH_NUM]
    );
    $c    = [];
    if (!empty($temp)) {
      foreach ($temp as $row) {
        $c[] = ['<b>' . $row[0] . '</b>', date('d.m.Y', strtotime($row[1]))];
      }
    }
    $out .= $this->getStatTableContent(
      lang('The worst 10 days this month', 'reservations'),
      2,
      $c,
      '100%',
      ['left', 'center']
    );
    $out .= '</td>' . "\n";

    //20 уродов
    $out  .= '<td width="20%" valign="top">' . "\n";
    $temp = Query::sqlQuery(
      'select name, surname, client_id, reservations_removed
			from ' . Query::tableName('clients') . '
			where reservations_removed > 0
			order by reservations_removed desc
			limit 20', [], true, ['style' => PDO::FETCH_NUM]
    );
    $c    = [];
    foreach ($temp as $row) {
      $c[] = [LayoutHelper::renderClientInfoHref($row[0], $row[1], $row[2]), $row[3]];
    }
    $out .= $this->getStatTableContent(lang('Top 20 Cancellations', 'reservations'), 2, $c, '100%');
    $out .= '</td>' . "\n";

    //20 прибыльных
    $out  .= '<td width="20%" valign="top">' . "\n";
    $temp = Query::sqlQuery(
      'select c.name, c.surname, c.client_id, count(r.reservation_id) as cnt
			from ' . Query::tableName('clients') . ' c,
				' . Query::tableName('reservations') . ' r
			where r.client_id = c.client_id
			group by r.client_id
			order by cnt desc
			limit 20', [], true, ['style' => PDO::FETCH_NUM]
    );
    $c    = [];
    foreach ($temp as $row) {
      $c[] = [LayoutHelper::renderClientInfoHref($row[0], $row[1], $row[2]), $row[3]];
    }
    $out .= $this->getStatTableContent(lang('Top 20 reservations', 'reservations'), 2, $c, '100%');
    $out .= '</td>' . "\n";

    $out .= '</tr>' . "\n";

    $out .= '<tr><td colspan="5">' . lang('Online reservations this month',
        'reservations') . ': <b>' . $this->engine->statistics->getCurrentMonthReservationsCount() . '</b></td></tr>' . "\n";
    $out .= '</table>' . "\n";

    return $out;
  }

  //статичтика за последний час - html выход
  function statisticsByLastHour()
  {
    $out = '';

    $out .= '<table width="100%" border="0"><tr>';

    //за последний час
    $c    = [];
    $temp = Query::sqlQuery(
      'select count(r.reservation_id) as cnt, a.title, at.title as type, at.color, asp.title as sport, a.type_id, a.sport_id 
			from ' . Query::tableName('reservations') . ' r, ' . Query::tableName('areas') . ' a, ' . Query::tableName(
        'areas_types'
      ) . ' at, ' . Query::tableName('areas_sports') . ' asp 
			where
				r.ordered > "' . date('Y-m-d H:i:s', time() - 60 * 60) . '"
				and r.area_id = a.area_id
				and a.type_id = at.type_id
				and a.sport_id = asp.sport_id
			group by r.area_id'
    );
    foreach ($temp as $row) {
      $title = $this->engine->areas->getTitleByTypeAndSport($row['type_id'], $row['sport_id']);
      $c[]   = [
        $row['cnt'],
        LayoutHelper::renderSquareByTypeSport($row['type_id'], $row['sport_id']),
        $title . ' - ' . $row['title']
      ];
    }
    $out .= '<td width="50%" align="right" valign="top">';
    $out .= $this->getStatTableContent(
      lang('Bookings within the last hour', 'reservations'),
      3,
      $c,
      '50%',
      ['center', 'center']
    );
    $out .= '</td>';

    //сейчас играют
    $c    = [];
    $temp = Query::sqlQuery(
      'select c.name, c.surname, c.client_id, a.title, at.title as type, at.color, asp.title as sport, a.type_id, a.sport_id 
			from ' . Query::tableName('reservations') . ' r, ' . Query::tableName('areas') . ' a, ' . Query::tableName(
        'areas_sports'
      ) . ' asp, ' . Query::tableName('areas_types') . ' at, ' . Query::tableName('clients') . ' c
			where
				now() between r.start and r.finish
				and r.area_id = a.area_id
				and at.type_id = a.type_id
				and asp.sport_id = a.sport_id
				and c.client_id = r.client_id'
    );
    foreach ($temp as $row) {
      $c[] = [
        LayoutHelper::renderClientInfoHref($row['name'], $row['surname'], $row['client_id']),
        LayoutHelper::renderSquareByTypeSport($row['type_id'], $row['sport_id']),
        $this->engine->areas->getTitleByTypeAndSport($row['type_id'], $row['sport_id']) . ' - ' . $row['title']
      ];
    }
    $out .= '<td width="50%" align="left" valign="top">';
    $out .= $this->getStatTableContent(lang('Currently occupied playgrounds', 'reservations'), 3, $c, '50%', ['left', 'center']);
    $out .= '</td>';

    $out .= '</tr></table>';

    return $out;
  }

  //расшифровка кода ошибки checkAreaDateTimeAvailable
  function explainCheckAreaDateTimeAvailableError($error_code)
  {
    if ($error_code == 1) {
      return lang('date_time is unavailable for this area', 'message_error');
    } elseif ($error_code == 2) {
      return lang('period unavailable', 'message_error');
    } elseif ($error_code == 3) {
      return lang('date id holiday', 'message_error');
    } elseif ($error_code == 4) {
      return lang('period blocked', 'message_error');
    } elseif ($error_code == 5) {
      return lang('period already ordered', 'message_error');
    } elseif ($error_code == 6) {
      return lang('period already requested by ticket', 'message_error');
    } elseif ($error_code == PricingReservationEngine::ERROR_NO_PERIOD_PRICE) {
      return lang('date_time is unavailable for this area', 'message_error');
    } elseif ($error_code == 'p1') {
      return lang('error_p1', 'message_error');
    }
  }

  //шаблон для вывода таблицы статистики
  function getStatTableContent($title, $columns_count, $data, $table_width = '', $align = [])
  {
    $out = '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" class="main"' . ($table_width ? ' width="' . $table_width . '"'
        : '') . '>' . "\n";
    $out .= '<tr><th colspan="' . $columns_count . '" style="height:35px;">' . $title . '</th></tr>' . "\n";
    if (is_array($data) and !empty ($data)) {
      $j = 0;
      foreach ($data as $item) {
        $class = $j % 2 == 0 ? 'light' : 'dark';

        $out .= '<tr>';
        for ($i = 0; $i < $columns_count; $i++) {
          $out .= '<td class="' . $class . '"' . (isset ($align[$i]) ? ' align="' . $align[$i] . '"' : '') . '>' . $item[$i] . '</td>' . "\n";
        }
        $out .= '</tr>' . "\n";

        $j++;
      }
    }
    $out .= '</table>' . "\n";

    return $out;
  }

  function renderPages($type_id, $sport_id, $date)
  {
    $page  = (int)(Service::request()->_('page', 1));
    $pages = USE_PAGE_BREAK_FROM_BASE_AREA ? $this->renderPagesBase(
      $type_id,
      $sport_id,
      $page,
      $date
    ) : $this->renderPagesDefault($type_id, $sport_id, $page, $date);

    return $pages;
  }

  function renderPagesDefault($type_id, $sport_id, $page, $date)
  {
    //кол-во столбцов на страницу
    $perpage          = defined('ADMIN_COUNT_COURT_ON_PAGE') ? ADMIN_COUNT_COURT_ON_PAGE : 6;
    $areas_navigation = null;
    $this->engine->areas->getAreasTitlesByType($type_id, $areas, $sport_id);
    $areas_count = count($areas);
    if ($areas_count > $perpage) {
      //навигация нужна

      //определяем номер текущей странички
      if ($page < 1 || $page > ceil($areas_count / $perpage)) {
        $page = 1;
      }

      //переменная для renderAreasTypeColumns
      $limit = [($page - 1) * $perpage, $perpage];

      $href             = '';
      $cnt              = 1;
      $areas_navigation = '';
      foreach ($areas as $i => $area) {
        $href .= $area['title'] . ', ';
        if ($cnt % $perpage == 0 || $i == $areas_count - 1) {
          $page_current = ceil($cnt / $perpage);
          if ($page == $page_current) {
            $areas_navigation .= '<b>' . substr($href, 0, -2) . '</b> | ';
          } else {
            $areas_navigation .= '<a href="reservations.php?action=showReservations&type_id=' . $type_id . '&sport_id=' . $sport_id . '&date=' . $date . '&page=' . $page_current . '">' . substr(
                $href,
                0,
                -2
              ) . '</a> | ';
          }
          $href = '';
        }
        $cnt++;
      }
      $areas_navigation = substr($areas_navigation, 0, -2);
    } else {
      $page  = 1;
      $limit = null;
    }

    return [$page, $limit, $areas_navigation];
  }

  function renderPagesBase($type_id, $sport_id, $page, $date)
  {
    $pageData = $this->engine->areas->resolveAreasPage((int)$type_id, (int)$sport_id, (int)$page);
    $page     = $pageData['page'];
    $limit    = $pageData['limit'];
    $pages    = $pageData['pages'];

    $areas_navigation = '';
    foreach ($pages as $_page => $areas) {
      $href = '';
      foreach ($areas as $i => $area) {
        $href .= $area['title'] . ', ';
      }
      if ($page == $_page) {
        $areas_navigation .= '<b>' . substr($href, 0, -2) . '</b> | ';
      } else {
        $areas_navigation .= '<a href="reservations.php?action=showReservations&type_id=' . $type_id . '&sport_id=' . $sport_id . '&date=' . $date . '&page=' . $_page . '">' . substr(
            $href,
            0,
            -2
          ) . '</a> | ';
      }
    }
    $areas_navigation = substr($areas_navigation, 0, -2);

    return [$page, $limit, $areas_navigation];
  }

  /* ------------ Изменение заказа -------------*/
  function changeOrders()
  {
    $reservations         = Service::request()->_('reservations');
    $encash               = Service::request()->_('encash');
    $prepayment_sum       = NumberHelper::float(Service::request()->_('prepayment_sum'));
    $reservations_sum     = NumberHelper::float(Service::request()->_('reservations_sum'));
    $old_reservations_sum = NumberHelper::float(Service::request()->_('old_reservations_sum'));
    $client_id            = (int)Service::request()->_('client_id');

    $getSum = 0; // по умолчанию сумма которую снимем со счета гутхабен
    if (PaymentHelper::isPrivateAccount($encash) && $prepayment_sum < ($reservations_sum - $old_reservations_sum)) {
      // Если оплата будет по гутхабен и там не хватает денег то ошибка и пропускаем
      $this->error = $this->explainCheckAreaDateTimeAvailableError('p1');
    } elseif (is_array($reservations) && !empty($reservations)) {
      foreach ($reservations as $reservation_id => $price) {
        $price = NumberHelper::float($price);
        if ($this->engine->getReservationDataById($reservation_id, $reservation_data)) {
          $this->engine->changeReservationPrice($reservation_id, $price, ($encash !== null ? (int)$encash : false));

          $getSum += (PaymentHelper::isPrivateAccount($encash) ? $price : 0) - (PaymentHelper::isPrivateAccount($reservation_data['encash']) ? NumberHelper::float($reservation_data['price']) : 0);

          $reservations[$reservation_id] = [
            'from_price'  => $reservation_data['price'],
            'from_encash' => $reservation_data['encash'],
            'to_price'    => $price,
            'to_encash'   => $encash,
            'start'       => $reservation_data['start'],
            'finish'      => $reservation_data['finish'],
            'area_id'     => $reservation_data['area_id']
          ];
          $old_encash                    = (int)$reservation_data['encash'];
          // меняем статус резервирования, если оплата наличкой то требует подтверждения
          $this->engine->changePaymentState($reservation_id, (int)(!PaymentHelper::isCash($encash) /*&& !PaymentHelper::isCard($encash)*/));
        }
      }

      //снимаем или ложим деньги со счета
      if ($getSum !== 0) {
        $data = [
          'client_id'    => $client_id,
          'type_code'    => $old_encash != $encash ? 'change_payment_method' : 'change_amount',
          'amount'       => round(abs($getSum), 2),
          'related_data' => [
            'area_id'      => $reservations[array_key_first($reservations)]['area_id'],
            'date'         => date('d.m.Y', strtotime($reservations[array_key_first($reservations)]['start'])),
            'time'         => date('H:i', strtotime($reservations[array_key_first($reservations)]['start'])) . ' - '
              . date('H:i', strtotime($reservations[array_key_last($reservations)]['finish'])),
            'from_encash'  => PaymentHelper::getAliasPaymentMethod($old_encash),
            'to_encash'    => PaymentHelper::getAliasPaymentMethod($encash),
            'reservations' => $reservations,
          ],
          'related_id'   => null
        ];
        match ($getSum < 0) {
          true  => ModCommHelper::callSafe('clients', 'PrivateAccount/deposit', $data),
          false => ModCommHelper::callSafe('clients', 'PrivateAccount/withdraw', $data),
        };
      }
    }

    return $this->showReservations();
  }

  public function getMenu()
  {
    $_page = [];
    $areas = new AreasModel();
    foreach ($areas->relevantSportsByType() as $key => $area) {
      $_page['reservations_' . $key] = [
        'parent_key' => 'reservations',
        'template'   => 'internal',
        'title'      => $area->title_site_url,
        'href'       => 'reservations.php?action=showReservations&type_id=' . $area->type . '&sport_id=' . $area->sport . '&date=' . date(
            'Y-m-d'
          ),
        'access'     => 2,
        'key'        => 'reservations_' . $key
      ];
    }

    return $_page;
  }
}

$a                          = new reservations_admin;
$_page['content']           = $a->start();
$_page['key']               = $a->getPageKey();
$_page['key_r']             = $a->getPageKeyR();
$_page['js'][]              = ['maskedinput', 'common', false, 'cdn'];
$_page['js'][]              = ['jquery-ui/jquery-ui.min', 'third', true, 'cdn'];
$_page['js'][]              = ['jquery-ui/i18n/datepicker-' . config('lang')->getCurrentLang(), 'third', true, 'cdn'];
$_page['css'][]             = ['jquery-ui/jquery-ui.min', 'third', true, 'cdn'];
$_page['js'][]              = ['clients', 'admin', false, 'cdn'];
$_page['js'][]              = ['ajaxloadmodule', 'admin', false, 'cdn'];
$_page['js'][]              = ['popup', 'admin', false, 'cdn'];
$_page['js'][]              = ['select2.min', 'third/select2', false, 'cdn'];
$_page['js'][]              = ['i18n/' . config('lang')->getCurrentLang(), 'third/select2', false, 'cdn'];
$_page['css'][]             = ['select2.min', 'third/select2', false, 'cdn'];
$_page['reservations_menu'] = $a->getMenu();
