<?php

use AC\app\helpers\LayoutHelper;
use AC\app\services\DataService;
use AC\core\engines\Engines;
use AC\core\modules\areas\entities\dto\AreaDto;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\helpers\TranslateHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;


useClass('core\helpers\ColorsHelper');


class blocks_admin
{
  /**
   * @var Engines
   */
  public    $r;
  public    $page_key;
  protected $error = '', $insert_error = '', $message = '';

  function start()
  {
    $this->r = new Engines();
    $action  = Service::request()->_('action');
    $mode    = Service::request()->_('mode', null);

    if ($mode !== null) {
      $action = 'view' . ucfirst($mode);
    }

    $out = match ($action) {
      'insertBlock'           => $this->insertBlock(),
      'removeBlock'           => $this->removeBlock(),
      'editBlock'             => $this->editBlock(),
      'changeBlock'           => $this->changeBlock(),
      //периодические блокировки
      'insertPeriodicalBlock' => $this->insertPeriodicalBlock(),
      'removePeriodicalBlock' => $this->removePeriodicalBlock(),
      'editPeriodicalBlock'   => $this->editPeriodicalBlock(),
      'changePeriodicalBlock' => $this->changePeriodicalBlock(),
      'viewPeriodical'        => $this->viewPeriodical(),
      //бесконечные блокировки
      'insertUnlimitedBlock'  => $this->insertUnlimitedBlock(),
      'removeUnlimitedBlock'  => $this->removeUnlimitedBlock(),
      'editUnlimitedBlock'    => $this->editUnlimitedBlock(),
      'changeUnlimitedBlock'  => $this->changeUnlimitedBlock(),
      'viewUnlimited'         => $this->viewUnlimited(),
      default                 => $this->getBlocksList(),
    };
    if (Service::request()->check('use_webIo')) {
      $this->r->webIo->sendFTPCurrentIcal($this->r);
    }
    return $out;
  }

  function getPageKey()
  {
    return $this->page_key;
  }


  /* ОБЫЧНЫЕ БЛОКИРОВКИ */

  //обычные блокировки
  function getBlocksList()
  {
    $area_id      = Service::request()->_('area', null);
    $active       = Service::request()->_('active', $area_id !== null ? null : 1);
    $periods_list = '';
    $this->r->blocks->getBlocksAreas($blockAreas);
    $areas = DataService::areas();
    if (isset ($this->message)) {
      $periods_list .= '<span class="message">' . $this->message . '</span>';
    }
    if (!empty($this->error)) {
      $periods_list .= '<span class="error">' . $this->error . '</span>';
    }

    $periods_list .= '<h1>' . lang('blocks__title', 'structure') . '</h1>';
    $periods_list .= "<div><big class=\"blue\"><b>" . lang('All seats', 'blocks') . ":</b></big> ";
    $periods_list .= $active !== null && $active == 1 ? "<b>" . lang('title_h1', 'blocks') . "</b> | " : "<a href=\"blocks.php\">" . lang('title_h1',
        'blocks') . "</a> | ";
    $periods_list .= $active !== null && $active == 0 ? "<b>" . lang('Expired blocks', 'blocks') . "</b>"
      : "<a href=\"blocks.php?active=0\">" . lang('Expired blocks', 'blocks') . "</a>";
    $periods_list .= "</div>";
    $mode_postfix = $active != null ? '&active=' . $active : '';

    //навигация по площадкам
    $last_type_id  = null;
    $last_sport_id = null;

    if (!empty($blockAreas)) {
      foreach ($blockAreas as $area) {
        if (key_exists($area['area_id'], $areas)) {
          if ($last_type_id != $area['type_id']) {
            if ($last_type_id !== null) {
              $periods_list = substr($periods_list, 0, -3);
            }
            $last_sport_id = null;
            $periods_list  .= "<br>\n<span class=\"blue\"><b>" . $area['type_title'] . '</b></span> ';
          }
          if ($last_sport_id != $area['sport_id']) {
            if ($last_sport_id !== null) {
              $periods_list = substr($periods_list, 0, -3);
            }
            $periods_list .= "<br>\n&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class=\"blue\">"
              . LayoutHelper::renderSquareByTypeSport($area['type_id'], $area['sport_id']) . ' <b>' . $area['sport_title'] . ' : </b></span>';
          }

          if ($active == 0 && $area['area_id'] == $area_id) {
            $periods_list .= '<b>' . $area['area_title'] . ' (' . $area['cnt'] . ')</b> | ';
          } else {
            $periods_list .= "<a href=\"blocks.php?area=" . $area['area_id'] . "\">" . $area['area_title'] . ' (' . $area['cnt'] . ')</a> | ';
          }
          $last_type_id  = $area['type_id'];
          $last_sport_id = $area['sport_id'];
        }
      }
      $periods_list = substr($periods_list, 0, -3);
    }
    if ($active !== null && $active == 0) {
      $periods_list .= '<form action="blocks.php" method="POST" id="blocks_list">' . "\n";
    }

    $periods_list .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide" style="margin-top: 10px">' . "\n";
    $periods_list .= '<tr>';
    if ($active !== null && $active == 0) {
      $periods_list .= '<th rowspan="2"><input type="checkbox" name="all_select" value="0" onchange="selectAll(this, \'blocks_list\')"/>' . lang('To mark') . '</th>';
    }

    $periods_list .= '<th rowspan="2">' . lang('Title') . '</th><th colspan="2">' . lang('Date') . '</th><th rowspan="2" colspan="2">' . lang('Playground') . '</th><th rowspan="2" colspan="3">' . lang('Action') . '</th></tr><tr><th>' . lang('From') . '</th><th class="light">' . lang('Until') . '</th></tr>' . "\n";
    if ($this->r->blocks->getBlocksData($blocks, $active, $area_id)) {
      foreach ($blocks as $block) {
        if ($area = $areas[$block['area_id']]) {
          $periods_list .= '<tr>';
          if ($active !== null && $active == 0) {
            $periods_list .= '<td class="light" align="center" valign="top"><input type="checkbox" name="blocks[]" value="' . $block['block_id'] . '"/></td>';
          }
          $periods_list .= '<td class="dark">' . $block['reason'] . '</td>' .
            '<td class="light" align="center">' . date('<b>d.m.Y</b> H:i', strtotime($block['start'])) . '</td>' .
            '<td class="dark" align="center">' . date('<b>d.m.Y</b> H:i', strtotime($block['finish'])) . '</td>' .
            '<td class="light">' . LayoutHelper::renderSquareByTypeSport($area->typeId, $area->sportId) . '</td>' .
            '<td class="dark">' . $this->r->areas->getTitleByAreaId($block['area_id']) . ' - ' . $area->title . '</td>';
          $periods_list .= '<td class="light">' . (isset($block['use_webIo']) && $block['use_webIo'] ? '<img src="' . base_url(paths()->getAssetsDir('images/icon/state/light_on.svg',
                'common')) . '" alt="" style="width: 15px"/> ' : '') . '</td>';
          $periods_list .= '<td class="light"><a href="blocks.php?action=editBlock&block_id=' . $block['block_id'] . '" class="btnEdit">' . lang('button_update') . '</a></td>';
          $periods_list .= '<td class="light"><a href="blocks.php?action=removeBlock&block_id=' . $block['block_id'] . '" onclick="return ifConfirm ()" class="btnRemove">' . lang('button_remove') . '</a></td>';
          $periods_list .= '</tr>' . "\n";
        }
      }
    }
    if ($active !== null && $active == 0) {
      $periods_list .= '<tr><td>  </td></tr>';
      $periods_list .= '<tr><td colspan="18" align="right">
							<input type="button" name="archives" value="' . lang('button_remove') . '" onClick="return buttonAction(\'blocks_list\', \'blocks.php?action=removeBlock' . $mode_postfix . '\')" class="button"/>
					</td></tr>' . "\n";
    }
    $periods_list .= '</table>' . "\n";
    if ($active !== null && $active == 0) {
      $periods_list .= '</form>' . "\n";
    }

    $this->page_key = 'blocks_';

    return [
      $this->getBlockNormalFormContent(0),
      $periods_list,

    ];
  }

