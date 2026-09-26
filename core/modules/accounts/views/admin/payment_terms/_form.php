<?php
/**
 * @var string $title
 * @var string $url
 * @var string $content
 */
?>
<h1><?= lang('Account payment terms', 'structure') ?></h1>
<form
  action="<?= $url ?>"
  method="post" onsubmit="return ifConfirm ()">
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide"
         style="width:55%">
    <thead>
    <tr>
      <th><h4><?= $title ?></h4></th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td>
        <textarea name="content" class="ckeditor"><?= $content ?></textarea>
      </td>
    </tr>
    <tr>
      <th>
        <div style='display: flex;justify-content: center'>
          <input type='submit' value="<?= lang('button_save') ?>" class='button'>
        </div>
      </th>
    </tr>
    </tbody>
  </table>
</form>
