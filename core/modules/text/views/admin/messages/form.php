<?php
/**
 * @var array                                          $message
 * @var array<string, string>                          $values
 * @var array<string> $languages
 * @var string                                         $defaultLanguage
 * @var array<string>                                  $paramsMessage
 * @var string                                         $formAction
 * @var string                                         $backHref
 * @var string                                         $currentTypeTitle
 */

use AC\core\system\helpers\StringHelper;

$hasVariables  = !empty($paramsMessage);
?>

<h1><?= lang('config_messages_title', 'structure'); ?> ( <?= StringHelper::shield($currentTypeTitle) ?> )</h1>

<form action="<?= StringHelper::shield($formAction) ?>" method="post" onsubmit="return ifConfirm()">
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide" style="width:70%">
    <caption>
      <?php
      $this->addPathToView(paths()->getTplDir('views', 'admin'));
      echo $this->render('base/_language_tabs', [
        'languages'        => $languages,
        'active_language'  => $defaultLanguage,
        'content_selector' => '.lang-field',
        'container_class'  => '',
        'container_id'     => 'message-language-tabs'
      ]);
      ?>
    </caption>

    <tbody>
    <?php foreach ($languages as $code): ?>
      <?php $active = $code === $defaultLanguage; ?>
      <tr class="lang-field lang-<?= StringHelper::shield($code) ?> <?= $active ? 'active' : '' ?>">
        <th colspan="<?= $hasVariables ? 2 : 1 ?>">
          <?= StringHelper::shield($message['name']) ?> — <?= strtoupper($code) ?>
        </th>
      </tr>
      <tr class="lang-field lang-<?= StringHelper::shield($code) ?> <?= $active ? 'active' : '' ?>">
        <td class="light"<?= $hasVariables ? '' : ' colspan="2"' ?>>
          <textarea
            name="messages[<?= StringHelper::shield($code) ?>][content]"
            class="ckeditor input wide"
            rows="15"
          ><?= StringHelper::shield($values[$code] ?? '') ?></textarea>
        </td>
        <?php if ($hasVariables): ?>
          <td class="light" rowspan="<?= count($languages) * 2 ?>">
            <strong><?= lang('title_variables', 'config_messages') ?>:</strong>
            <table style="width:100%; margin-top:10px;">
              <?php foreach ($paramsMessage as $variable): ?>
                <tr>
                  <td style="width:40%; font-weight:bold;">
                    <?= StringHelper::shield(lang('variable_' . $variable, 'config_messages')) ?>
                  </td>
                  <td><?= StringHelper::shield('{{' . $variable . '}}') ?></td>
                </tr>
              <?php endforeach; ?>
            </table>
          </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>

    <tr>
      <th colspan="<?= $hasVariables ? 2 : 1 ?>">
        <div style="display: flex; justify-content: center; gap: 8px;">
          <input type="submit" value="<?= lang('button_save') ?>" class="button">
          <a href="<?= StringHelper::shield($backHref) ?>"
             class="button">
            <?= lang('Back') ?>
          </a>
        </div>
      </th>
    </tr>
    </tbody>
  </table>