  //добавить блок
  function insertBlock()
  {
    if (isset($_POST['area_id']) && is_array($_POST['area_id'])) {
      foreach ($_POST['area_id'] as $area_id) {
        if ($this->checkBlockInputData($start, $finish, true, 0)) {

          $this->getIntersectionsWhenMakingBlockWithOtherBlockers(
            $area_id,
            substr($start, 0, -6),
            substr($finish, 0, -6),
            $_POST['s_time'],
            $_POST['f_time']
          );
          if ($this->r->blocks->insertBlock(
            $area_id,
            $start,
            $finish,
            $_POST['reason'],
            isset($_POST['type_view']) ? $_POST['type_view'] : "0",
            (int)Service::request()->_('use_webIo', 0),
            $error_code,
            $block_id
          )) {
            $this->message = lang('The blocking added', 'message_success');
          } else {
            $codes    = [];
            $codes[1] = lang('Area not found', 'message_error');
            $codes[2] = lang('Time range invalid!', 'message_error');
            $codes[3] = lang('The time range overlaps with the one already entered', 'message_error');

            $this->insert_error = $codes[$error_code];
          }
        }
      }
    }

    return $this->getBlocksList();
  }

  //удалить блокировку
  function removeBlock()
  {
    $blocks = Service::request()->_('blocks', []);
    if (empty($blocks)) {
      $blocks[] = Service::request()->_('block_id');
    }
    $blocks_data = $this->r->blocks->getBlocksDataByBlockIds($blocks);
    foreach ($blocks_data as $block) {
      $this->r->blocks->addArchive($block);
      $this->r->blocks->removeBlockById($block['block_id']);
      $this->message = lang('The blocking deleted', 'message_success');
    }

    return $this->getBlocksList();
  }

  //редактировать блокировку
  function editBlock()
  {
    $block_id = (isset ($_GET['block_id']) ? (int)$_GET['block_id'] : (int)$_POST['block_id']);

    $tb = getThemeBuilder();
    $this->r->blocks->getBlockData($block_id, $row);
    $out = '';

    $out .= $this->getBlockNormalFormContent(1, $row);
    $out .= $tb->back('blocks.php');

    $this->page_key = 'blocks_';

    return [$out];
  }

  //изменить блокировку
  function changeBlock()
  {
    if (isset ($_POST['block_id']) && isset ($_POST['block_id']) && $this->checkBlockInputData(
        $start,
        $finish,
        false,
        0
      )) {
      if ($this->r->blocks->changeBlock(
        (int)$_POST['block_id'],
        $start,
        $finish,
        $_POST['reason'],
        isset($_POST['type_view']) ? $_POST['type_view'] : "0",
        (int)Service::request()->_('use_webIo', 0),
        $error_code
      )) {
        $this->message = lang('The blocking changes', 'message_success');

        return $this->getBlocksList();
      } else {
        if ($error_code == 4) {
          //фатальная ошибка (блокировка не найдена), на список блокировок
          return $this->getBlocksList();
        } else {
          //нефатальаня ошибка, на форму редактирования
          $codes              = [];
          $codes[2]           = lang('Time range invalid!', 'message_error');
          $codes[3]           = lang('The time range overlaps with the one already entered', 'message_error');
          $this->insert_error = $codes[$error_code];

          return $this->editBlock();
        }
      }
    }
  }

