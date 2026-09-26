<?php
/**
 * Унифицированный рендер способов оплаты Payone (иконки из конфига).
 *
 * @var array  $methods      (code => meta)
 * @var string $wrapperClass
 * @var string $wrapperAttrs Raw HTML attributes for wrapper tag (e.g. 'style="..."')
 * @var array  $extraHtml
 */
foreach ($methods as $code => $meta) { ?>
  <div class="display-right display-block" style="position: relative;margin: 0 0 5px 10px;">
    <input name="<?= $meta['name'] ?>" value="<?= $meta['value'] ?>"
           type="radio" id="<?= $meta['id'] ?>" class="check" <?= $meta['checked'] ?>/>
    <span class="podlog2"></span>
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
  </div>
  <?php
}
foreach ($extraHtml as $item) {
  if (is_string($item) && trim($item) !== '') {
    echo $item;
  }
}

