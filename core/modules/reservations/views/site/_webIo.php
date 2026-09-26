<?php
/**
 * @var array $webIo
 */
?>
<?php foreach ($webIo as $l): ?>
  <div class="display-block" style="margin: 15px 0;display: <?= ($l->hidden ? 'none' : 'block')?>">
    <div class="position-relative">
      <h5 style="font-size: 15px;"><?= $l->label?></h5>
      <div  style="display: flex; flex-wrap: wrap;align-items: center">
        <?php $i = 1; foreach ($l->times as $time => $item):?>
          <p class="position-relative <?= ($item['disable'] ? 'webio-hidden' : '') ?>" data-hidden="<?= $i ?>" style="margin: 4px 7px 4px;display: <?= ($item['disable'] ? 'none' : 'block') ?>">
            <?php if($item['hidden']):?>
              <input name="<?= $l->name ?>_state[<?= $time ?>]" value="1" type="hidden"/>
            <?php else:?>
              <input name="<?= $l->name ?>_state[<?= $time ?>]" id="<?= $l->name ?>_state[<?= $time ?>]" value="1" type="checkbox" class="check"/>
              <span class="podlog2"></span>
              <label for="<?= $l->name ?>_state[<?= $time ?>]">
                <strong>&nbsp;<?= $item['title']?></strong> <?= $l->icon?>
              </label>
            <?php endif;?>
          </p>
        <?php $i++; endforeach;?>
      </div>
    </div>
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

