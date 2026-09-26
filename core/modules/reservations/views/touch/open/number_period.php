<?php
/**
 * @var int|float $period
 * @var bool $checkMaxNumberPeriods
 * @var int $numberPeriodsStart
 * @var int $numberPeriodsEnd
 * @var int $numberPeriodsDefault
 */

use AC\core\system\helpers\TranslateHelper;

?>
<div class="orderItemBox">
  <div style="float: left; width: 105px; padding: 8px 0"><?= lang('Playtime') ?> :</div>
  <div style="margin-left: 120px">
    <div class="type-reservation">
      <?php for ($i = $numberPeriodsStart; $i <= $numberPeriodsEnd; $i++) { ?>
        <div class="type-reservation-item">
          <input type="radio" name="numberOfPeriods" value="<?= $i ?>" class="radio" <?= ($i == $numberPeriodsDefault ? 'checked' : '') ?>
                 id="numberOfPeriods_<?= $i ?>"/>
          <span class="podlog"></span><label for="numberOfPeriods_<?= $i ?>"><?= TranslateHelper::translatePeriodInMinute($i * $period, false) ?></label>
        </div>
      <?php } ?>
    </div>
  </div>
</div>
