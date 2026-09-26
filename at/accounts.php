<?php

require 'accounts_admin.php';

$current_lang     = config('lang')->getCurrentLang();
$a                = new accounts_admin;
$_page['content'] = $a->start();
$_page['key']     = $a->getPageKey();
$_page['js'][]    = 'accounts';
$_page['js'][]    = ['maskedinput', 'common', false, 'cdn'];
$_page['js'][]    = ['jquery-ui/jquery-ui.min', 'third', true, 'cdn'];
$_page['js'][]    = ['jquery-ui/i18n/datepicker-' . $current_lang, 'third', true, 'cdn'];
$_page['css'][]   = ['jquery-ui/jquery-ui.min', 'third', true, 'cdn'];
$_page['js'][]    = ['clients', 'admin', false, 'cdn'];
$_page['js'][]    = ['visualeditor/ckeditor', 'admin', false, 'cdn'];
$_page['js'][]    = ['popup', 'admin', false, 'cdn'];
$_page['js'][]    = ['select2/js/select2.min', 'third', true, 'cdn'];
$_page['js'][]    = ['select2/js/i18n/' . $current_lang, 'third', true, 'cdn'];
$_page['css'][]   = ['select2/css/select2.min', 'third', true, 'cdn'];