  //форма ввода обычной блокировки
  function getBlockNormalFormContent($mode, $row = [])
  {
    $tb            = getThemeBuilder();
    $insert_period = '';

    //форма добавления полного блокирования на промежуток
    if (isset ($this->insert_error)) {
      $insert_period .= '<span class="error">' . $this->insert_error . '</span>';
    }

    //подготовить массивы для select'ов
    $this->prepareSelectsArrays();

    //значения
    if ($mode == 0) {
      //добавить
      if (isset ($_COOKIE['block_normal_last'])) {
        $row = unserialize($_COOKIE['block_normal_last'], ['allowed_classes' => false]);
      } else {
        $row['s_year']  = date('Y');
        $row['s_month'] = date('m');
        $row['s_day']   = date('d');

        $row['f_year']  = date('Y');
        $row['f_month'] = date('m');
        $row['f_day']   = date('d');

        $row['s_time'] = '00:00';
        $row['f_time'] = '00:00';
      }

      $action = 'insertBlock';
    } else {
      //изменить
      $row['s_year']  = substr($row['start'], 0, 4);
      $row['s_month'] = substr($row['start'], 5, 2);
      $row['s_day']   = substr($row['start'], 8, 2);

      $row['f_year']  = substr($row['finish'], 0, 4);
      $row['f_month'] = substr($row['finish'], 5, 2);
      $row['f_day']   = substr($row['finish'], 8, 2);

      $row['s_time'] = substr($row['start'], 11, 5);
      $row['f_time'] = substr($row['finish'], 11, 5);

      $action = 'changeBlock';
    }

    $insert_period .= '<form method="post" action="blocks.php?action=' . $action . '" name="insertBlockPeriod">' . "\n";
    if ($mode == 1) {
      $insert_period .= '<input type="hidden" name="block_id" value="' . $row['block_id'] . '">' . "\n";
    }

    $tbl = '<tr><th colspan="2">' . lang('Put out of operation', 'blocks') . '</th></tr>' . "\n";

    ob_start();
    if ($mode == 0) {
      //площадку выбирать только при добавлении
      ?>
      <tr>
        <td class="dark"><?= lang('Playground') ?>:</td>
        <td class="dark"><?= $tb->select('area_id[]', $this->select_areas, false, true); ?></td>
      </tr>
      <?
    } else {
      //получаем название площадки
      $this->r->areas->getAreaData($row['area_id'], $area_data);
      ?>
      <tr>
        <td class="dark"><?= lang('Playground') ?>:</td>
        <td class="dark"><?= $this->r->areas->getTitleByAreaId($row['area_id']) . ' - ' . $area_data['title']; ?></td>
      </tr>
      <?
    }
    ?>
    <tr>
      <td colspan="2" class="light">
        <table border="0" cellspacing="3" cellpadding="0">
          <tr>
            <td><?= lang('From') ?>:</td>
            <td><?= $tb->select('s_year', $this->select_years, $row['s_year']); ?></td>
            <td>.</td>
            <td><?= $tb->select('s_month', $this->select_monthes, $row['s_month']); ?></td>
            <td>.</td>
            <td><?= $tb->select('s_day', $this->select_days, $row['s_day']); ?></td>
            <td>&nbsp;</td>
            <td><?= $tb->select('s_time', $this->select_times, $row['s_time']); ?></td>
            <td rowspan="2"><a href="javascript:void null"
                               onclick="blocks_copyPeriodDate (document.forms['insertBlockPeriod']);blocks_copyPeriodTime (document.forms['insertBlockPeriod'])"><img
                  src="<?= base_url(paths()->getAssetsDir('images/copy.gif')) ?>" alt="Kopieren" border="0"></a></td>
          </tr>
          <tr>
            <td><?= lang('Until') ?>:</td>
            <td><?= $tb->select('f_year', $this->select_years, $row['f_year']); ?></td>
            <td>.</td>
            <td><?= $tb->select('f_month', $this->select_monthes, $row['f_month']); ?></td>
            <td>.</td>
            <td><?= $tb->select('f_day', $this->select_days, $row['f_day']); ?></td>
            <td>&nbsp;</td>
            <td><?= $tb->select('f_time', $this->select_times, $row['f_time']); ?></td>
          </tr>

        </table>
      </td>
    </tr>
    <tr>
      <td class="dark"><?= lang('Title') ?>:</td>
      <td class="dark"><input type="text" name="reason" maxlength="255" class="input small"
                              value="<?= isset($row['reason']) ? $row['reason'] : '' ?>"></td>
    </tr>
    <tr>
      <td colspan="2" class="dark" style="padding-left: 85px">
        <input type="checkbox" name="use_webIo" class="input" value="1" <?= isset($row['use_webIo']) && $row['use_webIo'] ? 'checked' : '' ?>>
        <?= lang('label_use_webIo', 'blocks') ?>
      </td>
    </tr>
    <input type="hidden" name="type_view" value="0">
    <!--    <tr>-->
    <!--      <td class="dark">View:</td>-->
    <!--      <td class="dark">-->
    <!--        <input type="radio" name="type_view" class="input"-->
    <!--               value="0" --><?//= (isset($row['type_view']) ? ($row['type_view'] == 0 ? 'checked' : '') : 'checked')
    ?>
    <!--               id="type_view_0"/> <label for="type_view_0">Nein</label><br/>-->
    <!--        <input type="radio" name="type_view" class="input"-->
    <!--               value="1" --><?//= ((isset($row['type_view']) && $row['type_view'] == 1) ? 'checked' : '')
    ?>
    <!--               id="type_view_1"/> <label for="type_view_1">Regen</label><br/>-->
    <!--        <input type="radio" name="type_view" class="input"-->
    <!--               value="2" --><?//= ((isset($row['type_view']) && $row['type_view'] == 2) ? 'checked' : '')
    ?>
    <!--               id="type_view_2"/> <label for="type_view_2">Bewässerung</label>-->
    <!---->
    <!--      </td>-->
    <!--    </tr>-->

    <tr>
      <th align="center" colspan="2"><input type=submit class="button" value="<?= lang('Block', 'blocks') ?>">&nbsp;<input type="reset"
                                                                                                                           class="button"
                                                                                                                           value="<?= lang('button_reset') ?>">
      </th>
    </tr>
    <?
    $tbl .= ob_get_contents();
    ob_end_clean();

    $insert_period .= $tb->table($tbl, false);
    $insert_period .= "</form>\n";

    return $insert_period;
  }


  /* ПЕРИОДИЧЕСКИЕ БЛОКИРОВКИ */

