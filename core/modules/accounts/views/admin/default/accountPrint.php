<?php
/**
 * @var array $accountIds
 * @var int $accountType
 * @var string $action
 * @var string $jsonAccountIds
 * @var bool $send
 */
?>
<form action="accounts_view.php" method="POST" id="accountPrint" target="_blank">
  <input type="hidden" name="account_type" value="<?= $accountType ?>"/>
  <input type="hidden" name="action" value="<?= $action ?>"/>
  <?php foreach ($accountIds as $account_id) { ?>
        <input type="hidden" name="account[]" value="<?= $account_id ?>"/>
     <?php } ?>
</form>
<script>document.getElementById('accountPrint').submit()</script>
<script>markExecutionAll('<?= $jsonAccountIds?>', <?= $send ?>)</script>