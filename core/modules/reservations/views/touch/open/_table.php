<?php
/**
 *  Вывод таблицы расписания для кортов
 * @var array       $sports_tabs    - все спроты этого типа площадок
 * @var array       $pages_tabs     - вкладки для страниц страницы
 * @var array       $weeks_tabs     - вкладки для недельного и обычного рассписания
 * @var int         $sport_id       - индетификатор текущего спорта
 * @var int         $type_id        - тип корта
 * @var int         $client_id      - индефикатор зарегистрированного клиента
 * @var null|object $client         - данные клиента клиента
 * @var bool        $client_bar     - данные клиента клиента
 * @var int         $on_reservation - возможность бронировать этот день
 * @var string      $date           - дата
 * @var int         $page           - номер страницы
 * @var array       $areas          - площадки
 * @var bool        $week           - недельный отчет показывать
 * @var View        $this
 * @var array       $params
 * @var string       $courtType
 */

use AC\core\engines\Engines;
use AC\core\system\view\View;

$engine      = new Engines();
$count_page  = count($pages_tabs);
$count_areas = 0;
foreach ($pages_tabs as $pages_tab) {
  $count_areas += OPEN_STREET_PER_PAGE;
}

$page_next = false;
$page_prev = false;
if ($page > 1 && $count_page > 1) {
  $page_prev = $page - 1;
}

if ($count_areas > OPEN_STREET_PER_PAGE && $page < $count_page && $count_page > 1) {
  $page_next = $page + 1;
}

//переход на нужное время
if ($engine->areas->getAreasTimeTableByType(2, $area_time, $price_tmp)) {
  $tmp = current($area_time);

  //Костыль из-за strtotime
  if ($tmp[0][3] === "24:00") {
    $tmp[0][3] = "23:00";
  }
  $count_cell   = strtotime($date . ' ' . $tmp[0][3]) - strtotime($date . ' ' . $tmp[0][2]);
  $current_cell = strtotime($date . ' ' . date('H') . ':00') - strtotime($date . ' ' . $tmp[0][2]);

  echo '<script>' . "\n";
  echo 'per_page = ' . (OPEN_STREET_PER_PAGE ? (isset($areas) && count($areas) < OPEN_STREET_PER_PAGE ? count($areas) : OPEN_STREET_PER_PAGE)
      : (isset($areas) && count($areas) < PER_PAGE ? count($areas) : PER_PAGE)) . ';' . "\n";
  echo 'amount_cell = ' . ($count_cell / 1800) . ';' . "\n";
  echo 'current_amount_cell = ' . ($current_cell / 1800) . ';' . "\n";
  echo '</script>' . "\n";
}
?>
<tr>
  <td class="lateral">
    <?php if ($page_prev) { ?>
      <div
        onclick="location.href='reservations.php?action=showReservations&type_id=<?= $type_id ?>&area_id=<?= $area_id ?>&sport_id=<?= $sport_id ?>&date=<?= $date ?>&page=<?= $page_prev ?>'"
        class="v-arrows-button-bg">
        <div style="background:url(<?= base_url(paths()->getAssetsDir('images/icons_sprite.png')) ?>) center -188px no-repeat"></div>
      </div>
    <?php } ?>
  </td>
  <td>
    <div style="background-color: #fff; color: #000; padding: 1px 5px; margin-bottom: 1px;font-size: 14px">
      <?php echo langByAreaType('info_text_above_timetable', $courtType, $type_id); ?>
    </div>
    <div id="mainScheduleBlock">
      <?php
      switch ($week) {
        case 0:
          echo $this->render($courtType . '/_columns', $params);
          break;
        case 1:
          echo $this->render($courtType . '/_week', $params);
          break;
      }
      ?>
    </div>
  </td>
  <td class="lateral">
    <?php if ($page_next) { ?>
      <div
        onclick="location.href='reservations.php?action=showReservations&type_id=<?= $type_id ?>&area_id=<?= $area_id ?>&sport_id=<?= $sport_id ?>&date=<?= $date ?>&page=<?= $page_next ?>'"
        class="v-arrows-button-bg">
        <div style="background:url(<?= base_url(paths()->getAssetsDir('images/icons_sprite.png')) ?>) center -138px no-repeat"></div>
      </div>
    <?php } ?>
  </td>
</tr>