  //периодические блокировки
  public function viewPeriodical()
  {
    $area_id  = Service::request()->_('area', null);
    $active   = Service::request()->_('active', $area_id !== null ? null : 1);
    $periodic = '';
    $this->r->blocks->getPeriodicalBlocksAreas($blockAreas);
    $areas = DataService::areas();
    if (isset ($this->message)) {
      $periodic .= '<span class="message">' . $this->message . '</span>';
    }

    $periodic     .= '<h1>' . lang('blocks_periodical_title', 'structure') . '</h1>';
    $periodic     .= "<div><big class=\"blue\"><b>" . lang('All seats', 'blocks') . ":</b></big> ";
    $periodic     .= $active !== null && $active == 1 ? "<b>" . lang(
        'blocks_periodical_title',
        'structure'
      ) . "</b> | " : "<a href=\"blocks.php?mode=periodical\">" . lang('blocks_periodical_title', 'structure') . "</a> | ";
    $periodic     .= $active !== null && $active == 0 ? "<b>" . lang('Expired', 'blocks') . ' ' . lang(
        'blocks_periodical_title',
        'structure'
      ) . "</b>" : "<a href=\"blocks.php?mode=periodical&active=0\">" . lang('Expired', 'blocks') . ' ' . lang('blocks_periodical_title',
        'structure') . "</a>";
    $periodic     .= "</div>";
    $mode_postfix = $active != null ? '&active=' . $active : '';

    //навигация по площадкам
    $last_type_id  = null;
    $last_sport_id = null;
    if (!empty($blockAreas)) {
      foreach ($blockAreas as $area) {
        if (key_exists($area['area_id'], $areas)) {
          if ($last_type_id != $area['type_id']) {
            if ($last_type_id !== null) {
              $periodic = substr($periodic, 0, -3);
            }
            $last_sport_id = null;
            $periodic      .= "<br>\n<span class=\"blue\"><b>" . $area['type_title'] . '</b></span> ';
          }
          if ($last_sport_id != $area['sport_id']) {
            if ($last_sport_id !== null) {
              $periodic = substr($periodic, 0, -3);
            }
            $periodic .= "<br>\n&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class=\"blue\">" . LayoutHelper::renderSquareByTypeSport($area['type_id'],
                $area['sport_id']) . ' <b>' . $area['sport_title'] . ' : </b></span> ';
          }
          if ($active == 0 && $area['area_id'] == $area_id) {
            $periodic .= '<b>' . $area['area_title'] . ' (' . $area['cnt'] . ')</b> | ';
          } else {
            $periodic .= "<a href=\"blocks.php?mode=periodical&area=" . $area['area_id'] . "\">" . $area['area_title'] . ' (' . $area['cnt'] . ')</a> | ';
          }
          $last_type_id  = $area['type_id'];
          $last_sport_id = $area['sport_id'];
        }
      }
      $periodic = substr($periodic, 0, -3);
    }
    if ($active !== null && $active == 0) {
      $periodic .= '<form action="blocks.php?mode=periodical" method="POST" id="blocks_list">' . "\n";
    }

    $periodic .= '<br><table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
    $periodic .= '<tr>';
    if ($active !== null && $active == 0) {
      $periodic .= '<th rowspan="2"><input type="checkbox" name="all_select" value="0" onchange="selectAll(this, \'blocks_list\')"/>' . lang('To mark') . '</th>';
    }
    $periodic .= '<th rowspan="2">' . lang('Title') . '</th><th colspan="2">' . lang('Date') . '</th><th rowspan="2" colspan="2">' . lang('Playground') . '</th><th rowspan="2">' . lang('Time in hours') . '</th>';
    $periodic .= '<th rowspan="2">' . TranslateHelper::translateWeekday(0, true) . '</th>';
    $periodic .= '<th rowspan="2">' . TranslateHelper::translateWeekday(1, true) . '</th>';
    $periodic .= '<th rowspan="2">' . TranslateHelper::translateWeekday(2, true) . '</th>';
    $periodic .= '<th rowspan="2">' . TranslateHelper::translateWeekday(3, true) . '</th>';
    $periodic .= '<th rowspan="2">' . TranslateHelper::translateWeekday(4, true) . '</th>';
    $periodic .= '<th rowspan="2">' . TranslateHelper::translateWeekday(5, true) . '</th>';
    $periodic .= '<th rowspan="2">' . TranslateHelper::translateWeekday(6, true) . '</th>';
    $periodic .= '<th rowspan="2" colspan="3">' . lang('Action') . '</th></tr><tr><th>' . lang('From') . '</th><th class="light">' . lang('Until') . '</th></tr>' . "\n";
    if ($this->r->blocks->getPeriodicalBlocksData($blocks, $active, $area_id)) {
      foreach ($blocks as $block) {
        if ($area = $areas[$block['area_id']]) {
          $periodic .= '<tr>';
          if ($active !== null && $active == 0) {
            $periodic .= '<td class="light" align="center" valign="top"><input type="checkbox" name="blocks[]" value="' . $block['block_id'] . '"/></td>';
          }
          $periodic .= '<td class="dark">' . $block['reason'] . ' </td>' .
            '<td class="light" align="center">' . date('<b>d.m.Y</b>', strtotime($block['date_start'])) . '</td>' .
            '<td class="dark" align="center">' . date('<b>d.m.Y</b>', strtotime($block['date_finish'])) . '</td>' .
            '<td class="light">' . LayoutHelper::renderSquareByTypeSport($area->typeId, $area->sportId) . '</td>' .
            '<td class="dark">' . $this->r->areas->getTitleByAreaId($block['area_id']) . ' - ' . $area->title . ' </td>' .
            '<td class="light">' . date('H:i', strtotime($block['time_start'])) . ' - ' . date(
              'H:i',
              strtotime($block['time_finish'])
            ) . '</td>';

          //дни недели
          for ($j = 0; $j < 7; $j++) {
            $periodic .= '<td class="' . ($j % 2 == 0 ? 'dark' : 'light') . '"' . ($block['weekdays'][$j] == 1 ? ' align="center">x' : '>') . '</td>';
          }
          /* @todo исправить путь к файлу изображения */
          $periodic .= '<td class="light">' . (isset($block['use_webIo']) && $block['use_webIo'] ? '<img src="' . base_url(
                paths()->getAssetsDir('images/icon/state/light_on.svg', 'common')
              ) . '" alt="" style="width: 15px"/> ' : '') . '</td>';
          $periodic .= '<td class="light"><a href="blocks.php?action=editPeriodicalBlock&block_id=' . $block['block_id'] . '" class="btnEdit">' . lang('button_update') . '</a></td>';
          $periodic .= '<td class="light"><a href="blocks.php?action=removePeriodicalBlock&block_id=' . $block['block_id'] . '" onclick="return ifConfirm ()" class="btnRemove">' . lang('button_remove') . '</a></td>';
          $periodic .= '</tr>' . "\n";
        }
      }
    }
    if ($active !== null && $active == 0) {
      $periodic .= '<tr><td>  </td></tr>';
      $periodic .= '<tr><td colspan="18" align="right">
							<input type="button" name="archives" value="' . lang('button_remove') . '" onClick="return buttonAction(\'blocks_list\', \'blocks.php?action=removePeriodicalBlock' . $mode_postfix . '\')" class="button"/>
					</td></tr>' . "\n";
    }
    $periodic .= '</table>' . "\n";
    if ($active !== null && $active == 0) {
      $periodic .= '</form>' . "\n";
    }

    $this->page_key = 'blocks_periodical';

    return [
      $this->getPeriodicalBlockFormContent(0),
      $periodic,

    ];
  }


