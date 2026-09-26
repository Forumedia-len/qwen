<?php

use AC\core\modules\areas\models\AreasModel;

$admin = [];
//Cчета
$admin['accounts']['parent_key'] = 'login';
$admin['accounts']['template'] = 'internal';
$admin['accounts']['icon'] = 'account';
$admin['accounts']['title'] = lang('accounts_title', 'structure');
$admin['accounts']['href'] = 'accounts.php';
$admin['accounts']['sort'] = 140;

/** @var AreasModel $areasModel */
$areasModel = module('areas')->useModel();
$types      = $areasModel->selectActiveType('type_id');
foreach ($types as $type) {
  $admin['accounts_' . $type->current_alias]['parent_key'] = 'accounts';
  $admin['accounts_' . $type->current_alias]['template'] = 'internal';
  $admin['accounts_' . $type->current_alias]['tab'] = $type->current_alias;
  $admin['accounts_' . $type->current_alias]['title'] = $type->title;
  $admin['accounts_' . $type->current_alias]['href'] = 'accounts.php?' . 'type=' . $type->type_id;
  $admin['accounts_' . $type->current_alias]['sort'] = $type->type_id * 100;
}

$type = $types[Service::request()->_get('type', $areasModel->getFirstActiveType())];
$addType = 'type=' . $type->type_id;

//Выставление счета
$admin['accounts_generate']['parent_key'] = 'accounts_' . $type->current_alias;
$admin['accounts_generate']['template'] = 'internal';
$admin['accounts_generate']['title'] = lang('accounts_generate_title', 'structure');
$admin['accounts_generate']['href'] = 'accounts.php?' . $addType;
$admin['accounts_generate']['sort'] = 100;

$admin['accounts_list']['parent_key'] = 'accounts_' . $type->current_alias;
$admin['accounts_list']['template'] = 'internal';
$admin['accounts_list']['title'] = lang('accounts_list_title', 'structure');
$admin['accounts_list']['href'] = 'accounts.php?action=showAccountsList&' . $addType;
$admin['accounts_list']['sort'] = 200;
//Архив счетов
$admin['accounts_archives']['parent_key'] = 'accounts_' . $type->current_alias;
$admin['accounts_archives']['template'] = 'internal';
$admin['accounts_archives']['title'] = lang('accounts_archives_title', 'structure');
$admin['accounts_archives']['href'] = 'accounts.php?action=showAccountsArchives&' . $addType;
$admin['accounts_archives']['sort'] = 220;


//Журнал счетов для абонементов
$admin['accounts_tickets_list']['parent_key'] = 'accounts_' . $type->current_alias;
$admin['accounts_tickets_list']['template'] = 'internal';
$admin['accounts_tickets_list']['title'] = lang('accounts_tickets_list_title', 'structure');
$admin['accounts_tickets_list']['href'] = 'accounts.php?action=showAboAccountsList&' . $addType;
$admin['accounts_tickets_list']['sort'] = 300;
//Архив счетов для абонементов
$admin['accounts_tickets_archives']['parent_key'] = 'accounts_' . $type->current_alias;
$admin['accounts_tickets_archives']['template'] = 'internal';
$admin['accounts_tickets_archives']['title'] = lang('accounts_tickets_archives_title', 'structure');
$admin['accounts_tickets_archives']['href'] = 'accounts.php?action=showAboAccountsArchives&' . $addType;
$admin['accounts_tickets_archives']['sort'] = 320;

//Журнал дополнительных счетов
$admin['accounts_other_list']['parent_key'] = 'accounts_' . $type->current_alias;
$admin['accounts_other_list']['template'] = 'internal';
$admin['accounts_other_list']['title'] = lang('accounts_other_list_title', 'structure');
$admin['accounts_other_list']['href'] = 'accounts.php?action=showOtherAccountsList&' . $addType;
$admin['accounts_other_list']['sort'] = 400;
//Архив дополнительных счетов
$admin['accounts_other_archives']['parent_key'] = 'accounts_' . $type->current_alias;
$admin['accounts_other_archives']['template'] = 'internal';
$admin['accounts_other_archives']['title'] = lang('accounts_other_archives_title', 'structure');
$admin['accounts_other_archives']['href'] = 'accounts.php?action=showOtherAccountsArchives&' . $addType;
$admin['accounts_other_archives']['sort'] = 420;

