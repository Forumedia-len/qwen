<?php

namespace AC\core\modules\payment\paypal\controllers;

use AC\core\engines\AccountsEngine;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use Service;

class PaypalPaymentOldController extends PaypalPaymentController
{
  protected $baseUrl          = 'payment/paypal/old/';
  public    $default_template = 'old';

  public function checkDetails()
  {
    $token = Service::request()->_('token');
    if (!$token) {
      $url = site_url($this->baseUrl);

      $currency_code = CURR_VALUTE_PP;
      $payment_type  = 'Sale';
      [$ids, $payment_amount] = explode(';', Service::request()->_post('payment_data'));

      $payment_amount = NumberHelper::format($payment_amount, 2, '.');
      $returnURL = 'checkDetails?currency_code=' . $currency_code . '&payment_type=' . $payment_type . '&payment_amount=' . $payment_amount . '&ids=' . $ids . '&typeDetails=' . $this->typeDetails;
      $this->session->set('returnURL', $returnURL);
      $returnURL = urlencode($url . $returnURL);
      $cancelURL = urlencode($url . 'cancel?payment_type=' . $payment_type . '&typeDetails=' . $this->typeDetails);

      $desc = '';
      switch ($this->typeDetails) {
        case 'payPerGame':
          $this->session->set('reservation_id', $ids);
          $idsArr = explode('|', $ids);
          if (Service::engines()->getReservationsOnBasisOfTmp($idsArr)) {
            return $this->reservationsError($idsArr, lang('The periods are already booked', 'message_error'));
          }
          $desc .= 'Abspielbetrag ' . NumberHelper::format($payment_amount) . ' ' . CURR_VALUTE . '|';

          if (Service::engines()->getReservationDataByIds($idsArr, $items, true)) {
            $arrDesc = [];
            $s_b_t = ModCommHelper::get('areas', 'areas/relevantSportsByType', [], 'sportsByType', []);
            foreach ($items as $item) {
              $keyD                      = $s_b_t[$item['type_id'] . '_' . $item['sport_id']]->title . ' ' . $item['area_title']
                . ' ' . date('d.m.Y', strtotime($item['start']));
              $arrDesc[$keyD]['times'][] = strtotime($item['start']);
              $arrDesc[$keyD]['period']  = $item['period'];
            }
            foreach ($arrDesc as $keyDesc => $item) {
              $desc .= $keyDesc . ' '
                . implode(';', TimeHelper::getTimeByStartFinish($item['times'], $item['period'], true, false, '-'));
            }
          }

          break;
        case 'replenishmentBalance':
          $this->session->set('pp_id', $ids);
          Service::engines()->pp->getPP($ids, $items);
          $desc .= 'Zahlbetrag ' . NumberHelper::format($items['price_real']) . ' ' . CURR_VALUTE
            . ' -> Abspielbetrag ' . NumberHelper::format($items['price_account']) . ' ' . CURR_VALUTE;
          break;
        default:
          return $this->error();
      }

      $nvpstr   = "&Amt=" . $payment_amount . "&PAYMENTACTION=" . $payment_type . "&ReturnUrl=" . $returnURL . "&CANCELURL=" . $cancelURL . "&CURRENCYCODE=" . $currency_code . "&NOSHIPPING=1&LOCALECODE=de_DE&DESC=" . substr($desc,
          0, 127);
      $resArray = $this->model->hashCall("SetExpressCheckout", $nvpstr);
    } else {
      $nvpstr   = "&TOKEN=" . urlencode($token);
      $resArray = $this->model->hashCall("GetExpressCheckoutDetails", $nvpstr);
    }
    $this->session->set('reshash', $resArray);

    if ($resArray && isset($resArray["ACK"]) && (strtoupper($resArray["ACK"]) === "SUCCESS"
        || str_starts_with($resArray["ACK"], 'Success'))) {
      if (!$token) {
        if ($this->typeDetails === 'payPerGame' && !empty($resArray['TOKEN'])) {
          $ppToken = trim((string)$resArray['TOKEN']);
          $idsRaw  = (string)$this->session->get('reservation_id');
          foreach (array_filter(explode('|', $idsRaw)) as $rid) {
            Service::engines()->setPayOneReference($rid, $ppToken);
          }
        }
        $returnURL = $this->session->getWithDelete('returnURL');
        return $this->model->redirectToPayPal($returnURL, $resArray);
      }
      
      if ($this->typeDetails === 'payPerGame' && ($idsArr = explode('%7C',
          urlencode($this->session->get('reservation_id')))) && Service::engines()->getReservationsOnBasisOfTmp($idsArr)) {
        return $this->reservationsError($idsArr, lang('The periods are already booked', 'message_error'));
      }
      
      return $this->getExpressCheckoutDetails();
    }

    return $this->error();
  }

