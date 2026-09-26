<?php

/**
 * @var string $sport_by_type
// * @var bool $useSeason
 */

?>
<form action="config.php?mode=registration&action=saveSportByType" method="post" onsubmit="return ifConfirm ()">
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
    <tr>
      <th colspan="2"><?= lang('parameter_default_values_in_selecting_sport_type', 'config') ?></th>
    </tr>
    <tr>
      <td colspan="2">
        <table border="0" align="center" class="wide">
          <?php
          $i = 1;
          foreach ($sport_by_type as $key => $value) {
            if ($i > 2) {
              ?></tr><tr class="dark"><?php
              $i   = 1;
            }
            if ($i == 1) {
              ?><tr class="dark"><?php
            }
            ?><td style="white-space:nowrap; width: 20px"><input type="checkbox" name="default_values_in_selecting_sport_type[]" value="<?= $key ?>" <?= ($value['active'] ? 'checked' : '') ?>/></td>
            <td><?= $value['title'] ?></td><td></td><?php
            $i++;
          }?>
        </table>
      </td>
    </tr>
    <tr>
      <th colspan="2"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
    </tr>
  </table>
</form>