//Журнал счетов предоплаты
$admin['accounts_prepayment_list']['parent_key'] = 'accounts_' . $type->current_alias;
$admin['accounts_prepayment_list']['template'] = 'internal';
$admin['accounts_prepayment_list']['title'] = lang('accounts_prepayment_list_title', 'structure');
$admin['accounts_prepayment_list']['href'] = 'accounts.php?action=showPrepaymentAccountsList&' . $addType;
$admin['accounts_prepayment_list']['sort'] = 500;
//Архив счетов предоплаты
$admin['accounts_prepayment_archives']['parent_key'] = 'accounts_' . $type->current_alias;
$admin['accounts_prepayment_archives']['template'] = 'internal';
$admin['accounts_prepayment_archives']['title'] = lang('accounts_prepayment_archives_title', 'structure');
$admin['accounts_prepayment_archives']['href'] = 'accounts.php?action=showPrepaymentAccountsArchives&' . $addType;
$admin['accounts_prepayment_archives']['sort'] = 520;

$showOnlinePaymentInvoices = config('account')->useOnlinePaymentInvoice()
  || getEngine('accounts', false)->hasOnlinePaymentInvoices();

if ($showOnlinePaymentInvoices) {
  // Журнал счетов для онлайн-оплат
  $admin['accounts_online_payment_list']['parent_key'] = 'accounts_' . $type->current_alias;
  $admin['accounts_online_payment_list']['template'] = 'internal';
  $admin['accounts_online_payment_list']['title'] = lang('accounts_online_payment_list_title', 'structure');
  $admin['accounts_online_payment_list']['href'] = 'accounts.php?action=showOnlinePaymentAccountsList&' . $addType;
  $admin['accounts_online_payment_list']['sort'] = 600;

  // Архив счетов для онлайн-оплат
  $admin['accounts_online_payment_archives']['parent_key'] = 'accounts_' . $type->current_alias;
  $admin['accounts_online_payment_archives']['template'] = 'internal';
  $admin['accounts_online_payment_archives']['title'] = lang('accounts_online_payment_archives_title', 'structure');
  $admin['accounts_online_payment_archives']['href'] = 'accounts.php?action=showOnlinePaymentAccountsArchives&' . $addType;
  $admin['accounts_online_payment_archives']['sort'] = 620;
}

////старый сайт по открытым кортам для счетов
//$admin['accounts_open_site']['parent_key'] = 'accounts';
//$admin['accounts_open_site']['template'] = 'internal';
//$admin['accounts_open_site']['target'] = '_blank';
//$admin['accounts_open_site']['title'] = 'Aussenplatz-Rechnungsarchiv';
//$admin['accounts_open_site']['href'] = '../old/open/at';

//		$admin['accounts_platze_archives']['parent_key'] = 'accounts';
//		$admin['accounts_platze_archives']['template'] = 'internal';
//		$admin['accounts_platze_archives']['title'] = lang('accounts_platze_archives_title', 'structure');
//		$admin['accounts_platze_archives']['href'] = '/old/open/at/';

$admin['accounts_config']['parent_key'] = 'accounts';
$admin['accounts_config']['template'] = 'internal';
$admin['accounts_config']['tab'] = 'config';
$admin['accounts_config']['title'] = lang('accountTextConfig', 'structure');
$admin['accounts_config']['href'] = 'accounts.php?action=showAccountTextConfig';
$admin['accounts_config']['sort'] = 900;

$admin['accounts_text_config']['parent_key'] = 'accounts_config';
$admin['accounts_text_config']['template'] = 'internal';
$admin['accounts_text_config']['title'] = lang('accounts_text_config_title', 'structure');
$admin['accounts_text_config']['href'] = 'accounts.php?action=showAccountTextConfig';
$admin['accounts_text_config']['sort'] = 100;

//Редактирование шаблона счетов
$admin['accounts_pdf_template']['parent_key'] = 'accounts_config';
$admin['accounts_pdf_template']['template'] = 'internal';
$admin['accounts_pdf_template']['title'] = lang('accounts_template_pdf_title', 'structure');
$admin['accounts_pdf_template']['href'] = 'accounts/pdfTemplate';
$admin['accounts_pdf_template']['sort'] = 200;

//$admin['accounts_texts']['parent_key'] = 'accounts_config';
//$admin['accounts_texts']['template'] = 'internal';
//$admin['accounts_texts']['title'] = lang('Account payment terms', 'structure');
//$admin['accounts_texts']['href'] = 'accounts/texts/paymentTerms';
//$admin['accounts_texts']['sort'] = 300;

//Показать счет
$admin['accounts_view']['parent_key'] = 'accounts';
$admin['accounts_view']['template'] = 'account';
$admin['accounts_view']['visible'] = false;

//Построение счета
$admin['accounts_builder']['parent_key'] = 'accounts';
$admin['accounts_builder']['template'] = 'account';
$admin['accounts_builder']['visible'] = false;

return $admin;
