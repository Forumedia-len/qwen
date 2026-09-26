<?php

use AC\app\config\CookieApplyConfig;
use AC\core\modules\text\engines\TextEngine;

/** @var  CookieApplyConfig $configCookies */
$configCookies = config('cookieApply');

if (!$configCookies->checkCookiesApplied()) {
  \Service::lang()->addFile('cookie');
  ?>
  <script>
    let LISTCOOK_NEED = <?= json_encode(array_values($configCookies->need)) ?>;
    let LISTCOOK_PREF = <?= json_encode(array_values($configCookies->preferences)) ?>;
    let LISTCOOK_STAT = <?= json_encode(array_values($configCookies->stat)) ?>;
    let LISTCOOK_MARK = <?= json_encode(array_values($configCookies->marketing)) ?>;
  </script>
  <?php
  if ($configCookies->checkNewVersionView()) {
    $useOther = $configCookies->checkUseOtherCookies();
    ?>
    <div id="apply_cookies_saver_form">
      <div id="apply_cookie">
        <form id="apply_cookie_form">
          <div class="title">
            <?= lang('title', 'cookie') ?>
          </div>
          <div class="content-cook">
            <?php
            /** @var TextEngine $cookie */
            $cookie = getEngine('text', false);
            $cookie?->getContent($text_cookie, 'cookie');
            if (empty($text_cookie['content'])) {
              $text_cookie['content'] = lang('message', 'cookie');
            }
            echo $text_cookie['content'];
            ?>
          </div>
          <div class="apply_buttons">
            <div class="button-cook apply_ok" <?= (!$useOther ? ' style="display:none"':'')?>>
              <?= lang('button_ok', 'cookie') ?></div>
            <div class="button-cook apply_all"><?= !$useOther ? lang('button_ok', 'cookie') : lang('button_all', 'cookie') ?></div>
          </div>
          <div class="apply_checked">
            <div class="checkline">
              <div class="check-cook"><input type="checkbox" id="chkneed" disabled checked name="need" value="Y">
                <label for="chkneed"><?= lang('check_needful', 'cookie') ?></label></div>
              <?php if (!$configCookies->useLightVersionOtherCookies()): ?>
                <div class="check-cook" <?php if (!count($configCookies->preferences)): ?>style="display:none"<?php endif ?>>
                  <input type="checkbox" id="chkpref" name="pref" value="Y">
                  <label for="chkpref"><?= lang('check_preferences', 'cookie') ?></label>
                </div>
                <div class="check-cook" <?php if (!count($configCookies->stat)): ?>style="display:none"<?php endif ?>>
                  <input type="checkbox" id="chkstat" name="stat" value="Y">
                  <label for="chkstat"><?= lang('check_stat', 'cookie') ?></label>
                </div>
                <div class="check-cook" <?php if (!count($configCookies->marketing)): ?>style="display:none"<?php endif ?>>
                  <input type="checkbox" id="chkmark" name="mark" value="Y">
                  <label for="chkmark"><?= lang('check_marketing', 'cookie') ?></label>
                </div>
              <?php else: ?>
                <div class="check-cook" <?php if (!count($configCookies->other)): ?>style="display:none"<?php endif ?>>
                  <input type="checkbox" id="chother" name="other" value="Y">
                  <label for="chother"><?= lang('check_other', 'cookie') ?></label>
                </div>
              <?php endif; ?>
            </div>
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
                           style="<?php if (!empty($configCookies->preferences)): ?>top:<?=$hIndex*32;?>px;<?php $hIndex++; else: echo 'display:none;'; endif; ?>"><?= lang(
                          'tabs_name_preferences',
                          'cookie'
                        ) ?></div>
                      <div class="btn-tabs vertical " data-id="3"
                           style="<?php if (!empty($configCookies->stat)): ?>top:<?=$hIndex*32;?>px;<?php $hIndex++; else: echo 'display:none;'; endif; ?>"><?= lang(
                          'tabs_name_stat',
                          'cookie'
                        ) ?></div>
                      <div class="btn-tabs vertical " data-id="4"
                           style="<?php if (!empty($configCookies->marketing)): ?>top:<?=$hIndex*32;?>px;<?php $hIndex++; else: echo 'display:none;'; endif; ?>"><?= lang(
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
                            <?php foreach ($configCookies->need as $item): ?>
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
                            <?php foreach ($configCookies->preferences as $item): ?>
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
                            <?php foreach ($configCookies->stat as $item): ?>
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
                            <?php foreach ($configCookies->marketing as $item): ?>
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
                            <?php foreach ($configCookies->other as $item): ?>
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
    </div>
  <?php } else {
    /** @var TextEngine $cookie */
    $cookie = getEngine('text', false);
    $cookie?->getContent($text_cookie, 'cookie');
    if (empty($text_cookie['content'])) {
      $text_cookie['content'] = '<p><strong>WICHTIG:</strong> Wir nutzen ausschliesslich systemrelevante Cookies, die f&uuml;r den Betrieb der Website notwendig sind. <strong>Es werden keine Daten zu statistischen Zwecken, Marketing etc. durch Cookies erhoben.</strong> Deshalb gibt es hier auch keine weitere Auswahl. <strong>Viel Spa&szlig; bei Ihrem Sport.</strong></p>';
    }
    ?>
    <div id="apply_cookies_saver">
      <div class="container">
        <?= $text_cookie['content'] ?>
      </div>
      <div class="container"><a id="button_apply" style="">OK</a></div>
    </div>
    <?php
  }
}