<?php
$admin = [];
if(config('typeReservation')->useOtherGroupTypes()) {
  $admin['fitness_tickets']['parent_key'] = 'tickets_';
  $admin['fitness_tickets']['template']   = 'internal';
  $admin['fitness_tickets']['title']      = lang('tickets_fitness_view_title', 'structure');
//  $admin['fitness_tickets']['href']       = 'tickets/fitness';
  $admin['fitness_tickets']['href']       = 'tickets_fitness.php';
  $admin['fitness_tickets']['tab']       = 'tickets';
  $admin['fitness_tickets']['sort']       = 110;

  $admin['fitness_tickets_show']['parent_key'] = 'fitness_tickets';
  $admin['fitness_tickets_show']['template']   = 'internal';
  $admin['fitness_tickets_show']['title']      = lang('tickets_fitness_view_title', 'structure');
//  $admin['fitness_tickets_show']['href']       = 'tickets/fitness';
  $admin['fitness_tickets_show']['href']       = 'tickets_fitness.php';
  $admin['fitness_tickets_show']['sort']       = 100;

  $admin['fitness_tickets_export']['parent_key'] = 'fitness_tickets';
  $admin['fitness_tickets_export']['template']   = 'internal';
  $admin['fitness_tickets_export']['title']      = lang('tickets_fitness_export_title', 'structure');
//  $admin['fitness_tickets_export']['href']       = 'tickets/fitness/export';
  $admin['fitness_tickets_export']['href']       = 'tickets_fitness.php?action=viewExportFormTickets';
  $admin['fitness_tickets_export']['sort']       = 200;


  $admin['fitness_accounts_tickets']['parent_key'] = 'accounts';
  $admin['fitness_accounts_tickets']['template']   = 'internal';
  $admin['fitness_accounts_tickets']['title']      = 'Fitness';
  $admin['fitness_accounts_tickets']['href']       = 'account_fitness.php';
  $admin['fitness_accounts_tickets']['tab']        = 'fitness';
  $admin['fitness_accounts_tickets']['sort']       = 750;

  $admin['fitness_accounts_tickets_generate']['parent_key'] = 'fitness_accounts_tickets';
  $admin['fitness_accounts_tickets_generate']['template']   = 'internal';
  $admin['fitness_accounts_tickets_generate']['title']      = lang('accounts_generate_title', 'structure');
  $admin['fitness_accounts_tickets_generate']['href']       = 'account_fitness.php';
  $admin['fitness_accounts_tickets_generate']['sort']       = 100;

  $admin['fitness_accounts_tickets_list']['parent_key'] = 'fitness_accounts_tickets';
  $admin['fitness_accounts_tickets_list']['template']   = 'internal';
  $admin['fitness_accounts_tickets_list']['title']      = lang('fitness_accounts_list_title', 'structure');
  $admin['fitness_accounts_tickets_list']['href']       = 'account_fitness.php?action=showAboFitnessAccountsList';
  $admin['fitness_accounts_tickets_list']['sort']       = 200;

  $admin['fitness_accounts_tickets_archives']['parent_key'] = 'fitness_accounts_tickets';
  $admin['fitness_accounts_tickets_archives']['template']   = 'internal';
  $admin['fitness_accounts_tickets_archives']['title']      = lang('fitness_accounts_archives_title', 'structure');
  $admin['fitness_accounts_tickets_archives']['href']       = 'account_fitness.php?action=showAboFitnessAccountsArchives';
  $admin['fitness_accounts_tickets_archives']['sort']       = 300;
}

return $admin;

