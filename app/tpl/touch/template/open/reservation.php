<?php

use AC\core\system\helpers\TranslateHelper;

?>
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
  <script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/open/ajaxloadmodule.js')) ?>"></script>
  <script type="text/javascript" src="<?= base_url(paths()->getAssetsDir('js/open/popup.js')) ?>"></script>
</head>
<body onresize="windowSizeWork()">

<div id="pageLoaderBox">
  <img src="<?= base_url(paths()->getAssetsDir('images/page_loader.gif')) ?>" alt="Loading"/><br/><br/>
  <?= lang('loading_please_wait_a_moment')?>
</div>
<div id="bodyBlock">
  <table id="bodyScheme">
    <tr class="header">
      <td>
        <?php $date = Service::request()->_('date', date('Y-m-d')) ?>
        <div class="manage-button-bg" onclick="viewHideCalendar('view')">
          <span class="f28"><?= date('d', strtotime($date)) ?></span><br/><?= mb_substr(
            TranslateHelper::translateMonth(date('n', strtotime($date))),
            0,
            3,
            'UTF-8'
          ) ?></div>
        <div id="calendarWindow">
          <div class="close-button" onclick="viewHideCalendar('hide')"></div>
          <?php if (isset($_page['content'][0])) {
            echo $_page['content'][0];
          }
          ?>
        </div>
      </td>
      <td>
        <?php if ($_page['template'] == 'reservation' && Service::request()->_('action','')!='selectSport'): ?>
          <div onclick="scrollSchedule('vr', 'back')" class="arrows-button-bg">
          <div style="background:url(<?= base_url(paths()->getAssetsDir('images/icons_sprite.png')) ?>) center -234px no-repeat"></div>
          </div><? endif ?>
      </td>
      <td style="float: right;padding-top: 26px;">
        <div onclick="link('login.php')" class="manage-button-bg">
          <div style="background:url(<?= base_url(paths()->getAssetsDir('images/icons_sprite.png')) ?>) center -407px no-repeat;"></div>
        </div>
      </td>
    </tr>
<!--    <tr class="body-select">-->
<!--      <td colspan="3">-->
        <?php
        if (isset($_page['content'][1])) {
          echo $_page['content'][1];
        }
        ?>
<!--      </td>-->
<!--    </tr>-->
    <tr class="footer">
      <td>&nbsp;</td>
      <td><?php if ($_page['template'] == 'reservation' && Service::request()->_('action','')!='selectSport'): ?>
          <div onclick="scrollSchedule('vr', 'forward')" class="arrows-button-bg">
          <div style="background:url(<?= base_url(paths()->getAssetsDir('images/icons_sprite.png')) ?>) center -278px no-repeat"></div>
          </div><?php endif ?></td>
      <td>&nbsp;</td>
    </tr>
  </table>
</div>
<?= view()->renderer(paths()->getTplDir('_time_reload'), get_defined_vars()) ?>
</body>
</html>
