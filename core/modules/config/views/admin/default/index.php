<?php
/**
 * @var ConfigModel $models
 * @var array       $active_type
 * @var View        $this
 * @var array       $variables - массив переданых с переменных
 */


use AC\app\config\ClientRestrictionConfig;
use AC\core\system\view\View;
use AC\core\modules\config\models\ConfigModel;

?>
<form action="config.php?mode=count&action=save" method="post" onsubmit="return ifConfirm ()">
  <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
    <tr>
      <td>
        <?= $this->render('min_max', compact($variables)) ?>
      </td>
    </tr>
    <tr>
      <td>
        <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
          <tr>
            <th><?= lang('title_parameter', 'config') ?></th>
            <th><?= lang('title_value', 'config') ?></th>
          </tr>

          <?php if (Service::auth()->checkRights(-1)): ?>
            <tr>
              <td class="dark" align="right"><?= lang('use 2-factor authentication', 'config_emails') ?></td>
              <td class="light"><input type="checkbox" name="email[use_2FA_admin]"
                                       value="1"<?= ($models->email->use_2FA_admin == 1 ?
                  ' checked' : '') ?>></td>
            </tr>
          <?php else: ?>
            <input type="hidden" name="email[use_2FA_admin]" value="<?= ($models->email->use_2FA_admin == 1 ?
              '1' : '0') ?>">
          <?php endif; ?>

          <tr>
            <td class="dark" align="right" rowspan="2"><?= lang('parameter_email_address_administrator', 'config') ?></td>
            <td class="light"><input type="text" class="input wide" name="email[admin_email]"
                                     value="<?= htmlspecialchars($models->email->admin_email,
                                       ENT_QUOTES) ?>"></td>
          </tr>
          <tr>
            <td class="light"><?= lang('text_please_enter_only_one_address', 'config') ?></td>
          </tr>
          <tr>
            <td class="dark" align="right" rowspan="2"><?= lang('parameter_email_address_for_inquiries', 'config') ?></td>
            <td class="light"><input type="text" class="input wide" name="email[notify_email]"
                                     value="<?= htmlspecialchars($models->email->notify_email,
                                       ENT_QUOTES) ?>"></td>
          </tr>
          <tr>
            <td class="light"><?= lang('text_multiple_address_possible_separated_by_commas', 'config') ?></td>
          </tr>
          <tr>
            <td class="dark" align="right"><?= lang('parameter_line_setting_email_subject', 'config') ?></td>
            <td class="light"><input type="text" class="input wide" name="email[email_subject_prefix]"
                                     value="<?= htmlspecialchars($models->email->email_subject_prefix,
                                       ENT_QUOTES) ?>"></td>
          </tr>
          <tr>
            <td class="dark" align="right"><?= lang('parameter_administrator_emails_desired', 'config') ?></td>
            <td class="light"><input type="checkbox" name="email[order_notify]"
                                     value="1"<?= ($models->email->order_notify == 1 ?
                ' checked' : '') ?>>
            </td>
          </tr>
          <?php foreach ($active_type as $item):
            $keyCurrentAlias = config('letterTemplates')->getAliasDisableSendMailClientReservation($item->current_alias);
            $checked         = config('letterTemplates')->checkSendMailByType($item->type_id)
            ?>
            <tr>
              <td class="dark" align="right"><?= lang('parameter_administrator_disable_send_mail_client_reservation', 'config',
                  ['title' => $item->title]) ?></td>
              <td class="light"><input type="checkbox" name="email[<?= $keyCurrentAlias ?>]"
                                       value="1" <?= ($checked ? ' checked' : '') ?>>
              </td>
            </tr>
          <?php endforeach ?>

          <tr>
            <th colspan="2"></th>
          </tr>
          <tr>
            <td class="dark" align="right"><?= lang('parameter_project_title', 'config') ?></td>
            <td class="light"><input type="text" name="common[project_title]"
                                     value="<?= $models->common->project_title ?? '' ?>">
            </td>
          </tr>
          <tr>
            <td class="dark" align="right"><?= lang('parameter_home_page', 'config') ?></td>
            <td class="light"><input type="text" name="common[home_page]"
                                     value="<?= $models->common->home_page ?? '' ?>">
            </td>
          </tr>
          <?php foreach (config('clientRestriction')->buildAdminFields() as $field):
            $typeKey = $field['typeKey'];
            $dbAlias = $field['dbAlias'];
            $fieldId = preg_replace('/[^A-Za-z0-9_]/', '_', $typeKey . '__' . $dbAlias);
            $valueRestriction = $field['value'];
            ?>
            <tr>
              <td class="dark" align="right">
                <?= htmlspecialchars($field['label'], ENT_QUOTES) ?>
              </td>
              <td class="light">
                <?php if ($field['input'] === 'booking_checkbox'): ?>
                  <input type="checkbox" class="check_<?= $fieldId ?>"
                         value="1" <?= ($valueRestriction === ClientRestrictionConfig::BOOKING_ALLOWED ? ' checked' : '') ?>>

                  <input type="hidden" class="hidden_<?= $fieldId ?>"
                         name="<?= htmlspecialchars($typeKey, ENT_QUOTES) ?>[<?= htmlspecialchars($dbAlias, ENT_QUOTES) ?>]"
                         value="<?= (int)$valueRestriction ?>">
                  <script>
                    $(".check_<?=$fieldId?>").on('click', function () {
                      let sel = $(this).prop('checked');
                      $('.hidden_<?= $fieldId ?>').val((sel) ? '<?= ClientRestrictionConfig::BOOKING_ALLOWED ?>' : '<?= ClientRestrictionConfig::BOOKING_STOPPED ?>');
                    });
                  </script>
                <?php else: ?>
                  <select name="<?= htmlspecialchars($typeKey, ENT_QUOTES) ?>[<?= htmlspecialchars($dbAlias, ENT_QUOTES) ?>]"
                          id="<?= $fieldId ?>">
                    <option value="0" <?= ($valueRestriction === 0 || $valueRestriction === null ? 'selected' : '') ?>>
                      - <?= lang('not active') ?> -
                    </option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <option value="<?= $i ?>" <?= ($valueRestriction === $i ? 'selected' : '') ?>>
                        <?= $i ?>
                      </option>
                    <?php endfor; ?>
                  </select>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <tr>
            <th colspan="2"><?= lang('title_door_codes_settings', 'config') ?></th>
          </tr>
          <tr>
            <td class="dark" align="right"><?= lang('parameter_number_of_codes_per_unit_time', 'config') ?></td>
            <td class="light"><input type="text" class="input" name="door[count_door_code]"
                                     value="<?= $models->door->count_door_code ?>"></td>
          </tr>
          <tr>
            <td class="dark">&nbsp;</td>
            <td class="light"><?= lang('text_number_of_codes_per_unit_time', 'config') ?>
            </td>
          </tr>
          <tr>
            <th colspan="2"><input type="submit" value="<?= lang('button_save') ?>" class="button"></th>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</form>