  //добавить периодическую блокировку
  function insertPeriodicalBlock()
  {
    if (isset($_POST['area_id']) && is_array($_POST['area_id'])) {
      foreach ($_POST['area_id'] as $area_id) {
        if ($this->checkBlockInputData($date_start, $date_finish, true, 1) && !empty ($_POST['weekdays'])) {
          if (is_array($_POST['weekdays'])) {
            $weekdays = $_POST['weekdays'];
          } else {
            $weekdays = [];
          }
          $this->getIntersectionsWhenMakingBlockWithOtherBlockers(
            $area_id,
            $date_start,
            $date_finish,
            $_POST['s_time'],
            $_POST['f_time'],
            $weekdays
          );

          $this->r->blocks->insertPeriodicalBlock(
            $area_id,
            $date_start,
            $date_finish,
            $_POST['s_time'],
            $_POST['f_time'],
            $weekdays,
            $_POST['reason'],
            $block_id,
            (int)Service::request()->_('use_webIo', 0)
          );
        }
      }
    }

    return $this->viewPeriodical();
  }

  //удалить периодическую блокировку
  function removePeriodicalBlock()
  {
    $blocks = Service::request()->_('blocks', []);
    if (empty($blocks)) {
      $blocks[] = Service::request()->_('block_id');
    }
    $blocks_data = $this->r->blocks->getBlocksDataByBlockIds($blocks, '_periodical');
    foreach ($blocks_data as $block) {
      $this->r->blocks->addArchive($block, 'periodical');
      $this->r->blocks->removePeriodicalBlockById($block['block_id']);
      $this->message = lang('The blocking deleted', 'message_success');
    }


    return $this->viewPeriodical();
  }

  //редактировть блокировку
  function editPeriodicalBlock()
  {
    $tb = getThemeBuilder();
    $this->r->blocks->getPeriodicalBlockData($_GET['block_id'], $row);
    $out = $this->getPeriodicalBlockFormContent(1, $row);
    $out .= $tb->back('blocks.php?action=viewPeriodical');

    $this->page_key = 'blocks_periodical';

    return [$out];
  }

  //изменить блокировку
  function changePeriodicalBlock()
  {
    $date_start  = "$_POST[s_year]-$_POST[s_month]-$_POST[s_day]";
    $date_finish = "$_POST[f_year]-$_POST[f_month]-$_POST[f_day]";

    $weekdays = is_array($_POST['weekdays']) ? $_POST['weekdays'] : [];

    $this->r->blocks->changePeriodicalBlock(
      $_POST['block_id'],
      $date_start,
      $date_finish,
      $_POST['s_time'],
      $_POST['f_time'],
      $weekdays,
      $_POST['reason'],
      (int)Service::request()->_('use_webIo', 0)
    );

    return $this->viewPeriodical();
  }

