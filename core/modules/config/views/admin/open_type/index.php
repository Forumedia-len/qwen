<?php
/**
 * @var ConfigModel $models
 * @var array $TypePricingSystem
 * @var int $type_id
 * @var View  $this
 */


use AC\core\system\view\View;
use AC\core\modules\config\models\ConfigModel;

$typeIdQuery = !empty($type_id) ? '&type_id=' . (int)$type_id : '';

?>
<form action="config.php?mode=open_type&action=save<?= $typeIdQuery ?>" method="post" onsubmit="return ifConfirm ()">
  <?php if (!empty($type_id)): ?>
    <input type="hidden" name="type_id" value="<?= (int)$type_id ?>">
  <?php endif; ?>
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide open-type">
    <tr>
      <th><?= lang('title_parameter', 'config') ?></th>
      <th><?= lang('title_value', 'config') ?></th>
    </tr>
    <tr>
      <th rowspan="2"><?= lang('title_type_pricing_system_chose', 'config_open_type') ?></th>
      <td>
        <div style="display: flex;justify-content:space-around;flex-wrap: wrap;">
          <?php foreach ($TypePricingSystem  as $typeSystem): ?>
            <div>
              <input type="radio" id="type_pricing_system_<?= $typeSystem->id ?>" name="open[type_pricing_system]" value="<?= $typeSystem->id ?>" <?= ($models->type_pricing_system == $typeSystem->id ? 'checked' : '') ?>>
              <label for="type_pricing_system_<?= $typeSystem->id ?>"><?= '<b>'.$typeSystem->id . '</b>'?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </td>
    </tr>
    <tr>
      <td>
        <ol style="max-width: 590px;">
          <?= lang('description_type_pricing', 'config_open_type')?>
          <?php foreach ($TypePricingSystem  as $typeSystem): ?>
            <li style="margin-bottom: 5px;padding: 5px">
              <?= '<b>' . $typeSystem->id . '</b> - ' . $typeSystem->description?>
            </li>
          <?php endforeach; ?>
        </ol>
      </td>
    </tr>
    <tr>
      <th colspan="2"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
    </tr>
  </table>
</form>

