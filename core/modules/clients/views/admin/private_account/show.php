<?php
/**
 * @var array $sections — массив объектов ClientPrivateAccountDto.
 * Каждый элемент содержит:
 * - title (string): заголовок секции.
 * - rows (array): массив строк с данными, где каждая строка содержит:
 *   - label (string): метка слева (например, имя клиента).
 *   - value (string): значение справа (например, сумма баланса).
 *   - icon (string, опционально): HTML-иконка (например, для PayPal).
 *
 * Все значения экранированы через StringHelper::shield() для безопасности.
 *
 * @var Page $this
 */

use AC\core\modules\clients\entities\dto\ClientPrivateAccountDto;
use AC\core\modules\clients\entities\dto\PaymentRowDto;
use AC\core\system\view\Page;

if ($this->issetMessages()): ?>
  <div style="padding: 20px">
    <?= $this->getMessages() ?>
  </div>
<?php endif; ?>
<div class="block-personal-account">
  <table class='main personal-account'>
    <?php /** @var ClientPrivateAccountDto $section */
    foreach ($sections as $section): ?>
      <tr>
        <th colspan="5"><?= $section->title ?></th>
      </tr>
      <?php
      /** @var PaymentRowDto $row */
      foreach ($section->rows as $key => $row):
        $class = $key % 2 ? 'light' : 'dark'; ?>
        <tr>
          <td class="<?= $class ?>"><?= $row->date ?? '' ?></td>
          <td class="<?= $class ?>"><?= $row->label ?? '' ?></td>
          <td class="<?= $class ?>" style="text-align: right;">
            <?= $row->amount ?? '' ?>
            <?= $row->icon ?? '' ?>
          </td>
          <td class="<?= $class ?>">
            <?= $row->comment ?? '' ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </table>
</div>
