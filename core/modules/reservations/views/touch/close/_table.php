<?php
/**
 *  Вывод таблицы расписания для кортов
 * @var array       $sports_tabs    - все спорты этого типа площадок
 * @var array       $pages_tabs     - вкладки для страниц страницы
 * @var array       $weeks_tabs     - вкладки для недельного и обычного расписания
 * @var int         $sport_id       - идентификатор текущего спорта
 * @var int         $type_id        - тип корта
 * @var int         $client_id      - индикатор зарегистрированного клиента
 * @var null|object $client         - данные клиента
 * @var bool        $client_bar     - данные клиента
 * @var int         $on_reservation - возможность бронировать этот день
 * @var string      $date           - дата
 * @var int         $page           - номер страницы
 * @var array       $areas          - площадки
 * @var bool        $week           - недельный отчет показывать
 * @var View        $this
 * @var array       $params
 * @var string      $courtType
 * @var string      $title_S_T
 */

use AC\core\system\view\View;

?>
<?php if (($client === null || !$client_bar)) { ?>
  <?php
  $member = Service::request()->_('member');
  $price  = Service::request()->_('price');
  ?>
  <script>
    $(document).ready(function () {
      $('#mySelect').change(function () {
        if ($('#mySelect :selected').val() == '0' && $('#user_right_button').attr('checked')) {
          $('.period-price').css('display', 'inline-block')
          $('.period-sale').css('display', 'none')
        } else if ($('#mySelect :selected').val() == '1' && $('#user_right_button').attr('checked')) {
          $('.period-price').css('display', 'none')
          $('.period-sale').css('display', 'inline-block')
        } else if ($('#mySelect :selected').val() == '0') {
          $('.period-price').css('display', 'none')
          $('.period-sale').css('display', 'none')
        } else if ($('#mySelect :selected').val() == '1') {
          $('.period-price').css('display', 'none')
          $('.period-sale').css('display', 'none')
        }
      })
      $('#user_right_button').click(function () {
        if ($(this).attr('checked') && $('#mySelect :selected').val() == '0') {
          $('.period-price').css('display', 'inline-block')
          $('.period-sale').css('display', 'none')
        } else if ($(this).attr('checked') && $('#mySelect :selected').val() == '1') {
          $('.period-price').css('display', 'none')
          $('.period-sale').css('display', 'inline-block')
        } else {
          $('.period-price').css('display', 'none')
          $('.period-sale').css('display', 'none')
        }
      })
      $('[name=price]').click(function () {
        if ($(this).prop('checked')) window.location.href = window.location.href + '&price=1'
        if (!$(this).prop('checked')) window.location.href = window.location.href.replace('&price=1', '')
      })
    })
  </script>
  <div class="contik">
    <?php
    echo('<form id="myForm2">');
    if ($price) {
      echo('<label>'.lang('Show prices', 'show_order').'</label><input name="price" id="user_right_button" type="checkbox" value="1" class="check" checked style="width: 50px;height: 46px;top: 25px;left: 27px;"><span class="podlog3"></span>');
    } else {
      echo('<label>'.lang('Show prices', 'show_order').'</label><input name="price" id="user_right_button" type="checkbox" value="1" class="check" style="width: 50px;height: 46px;top: 25px;left: 27px;"><span class="podlog3"></span>');
    }
    echo('<select id="mySelect">');
    if ($member == 1) {
      echo('<option value="1" selected>'.lang('Member').'</option>');
    } else {
      echo('<option value="1">'.lang('Member').'</option>');
    }
    if ($member == 0) {
      echo('<option value="0" selected>'.lang('Non-member').'</option>');
    } else {
      echo('<option value="0">'.lang('Non-member').'</option>');
    }
    echo('</select>');
    echo('</form>');
    ?>
  </div>
<?php } ?>
<div id="loginForm">
  <div id="section_img_tennis" style="width: 50%">
    <h1><?= $title_S_T ?> <span><?= date('d.m.Y', strtotime($date)) ?></span></h1>
    <div id="content">
      <?php echo langByAreaType('info_text_above_timetable', $courtType, $type_id); ?>
    </div>
  </div>
  <br>
  <?php
  switch ($week) {
    case 0:
      echo $this->render($courtType . '/_columns', $params);
      break;
    case 1:
      echo $this->render($courtType . '/_week', $params);
      break;
  }
  ?>
  <div style="clear:both;"></div>
  <div class="legend-field">
    <div class="row">
      <div class="legend-field-title col-sm-3">
        <span><?= lang('legend_cort1')?>: </span>
      </div>
      <div class="col-sm-8">
        <div>
          <span class="ordered col-sm-3 col-xs-12"><?= lang('legend_cort2')?></span>
          <span class="blocked col-sm-5 col-xs-12"><?= lang('legend_cort3')?></span>
          <span class="own col-sm-4 col-xs-12"><?= lang('legend_cort4')?></span>
        </div>
      </div>
    </div>
  </div>
  <div style="clear:both;"></div>
</div>