<?php
/**
 * @var array $data
 */
?>
<table style="width: 100%; border: none;background-color: #fff;border-spacing: 1px;border-collapse: separate;padding: 0" readonly>
  <tr>
    <th colspan="2"><?= lang('Membership fees association', 'membership_fees') ?> :</th>
  </tr>
  <tr>
    <td class="dark"><?= lang('membership_fees_groups', 'structure') ?> :</td>
    <td class="dark"><?= useLayout()->render('select', $data['membership_fees_groups'] , 'common') ?></td>
  </tr>
  <tr>
    <td class="light"><?= lang('Gender', 'membership_fees') ?> :</td>
    <td class="light"><?= useLayout()->render('select', $data['gender'], 'common') ?></td>
  </tr>
  <tr>
    <td class="dark"><?= lang('Entry date', 'membership_fees') ?> :</td>
    <td class="dark"><input type="text" name="entry_date" value="<?= $data['entry_date'] ?>" size="20" class="input small datepicker" data-year-range='-100:+0'></td>
  </tr>
  <tr>
    <td class="light"><?= lang('Actual age', 'membership_fees') ?> :</td>
    <td class="light"><?= $data['actual_age'] ?></td>
  </tr>
  <tr><td colspan="2"></td></tr>
  <tr>
    <td class="dark" colspan="2"><label for="auto_transition_group"><?= lang('Automatic transition in the group by age', 'membership_fees') ?></label> <input type="checkbox" name="auto_transition_group" id="auto_transition_group" value="1" <?= ( $data['auto_transition_group'] ?? '')?>></td>
  </tr>
</table>
