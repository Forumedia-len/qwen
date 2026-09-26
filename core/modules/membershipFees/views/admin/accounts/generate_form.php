<?php
/**
 * @var string $selectYears
 * @var string $selectClients
 * @var string $selectTextConfig
 * @var string $urlFormYear
 * @var string $urlForm
 * @var int    $currentYear
 */

?>
<table align="center" border="0" cellspacing="1">
  <tr>
    <td valign="top">
      <form action="<?= $urlFormYear ?>" method="post">
        <table cellspacing="1" cellpadding="3" class="main" border="0">
          <tr>
            <th colspan="3">
              <?= lang('Member invoices', 'membership_fees_accounts') ?>
            </th>
          </tr>
          <tr>
            <td class="light">
              <?= lang('Date') ?>:
            </td>
            <td class="light">
              <?= $selectYears ?>
            </td>
            <td>
              <input type="submit" value="&gt;&gt;&gt;" class="button"/>
            </td>
          </tr>
        </table>
      </form>
    </td>
  </tr>
  <?php
  //Выбор Клиентов имеющих заказы за эту дату
  if ($selectClients) { ?>
    <tr>
      <td valign="top">
        <form action="<?= $urlForm ?>" method="post">
          <input type="hidden" name="year" value="<?= $currentYear ?>"/>
          <table cellspacing="1" cellpadding="3" class="main" border="0" style="width: 100%">
            <tr>
              <td class="dark"><?= lang('Client') ?>:</td>
              <td class="dark"><?= $selectClients ?></td>
            </tr>
            <tr>
              <td class="light">&nbsp;</td>
              <td class="light">
                <a href="#" onclick="return selectClientList('clientList')"><?= lang('Mark all') ?></a>
              </td>
            </tr>
            <tr>
              <td class="dark"><?= lang('Text') ?>:</td>
              <td class="dark">
                <?= $selectTextConfig ?>
              </td>
            </tr>
            <tr>
              <td class="dark" colspan="2" align="center">
                <input type="submit" value="<?= lang('Execute') ?>" class="button"/>
              </td>
            </tr>
          </table>
        </form>

      </td>
    </tr>
  <?php } ?>
</table>

