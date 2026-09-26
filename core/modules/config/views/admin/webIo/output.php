<?php
/**
 * @var array      $areas         - массив моделей AreasModel
 * @var WebIoModel $model
 * @var array      $webIo_types   - массив типов приборов для webIo - (освещение, отопление, сетка)
 * @var array      $webIo_outputs - массив портов с данными для прибора
 */


use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\webIo\models\WebIoModel;

?>
<style>
  .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
    white-space: nowrap !important;
  }

  .select2-container--default.select2-container--focus .select2-selection--multiple,
  .select2-container--default .select2-selection--multiple {
    min-width: 220px;
  }
</style>
<form action="config.php?mode=webIo" method="post">
  <input type="hidden" name="action" value="updateOutput">
  <input type="hidden" name="webio_id" value="<?= $model->id?>">
  <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
    <tr>
      <th colspan="4"><?= lang('title_webIo_output_setting', 'webIo_output') ?></th>
    </tr>
    <tr>
      <th><?= lang('title_webIo_output_port', 'webIo_output') ?></th>
      <th><?= lang('title_name_area', 'webIo_output') ?></th>
      <th><?= lang('title_type_device', 'webIo_output') ?></th>
      <th><?= lang('title_url_output', 'webIo_output') ?></th>
    </tr>
    <?php
    // порты webIo от 0 до 11
    for ($port = 0; $port < $model->count_ports; $port++) { ?>
      <tr>
        <td class="<?= ($port % 2 == 0 ? 'dark' : 'light') ?>">Output <?= $port ?> :</td>
        <td class="<?= ($port % 2 == 0 ? 'dark' : 'light') ?>" width="225px">
          <select name="area[<?= $port ?>][]" id="MultipleSelectBox_<?= $port ?>" multiple>
            <option value="0"></option>
            <?php
            /** @var AreasModel $area */ ?>
            <?php
            foreach ($areas as $area) { ?>
              <option value="<?= $area->area_id ?>" <?= (isset($webIo_outputs[$port]) && in_array(
                $area->area_id,
                (array)$webIo_outputs[$port]['area_id']
              ) ? 'selected' : '') ?> >
                <?= $area->type_title . ' - ' . $area->sport_title . ' - ' . $area->title ?>
              </option>
              <?php
            } ?>
          </select>
        </td>
        <td class="<?= ($port % 2 == 0 ? 'dark' : 'light') ?>">
          <select name="type[<?= $port ?>]" id="">
            <option value="0"></option>
            <?php
            /** @var AreasModel $area */ ?>
            <?php
            foreach ($webIo_types as $type) {
              $type = (object)$type;
              ?>
              <option
                value="<?= $type->id ?>" <?= (isset($webIo_outputs[$port]) && $webIo_outputs[$port]['webio_type_id'] == $type->id ? 'selected' : '') ?>>
                <?= $type->title ?>
              </option>
              <?php
            } ?>
          </select>
        </td>
        <?
        $fileName="";
        if(MC_ARENA){
          $arr_type = [
        1 => "licht",   // Освещение
        2 => "heizen",  // Отопление
        3 => "netz"      // Сеть
        ];
          $h_arr=explode('.',HOST_NAME);
          $arr_id = $webIo_outputs[$port]['area_id'][0];
          $type_alias=$arr_type[$webIo_outputs[$port]['webio_type_id']];
          $fileName = "{$h_arr[0]}_{$type_alias}_{$arr_id}.ical";
        }
        ?>
        <td class="<?= ($port % 2 == 0 ? 'dark' : 'light') ?>">
          <?= (isset($webIo_outputs[$port]) ?
            '<strong>' . lang('Default') . '</strong> : ' . BASE_HREF . 'at/ical_webio_generate.php?area_id=' . implode( ',', (array)$webIo_outputs[$port]['area_id'] ) . '&type=' . $webIo_outputs[$port]['webio_type_id'] . '<br>' .
            '<strong>' . lang('Old') . '</strong> : ' . 'https://ssl3.forumedia.eu/' . HOST_NAME. '/at/ical_webio_generate.php?area_id=' . implode( ',', (array)$webIo_outputs[$port]['area_id'] ) . '&type=' . $webIo_outputs[$port]['webio_type_id']
            .(!empty($fileName)?('<br/><strong>' . lang('File') . '</strong> :' . " ".$fileName):"") 
                    : '') ?>
        </td>
      </tr>
      <?php
    } ?>
    <tr>
      <th colspan="4"><input type="submit" value="<?= lang('button_generate') ?>" class="button"></th>
    </tr>
  </table>
</form>
<script>
  for (let i = 0; i < 12; i++) {
    $('#MultipleSelectBox_' + i).select2()
  }
</script>