  //форма ввода периодической блокировки
  function getPeriodicalBlockFormContent($mode, $row = [])
  {
    $tb              = getThemeBuilder();
    $insert_periodic = '';

    //форма добавления периодческого блокирования в заданное время суток на заданный промежуток
    if (isset ($this->insert_error)) {
      $insert_periodic .= '<span class="error">' . $this->insert_error . '</span>';
    }

    //подготовить массивы
    $this->prepareSelectsArrays();

    //значения
    if ($mode == 0) {
      //добавить
      if (isset ($_COOKIE['block_periodical_last'])) {
        $row = unserialize($_COOKIE['block_periodical_last'], ['allowed_classes' => false]);
      } else {
        $row['s_time'] = $f_time = '00:00';

        $row['s_year']  = date('Y');
        $row['s_month'] = date('m');
        $row['s_day']   = date('d');

        $row['f_year']  = date('Y');
        $row['f_month'] = date('m');
        $row['f_day']   = date('d');
      }

      $action = 'insertPeriodicalBlock';
    } else {
      //изменить
      $row['s_time'] = substr($row['time_start'], 0, 5);
      $row['f_time'] = substr($row['time_finish'], 0, 5);

      $row['s_year']  = substr($row['date_start'], 0, 4);
      $row['s_month'] = substr($row['date_start'], 5, 2);
      $row['s_day']   = substr($row['date_start'], 8, 2);

      $row['f_year']  = substr($row['date_finish'], 0, 4);
      $row['f_month'] = substr($row['date_finish'], 5, 2);
      $row['f_day']   = substr($row['date_finish'], 8, 2);

      $action = 'changePeriodicalBlock';
    }

    $insert_periodic .= '<form method="post" action="blocks.php?action=' . $action . '" name="insertPeriodicalBlock">' . "\n";
    if ($mode == 1) {
      $insert_periodic .= '<input type="hidden" name="block_id" value="' . $row['block_id'] . '">' . "\n";
    }
    $tbl = '<tr><th colspan="2">' . lang('Put out of operation', 'blocks') . '</th></tr>' . "\n";
    ob_start();

    if ($mode == 0) {
      //площадку выбирать только при добавлении
      ?>
      <tr>
        <td class="dark"><?= lang('Playground') ?>:</td>
        <td class="dark"><?= $tb->select('area_id[]', $this->select_areas, false, true); ?> </td>
      </tr>
      <?
    } else {
      //получаем название площадки
      $this->r->areas->getAreaData($row['area_id'], $area_data);
      ?>
      <tr>
        <td class="dark"><?= lang('Playground') ?>:</td>
        <td class="dark"><?= $this->r->areas->getTitleByAreaId(
            $area_data['area_id']
          ) . ' - ' . $area_data['title']; ?></td>
      </tr>
      <?
    }
    ?>
    <tr>
      <td class="light"><?= lang('Weekdays') ?>:</td>
      <td class="light">
        <table>
          <?
          //дни недели
          $weekdays = [];
          for ($i = 0; $i <= 6; $i++) {
            if ($mode == 0) {
              $checked = ' checked';
            } elseif ($mode == 1 && $row['weekdays'][$i] == 1) {
              $checked = ' checked';
            } else {
              $checked = '';
            }

            echo '				<tr><td>' . TranslateHelper::translateWeekday(
                $i
              ) . '</td><td><input type="checkbox" name="weekdays[' . $i . ']" value="1"' . $checked . '></td></tr>' . "\n";
          }
          ?>
        </table>
      </td>
    </tr>
    <tr>
      <td class="dark"><?= lang('Time') ?>:</td>
      <td class="dark">
        <table border="0" cellspacing="3" cellpadding="0">
          <tr>
            <td><?= lang('Form') ?>:</td>
            <td><?= $tb->select('s_time', $this->select_times, $row['s_time']); ?></td>
            <td rowspan="2"><a href="javascript:void null"
                               onclick="var a = document.forms['insertPeriodicalBlock'];a.f_time.value = a.s_time.value"><img
                  src="<?= base_url(paths()->getAssetsDir('images/copy.gif')) ?>" alt="Kopieren" border="0"></a></td>
          </tr>
          <tr>
            <td><?= lang('Until') ?>:</td>
            <td><?= $tb->select('f_time', $this->select_times, (isset($row['f_time']) ? $row['f_time'] : '')); ?></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td class="light"><?= lang('Date') ?>:</td>
      <td class="light">
        <table border="0" cellspacing="3" cellpadding="0">
          <tr>
            <td><?= lang('From') ?>:</td>
            <td><?= $tb->select('s_year', $this->select_years, $row['s_year']); ?></td>
            <td>.</td>
            <td><?= $tb->select('s_month', $this->select_monthes, $row['s_month']); ?></td>
            <td>.</td>
            <td><?= $tb->select('s_day', $this->select_days, $row['s_day']); ?></td>
            <td rowspan="2"><a href="javascript:void null"
                               onclick="blocks_copyPeriodDate (document.forms['insertPeriodicalBlock'])"><img
                  src="<?= base_url(paths()->getAssetsDir('images/copy.gif')) ?>" alt="Kopieren" border="0"></a></td>
          </tr>
          <tr>
            <td><?= lang('Until') ?>:</td>
            <td><?= $tb->select('f_year', $this->select_years, $row['f_year']); ?></td>
            <td>.</td>
            <td><?= $tb->select('f_month', $this->select_monthes, $row['f_month']); ?></td>
            <td>.</td>
            <td><?= $tb->select('f_day', $this->select_days, $row['f_day']); ?></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td class="dark"><?= lang('Title') ?>:</td>
      <td class="dark"><input type="text" name="reason" maxlength="255" class="input small"
                              value="<?= (isset($row['reason']) ? $row['reason'] : '') ?>"></td>
    </tr>
    <tr>
      <td colspan="2" class="dark" style="padding-left: 85px">
        <input type="checkbox" name="use_webIo" class="input" value="1" <?= isset($row['use_webIo']) && $row['use_webIo'] ? 'checked' : '' ?>>
        <?= lang('label_use_webIo', 'blocks') ?>
      </td>
    </tr>
    <tr>
      <th align="center" colspan="2"><input type=submit class="button" value="<?= lang('Block', 'blocks') ?>">&nbsp;<input type="reset"
                                                                                                                           class="button"
                                                                                                                           value="<?= lang('button_reset') ?>">
      </th>
    </tr>
    <?
    $tbl .= ob_get_contents();
    ob_end_clean();

    $insert_periodic .= $tb->table($tbl, false);
    $insert_periodic .= "</form>\n";

    return $insert_periodic;
  }

  /* БЕСКОНЕЧНЫЕ БЛОКИРОВКИ */

  //бесконечные блокировки
  function viewUnlimited()
  {
    $unlimited = '';

    if (isset ($this->message)) {
      $unlimited .= '<span class="message">' . $this->message . '</span>';
    }

    $unlimited .= '<h1>' . lang('blocks_unlimited_title', 'structure') . '</h1>';

    $unlimited .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
    $unlimited .= '<tr><th rowspan="2">' . lang('Title') . '</th><th colspan="1">' . lang('Date') . '</th><th rowspan="2" colspan="2">' . lang('Playground') . '</th><th rowspan="2">' . lang('Time') . '</th>';
    $unlimited .= '<th rowspan="2">' . TranslateHelper::translateWeekday(0, true) . '</th>';
    $unlimited .= '<th rowspan="2">' . TranslateHelper::translateWeekday(1, true) . '</th>';
    $unlimited .= '<th rowspan="2">' . TranslateHelper::translateWeekday(2, true) . '</th>';
    $unlimited .= '<th rowspan="2">' . TranslateHelper::translateWeekday(3, true) . '</th>';
    $unlimited .= '<th rowspan="2">' . TranslateHelper::translateWeekday(4, true) . '</th>';
    $unlimited .= '<th rowspan="2">' . TranslateHelper::translateWeekday(5, true) . '</th>';
    $unlimited .= '<th rowspan="2">' . TranslateHelper::translateWeekday(6, true) . '</th>';
    $unlimited .= '<th rowspan="2" colspan="3">' . lang('Action') . '</th></tr><tr><th>' . lang('From') . '</th></tr>' . "\n";
    if ($this->r->blocks->getUnlimitedBlocksData($blocks)) {
      $areas = DataService::areas();
      foreach ($blocks as $block) {
        if ($area = $areas[$block['area_id']]) {
          $unlimited .= '<tr>' .
            '<td class="dark">' . $block['reason'] . '</td>' .
            '<td class="light" align="center">' . date('<b>d.m.Y</b>', strtotime($block['date_start'])) . '</td>' .
            '<td class="light">' . LayoutHelper::renderSquareByTypeSport($area->typeId, $area->sportId) . '</td>' .
            '<td class="dark">' . $area->title . '</td>' .
            '<td class="light">' . date('H:i', strtotime($block['time_start'])) . ' - ' . date(
              'H:i',
              strtotime($block['time_finish'])
            ) . '</td>';

          //дни недели
          for ($j = 0; $j < 7; $j++) {
            $unlimited .= '<td class="' . ($j % 2 == 0 ? 'dark' : 'light') . '"' . ($block['weekdays'][$j] == 1 ? ' align="center">x' : '>') . '</td>';
          }
          /** @todo тут тоже доработать путь к изображению */
          $unlimited .= '<td class="light">' . (isset($block['use_webIo']) && $block['use_webIo'] ? '<img src="' . base_url(
                paths()->getAssetsDir('images/icon/state/light_on.svg', 'common')
              ) . '" alt="" style="width: 15px"/> ' : '') . '</td>';
          $unlimited .= '<td class="light"><a href="blocks.php?action=editUnlimitedBlock&block_id=' . $block['block_id'] . '" class="btnEdit">' . lang('button_update') . '</a></td>';
          $unlimited .= '<td class="light"><a href="blocks.php?action=removeUnlimitedBlock&block_id=' . $block['block_id'] . '" onclick="return ifConfirm ()" class="btnRemove">' . lang('button_remove') . '</a></td>';
          $unlimited .= '</tr>' . "\n";
        }
      }
    }
    $unlimited .= '</table>' . "\n";

    $this->page_key = 'blocks_unlimited';

    return [
      $this->getUnlimitedBlockFormContent(0),
      $unlimited,

    ];
  }

