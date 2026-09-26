<?php
$admin = [];

//$admin['mailing']['parent_key'] = 'config_';
//$admin['mailing']['template']   = 'internal';
//$admin['mailing']['title']      = lang('mailing_title', 'structure');
//$admin['mailing']['href']       = 'mailing';
//$admin['mailing']['sort']       = 800;


//настройки шаблонов писем
$admin['mailing_court_letter_templates']['parent_key'] = 'config_';
$admin['mailing_court_letter_templates']['template']   = 'internal';
$admin['mailing_court_letter_templates']['title']      = lang('mailing_letter_templates_title', 'structure');
$admin['mailing_court_letter_templates']['href']       = 'mailing/courtLetterTemplates';
$admin['mailing_court_letter_templates']['sort']       = 800;

return $admin;