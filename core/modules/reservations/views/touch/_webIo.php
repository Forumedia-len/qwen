<?php
/**
 * @var array $webIo
 */
?>
<?php foreach ($webIo as $l) : ?>
<div style="display: <?= ($l->hidden ? 'none' : 'block')?>; margin: 2px 0 5px">
  <h5 style="font-size: 15px;margin: 5px 0"><?= $l->label?></h5>
  <ul style="padding: 0;margin: 5px 0;display: flex;flex-wrap: wrap;align-items: center;list-style: none">
    <?php $i = 1; foreach ($l->times as $time => $item): ?>
      <li class="lightManageButton <?= (!$item['hidden'] && $item['disable'] ? 'webio-hidden' : '') ?>" data-hidden="<?= $i ?>" style="margin: 2px 10px 3px;display: <?= ($item['disable'] || $item['hidden'] ? 'none' : 'block') ?>; <?= ($item['hidden'] ? 'width: 0': '') ?>">
        <?php if($item['hidden']):?>
          <input name="<?= $l->name ?>_state[<?= $time ?>]" value="1" type="hidden"/>
        <?php else:?>
          <input name="<?= $l->name ?>_state[<?= $time ?>]" id="<?= $l->name ?>_state[<?= $time ?>]" value="1" type="checkbox" class="check"/>
          <span class="podlog2"></span>
          <label for="<?= $l->name ?>_state[<?= $time ?>]">
            <strong>&nbsp;<?= $item['title']?></strong> <?= $l->icon?>
          </label>
        <?php endif; ?>
      </li>
    <?php $i++; endforeach;?>
  </ul>
</div>
<?php endforeach; ?>
<script>
  $('body').on('change', '[name=type_reservation]', function () {
    if ($(this).val() == '2') {
      $('.webio-hidden').css("display", 'block');
    } else {
      $('.webio-hidden').css("display", 'none');
    }
  })
  $('body').on('change', '[name=numberOfPeriods]', function () {
    let val = $(this).val();
    $('.webio-hidden').each(function (){
      if ($(this).attr('data-hidden') <= val) {
        $(this).css("display", 'block');
      } else {
        $(this).css("display", 'none');
      }
    })
  })
</script>
