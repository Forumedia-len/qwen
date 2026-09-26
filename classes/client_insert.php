<?php

use AC\app\config\CountryConfig;
use AC\app\config\LangConfig;
use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\engines\Engines;
use AC\core\system\helpers\StringHelper;

class client_insert
{

  static function getClientForm($form_action, $mode, $row = [])
  {
    //параметры
    if ($mode == 0) {
      $title    = lang('Add clients', 'clients');
      $tabindex = 0;
    } elseif ($mode == 1) {
      $title    = lang('Changing clients', 'clients');
      $tabindex = 0;
    } elseif ($mode == 2) {
      $title    = lang('New client', 'clients');
      $tabindex = 2;
    }

    $out = '';

    //открытие формы
    if ($form_action !== null) {
      $out .= '<form action="' . $form_action . '" method="post" name="insertClient">' . "\n";
    }
    foreach (self::maskedFields() as $field => $param) {
      $out .= '<input type="hidden" name="oldMaskedFields[' . $field . ']" value="' . $row[$field] . '">';
    }

    $out .= '<table border="0" width="1020" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" class="main" align="center">' . "\n";
    $out .= '	<tr>' . "\n";
    $out .= '		<th colspan="4">' . $title . '</th>' . "\n";
    $out .= '	</tr>' . "\n";
    if ($mode == 1) {
      $out .= '	<tr>' . "\n";
      $out .= '		<td class="dark">' . lang('Registered on') . ':</td>' . "\n";
      $out .= '		<td class="dark" colspan="3">' . date('d.m.Y H:i:s', strtotime($row['registered'])) . '</td>' . "\n";
      $out .= '	</tr>' . "\n";
    }
    $out .= '	<tr>' . "\n";
    $out .= '		<td class="light" width="25%">' . lang('Client') . ':</td>' . "\n";
    $out .= '		<td class="light" width="25%">' . "\n";
    $out .= '			<table cellpadding="1" cellspacing="0" border="0">' . "\n";
    $out .= '				<tr>' . "\n";
    $out .= '					<td><input type="radio" name="mode" tabindex="' . ($tabindex + 1) . '" value="1"' . (!isset ($row['mode']) || $row['mode'] == 1
        ? ' checked' : '') . '/></td>' . "\n";
    $out .= '					<td>' . lang('Online') . '</td>' . "\n";
    $out .= '					<td>&nbsp;</td>' . "\n";
    $out .= '					<td class="light"><input type="checkbox" tabindex="' . ($tabindex + 2) . '" name="active" value="1"' . (isset($row['active']) && $row['active'] == 1
        ? ' checked' : '') . '/></td>' . "\n";
    $out .= '					<td class="light">' . lang('Active') . '</td>' . "\n";
    $out .= '				</tr>' . "\n";
    $out .= '				<tr>' . "\n";
    foreach (ConfigClubStateModel::getMarks() as $item) {
      $out .= '                  <td style="white-space:nowrap" class="light"><input type="radio" name="club_state" tabindex="' . ($tabindex + 2) . '" value="' . $item->id . '"' . (isset($row['club_state']) && ($item->id == $row['club_state']) || $item->id == 1
          ? ' checked' : '') . '/></td>' . "\n";
      $out .= '                  <td>' . $item->title . '</td>' . "\n";
      $out .= '                  <td>&nbsp;</td>' . "\n";
    }

    $out .= '				</tr>' . "\n";
    $out .= '				<tr>' . "\n";
    $out .= '					<td><input type="radio" name="encash" value="1" ' . (!isset($row['encash'])
        ? 'checked'
        : ($row['encash'] == 1 ? 'checked'
          : '')) . ' /></td>' . "\n";
    $out .= '					<td>' . lang('Invoice payment') . '</td>' . "\n";
    $out .= '					<td>&nbsp;</td>' . "\n";
    $out .= '					<td><input type="radio" name="encash" value="0" ' . ((isset($row['encash']) && $row['encash'] == 0) ? 'checked'
        : '') . '/></td>' . "\n";
    $out .= '					<td>' . lang('Cash payment') . '</td>' . "\n";
    $out .= '					<td>&nbsp;</td>' . "\n";
    $out .= '					<td><input type="radio" name="encash" value="2" ' . ((isset($row['encash']) && $row['encash'] == 2) ? 'checked'
        : '') . '/></td>' . "\n";
    $out .= '					<td>' . lang('Credit balance') . '</td>' . "\n";
    $out .= '				</tr>' . "\n";
    $out .= '				<tr class="dark">' . "\n";
    $out .= '				<td><input type="checkbox" name="super" value="1" ' . ((isset($row['super']) && $row['super'] == 1) ? 'checked'
        : '') . ' /></td>' . "\n";
    $out .= '					<td colspan="6">' . lang('Admin bookings', 'clients') . '</td>' . "\n";
    $out .= '				</tr>' . "\n";
    if (Service::query()::getDB()->checkField('show_client_data', 'clients')) {
      $out .= '				<tr><td colspan="6">
				<input type="checkbox" id="show_client_data" value="1" name="show_client_data"' . (((isset($row['show_client_data']) && $row['show_client_data'] == 1) || empty($row))
          ? 'checked' : '') . '>
				<label for="show_client_data"> ' . lang('Credit balance is visible', 'registration_fields') . '</label>
				</td></tr>' . "\n";
    }

