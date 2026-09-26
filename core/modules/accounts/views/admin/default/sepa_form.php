<?php
/**
 * @var string $account_type
 * @var string $areaType
 * @var string $date_start
 * @var string $date_finish
 */

?>
<?= lang('Creation date', 'accounts') ?>: <input type="text" name="sepa_date_start" id="sepa_date_start" value="<?= $date_start ?>"/>
&nbsp;  <?= lang('Execution date', 'accounts') ?>: <input type="text" name="sepa_date_finish" id="sepa_date_finish" value="<?= $date_finish ?>"/>
<input type="hidden" name="account_type" value="<?= $account_type ?>" id="account_type">
<input type="hidden" name="type" value="<?= $areaType ?>">
<input type="button" name="download" value="<?= lang('SEPA-Export', 'accounts') ?>" class="button"/>
<div style="float:right; padding:4px 0 0 8px;">
  <a href="sepa_help.php" target="_blank">
    <img src="<?= base_url(paths()->getAssetsDir('images/buttons/sepa_help.png')) ?>" alt=""/>
  </a>
</div>