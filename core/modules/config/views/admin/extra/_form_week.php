<?php
/**
 * @var object $model
 */

use AC\core\system\helpers\TranslateHelper;

?>
<div style="margin: 15px 15px 10px 0">
  <h1 style="text-align:center"><?= lang('Surcharges for', null, ['court_title'=> $model->title]) ?></h1>
  <form action="config.php?mode=extra&action=changeExtraWeek" method="post" id="extra-week-<?= ($model->extra_id ? : '') ?>">
    <input type="hidden" name="extra_id" value="<?= $model->extra_id ?>">
    <table border="0" cellspacing="1" cellpadding="1" align="center" class="main wide" style="min-width: 400px">
      <tr>
        <th rowspan="2"><?= lang('Time in hours')?></th>
        <th colspan="7"><?= lang('Weekday')?> / <?= lang('Prices')?> <?= CURR_VALUTE ?></th>
      </tr>
      <tr>
        <th><?= TranslateHelper::translateWeekday(0, true)?></th>
        <th><?= TranslateHelper::translateWeekday(1, true)?></th>
        <th><?= TranslateHelper::translateWeekday(2, true)?></th>
        <th><?= TranslateHelper::translateWeekday(3, true)?></th>
        <th><?= TranslateHelper::translateWeekday(4, true)?></th>
        <th><?= TranslateHelper::translateWeekday(5, true)?></th>
        <th><?= TranslateHelper::translateWeekday(5, true)?></th>
      </tr>
      <?php
      $j = 0;
      foreach ($model->times as $time => $weekdays) {
      ?>
      <tr>
        <td class="<?=($j % 2 == 0 ? 'light' : 'dark')?>" style="white-space: nowrap"><b><?=$model->timesTitles[substr($time, 0, 5)][0]?></b></td>
        <?php for ($w = 0;$w < 7; $w++) { ?>
          <td class="<?=($j % 2 == 0 ? 'light' : 'dark')?>" style="width: 30px">
            <input type="text" class="input smallest price" style="width: 6ch;margin-right: 0;margin-bottom: 3px" name="price[<?=$w?>][<?=$time?>]" value="<?=number_format($model->prices[$w][$time], 2, ',' , '')?>">
          </td>
        <?php } ?>
      </tr>
      <?php
        $j++;
      }
      ?>
      <tr>
        <th align="center" colspan="8"><input type=submit class="button" value="<?= lang('button_update')?>">&nbsp;<input type="reset" class="button" value="<?= lang('button_reset')?>"></th>
      </tr>
    </table>
  </form>
</div>