    $out .= '				<tr>
				              <td><input type="checkbox" name="abo_delete" value="1"' .
      ((isset($row['abo_delete']) && $row['abo_delete'] == 1) || (!isset($row['abo_delete']) && Service::configDB('registration',
          'user_can_remove_abo'))
        ? ' checked' : '') . '/></td>
				              <td colspan="6">' . lang('parameter_user_can_remove_abo', 'config') . '</td>
				            </tr>' . "\n";
    $out .= '				<tr>
				              <td><input type="checkbox" name="refund_for_ticket" value="1"'
      . ((isset($row['refund_for_ticket']) && $row['refund_for_ticket'] == 1) || (!isset($row['refund_for_ticket']) && Service::configDB('personal_account',
          'refund_when_cancel_ticket'))
        ? ' checked' : '') . '/></td>
				              <td colspan="6">' . lang('parameter_refund_when_cancel_ticket', 'config') . '</td>
				            </tr>' . "\n";
    if (config('payment')->useOnlinePayment()) {
      $out .= '				<tr>
				              <td><input type="checkbox" name="refund_for_paypal" value="1"'
        . ((isset($row['refund_for_paypal']) && $row['refund_for_paypal'] == 1) || (!isset($row['refund_for_paypal']) && Service::configDB('personal_account',
            'refund_for_cancellation_reservation_paid_by_paypal'))
          ? ' checked' : '') . '/></td>
				              <td colspan="6">' . lang('parameter_refund_for_cancellation_reservation_paid_by_paypal', 'config') . '</td>
				            </tr>' . "\n";
    }

