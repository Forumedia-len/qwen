<?php
/**
 *  @var string $areaType
 */?>
<input type="submit" data-type="<?= $areaType ?>" name="submit_print" value="<?= lang('Print') ?>" class="button"/>
<input type="submit" data-type="<?= $areaType ?>" name="submit_archives" value="<?= lang('Archive') ?>" class="button"/>
<input type="submit" data-type="<?= $areaType ?>" name="submit_download" value="<?= lang('Export RE Journal (CSV)', 'accounts') ?>" class="button"/>
<input type="submit" data-type="<?= $areaType ?>" name="submit_sendAllEmails" value="<?= lang('Send as email', 'accounts') ?>" class="button"
       onClick="return ifConfirm('<?= lang('confirm_send_email_account', 'js_confirm') ?>')"/>

