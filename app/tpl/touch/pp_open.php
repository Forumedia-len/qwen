<!DOCTYPE html>
<html lang="<?= config('lang')->getCurrentLang() ?>">
<head>
  <?php if (defined('NOINDEX_SITE') && NOINDEX_SITE) { ?>
    <meta name="robots" content="noindex, indexifembedded">
  <?php } ?>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title><?= config('app')->getProjectTitle() ?></title>
  <link href="<?= BASE_HREF?>touchscreen/css/open_default.min.css" rel="stylesheet" type="text/css"/>
  <script type="text/javascript" src="<?= BASE_HREF ?>touchscreen/js/jquery.min.js"></script>
  <?= view()->renderer(paths()->getTplDir('_base_script.php', 'common'), get_defined_vars())?>
  <script type="text/javascript" src="<?= BASE_HREF ?>touchscreen/js/open/main.js"></script>
  <script type="text/javascript" src="<?= BASE_HREF ?>touchscreen/js/open/login.js"></script>
  <script type="text/javascript" src="<?= BASE_HREF ?>touchscreen/js/open/loading.js"></script>
  <script type="text/javascript" src="<?= BASE_HREF ?>touchscreen/js/keyboard.js"></script>
  <script type="text/javascript" src="<?= BASE_HREF ?>touchscreen/js/open/cards.js"></script>
  <script type="text/javascript" src="<?= BASE_HREF ?>touchscreen/js/open/cookie.js"></script>
  <script type="text/javascript" src="<?= BASE_HREF ?>assets/js/default.js"></script>
  <script>
    amount_cell = 0
    current_amount_cell = 0
    per_page = 4

    var cookie = new Cookie('scrolldata')
    if (cookie.hr)
      cookie.hr = 0
    if (cookie.vr)
      cookie.vr = 0
    cookie.store(1)
  </script>
</head>
<body onresize="windowSizeWork()">
<div id="pageLoaderBox">
  <img src="<?= BASE_HREF ?>touchscreen/images/page_loader.gif" alt="Loading"/><br/><br/>
  <?= lang('loading_please_wait_a_moment')?>
</div>
<div id="bodyBlock">
  <table id="bodyScheme">
    <tr class="header">
      <td>&nbsp;</td>
      <td align="center">
        <div class="arrows-button-bg"
             onclick="link('<?= BASE_HREF ?>touchscreen/reservations.php?type_id=2&sport_id=<?= OPEN_DEFAULT_SPORT_ID ?> ')"
             style="padding:0;margin: 10px; height:38px;cursor: pointer">
          <div class="green"
               style=" font-size: 20px; height:29px; padding:7px 0 0;">
            <?= lang('Day_view')?>
          </div>
        </div>
        <?php if (!$_COOKIE['one_court_type']) { ?>
          <div style="height: 38px">
            <a class="back_button_log" href="<?= BASE_HREF ?>touchscreen">
              <?= lang('back_to_the_selection_screen')?>
            </a>
          </div>
        <?php } ?>
      </td>
      <td>
        <div onclick="link('<?= BASE_HREF ?>touchscreen/index.php')" class="off_court_button">
          <div class="background_off_icon">

          </div>
        </div>
      </td>
    </tr>
    <tr>
      <td class="lateral">&nbsp;</td>
      <td>
        <div id="mainScheduleBlock" style="background:none">
          <div id="loginBlock">
            <?php
            if (isset($_page['content'][1])) {
              echo $_page['content'][1];
            }
            ?>
          </div>
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
</body>
</html>