    $out .= '			</table>' . "\n";
    $out .= '		</td>' . "\n";
    $out .= '		<td class="light" colspan="2" style="padding: 5px 0 0; vertical-align: baseline" disabled="disabled">'
      . (config('membershipFees')->useMembershipFees()
        ? module('membershipFees', ['mode' => 'client_data', 'useRouting' => false, 'params' => ['client' => $row]])->execContent('viewForm')
        : '') . '</td>' . "\n";
    $out .= '	</tr>' . "\n";
    // языки
    /** @var LangConfig $confLang */
    $confLang = config('lang');
    if ($confLang->getActiveLanguages() > 1 && $confLang->detectLanguageColumn('clients', 'lang')) {
      $out .= '		<tr class="light">' . "\n";
      $out .= '		  <td class="light" width="25%">' . lang('Languages') . ':</td>' . "\n";
      $out .= '		  <td class="light"><table><tr>';
      foreach ($confLang->getActiveLanguages() as $ilang => $lang) {
        if ($ilang % 4 == 0) {
          $out .= '</tr><tr>';
        }
        $out .= '<td><input type="radio" name="lang" value="' . $lang . '"' . ((isset($row['lang']) && $row['lang'] == $lang) || $confLang->getDefault() == $lang ? ' checked' : '') . '/> ' . ucfirst($lang) . '</td>';
      }
      $out .= '     </tr></table></td>' . "\n";
      $out .= '		  <td class="light" colspan="2">&nbsp;</td>' . "\n";
      $out .= '	</tr>' . "\n";
    }
    $out .= '<tr><td class="dark"></td><td class="dark">' . self::generateUnavailableSportsField($row) . '</td><td class="dark" width="25%">' . lang('Mg.no') . ':</td>' . "\n";
    $out .= '		<td class="dark" width="25%">
		  <input type="text" tabindex="' . ($tabindex + 3) . '" size="20" name="number" class="input small" value="' . (isset($row['number'])
        ? htmlspecialchars(
          $row['number'],
          ENT_QUOTES
        ) : '') . '"/></td></tr>' . "\n";

    if (!MC_ARENA) {
      $out .= '				<tr>
				<td class="dark" colspan="1"></td>
				<td class="dark" colspan="1"><input type="checkbox" name="student" value="1"' . (isset($row['student']) && $row['student'] == 1 ? ' checked'
          : '') . '/> ' . lang('parameter_registration_field_student', 'registration_fields') . '</td>
				<td class="dark" colspan="1">' . lang('parameter_registration_field_student_number', 'registration_fields') . ':</td>
				<td class="dark" colspan="1"><input type="text" name="student_number" class="input small" value="' . (isset($row['student_number'])
          ? htmlspecialchars(
            $row['student_number'],
            ENT_QUOTES
          ) : '') . '"/></td></tr>' . "\n";
    }
    $out .= '	<tr>' . "\n";
    $out .= '		<td colspan="4" class="light"><b>&nbsp;</b></td>' . "\n";
    $out .= '	</tr>' . "\n";

    $out .= '	<tr>' . "\n";
    $out .= '		<td class="dark">' . lang(
        'parameter_registration_field_login',
        'registration_fields'
      ) . '<span style="color:red">*</span>:</td>' . "\n";
    $out .= '		<td class="dark" colspan="3">';
    if (!isset($row['login'])) {
      $out .= '<input type="text" tabindex="' . ($tabindex + 4) . '" size="30" name="login" class="input small" value=""/>';
    } else {
      $out .= '<span tabindex="' . ($tabindex + 4) . '"  />' . htmlspecialchars($row['login'], ENT_QUOTES) . '</span>';
      $out .= '<input type="hidden" name="login" value="' . htmlspecialchars($row['login'], ENT_QUOTES) . '">';
    }

    $out .= '</td>' . "\n";
    $out .= '	</tr>' . "\n";
    $out .= '	<tr>' . "\n";
    $out .= '		<td class="dark">' . lang(
        'parameter_registration_field_name',
        'registration_fields'
      ) . '<span style="color:red">*</span>:</td>' . "\n";
    $out .= '		<td class="dark"><input type="text" tabindex="' . ($tabindex + 5) . '" size="30" name="name" class="input small" value="' . (isset($row['name'])
        ? htmlspecialchars(
          $row['name'],
          ENT_QUOTES
        ) : '') . '"/></td>' . "\n";
    $out .= '		<td class="dark">' . lang('parameter_registration_field_birthday', 'registration_fields') . ':</td>' . "\n";
    if (isset ($row['birthday']) && $row['birthday'] != null) {
      $tmp      = explode('-', $row['birthday']);
      $birthday = $tmp[2] . '.' . $tmp[1] . '.' . $tmp[0];
    } else {
      $birthday = '';
    }
    $out .= '		<td class="dark"><input type="text" tabindex="' . ($tabindex + 15) . '" size="30" name="birthday" class="input small datepicker" data-year-range="-100:+0" value="' . $birthday . '"></td>' . "\n";

    $out .= '	</tr>' . "\n";
    $out .= '	<tr>' . "\n";

    $out .= '		<td class="dark">' . lang(
        'parameter_registration_field_surname',
        'registration_fields'
      ) . '<span style="color:red">*</span>:</td>' . "\n";
    $out .= '		<td class="dark"><input type="text" tabindex="' . ($tabindex + 6) . '" size="30" name="surname" class="input small" value="' . (isset($row['surname'])
        ? htmlspecialchars(
          $row['surname'],
          ENT_QUOTES
        ) : '') . '"></td>' . "\n";
    $out .= '		<td class="dark">' . lang('parameter_registration_field_post_code', 'registration_fields') . ':</td>' . "\n";
    $out .= '		<td class="dark"><input type="text" tabindex="' . ($tabindex + 12) . '" size="20" name="post_code" class="input small" value="' . (isset($row['post_code'])
        ? htmlspecialchars(
          $row['post_code'],
          ENT_QUOTES
        ) : '') . '"/></td>' . "\n";
    $out .= '	</tr>' . "\n";

    $out .= '	<tr>' . "\n";
    $out .= '	</tr>' . "\n";

    $out .= '	<tr>' . "\n";
    $out .= '		<td class="light">' . lang('parameter_registration_field_phone', 'registration_fields') . ':</td>' . "\n";
    $out .= '		<td class="light"><input type="text" tabindex="' . ($tabindex + 7) . '" size="20" name="phone" class="input small" value="' . (isset($row['phone'])
        ? htmlspecialchars(
          $row['phone'],
          ENT_QUOTES
        ) : '') . '"></td>' . "\n";
    $out .= '   </td>' . "\n";
    $out .= '		<td class="light">' . lang('parameter_registration_field_city', 'registration_fields') . ':</td>' . "\n";
    $out .= '		<td class="light"><input type="text" tabindex="' . ($tabindex + 13) . '" size="20" name="city" class="input small" value="' . (isset($row['city'])
        ? htmlspecialchars(
          $row['city'],
          ENT_QUOTES
        ) : '') . '"/></td>' . "\n";
    $out .= '	</tr>' . "\n";

    $out .= '	<tr>' . "\n";
    $out .= '		<td class="light">' . lang('parameter_registration_field_phone_mobile', 'registration_fields') . ':</td>' . "\n";
    $out .= '		<td class="light"><input type="text" tabindex="' . ($tabindex + 9) . '" size="30" name="phone_mobile" class="input small" value="' . (isset($row['phone_mobile'])
        ? htmlspecialchars(
          $row['phone_mobile'],
          ENT_QUOTES
        ) : '') . '"></td>' . "\n";
    $out .= '		<td class="light">' . lang('Address') . ':</td>' . "\n";
    $out .= '		<td class="light"><input type="text" name="address" tabindex="' . ($tabindex + 14) . '" class="input small" value="' . (isset($row['address'])
        ? htmlspecialchars(
          $row['address'],
          ENT_QUOTES
        ) : '') . '"></td>' . "\n";
    $out .= '	</tr>' . "\n";

    $out .= '	<tr>' . "\n";
    $out .= '		<td class="light">' . lang('parameter_registration_field_fax', 'registration_fields') . ':</td>' . "\n";
    $out .= '		<td class="light"><input type="text" tabindex="' . ($tabindex + 10) . '" size="30" name="fax" class="input small" value="' . (isset($row['fax'])
        ? htmlspecialchars(
          $row['fax'],
          ENT_QUOTES
        ) : '') . '"></td>' . "\n";
    $out .= '		<td class="light">' . lang('parameter_registration_field_firm', 'registration_fields') . ':</td>' . "\n";
    $out .= '		<td class="light">';
    if (defined('USE_FIRM_FIELD_REGISTRATION_AS_SELECT') && USE_FIRM_FIELD_REGISTRATION_AS_SELECT) {
      $firmValues = explode(';', USE_FIRM_FIELD_REGISTRATION_AS_SELECT);
      $out        .= '<select name="firm" class="select_new">';
      $out        .= '<option value="" ' . (empty($row['firm']) ? ' selected' : '') . '></option>';
      foreach ($firmValues as $firmValue) {
        $out .= '<option value="' . $firmValue . '" ' . (isset($row['firm']) && $row['firm'] == $firmValue ? ' selected' : '') . '>' . $firmValue . '</option>' . "\n";
      }
      $out .= '</select>';
    } else {
      $out .= '<input type="text" tabindex="' . ($tabindex + 6) . '" size="60" name="firm" class="input small" value="' . (isset($row['firm'])
          ? htmlspecialchars(
            $row['firm'],
            ENT_QUOTES
          ) : '') . '"/>';
    }

    $out .= '	</td>' . "\n";
    $out .= '	</tr>' . "\n";
    $out .= '	<tr>' . "\n";
    $out .= '		<td class="dark">' . lang('parameter_registration_field_email', 'registration_fields') . ':</td>' . "\n";
    $out .= '		<td class="dark"><input type="text" tabindex="' . ($tabindex + 11) . '" size="30" name="email" class="input small" value="' . (isset($row['email'])
        ? $row['email'] : '') . '"></td>' . "\n";
    // Поле для выбора страны - если есть таблица и поле
    /** @var CountryConfig $countryConfig */
    $countryConfig = config('country');
    if ($countryConfig->detectTable() && Service::query()::getDB()->checkField('country', 'clients')) {
      $out .= '		<td class="dark">' . lang('parameter_registration_field_country', 'registration_fields') . ':</td>' . "\n";
      $out .= '		<td class="dark">' . useLayout()->render('select', [
          'name'    => 'country',
          'values'  => $countryConfig->getCountries(),
          'current' => $row['country'] ?? $countryConfig->getDefaultCode(),
        ], 'common') . '</td>' . "\n";
    } else {
      $out .= '	<td colspan="2" class="dark"></td>' . "\n";
    }

    $out .= '	</tr>' . "\n";
    $out .= '	<tr>' . "\n";
    $out .= '		<td colspan="4" class="light"><b>' . lang('parameter_registration_field_password', 'registration_fields') . '</b>' . ($mode == 1
        ? '<br>' . lang('Leave the fields blank if you do not want to change your password', 'clients') : '') . '</td>' . "\n";
    $out .= '	</tr>' . "\n";
    $out .= '	<tr>' . "\n";
    $out .= '		<td class="light">' . lang('parameter_registration_field_password', 'registration_fields') . ':</td>' . "\n";
    $out .= '		<td class="light"><input type="password" tabindex="' . ($tabindex + 17) . '" size="20" name="password" class="input small" value="' . (isset($row['password'])
        ? $row['password'] : '') . '"/></td>' . "\n";
    $out .= '		<td class="light">' . lang('parameter_registration_field_password_confirmation', 'registration_fields') . ':</td>' . "\n";
    $out .= '		<td class="light"><input type="password" tabindex="' . ($tabindex + 18) . '" size="20" name="password_confirmation" class="input small"value="' . (isset($row['password'])
        ? $row['password_confirmation'] : '') . '"/></td>' . "\n";
    $out .= '	</tr>' . "\n";
    if (USE_PAYMENT_INVOICE) {
      //---------- реквизиты банка----------
      $out .= '	<tr>' . "\n";
      $out .= '		<td colspan="4" class="dark"><b>&nbsp;</b></td>' . "\n";
      $out .= '	</tr>' . "\n";
      $out .= '	<tr>' . "\n";
      $out .= '		<td class="light">' . lang('parameter_registration_field_account_owner', 'registration_fields') . ':</td>' . "\n";
      $out .= '		<td class="light"><input type="text" tabindex="' . ($tabindex + 19) . '" size="20" name="bank_account_holder" class="input small" value="' . (isset($row['account_owner'])
          ? htmlspecialchars(
            $row['account_owner'],
            ENT_QUOTES
          ) : '') . '"/></td>' . "\n";
      $out .= '		<td class="light">' . lang('parameter_registration_field_bank_name', 'registration_fields') . ':</td>' . "\n";
      $out .= '		<td class="light"><input type="text" tabindex="' . ($tabindex + 22) . '" size="20" name="bank_name" class="input small" value="' . (isset($row['bank_name'])
          ? htmlspecialchars(
            $row['bank_name'],
            ENT_QUOTES
          ) : '') . '"/></td>' . "\n";
      $out .= '	</tr>' . "\n";
//    $out .= '	<tr>' . "\n";
//    $out .= '		<td class="light">Bankleitzahl:</td>' . "\n";
//    $out .= '		<td class="light"><input type="text" tabindex="' . ($tabindex + 21) . '" size="20" name="bank_identifier_code" class="input small" value="' . htmlspecialchars($row['bank_index'],
//        ENT_QUOTES) . '"/></td>' . "\n";
//    $out .= '		<td class="light">Kontonummer:</td>' . "\n";
//    $out .= '		<td class="light"><input type="text" tabindex="' . ($tabindex + 20) . '" size="20" name="bank_account_number" class="input small" value="' . htmlspecialchars($row['account_number'],
//        ENT_QUOTES) . '"/></td>' . "\n";
//    $out .= '	</tr>' . "\n";

      //-- SEPA BEGIN
      $out .= '	<tr>' . "\n";
      $out .= '		<td colspan="4" class="dark"><b>' . mb_strtoupper(
          lang('parameter_registration_field_group_sepa_data', 'registration_fields')
        ) . '</b></td>' . "\n";
      $out .= '	</tr>' . "\n";

      $out  .= '	<tr>' . "\n";
      $out  .= '		<td class="light">' . lang('parameter_registration_field_bank_iban', 'registration_fields') . ':</td>' . "\n";
      $iban = StringHelper::shield($row['bank_iban'] ?? '');
      $out  .= '		<td class="light" colspan="3"><input type="text" tabindex="' . ($tabindex + 19) . '" maxlength="34" name="bank_iban" id="iban-field" data-iban-value="' . $iban . '" class="input small" value="'
        . $iban . '"/><span id="test-iban-field"></span>
		' . lang('Text about the test Iban', 'registration_fields') . '
								</td>' . "\n";
      $out  .= '	</tr><tr>' . "\n";
      $out  .= '		<td class="dark">' . lang('parameter_registration_field_bank_bic', 'registration_fields') . ':</td>' . "\n";
      $out  .= '		<td class="dark" colspan="3"><input type="text" tabindex="' . ($tabindex + 20) . '" maxlength="11" name="bank_bic" class="input small" id="bic-field" value="' . (isset($row['bank_bic'])
          ? htmlspecialchars(
            $row['bank_bic'],
            ENT_QUOTES
          ) : '') . '"/>
		<br />' . lang('text about bic', 'registration_fields') . '
		</td>' . "\n";
      $out  .= '	</tr>' . "\n";
      $out  .= '	<tr>' . "\n";
      $out  .= '		<td class="light">' . lang('parameter_registration_field_bank_sepa_mandat', 'registration_fields') . ':</td>' . "\n";

      $sepa_mandat = isset($row['bank_sepa_mandat']) ? (new DateTimeImmutable($row['bank_sepa_mandat']))->format('d.m.Y') : null;
      $out         .= '		<td class="light"><input type="text" tabindex="' . ($tabindex + 21) . '" size="20" name="bank_sepa_mandat" class="input small datepicker"
        value="' . ($sepa_mandat && empty(DateTimeImmutable::getLastErrors()) ? $sepa_mandat : '') . '"/></td>' . "\n";

      $reference = StringHelper::shield($row['bank_sepa_referenz'] ?? '');
      if ($reference == $iban) {
        $reference = StringHelper::mask($reference, self::maskedFields()['bank_sepa_referenz'][0], self::maskedFields()['bank_sepa_referenz'][1],
          'X');
      }
      $out .= '		<td class="light">' . lang('parameter_registration_field_bank_sepa_reference', 'registration_fields') . ':</td>' . "\n";
      $out .= '		<td class="light"><input type="text" tabindex="' . ($tabindex + 22) . '" size="20" name="bank_sepa_referenz" class="input small" value="' . $reference . '"/></td>' . "\n";
      $out .= '	</tr>' . "\n";
      //-- SEPA END
    }


    //---------- Скидки и НДС -------------------
    $_r  = new Engines();
    $out .= '	<tr>' . "\n";
    $out .= '		<td colspan="4" class="dark"><b>' . lang('VAT', 'registration_fields') . ' / ' . lang(
        'Discount',
        'registration_fields'
      ) . '</b></td>' . "\n";
    $out .= '	</tr>' . "\n";
    $out .= '	<tr>' . "\n";
    $out .= '		<td class="light">' . lang('VAT', 'registration_fields') . ':</td>' . "\n";
    $out .= '		<td class="light"><select tabindex="' . ($tabindex + 23) . '" name="nds" class="input small">' . "\n";
    if ($_r->nds->getNdss($nds)) {
      foreach ($nds as $n) {
        $out .= '<option value="' . $n['nds_id'] . '" ' . (!empty($row['nds'])
            ? ($n['nds_id'] == $row['nds'] ? 'selected' : '')
            : ($n['set_default']
              ? 'selected' : '')) . '>' . $n['rate'] . '% ' . htmlspecialchars(
            $n['comment'],
            ENT_QUOTES
          ) . '</option>' . "\n";
      }
    }
    $out .= '		</select></td>' . "\n";
    $out .= '		<td class="light">' . lang('Discount', 'registration_fields') . ':</td>' . "\n";
    $out .= '		<td class="light"><select tabindex="' . ($tabindex + 24) . '" name="discount" class="input small">' . "\n";
    if ($_r->discount->getDiscounts(0, $discount)) {
      $out .= '<option value="null">' . lang('No') . '</option>' . "\n";
      foreach ($discount as $d) {
        $out .= '<option value="' . $d['discount_id'] . '" ' . (isset($row['discount']) && $d['discount_id'] == $row['discount'] ? 'selected'
            : '') . '>' . htmlspecialchars(
            $d['title'],
            ENT_QUOTES
          ) . ' (' . lang('Single') . ':' . number_format($d['retail'], 2, ',', '') . ' ' . ($d['dimension'] == 1 ? '%' : CURR_VALUTE) . ' | ' . lang(
            'Abo'
          )
          . ':' . number_format($d['ticket'], 2, ',', '')
          . ' ' . ($d['dimension'] == 1
            ? '%' : CURR_VALUTE) . ')</option>' . "\n";
      }
    }
    $out .= '		</select></td>' . "\n";
    $out .= '	</tr>' . "\n";

    //---------- количество дней на сколько чел может заказывать вперед -------------------
    $follow_days = 5;
    $out         .= '	<tr>' . "\n";
    $out         .= '		<td class="dark">' . lang('Booking restriction in days', 'clients') . ':</td>' . "\n";
    $out         .= '		<td class="dark"><select name="limit_day" class="input small">' . "\n";
    $out         .= '<option value="0" ' . ((isset($row['limit_day']) && $row['limit_day'] == 0) ? 'selected'
        : '') . ' > - ' . lang('not active', 'clients') . ' -</option>' . "\n";
    for ($i = 0; $i < $follow_days; $i++) {
      $out .= '<option value="' . ($i + 1) . '" ' . ((isset($row['limit_day']) && $row['limit_day'] == ($i + 1)) ? 'selected'
          : '') . ' >' . ($i + 1) . '</option>' . "\n";
    }
    $out .= '		</select></td>' . "\n";
    $out .= '		<td class="dark">&nbsp;</td>' . "\n";
    $out .= '		<td class="dark">&nbsp;</td>' . "\n";
    $out .= '	</tr>' . "\n";

    //---------- Леттер коды -------------------
    $out .= '	<tr>' . "\n";
    $out .= '		<td class="light">' . lang('Special price') . ':</td>' . "\n";
    $out .= '		<td class="light">';

    $out .= '<table border="0" cellpadding="0" cellspacing="0">' . "\n";
    if ($_r->specprice->getSprices($stocks)) {
      foreach ($stocks as $stock) {
        $out .= '<tr><td style="text-align: center">' . ((isset($stock['for_all']) && $stock['for_all'] == '1')
            ? '<i class="fas fa-check"></i>'
            : ('<input type="checkbox" name="sprice_id[]" value="' . $stock['sprice_id'] . '" ' . (((isset($row['sprice_id']) && !empty($row['sprice_id']) && in_array(
                  $stock['sprice_id'],
                  $row['sprice_id']
                )) ? 'checked' : '')) . ' />')) . '</td><td>[' . $stock['code'] . '] ' . $stock['title'] . ' <strong>' . number_format(
            $stock['rate'],
            2,
            ',',
            ''
          ) . ' ' . CURR_VALUTE . '</strong></td></tr>' . "\n";
      }
    }
    $out .= '</table>' . "\n";
    $out .= '		</td>' . "\n";
    $out .= '		<td class="light">' . lang('parameter_registration_field_stock', 'registration_fields') . ':</td>' . "\n";
    $out .= '		<td class="light">' . "\n";
    $out .= '<table border="0" cellpadding="0" cellspacing="0">' . "\n";
    if ($_r->stocks->getStocks($stocks)) {
      foreach ($stocks as $stock) {
        $out .= '<tr><td style="text-align: center">' . ((isset($stock['for_all']) && $stock['for_all'] == '1')
            ? '<i class="fas fa-check"></i>'
            : ('<input type="checkbox" name="stock_id[]" value="' . $stock['stock_id'] . '" ' . ((isset($row['stock_id']) && !empty($row['stock_id']) && in_array(
                  $stock['stock_id'],
                  $row['stock_id']
                )) ? 'checked' : '') . ' />')) . '</td><td>[' . $stock['code'] . '] ' . $stock['title'] . ' <strong>' . number_format(
            $stock['rate'],
            2,
            ',',
            ''
          ) . ' ' . ($stock['dimension'] == 2 ? '%' : CURR_VALUTE) . '</strong></td></tr>' . "\n";
      }
    }
    $out .= '</table>' . "\n";
    $out .= '</td>' . "\n";
    $out .= '	</tr>' . "\n";

    if ($mode != 2) {
      $out .= '	<tr>' . "\n";
      $out .= '		<th colspan="4">' . "\n";
      $out .= '			<input type="submit" tabindex="' . ($tabindex + 25) . '" value="' . ($mode == 0 || $mode == 2 ? lang('button_create')
          : lang('button_update')) . '" class="button" style="width:100px"> &nbsp;' . "\n";
      $out .= '			<input type="reset" tabindex="' . ($tabindex + 26) . '" value="' . lang(
          'button_reset'
        ) . '" class="button" style="width:100px">' . "\n";
      $out .= '		</th>' . "\n";
      $out .= '	</tr>' . "\n";
    } else {
      $out .= '	<tr>' . "\n";
      $out .= '		<th colspan="4">&nbsp;</th>' . "\n";
      $out .= '	</tr>' . "\n";
    }

    $out .= '</table>' . "\n";

    //закрытие формы
    if ($form_action !== null) {
//      $out .= '<script>initializeInsertClientForm("insertClient")</script>' . "\n";
      $out .= '</form>' . "\n";
    }

    return $out;
  }

  static function generateUnavailableSportsField($data = [])
  {
    $out                = '';
    $unavailable_sports = explode(';', $data["unavailable_sports"] ?? '');
    $i                  = 1;
    if (!MC_ARENA) {
      $out .= '<table border="0" align="center" class="wide" style="margin: 0 -3px;min-width: 500px">';
    }
    foreach (module('clients')
               ->useModel()->getEngine()
               ->availableSportByTypeBySeason($unavailable_sports, !isset($data['client_id'])) as $key => $value) {
      if (!MC_ARENA) {
        if ($i > 2) { // по три спорта в строку
          $out .= '</tr><tr class="dark">' . "\n";
          $i   = 1;
        }
        if ($i == 1) {
          $out .= '<tr class="dark">' . "\n";
        }
        $out .= '<td style="white-space:nowrap; width: 20px"><input type="checkbox" name="available_sport[]" value="' . $key . '" '
          . ($value['active'] ? 'checked' : '') . '/></td>' . "\n";
        $out .= '<td>' . $value['title'] . '</td><td></td>' . "\n";
      } elseif ($value['active']) {
        $out .= '<input type="hidden" name="available_sport[]" value="' . $key . '" ' . '/>' . "\n";
      }
      $i++;
    }
    if (!MC_ARENA) {
      $out .= '</td></tr></table>';
    }

    return $out;
  }

  private static function maskedFields(): array
  {
    return ['bank_iban' => [5, 3], 'bank_sepa_referenz' => [7, 2, 'bank_iban']];
  }

  /**
   * @param $mode   0 client 1 admin
   * @param $engine Engines
   * @param $message
   * @param $new_client_id
   *
   * @return bool
   */
  static function proceedInsertClient($mode, &$engine, &$message, &$new_client_id)
  {
    if (client_insert::checkClientFormRecieving($mode)) {
      $area_type = 0;
      if (isset($_POST['area_type'])) {
        $area_type = (int)$_POST['area_type'];
      }
      if (isset($_POST['area_type1'])) {
        $area_type = (int)$_POST['area_type1'];
      }

      if (isset($_POST['area_type2'])) {
        $area_type = ($area_type == 0 ? (int)$_POST['area_type2'] : 0);
      }

      if ($_POST['bank_sepa_mandat'] != '') {
        $a = explode('.', $_POST['bank_sepa_mandat']);
        if (count($a) == 3) {
          $bank_sepa_mandat = $a[2] . '-' . $a[1] . '-' . $a[0];
        } else {
          $bank_sepa_mandat = null;
        }
      } else {
        if (isset($_POST['bank_sepa_mandat_day']) && isset($_POST['bank_sepa_mandat_month']) && isset($_POST['bank_sepa_mandat_year'])) {
          $bank_sepa_mandat = $_POST['bank_sepa_mandat_year'] . '-' . $_POST['bank_sepa_mandat_month'] . '-' . $_POST['bank_sepa_mandat_day'];
        } else {
          $bank_sepa_mandat = null;
        }
      }

      //Т.к с клиента мы получаем список только отмеченных(Доступных) спортов, то генерим список недоступных кортов на их базе
      $unavailable_sports = module('clients')->useModel('clientsRegistration')->getUnavailableSportByTypeBySeason($_POST['available_sport']);

      if ($engine->clients->insertClient(
        $mode,
        $_POST['mode'],
        $area_type,
        (int)isset($_POST['active']),
        (int)$_POST['club_state'],
        (int)$_POST['encash'],
        isset ($_POST['login']) ? $_POST['login'] : null,
        isset ($_POST['password']) ? $_POST['password'] : null,
        isset ($_POST['password_confirmation']) ? $_POST['password_confirmation'] : null,
        isset ($_POST['number']) ? $_POST['number'] : '',
        $_POST['name'],
        $_POST['surname'],
        $_POST['birthday'],
        $_POST['phone'],
        $_POST['phone_mobile'],
        $_POST['fax'],
        $_POST['post_code'],
        $_POST['city'],
        $_POST['address'],
        $_POST['email'],
        (int)$_POST['nds'],
        (int)$_POST['discount'],
        [
          $_POST['bank_account_holder'],
          (isset($_POST['bank_account_number']) ? $_POST['bank_account_number'] : ''),
          (isset($_POST['bank_identifier_code']) ? $_POST['bank_identifier_code'] : ''),
          $_POST['bank_name'],
        ],
        [
          $_POST['bank_iban'],
          $_POST['bank_bic'],
          $bank_sepa_mandat,
          $_POST['bank_sepa_referenz'],
          (isset($_POST['sepa_type']) ? $_POST['sepa_type'] : 0),
          (isset($_POST['sepa_standart']) ? $_POST['sepa_standart'] : 0),
        ],
        (isset($_POST['limit_day']) ? (int)$_POST['limit_day'] : 0),
        ((isset($_POST['stock_id']) && is_array($_POST['stock_id'])) ? $_POST['stock_id'] : false),
        ((isset($_POST['sprice_id']) && is_array($_POST['sprice_id'])) ? $_POST['sprice_id'] : false),
        $unavailable_sports,
        isset($_POST['super']),
        $data_check_results,
        $new_client_id,
        isset($_POST['abo_delete']),
        isset($_POST['student']) ? $_POST['student'] : '0',
        isset($_POST['student_number']) ? $_POST['student_number'] : '',
        isset($_POST['firm']) ? $_POST['firm'] : '',
        isset($_POST['refund_for_ticket']),
        isset($_POST['refund_for_paypal']),
        $_POST['lang'] ?? null,
        $_POST['country'] ?? null,
        (int)isset($_POST['show_client_data'])
      )
      ) {
        //все ok
        $message = '<p align="center"><b>' . lang('The client has been registered!', 'message_success') . '</b></p>';

        return true;
      } else {
        //ошибка добавления
        $new_client_id = null;
        $message       = client_insert::explainClientDataCheckResult($data_check_results);

        return false;
      }
    } else {
      //не все входодные данные пришли
      $new_client_id = null;
      $message       = '<p class="red"><b>' . lang('New client cannot be added.', 'message_error') . "\n" . '</b></p>'
        . '<p class="red"><b>' . lang('Required POST variables are not presented!', 'message_error') . '</b></p>';

      return false;
    }
  }

  //выдать текст ошибки для некорректных данных
  static function explainClientDataCheckResult($r)
  {
    if ($r[0] == '1') {
      $o[] = lang('error_attribute_min_value', 'message_error', [
        'attribute' => lang('parameter_registration_field_login', 'registration_fields'),
        'value'     => 6,
      ]);
    } elseif ($r[0] == '2') {
      $o[] = lang('error_attribute_unique_login', 'message_error', [
        'attribute' => lang('parameter_registration_field_login', 'registration_fields'),
      ]);
    } elseif ($r[0] == '3') {
      $o[] = lang('error_attribute_type_login', 'message_error', [
        'attribute' => lang('parameter_registration_field_login', 'registration_fields'),
        'min'       => 6,
        'max'       => 20,
      ]);
    } elseif ($r[0] == '4') {
      $o[] = lang('error_attribute_max_value', 'message_error', [
        'attribute' => lang('parameter_registration_field_login', 'registration_fields'),
        'value'     => 20,
      ]);
    }

    if ($r[1] == '1') {
      $o[] = lang('error_attribute_min_value', 'message_error', [
        'attribute' => lang('parameter_registration_field_password', 'registration_fields'),
        'value'     => 6,
      ]);
    } elseif ($r[1] == '2') {
      $o[] = lang('error_attribute_confirmation', 'message_error', [
        'attribute'    => lang('parameter_registration_field_password', 'registration_fields'),
        'confirmation' => lang('parameter_registration_field_password_confirmation', 'registration_fields'),
      ]);
    }

    if ($r[2] == '1') {
      $o[] = lang('error_attribute_min_value', 'message_error', [
        'attribute' => lang('parameter_registration_field_name', 'registration_fields'),
        'value'     => 3,
      ]);
    }

    if ($r[3] == '1') {
      $o[] = lang('error_attribute_min_value', 'message_error', [
        'attribute' => lang('parameter_registration_field_surname', 'registration_fields'),
        'value'     => 3,
      ]);
    }

    if ($r[4] == '1') {
      $o[] = lang('error_attribute_incorrect', 'message_error', [
        'attribute' => lang('parameter_registration_field_birthday', 'registration_fields'),
      ]);
    }

    if ($r[5] == '1') {
      $o[] = lang('Please enter at least one telephone number', 'message_error');
    }

    if ($r[6] == '1') {
      $o[] = lang('PEmail not correct!', 'message_error');
    }

    if ($r[7] == '1') {
      $o[] = lang('error_attribute_type_required', 'message_error', [
        'attribute' => lang('parameter_registration_field_post_code', 'registration_fields'),
      ]);
    }

    if ($r[8] == '1') {
      $o[] = lang('error_attribute_type_required', 'message_error', [
        'attribute' => lang('parameter_registration_field_city', 'registration_fields'),
      ]);
    }

    if ($r[9] == '1') {
      $o[] = lang('error_attribute_type_required', 'message_error', [
        'attribute' => lang('Address'),
      ]);
    }

    if ($r[10] == '1') {
      $o[] = lang('error_attribute_type_required', 'message_error', [
        'attribute' => lang('parameter_registration_field_account_owner', 'registration_fields'),
      ]);
    }

    if ($r[13] == '1') {
      $o[] = lang('error_attribute_type_required', 'message_error', [
        'attribute' => lang('parameter_registration_field_bank_name', 'registration_fields'),
      ]);
    }

    if ($r[14] == '1') {
      $o[] = lang('error_attribute_type_required', 'message_error', [
        'attribute' => lang('parameter_registration_field_bank_iban', 'registration_fields'),
      ]);
    }
    if ($r[15] == '1') {
      $o[] = lang('error_attribute_type_required', 'message_error', [
        'attribute' => lang('parameter_registration_field_bank_bic', 'registration_fields'),
      ]);
    }

    return '<p class="red"><b>' . lang('text_error') . '</b><br>' . join('<br>', $o) . '</p>';
  }

  //восстановить введенные значения формы с полями клиента
  static function getClientFormEnteredValues()
  {
    $result = [];
    foreach (
      [
        'mode',
        'active',
        'club_state',
        'encash',
        'number',
        'login',
        'name',
        'surname',
        'birthday',
        'birthday_year',
        'birthday_month',
        'birthday_day',
        'phone',
        'phone_mobile',
        'fax',
        'city',
        'post_code',
        'address',
        'email',
        'nds',
        'discount',
        'bank_account_holder',
        'bank_account_number',
        'bank_identifier_code',
        'bank_name',
        'bank_iban',
        'bank_bic',
        'bank_sepa_mandat_year',
        'bank_sepa_mandat_month',
        'bank_sepa_mandat_day',
        'bank_sepa_referenz',
        'sepa_type',
        'stock_id',
        'sprice_id',
        'limit_day',
        'password',
        'password_confirmation',
        'student',
        'student_number',
        'firm',
      ] as $v
    ) {
      if (isset ($_POST[$v])) {
        if ($v == 'bank_account_holder') {
          $result['account_owner']       = $_POST[$v];
          $result['bank_account_holder'] = $_POST[$v];
        } else {
          if ($v == 'bank_account_number') {
            $result['account_number'] = $_POST[$v];
          } else {
            if ($v == 'bank_identifier_code') {
              $result['bank_index'] = $_POST[$v];
            } else {
              $result[$v] = $_POST[$v];
            }
          }
        }
      }
    }

    return $result;
  }

  //проверить наличие всех POST переменных формы
  //$mode
  //	0 client
  //	1 admin
  static function checkClientFormRecieving($mode)
  {
    if ($mode && ($old = Service::request()->_post('oldMaskedFields', null))) {
      foreach (self::maskedFields() as $field => $param) {
        $new = str_replace(' ', '', $_POST[$field]);
        if ($new == $old[$field] || $new == StringHelper::mask($old[$field], $param[0], $param[1], 'X')) {
          $_POST[$field] = $old[$field];
        }
      }
    }

    if ((isset ($_POST['name']) && empty(trim($_POST['name']))) ||
      (isset ($_POST['login']) && empty(trim($_POST['login']))) ||
      !isset ($_POST['name']) ||
      !isset ($_POST['login'])) {
      return false;
    }

    return isset ($_POST['mode']) && isset ($_POST['encash']) && (
        ((int)$_POST['mode'] == 1 && isset ($_POST['number']) && isset ($_POST['login']) && isset ($_POST['password']) && isset ($_POST['password_confirmation'])) ||
        ((int)$_POST['mode'] == 2 && isset ($_POST['number']))
      ) &&
      isset ($_POST['name']) && isset ($_POST['surname']) &&
      isset ($_POST['phone']) && isset ($_POST['phone_mobile']) &&
      isset ($_POST['fax']) &&
      isset ($_POST['post_code']) && isset ($_POST['city']) && isset ($_POST['address']) &&
      isset ($_POST['email']) &&
      isset ($_POST['birthday']) &&
      (self::checkPaymentMethod() || ($mode == 0 && isset ($_POST['bank_iban']) && isset ($_POST['bank_bic']) && isset ($_POST['bank_sepa_mandat_year']) && isset ($_POST['bank_sepa_mandat_month']) && isset ($_POST['bank_sepa_mandat_day']) && isset ($_POST['bank_sepa_referenz'])) || $mode == 1) &&
      ((self::checkPaymentMethod() || ($mode == 0 && isset ($_POST['bank_account_holder']) && isset ($_POST['bank_account_number']) && isset ($_POST['bank_identifier_code']) && isset ($_POST['bank_name'])) || $mode == 1)
      );
  }

  static public function checkPaymentMethod()
  {
    switch (REGISTRATION_PAYMENT_METHOD) {
      case 'RE_NBD':
      case 'GU':
        return true;
        break;
      case 'RE':
      default :
        return false;
        break;
    }
  }
}