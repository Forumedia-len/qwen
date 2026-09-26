<?php
/**
 * @var array<string, \AC\core\modules\text\entities\dto\TextDto> $langsData
 * @var array                                                     $aliasOptions
 * @var string                                                    $currentAlias
 * @var string                                                    $formAction
 * @var array                                                     $structureData
 * @var string                                                    $aliasBaseHref
 * @var string                                                    $default_language
 */

use AC\core\system\helpers\StringHelper;

?>
<?php if (count($aliasOptions) > 1): ?>
  <div class="text-alias-switcher" style="margin-bottom: 16px; text-align: right;">
    <label for="text-alias-select"><?= lang('config_text_title', 'structure') ?>:</label>
    <select id="text-alias-select"
            data-base-href="<?= StringHelper::shield($aliasBaseHref) ?>"
            onchange="if (this.value) { window.location.href = this.dataset.baseHref + this.value; }">
      <?php foreach ($aliasOptions as $alias => $title): ?>
        <option value="<?= StringHelper::shield($alias) ?>"
          <?= $alias === $currentAlias ? 'selected' : '' ?>>
          <?= StringHelper::shield($title) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
<?php endif; ?>

<form action="<?= StringHelper::shield($formAction) ?>" method="post" onsubmit="return ifConfirm()">
  <input type="hidden" name="alias" value="<?= StringHelper::shield($currentAlias) ?>">
  <input type="hidden" name="action" value="update">
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide" style="width:70%">
    <caption>
      <?php
      $this->addPathToView(paths()->getTplDir('views', 'admin'));
      echo $this->render('base/_language_tabs', [
        'languages'        => array_keys($langsData),
        'active_language'  => $default_language,
        'content_selector' => '.lang-field',
        'container_class'  => '',
        'container_id'     => 'text-language-tabs'
      ]);
      ?>
    </caption>

    <?php
    foreach ($langsData as $langCode => $langData):
      $isActive = $langCode === $default_language;
      ?>
      <tr class="lang-field lang-<?= $langCode ?> <?= $isActive ? 'active' : '' ?>">
        <th colspan="2"><?= ($structureData['title'] ?? lang('Text')) ?> - <?= strtoupper($langCode) ?></th>
      </tr>
      <tr class="lang-field lang-<?= $langCode ?> <?= $isActive ? 'active' : '' ?>">
        <td class="light"><?= lang('Content') ?>:</td>
        <td class="light">
          <textarea name="texts[<?= $langCode ?>][content]"
                    class="ckeditor input wide"
                    rows="18"><?= StringHelper::shield($langData->getContent() ?? '') ?></textarea>
        </td>
      </tr>
    <?php endforeach; ?>

    <tr>
      <th align="center" colspan="2">
        <input type="submit" value="<?= lang('button_save') ?>" class="button">
        &nbsp;
        <input type="reset" value="<?= lang('button_reset') ?>" class="button">
      </th>
    </tr>
  </table>
</form>

