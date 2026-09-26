<?php
/**
 * @var string $date_start
 * @var string $date_finish
 * @var bool   $showSundayColumn
 * @var string $actionUrl
 */
?>
<form action="<?= $actionUrl ?>" method="post" name="formHoliday">
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" class="main" align="center">
    <tr>
      <th colspan="2"><?= lang('Add public holidays', 'holiday') ?></th>
    </tr>
    <tr>
      <td class="light"><?= lang('Date') ?>:</td>
      <td class="light">
        <table>
          <tr>
            <td><label for='date_start'><?= lang('From') ?>:</label></td>
            <td><input id='date_start' type='text' name='date_start' class='input small datepicker' value="<?= ($date_start ?? date('d.m.Y')) ?>"/>
            </td>
            <td rowspan='2'>
              <a href='javascript:void null' onclick="copyPeriodDate (document.forms['formHoliday']);">
                <img src="<?= base_url(paths()->getAssetsDir('images/copy.gif')) ?>" alt='Kopieren' border='0'>
              </a>
            </td>
          </tr>
          <tr>
            <td><label for='date_finish'><?= lang('Until') ?>:</label></td>
            <td><input id='date_finish' type='text' name='date_finish' class='input small datepicker' value="<?= ($date_finish ?? date('d.m.Y')) ?>"/>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <?php if ($showSundayColumn): ?>
      <tr>
        <td class="dark"><label for="sunday_prices"><?= lang('Use Sunday Prices', 'holiday') ?>:</label></td>
        <td class="dark">
          <input id="sunday_prices" type="checkbox" name="sunday_prices" value="1"/>
        </td>
      </tr>
      <?php if (config('holidays')->useSundayTimes()): ?>
        <tr>
          <td class="dark"><label for="sunday_times"> <?= lang('Use opening hours on Sundays', 'holiday') ?>:</label></td>
          <td class="dark">
            <input id="sunday_times" type="checkbox" name="sunday_times" value="1"/>
          </td>
        </tr>
      <?php endif; ?>
    <?php endif; ?>
    <tr>
      <th align="center" colspan="2">
        <input type="submit" value="<?= lang('button_create') ?>" class="button"> &nbsp;
        <input type="reset" value="<?= lang('button_reset') ?>" class="button">
      </th>
    </tr>
    <?php if (config('holidays')->checkBlockingEventsForHolidays()): ?>
      <tr>
        <td colspan="2" style="text-align: center"><?= lang('text_bottom_form', 'holiday') ?></td>
      </tr>
    <?php endif; ?>
  </table>
</form>