  public function payment()
  {
    $insert_errors         = [];
    $createdReservationIds = [];
    if ($this->session->exists('token') && $this->session->exists('payer_id')) {
      $token          = urlencode($this->session->getWithDelete('token'));
      $payment_amount = NumberHelper::format(urlencode($this->session->getWithDelete('payment_amount')), 2, '.');
      $payment_type   = urlencode($this->session->getWithDelete('payment_type'));
      $currency_code  = urlencode($this->session->getWithDelete('currency_code'));
      $payer_id       = urlencode($this->session->getWithDelete('payer_id'));
      $serverName     = urlencode(site_url());

      $nvpstr        = '&TOKEN=' . $token . '&PAYERID=' . $payer_id . '&PAYMENTACTION=' . $payment_type . '&AMT=' . $payment_amount . '&CURRENCYCODE=' . $currency_code . '&IPADDRESS=' . $serverName;
      $door_code_out = '';
      $message       = '';
      switch ($this->typeDetails) {
        case 'payPerGame':
          $check = $this->payPerGame($door_code_out, $insert_errors, $message, $createdReservationIds);
          if (!$check) {
            return $this->reservationsError($insert_errors, $message);
          }
          break;
        case 'replenishmentBalance':
          $check = $this->replenishmentBalance($prepayment_sum, $account_id, urldecode($token));
          $this->session->set('account_id', $account_id);
          $this->session->set('prepayment_sum', $prepayment_sum);
          break;
        default:
          $check = false;
      }
      if ($check) {
        $resArray = $this->model->hashCall("DoExpressCheckoutPayment", $nvpstr);
        Service::session()->set('reshash', $resArray);
        if (isset($resArray["ACK"]) && strtoupper($resArray["ACK"]) === "SUCCESS") {
          if ($this->typeDetails === 'payPerGame') {
            $txnId = trim((string)($resArray['TRANSACTIONID'] ?? ''));
            if ($txnId !== '') {
              Service::engines()->appendTmpPaypalReferenceTransactionId(urldecode($token), $txnId);
            }
            /** @var AccountsEngine|false $accounts */
            $accounts = getEngine('accounts', false);
            if ($accounts) {
              $accounts->createOnlinePaymentInvoiceForReservations(
                $createdReservationIds,
                urldecode($token),
                $txnId,
                null,
                (string)$this->session->get('configTypeKey'),
                [
                  'name'    => (string)($_SESSION['name'] ?? ''),
                  'surname' => (string)($_SESSION['surname'] ?? ''),
                  'email'   => (string)($_SESSION['email'] ?? ''),
                ]
              );
            }
          }
          if ($this->typeDetails === 'replenishmentBalance') {
            $txnId = trim((string)($resArray['TRANSACTIONID'] ?? ''));
            $accId = (int)$this->session->get('account_id');
            if ($accId > 0 && $txnId !== '') {
              $tok = urldecode($token);
              /** @var AccountsEngine|false $accounts */
              $accounts = getEngine('accounts', false);
              if ($accounts) {
                $existing = $accounts->getPrepaymentAccountPriceInfo($accId);
                $core      = 'paypal|' . $tok . '|' . $txnId;
                $accounts->updatePrepaymentAccountPriceInfo(
                  $accId,
                  OnlineGatewayService::mergePrepaymentPriceInfoWithExistingTypeKeyTail($core, $existing)
                );
              }
            }
          }
          $this->unsetParamsSession();

          return $this->render(
            'thank_you',
            [
              'door_code_out' => $door_code_out,
              'currency_code' => $currency_code,
              'resArray'      => $resArray,
              'typeDetails'   => $this->typeDetails,
              'message'       => $message
            ]
          );
        }
      }
    }

    return $this->error($insert_errors);
  }

  public function getExpressCheckoutDetails()
  {
    $this->session->set('token', Service::request()->_('token'));
    $this->session->set('payer_id', Service::request()->_('PayerID'));
    $paymentAmount = NumberHelper::format(Service::request()->_('payment_amount'), 2, '.');
    $this->session->set('payment_amount', $paymentAmount);

    $this->session->set('currency_code', Service::request()->_('currency_code'));
    $this->session->set('payment_type', Service::request()->_('payment_type'));

    return $this->render('get_express_checkout_details', [
      'paymentAmount' => $paymentAmount,
      'currencyCode'  => Service::request()->_('currency_code'),
      'actionUrl'     => $this->baseUrl . 'payment'
    ]);
  }

  protected function setView($key = 'payment_paypal')
  {
    parent::setView($key);
  }
}