  //форма ввода периодической блокировки
  function getUnlimitedBlockFormContent($mode, $row = [])
  {
    $tb               = getThemeBuilder();
    $insert_unlimited = '';

    //форма добавления периодческого блокирования в заданное время суток на заданный промежуток
    if (isset ($this->insert_error)) {
      $insert_unlimited .= '<span class="error">' . $this->insert_error . '</span>';
    }

    //подготовить массивы
    $this->prepareSelectsArrays();

    //значения
    if ($mode == 0) {
      //добавить
      if (isset ($_COOKIE['block_unlimited_last'])) {
        $row = unserialize($_COOKIE['block_unlimited_last'], ['allowed_classes' => false]);
      } else {
        $row['s_time'] = $f_time = '00:00';

        $row['s_year']  = date('Y');
        $row['s_month'] = date('m');
        $row['s_day']   = date('d');

//                $row['f_year']  = date('Y');
//                $row['f_month'] = date('m');
//                $row['f_day']   = date('d');
      }

      $action = 'insertUnlimitedBlock';
    } else {
      //изменить
      $row['s_time'] = substr($row['time_start'], 0, 5);
      $row['f_time'] = substr($row['time_finish'], 0, 5);

      $row['s_year']  = substr($row['date_start'], 0, 4);
      $row['s_month'] = substr($row['date_start'], 5, 2);
      $row['s_day']   = substr($row['date_start'], 8, 2);

//            $row['f_year']  = substr($row['date_finish'], 0, 4);
//            $row['f_month'] = substr($row['date_finish'], 5, 2);
//            $row['f_day']   = substr($row['date_finish'], 8, 2);

      $action = 'changeUnlimitedBlock';
    }

    $insert_unlimited .= '<form method="post" action="blocks.php?action=' . $action . '" name="insertUnlimitedBlock">' . "\n";
    if ($mode == 1) {
      $insert_unlimited .= '<input type="hidden" name="block_id" value="' . $row['block_id'] . '">' . "\n";
    }
    $tbl = '<tr><th colspan="2">' . lang('Put out of operation', 'blocks') . '</th></tr>' . "\n";
    ob_start();

    if ($mode == 0) {
      //площадку выбирать только при добавлении
      ?>
      <tr>
        <td class="dark"><?= lang('Playground') ?>:</td>
        <td class="dark"><?= $tb->select('area_id[]', $this->select_areas, false, true); ?></td>
      </tr>
      <?
    } else {
      //получаем название площадки
      $this->r->areas->getAreaData($row['area_id'], $area_data);
      ?>
      <tr>
        <td class="dark"><?= lang('Playground') ?>:</td>
        <td class="dark"><?= $this->r->areas->getTitleByAreaId(
            $area_data['area_id']
          ) . ' - ' . $area_data['title']; ?></td>
      </tr>
      <?
    }
    ?>
    <tr>
      <td class="light"><?= lang('Weekdays') ?>:</td>
      <td class="light">
        <table>
          <?
          //дни недели
          $weekdays = [];
          for ($i = 0; $i <= 6; $i++) {
            if ($mode == 0) {
              $checked = ' checked';
            } elseif ($mode == 1 && $row['weekdays'][$i] == 1) {
              $checked = ' checked';
            } else {
              $checked = '';
            }

            echo '				<tr><td>' . TranslateHelper::translateWeekday(
                $i
              ) . '</td><td><input type="checkbox" name="weekdays[' . $i . ']" value="1"' . $checked . '></td></tr>' . "\n";
          }
          ?>
        </table>
      </td>
    </tr>
    <tr>
      <td class="dark"><?= lang('Time') ?>:</td>
      <td class="dark">
        <table border="0" cellspacing="3" cellpadding="0">
          <tr>
            <td><?= lang('From') ?>:</td>
            <td><?= $tb->select('s_time', $this->select_times, $row['s_time']); ?></td>
            <td rowspan="2"><a href="javascript:void null"
                               onclick="var a = document.forms['insertUnlimitedBlock'];a.f_time.value = a.s_time.value"><img
                  src="<?= base_url(paths()->getAssetsDir('images/copy.gif')) ?>" alt="Kopieren" border="0"></a></td>
          </tr>
          <tr>
            <td><?= lang('Until') ?>:</td>
            <td><?= $tb->select('f_time', $this->select_times, (isset($row['f_time']) ? $row['f_time'] : '')); ?></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td class="light"><?= lang('Date') ?>:</td>
      <td class="light">
        <table border="0" cellspacing="3" cellpadding="0">
          <tr>
            <td><?= lang('From') ?>:</td>
            <td><?= $tb->select('s_year', $this->select_years, $row['s_year']); ?></td>
            <td>.</td>
            <td><?= $tb->select('s_month', $this->select_monthes, $row['s_month']); ?></td>
            <td>.</td>
            <td><?= $tb->select('s_day', $this->select_days, $row['s_day']); ?></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td class="dark"><?= lang('Title') ?>:</td>
      <td class="dark"><input type="text" name="reason" maxlength="255" class="input small"
                              value="<?= (isset($row['reason']) ? $row['reason'] : '') ?>"></td>
    </tr>
    <tr>
      <td colspan="2" class="dark" style="padding-left: 85px">
        <input type="checkbox" name="use_webIo" class="input" value="1" <?= isset($row['use_webIo']) && $row['use_webIo'] ? 'checked' : '' ?>>
        <?= lang('label_use_webIo', 'blocks') ?>
      </td>
    </tr>
    <tr>
      <th align="center" colspan="2"><input type=submit class="button" value="<?= lang('Block', 'blocks') ?>">&nbsp;<input type="reset"
                                                                                                                           class="button"
                                                                                                                           value="<?= lang('button_reset') ?>">
      </th>
    </tr>
    <?
    $tbl .= ob_get_contents();
    ob_end_clean();

    $insert_unlimited .= $tb->table($tbl, false);
    $insert_unlimited .= "</form>\n";

    return $insert_unlimited;
  }

