<?php
/**
 * @var array  $params
 *  Вывод таблицы расписания для кортов
 * @var array  $sports         - все спроты этого типа площадок
 * @var int    $sport_id       - индетификатор текущего спорта
 * @var int    $type_id        - тип корта
 * @var int    $client_id      - индефикатор зарегистрированного клиента
 * @var int    $on_reservation - возможность бронировать этот день
 * @var string $date           - дата
 * @var int    $page           - номер страницы
 * @var array  $pages          - вкладки для страниц страницы
 * @var array  $areas          - площадки
 * @var bool   $week           - недельный отчет показывать
 */
$areas = [];
if(isset($params['areas'])) {
$areas =   $params['areas'];
} else {
  foreach ($params as $type) {
    if (isset($type['areas']) && is_array($type['areas'])) $areas = array_merge($areas, $type['areas']);
  }
}

foreach ($areas as $ar) {
  $aliases[$ar->id] = $ar->title;
}
?>

<table width="100%" cellspacing="0" cellpadding="0" border="0">
  <tr>
    <?php
    $width_column = floor(100 / (count($areas)));
    foreach ($areas as $area) { ?>
      <td width="<?= $width_column ?>%" align="center" valign="top">
        <table border="0" cellspacing="2" cellpadding="0" width="100%" class="areaPeriods">
          <tr>
            <th class="areaTitle" style="height:25px;">
              <?= $aliases[$area->id] ?>
            </th>
          </tr>
          <?php if (count($area->periods) == 0) { ?>
            <tr>
              <td class="avaliable" style="text-align: center"><?= lang('No operation', 'show_order')?></td>
            </tr>
          <?php } else {
            $i = 1;
            foreach ($area->periods as $period) { ?>
              <tr>
                <td>
                  <div class="timeBoxContent">
                    <div class="timeBox" id="timeBox_<?= $area->id ?>_<?= $i ?>">
                      <div class="<?= $period->class ?>">
                        <?= ($area->light_on == 1 ? '<div class="lightOn">' : '') ?>
                        <?= $period->start . ' - ' . $period->finish . lang('Clock')?> <br>
                        <?= (isset($period->client->name)? (($period->client->visible)?$period->client->name:'') . (isset($period->ticket) && $period->ticket !== false ? ' (Abo)' : '') : '') ?>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
              <?php $i++;
            }
          } ?>
        </table>
      </td>
    <?php } ?>
  </tr>
</table>