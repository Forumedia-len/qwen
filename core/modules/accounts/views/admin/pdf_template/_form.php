<?php
/**
 * @var object $model
 * @var string|null $templateFile
 */
/**
 * @var object $model
 * @var string|null $templateFile
 * @var string $templateFileName
 */
?>
<h1><?= lang('accounts_template_pdf_title', 'structure') ?></h1>
<div>
  <form action="<?= Service::structure()->getPageHrefByKey('accounts_pdf_template')?>/updatePdfTemplate" method="post" onsubmit="return ifConfirm ()">
    <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide" width="70%">
      <tr>
        <th colspan="2">
          <?= lang('accounts_template_pdf_table_title', 'accounts_pdf_template') ?>
        </th>
      </tr>
      <tr>
        <td class="dark" align="right" width="300px">
          <?= lang('accounts_template_pdf_header_title', 'accounts_pdf_template') ?>
        </td>
        <td class="light">
          <input type="text" class="input wide" name="account_view_header"
                 value="<?= htmlspecialchars($model->account_view_header, ENT_QUOTES) ?>">
        </td>
      </tr>
      <tr>
        <td class="dark" align="right">
          <?= lang('accounts_template_pdf_city_title', 'accounts_pdf_template') ?>
        </td>
        <td class="light"><input type="text" class="input wide" name="account_view_city"
                                 value="<?= htmlspecialchars($model->account_view_city, ENT_QUOTES) ?>"></td>
      </tr>
      <tr>
        <td class="dark" align="right">
          <?= lang('accounts_template_pdf_address_title', 'accounts_pdf_template') ?>
        </td>
        <td class="light">
        <textarea name="account_view_address" class="ckeditor">
          <?= $model->account_view_address ?>
        </textarea>
        </td>
      </tr>
      <tr>
        <td class="dark" align="right">
          <?= lang('accounts_template_pdf_footer_title', 'accounts_pdf_template') ?>
        </td>
        <td class="light">
        <textarea name="account_view_footer" class="ckeditor">
        <?= $model->account_view_footer ?>
        </textarea>
        </td>
      </tr>
      <tr>
        <td class="dark" align="right">
          <?= lang('accounts_template_pdf_nds_title', 'accounts_pdf_template') ?>
        </td>
        <td class="light">
          <input type="checkbox" class="input" name="account_view_nds_view"
                 value="1" <?= ($model->account_view_nds_view == '1' ? 'checked' : '') ?> >
        </td>
      </tr>
      <tr>
        <td class="dark" align="right">
          <?= lang('accounts_template_pdf_bank_view_title', 'accounts_pdf_template') ?>
        </td>
        <td class="light"><input type="checkbox" class="input" name="account_view_bank_view"
                                 value="1" <?= ($model->account_view_bank_view == '1' ? 'checked' : '') ?>>
        </td>
      </tr>
      <tr>
        <td class="dark" align="right">
          <?= lang('accounts_template_pdf_mail_view_title', 'accounts_pdf_template') ?>
        </td>
        <td class="light"><input type="checkbox" class="input" name="account_view_mail_view"
                                 value="1" <?= ($model->account_view_mail_view == '1' ? 'checked' : '') ?>>
        </td>
      </tr>
      <tr>
        <td class="dark" align="right">
          <?= lang('accounts_template_pdf_main_page_title', 'accounts_pdf_template') ?>
        </td>
        <td class="light"><input type="file" class="input" name="main_page_template"
                                 onchange="uploadAccountTemplate('<?= $templateFileName?>', this, 'main_page_upload_box')">
          <div id="main_page_upload_box" style="margin-top:10px;">
            <?php
            if ($templateFile) { ?>
              <div>
                <a href="<?= base_url($templateFile)?>" target="_blank" class="upload-box pdf">
                  <?= lang('accounts_template_pdf_view_file_title', 'accounts_pdf_template') ?>
                </a>
                <img src="<?= base_url(paths()->getAssetsDir('images/buttons/remove.gif'))?>" onclick="removeAccountTemplate(this, '<?=$templateFile?>')" style="cursor: pointer"
                     alt="<?=lang('button_remove')?>"/>
              </div>
              <?php
            } ?>
          </div>
        </td>
      </tr>
      <tr>
        <th colspan="2"><input type="submit" value="<?=lang('button_save')?>" class="button"></th>
      </tr>
    </table>
  </form>
</div>