  //добавить бесконечную блокировку
  function insertUnlimitedBlock()
  {
    if ($this->checkBlockInputData($date_start, $date_finish, true, 2) && !empty ($_POST['weekdays'])) {
      if (is_array($_POST['weekdays'])) {
        $weekdays = $_POST['weekdays'];
      } else {
        $weekdays = [];
      }
      foreach ($_POST['area_id'] as $area_id) {
        $this->getIntersectionsWhenMakingBlockWithOtherBlockers(
          $area_id,
          $date_start,
          $date_finish,
          $_POST['s_time'],
          $_POST['f_time'],
          $weekdays
        );
        $this->r->blocks->insertUnlimitedBlock(
          $area_id,
          $date_start,
          $_POST['s_time'],
          $_POST['f_time'],
          $weekdays,
          $_POST['reason'],
          $block_id,
          (int)Service::request()->_('use_webIo', 0)
        );
      }
    }

    return $this->viewUnlimited();
  }

  //удалить бесконечную блокировку
  function removeUnlimitedBlock()
  {
    $this->r->blocks->removeUnlimitedBlock($_GET['block_id']);

    return $this->viewUnlimited();
  }

  //редактировать бесконечную блокировку
  function editUnlimitedBlock()
  {
    $tb = getThemeBuilder();
    $this->r->blocks->getUnlimitedBlockData($_GET['block_id'], $row);
    $out = '';

    $out .= $this->getUnlimitedBlockFormContent(1, $row);
    $out .= $tb->back('blocks.php?action=viewUnlimited');

    $this->page_key = 'blocks_unlimited';

    return [$out];
  }

  //изменить блокировку
  function changeUnlimitedBlock()
  {
    $date_start = "$_POST[s_year]-$_POST[s_month]-$_POST[s_day]";

    $weekdays = is_array($_POST['weekdays']) ? $_POST['weekdays'] : [];

    $this->r->blocks->changeUnlimitedBlock(
      $_POST['block_id'],
      $date_start,
      $_POST['s_time'],
      $_POST['f_time'],
      $weekdays,
      $_POST['reason'],
      (int)Service::request()->_('use_webIo', 0)
    );

    return $this->viewUnlimited();
  }

  /* СЕРВИС */

  //проверка наличия (только наличия) входных данных
  //$mode - block mode
  //	0 normal
  //	1 periodical
  function checkBlockInputData(&$start, &$finish, $store_in_cookies, $mode)
  {
    $fields = [
      's_year',
      's_month',
      's_day',
      's_time',
      'f_time',
      'reason',
    ];
    //Если это не бесконечная блокировка
    if ($mode != 2) {
      $fields[] = 'f_year';
      $fields[] = 'f_month';
      $fields[] = 'f_day';
    }

    $store = [];

    foreach ($fields as $f) {
      if (!isset ($_POST[$f])) {
        return false;
      }
      if ($f != 'reason') {
        $store[$f] = $_POST[$f];
      }
    }

    $start = $_POST['s_year'] . '-' . $_POST['s_month'] . '-' . $_POST['s_day'] . ' ' . $_POST['s_time'];
    if ($mode != 2) {
      $finish = $_POST['f_year'] . '-' . $_POST['f_month'] . '-' . $_POST['f_day'] . ' ' . $_POST['f_time'];
    }


    //проверка даты на корректность
    if ($start != date('Y-m-d H:i', strtotime($start)) || ($mode != 2 && $finish != date(
          'Y-m-d H:i',
          strtotime($finish)
        ))) {
      return false;
    }

    if ($mode == 1 || $mode == 2) {
      //для периодических блокировок
      $start  = substr($start, 0, -6);
      $finish = substr($finish, 0, -6);
    }

    if ($store_in_cookies) {
      $val = 'block_' . ($mode == 0 ? 'normal' : 'periodical') . '_last';
      //сохраняем в кукисы
      $_COOKIE[$val] = serialize($store);
      setcookie($val, serialize($store), time() + 864000);//expire in 10 days
    }

    return true;
  }

  //подготовить массивы для select'ов - для форм ввода
  function prepareSelectsArrays()
  {
    if (!isset ($this->select_years)) {
      //площадки
      $this->r->areas->getAllAreasData($areas_data);
      $this->select_areas = [];
      foreach ($areas_data as $area_data) {
        $this->select_areas[$area_data['area_id']] = $this->r->areas->getTitleByAreaId(
            $area_data['area_id']
          ) . ' - ' . $area_data['title'];
      }

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

      //время
      $this->select_times = [];
      for ($i = 0; $i < 1440; $i = $i + 15) {
        $this->select_times[TimeHelper::convertMinutes2MySQLTime($i)] = TimeHelper::convertMinutes2MySQLTime($i);
      }
    }
  }

  protected function getIntersectionsWhenMakingBlockWithOtherBlockers(
    $area_id,
    $date_start,
    $date_finish,
    $time_start,
    $time_finish,
    $weekdays = [],
    $type_message = 'error'
  ) {
    $data = $this->r->obtainIntersectionWithAllSourcesForInterval(
      $area_id,
      $date_start,
      $date_finish,
      $time_start,
      $time_finish,
      $weekdays
    );
    if (!empty($data)) {
      $this->{$type_message} .= '<br>' . implode('<br>', $data);
    }
  }

}

$a                = new blocks_admin;
$_page['content'] = $a->start();
$_page['key']     = $a->getPageKey();

