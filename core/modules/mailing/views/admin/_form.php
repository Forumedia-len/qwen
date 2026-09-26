<?php
/**
 * @var string                                                         $typeMode
 * @var string                                                         $alias
 * @var array                                                          $languages
 * @var array<\AC\core\modules\mailing\entities\dto\LetterTemplateDto> $templateByLanguages
 * @var string                                                         $activeLanguage
 * @var string                                                         $baseUrl
 * @var array                                                          $variableTemplate
 */

use AC\core\modules\mailing\entities\dto\LetterTemplateDto;
use AC\core\system\helpers\StringHelper;

?>
<h1><?= lang('Change template', 'config_letters') ?></h1>
<form method="post" action="<?= $baseUrl ?>/save">
  <input type="hidden" name="typeMode" value="<?= $typeMode ?>">
  <input type="hidden" name="alias" value="<?= $alias ?>">
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <caption>
      <?php
      $this->addPathToView(paths()->getTplDir('views', 'admin'));
      echo $this->render('base/_language_tabs', [
        'languages'        => $languages,
        'active_language'  => $activeLanguage,
        'content_selector' => '.lang-field',
        'container_class'  => '',
        'container_id'     => 'mailing-language-tabs'
      ]);
      ?>
    </caption>
    <?php
    foreach ($templateByLanguages as $langCode => $letterTemplate):
      $isActive = $langCode === $activeLanguage;
      ?>
      <tr class="lang-field lang-<?= $langCode ?> <?= $isActive ? 'active' : '' ?>">
        <th colspan='2'>
          <?=
          $letterTemplate->getModeTemplate()->label() . ' - '
          . $letterTemplate->getTitle() . (count($languages) > 1 ? ' - '
            . mb_strtoupper($letterTemplate->getLanguage()) : '') ?>
        </th>
        <?php if (!empty($variableTemplate)) : ?>
          <th><?= lang('title_variables', 'mailing') ?></th>
        <?php endif; ?>
      </tr>
      <tr class="lang-field lang-<?= $langCode ?> <?= $isActive ? 'active' : '' ?>">
        <td class="light"><?= lang('Subject', 'config_letters') ?></td>
        <td class="light">
          <input type="text" name="subject[<?= $langCode ?>]" class="input wide"
                 value="<?= StringHelper::shield($letterTemplate->getSubject() ?: '') ?>">
        </td>
        <?php if (!empty($variableTemplate)) : ?>
          <td class="dark" rowspan="3">
            <table>
              <?php foreach ($variableTemplate as $variable) { ?>
                <tr>
                  <th><?= $variable[0] ?></th>
                  <td><?= $variable[1] ?></td>
                </tr>
              <?php } ?>
            </table>
          </td>
        <?php endif; ?>

      </tr>
      <tr class="lang-field lang-<?= $langCode ?> <?= $isActive ? 'active' : '' ?>">
        <td class="dark"><?= lang('Content') ?></td>
        <td class="dark">
        <textarea name="content[<?= $langCode ?>]" class="ckeditor input wide mono" style="height:30em">
          <?= StringHelper::shield($letterTemplate->getContent() ?: '') ?>
        </textarea>
        </td>
      </tr>
    <?php endforeach; ?>
    <tr>
      <th colspan="2">
        <input type=submit class="button" value="<?= lang('button_update') ?>">&nbsp;
        <input type="reset" class="button" value="<?= lang('button_reset') ?>">
      </th>
    </tr>
  </table>
</form>