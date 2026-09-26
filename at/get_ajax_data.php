<?php

use AC\app\entities\enums\Encash;
use AC\core\engines\AccountsEngine;
use AC\core\engines\Engines;
use AC\core\modules\reservations\entities\dto\PriceOptionsFormBlockDto;
use AC\core\modules\reservations\locators\ReservServiceLocator;
use AC\core\system\helpers\NumberHelper;


class AjaxData
{

  private $action;

  function __construct($act)
  {
    $this->action = $act;
  }

  public function start()
  {
    switch ($this->action) {
      case 'generateCouponCode':
        return $this->generateCouponCode();
      case 'getOrdersData':
        return $this->getOrdersData();
      case 'getStocksList':
        return $this->getStocksList();
      case 'getPrepaymentAccount':
        return $this->getPrepaymentAccount();
      case 'getAccountTextForm':
        return $this->getAccountTextForm();
      default:
        break;
    }
  }

  private function generateCouponCode()
  {
    $r = getEngine('coupon');
    if (isset ($_GET['coupon_id'])) {
      //принятые значения
      $coupon_id = (int)$_GET['coupon_id'];
      $code      = $this->generateCode(10);
      if ($r->insertCode($coupon_id, $code, 0)) {
        return 1;
      }
    }

    return 0;
  }

  private function generateCode($number)
  {
    $r = getEngine('coupon');

    $arr = [
      'A',
      'B',
      'C',
      'D',
      'E',
      'F',
      'G',
      'H',
      'I',
      'J',
      'K',
      'L',
      'M',
      'N',
      'O',
      'P',
      'R',
      'S',
      'T',
      'U',
      'V',
      'X',
      'Y',
      'Z',
      '1',
      '2',
      '3',
      '4',
      '5',
      '6',
      '7',
      '8',
      '9',
      '0',
    ];
    // Генерируем пароль
    $pass = "";
    for ($i = 0; $i < $number; $i++) {
      // Вычисляем случайный индекс массива
      $index = rand(0, count($arr) - 1);
      $pass  .= $arr[$index];
    }

    if ($r->checkCodeExists($pass)) {
      $pass = $this->generateCode($number);
    }

    return $pass;
  }

  private function getOrdersData()
  {
    $out = '';

    $r        = new Engines();
    $area_id  = (int)Service::request()->_('area_id');
    $type_id  = (int)Service::request()->_('type_id');
    $sport_id = (int)Service::request()->_('sport_id');
    $page     = (int)Service::request()->_get('page');
    $date     = Service::request()->_('date');
    $time     = Service::request()->_('time');
    if ($area_id != null && $date != null && $time != null) {
      //принятые значения
      $mysql_datetime = $date . ' ' . $time;
      $unixtime       = strtotime($mysql_datetime);
      if ($r->getReservationData($area_id, $mysql_datetime, $reservation_data)) {
        //Выводим форму
        $r->areas->getAreaData($area_id, $area_data);
        $r->clients->getClientData($reservation_data['client_id'], $client_data);

        $out .= '<h1>' . $r->areas->getTitleByAreaId($area_id) . ' - ' . $area_data['title'] . '</h1>';
        $out .= '<h2>' . $client_data['name'] . ' ' . $client_data['surname'] . '</h2>';
        if ($r->getReservationGroupData($area_id, $client_data['client_id'], $date, $time, $reservations_data)) {
          $out    .= '<form action="reservations.php?action=changeOrders&type_id=' . $type_id . '&sport_id=' . $sport_id . '&date=' . $date . '&page=' . $page . ' " method="post" style="text-align:center" id="groupOrdersForm">';
          $out    .= '<input type="hidden" name="client_id" value="' . $client_data['client_id'] . '"/>';
          $out    .= '<input type="hidden" name="prepayment_sum" value="' . $client_data['prepayment_sum'] . '"/>';
          $out    .= '<table class="groupTime">';
          $out    .= '<tr><th colspan="4" class="underline">' . date('d.m.Y', strtotime($date)) . '</th></tr>';
          $sum    = $old_reservation_sum = 0;
          $encash = false;
          foreach ($reservations_data as $rd) {
            $encash = $rd['encash'];
            $out    .= '<tr><td>' . date('H:i', strtotime($rd['start'])) . ' - ' . date(
                'H:i',
                strtotime($rd['finish'])
              ) . '</td><td> = </td><td><input name="reservations[' . $rd['reservation_id'] . ']" type="text" order_val="1" readonly value="' . $rd['price']
              . '" /></td><td>' . CURR_VALUTE . '</td></tr>';
            $sum    += $rd['price'];
            if ($encash == 2) {
              $old_reservation_sum += $rd['price'];
            }
          }
          $out   .= '<input type="hidden" name="old_reservations_sum" value="' . $old_reservation_sum . '"/>';
          $out   .= '<tr><th style="text-align:right">' . lang('Sum') . '</th><th> = </th><th><input type="text" name="reservations_sum" onkeyup="calculatePrice(this, \'groupOrdersForm\')" value="' . number_format(
              $sum,
              2,
              ',',
              ''
            ) . '" /></th><th>' . CURR_VALUTE . '</th></tr>';
          $out   .= '</table>';
          $out   .= '<br /><div class="groupEncash">';
          $textC = lang('Do you really want to change the payment method?', 'reservations');
          $out   .= '<input type="radio" name="encash" onclick="return ifConfirm(\'' . $textC . '\')" value="0" ' . ($encash == 0 ? 'checked' : '') . ' />' . lang('CASH') . ' &nbsp;';
          $out   .= '<input type="radio" name="encash" onclick="return ifConfirm(\'' . $textC . '\')" value="1" ' . ($encash == 1 ? 'checked' : '') . ' />' . lang('RE') . ' &nbsp;';
          $out   .= '<input type="radio" name="encash" onclick="return ifConfirm(\'' . $textC . '\')" value="4" ' . ($encash == 4 ? 'checked' : '') . '/>' . lang('EC') . '<br />';
          $out   .= '<input type="radio" name="encash" onclick="return ifConfirm(\'' . $textC . '\')" value="2" ' . ($encash == 2 ? 'checked' : '') . '/>' . lang('Credit balance') . ': '
            . NumberHelper::valute($client_data['prepayment_sum']);
          $out   .= '</div> <br />';
          $out   .= '<input type="submit" name="go" value="' . lang('button_save') . '" class="button_login"/> &nbsp;';
          $out   .= '<input type="button" name="cancel" value="' . lang('Cancel') . '" class="button_login" onclick="popup.closeWindow()"/>';
          $out   .= '</form>';
        } else {
          $out = lang('Error_message', 'message_error', ['error_message' => lang('reservation groups data failed', 'message_error')]);
        }
      } else {
        $out = lang('Error_message', 'message_error', ['error_message' => lang('reservation data failed', 'message_error')]);
      }
    } else {
      $out = lang('Error_message', 'message_error', ['error_message' => lang('input data error', 'message_error')]);
    }

    return $out;
  }

