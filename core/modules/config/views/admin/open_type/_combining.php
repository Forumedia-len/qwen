<?php
/**
 *  @var  array $players
 *  @var  array $use
 *  @var  int $type_id
 */



?>
<?php $typeIdQuery = !empty($type_id) ? '&type_id=' . (int)$type_id : ''; ?>
<form action="config.php?mode=open_type&action=saveCombination<?= $typeIdQuery ?>" method="post">
  <?php if (!empty($type_id)): ?>
    <input type="hidden" name="type_id" value="<?= (int)$type_id ?>">
  <?php endif; ?>
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide open-type">
    <caption><?= lang('select_possible_combinations_of_players', 'config_open_type')?></caption>
    <tr>
      <th rowspan="2" style="font-size: 15px;font-weight: bold"><?= lang('main_player', 'config_open_type') ?>:</th>
      <th colspan="<?= count($players)?>" style="font-size: 15px;font-weight: bold"><?= lang('other_player', 'config_open_type') ?>:</th>
    </tr>
    <tr>
      <?php foreach ($players as $player):?>
        <th><?= $player->title ?></th>
      <?php endforeach;?>
    </tr>
    <?php foreach ($use as $mainPlayer => $otherPlayers):?>
      <tr>
        <th><?= $players[$mainPlayer]->title ?></th>
        <?php foreach ($otherPlayers as $otherPlayer => $check):?>
          <td><input type="checkbox" class="check" name="combinations[<?= $mainPlayer?>][<?= $otherPlayer?>]" value="1" <?= ($check ? 'checked' : '')?>></td>
        <?php endforeach;?>
      </tr>
    <?php endforeach;?>
    <tr>
      <th colspan="<?= count($players) + 1?>"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
    </tr>
  </table>
</form>
