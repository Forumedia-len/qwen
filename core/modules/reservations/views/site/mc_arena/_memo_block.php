<?php



?>
<div class="orderItemBox comment-field">
  <div class="order-label" style="float:left; margin-right:10px;">
    <?= lang(
      'show_order_label_comment' . (CORONA_MC_ARENA ? '_corona_mc_arena' : ''),
      'show_order'
    ) ?>:
  </div>
  <input name="memo" value="" title="<?= lang('show_order_label_comment' . (CORONA_MC_ARENA ? '_corona_mc_arena' : ''), 'show_order') ?>"
         type="text" style="width:90%;max-width:400px;"/>
</div>