<div id="apply_cookie">
  <form id="apply_cookie_form">
    <div class="title">
      <?= lang('title', 'cookie') ?>
    </div>
    <div class="content-cook">
      <?= lang('message', 'cookie') ?>
    </div>
    <div class="apply_buttons">
      <div class="button-cook apply_ok"><?= lang('button_ok', 'cookie') ?></div>
      <div class="button-cook apply_all"><?= lang('button_all', 'cookie') ?></div>
    </div>
    <div class="apply_checked">
      <div class="checkline">
        <div class="check-cook"><input type="checkbox" id="chkneed" disabled checked name="need" value="Y">
          <label for="chkneed"><?= lang('check_needful', 'cookie') ?></label></div>
        <div class="check-cook" <?php if (empty($configCookies['preferences'])): ?>style="display:none"<?php endif ?>>
          <input type="checkbox" id="chkpref" name="pref" value="Y">
          <label for="chkpref"><?= lang('check_preferences', 'cookie') ?></label>
        </div>
        <div class="check-cook" <?php if (empty($configCookies['stat'])): ?>style="display:none"<?php endif ?>>
          <input type="checkbox" id="chkstat" name="stat" value="Y">
          <label for="chkstat"><?= lang('check_stat', 'cookie') ?></label>
        </div>
        <div class="check-cook" <?php if (empty($configCookies['marketing'])): ?>style="display:none"<?php endif ?>>
          <input type="checkbox" id="chkmark" name="mark" value="Y">
          <label for="chkmark"><?= lang('check_marketing', 'cookie') ?></label>
        </div>
      </div>
      <div class="button_show"><?= lang('button_detail', 'cookie') ?></div>
    </div>
    <div class="apply_type m-hidden">
      <div class="tabs-gorizontal">
        <div class="tabs" data-content="gorizontal">
          <div class="btn-tabs gorizontal active" data-id="1" style="left:0px">
            <div><?= lang('tabs_name_types', 'cookie') ?></div>
          </div>
          <div class="btn-tabs gorizontal" data-id="2" style="left:120px">
            <div><?= lang('tabs_name_about', 'cookie') ?></div>
          </div>
        </div>
        <div class="content-cook" data-content="gorizontal">
          <div data-tab="1" class="data-tabs gorizontal active">
            <div class="tabs-vertical">
              <div class="tabs" data-content="vertical">
                <?php $hIndex = 1 ?>
                <div class="btn-tabs vertical active" data-id="1" style="top:0"><?= lang('tabs_name_needful', 'cookie') ?></div>
                <div class="btn-tabs vertical " data-id="2"
                     style="<?php if (!empty($configCookies['preferences'])): ?>top:<?=$hIndex*32;?>px;<?php $hIndex++; else: echo 'display:none;'; endif; ?>"><?= lang(
                    'tabs_name_preferences',
                    'cookie'
                  ) ?></div>
                <div class="btn-tabs vertical " data-id="3"
                     style="<?php if (!empty($configCookies['stat'])): ?>top:<?=$hIndex*32;?>px;<?php $hIndex++; else: echo 'display:none;'; endif; ?>"><?= lang(
                    'tabs_name_stat',
                    'cookie'
                  ) ?></div>
                <div class="btn-tabs vertical " data-id="4"
                     style="<?php if (!empty($configCookies['marketing'])): ?>top:<?=$hIndex*32;?>px;<?php $hIndex++; else: echo 'display:none;'; endif; ?>"><?= lang(
                    'tabs_name_marketing',
                    'cookie'
                  ) ?></div>
                <?php /*<div class="btn-tabs vertical " data-id="5" style="top:128px"><?=lang('tabs_name_other', 'cookie')?></div>*/ ?>
              </div>
              <div class="content-cook" data-content="vertical">
                <div class="data-tabs vertical active" data-tab="1">
                  <div class="vertical-left"></div>
                  <div class="vertical-right"><?= lang('tabs_content_needful', 'cookie') ?><br/>
                    <table>
                      <thead>
                      <tr>
                        <th><?= lang('table_header_name', 'cookie') ?></th>
                        <th><?= lang('table_header_provider', 'cookie') ?></th>
                        <th><?= lang('table_header_purpose', 'cookie') ?></th>
                        <th><?= lang('table_header_expiration', 'cookie') ?></th>
                        <th><?= lang('table_header_type', 'cookie') ?></th>
                      </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($configCookies['need'] as $item): ?>
                        <tr>
                          <td><?= $item['name'] ?></td>
                          <td><?= $item['provider'] ?></td>
                          <td><?= $item['purpose'] ?></td>
                          <td><?= $item['expiration'] ?></td>
                          <td><?= $item['type'] ?></td>
                        </tr>
                      <?php endforeach ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="data-tabs vertical m-hidden" data-tab="2">
                  <div class="vertical-left"></div>
                  <div class="vertical-right"><?= lang('tabs_content_preferences', 'cookie') ?><br/>
                    <table>
                      <thead>
                      <tr>
                        <th><?= lang('table_header_name', 'cookie') ?></th>
                        <th><?= lang('table_header_provider', 'cookie') ?></th>
                        <th><?= lang('table_header_purpose', 'cookie') ?></th>
                        <th><?= lang('table_header_expiration', 'cookie') ?></th>
                        <th><?= lang('table_header_type', 'cookie') ?></th>
                      </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($configCookies['preferences'] as $item): ?>
                        <tr>
                          <td><?= $item['name'] ?></td>
                          <td><?= $item['provider'] ?></td>
                          <td><?= $item['purpose'] ?></td>
                          <td><?= $item['expiration'] ?></td>
                          <td><?= $item['type'] ?></td>
                        </tr>
                      <?php endforeach ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="data-tabs vertical m-hidden" data-tab="3">
                  <div class="vertical-left"></div>
                  <div class="vertical-right"><?= lang('tabs_content_stat', 'cookie') ?><br/>
                    <table>
                      <thead>
                      <tr>
                        <th><?= lang('table_header_name', 'cookie') ?></th>
                        <th><?= lang('table_header_provider', 'cookie') ?></th>
                        <th><?= lang('table_header_purpose', 'cookie') ?></th>
                        <th><?= lang('table_header_expiration', 'cookie') ?></th>
                        <th><?= lang('table_header_type', 'cookie') ?></th>
                      </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($configCookies['stat'] as $item): ?>
                        <tr>
                          <td><?= $item['name'] ?></td>
                          <td><?= $item['provider'] ?></td>
                          <td><?= $item['purpose'] ?></td>
                          <td><?= $item['expiration'] ?></td>
                          <td><?= $item['type'] ?></td>
                        </tr>
                      <?php endforeach ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="data-tabs vertical m-hidden" data-tab="4">
                  <div class="vertical-left"></div>
                  <div class="vertical-right"><?= lang('tabs_content_marketing', 'cookie') ?><br/>
                    <table>
                      <thead>
                      <tr>
                        <th><?= lang('table_header_name', 'cookie') ?></th>
                        <th><?= lang('table_header_provider', 'cookie') ?></th>
                        <th><?= lang('table_header_purpose', 'cookie') ?></th>
                        <th><?= lang('table_header_expiration', 'cookie') ?></th>
                        <th><?= lang('table_header_type', 'cookie') ?></th>
                      </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($configCookies['marketing'] as $item): ?>
                        <tr>
                          <td><?= $item['name'] ?></td>
                          <td><?= $item['provider'] ?></td>
                          <td><?= $item['purpose'] ?></td>
                          <td><?= $item['expiration'] ?></td>
                          <td><?= $item['type'] ?></td>
                        </tr>
                      <?php endforeach ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="data-tabs vertical m-hidden" data-tab="5">
                  <div class="vertical-left"></div>
                  <div class="vertical-right"><?= lang('tabs_content_other', 'cookie') ?><br/>
                    <table>
                      <thead>
                      <tr>
                        <th><?= lang('table_header_name', 'cookie') ?></th>
                        <th><?= lang('table_header_provider', 'cookie') ?></th>
                        <th><?= lang('table_header_purpose', 'cookie') ?></th>
                        <th><?= lang('table_header_expiration', 'cookie') ?></th>
                        <th><?= lang('table_header_type', 'cookie') ?></th>
                      </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($configCookies['other'] as $item): ?>
                        <tr>
                          <td><?= $item['name'] ?></td>
                          <td><?= $item['provider'] ?></td>
                          <td><?= $item['purpose'] ?></td>
                          <td><?= $item['expiration'] ?></td>
                          <td><?= $item['type'] ?></td>
                        </tr>
                      <?php endforeach ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="data-tabs gorizontal m-hidden" data-tab="2"><?= lang('about_cookies', 'cookie') ?></div>
        </div>
      </div>
    </div>
  </form>
