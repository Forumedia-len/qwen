<?php

/**
 *[
 * 'image' => null, // изображение
 * 'name' => '', // название
 * 'section' => 'messages', // секция в которую будет сохранено в файле перевода
 * 'group' => 'common', // группа при выводе для удобства
 * 'device' => ['site', 'touch'], // тип устройства на котором будет отображаться
 * 'courtTypeNotUse' => [],  // типы кортов на которых не будет использоваться
 * 'courtUse' => false, // использовать для типов кортов или это общие
 * 'key' => null, // ключ для перевода
 * 'showAccordingToConditions' => true, // показывать или нет
 * 'variables' => [], // предаваемы варианты переменных
 * ];
 */

$messages = [];

$prefix_text_messages = !USE_AUTHORIZATION ? '_without_authorization' : '';

// ----------------------------- тексты в рассписании -----------------------------------------
$messages[] = [
  'image'        => 'info_text_above_timetable.jpg',
  'name'         => lang('title_info_text_above_timetable', 'config_messages'),
  'group'        => 'schedule',
  'key'          => 'info_text_above_timetable' . $prefix_text_messages,
  'courtTypeUse' => true,
];
$messages[] = [
  'image'        => 'mobile_info_text_above_timetable.jpg',
  'name'         => lang('title_mobile_info_text_above_timetable', 'config_messages'),
  'group'        => 'schedule',
  'device'       => ['site'],
  'key'          => 'mobile_info_text_above_timetable' . $prefix_text_messages,
  'courtTypeUse' => true,
];

// ------------------------------------------ текст перед брони --------------------------
$messages[] = [ // наличка
  'image'           => 'info_text_before_booking_cash_payer.jpg',
  'name'            => lang('title_info_text_before_booking_cash_payer', 'config_messages'),
  'group'           => 'booking',
  'device'          => ['touch'],
  'courtTypeNotUse' => ['open', 'mc_arena'],
  'key'             => 'info_text_before_booking_cash_payer',
  'variables'       => ['price'],
  'courtTypeUse'    => true,
];
$messages[] = [ // счет
  'image'           => 'info_text_before_booking_invoice.jpg',
  'name'            => lang('title_info_text_before_booking_invoice', 'config_messages'),
  'group'           => 'booking',
  'device'          => ['touch'],
  'courtTypeNotUse' => ['open', 'mc_arena'],
  'key'             => 'info_text_before_booking_invoice',
  'variables'       => ['price'],
  'courtTypeUse'    => true,
];
$messages[] = [ // гость
  'image'                     => 'info_text_before_guest_booking_without_login.jpg',
  'name'                      => lang('title_info_text_before_guest_booking_without_login', 'config_messages'),
  'group'                     => 'booking',
  'device'                    => ['touch'],
  'courtTypeNotUse'           => ['open', 'mc_arena'],
  'key'                       => 'info_text_before_guest_booking_without_login',
  'variables'                 => ['price'],
  'showAccordingToConditions' => GUEST,
  'courtTypeUse'              => true,
];

// ----------------------------- тексты при бронировании ------------------------------------------
$messages[] = [
  'image'        => 'info_text_when_booking.jpg',
  'name'         => lang('title_info_text_when_booking', 'config_messages'),
  'group'        => 'booking',
  'key'          => 'info_text_when_booking',
  'courtTypeUse' => true,
];

