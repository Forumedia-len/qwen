<!DOCTYPE html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
  <title><?= config('app')->getProjectTitle() ?><?= (isset($_page['title']) ? ' - ' . $_page['title'] : '') ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <?php if (isset($show) && $show == 'ok'): ?>
    <meta http-equiv="refresh" content="10;url=<?= Service::structure()->getPageHrefByKey('index') ?>">
  <?php endif; ?>
  <?= view()->renderer(paths()->getTplDir('_style'), get_defined_vars()) ?>
  <?= view()->renderer(paths()->getTplDir('_script'), get_defined_vars()) ?>
</head>
<body onresize="windowSizeWork()">
<div id="pageLoaderBox">
  <img src="<?= base_url(paths()->getAssetsDir('images/page_loader.gif')) ?>" alt="Loading"/><br/><br/>
  <?= lang('loading_please_wait_a_moment')?>
</div>
<div id="bodyBlock">
  <table id="bodyScheme">
    <tr class="header" style="height: 120px">
      <td>&nbsp;</td>
      <td align="center">
        <div class="arrows-button-bg"
             onclick="link('reservations.php?action=selectSport&type_id=2')"
             style="padding:0;margin: 10px; height:38px;cursor: pointer">
          <div class="green"
               style=" font-size: 20px; height:29px; padding:7px 0 0;">
            <?= lang('Day_view')?>
          </div>
        </div>
        <?php if (isset($_COOKIE['one_court_type']) && !$_COOKIE['one_court_type']) { ?>
          <div style="height: 38px">
            <a class="back_button_log" href="<?= site_url() ?>">
              <?= lang('back_to_the_selection_screen')?>
            </a>
          </div>
        <?php } ?>
      </td>
      <td>
        <div onclick="link('index.php')" class="off_court_button">
          <div class="background_off_icon">

          </div>
        </div>
      </td>
    </tr>
    <tr>
      <td class="lateral">&nbsp;</td>
      <td>
        <div id="mainScheduleBlock" style="<?= (isset($_page['mainScheduleBlock']['style']) ? $_page['mainScheduleBlock']['style'] : '')?>">
          <?php
          if (isset($_page['content'][1])) {
            echo $_page['content'][1];
          }
          ?>
        </div>
      </td>
      <td class="lateral">&nbsp;</td>
    </tr>
    <tr class="footer">
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
  </table>
</div>
<?= view()->renderer(paths()->getTplDir('_time_reload'), get_defined_vars()) ?>
</body>
</html>

