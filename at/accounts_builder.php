<?php

use AC\core\engines\AccountsEngine;
use AC\core\engines\Engines;
use AC\core\system\helpers\NumberHelper;

$_account = new AccountsEngine();
$r        = new Engines();

$out = '';
if (isset($_POST['title']) && isset($_POST['count']) && isset($_POST['price']) && isset($_POST['client_id']) && isset($_POST['date']) && isset($_POST['nds'])) {
  if (($r->clients->getClientData((int)$_POST['client_id'], $client_data)) && ($_POST['date'] == date(
        'Y-m-d',
        strtotime($_POST['date'])
      ))) {
    $reservations = [];
    //Подготовить массив данных счета
    for ($i = 0; $i < count($_POST['title']); $i++) {
      $tmp['title']   = $_POST['title'][$i];
      $tmp['count']   = NumberHelper::float($_POST['count'][$i]);
      $tmp['price']   = NumberHelper::float($_POST['price'][$i]);
      $reservations[] = $tmp;
      unset($tmp);
    }
    if ($_account->insertOtherAccount(
      $client_data['client_id'],
      $client_data['name'],
      $client_data['surname'],
      $client_data['address'],
      $client_data['post_code'],
      $client_data['city'],
      $client_data['email'],
      $client_data['number'],
      $_POST['date'],
      null,
      $_POST['text_config'],
      (int)$_POST['nds'],
      array(
        $client_data['bank_iban'],
        $client_data['bank_bic'],
        $client_data['bank_sepa_referenz'],
        $client_data['bank_sepa_mandat'],
        $client_data['sepa_type'],
        $client_data['sepa_standart']
      ),
      array(
        $client_data['account_owner'],
        $client_data['account_number'],
        $client_data['bank_index'],
        $client_data['bank_name']
      ),
      $reservations
    )) {
      $out = '<div class="message">'.lang('The invoice is generated!', 'accounts_builder').'<br/>';
      $out .= '<a href="javascript:window.close()">'.lang('Close', 'accounts_view').'</a></div>';
    }
  }
}

if (isset($_POST['year']) && isset($_POST['month']) && isset($_POST['days']) && isset($_POST['client_id']) && isset($_POST['nds'])) {
  if ($r->clients->getClientData((int)$_POST['client_id'], $client_data)) {
    $out = '<div class="pageBlock" ' . ($page_break ?? '') . '>' . "\n";
    $out .= '<div class="pageBgBottomLine"><div class="pageBgLeftLine"><div class="pageBgBottom"><div class="pageBgTop"><div class="pageBgCorner">' . "\n";
    $out .= '<div class="page">' . "\n";
    /*$out .= '<div class="address">
          Hallenwart<br/>
          Stefan Witt, Eichenweg 8<br/>
          78549 Spaichingen<br/>
          Tel.Nr. 0742/6177<br/>
          stefan-witt@t-online.de
        </div>'."\n";*/
    $out .= '<p><br/>' . "\n";
    $out .= $client_data['name'] . ' ' . $client_data['surname'] . '<br/>' . "\n";
    $out .= $client_data['address'] . '<br/>' . "\n";
    $out .= $client_data['post_code'] . ' ' . $client_data['city'] . '' . "\n";
    $out .= '<br/><br/></p>' . "\n";
    $out .= '<p align="right" style="padding-right:90px;">'.lang('Client no.', 'accounts_builder').': ' . $client_data['number'] . '</p>' . "\n";


    $out .= '<br/><br/><br/><br/><br/>';

    $out .= '<h1>'.lang('INVOICE HALL USE', 'accounts_builder').': ' . $client_data['number'] . '</h1>';

    $account_date = $_POST['year'] . '-' . $_POST['month'] . '-' . $_POST['days'];
    $out          .= '<div class="date">' . date('d.m.Y', strtotime($account_date)) . '</div>';

    //расписываем таблицу заказов
    $out .= '<form action="accounts_builder.php" method="POST">';

    $out .= '<input type="hidden" name="client_id" value="' . $client_data['client_id'] . '"/>';
    $out .= '<input type="hidden" name="date" value="' . $account_date . '"/>';
    $out .= '<input type="hidden" name="nds" value="' . (int)$_POST['nds'] . '"/>';
    $out .= '<input type="hidden" name="text_config" value="' . (int)$_POST['text_config'] . '"/>';

    $out .= '<table class="accountsViewTable" id="account_list">' . "\n";
    $out .= '<tr>' . "\n";
    $out .= '<th width="45%">'.lang('Designation', 'accounts_builder').'</th>' . "\n";
    $out .= '<th width="5%">'.lang('Quantity', 'accounts_builder').'</th>' . "\n";
    $out .= '<th width="20%">'.lang('Unit price', 'accounts_builder').' '.CURR_VALUTE.'</th>' . "\n";
    $out .= '<th width="20%">'.lang('Total price').' '.CURR_VALUTE.'</th>' . "\n";
    $out .= '<th width="10%">'.lang('Action').'</th>' . "\n";
    $out .= '</tr>' . "\n";
    $out .= '<tr>' . "\n";
    $out .= '<td><input type="text" name="title[]"/></td>' . "\n";
    $out .= '<td><input type="text" name="count[]" value="0" class="price" onkeyup="a_b.calculateSumItem(1)" onkeypress="return a_b.testInputData(event, \'float\')" id="count_1"/></td>' . "\n";
    $out .= '<td><input type="text" name="price[]" value="0,00" class="price" onkeyup="a_b.calculateSumItem(1)" onkeypress="return a_b.testInputData(event, \'float\')" id="price_1"/> '.CURR_VALUTE.'</td>' . "\n";
    $out .= '<td id="sum_1">0,00 '.CURR_VALUTE.'</td>' . "\n";
    $out .= '<td>&nbsp;</td>' . "\n";
    $out .= '</tr>' . "\n";
    $out .= '</table>' . "\n";

    $out .= '<script> var a_b = new account_builder(\'a_b\', ' . (int)$_POST['nds'] . ', \''. CURR_VALUTE.'\'); </script>' . "\n";
    $out .= '<br/><a href="#" onclick="return a_b.insertAccountString()" class="btnAdd">'.lang('Add line', 'accounts_builder').'</a>' . "\n";
    $out .= '<table class="accountsViewTable">' . "\n";
    $out .= '<tr>' . "\n";
    $out .= '<td colspan="4" class="noBorder"><br/>'.lang('Total amount incl. nds VAT', 'accounts_builder', ['nds' => (int)$_POST['nds'] . '%']).'</td>' . "\n";
    $out .= '<td class="noBorder"><br/><strong><span id="sum_account">0,00</span> '.CURR_VALUTE.'</strong></td><td class="noBorder">&nbsp;</td>' . "\n";
    $out .= '</tr>' . "\n";
    $out .= '</table>' . "\n";

    $out        .= '<input type="submit" name="save" value="'.lang('button_save').'" class="button"/>';
    $out        .= '</form>';
    $out        .= '</div>' . "\n";
    $out        .= '</div></div></div></div></div>' . "\n";
    $out        .= '</div>';
    $page_break = 'style="page-break-before:always"';
  }
}


$_page['content'][0] = $out;
$_page['key']        = 'accounts_builder';
$_page['title']      = 'Statistik';
$_page['js'][]       = 'accounts';
