<?php
/**
 *  Вывод таблицы расписания для кортов
 * @var array  $sports_tabs    - все спроты этого типа площадок
 * @var array  $pages_tabs     - вкладки для страниц страницы
 * @var array  $weeks_tabs     - вкладки для недельного и обычного рассписания
 * @var int    $sport_id       - индетификатор текущего спорта
 * @var int    $type_id        - тип корта
 * @var int    $client_id      - индефикатор зарегистрированного клиента
 * @var int    $on_reservation - возможность бронировать этот день
 * @var string $date           - дата
 * @var int    $page           - номер страницы
 * @var array  $areas          - площадки
 * @var bool   $week           - недельный отчет показывать
 * @var View   $this
 * @var array  $params
 * @var string  $courtType
 */

use AC\core\system\view\View;

?>

<?php // -------------- Вывод табов для спорта - начало ----?>
<?php if ($sports_tabs != false and count($sports_tabs) > 1) { ?>
  <div class="tab-field">
    <?php
        $is_first_tab = true;
        foreach ($sports_tabs as $sport) {
    ?>
      <div class="tab-item <?=$sport->active ? 'active-tab' : '' ?>">
        <?php if (!$sport->active) { ?>
        <a href="reservations.php?action=showReservations&type_id=<?= $type_id ?>&sport_id=<?= $sport->id ?>&date=<?= $date ?>&page=1<?= ($week == 1 ? '&week=1' : '') ?>"
           class="<?//=$sport->class?>">
          <?php } ?>
          <div class="<?= ($sport->active ? ' tab-item-active' : 'tab-item-no-active') ?> <?= ($is_first_tab ? 'first': 'no-first')?> <?= $sport->class ?>-tab ">
            <span class="sport-icon"></span>
            <span class="<?=($sport->active ? 'sport-active': 'sport-no-active')?>"><?= $sport->title ?></span>
            <div class="tab-item-right-line"></div>
          </div>
          <?php if (!$sport->active) { ?>
        </a>
      <?php } ?>
      </div>
    <?php $is_first_tab = false; } ?>
      <div class="tab-field-end-line"></div>
  </div>
<?php } ?>
<?php // -------------- Вывод табов для спорта - конец ----?>
<div class="content reservation-content">

  <?php // -------------- Вывод табов для страниц если нужно - начало ----?>
  <?php if (count($pages_tabs) > 1) { ?>
    <div class="courts-tabs-field">
      <div class="court-tab-left-arrow"></div>
      <div class="courts-items-wrapper">
        <table>
          <tr>
            <?php foreach ($pages_tabs as $_page) { ?>
              <td class="court-tab-item <?= ($_page->page == $page ? 'current-court-tab-item' : '') ?>" data-page="<?=$_page->page?>">
                <a href="reservations.php?action=showReservations&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>&date=<?= $date ?>&page=<?= $_page->page ?>&area_id=<?= $_page->area_id ?><?= ((isset($week) && $week == 1) ? '&week=1' : '') ?>">
                  <span class="mobile-hidden">
                    <?= $_page->name_title ?>
                  </span>
                  <span>
                    <?= $_page->number_title ?>
                  </span>
                </a>
              </td>
            <?php } ?>
          </tr>
        </table>
      </div>
      <div class="court-tab-right-arrow"></div>
    </div>
  <?php } ?>
  <?php // -------------- Вывод табов для страниц ели нужно - конец ----?>

  <?php // -------------- Вывод табов для недельного и обычного рассписания - начало ----?>
    <div class="reservations-view-type-field">
        <?php foreach ($weeks_tabs as $sk_week => $_week) { ?>
            <div class="reservations-view-type-item <?= ($_week->type != $week ? 'reservations-week-view' : 'reservations-day-view') . ($_week->type == $week ? ' active' : '') ?>">
                <?php if ($_week->type != $week) { ?>
                <a href="reservations.php?action=showReservations&type_id=<?= $type_id ?>&sport_id=<?= $sport_id ?>&date=<?= $date ?>&page=<?= $page ?><?= ($_week->type == 1 ? '&week=1' : '') ?>">
                    <?php } ?>
                    <?= $_week->title ?>
                    <?php if ($_week->type != $week) { ?>
                </a>
            <?php } ?>
            </div>
        <?php } ?>
    </div>
  <?php // -------------- Вывод табов для недельного и обычного рассписания - конец ----?>
  <?php
  switch ($week) {
    case 0:
      echo $this->render( '_columns', $params);
      break;
    case 1:
      echo $this->render( '_week', $params);
      break;
  }
  ?>
</div>

