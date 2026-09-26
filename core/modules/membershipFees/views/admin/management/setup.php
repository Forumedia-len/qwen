<?php

/**
 * @var array  $clients
 * @var array  $groups
 * @var int    $selectedClientId
 * @var int    $selectedGroupId
 * @var string $assignUrl
 * @var string $createClientUrl
 */
?>
<form action="<?= htmlspecialchars($assignUrl, ENT_QUOTES) ?>" method="post">
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th colspan="2"><?= lang('membership_fees_management_setup', 'structure') ?></th>
    </tr>
    <tr>
      <td class="dark"><?= lang('Select name', 'membership_fees_management') ?>:</td>
      <td class="dark">
        <?= useLayout()->render('select', [
          'name'    => 'client_id',
          'values'  => $clients,
          'current' => $selectedClientId,
          'class'   => 'wide',
        ], 'common') ?>
      </td>
    </tr>
    <tr>
      <td class="light"><?= lang('Contribution group', 'membership_fees_management') ?>:</td>
      <td class="light">
        <?= useLayout()->render('select', [
          'name'    => 'membership_fees_group',
          'values'  => $groups,
          'current' => $selectedGroupId,
          'class'   => 'wide',
        ], 'common') ?>
      </td>
    </tr>
    <tr>
      <td class="dark"><?= lang('Action') ?>:</td>
      <td class="dark">
        <input type="submit" value="<?= lang('Assign', 'membership_fees_management') ?>" class="button">
        <a href="<?= htmlspecialchars($createClientUrl, ENT_QUOTES) ?>" class="button">
          <?= lang('Create new user', 'membership_fees_management') ?>
        </a>
      </td>
    </tr>
  </table>
</form>
