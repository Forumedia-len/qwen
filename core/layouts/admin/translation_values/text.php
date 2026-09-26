<?php
/**
 * @var array $item
 */
?>
<div>
  <div style="display: inline-block" id="<?= $item['name']?>_translate">
    <?php foreach ( $item['value'] as $lang => $value) { ?>
      <input type="text" class="input wide" name="<?= $item['name'] ?>[<?=$lang?>]" value="<?= $item['value'][$lang]?>" <?= ($lang != config('lang')->getDefault() ? 'style="display:none"' : '')?>>
    <?php } ?>
  </div>
  <?php if (is_array($item['value']) && count($item['value']) > 1) { ?>
    <a href="#" onclick="return viewTranslateBlock(this)" style="width: 30px;float: right">
      <?= useLayout()->render('svg/translate', [], 'common') ?>
    </a>
  <?php } ?>
</div>
<!---->
<!--<script>-->
<!--  function viewTranslateBlock (e) {-->
<!--    console.log(e)-->
<!---->
<!--    return false-->
<!--  }-->
<!--</script>-->
