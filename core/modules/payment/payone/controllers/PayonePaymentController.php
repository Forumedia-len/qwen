<?php

namespace AC\core\modules\payment\payone\controllers;

use AC\app\config\LangConfig;
use AC\core\modules\config\models\ConfigPPModel;
use AC\core\modules\payment\payone\actions\orders\PaymentOrder;
use AC\core\modules\payment\payone\helpers\PayoneErrorHelper;
use AC\core\modules\payment\payone\http\gateway\AbstractGateway;
use AC\core\modules\payment\payone\services\PayoneDataService;
use AC\core\modules\payment\payone\services\PayoneService;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\system\controller\BaseController;
use AC\core\system\db\Query;
use AC\core\system\helpers\JsonHelper;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\RedisHelper;
use Service;

class PayonePaymentController extends BaseController
{
  protected $useBaseModel = false;
  protected $baseUrl = 'payment/payone/';

  protected $tpl_view = 'payment\payone';

  public function start()
  {
    /** @var LangConfig $configLang */
    $configLang = config('lang');
    $this->initData();
    if ($configLang->getCurrentLang() !== $configLang->getDefault()) {
      Service::lang($configLang->getDefault())->addFile('', ($this->getModulePath(true) . paths()->getLangDir()));
    }

    return parent::start();
  }

  public function authorization()
  {
    if (PayoneService::session()->hasValidSessionKey()) {
      PayoneService::session()->unsetValidKeys();
    }
    $errorCode = null;
    if (Service::request()->isPost()) {
      $r            = Service::request()->_post('r');
      $h            = Service::request()->_post('h');
      $paymentOrder = $this->makePaymentOrder(Service::request()->_post('paymentType'));
      if ($r && $h && $h === md5($r . PayoneDataService::portal()->key)) {
        PayoneDataService::portal()->param .= '|' . $paymentOrder->getParam();
        if ($paymentOrder->setDataRequest(PayoneDataService::personal(), PayoneDataService::order(), PayoneDataService::portal(), $errorCode)) {
          $this->setPayOneLog('sendRequest', 'authorization', $paymentOrder->getParam());
          if (config('payment')->useFullProcessing()) {
            $payType = strtolower(trim((string)Service::request()->_post('pay_type', '')));
            // Требование: карта и PayPal (pp) идут через `frontend` (не через post-gateway).
            $rawResponse = $this->makeGateway($this->resolveGatewayByPayType($payType))->sendRequest('authorization', $this->getData());
            return $this->renderResponse($rawResponse, $paymentOrder);
          } else {
            // Локальная обработка / mock — оставляем прежний редирект на frontend/v2 или mock.php.
            $this->makeGateway()->sendRequest('authorization', $this->getData());
          }
          die;
        }
      } else {
        $errorCode = '2';
      }
    }

    return $this->error($errorCode ?? '1');
  }

  protected function renderResponse($rawResponse, PaymentOrder $paymentOrder)
  {
    $response = $this->parseServiceResponse($rawResponse);
    switch ($response['status']) {
      case 'ERROR':
        $errorCode = $response['errorcode'] ?? '7';
        return $this->error($errorCode, 'FALSE', $response['customermessage'] ?? null, $response);
      case 'APPROVED':
      case 'PENDING':
        $this->setPayOneLog('response', $response['status'], $paymentOrder->getParam(), $response);
        return $this->success();
      case 'REDIRECT':
        if (!empty($response['redirecturl'])) {
          $this->setPayOneLog('sendRequest', 'REDIRECT', $paymentOrder->getParam(), $response);
          header('Location:' . $response['redirecturl']);
        }
        die;
    }

    return true;
  }

  /**
   * Выбор канала Payone по способу оплаты (pay_type из формы).
   *
   * Возвращает имя gateway для makeGateway(): 'server' | 'frontend'.
   */
  private function resolveGatewayByPayType(string $payType): string
  {
    return match (strtolower(trim($payType))) {
      'cc', 'pp' => 'frontend',
      default    => 'server',
    };
  }

