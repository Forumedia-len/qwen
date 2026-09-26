<!DOCTYPE html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
  <title><?= config('app')->getProjectTitle() ?><?= (isset($_page['title']) ? ' - ' . $_page['title'] : '') ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <?= view()->renderer(paths()->getTplDir('_style'), get_defined_vars()) ?>
  <?= view()->renderer(paths()->getTplDir('_script'), get_defined_vars()) ?>
  <script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/popup_window.js')) ?>"></script>
  <script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/reservations_form.js')) ?>"></script>
  <script type="text/javascript">
    $(document).ready(function () {
      $('.sale_period').css('display', 'block')

      $('.largeCalendarRunArea').click(function () {
        $('#shadow').show()
      })
      $('#shadow,#calendarLarge #close_calen').click(function () {
        $('#shadow').hide()
        location.href = window.location.href
      })
      $('#calendarLarge #close_calen').click(function () {
        $('#calendarLarge').hide()
      })
      <?
      if (isset($_GET['click']) && $_GET['click'] == 1) {?>
      $('#calendarLarge').css('display', 'block')
      $('#shadow').show()
      <?
      }
      ?>

      $('.aroundBox.active').click(function () {
        // console.log($('#shadow'))
        $('#shadow').show()
      })
    })
  </script>

</head>
<body>
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin:20px auto;">
  <tr>
    <?= view()->renderer(paths()->getTplDir('template/close/_menu.php'), get_defined_vars()) ?>
    <td valign="top" id="section_right">
      <?php
      if ($_page['content'][0]) { ?>
        <div id="but_calen" class="button largeCalendarRunArea" onclick="showLargeCalendar (event)">
          <img src="<?= base_url(paths()->getAssetsDir('images/calen.png'))?>"><?= lang('change_date', 'calendar')?>
        </div>
        <?= $_page['content'][0];
      }
      ?>
      <?php
        if (isset($_page['content'])) {
          echo $_page['content'][1];
        }
      ?>
    </td>
  </tr>
</table>
<?= view()->renderer(paths()->getTplDir('_time_reload'), get_defined_vars()) ?>
</body>
</html>
