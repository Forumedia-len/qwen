<?php

$admin = [];
if (config('membershipFees')->useMembershipFees()) {
  $admin['membership_fees_management']['parent_key'] = 'login';
  $admin['membership_fees_management']['template']   = 'internal';
  $admin['membership_fees_management']['title']      = lang('membership_fees_management', 'structure');
  $admin['membership_fees_management']['menu_title'] = lang('membership_fees_management_menu', 'structure');
  $admin['membership_fees_management']['icon']       = 'association';
  $admin['membership_fees_management']['href']       = 'membershipFees/management';
  $admin['membership_fees_management']['sort']       = 210;
  $admin['membership_fees_management']['access']     = 2;

  $admin['membership_fees_management_setup']['parent_key'] = 'membership_fees_management';
  $admin['membership_fees_management_setup']['template']   = 'internal';
  $admin['membership_fees_management_setup']['title']      = lang('membership_fees_management_setup', 'structure');
  $admin['membership_fees_management_setup']['href']       = 'membershipFees/management';
  $admin['membership_fees_management_setup']['sort']       = 100;

  $admin['membership_fees_management_groups']['parent_key'] = 'membership_fees_management';
  $admin['membership_fees_management_groups']['template']   = 'internal';
  $admin['membership_fees_management_groups']['title']      = lang('membership_fees_management_groups', 'structure');
  $admin['membership_fees_management_groups']['href']       = 'membershipFees/groups?vereinsverwaltung=1';
  $admin['membership_fees_management_groups']['sort']       = 200;

  $admin['membership_fees_management_anniversary']['parent_key'] = 'membership_fees_management';
  $admin['membership_fees_management_anniversary']['template']   = 'internal';
  $admin['membership_fees_management_anniversary']['title']      = lang('membership_fees_anniversary', 'structure');
  $admin['membership_fees_management_anniversary']['href']       = 'membershipFees/anniversary?vereinsverwaltung=1';
  $admin['membership_fees_management_anniversary']['sort']       = 300;

  $admin['membership_fees_management_members_by_year_of_birth']['parent_key'] = 'membership_fees_management';
  $admin['membership_fees_management_members_by_year_of_birth']['template']   = 'internal';
  $admin['membership_fees_management_members_by_year_of_birth']['title']      = lang('membership_fees_reports_membersByYearOfBirth', 'structure');
  $admin['membership_fees_management_members_by_year_of_birth']['href']       = 'membershipFees/management/membersByYearOfBirth';
  $admin['membership_fees_management_members_by_year_of_birth']['sort']       = 400;

  $admin['membership_fees_management_membership']['parent_key'] = 'membership_fees_management';
  $admin['membership_fees_management_membership']['template']   = 'internal';
  $admin['membership_fees_management_membership']['title']      = lang('membership_fees_reports_membership', 'structure');
  $admin['membership_fees_management_membership']['href']       = 'membershipFees/management/membership';
  $admin['membership_fees_management_membership']['sort']       = 500;

  $admin['membership_fees_management_accounts']['parent_key'] = 'membership_fees_management';
  $admin['membership_fees_management_accounts']['template']   = 'internal';
  $admin['membership_fees_management_accounts']['title']      = lang('membership_fees_management_accounts', 'structure');
  $admin['membership_fees_management_accounts']['href']       = 'membershipFees/accounts?vereinsverwaltung=1';
  $admin['membership_fees_management_accounts']['sort']       = 600;

  $admin['membership_fees_management_accounts_generate']['parent_key']  = 'membership_fees_management_accounts';
  $admin['membership_fees_management_accounts_generate']['template']    = 'internal';
  $admin['membership_fees_management_accounts_generate']['title']       = lang('accounts_generate_title', 'structure');
  $admin['membership_fees_management_accounts_generate']['href']        = 'membershipFees/accounts?vereinsverwaltung=1';
  $admin['membership_fees_management_accounts_generate']['content_menu'] = true;
  $admin['membership_fees_management_accounts_generate']['content_key'] = 1;
  $admin['membership_fees_management_accounts_generate']['sort']        = 100;

  $admin['membership_fees_management_accounts_list']['parent_key']  = 'membership_fees_management_accounts';
  $admin['membership_fees_management_accounts_list']['template']    = 'internal';
  $admin['membership_fees_management_accounts_list']['title']       = lang('accounts_membershipFees_list_title', 'structure');
  $admin['membership_fees_management_accounts_list']['href']        = 'membershipFees/accounts/list?vereinsverwaltung=1';
  $admin['membership_fees_management_accounts_list']['content_menu'] = true;
  $admin['membership_fees_management_accounts_list']['content_key'] = 1;
  $admin['membership_fees_management_accounts_list']['sort']        = 200;

  $admin['membership_fees_management_accounts_archives']['parent_key']  = 'membership_fees_management_accounts';
  $admin['membership_fees_management_accounts_archives']['template']    = 'internal';
  $admin['membership_fees_management_accounts_archives']['title']       = lang('accounts_membershipFees_archives_title', 'structure');
  $admin['membership_fees_management_accounts_archives']['href']        = 'membershipFees/accounts/archives?vereinsverwaltung=1';
  $admin['membership_fees_management_accounts_archives']['content_menu'] = true;
  $admin['membership_fees_management_accounts_archives']['content_key'] = 1;
  $admin['membership_fees_management_accounts_archives']['sort']        = 300;

  $admin['membership_fees_management_export']['parent_key'] = 'membership_fees_management';
  $admin['membership_fees_management_export']['template']   = 'internal';
  $admin['membership_fees_management_export']['title']      = lang('membership_fees_management_export', 'structure');
  $admin['membership_fees_management_export']['href']       = 'clients.php?action=viewExportFormClients&vereinsverwaltung=1';
  $admin['membership_fees_management_export']['sort']       = 700;

  $admin['membership_fees_groups']['parent_key'] = 'clients';
  $admin['membership_fees_groups']['template']   = 'internal';
  $admin['membership_fees_groups']['title']      = lang('membership_fees_groups', 'structure');
  $admin['membership_fees_groups']['href']       = 'membershipFees/groups';
  $admin['membership_fees_groups']['sort']       = 900;

  $admin['membership_fees_anniversary']['parent_key'] = 'clients';
  $admin['membership_fees_anniversary']['template']   = 'internal';
  $admin['membership_fees_anniversary']['title']      = lang('membership_fees_anniversary', 'structure');
  $admin['membership_fees_anniversary']['href']       = 'membershipFees/anniversary';
  $admin['membership_fees_anniversary']['sort']       = 950;


  $admin['membership_fees_accounts']['parent_key'] = 'accounts';
  $admin['membership_fees_accounts']['template']   = 'internal';
  $admin['membership_fees_accounts']['title']      = lang('membership_fees', 'structure');
  $admin['membership_fees_accounts']['href']       = 'membershipFees/accounts';
  $admin['membership_fees_accounts']['tab']        = 'membershipFees';
  $admin['membership_fees_accounts']['sort']       = 800;

  $admin['membership_fees_accounts_generate']['parent_key'] = 'membership_fees_accounts';
  $admin['membership_fees_accounts_generate']['template']   = 'internal';
  $admin['membership_fees_accounts_generate']['title']      = lang('accounts_generate_title', 'structure');
  $admin['membership_fees_accounts_generate']['href']       = 'membershipFees/accounts';
  $admin['membership_fees_accounts_generate']['sort']       = 100;

  $admin['membership_fees_accounts_list']['parent_key'] = 'membership_fees_accounts';
  $admin['membership_fees_accounts_list']['template']   = 'internal';
  $admin['membership_fees_accounts_list']['title']      = lang('accounts_membershipFees_list_title', 'structure');
  $admin['membership_fees_accounts_list']['href']       = 'membershipFees/accounts/list';
  $admin['membership_fees_accounts_list']['sort']       = 200;

  $admin['membership_fees_accounts_archives']['parent_key'] = 'membership_fees_accounts';
  $admin['membership_fees_accounts_archives']['template']   = 'internal';
  $admin['membership_fees_accounts_archives']['title']      = lang('accounts_membershipFees_archives_title', 'structure');
  $admin['membership_fees_accounts_archives']['href']       = 'membershipFees/accounts/archives';
  $admin['membership_fees_accounts_archives']['sort']       = 300;

  $admin['membership_fees_reports_membersByYearOfBirth']['parent_key'] = 'reports';
  $admin['membership_fees_reports_membersByYearOfBirth']['template']   = 'statistics';
  $admin['membership_fees_reports_membersByYearOfBirth']['title']      = lang('membership_fees_reports_membersByYearOfBirth', 'structure');
  $admin['membership_fees_reports_membersByYearOfBirth']['href']       = 'membershipFees/reports/membersByYearOfBirth';
  $admin['membership_fees_reports_membersByYearOfBirth']['visible']    = false;
  $admin['membership_fees_reports_membersByYearOfBirth']['sort']       = 300;

  $admin['membership_fees_reports_membership']['parent_key'] = 'reports';
  $admin['membership_fees_reports_membership']['template']   = 'statistics';
  $admin['membership_fees_reports_membership']['title']      = lang('membership_fees_reports_membership', 'structure');
  $admin['membership_fees_reports_membership']['href']       = 'membershipFees/reports/membership';
  $admin['membership_fees_reports_membership']['visible']    = false;
  $admin['membership_fees_reports_membership']['sort']       = 400;
}

return $admin;