  public function success(): string
  {
    $session = PayoneService::session();
    if ($session->hasValidSessionKey() && $session->exists('reference')) {
      $reference                             = $session->get('reference');
      PayoneDataService::portal()->reference = $reference;
      PayoneDataService::order()->setAmount(PayoneService::session()->get('amount'));
      $paymentOrder = $this->makePaymentOrder($session->getPaymentOrderPrefix());
      if (($status = $paymentOrder->checkStatusPayment($reference)) && $status == 'OK') {
        $this->setPayOneLog('success', 'OK', $paymentOrder->getParam());
        $message = lang('payone_success' . ($session->hasValidSessionKey() ? '_' . $session->sessionKey() : ''), 'message_success');
        PayoneService::session()->unsetValidKeys();
        $params = RedisHelper::get($reference) ?? [];
        RedisHelper::delete($reference);

        return PayoneService::response()->success($message, $params);
      }
      if ($status == 'FALSE') {
        $this->setPayOneLog('success', 'FALSE', $paymentOrder->getParam(), RedisHelper::get($reference));

        return $this->error('7');
      }

      return PayoneService::response()->wait(
        [
          'reference'  => $reference,
          'urlDataDto' => PayoneDataService::url(['useUrlCreation' => true]),
          'orderType'  => $session->getPaymentOrderPrefix(),
        ]);
    }

    return $this->error('6');
  }

  public function error(
    ?string $_messageCode = null,
    ?string $status = 'FALSE',
    ?string $messagePayone = null,
    ?array $additionalLogData = []
  ): string {
    $messageCode = $_messageCode ?? Service::request()->_get('messageCode');
    $status = Service::request()->_get('status') ?? $status;
    $sendLog = false;
    if (PayoneService::session()->hasValidSessionKey()) {
      if (PayoneService::session()->exists('reference')) {
        $reference                             = PayoneService::session()->get('reference');
        PayoneDataService::portal()->reference = $reference;
        PayoneDataService::order()->setAmount(PayoneService::session()->get('amount'));
        $paymentOrder = $this->makePaymentOrder(PayoneService::session()->getPaymentOrderPrefix());
        $paymentOrder->delete($reference, $status);
        $messageCode = $_messageCode ?? RedisHelper::get($reference, 'messageCode', $messageCode);
        if ($messageCode !== 'error_two_transactions') {
          $paymentOrder->setStatus($reference, $status);
        }
        $this->setPayOneLog('error', $status, $paymentOrder->getParam(), $additionalLogData ?? RedisHelper::get($reference));
        RedisHelper::delete($reference);
        $sendLog = true;
      }
      if ($messageCode !== 'error_two_transactions') {
        PayoneService::session()->unsetValidKeys();
      }
    }
    if(!$sendLog) {
      $this->setPayOneLog('error', $status, 'notSession', ['notSession']);
    }
    $message = PayoneErrorHelper::getError($messageCode ?? '7') . ($messagePayone ? '<br>' . $messagePayone : '');

    return PayoneService::response()->error($message);
  }

  public function back(): string
  {
    return $this->error('You canceled the order', 'CANCEL');
  }

  public function status(): void
  {
    if (Service::request()->isPost() && ($post = Service::request()->_post()) && !empty($post['txaction'])) {
      $txaction = $post['txaction'];
      $this->initData($post);
      PayoneDataService::order()->setAmount($post['price']);
      [$mode, $typeKey, $param, $ids, $client_id] = explode('|', PayoneDataService::portal()->param);
      $paymentOrder = $this->makePaymentOrder($param == 'ATPRPM' ? 'accountReplenishment' : 'reservation');
      $this->setPayOneLog('status', $txaction, $param);
      $reference = PayoneDataService::portal()->reference;
      switch ($txaction) {
        case 'paid':
          $paymentOrder->payment($reference, $ids, $client_id);
          break;
        case 'failed':
          $paymentOrder->delete($reference, 'FAILED');
          break;
        default:
          break;
      }
    }
  }

  public function statusPayment(): string
  {
    $reference = Service::request()->_get('reference');
    $orderType = Service::request()->_get('type');
    if ($reference && $orderType) {
      return $this->makePaymentOrder($orderType)->checkStatusPayment($reference) ?? 'WAIT';
    }

    return 'WAIT';
  }

  protected function parseServiceResponse($response): array
  {
    if (is_array($response)) {
      return $response;
    }
    if (!is_string($response) || trim($response) === '') {
      return [];
    }

    $lines  = preg_split('/\r\n|\r|\n/', $response) ?: [];
    $parsed = [];
    foreach ($lines as $line) {
      $line = trim((string)$line);
      if ($line === '' || strpos($line, '=') === false) {
        continue;
      }
      // limit=2: redirecturl contains '='
      [$k, $v] = explode('=', $line, 2);
      $k = trim((string)$k);
      if ($k === '') {
        continue;
      }
      $parsed[$k] = (string)$v;
    }

    return $parsed;
  }