  function getPrepaymentAccount()
  {
    $out = '';
    if (isset ($_GET['account_id']) && isset ($_GET['client_id'])) {
      $a = getEngine('accounts');
      if ($account = $a->getPrepaymentAccountsById([(int)$_GET['account_id']])) {
        foreach ($account as $account_id => $account_data) {
          $out .= '<h1>' . config('accountView')->getNumberAccount($account_data['a_number'], PREPAYMENT_ACCOUNT_NUMBER) . '</h1>';
        }
        $out .= '<form action="clients.php?action=setPrepayment' . (isset($_GET['alpha']) ? '&alpha=' . $_GET['alpha'] : '') . '" method="post">';
        $out .= '<input type="hidden" name="account_id" value="' . $account_id . '"/>';
        $out .= '<input type="hidden" name="client_id" value="' . (int)$_GET['client_id'] . '"/>';

        $out .= '<table class="groupTime">';
        if (isset($account_data['reservations'])) {
          foreach ($account_data['reservations'] as $reservation_id => $reservation_data) {
            $out .= '<tr><th style="text-align:right">' . lang('Total price') . ' ' . NumberHelper::valute(
                $reservation_data['price']) . '</th><th> = </th><th><input type="text" name="prepayment_sum" value="' . number_format(
                $reservation_data['price'],
                2,
                ',',
                ''
              ) . '" /></th><th>' . CURR_VALUTE . '</th></tr>';
          }
        }
        $out .= '</table>';

        $out .= '<input type="submit" name="go" value="' . lang('button_save') . '" class="button_login"/>';
        $out .= '</form>';
      } else {
        $out = lang('Error_message', 'message_error', ['error_message' => lang('account data error', 'message_error')]);
      }
    } else {
      $out = lang('Error_message', 'message_error', ['error_message' => lang('input data error', 'message_error')]);
    }

    return $out;
  }

