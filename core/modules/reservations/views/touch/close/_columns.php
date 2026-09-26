<?php
/**
 *  Вывод таблицы расписания для кортов
 * @var array  $sports         - все спроты этого типа площадок
 * @var int    $sport_id       - индетификатор текущего спорта
 * @var int    $type_id        - тип корта
 * @var int    $client_id      - индефикатор зарегистрированного клиента
 * @var bool   $client_bar     - зарегился ли левый клиент
 * @var int    $on_reservation - возможность бронировать этот день
 * @var string $date           - дата
 * @var int    $page           - номер страницы
 * @var array  $pages          - вкладки для страниц страницы
 * @var array  $areas          - площадки
 * @var bool   $week           - недельный отчет показывать
 * @var array  $params
 */
?>
<table style="width:100%">
  <tr>
    <td>
      <?php foreach ($areas as $area) { ?>
        <div id="windowWindowBlock_<?= $area->id ?>" class="windowWindowBlock">
          <script type="text/javascript">
            var popup<?=$area->id?> = new popupWindow('popup<?=$area->id?>', 'windowWindowBlock_<?=$area->id?>')
          </script>
          <div id="area_<?= $area->id ?>" class="areaPeriodsLarge">
            <div class="aroundBox">
              <div class="header">
                <div class="headerContent">
                  <a href="javascript:void(0);location.reload();" onclick="popup<?= $area->id ?>.closeWindow();"
                     class="button_back2">
                    <?= lang('cancel')?>
                  </a>
                </div>
                <div class="areaTitle_2">
                  <?= $area->title ?>
                </div>
              </div>
              <div class="content" style="padding:0; border:0; border-top:1px solid #666666;">
                <form action="<?= site_url() ?>reservations.php" method="post"
                      id="reservations_<?= $area->id ?>">
                  <input type="hidden" name="area_id" value="<?= $area->id ?>"/>
                  <input type="hidden" name="type_id" value="<?= $type_id ?>"/>
                  <input type="hidden" name="sport_id" value="<?= $sport_id ?>"/>
                  <input type="hidden" name="action" value="showOrder"/>
                  <input type="hidden" name="date" value="<?= $date ?>"/>
                  <input type="hidden" name="page" value="<?= $page ?>"/>
                  <table cellspacing="0" cellpadding="0" class="areaPeriods" style="width:100%; background:#fff;">
                    <?php
                    $count = ceil(count($area->periods) / 3);
                    for ($i = 0; $i < $count; $i++) {
                      $periods = array(
                        isset($area->periods[$i]) ? $area->periods[$i] : array(),
                        isset($area->periods[$count + $i]) ? $area->periods[$count + $i] : array(),
                        isset($area->periods[$count * 2 + $i]) ? $area->periods[$count * 2 + $i] : array(),
                      );
                      ?>
                      <tr>
                        <?php foreach ($periods as $period) {
                          echo $this->render( $courtType . '/_period', array_merge($params, array(
                            'period' => $period,
                            'area'   => $area,
                            'show_form' => true,
                          )));
                        } ?>
                      </tr>
                      <?php
                    }
                    ?>
                    <tr>
                      <td colspan="3" style="text-align:center; padding-left:0">
                        <input type="submit" value="<?= lang('Book')?>"
                               onclick="document.getElementById('reservations_<?= $area->id ?>').submit()"
                               class="button" disabled style="background:#999999;"/>
                               <script>
                                $('body').on('click','.areaPeriodsLarge form ',function(){
                                    let flgval=false;
                                 $('.areaPeriodsLarge form .period-time-check [type=hidden]').each(function(){
                                  if($(this).val()=='1' && !flgval)
                                  {
                                  flgval=true;  
                                  }
                                 })
                                 if(flgval){$('.areaPeriodsLarge form [type="submit"]').prop('disabled',false).prop('style',false);}
                                             else{$('.areaPeriodsLarge form [type="submit"]').prop('disabled',true).css('background','#999999');}
                                })
                               </script>
                      </td>
                    </tr>
                  </table>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php } ?>
      <table width="100%" cellspacing="8" cellpadding="0">
        <tr>
          <?php
          $width_column = floor(100 / (count($areas)));
          foreach ($areas as $area) { ?>
            <td width="<?= $width_column ?>%" align="center" valign="top">
              <div class="aroundBox <?=(($client_id !== null || $client_bar) ? 'active': '')?>">
                <div class="content" style="padding:0;">
                  <table cellspacing="0" cellpadding="0" width="100%" class="areaPeriods"
                         <?=$client_bar || $client_id !== null ? 'onmousedown="this.className=\'areaPeriods dark\';"
                         onclick="popup'.$area->id.'.createWindow(); this.className=\'areaPeriods\';"' : '' ?>>
                    <tr>
                      <th class="areaTitle" style="height:25px;">
                        <?= $area->title ?>
                      </th>
                    </tr>
                    <?php if (count($area->periods) == 0) { ?>
                      <tr>
                        <td class="avaliable" style="text-align: center"><?= lang('No operation', 'show_order')?></td>
                      </tr>
                    <?php } else {
                      foreach ($area->periods as $period) { ?>
                        <tr>
                          <?php
                          echo $this->render($courtType . '/_period', array_merge($params, array(
                            'period' => $period,
                            'area'   => $area,
                            'show_form' => false,
                          )));
                          ?>
                        </tr>
                      <?php }
                    } ?>
                  </table>
                </div>
              </div>
            </td>
          <?php } ?>
        </tr>
      </table>
    </td>
  </tr>
</table>