  public function getData($asArray = true, $byAlias = true)
  {
    $data = [];

    $portalData   = $asArray ? PayoneDataService::portal()->toArray() : PayoneDataService::portal();
    $orderData    = $asArray ? PayoneDataService::order()->toArray() : PayoneDataService::order();
    $personalData = $asArray ? PayoneDataService::personal()->toArray() : PayoneDataService::personal();
    $urlData      = $asArray ? PayoneDataService::url()->toArray() : PayoneDataService::url();

    if ($byAlias) {
      $data['portalData']   = $portalData;
      $data['orderData']    = $orderData;
      $data['personalData'] = $personalData;
      $data['urlData']      = $urlData;
    } else {
      $data = array_merge($portalData, $orderData, $personalData, $urlData);
    }

    return $data;
  }

  /**
   * @param string $namePaymentOrder
   *
   * @return PaymentOrder
   */
  public function makePaymentOrder(string $namePaymentOrder = 'reservation'): PaymentOrder
  {
    $className = $this->getModulePath() . 'actions\orders\\' . ucfirst($namePaymentOrder) . 'Order';

    return useClass($className, true);
  }

  /**
   * @param string $nameGateway
   *
   * @return AbstractGateway
   */
  public function makeGateway(string $nameGateway = 'frontend'): AbstractGateway
  {
    $className = $this->getModulePath() . 'http\gateway\\' . ucfirst($nameGateway) . 'Gateway';

    return useClass($className, true, $this->getModulePath() . 'http\\request\\');
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
        'client_id' => Service::engines()->clients->current_client_data['client_id'],
        'items'     => $items,
        'action'    => site_url($this->baseUrl . '/authorization'),
        'methods'   => OnlineGatewayService::payoneConfig()->renderMethods('balance'),
      ]
    );
  }

  /**
   * @param string $action
   * @param string $status
   * @param string $paymentParam
   * @param array  $totals
   *
   * @return void
   */
  private function setPayOneLog(string $action, string $status, string $paymentParam, array $totals = []): void
  {
    if (defined('USE_PAY_ONLINE_LOG') && USE_PAY_ONLINE_LOG) {
      $tableLogs   = 'payone_logs';
      $typePayment = RedisHelper::get(PayoneDataService::portal()->reference, 'typePayment') ?? PayoneDataService::order()->typePayment();
      if (Service::query()::getDB()->checkTable($tableLogs)) {
        $sql    = 'INSERT INTO ' . Query::tableName($tableLogs) . ' SET '
          . 'action = ?, date_create = NOW(), type_order = ?, type_payment = ?, client_id = ?, '
          . 'status = ?, `get` = ?, `post` = ?, `session` = ?, reference = ?, data_payment = ?, '
          . 'price = ?, totals = ?';
        $params = [
          $action,
          $paymentParam,
          $typePayment,
          $this->payoneLogClientId(),
          $status,
          JsonHelper::encode($_GET),
          JsonHelper::encode($_POST),
          JsonHelper::encode($_SESSION),
          PayoneDataService::portal()->reference,
          JsonHelper::encode($this->getData()),
          PayoneDataService::order()->getAmountAsString(),
          JsonHelper::encode($totals),
        ];
        Query::sqlQuery($sql, $params, false);
      }
    }
  }

  /**
   * Получить числовой ID клиента для журнала Payone.
   *
   * В серверном callback ID зарегистрированного клиента хранится в `portal.param`.
   * Для гостя используется значение `0`, совместимое с целочисленным полем старых таблиц логов.
   */
  private function payoneLogClientId(): int
  {
    $clientId = (int)(PayoneDataService::personal()->customerid ?? Service::auth()->getUserId());
    if ($clientId > 0) {
      return $clientId;
    }

    $portalParam = explode('|', (string)PayoneDataService::portal()->param);

    return max(0, (int)($portalParam[4] ?? 0));
  }

  public function initData($data = []): void
  {
    $configData          = OnlineGatewayService::payoneConfig(['typeKey' => Service::request()->_post('configTypeKey')])->getData();
    $configData['param'] .= '|' . rawurlencode($configData['typeKey']);
    PayoneDataService::allInstance(array_merge($configData, $data));
  }

  protected function setView($key = 'payment_payone')
  {
    parent::setView($key);
  }

  protected function getModulePath($modulePath = null)
  {
    return parent::getModulePath('payment\payone');
  }
}
