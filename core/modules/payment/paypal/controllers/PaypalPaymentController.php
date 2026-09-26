<?php

namespace AC\core\modules\payment\paypal\controllers;

use AC\core\modules\config\models\ConfigPPModel;
use AC\core\modules\payment\payone\entities\enums\OnlineGateway;
use AC\core\modules\payment\paypal\models\PaypalPaymentModel;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\modules\payment\services\OnlinePaymentPostProcessingService;
use AC\core\system\controller\BaseController;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\session\Session;
use AC\core\engines\Engines;
use Service;

class PaypalPaymentController extends BaseController
{
  /**
   * @var Engines
   */
  protected $engine;
  /**
   * @var Session
   */
  protected $session;
  protected $base_model = 'PaypalPaymentModel';
  /**
   * @var PaypalPaymentModel
   */
  public    $model;
  protected $baseUrl = 'payment/paypal/';
  
  protected $tpl_view = 'payment\paypal';
  protected $typeDetails;
  protected $token;
  
  
  public function __construct($action = false, $runController = true)
  {
    if(!config('payment')->useFullProcessing()) {
      $this->base_model = 'LocMockPaypalPaymentModel';
    }
    $this->session     = Service::session();
    $this->typeDetails = Service::request()->_post(
      'typeDetails',
      ($this->session->exists('typeDetails') ? $this->session->get('typeDetails') : null)
    );
    if ($this->typeDetails) {
      $this->session->set('typeDetails', $this->typeDetails);
    }
    if($configTypeKey = Service::request()->_post('configTypeKey', ($this->session->exists('configTypeKey') ? $this->session->get('configTypeKey') : null))) {
      $this->session->set('configTypeKey', $configTypeKey);
      OnlineGatewayService::paypalConfig(['typeKey' => $configTypeKey]);
    }
    parent::__construct($action, $runController);
  }
  
  protected function getModulePath($modulePath = null)
  {
    return parent::getModulePath('payment\paypal');
  }
  
  public function showListReplenishmentBalance()
  {
    Service::engines()->pp->getData($items);
    foreach ($items as $key => $item) {
      /** @var ConfigPPModel $item */
      $item->price_real_title    = NumberHelper::format($item->price_real);
      $item->price_account_title = NumberHelper::format($item->price_account);
      $item->valute              = CURR_VALUTE;
      $items[$key]               = $item;
    }
    
    return $this->render(
      'balance/list',
      [
        'client_id' => $this->engine->clients->current_client_data['client_id'],
        'items'     => $items,
        'action'    => site_url($this->baseUrl . '/checkDetails')
      ]
    );
  }
  
  
  public function error($rids = [])
  {
    $this->setStatus();
    $this->removeReservations($rids);
    $this->cancelReplenishmentBalance();
    [$reshash, $nvpReqArray] = $this->unsetParamsSession();
    
    if ($this->session->exists('errors')) {
      $errors = $this->session->getWithDelete('errors');
      
      return $this->render('errors/error_url', [
        'errorCode'    => $errors['number'],
        'errorMessage' => $errors['message']
      ]);
    }
    
    return $this->render('errors/error_response', [
      'resArray' => $reshash
    ]);
  }
  
  public function reservationsError($rids = [], $errorMessage = '')
  {
    $this->setStatus();
    $this->removeReservations($rids);
    $this->unsetParamsSession();
    
    return $this->render('reservation/reservation_error', ['errorMessage' => $errorMessage]);
  }
  
  public function cancel()
  {
    switch ($this->typeDetails) {
      case 'payPerGame':
        $this->setStatus('CANCEL');
        $this->unsetParamsSession();
        
        return $this->render('reservation/reservation_false');
      case 'replenishmentBalance':
        $this->unsetParamsSession();
        return Service::redirect()->redirect(site_url())->send();
      default:
        return $this->error();
    }
  }
  
  protected function setStatus($status = 'ERROR')
  {
    if ($this->session->exists('reservation_id') && !empty($this->session->get('reservation_id'))) {
      $rids = explode('|', $this->session->getWithDelete('reservation_id'));
      if (is_array($rids)) {
        foreach ($rids as $rid) {
          $this->engine->setPayPalStatus($rid, $status);
        }
      }
    }
    $this->session->delete('reservation_id');
  }
  
  protected function removeReservations($rids = [])
  {
    foreach ($rids as $rid) {
      $this->engine->removeReservationById($rid);
    }
  }
  
  protected function unsetParamsSession()
  {
    return [
      $this->session->getWithDelete('reshash'),
      $this->session->getWithDelete('nvpReqArray'),
      $this->session->getWithDelete('typeDetails'),
      $this->session->getWithDelete('account_id'),
      $this->session->getWithDelete('prepayment_sum'),
      $this->session->getWithDelete('configTypeKey'),
    ];
  }
  
  protected function payPerGame(
    &$door_code_out = '',
    &$insert_errors = [],
    &$message = '',
    array &$createdReservationIds = [],
  )
  {
    $r_ids = explode('%7C', urlencode($this->session->getWithDelete('reservation_id')));
    if (Service::engines()->getReservationsOnBasisOfTmp($r_ids)) {
      $message       = lang('The periods are already booked', 'message_error');
      $insert_errors = $r_ids;
      
      return false;
    }
    $result                = OnlinePaymentPostProcessingService::createReservations($r_ids, 'FALSE');
    $createdReservationIds = $result['reservationIds'];
    $message               = $result['message'];
    $door_code_out         = $result['doorCode'];
    if ($result['success']) {
      return true;
    }

    // Уже добавленные бронирования нужно удалить при частично неуспешной операции.
    $insert_errors = $createdReservationIds;
    
    return false;
  }
  
  
  public function replenishmentBalance(&$prepayment_sum = 0, &$account_id = null, ?string $paypalExpressCheckoutToken = null)
  {
    $pp_id = urlencode($this->session->getWithDelete('pp_id'));
    if (!$pp_id) {
      return false;
    }
    $priceInfo = $paypalExpressCheckoutToken !== null && $paypalExpressCheckoutToken !== ''
      ? ('paypal|' . $paypalExpressCheckoutToken . OnlineGatewayService::encodePrepaymentPriceInfoTypeKeyTail())
      : 'paypal';
    $relatedData = ['payment_profile' => OnlineGateway::PAYPAL->value];
    if ($paypalExpressCheckoutToken !== null && $paypalExpressCheckoutToken !== '') {
      $relatedData['reference'] = $paypalExpressCheckoutToken;
    }

    return OnlinePaymentPostProcessingService::replenishPrivateAccount(
      $pp_id,
      Service::engines()->clients->current_client_data,
      $priceInfo,
      $relatedData,
      $prepayment_sum,
      $account_id,
      null,
      true
    );
  }
  
  protected function cancelReplenishmentBalance()
  {
    if ($this->session->exists('account_id') && $this->session->exists('prepayment_sum')) {
      $account_id     = $this->session->getWithDelete('account_id');
      $prepayment_sum = $this->session->getWithDelete('prepayment_sum');
      $client         = Service::engines()->clients->current_client_data;
      OnlinePaymentPostProcessingService::rollbackPrivateAccount(
        (int)$client['client_id'],
        (int)$account_id,
        $prepayment_sum
      );
    }
  }
  
}