</div>
<script>
  var clickok = function () {
    let arr = $('#apply_cookie_form').serializeArray()
    arr.push({name: 'need', value: 'Y'})
    $('#apply_cookies_saver_form').addClass('close')
    setTimeout(function () {$('#apply_cookies_saver_form').css('display', 'none')}, 600)
    let cook = JSON.stringify(arr)
    //console.log(cook);
    setCookie('user_apply_saver_cookie', cook, false, false, 3600 * 24 * 365)
  }

  var clickall = function () {
    let arr = [{name: 'need', value: 'Y'}, {name: 'pref', value: 'Y'}, {name: 'stat', value: 'Y'}, {name: 'mark', value: 'Y'}]
    $('#apply_cookies_saver_form').addClass('close')
    setTimeout(function () {$('#apply_cookies_saver_form').css('display', 'none')}, 600)
    let cook = JSON.stringify(arr)
    setCookie('user_apply_saver_cookie', cook, false, false, 3600 * 24 * 365)
  }

  $('#apply_cookies_saver').html('')
  $('#apply_cookies_saver').attr('id', 'apply_cookies_saver_form')
  $('#apply_cookies_saver_form').html('<?php echo $form_apply?>')
  $('#apply_cookie_form .apply_ok').click(clickok)
  $('#apply_cookie_form .apply_all').click(clickall)

  $('.tabs-gorizontal .gorizontal.btn-tabs').click(function () {
    if (!$(this).hasClass('active')) {
      let id = $(this).attr('data-id')
      let old_id = $('.tabs-gorizontal .gorizontal.btn-tabs.active').attr('data-id')
      $('#apply_cookie_form .gorizontal.data-tabs[data-tab=' + old_id + ']').removeClass('active').addClass('m-hidden')
      $('#apply_cookie_form .gorizontal.data-tabs[data-tab=' + id + ']').removeClass('m-hidden').addClass('active')
      $('.tabs-gorizontal .gorizontal.btn-tabs.active').removeClass('active')
      $(this).addClass('active')
    }
  })

  $('.tabs-vertical .vertical.btn-tabs').click(function () {
    if (!$(this).hasClass('active')) {
      let id = $(this).attr('data-id')
      let old_id = $('.tabs-vertical .vertical.btn-tabs.active').attr('data-id')
      $('#apply_cookie_form .vertical.data-tabs[data-tab=' + old_id + ']').removeClass('active').addClass('m-hidden')
      $('#apply_cookie_form .vertical.data-tabs[data-tab=' + id + ']').removeClass('m-hidden').addClass('active')
      $('.tabs-vertical .vertical.btn-tabs.active').removeClass('active')
      $(this).addClass('active')
    }
  })

  $('#apply_cookie_form .button_show').click(function () {
    $(this).toggleClass('open')
    $('.apply_type').toggleClass('m-hidden')
  })

</script>