// ----------------------------- тексты после бронирования -----------------------------------------
$messages[] = [ // наличка
  'image'        => 'info_text_after_booking_cash_payer.jpg',
  'name'         => lang('title_info_text_after_booking_cash_payer', 'config_messages'),
  'group'        => 'booking',
  'key'          => 'info_text_after_booking_cash_payer',
  'variables'    => ['price', 'cancellation_days'],
  'courtTypeUse' => true,
];
$messages[] = [ // по счету
  'image'                     => 'info_text_after_booking_billing_customer.jpg',
  'name'                      => lang('title_info_text_after_booking_billing_customer', 'config_messages'),
  'group'                     => 'booking',
  'key'                       => 'info_text_after_booking_billing_customer',
  'variables'                 => ['price', 'cancellation_days'],
  'showAccordingToConditions' => config('payment')->useInvoicePayment(),
  'courtTypeUse'              => true,
];
$messages[] = [ // с лицевого счета
  'image'        => 'info_text_after_booking_credit.jpg',
  'name'         => lang('title_info_text_after_booking_credit', 'config_messages'),
  'group'        => 'booking',
  'key'          => 'info_text_after_booking_credit',
  'variables'    => ['price', 'cancellation_days'],
  'courtTypeUse' => true,
];
$messages[] = [ //  paypal
  'image'                     => 'info_text_after_booking_paypal.jpg',
  'name'                      => lang('title_info_text_after_booking_paypal', 'config_messages'),
  'group'                     => 'booking',
  'key'                       => 'info_text_after_booking_paypal',
  'variables'                 => ['price', 'cancellation_days'],
  'showAccordingToConditions' => config('payment')->useOnlinePayment(),
  'courtTypeUse'              => true,
];
$messages[] = [ // гость
  'image'                     => 'info_text_after_booking_guest.jpg',
  'name'                      => lang('title_info_text_after_booking_guest', 'config_messages'),
  'group'                     => 'booking',
  'key'                       => 'info_text_after_booking_guest',
  'variables'                 => ['price', 'cancellation_days'],
  'showAccordingToConditions' => GUEST,
  'courtTypeUse' => true,
];
$messages[] = [ // наличка
  'image'           => 'info_text_door_code_text.jpg',
  'name'            => lang('info_text_door_code_text', 'config_messages'),
  'group'           => 'booking',
  'courtTypeNotUse' => ['open', 'mc_arena'],
  'key'             => 'info_text_door_code_text',
  'courtTypeUse'    => true,
];
// ------------------------------------ тексты при регистрации  -----------------------------------------
$messages[] = [ // текст сверху
  'image'        => 'info_text_registration_mask_top.jpg',
  'name'         => lang('title_info_text_registration_mask_top', 'config_messages'),
  'group'        => 'registration',
  'key'          => 'info_text_registration_mask_top',
];
$messages[] = [ // текст снизу
  'image'        => 'info_text_registration_mask_bottom.jpg',
  'name'         => lang('title_info_text_registration_mask_bottom', 'config_messages'),
  'group'        => 'registration',
  'key'          => 'info_text_registration_mask_bottom',
];

// ------------------------------------ тексты после регистрации  -----------------------------------------
$messages[] = [ //paypal автоматическая активация
  'image'                     => 'info_text_after_registration_cash_payer_aut_active.jpg',
  'name'                      => lang('title_info_text_after_registration_cash_payer_aut_active', 'config_messages'),
  'group'                     => 'registration',
  'key'                       => 'info_text_after_registration_cash_payer_aut_active',
  'showAccordingToConditions' => config('payment')->useOnlinePayment(),
];
$messages[] = [ //paypal ручная активация
  'image'                     => 'info_text_after_registration_cash_payer_man_active.jpg',
  'name'                      => lang('title_info_text_after_registration_cash_payer_man_active', 'config_messages'),
  'group'                     => 'registration',
  'key'                       => 'info_text_after_registration_cash_payer_man_active',
  'showAccordingToConditions' => config('payment')->useOnlinePayment(),
];
$messages[] = [ //счет автоматическая активация
  'image' => 'info_text_after_registration_invoice_aut_active.jpg',
  'name'  => lang('title_info_text_after_registration_invoice_aut_active', 'config_messages'),
  'group' => 'registration',
  'key'   => 'info_text_after_registration_invoice_aut_active',
];
$messages[] = [ //счет ручная активация
  'image' => 'info_text_after_registration_invoice_man_active.jpg',
  'name'  => lang('title_info_text_after_registration_invoice_man_active', 'config_messages'),
  'group' => 'registration',
  'key'   => 'info_text_after_registration_invoice_man_active',
];

return $messages;