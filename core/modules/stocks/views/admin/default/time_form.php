<?php
/**
 * @var \AC\core\modules\stocks\entities\dto\StockDto $stock
 * @var string                                        $prev
 * @var string                                        $finish
 * @var int                                           $interval
 * @var array                                         $startData
 * @var array                                         $finishData
 * */

use AC\core\modules\stocks\entities\dto\StockDto;
use AC\core\system\helpers\TimeHelper;

?>
<form method="post" action="<?= Service::structure()->getPageHrefByKey('stocks') ?>">
  <input type="hidden" name="action" value="changeTime">
  <input type="hidden" name="stock_id" value="<?= $stock->stockId ?>">
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th colspan="2"><?= lang('Validity in time', 'config_stock') ?> - <?= $stock->title ?></th>
    </tr>
    <tr>
      <td class="dark"><?= lang('Active') ?>:</td>
      <td class="dark">
        <input type="checkbox" class="input" name="active" value="1"
          <?= $stock->duration ? 'checked' : '' ?> />
      </td>
    </tr>

    <!-- Start Date -->
    <tr>
      <td class="light"><?= lang('From') ?>:</td>
      <td class="light">
        <table cellspacing="0" cellpadding="0" border="0">
          <tr>
            <td>
              <?= $startData['years'] ?>
            </td>
            <td>
              <?= $startData['months'] ?>
            </td>
            <td>
              <?= $startData['days'] ?>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- Finish Date -->
    <tr>
      <td class="light"><?= lang('Until') ?>:</td>
      <td class="light">
        <table cellspacing="0" cellpadding="0" border="0">
          <tr>
            <td>
              <?= $finishData['years'] ?>
            </td>
            <td>
              <?= $finishData['months'] ?>
            </td>
            <td>
              <?= $finishData['days'] ?>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- Time Slots -->
    <tr>
      <td class="light" colspan="2">
        <table border="0" cellspacing="1" cellpadding="3" class="main wide">
          <tr>
            <th width='90px'><?= lang('Time in hours') ?></th>
            <?php for ($i = 0; $i < 7; $i++): ?>
              <th>
                <?= lang("weekday_small_$i") ?><br/>
                <input type="checkbox" value="1" onchange="selectAllCheck(this, 'weekday_c_<?= $i ?>')"/>
              </th>
            <?php endfor; ?>
          </tr>
          <?php
          $current = $prev;
          $j       = 0;
          do {
            $next  = TimeHelper::addMinutes2MySQLTime($current, $interval);
            $class = $j % 2 == 0 ? 'light' : 'dark';
            ?>
            <tr>
              <td class="<?= $class ?>"><b><?= $current ?> - <?= $next ?></b></td>
              <?php for ($i = 0; $i < 7; $i++):
                $start = TimeHelper::convertTime24($current);
                ?>
                <td class="<?= $class ?>" align="center">
                  <input class="input weekday_c_<?= $i ?>" type="checkbox"
                         name="durations[<?= $i ?>][<?= $start ?>]"
                         value="<?= $next ?>:00"
                    <?= isset($stock->durations[$i][$start]) ? 'checked' : '' ?> />
                </td>
              <?php endfor; ?>
            </tr>
            <?php
            $current = $next;
            $j++;
          } while (strtotime($current) < strtotime(TimeHelper::convertTime24($finish)));
          ?>
        </table>
      </td>
    </tr>

    <tr>
      <th align="center" colspan="8">
        <input type="submit" class="button" value="<?= lang('button_update') ?>">
        &nbsp;
        <input type="reset" class="button" value="<?= lang('button_reset') ?>">
      </th>
    </tr>
  </table>
</form>