  function getStocksList()
  {
    $client_id = Service::request()->_('client_id');
    $date      = Service::request()->_('date');
    $time      = Service::request()->_('time');
    $client    = ReservServiceLocator::client($client_id);
    $area      = ReservServiceLocator::area(Service::request()->_('area_id'));
    $options   = ReservServiceLocator::priceOptions()->getPriceOptions($client, $date, [$time], $area);
    $out       = '';
    $useOption = [
      'stock'  => !empty($options['data']['stock']),
      'sprice' => !empty($options['data']['sprice']),
    ];
    if (!empty($options['data'])) {
      $out .= '<div style="display: flex">';
      if ($useOption['stock']) {
        /** @var PriceOptionsFormBlockDto $stock */
        $stock              = $options['data']['stock'];
        $stock->h3          = lang('show_order_block_options_stock', 'show_order');
        $stock->description = '';

        $out .= Service::mainPage()->render('reservations/views/admin/_options',
          ['priceOptionsBlock' => $stock, 'oneColumn' => !$useOption['sprice']]);
      }
      if ($useOption['sprice']) {
        /** @var PriceOptionsFormBlockDto $sprice */
        $sprice              = $options['data']['sprice'];
        $sprice->h3          = lang('show_order_block_options_spec_price', 'show_order');
        $sprice->description = lang('Select here to assign a different price to the hourly booking: (Replaces the standard rate)',
          'spec_price');

        $out .= Service::mainPage()->render('reservations/views/admin/_options',
          ['priceOptionsBlock' => $sprice, 'oneColumn' => !$useOption['stock']]);
      }
      $out .= '</div>';
    }


    $out .= '<br/><strong>' . lang('show_order_block_options_barzahlung_account', 'show_order') . ':</strong><br/>' . "\n";
    $out .= '<table border="0" cellpadding="0" cellspacing="0">';
    $out .= '<tr><td><input type="radio" name="prepayment" value="0" ' . ($client->encash === Encash::Cash ? 'checked' : '') . '/></td><td>' . lang(
        'show_order_block_options_barzahlung_account',
        'show_order'
      ) . '</td></tr>';
    $out .= '</table>';
    $out .= '<br/><strong>' . lang('show_order_block_options_invoice', 'show_order') . ':</strong><br/>' . "\n";
    $out .= '<table border="0" cellpadding="0" cellspacing="0">';
    $out .= '<tr><td><input type="radio" name="prepayment" value="1" ' . ($client->encash === Encash::Invoice ? 'checked' : '') . '/></td><td>' . lang(
        'show_order_block_options_on_bill',
        'show_order'
      ) . '</td></tr>';
    $out .= '</table>';
    $out .= '<br/><strong>' . lang('show_order_block_options_personal_account', 'show_order') . ':</strong><br/>' . "\n";
    $out .= '<table border="0" cellpadding="0" cellspacing="0">';
    $out .= '<tr><td><input type="radio" name="prepayment" value="2" ' . ($client->encash === Encash::PrivateAccount ? 'checked' : '') . '/></td><td>' . lang(
        'show_order_block_options_personal_account',
        'show_order'
      ) . ': <strong>' . NumberHelper::valute($client->prepaymentSum) . '</strong></td></tr>';
    $out .= '</table>';

    return $out;
  }

  function getAccountTextForm()
  {
    $out        = '<h2>' . lang('text_fields', 'accounts_view') . '</h2>';
    $account_id = Service::request()->_get('account_id');
    if ($account_id) {
      $a          = new AccountsEngine();
      $textFields = $a->getAccountTextFieldsById((int)$account_id);
      if (is_array($textFields)) {
        $out .= '<form action="accounts.php?action=changeAccountTextFields&account_type=' . Service::request()->_get('account_type') . '&type=' . Service::request()->_get(
            'type'
          ) . '" method="post">';
        $out .= '<input type="hidden" name="account_id" value="' . $account_id . '"/>';

        $out .= '<table class="groupTime">';
        foreach ($a->getDataAccountTextFields() as $key => $field) {
          $out .= '<tr><th style="text-align:right">' . $field['title'] . '</th><th>';
          switch ($field['editor']) {
            case 'textarea':
              $out .= '<textarea name="text_fields[' . $key . ']" class="input" id="' . $key . '_f" style="width:300px;">' . (empty($textFields[$key])
                  ? '' : $textFields[$key]) . '</textarea>';
              break;
            default:
              $out .= '<input type="text" name="text_fields[' . $key . ']" id="' . $key . '_f" style="width:300px;text-align:left" value="' . (empty($textFields[$key])
                  ? '' : $textFields[$key]) . '" />';
              break;
          }
          $out .= '</th></tr>';
        }
        $out .= '</table>';
        $out .= '<script>';
        foreach ($a->getDataAccountTextFields() as $key => $field) {
          if ($field['editor'] == 'textarea') {
            $out .= 'CKEDITOR.replace(\'' . $key . '_f\', {removeButtons: \'Link,Unlink,Table,SpecialChar,FontFormat,FitWindow,Image,HorizontalRule,SpecialChar,PageBreak,Font,FontSize,Maximize,ShowBlocks\'});';
          }
        }
        $out .= '</script>';
        $out .= '<input type="submit" name="go" value="' . lang('button_save') . '" class="button_login"/>';
        $out .= '</form>';
      } else {
        $out = lang('Error_message', 'message_error', ['error_message' => lang('account data error', 'message_error')]);
      }
    } else {
      $out = lang('Error_message', 'message_error', ['error_message' => lang('input data error', 'message_error')]);;
    }

    return $out;
  }

}

if (isset($_GET['action'])) {
  $action = $_GET['action'];
} else {
  if (isset($_POST['action'])) {
    $action = $_POST['action'];
  }
}

if (isset($action)) {
  $ad = new AjaxData($action);
  echo $ad->start();
}
?>
