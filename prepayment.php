<?php

use AC\core\modules\mailing\entities\enums\ModeTemplate;
use AC\core\modules\payment\payone\entities\enums\OnlineGateway;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\modules\text\engines\TextEngine;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\ReCaptchaHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;

$r = Service::engines();
$client = $r->clients->current_client_data ?? [];

$_page['key']        = 'prepayment';
$_page['title']      = lang(
    'title',
    'prepayment'
  ) . ' ' . $client['name'] . ' ' . $client['surname'];
$_page['meta-title'] = lang('title', 'prepayment');
$out                 = '	<div class="content prepayment">' . "\n";
if (!empty($client) && $client['client_id']) {
  if (Service::request()->_get('action') === 'sendquery') {
    $tpl_data['CLIENT_NAME']    = $client['name'];
    $tpl_data['CLIENT_SURNAME'] = $client['surname'];
    $tpl_data['EMAIL']          = $client['email'];
    $tpl_data['PRICE']          = NumberHelper::valute($_POST['prepayment_sum'] ?? 0);
    if (Service::mailer()->dispatch(explode(',', Service::configDB('email', 'notify_email')), ModeTemplate::ADMIN->value, 'query_prepayment', $tpl_data)) {
      $out .= lang('thank_you_very_much_for_your_inquiry', 'prepayment');
    } else {
      $out .= lang('error_query_failed', 'prepayment');
    }

    $out .= '<p style="text-align:center"><a href="' . site_url('prepayment.php') . '" class="back">' . lang('back') . '</a></p>';
  } elseif (Service::request()->_get('action') === 'activecoupon') {
    if (!$r->clients->blockingCouponInput($client['client_id'])) {
      if (Service::session()->exists('cnt')) {
        Service::session()->set('cnt', (Service::session()->get('cnt') ?: 0) + 1);
      }
      if (Service::session()->get('cnt') > 5) {
        $r->clients->setBlockingCouponInput($client['client_id']);
      }
      if (ReCaptchaHelper::checkReCaptcha('g-recaptcha-response', 0.6)) {
        if (Service::session()->get('cnt') <= 5) {
          if ($coupon = $r->coupons->getCouponsByCode(Service::request()->_post('coupon_code'))) {
            if ($r->coupons->activeCouponCode($coupon['code_id'], $client['client_id'])) {
              $response = ModCommHelper::callSafe('clients', 'PrivateAccount/deposit',
                [
                  'client_id'    => (int)$client['client_id'],
                  'type_code'    => 'coupon_applied',
                  'amount'       => $coupon['price'],
                  'related_data' => $coupon,
                  'related_id'   => (int)$coupon['coupon_id']
                ]
              );
              if ($response->isSuccess()) {
                $out .= '<br /><p>' . lang('your_voucher_has_been_activated', 'prepayment', array(
                    'price' => NumberHelper::valute($coupon['price'])
                  )) . '</p>';
              } else {
                $out .= '<br /><p>' . lang('active_error', 'prepayment') . ' #' . $response->getMessage() . '</p>';
              }
            } else {
              $out .= '<br /><p>' . lang('your_voucher_code_is_invalid', 'prepayment') . '</p>';
            }
          } else {
            if(!Service::session()->exists('cnt')) {
              Service::session()->set('cnt', (Service::session()->get('cnt') ?: 0) + 1);
            }
            sleep(2);
            $out .= '<br /><p>' . lang('your_voucher_code_is_invalid', 'prepayment') . '</p>';
          }
        } else {
          $out .= '<br /><p>' . lang('You have made more than {{number}} attempts in a short period of time.', 'prepayment', ['number' => 5]) . '</p>';
        }
      } else {
        $out .= '<br /><p>' . lang('Recaptcha thinks you are a bot.', 'prepayment') . '</p>';
      }
    } else {
      $out .= '<br /><p>' . lang('More than {{number}} attempts have been made to enter the code. Access is blocked until tomorrow.', 'prepayment', ['number' => 5]) . '</p>';
    }

    $out .= '<p style="text-align:center">
              <a href="' . site_url('prepayment.php') . '">' . lang('button_send') . '</a></p>';
  } else {
    //Данные клиента
    $out .= '<p><strong>' . lang('your_credit', 'prepayment') . ': ' . NumberHelper::valute(
        $client['prepayment_sum']) . '</strong></p>' . "\n";
    if (SHOW_PREPAYMENT_ADMIN_MAIL) {
      $out .= '<p>' . lang('you_can_use_this_to_top_up_your_credit_account', 'prepayment') . '</p>' . "\n";
      $out .= '<div class="row">
            <div class="col-md-8">
              <form action="' . site_url('prepayment.php?action=sendquery') . '" method="post">
                  <input type="text" name="prepayment_sum" value="0,00"/>
                  <input type="submit" name="go" value="' . lang('button_send_request', 'prepayment') . '" class="button"/>
              </form>
            </div>
         </div>' . "\n";
    }
    // платежные системы (варианты пополнения)
    if (config('payment')->useOnlinePayment()) {
      // реклама или описание
      /** @var TextEngine $textEngine */
      $textEngine = getEngine('text', false);
      if($textEngine?->getContent($row, 'prepayment') && !empty($row['content'])) {
        $out .= $row['content'];
      }
      $out .= match (OnlineGatewayService::runtimeGateway()) {
        OnlineGateway::PAYONE->value => module('payment', ['subName' => 'payone'])->execContent('showListReplenishmentBalance'),
        OnlineGateway::PAYPAL->value => module('payment', ['mode' => 'old', 'subName' => 'paypal'])->execContent('showListReplenishmentBalance'),
        default => '',
      };
    }

    if (defined('GUTHABEN_COUPONS') && GUTHABEN_COUPONS) {
      //Купоны
      $out .= '<p>' . "\n";
      if (!$r->clients->blockingCouponInput($client['client_id'])) {
        $out .= '<h2>' . lang('redeem_voucher', 'prepayment') . '</h2>';
        $out .= '<div class="row">' . "\n";
        $out .= '<div class="col-md-8">' . "\n";
        $out .= '<form action="' . site_url('prepayment.php?action=activecoupon') . '" method="post">' . "\n";
        $out .=  ReCaptchaHelper::getReCaptchaScript('coupon');
        $out .= '<input type="text" name="coupon_code" value=""/>' . "\n ";
        $out .= '<input type="submit" name="go" value="' . lang('button_send_request',
            'prepayment') . '" class="button" style="margin-top: 20px!important;"/>' . "\n";
        $out .= '</form>' . "\n";
        $out .= '</div>' . "\n";
        $out .= '</div>' . "\n";
      } else {
        $out .= '<br /><p>' . lang('More than {{number}} attempts have been made to enter the code. Access is blocked until tomorrow.', 'prepayment', ['number' => 5]) . '</p>';
      }
      if ($items = $r->coupons->getCouponsByClient($client['client_id'])) {
        $out .= '<br /><br />';
        $out .= '<h2>' . lang('my_vouchers', 'prepayment') . '</h2>';
        $out .= '<div class="table-adaptive">';
        $out .= '<table class="clientReservations">';
        foreach ($items as $item) {
          $out .= '<tr>';
          $out .= '<td>' . $item['title'] . '</td>';
          $out .= '<td>' . $item['code'] . '</td>';
          $out .= '<td>+' . NumberHelper::valute($item['price']) . '</td>';
          $out .= '<td>' . date('d.m.Y', strtotime($item['actived_date'])) . '</td>';
          $out .= '</tr>';
        }
        $out .= '</table>';
        $out .= '</div>';
      }
      $out .= '</p>' . "\n";
    }
  }
} else {
  $out .= lang('error_log_in_to_view', 'prepayment');
}

$out .= '	</div>' . "\n";


$_page['content'][1] = $out;
