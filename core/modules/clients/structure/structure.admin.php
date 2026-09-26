<?php

$admin = [];

//клиенты
$admin['clients']['parent_key'] = 'login';
$admin['clients']['template']   = 'internal';
$admin['clients']['title']      = lang('clients_title', 'structure');
$admin['clients']['icon']       = 'users';
$admin['clients']['href']       = 'clients.php?action=insertClientForm';
$admin['clients']['sort']       = 200;
$admin['clients']['access']     = 2;

//insert
$admin['clients_insert']['parent_key'] = 'clients';
$admin['clients_insert']['template']   = 'internal';
$admin['clients_insert']['title']      = lang('clients_insert_title', 'structure');
$admin['clients_insert']['href']       = 'clients.php?action=insertClientForm';
$admin['clients_insert']['sort']       = 100;
//not active
$admin['clients_inactive']['parent_key'] = 'clients';
$admin['clients_inactive']['template']   = 'internal';
$admin['clients_inactive']['title']      = lang('clients_inactive_title', 'structure');
$admin['clients_inactive']['href']       = 'clients.php?action=viewInActive';
$admin['clients_inactive']['sort']       = 200;

//online
$admin['clients_online']['parent_key'] = 'clients';
$admin['clients_online']['template']   = 'internal';
$admin['clients_online']['title']      = lang('clients_online_title', 'structure') . ' (' . getEngine('clients', false)->getClientsDataCount() . ')';
$admin['clients_online']['class']      = 'online-clients';
$admin['clients_online']['href']       = 'clients.php?mode=1';
$admin['clients_online']['sort']       = 300;
//offline
//$admin['clients_offline']['parent_key'] = 'clients';
//$admin['clients_offline']['template'] = 'internal';
//$admin['clients_offline']['title'] = lang('clients_offline_title', 'structure');
//$admin['clients_offline']['href'] = 'clients.php?mode=2';
//bar
$admin['clients_bar']['parent_key'] = 'clients';
$admin['clients_bar']['template']   = 'internal';
$admin['clients_bar']['title']      = lang('clients_bar_title', 'structure');
$admin['clients_bar']['href']       = 'clients.php?action=viewBarClients';
$admin['clients_bar']['sort']       = 400;
//export
$admin['clients_export']['parent_key'] = 'clients';
$admin['clients_export']['template']   = 'internal';
$admin['clients_export']['title']      = lang('clients_export_title', 'structure');
$admin['clients_export']['href']       = 'clients.php?action=viewExportFormClients';
$admin['clients_export']['sort']       = 500;

//данные клиента
$admin['clients_personal_data']['parent_key'] = 'clients';
$admin['clients_personal_data']['template']   = 'client_data';
$admin['clients_personal_data']['title']      = lang('ClientData', 'structure');
$admin['clients_personal_data']['href']       = 'clients/personalData/statistics';
$admin['clients_personal_data']['visible']    = false;

//client_prepayment_data - old
$admin['clients_private_account']['parent_key'] = 'clients';
$admin['clients_private_account']['template']   = 'client_data';
$admin['clients_private_account']['title']      = lang('ClientData', 'structure');
$admin['clients_private_account']['visible']    = false;
$admin['clients_private_account']['href']       = 'clients/privateAccount';

$admin['sepa_help']['parent_key'] = 'clients';
$admin['sepa_help']['template']   = 'print';
$admin['sepa_help']['visible']    = false;
return $admin;