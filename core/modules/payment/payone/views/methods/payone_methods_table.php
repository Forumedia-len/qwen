<?php
/**
 * Payone-методы в табличной разметке (<tr><td>..</td><td>..</td></tr>).
 *
 * @var array       $methods (code => meta)
 * @var array $extraHtml
 */
foreach ($methods as $code => $meta) {
  ?>
  <tr>
    <td>
      <input name="<?= $meta['name'] ?>" value="<?= $meta['value'] ?>"
             type="radio" id="<?= $meta['id'] ?>" class='check' <?= $meta['checked'] ?>/>
      <span class="podlog2"></span>
    </td>
    <td class="td-item-label">
      <label for="<?= $meta['id'] ?>">
        <?php
        foreach ($meta['icons'] as $icon) { ?>
          <img src="<?= $icon['src'] ?>"
               alt="<?= $icon['alt'] ?>"
               style="height: 33px;position: relative;top:3px"/>
        <?php
        } ?>
      </label>
      <?= $meta['comment_block'] ?? '' ?>
    </td>
  </tr>
  <?php
}
foreach ($extraHtml as $item) {
  if (is_string($item) && trim($item) !== '') {
    ?>
    <tr style="display:none">
      <td colspan="2"><?= $item ?></td>
    </tr>
    <?php
  }
}

